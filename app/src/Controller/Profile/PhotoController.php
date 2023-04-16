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
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class PhotoController extends AuthAwareController
{
    use TranslatorTrait;

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
                'message' => $this->say('profile_photo_update_empty'),
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
                'message' => $this->say('profile_photo_update_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json([
            'message' => $this->say('profile_photo_update_ok'),
            'fileName' => $fileName,
            'url' => $this->photoStorageService->getProfilePhotoPublicUrl($fileName),
        ], 200);
    }
}
