<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Database\Tag;
use App\Database\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Request\Tag\CreateRequest;
use App\Request\Tag\UpdateRequest;
use App\Service\TagService;
use App\View\TagsView;
use App\View\TagView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class TagsController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private TagRepository $tagRepository,
        private TagService $tagService,
        private TagsView $tagsView,
        private TagView $tagView,
        private UserRepository $userRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags', name: 'tag.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        return $this->tagsView->json($this->tagRepository->findAllByUserPK((int) $this->user->id));
    }

    #[Route(route: '/tags/common', name: 'tag.list.common', methods: 'GET', group: 'auth')]
    public function listCommon(): ResponseInterface
    {
        $users = $this->userRepository->findAllByCommonWallets($this->user);

        $userIDs = array_map(fn (User $user) => (int) $user->id, $users);

        return $this->tagsView->json($this->tagRepository->findAllByUsersPK($userIDs));
    }

    #[Route(route: '/tags', name: 'tag.create', methods: 'POST', group: 'auth')]
    public function create(CreateRequest $request): ResponseInterface
    {
        $request->setFields(['user_id' => $this->user->id]);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $tag = $this->tagService->create($request->createTag(), $this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to create new tag. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->tagView->json($tag);
    }

    #[Route(route: '/tags/<id>', name: 'tag.update', methods: 'PUT', group: 'auth')]
    public function update(int $id, UpdateRequest $request): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $request->setValue([
            'id' => $id,
            'user_id' => $this->user->id
        ]);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $tag->name = $request->getName();
        $tag->icon = $request->getIcon();
        $tag->color = $request->getColor();

        try {
            $tag = $this->tagService->store($tag);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store tag', [
                'action' => 'tag.update',
                'id'     => $tag->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update tag. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->tagView->json($tag);
    }

    #[Route(route: '/tags/<id>', name: 'tag.delete', methods: 'DELETE', group: 'auth')]
    public function delete(int $id): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        try {
            $this->tagService->delete($tag);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete tag', [
                'action' => 'tag.delete',
                'id'     => $tag->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to delete tag. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
