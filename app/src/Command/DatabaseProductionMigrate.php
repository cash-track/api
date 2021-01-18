<?php

declare(strict_types=1);

namespace App\Command;

use Ramsey\Uuid\Uuid;
use Spiral\Console\Command;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;

class DatabaseProductionMigrate extends Command
{
    protected const NAME = 'db:prod:migrate';

    protected const DESCRIPTION = '';

    protected const ARGUMENTS = [];

    protected const OPTIONS = [];

    protected $users = [
        // oldID => newID
    ];

    protected $oldWallets = [
        // oldID => newID
    ];

    /**
     * @var \Spiral\Database\DatabaseManager
     */
    private $dbal;

    /**
     * DatabaseProductionCheck constructor.
     *
     * @param \Spiral\Database\DatabaseManager $dbal
     * @param string|null $name
     */
    public function __construct(DatabaseManager $dbal, string $name = null)
    {
        parent::__construct($name);

        $this->dbal = $dbal;
    }

    /**
     * Perform command
     */
    protected function perform(): void
    {
        $db = $this->dbal->database('default');
        $oldDb = $this->dbal->database('old');

        $this->writeln('Moving users...');
        foreach ($oldDb->query('SELECT * FROM users')->fetchAll() as $oldUser) {
            try {
                $this->moveUser($db, $oldUser);
            } catch (\Exception $exception) {
                $str = print_r($oldUser);
                $this->output->writeln("Unable to move user.\n{$str}\n{$exception->getMessage()}");

                continue;
            }
        }

        $this->writeln('');

        $this->writeln('Moving balances to wallets...');
        foreach ($oldDb->query('SELECT * FROM balances')->fetchAll() as $balance) {
            $db->begin();

            try {
                $walletID = $this->moveBalanceToWallet($db, $balance);
            } catch (\Exception $exception) {
                $db->rollback();

                $str = print_r($balance);
                $this->output->writeln("Unable to move wallet.\n{$str}\n{$exception->getMessage()}");

                continue;
            }

            if ($walletID === 0) {
                continue;
            }

            foreach ($oldDb->query("SELECT * FROM trans WHERE balance_id = ?", [$balance['id']])->fetchAll() as $trans) {
                try {
                    $this->moveTransToCharge($db, $trans);
                } catch (\Exception $exception) {
                    $str = print_r($trans);
                    $this->output->writeln("Unable to move trans.\n{$str}\n{$exception->getMessage()}");

                    continue;
                }
            }

            foreach ($oldDb->query("SELECT * FROM user_balance WHERE balance_id = ?", [$balance['id']])->fetchAll() as $members) {
                try {
                    $this->moveBalanceMembers($db, $members);
                } catch (\Exception $exception) {
                    $str = print_r($members);
                    $this->output->writeln("Unable to move balance members.\n{$str}\n{$exception->getMessage()}");

                    continue;
                }
            }

            $db->commit();
        }

        $this->writeln('');

        $this->writeln('Recalculating wallets total...');

        foreach ($this->oldWallets as $oldWalletID => $newWalletID) {
            try {
                $this->recalculateWalletsTotal($db, $newWalletID);
            } catch (\Exception $exception) {
                $this->output->writeln("Unable to recalculate wallet {$newWalletID} total.\n{$exception->getMessage()}");
                continue;
            }
        }
    }

    protected function moveUser(DatabaseInterface $db, array $user)
    {
        $oldID = (int) ($user['id'] ?? 0);
        $email = $user['email'] ?? '';

        if (empty($oldID) || empty($email)) {
            $this->output->writeln("Empty user given, skipping:\n".print_r($user));
            return;
        }

        $this->output->writeln("Moving old user ID {$oldID}, email {$email}");

        $existingUser = $db->query("SELECT * FROM users WHERE email = '{$email}' LIMIT 1")->fetchAll();

        if (count($existingUser)) {
            $existingUser = $existingUser[0];
            $this->output->writeln('  - exist: true, ID: '.$existingUser['id'] ?? 0);
        } else {
            $this->output->writeln('  - exist: false, creating...');
            $existingUser = $this->createNewUserByOld($db, $user);
            $this->output->writeln('  - exist: true, created ID: '.$existingUser['id'] ?? 0);
        }

        $this->users[$oldID] = (int) $existingUser['id'];
    }

