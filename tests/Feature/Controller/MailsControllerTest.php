<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Controller\MailsController;
use App\Database\EntityHeader;
use App\Database\User;
use App\Mail\TestMail;
use App\Service\Mailer\MailerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Boot\EnvironmentInterface;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class MailsControllerTest extends TestCase
{
    public function testTestDisabledDebugDoNothing(): void
    {
        $mailer = $this->makeMailer();
        $mailer->expects($this->never())->method('send');

        $this->makeController($mailer, UserFactory::make(), debug: false)->test();
    }

    public function testTestEnabledDebugSendMessage(): void
    {
        $user = UserFactory::make();

        $mailer = $this->makeMailer();
        $mailer->expects($this->once())
               ->method('send')
               ->with($this->callback($this->isTestMailFor($user)));

        $this->makeController($mailer, $user, debug: true)->test();
    }

    public function testPreviewDisabledDebugDoNothing(): void
    {
        $mailer = $this->makeMailer();
        $mailer->expects($this->never())->method('render');

        $controller = $this->makeController($mailer, UserFactory::make(), debug: false);

        $this->assertEquals('ok', $controller->preview());
    }

    public function testPreviewEnabledDebugRenderMessage(): void
    {
        $user = UserFactory::make();

        $mailer = $this->makeMailer();
        $mailer->expects($this->once())
               ->method('render')
               ->with($this->callback($this->isTestMailFor($user)))
               ->willReturn('<html>test</html>');

        $controller = $this->makeController($mailer, $user, debug: true);

        $this->assertEquals('<html>test</html>', $controller->preview());
    }

    /**
     * Both routes are `group: 'auth'`, so the actor is always a resolved User — AuthAwareController
     * refuses to construct otherwise.
     */
    private function makeController(MailerInterface $mailer, User $actor, bool $debug): MailsController
    {
        $auth = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $auth->method('getActor')->willReturn($actor);

        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->with('DEBUG')->willReturn($debug);

        return new MailsController($auth, $mailer, $environment);
    }

    private function makeMailer(): MailerInterface
    {
        return $this->getMockBuilder(MailerInterface::class)->getMock();
    }

    private function isTestMailFor(User $user): \Closure
    {
        return function ($mail) use ($user) {
            $this->assertInstanceOf(TestMail::class, $mail);
            $this->assertInstanceOf(EntityHeader::class, $mail->userHeader);
            $this->assertEquals(User::class, $mail->userHeader->role);
            $this->assertEquals(['id' => $user->id], $mail->userHeader->params);

            return true;
        };
    }
}
