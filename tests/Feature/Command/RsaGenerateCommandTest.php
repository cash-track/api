<?php

declare(strict_types=1);

namespace Tests\Feature\Command;

use App\Command\RsaGenerateCommand;
use Spiral\Files\Exception\WriteErrorException;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;

class RsaGenerateCommandTest extends TestCase
{
    public function testGenerateFiles(): void
    {
        $files = $this->getMockBuilder(FilesInterface::class)->getMock();
        $files->expects($this->exactly(2))->method('write');

        $command = new RsaGenerateCommand($files, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->run($input, $output);
    }

    public function testMountToFile(): void
    {
        $files = $this->getMockBuilder(FilesInterface::class)->getMock();
        $files->expects($this->exactly(2))->method('exists')->with('file.env')->willReturnOnConsecutiveCalls(true, false);
        $files->expects($this->exactly(1))->method('read')->with('file.env')->willReturnOnConsecutiveCalls('{rsa-public-key}');
        $files->expects($this->exactly(1))->method('write')->with($this->callback(function ($file) {
            $this->assertEquals('file.env', $file);
            return true;
        }), $this->callback(function ($content) {
            $this->assertNotEquals('{rsa-public-key}', $content);
            return true;
        }));

        $command = new RsaGenerateCommand($files, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $input->method('getOption')->willReturnMap([
            ['mount', 'file.env'],
        ]);

        $command->run($input, $output);
    }

    public function testWriteFileThrownException(): void
    {
        $files = $this->getMockBuilder(FilesInterface::class)->getMock();
        $files->expects($this->exactly(2))->method('write')->willThrowException(new WriteErrorException());

        $command = new RsaGenerateCommand($files, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->run($input, $output);
    }

    public function testWriteFileEmptyOutPrefix(): void
    {
        $files = $this->getMockBuilder(FilesInterface::class)->getMock();
        $files->expects($this->exactly(2))->method('write')->with($this->callback(function ($path) {
            return $path === 'directory/prefix-public.key' || $path === 'directory/prefix-private.key';
        }));

        $command = new RsaGenerateCommand($files, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $input->method('getOption')->willReturnMap([
            ['mount', null],
            ['out-prefix', 'prefix'],
            ['out-dir', 'directory'],
        ]);

        $command->run($input, $output);
    }
}