    protected function createNewUserByOld(DatabaseInterface $db, array $user): array
    {
        if (($db->query("SELECT email FROM users WHERE nick_name = '{$user['nick']}'")->fetch()['email'] ?? $user['email']) !== $user['email']) {
            $user['nick'] = $user['nick'] . '-' . bin2hex(random_bytes(3));
            $this->writeln('  - warn: nickname is already claimed, new nickname: ' . $user['nick']);
        }

        if (empty($user['nick'])) {
            $user['nick'] = bin2hex(random_bytes(6));
        }

        $newID = $db->insert()
                    ->into('users')
                    ->columns([
                        'name',
                        'last_name',
                        'nick_name',
                        'email',
                        'default_currency_code',
                        'password',
                        'created_at',
                        'updated_at',
                        'is_email_confirmed',
                    ])
                    ->values([
                        $user['name'],
                        $user['last_name'] ?? '',
                        $user['nick'],
                        $user['email'],
                        'USD',
                        password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID),
                        $user['created_at'],
                        $user['updated_at'],
                        0,
                    ])
                    ->run();

        $newUser = $db->query("SELECT * FROM users WHERE id = {$newID}")->fetchAll();

        if (is_array($newUser) && !empty($newUser[0]['id'] ?? 0)) {
            return $newUser[0];
        }

