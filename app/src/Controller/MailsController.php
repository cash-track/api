<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mail\TestMail;
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

    /**
     * @Route(route="/mails/test", name="mails.test", methods="GET", group="auth")
     *
     * @return void
     */
    public function test()
    {
        if (! $this->isDebug()) {
            return;
        }

        $this->mailer->send(new TestMail($this->user));
    }

    /**
     * @Route(route="/mails/preview", name="mails.preview", methods="GET", group="auth")
     *
     * @return string
     */
    public function preview(): string
    {
        if (! $this->isDebug()) {
            return 'ok';
        }

        return $this->mailer->render(new TestMail($this->user));
    }

    private function isDebug(): bool
    {
        return (bool) $this->environment->get('DEBUG', false);
    }
}
