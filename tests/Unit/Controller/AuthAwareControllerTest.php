<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use App\Controller\AuthAwareController;
use App\Database\User;
use App\Exception\AuthenticationRequiredException;
use App\Exception\UnconfirmedProfileException;
use Spiral\Auth\AuthContextInterface;
use Tests\TestCase;

class AuthAwareControllerTest extends TestCase
{
    public function testConstructRejectsMissingActor(): void
    {
        $this->expectException(AuthenticationRequiredException::class);

        $this->makeController(null);
    }

    public function testConstructRejectsForeignActor(): void
    {
        $this->expectException(AuthenticationRequiredException::class);

        $this->makeController(new \stdClass());
    }

    public function testConstructAssignsUser(): void
    {
        $user = new User();

        $this->assertSame($user, $this->makeController($user)->actor());
    }

    public function testVerifyIsProfileConfirmedPassesForConfirmedProfile(): void
    {
        $this->expectNotToPerformAssertions();

        $user = new User();
        $user->isEmailConfirmed = true;

        $this->makeController($user)->verify();
    }

    public function testVerifyIsProfileConfirmedRejectsUnconfirmedProfile(): void
    {
        $this->expectException(UnconfirmedProfileException::class);

        $this->makeController(new User())->verify();
    }

    /**
     * An unresolved actor must never reach verifyIsProfileConfirmed() — that would answer 403
     * "profile not confirmed" to a caller who simply is not authenticated.
     */
    private function makeController(?object $actor)
    {
        $auth = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $auth->method('getActor')->willReturn($actor);

        return new class ($auth) extends AuthAwareController {
            public function actor(): User
            {
                return $this->user;
            }

            public function verify(): void
            {
                $this->verifyIsProfileConfirmed();
            }
        };
    }
}
