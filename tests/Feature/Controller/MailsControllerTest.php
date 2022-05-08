<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Controller\MailsController;
use App\Service\Mailer\MailerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\AuthScope;
use Spiral\Boot\EnvironmentInterface;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class MailsControllerTest extends TestCase
{
    public function testTestDisabledDebugDoNothing(): void
    {
        $authContext = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $authContext->method('getActor')->willReturn(null);

        $this->getContainer()->bind(AuthContextInterface::class, fn () => $authContext);
        $auth = $this->getContainer()->get(AuthScope::class);

        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->never())->method('send');

        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->with('DEBUG')->willReturn(false);

        $controller = new MailsController($auth, $mailer, $environment);
        $controller->test();
    }

    public function testTestEnabledDebugSendMessage(): void
    {
        $user = UserFactory::make();

        $authContext = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $authContext->method('getActor')->willReturn($user);

        $this->getContainer()->bind(AuthContextInterface::class, fn () => $authContext);
        $auth = $this->getContainer()->get(AuthScope::class);

        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->once())->method('send');

        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->with('DEBUG')->willReturn(true);

        $controller = new MailsController($auth, $mailer, $environment);
        $controller->test();
    }

    public function testPreviewDisabledDebugDoNothing(): void
    {
        $authContext = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $authContext->method('getActor')->willReturn(null);

        $this->getContainer()->bind(AuthContextInterface::class, fn () => $authContext);
        $auth = $this->getContainer()->get(AuthScope::class);

        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->never())->method('render');

        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->with('DEBUG')->willReturn(false);

        $controller = new MailsController($auth, $mailer, $environment);
        $controller->preview();
    }

    public function testPreviewEnabledDebugRenderMessage(): void
    {
        $user = UserFactory::make();

        $authContext = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $authContext->method('getActor')->willReturn($user);

        $this->getContainer()->bind(AuthContextInterface::class, fn () => $authContext);
        $auth = $this->getContainer()->get(AuthScope::class);

        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->once())->method('render');

        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->with('DEBUG')->willReturn(true);

        $controller = new MailsController($auth, $mailer, $environment);
        $controller->preview();
    }
}