        throw new \Exception("Unable to create new user. ID given: {$newID}. Data by ID: ".print_r($newUser));
    }

    protected function moveBalanceToWallet(DatabaseInterface $db, array $balance): int
    {
        $oldID = (int) ($balance['id'] ?? 0);
        $slug = $balance['slug'] ?? '';

        if (empty($oldID)) {
            $this->output->writeln("Empty balance given, skipping:\n".print_r($balance));
            return 0;
        }

        if (empty($balance['title'] ?? '')) {
            $balance['title'] = "Old Wallet";
        }

        if (empty($slug)) {
            $slug = bin2hex(random_bytes(6));
        }

        $this->output->writeln("Moving balance ID {$oldID}, slug {$slug}");

        if ($db->query("SELECT * FROM wallets WHERE slug = '{$slug}'")->rowCount()) {
            $slug .= "-" . bin2hex(random_bytes(6));
            $this->writeln("  - slug: already exist, new one: {$slug}");
        }

        $newID = $db->insert()
                    ->into('wallets')
                    ->columns([
                        'name',
                        'slug',
                        'is_active',
                        'is_archived',
                        'is_public',
                        'default_currency_code',
                        'created_at',
                        'updated_at',
                        'total_amount',
                    ])
                    ->values([
                        $balance['title'],
                        $slug,
                        $balance['is_active'] === '1',
                        $balance['is_active'] !== '1',
                        false,
                        'USD',
                        $balance['created_at'],
                        $balance['updated_at'],
                        0.0,
                    ])
                    ->run();

        $newWallet = $db->query("SELECT * FROM wallets WHERE id = {$newID}")->fetchAll();

        if (is_array($newWallet) && !empty($newWallet[0]['id'] ?? 0)) {
            $this->writeln("  - created new wallet ID {$newID}");
            $this->oldWallets[$oldID] = (int) $newID;
            return (int) $newID;
        }

        throw new \Exception("Unable to create new wallet. ID given: {$newID}. Data by ID: ".print_r($newWallet));
    }

    protected function moveTransToCharge(DatabaseInterface $db, array $trans)
    {
        $oldID = $trans['id'] ?? 0;
        $newID = Uuid::uuid4()->toString();
        $balanceID = $trans['balance_id'] ?? 0;
        $userID = $trans['user_id'] ?? 0;

        if (empty($balanceID) || empty($userID)) {
            $this->output->writeln("Empty transaction given, skipping:\n".print_r($trans));
            return;
        }

        if (! array_key_exists($userID, $this->users)) {
            $this->output->writeln("Unable to move trans for not moved user, skipping:\n".print_r($trans));
            return;
        }

        if (! array_key_exists($balanceID, $this->oldWallets)) {
            $this->output->writeln("Unable to move trans for not moved wallet, skipping:\n".print_r($trans));
            return;
        }

        $this->output->writeln("Moving charge ID {$oldID}, newID {$newID}");

        if (empty($trans['title'] ?? '')) {
            $trans['title'] = 'Old charge';
        }

        if (empty($trans['description'] ?? '')) {
            $trans['description'] = '';
        }



//        $this->batchInsert->values([
//            $newID,
//            $this->oldWallets[$balanceID],
//            $this->users[$userID],
//            $trans['type'],
//            (float) ($trans['amount'] ?? 0),
//            $trans['title'],
//            $trans['description'],
//            $trans['created_at'],
//            $trans['updated_at'],
//        ]);

        $db->insert()
           ->into('charges')
           ->columns([
               'id',
               'wallet_id',
               'user_id',
               'type',
               'amount',
               'title',
               'description',
               'created_at',
               'updated_at',
           ])
           ->values([
               $newID,
               $this->oldWallets[$balanceID],
               $this->users[$userID],
               $trans['type'],
               (float) ($trans['amount'] ?? 0),
               $trans['title'],
               $trans['description'],
               $trans['created_at'],
               $trans['updated_at'],
           ])
           ->run();

        $newCharge = $db->query("SELECT * FROM charges WHERE id = ?", [$newID])->fetchAll();

        if (is_array($newCharge) && !empty($newCharge[0]['id'] ?? '')) {
            $this->writeln("  - created new charge ID {$newID}");

            return;
        }

        throw new \Exception("Unable to create new charge. ID given: {$newID}. Data by ID: ".print_r($newCharge));
    }

    protected function moveBalanceMembers(DatabaseInterface $db, array $members)
    {
        $oldUserID = (int) ($members['user_id'] ?? 0);
        $oldWalletID = (int) ($members['balance_id'] ?? 0);

        if (empty($oldUserID) || empty($oldWalletID)) {
            $this->output->writeln("Empty members given, skipping:\n".print_r($members));
            return;
        }

        $newUserID = (int) ($this->users[$oldUserID] ?? 0);
        $newWalletID = (int) ($this->oldWallets[$oldWalletID] ?? 0);

        if (empty($newUserID)) {
            $this->output->writeln("No new user by old ID {$oldUserID}, skipping:\n".print_r($members));
            return;
        }

        if (empty($newWalletID)) {
            $this->output->writeln("No new wallet by old ID {$oldWalletID}, skipping:\n".print_r($members));
            return;
        }

        if ($db->query("SELECT * FROM user_wallets WHERE wallet_id = ? AND user_id = ?", [$newWalletID, $newUserID])->rowCount()) {
            $this->writeln("User {$newUserID} is already member of wallet {$newWalletID}");
            return;
        }

        $this->output->writeln("Moving member [{$oldUserID} => {$newUserID}] of wallet [{$oldWalletID} => {$newWalletID}]");

        $db->insert()
           ->into('user_wallets')
           ->columns([
               'wallet_id',
               'user_id',
           ])
           ->values([
               $newWalletID,
               $newUserID,
           ])
           ->run();

        if ($db->query("SELECT * FROM user_wallets WHERE wallet_id = ? AND user_id = ?", [$newWalletID, $newUserID])->rowCount()) {
            return;
        }

        throw new \Exception("Unable to create new member of wallet.");
    }

    protected function recalculateWalletsTotal(DatabaseInterface $db, int $walletID)
    {
        $income = (int) ($db->query("SELECT SUM(amount) AS 'sum' FROM charges WHERE wallet_id = ? AND type = ?", [$walletID, '+'])->fetchAll()[0]['sum'] ?? 0);
        $expense = (int) ($db->query("SELECT SUM(amount) AS 'sum' FROM charges WHERE wallet_id = ? AND type = ?", [$walletID, '-'])->fetchAll()[0]['sum'] ?? 0);

        $total = (float) ($income - $expense);

        $this->writeln("Updating total for wallet {$walletID} is {$total} where income {$income} and expense {$expense}");

        $db->table('wallets')->update(['total_amount' => $total])->where('id', $walletID)->run();
    }
}
