<?php

declare(strict_types=1);

namespace Tests\Feature\Command;

use App\Command\EncryptKeyGenerate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;

class EncryptKeyGenerateTest extends TestCase
{
    public function testRun(): void
    {
        $command = new EncryptKeyGenerate('rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $this->assertEquals(0, $command->run($input, $output));
    }
}
