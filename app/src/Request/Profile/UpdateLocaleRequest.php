<?php

declare(strict_types=1);

namespace App\Request\Profile;

use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Translator\Translator;
use Spiral\Validator\FilterDefinition;

final class UpdateLocaleRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $locale = '';

    public function __construct(
        private readonly Translator $translator,
    ) {
    }

    #[\Override]
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'locale' => [
                'is_string',
                'type::notEmpty',
                ['in_array', $this->translator->getCatalogueManager()->getLocales(), true],
            ]
        ]);
    }
}
