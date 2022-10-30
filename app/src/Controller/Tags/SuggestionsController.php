<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\View\TagsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Router\Annotation\Route;

final class SuggestionsController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        private readonly TagRepository $tagRepository,
        private readonly TagsView $tagsView,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags/suggestions/<query>', name: 'tag.suggestions', methods: 'GET', group: 'auth')]
    public function suggestions(string $query = ''): ResponseInterface
    {
        return $this->tagsView->json($this->tagRepository->searchAllByChargesByUsersPK(
            $this->userRepository->getCommonUserIDs($this->user),
            urldecode($query),
        ));
    }
}
