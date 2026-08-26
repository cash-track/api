<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Profile;

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
        $response = $this->put('/v1/profile/photo');

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

        $response = $this->withAuth($auth)->put('/v1/profile/photo');

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

        $response = $this->withAuth($auth)->put('/v1/profile/photo');

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    /**
     * If store() throws, the new filename was never persisted, so the old S3 object must NOT be
     * deleted — a broken avatar with no recovery path (see issue #224, item 1).
     */
    public function testUpdatePhotoUpdateUserFails(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $oldPhoto = $user->photo;

        $fileName = Fixtures::fileName();
        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock);
        $storageMock = $this->getMockStorageService();

        $storageMock->expects($this->once())
                    ->method('storeUploadedProfilePhoto')
                    ->with($fileMock)
                    ->willReturn($fileName);

        $storageMock->expects($this->never())->method('removeProfilePhoto');

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->onlyMethods(['store'])
                                ->getMock();

        $userServiceMock->expects($this->exactly(2))
                        ->method('store')
                        ->willThrowException(new \RuntimeException('Storage exception.'));

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);
        $this->getContainer()->bind(UserService::class, fn () => $userServiceMock);

        $response = $this->withAuth($auth)->put('/v1/profile/photo');

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('users', [
            'photo' => $oldPhoto,
        ]);
    }

    /**
     * Delete-after-commit: the old S3 object must only be removed once the new filename is
     * durably persisted, never before (see issue #224, item 1).
     */
    public function testUpdatePhotoDeletesOldPhotoOnlyAfterNewFilenameIsPersisted(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $oldPhoto = $user->photo;

        $fileName = Fixtures::fileName();
        $url = Fixtures::url($fileName);
        $fileMock = $this->getMockUploadedFile();
        $requestMock = $this->getMockUpdatePhotoRequest($fileMock);
        $storageMock = $this->getMockStorageService();

        $order = [];

        $storageMock->method('storeUploadedProfilePhoto')->willReturn($fileName);
        $storageMock->method('getProfilePhotoPublicUrl')->willReturn($url);
        $storageMock->expects($this->once())
                    ->method('removeProfilePhoto')
                    ->with($oldPhoto)
                    ->willReturnCallback(function () use (&$order): void {
                        $order[] = 'delete';
                    });

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->onlyMethods(['store'])
                                ->getMock();
        $userServiceMock->method('store')->willReturnCallback(function ($user) use (&$order) {
            $order[] = 'store';

            return $user;
        });

        $this->getContainer()->bind(UpdatePhotoRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(PhotoStorageService::class, fn () => $storageMock);
        $this->getContainer()->bind(UserService::class, fn () => $userServiceMock);

        $response = $this->withAuth($auth)->put('/v1/profile/photo');

        $response->assertOk();

        // AuthMiddleware::trackActiveAt() also calls store() before the controller runs, so two
        // 'store' entries land first — 'delete' must be last regardless.
        $this->assertSame(['store', 'store', 'delete'], $order);
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
                     ->getMock();

        $mock->photo = $file;
        $mock->method('getPhoto')->willReturn($file);

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
