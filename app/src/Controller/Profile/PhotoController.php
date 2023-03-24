<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Request\Profile\UpdatePhotoRequest;
use App\Service\PhotoStorageService;
use App\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class PhotoController extends AuthAwareController
{
    use PrototypeTrait;

    public function __construct(
        AuthScope $auth,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected ResponseWrapper $response,
        protected PhotoStorageService $photoStorageService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/profile/photo', name: 'profile.update.photo', methods: 'PUT', group: 'auth')]
    public function updatePhoto(UpdatePhotoRequest $request): ResponseInterface
    {
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
