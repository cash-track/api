# Gateway → API trust boundary

Stops clients from forging the headers only the gateway is supposed to set, by adding a
shared `X-Gateway-Secret` between the two services. Spans three repos: `api`, `gateway`,
`infra`.

## TL;DR

The code is safe to deploy on its own, in either order, with no secret configured. Do that
first. Turning the secret on is a **separate, later step with a required ordering** — get it
backwards and you take a production rate-limiting outage.

Two things to know before you touch anything:

1. **Create the 1Password field before the infra deploy.** The templates now reference
   `op://cash-track-prod/common/GATEWAY_SECRET`. If that field does not exist, `op inject`
   fails and the whole play aborts (it fails before shipping anything, so there is no partial
   state — but the deploy does not go through).
2. **Ansible restarts `api` before `gateway`.** Handlers run in definition order, and
   `Restart api` is defined first in `roles/compose-render/handlers/main.yml`. On a deploy
   that sets the secret in both env files at once, that ordering is exactly the broken state
   described below, for as long as the two restarts take.

## What changes the moment the code deploys

This happens with `GATEWAY_SECRET` unset — i.e. immediately, on the plain code deploy:

- Every `X-Internal-*` header arriving from a client is stripped before routing. The gateway
  never sets these, so any that arrive are forged by definition.
- `X-Gateway-Secret` is stripped before routing, always, in every branch.
- `AuthMiddleware` now **overwrites** `X-Internal-UserId` instead of appending to it.
- `RateLimitMiddleware::fetchIp()` falls back to `REMOTE_ADDR` when no IP header is present,
  instead of returning an empty string.

That alone closes the rate-limit bypass on unauthenticated routes (login / register /
password reset), where a rotating forged `X-Internal-UserId` previously minted a fresh Redis
bucket per request and switched the rule from `GuestRule` (100/60s) to `UserRule` (1000/60s).
It also closes a 500 on five endpoints that a forged header could trigger.

Client IP headers are **not** touched in this state. That part needs the secret.

## The four states

`GATEWAY_SECRET` exists independently on each side. Only three of the four combinations are
safe.

| API | Gateway | Result |
|---|---|---|
| unset | unset | **Safe.** Forged `X-Internal-*` stripped. IP headers passed through untouched. This is the state the code deploy lands in. |
| unset | set | **Safe, no-op.** Gateway sends the header; API ignores it and strips it. Useful as a staging step. |
| set | set, **same value** | **Full protection.** Gateway traffic trusted, its IP headers honoured. Anything reaching the API directly gets its IP headers rewritten to its real peer address. |
| set | unset **or different value** | **Broken.** Every gateway request is untrusted, so its IP headers are rewritten to `REMOTE_ADDR` — which over the Compose network is the gateway container's own address. All traffic from all users collapses into one shared `guest:<gateway-ip>` bucket at 100 requests/60s. |

That last row is the entire reason this document exists.

## Recommended sequence

### Step 0 — create the secret (before any deploy)

```bash
openssl rand -base64 32
```

Store it in 1Password as vault `cash-track-prod` → item `common` → field `GATEWAY_SECRET`.
Any non-empty string works; it is compared with `hash_equals`, not parsed.

Do this first regardless of which rollout you pick below — the infra templates reference the
field, and `op inject` will fail the play without it.

### Step 1 — deploy the code

Deploy `api` and `gateway` in either order. Nothing is coordinated yet because neither side
has the secret in its environment. Verify normal traffic before continuing.

### Step 2 — turn the secret on

Pick one of the two options.

**Option A — two-pass deploy (zero window, recommended).**

Because `set gateway / unset api` is a safe no-op, you can stage it:

1. Comment out the `GATEWAY_SECRET` line in
   `ansible/roles/compose-render/templates/api.env.tpl`, leaving the `gateway.env.tpl` one in
   place. Deploy. Only the gateway restarts; it now sends the header and the API ignores it.
2. Uncomment the API line. Deploy. Only the API's env file changed, so only the API restarts —
   and the gateway is already sending the header, so it comes back up trusted immediately.

**Option B — single deploy (accept a brief window).**

Deploy the branch as-is. Both env files change, both handlers fire, and the API restarts
first. Between the API coming back up with the secret configured and the gateway restarting
with the secret to send, gateway traffic is untrusted and rate limiting is globally capped at
100 requests/60s. The window is bounded by how long a `docker compose restart gateway` takes
after the API restart completes — on the order of seconds — but during it, requests over that
cap get a `429`.

At current traffic this may well stay under the cap and be a non-event. It is your call;
Option A removes the question entirely at the cost of one extra deploy.

## Verifying it took effect

Rate limiting is the observable signal, so read Redis directly rather than inferring from
response headers:

```bash
docker compose exec redis redis-cli --scan --pattern 'CT:rate-limit:*'
```

**Correct (secret matching on both sides):** keys carry real client addresses, e.g.
`CT:rate-limit:user:1-203.0.113.10`.

**Broken (state four above):** every key is the gateway container's address, e.g. a single
`CT:rate-limit:guest:172.18.0.7` climbing fast. If you see that, the two `GATEWAY_SECRET`
values disagree — the fastest fix is to restart the gateway so it picks the value up, or
unset it on the API to fall back to the safe passthrough state.

Confirm the direct-to-API path is actually closed:

```bash
for i in $(seq 1 5); do
  curl -s -o /dev/null -D - -X POST https://api.cash-track.app/auth/login \
    -H 'Content-Type: application/json' \
    -H "X-Internal-UserId: $RANDOM" \
    -d '{"email":"nobody@example.com","password":"wrong"}' | grep -i 'x-ratelimit'
done
```

`X-RateLimit-Limit` must read `100` (`GuestRule`) and `X-RateLimit-Remaining` must decrement
across the loop. A limit of `1000`, or a counter that resets each iteration, means the forged
header is still being honoured.

## Also in this deploy

`gateway.env.tpl` gains an explicit `TRUSTED_PROXIES` line set to the same RFC1918 + loopback
value the Go code already defaults to. It is a no-op that makes an implicit default visible
and tunable — the previous change that introduced the setting never added it to the template.
It does change the rendered `gateway.env`, so it triggers a gateway restart on the first
deploy after this branch.

## Rollback

Reverse the enabling order — **remove the secret from the API first**, then the gateway.
Removing it from the gateway first puts you straight into the broken state.

Rolling back the code itself is clean whether or not the secret was ever set: the middleware
simply stops running, and the API returns to its prior behaviour of trusting whatever headers
arrive. That reopens the rate-limit bypass, so treat a rollback as a reason to reapply.

## What this does not fix

The API is still publicly routed at `api.cash-track.app`
(`infra/compose/compose.app.yml`, the `api` Traefik router), so requests can still reach it
without passing through the gateway — this change makes those requests harmless rather than
preventing them.

The gateway reaches the API over the Compose network (`API_URL=http://api:8080`) and does not
need that public router. Deleting it would close the surface at the edge instead of in
application code. That is a separate change, and it needs confirming that nothing else depends
on direct API access first.
