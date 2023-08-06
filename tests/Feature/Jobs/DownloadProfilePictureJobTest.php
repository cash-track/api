<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Database\User;
use App\Jobs\DownloadProfilePictureJob;
use App\Repository\UserRepository;
use App\Service\PhotoStorageService;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class DownloadProfilePictureJobTest extends TestCase
{
    public function testInvoke(): void
    {
        $user = UserFactory::make();
        $payload = [
            'userId' => $user->id,
            'url' => Fixtures::url('picture.png'),
            'ext' => 'png',
            'mime' => null,
        ];
        $fileName = Fixtures::string() . '.png';

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $userRepoMock = $this->getMockBuilder(UserRepository::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['findByPK'])
                             ->getMock();
        $userRepoMock->expects($this->once())
                     ->method('findByPK')
                     ->with($user->id)
                     ->willReturn($user);

        $storageServiceMock = $this->getMockBuilder(PhotoStorageService::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $storageServiceMock->expects($this->once())
                           ->method('storeRemoteProfilePhoto')
                           ->with($payload['url'], $payload['ext'], $payload['mime'])
                           ->willReturn($fileName);
        $storageServiceMock->expects($this->once())
                           ->method('removeProfilePhoto')
                           ->with($user->photo);

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $userServiceMock->expects($this->once())
                        ->method('store')
                        ->with($this->callback(function (User $user) use ($fileName) {
                            $this->assertEquals($fileName, $user->photo);
                            return true;
                        }));

        /** @var DownloadProfilePictureJob $job */
        $job = $this->getContainer()->get(DownloadProfilePictureJob::class);
        $job->invoke(Fixtures::string(), $payload, [], $logger, $userRepoMock, $userServiceMock, $storageServiceMock);
    }

    public function testInvokeUserNotFound(): void
    {
        $payload = [
            'userId' => Fixtures::integer(),
            'url' => Fixtures::url('picture.png'),
            'ext' => 'png',
            'mime' => null,
        ];

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $userRepoMock = $this->getMockBuilder(UserRepository::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['findByPK'])
                             ->getMock();
        $userRepoMock->expects($this->once())
                     ->method('findByPK')
                     ->with($payload['userId'])
                     ->willReturn(null);
        $storageServiceMock = $this->getMockBuilder(PhotoStorageService::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $storageServiceMock->expects($this->never())->method('storeRemoteProfilePhoto');
        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        /** @var DownloadProfilePictureJob $job */
        $job = $this->getContainer()->get(DownloadProfilePictureJob::class);
        $job->invoke(Fixtures::string(), $payload, [], $logger, $userRepoMock, $userServiceMock, $storageServiceMock);
    }

    public function testInvokeDownloadFailed(): void
    {
        $user = UserFactory::make();
        $payload = [
            'userId' => $user->id,
            'url' => Fixtures::url('picture.png'),
            'ext' => 'png',
            'mime' => null,
        ];

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $userRepoMock = $this->getMockBuilder(UserRepository::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['findByPK'])
                             ->getMock();
        $userRepoMock->expects($this->once())
                     ->method('findByPK')
                     ->with($user->id)
                     ->willReturn($user);

        $storageServiceMock = $this->getMockBuilder(PhotoStorageService::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $storageServiceMock->expects($this->once())
                           ->method('storeRemoteProfilePhoto')
                           ->with($payload['url'], $payload['ext'], $payload['mime'])
                           ->willReturn(null);
        $storageServiceMock->expects($this->never())->method('removeProfilePhoto')->with($user->photo);

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $userServiceMock->expects($this->never())->method('store');

        /** @var DownloadProfilePictureJob $job */
        $job = $this->getContainer()->get(DownloadProfilePictureJob::class);
        $job->invoke(Fixtures::string(), $payload, [], $logger, $userRepoMock, $userServiceMock, $storageServiceMock);
    }

    public function testInvokeStoreUserFailed(): void
    {
        $user = UserFactory::make();
        $payload = [
            'userId' => $user->id,
            'url' => Fixtures::url('picture.png'),
            'ext' => 'png',
            'mime' => null,
        ];
        $fileName = Fixtures::string() . '.png';

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $userRepoMock = $this->getMockBuilder(UserRepository::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['findByPK'])
                             ->getMock();
        $userRepoMock->expects($this->once())
                     ->method('findByPK')
                     ->with($user->id)
                     ->willReturn($user);

        $storageServiceMock = $this->getMockBuilder(PhotoStorageService::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $storageServiceMock->expects($this->once())
                           ->method('storeRemoteProfilePhoto')
                           ->with($payload['url'], $payload['ext'], $payload['mime'])
                           ->willReturn($fileName);
        $storageServiceMock->expects($this->never())
                           ->method('removeProfilePhoto')
                           ->with($user->photo);

        $userServiceMock = $this->getMockBuilder(UserService::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $userServiceMock->expects($this->once())
                        ->method('store')
                        ->with($this->callback(function (User $user) use ($fileName) {
                            $this->assertEquals($fileName, $user->photo);
                            return true;
                        }))->willThrowException(new \RuntimeException());

        /** @var DownloadProfilePictureJob $job */
        $job = $this->getContainer()->get(DownloadProfilePictureJob::class);
        $job->invoke(Fixtures::string(), $payload, [], $logger, $userRepoMock, $userServiceMock, $storageServiceMock);
    }
}
