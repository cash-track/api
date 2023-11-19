<?php

declare(strict_types=1);

namespace App\Request\Tag;

use App\Auth\AuthMiddleware;
use App\Database\Tag;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Input\Header;
use Spiral\Filters\Attribute\Input\Route;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class UpdateRequest extends Filter implements HasFilterDefinition
{
    #[Header(key: AuthMiddleware::HEADER_USER_ID)]
    public int $user_id = 0;

    #[Route]
    public int $id = 0;

    #[Data]
    public string $name = '';

    #[Data]
    public ?string $icon = '';

    #[Data]
    public ?string $color = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'name' => [
                ['is_string'],
                ['string::range', 3, 255],
                ['type::notEmpty'],
                ['string::regexp', '/^[^\s]*$/'],
                ['unique::verify', Tag::class, 'name', ['user_id'], ['id']],
            ],
            'icon' => [
                ['is_string'],
                ['string::range', 1, 10]
            ],
            'color' => [
                ['is_string'],
                ['string::regexp', '/^\#[0-9a-fA-F]{6}$/'],
            ],
        ]);
    }
}
