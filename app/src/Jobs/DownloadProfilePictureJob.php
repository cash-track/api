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

        $logger->info('Downloading remote profile photo', [
            'id' => $id,
            'payload' => $payload,
            'headers' => $headers,
        ]);

        $user = $repository->findByPK($userId);
        if (! $user instanceof User) {
            $logger->error('User is not found', ['id' => $id]);
            return;
        }

        $oldFileName = $user->photo;

        $fileName = $storageService->storeRemoteProfilePhoto($url, $ext, $mime);
        if ($fileName === null) {
            $logger->error('Unable to download remote profile photo', ['id' => $id]);
            return;
        }

        $logger->info("Remote profile photo saved", ['id' => $id, 'fileName' => $fileName]);

        $user->photo = $fileName;

        try {
            $userService->store($user);
        } catch (\Throwable $exception) {
            $logger->error('Unable to attach profile photo to user', ['id' => $id, 'error' => $exception->getMessage()]);
            return;
        }

        if ($oldFileName !== null) {
            $storageService->removeProfilePhoto($oldFileName);
        }

        $logger->info('Remote profile photo attached to user', ['id' => $id, 'userId' => $userId]);
    }
}
