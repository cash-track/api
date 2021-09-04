<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Request\Profile\UpdatePhotoRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class PhotoController extends ProfileController
{
    use PrototypeTrait;

    /**
     * @Route(route="/profile/photo", name="profile.update.photo", methods="PUT", group="auth")
     *
     * @param \App\Request\Profile\UpdatePhotoRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function updatePhoto(UpdatePhotoRequest $request): ResponseInterface
    {
        $request->setContext($this->user);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $file = $request->getPhoto();

        $fileName = $this->photoStorageService->storeUploadedProfilePhoto($file);

        if ($fileName === null) {
            return $this->response->json([
                'message' => 'Unable to store uploaded photo. Please try again later.'
            ], 500);
        }

        if ($this->user->photo !== null) {
            $this->photoStorageService->removeProfilePhoto($this->user->photo);
        }

        $this->user->photo = $fileName;

        try {
            $this->userService->store($this->user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user', [
                'action' => 'profile.update.photo',
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update user photo. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json([
            'message' => 'Photo has been updated.',
            'fileName' => $fileName,
            'url' => $this->photoStorageService->getProfilePhotoPublicUrl($fileName),
        ], 200);
    }
}
