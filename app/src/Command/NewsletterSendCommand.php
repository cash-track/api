<?php

declare(strict_types=1);

namespace App\Command;

use App\Database\EntityHeader;
use App\Database\User;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Console\Command;
use Spiral\Console\Attribute\Argument;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Attribute\Option;

#[AsCommand(name: 'newsletter:send', description: 'Send newsletter email messages to many users')]
class NewsletterSendCommand extends Command
{
    #[Argument(description: 'Class name of mail to send [example: WelcomeMail]')]
    public string $mail = '';

    #[Option(shortcut: 'b', description: 'Amount of messages per batch')]
    public int $batch = 10;

    #[Option(name: 'all', description: 'Include all users no matter email confirmation [only confirmed by default]')]
    public bool $forAll = false;

    #[Option(name: 'unconfirmed', description: 'Include only users with unconfirmed email address [false by default]')]
    public bool $onlyUnconfirmed = false;

    #[Option(name: 'now', description: 'Send messages now bypassing queue [sending in queue by default]')]
    public bool $now = false;

    #[Option(name: 'test', description: 'User ID to send test mail')]
    public int $test = 0;

    private int $total = 0;

    private int $counter = 0;

    private string $mailClass = "\\App\\Mail\\";

    public function __construct(
        private readonly UserRepository $repository,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function perform(): int
    {
        $this->mailClass .= $this->mail;

        if ($this->mail === '' || !class_exists($this->mailClass)) {
            throw new \RuntimeException('Mail class name is required to be a correct class name in \App\Mail');
        }

        $query = $this->repository->allForNewsletter($this->getEmailConfirmedParam(), $this->test);

        if ($query === null) {
            throw new \RuntimeException('Unable to resolve query instance');
        }

        $this->total = $query->count();

        $params = "[all={$this->forAll}, unconfirmed={$this->onlyUnconfirmed}, now={$this->now}, test={$this->test}]";
        $this->info("Sending {$this->mail} to {$this->total} users {$params}");

        $query->limit($this->batch);

        $offset = 0;

        while ($this->counter < $this->total) {
            foreach ($query->offset($offset)->getIterator()->fetchAll() as $user) {
                $this->processUser($user);
            }

            $offset += $this->batch;
        }

        $this->info("Done for {$this->counter} users");

        return self::SUCCESS;
    }

    protected function processUser(array $user): void
    {
        $this->counter++;

        $log = "[{$this->counter}/{$this->total}]";

        $userId = $user['id'] ?? null;

        if ($userId === null) {
            $this->warning('Unable to resolve userId from array ' . print_r($user, true));
            return;
        }

        $this->info("{$log} Sending email to {$userId}");

        try {
            $this->send($userId);
        } catch (\Throwable $throwable) {
            $this->warning("{$log} Error: {$throwable->getMessage()}");

            $this->logger->error("Unable to send email to user: {$throwable->getMessage()}", [
                'user' => $user,
                'error' => $throwable->getTraceAsString(),
            ]);
        }
    }

    protected function send(int $userId): void
    {
        $header = new EntityHeader(User::class, ['id' => $userId]);

        /**
         * @psalm-suppress InvalidStringClass
         * @var \App\Service\Mailer\Mail $mail
         */
        $mail = new $this->mailClass($header);

        if ($this->now) {
            $this->mailer->sendNow($mail);
        } else {
            $this->mailer->send($mail);
        }
    }

    private function getEmailConfirmedParam(): ?bool
    {
        return $this->forAll ? null : !$this->onlyUnconfirmed;
    }
}
