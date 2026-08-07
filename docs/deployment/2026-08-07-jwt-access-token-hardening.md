# JWT access token hardening

Adds RS256 support for access tokens (HS256 remains the default), server-side token
revocation on logout via a Redis blacklist, and graceful degradation when Redis is down.

## TL;DR

This branch is safe to deploy as-is. No secrets need to change for the deploy itself:

- `ACCESS_TOKEN_PUBLIC_KEY` / `ACCESS_TOKEN_PRIVATE_KEY` are unset in every environment today,
  so `TokenStorage` falls back to HS256 with the existing `JWT_SECRET` — identical to current
  behavior, just now with revocation support added on top.
- Nothing new is required to be present. Nothing hard-fails if it's missing.
- This is **not** a coordinated cutover. Deploy the code first, enable RS256 later (next
  section), on your own schedule.

The one thing this deploy does change immediately: **logout now actually revokes tokens**
(previously a no-op — `TokenStorage::delete()` was a `// TODO` stub). And a Redis outage now
degrades auth/rate-limiting instead of crashing them (see "Redis outage behavior" below).

Deploying the code alone is not enough — RoadRunner keeps old workers running until told
otherwise:

```
./rr reset
```

`.rr.yaml` has no file watcher configured, so skip this and you'll keep serving the previous
build. (The tester hit this.)

## Enabling RS256 for access tokens

Whenever you're ready — this is independent of the code deploy above.

```bash
openssl genrsa -out access-token-private.pem 2048
openssl rsa -in access-token-private.pem -pubout -out access-token-public.pem

base64 -i access-token-private.pem | tr -d '\n' # -> ACCESS_TOKEN_PRIVATE_KEY
base64 -i access-token-public.pem  | tr -d '\n' # -> ACCESS_TOKEN_PUBLIC_KEY
```

Set both as env vars (same base64-encoded-PEM convention as the existing
`JWT_PUBLIC_KEY`/`JWT_PRIVATE_KEY`):

- `ACCESS_TOKEN_PUBLIC_KEY`
- `ACCESS_TOKEN_PRIVATE_KEY`

**Generate a keypair dedicated to access tokens — do not reuse `JWT_PUBLIC_KEY`/
`JWT_PRIVATE_KEY`.** That pair signs refresh tokens and is unrelated to this change. Sharing a
keypair between the two means a leaked access-token key also lets an attacker mint refresh
tokens, and vice versa; keeping them separate bounds the blast radius of either leaking.

Once both vars are set and the app restarts, new access tokens sign with RS256 automatically —
no other config change needed.

### JWT_SECRET removal — timing trap

Do not remove `JWT_SECRET` immediately after setting the RS256 keys. Access tokens issued
*before* the RS256 keys were set are still HS256 and still valid until they expire — pulling
`JWT_SECRET` breaks every one of those sessions immediately (`load()` returns null for HS256
tokens once the secret is gone, i.e. an immediate 401 on the user's next request).

**Wait at least `JWT_TTL` seconds after the RS256 deploy before removing `JWT_SECRET`.**
Default `JWT_TTL` is 3600 (1 hour). If you've overridden `JWT_TTL`, use that value instead.

## JWT_ISSUER / JWT_AUDIENCE

Optional. Default to `https://api.cash-track.app` in both cases — correct for production,
no action needed there.

For staging, set both to the staging API URL so `iss`/`aud` in issued tokens reflect where they
were actually issued.

## New Redis dependency: logout blacklist

Logout now writes a blacklist entry for the revoked token(s) (access, and refresh if one was
supplied).

- Keyspace: `CT:blacklist:jti:*` (`CT:` is the standing connection-level prefix from
  `app/config/redis.php`; `blacklist:jti:` + the token's `jti` is the key).
- TTL: set to whatever time remains on the token, capped at `JWT_TTL` for access-token entries
  and `JWT_REFRESH_TTL` (default 604800s, 7 days) for refresh-token entries.
- Self-expiring and bounded — every key TTLs out on its own; nothing to prune or monitor for
  unbounded growth.

## Redis outage behavior

This differs from before this branch — previously a Redis outage on the auth path either wasn't
possible (revocation didn't exist) or crashed the request. Now:

- **Auth (token verification + logout revocation)**: fails open. Revocation is not enforced
  during the outage (a blacklisted token could still verify), and logout silently skips the
  blacklist write. Nothing is logged from this path specifically — see below for what to watch
  instead.
- **Rate limiting**: fails open (requests are not throttled) during the outage. This path *does*
  log at `error` level, but only for the "was connected, dropped mid-command" case, not a
  sustained outage — see below.
- **Passkey endpoints**: return `503` — passkey ceremonies have no degraded mode.
- **Self-heals automatically**: the shared Redis client retries on a 5-second cooldown
  (`ReconnectingRedis::RECONNECT_COOLDOWN_SECONDS`) and starts working again within ~5s of Redis
  coming back, with no worker restart needed.

What to grep for:

```
# The actual heartbeat of the outage — fires roughly every 5s for its entire duration:
grep "Connection to a Redis instance failed" runtime/logs/app.log

# Recovery:
grep "Connection to a Redis instance has been established" runtime/logs/app.log
```

Note the `error`-level lines mentioned above ("Rate limiting is unavailable; failing open",
"Passkey challenge storage is unavailable") only fire on a connected-client-then-drops-mid-command
blip, not the common case of a fully-down Redis — don't rely on them as the primary outage
signal, use the `emergency`-level connection-failed line above instead.

## Verifying RS256 took effect

1. Log in, grab the fresh access token, decode its header:
   ```bash
   echo '<token>' | cut -d. -f1 | base64 -d
   ```
   Confirm `"alg":"RS256"` (was `"HS256"`).
2. Check the JWKS endpoint returns a populated key set:
   ```bash
   curl https://<api-host>/.well-known/jwks.json
   ```
   Expect one entry with `kty: RSA`, `use: sig`, `alg: RS256`, a non-empty `kid`, and populated
   `n`/`e`. An empty `{"keys": []}` means the app hasn't picked up the RSA keypair — check
   `ACCESS_TOKEN_PUBLIC_KEY`/`ACCESS_TOKEN_PRIVATE_KEY` are actually set and `./rr reset` ran.

## Rollback

If you revert this code after RS256 tokens have already been issued (RS256 keys were set and
the app has been running with them for a while):

- **Access tokens issued under RS256 stop verifying.** The rolled-back code has no RS256 path
  at all, so `load()` returns null for them — those users get a 401 on their next request and
  have to log in again. There's no way around this short of not rolling back.
- **Refresh tokens are unaffected.** They were already RS256 before this branch
  (`JWT_PUBLIC_KEY`/`JWT_PRIVATE_KEY`), and that keypair/algorithm didn't change — refresh flow
  keeps working across the rollback.
- **Logout reverts to a no-op**, same as before this branch — not a new regression, just losing
  the revocation this branch added.

Rolling back before ever setting the RS256 keys is a pure no-op — nothing to clean up.
