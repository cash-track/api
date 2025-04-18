<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Database\Tag;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\View\TagsView;
use App\View\TagView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class CommonController extends AuthAwareController
{
    public function __construct(
        AuthContextInterface $auth,
        private readonly ResponseWrapper $response,
        private readonly TagRepository $tagRepository,
        private readonly TagsView $tagsView,
        private readonly TagView $tagView,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags/common', name: 'tag.common.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        return $this->tagsView->json($this->tagRepository->findAllByUsersPK(
            $this->userRepository->getCommonUserIDs($this->user)
        ));
    }

    #[Route(route: '/tags/common/<id>', name: 'tag.common.index', methods: 'GET', group: 'auth')]
    public function index(string $id): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUsersPK((int) $id, $this->userRepository->getCommonUserIDs($this->user));

        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        return $this->tagView->json($tag);
    }
}
