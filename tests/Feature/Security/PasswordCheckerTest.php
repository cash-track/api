<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Security\PasswordChecker;
use App\Service\Auth\AuthService;
use Spiral\Validation\ValidatorInterface;
use Tests\Fixtures;
use Tests\TestCase;

class PasswordCheckerTest extends TestCase
{
    public function testVerifyEmptyContext(): void
    {
        $authService = $this->getMockBuilder(AuthService::class)->disableOriginalConstructor()->getMock();
        
        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();

        $validator->method('getContext')->willReturn(null);

        $checker = new PasswordChecker($authService);
        
        $this->assertFalse($checker->check($validator, 'verify', '', Fixtures::string()));
    }
}
