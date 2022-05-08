<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Repository\UserRepository;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class LoginControllerTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function testLoggedIn(): void
    {
        $user = $this->userFactory->create();

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]);

        $response->assertOk();

        $auth = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $auth);
        $this->assertArrayHasKey('id', $auth['data']);
        $this->assertArrayHasKey('accessToken', $auth);
        $this->assertArrayHasKey('refreshToken', $auth);

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertEquals($user->id, $body['data']['id']);
    }

    public function testWrongEmail(): void
    {
        $this->userFactory->create();
        $missingUser = UserFactory::make();

        $response = $this->post('/auth/login', [
            'email' => $missingUser->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testWrongPassword(): void
    {
        $user = $this->userFactory->create();

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => Fixtures::string(),
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testCannotFindUser(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(UserRepository::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['findByEmail'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('findByEmail')
             ->with($user->email)
             ->willThrowException(new \RuntimeException('Database exception'));

        $this->getContainer()->bind(UserRepository::class, fn () => $mock);

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testValidationFailsEmptyForm(): void
    {
        $response = $this->post('/auth/login', []);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function testValidationFailsEmptyFormFields(): void
    {
        $response = $this->post('/auth/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }
}
