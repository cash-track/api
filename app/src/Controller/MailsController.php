<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mail\TestMail;
use App\Service\Mailer\Mail;
use App\Service\Mailer\MailerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Router\Annotation\Route;

final class MailsController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        protected MailerInterface $mailer,
        protected EnvironmentInterface $environment,
    ) {
        parent::__construct($auth);
    }

    private function getMail(): Mail
    {
        return new TestMail($this->user->getEntityHeader());
    }

    #[Route(route:'/mails/test', name: 'mails.test', methods: 'GET', group: 'auth')]
    public function test(): void
    {
        if (! $this->isDebug()) {
            return;
        }

        $this->mailer->send($this->getMail());
    }

    #[Route(route:'/mails/preview', name: 'mails.preview', methods: 'GET', group: 'auth')]
    public function preview(): string
    {
        if (! $this->isDebug()) {
            return 'ok';
        }

        return $this->mailer->render($this->getMail());
    }

    private function isDebug(): bool
    {
        return (bool) $this->environment->get('DEBUG', false);
    }
}
