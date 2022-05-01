<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Profile;

use App\Service\UserService;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\AuthAsserts;
use Tests\Fixtures;
use Tests\TestCase;

class PasswordControllerTest extends TestCase implements DatabaseTransaction
{
    use AuthAsserts;

    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function testUpdatePasswordRequireAuth(): void
    {
        $response = $this->put('/profile/password');

        $response->assertUnauthorized();
    }

    public function testUpdatePassword(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $password = Fixtures::string();

        $response = $this->withAuth($auth)->put('/profile/password', [
            'currentPassword' => UserFactory::DEFAULT_PASSWORD,
            'newPassword' => $password,
            'newPasswordConfirmation' => $password,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertUserCannotLogin($user, UserFactory::DEFAULT_PASSWORD);
        $this->assertUserCanLogin($user, $password);
    }

    public function testUpdatePasswordValidationFailsWithWrongCurrentPassword()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $password = Fixtures::string();

        $response = $this->withAuth($auth)->put('/profile/password', [
            'currentPassword' => Fixtures::string(),
            'newPassword' => $password,
            'newPasswordConfirmation' => $password,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('currentPassword', $body['errors']);

        $this->assertUserCannotLogin($user, $password);
    }

    public function testUpdatePasswordValidationFailsWithEmptyRequest()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $password = Fixtures::string(5);

        $response = $this->withAuth($auth)->put('/profile/password');

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('currentPassword', $body['errors']);
        $this->assertArrayHasKey('newPassword', $body['errors']);

        $this->assertUserCannotLogin($user, $password);
    }

    public function testUpdatePasswordValidationFailsWithInvalidRequest()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $password = Fixtures::string(5);

        $response = $this->withAuth($auth)->put('/profile/password', [
            'currentPassword' => UserFactory::DEFAULT_PASSWORD,
            'newPassword' => $password,
            'newPasswordConfirmation' => $password . '.',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('newPassword', $body['errors']);
        $this->assertArrayHasKey('newPasswordConfirmation', $body['errors']);

        $this->assertUserCannotLogin($user, $password);
    }

    public function testUpdatePasswordFailsOnStorageException()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $mock = $this->getMockBuilder(UserService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['store'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('store')
             ->willThrowException(new \RuntimeException('Storage exception.'));

        $this->getContainer()->bind(UserService::class, fn () => $mock);

        $password = Fixtures::string();

        $response = $this->withAuth($auth)->put('/profile/password', [
            'currentPassword' => UserFactory::DEFAULT_PASSWORD,
            'newPassword' => $password,
            'newPasswordConfirmation' => $password,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertUserCannotLogin($user, $password);
    }
}
