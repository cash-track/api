<?php

declare(strict_types=1);

namespace App\Command;

use Spiral\Console\Command;
use Spiral\Database\DatabaseManager;

class DatabaseProductionCheck extends Command
{
    protected const NAME = 'db:prod:check';

    protected const DESCRIPTION = '';

    protected const ARGUMENTS = [];

    protected const OPTIONS = [];

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
        $this->output->writeln("Checking connection to primary database...");
        $db = $this->dbal->database('default');

        $out = $db->query('SELECT COUNT(*) AS `count` FROM users')->fetch()['count'] ?? 0;
        $this->output->writeln("Users amount: {$out}");

        $this->output->writeln("Checking connection to old database...");
        $oldDb = $this->dbal->database('old');

        $out = $oldDb->query('SELECT COUNT(*) AS `count` FROM users')->fetch()['count'] ?? 0;
        $this->output->writeln("Users amount: {$out}");
    }
}
