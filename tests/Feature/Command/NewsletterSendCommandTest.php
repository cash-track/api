<?php

declare(strict_types=1);

namespace Feature\Command;

use App\Command\NewsletterSendCommand;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;

class NewsletterSendCommandTest extends TestCase
{
    public function testRun(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $repository = $this->getContainer()->get(UserRepository::class);

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'TestMail';

        $this->assertEquals(0, $command->run($input, $output));
    }

    public function testRunTest(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $repository = $this->getContainer()->get(UserRepository::class);

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'TestMail';
        $command->test = 1;

        $this->assertEquals(0, $command->run($input, $output));
    }

    public function testRunDefault(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();

        $result = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result->method('fetchAll')->willReturn([
            ['id' => 1], ['id' => 2], ['blabla' => 3]
        ]);

        $query = $this->getMockBuilder(SelectQuery::class)->getMock();
        $query->method('count')->willReturn(3);
        $query->method('offset')->will($this->returnSelf());
        $query->method('getIterator')->willReturn($result);

        $repository->method('allForNewsletter')->willReturn($query);

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'TestMail';

        $this->assertEquals(0, $command->run($input, $output));
    }

    public function testSendThrowException(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();

        $result = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result->method('fetchAll')->willReturn([['id' => 1]]);

        $query = $this->getMockBuilder(SelectQuery::class)->getMock();
        $query->method('count')->willReturn(1);
        $query->method('offset')->will($this->returnSelf());
        $query->method('getIterator')->willReturn($result);

        $repository->method('allForNewsletter')->willReturn($query);

        $mailer->method('sendNow')->willThrowException(new \RuntimeException('Test exception'));

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'TestMail';
        $command->now = true;

        $this->assertEquals(0, $command->run($input, $output));
    }

    public function testRunMissingMailClass(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'Whenever';

        $this->expectException(\RuntimeException::class);

        $this->assertEquals(0, $command->run($input, $output));
    }

    public function testRunMissingQuery(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();

        $repository->method('allForNewsletter')->willReturn(null);

        $command = new NewsletterSendCommand($repository, $mailer, $logger, 'rsa:gen');
        $command->setContainer($this->getContainer());

        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->mail = 'TestMail';

        $this->expectException(\RuntimeException::class);

        $this->assertEquals(0, $command->run($input, $output));
    }
}
