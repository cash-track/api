<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Database\Tag;
use App\Repository\TagRepository;
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
use Spiral\Translator\Traits\TranslatorTrait;

final class TagsController extends AuthAwareController
{
    use TranslatorTrait;

    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private TagRepository $tagRepository,
        private TagService $tagService,
        private TagsView $tagsView,
        private TagView $tagView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags', name: 'tag.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        return $this->tagsView->json($this->tagRepository->findAllByUserPK((int) $this->user->id));
    }

    #[Route(route: '/tags', name: 'tag.create', methods: 'POST', group: 'auth')]
    public function create(CreateRequest $request): ResponseInterface
    {
        try {
            $tag = $this->tagService->create($request->createTag(), $this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('tag_create_exception'),
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->tagView->json($tag);
    }

    #[Route(route: '/tags/<id>', name: 'tag.update', methods: 'PUT', group: 'auth')]
    public function update(string $id, UpdateRequest $request): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $tag->name = $request->name;
        $tag->icon = $request->icon;
        $tag->color = $request->color;

        try {
            $tag = $this->tagService->store($tag);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store tag', [
                'action' => 'tag.update',
                'id'     => $tag->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('tag_update_exception'),
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->tagView->json($tag);
    }

    #[Route(route: '/tags/<id>', name: 'tag.delete', methods: 'DELETE', group: 'auth')]
    public function delete(string $id): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

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
                'message' => $this->say('tag_delete_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
