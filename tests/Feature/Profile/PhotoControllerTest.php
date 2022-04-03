<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Request\Profile\UpdatePhotoRequest;
use App\Service\PhotoStorageService;
use App\Service\UserService;
use Psr\Http\Message\UploadedFileInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class PhotoControllerTest extends TestCase implements DatabaseTransaction
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

    public function testUpdatePhotoRequireAuth(): void
    {
        $response = $this->put('/profile/photo');

        $response->assertUnauthorized();
    }

    public function testUpdatePhoto(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $fileName = Fixtures::fileName();
        $url = Fixtures::url($fileName);
        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock);
        $storageMock = $this->getMockStorageService();

        $storageMock->expects($this->once())
                    ->method('storeUploadedProfilePhoto')
                    ->with($fileMock)
                    ->willReturn($fileName);

        $storageMock->expects($this->once())
                    ->method('removeProfilePhoto')
                    ->with($user->photo);

        $storageMock->expects($this->once())
                    ->method('getProfilePhotoPublicUrl')
                    ->with($fileName)
                    ->willReturn($url);

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);

        $response = $this->withAuth($auth)->put('/profile/photo');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('fileName', $body);
        $this->assertArrayHasKey('url', $body);
        $this->assertEquals($fileName, $body['fileName']);
        $this->assertEquals($url, $body['url']);

        $this->assertDatabaseHas('users', [
            'photo' => $fileName,
        ]);
    }

    public function testUpdatePhotoValidationFails(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock, true);
        $storageMock = $this->getMockStorageService();

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);

        $response = $this->withAuth($auth)->put('/profile/photo');

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('photo', $body['errors']);
    }

    public function testUpdatePhotoStoreFails(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock);
        $storageMock = $this->getMockStorageService();

        $storageMock->expects($this->once())
                    ->method('storeUploadedProfilePhoto')
                    ->with($fileMock)
                    ->willReturn(null);

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);

        $response = $this->withAuth($auth)->put('/profile/photo');

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testUpdatePhotoUpdateUserFails(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $fileName = Fixtures::fileName();
        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock);
        $storageMock = $this->getMockStorageService();

        $storageMock->expects($this->once())
                    ->method('storeUploadedProfilePhoto')
                    ->with($fileMock)
                    ->willReturn($fileName);

        $storageMock->expects($this->once())
                    ->method('removeProfilePhoto')
                    ->with($user->photo);

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->onlyMethods(['store'])
                                ->getMock();

        $userServiceMock->expects($this->once())
                        ->method('store')
                        ->willThrowException(new \RuntimeException('Storage exception.'));

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);
        $this->getContainer()->bind(UserService::class, fn () => $userServiceMock);

        $response = $this->withAuth($auth)->put('/profile/photo');

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    private function getMockUploadedFile(): UploadedFileInterface
    {
        return $this->getMockBuilder(UploadedFileInterface::class)
                    ->getMock();
    }

    private function getMockUpdatePhotoRequest(UploadedFileInterface $file, bool $isInvalid = false)
    {
        $mock = $this->getMockBuilder(UpdatePhotoRequest::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['isValid', 'setContext', 'getPhoto', 'getErrors'])
                     ->getMock();

        $mock->method('isValid')->willReturn(!$isInvalid);
        $mock->method('getPhoto')->willReturn($file);
        $mock->method('getErrors')->willReturn(
            $isInvalid ? ['photo' => ['Validation error']] : [],
        );

        return $mock;
    }

    private function getMockStorageService()
    {
        $mock = $this->getMockBuilder(PhotoStorageService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods([
                         'getProfilePhotoPublicUrl',
                         'storeUploadedProfilePhoto',
                         'removeProfilePhoto',
                     ])
                     ->getMock();

        return $mock;
    }
}
