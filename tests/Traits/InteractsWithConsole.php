<?php

declare(strict_types=1);

namespace Tests\Traits;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait InteractsWithConsole
{
    public function runCommand(string $command, array $args = []): string
    {
        $input = new ArrayInput($args);
        $output = new BufferedOutput();

        $this->app->console()->run($command, $input, $output);

        return $output->fetch();
    }

    public function runCommandDebug(string $command, array $args = [], OutputInterface $output = null): string
    {
        $input = new ArrayInput($args);
        $output = $output ?? new BufferedOutput();
        $output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);

        $this->app->console()->run($command, $input, $output);

        return $output->fetch();
    }

    public function runCommandVeryVerbose(string $command, array $args = [], OutputInterface $output = null): string
    {
        $input = new ArrayInput($args);
        $output = $output ?? new BufferedOutput();
        $output->setVerbosity(BufferedOutput::VERBOSITY_DEBUG);

        $this->app->console()->run($command, $input, $output);

        return $output->fetch();
    }
}
