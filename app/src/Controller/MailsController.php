<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mail\TestMail;
use App\Service\Mailer\MailerInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class MailsController
{
    use PrototypeTrait;

    /**
     * @Route(route="/mails/test", name="mails.test", methods="GET", group="auth")
     *
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @return void
     */
    public function test(MailerInterface $mailer)
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $mailer->send(new TestMail($user));
    }

    /**
     * @Route(route="/mails/preview", name="mails.preview", methods="GET", group="auth")
     *
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @return string
     */
    public function preview(MailerInterface $mailer): string
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        return $mailer->render(new TestMail($user));
    }
}
