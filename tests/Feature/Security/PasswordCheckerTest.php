<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Database\User;
use App\Security\PasswordChecker;
use App\Service\Auth\AuthService;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Spiral\Validation\ValidatorInterface;
use Tests\Fixtures;
use Tests\TestCase;

class PasswordCheckerTest extends TestCase
{
    public function testVerifyEmptyValue(): void
    {
        $authService = $this->getMockBuilder(AuthService::class)->disableOriginalConstructor()->getMock();

        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();

        $validator->method('hasValue')->willReturn(false);

        $checker = new PasswordChecker($authService, $orm);

        $this->assertFalse($checker->check($validator, 'verify', '', Fixtures::string(), [User::class]));
    }

    public function testVerifyEmptyEntity(): void
    {
        $select = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $select->method('fetchOne')->willReturn(null);

        $repository = $this->getMockBuilder(Select\Repository::class)->disableOriginalConstructor()->getMock();
        $repository->method('select')->willReturn($select);

        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $orm->method('getRepository')->willReturn($repository);

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();

        $validator->method('hasValue')->willReturn(true);
        $validator->method('getValue')->willReturn(1);

        $authService = $this->getMockBuilder(AuthService::class)->disableOriginalConstructor()->getMock();

        $checker = new PasswordChecker($authService, $orm);

        $this->assertFalse($checker->check($validator, 'verify', '', Fixtures::string(), [User::class]));
    }
}
