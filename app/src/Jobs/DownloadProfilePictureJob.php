<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Database\User;
use App\Repository\UserRepository;
use App\Service\PhotoStorageService;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Spiral\Queue\JobHandler;

final class DownloadProfilePictureJob extends JobHandler
{
    public function invoke(
        string $id,
        array $payload,
        array $headers,
        LoggerInterface $logger,
        UserRepository $repository,
        UserService $userService,
        PhotoStorageService $storageService,
    ): void {
        [
            'userId' => $userId,
            'url' => $url,
            'ext' => $ext,
            'mime' => $mime,
        ] = $payload;

        $logger->debug('Downloading remote profile photo', ['id' => $id, 'user_id' => $userId]);

        $user = $repository->findByPK($userId);
        if (! $user instanceof User) {
            $logger->warning('Profile photo download skipped: user not found', ['id' => $id, 'user_id' => $userId]);
            return;
        }

        $oldFileName = $user->photo;

        $fileName = $storageService->storeRemoteProfilePhoto($url, $ext, $mime);
        if ($fileName === null) {
            return;
        }

        $logger->debug('Remote profile photo saved', ['id' => $id, 'fileName' => $fileName]);

        $user->photo = $fileName;

        try {
            $userService->store($user);
        } catch (\Throwable $exception) {
            $logger->error('Unable to attach profile photo to user', [
                'id' => $id,
                'user_id' => $userId,
                'exception' => $exception,
            ]);
            return;
        }

        if ($oldFileName !== null) {
            $storageService->removeProfilePhoto($oldFileName);
        }

        $logger->info('Remote profile photo attached to user', ['id' => $id, 'user_id' => $userId]);
    }
}
