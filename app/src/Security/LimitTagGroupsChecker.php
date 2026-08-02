<?php

declare(strict_types=1);

namespace App\Security;

use App\Database\LimitTagGroup;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select\Repository;
use Spiral\Core\Attribute\Singleton;
use Spiral\Validator\AbstractChecker;

/**
 * Validates the `tagGroups` request shape: array<{operation: 'and'|'or', tags: int[]}>.
 * Mirrors `array::of` + `entity:exists` (multiple mode), one structural level deeper.
 */
#[Singleton]
final class LimitTagGroupsChecker extends AbstractChecker
{
    public const array MESSAGES = [
        'valid' => 'error_limit_tag_groups_invalid',
    ];

    public function __construct(
        private readonly ORMInterface $orm,
    ) {
    }

    public function valid(mixed $value, string $role): bool
    {
        if (!is_array($value) || empty($value) || empty($role)) {
            return false;
        }

        $repository = $this->orm->getRepository($role);

        if (!$repository instanceof Repository) {
            return false;
        }

        foreach ($value as $group) {
            if (!$this->validGroup($group, $repository)) {
                return false;
            }
        }

        return true;
    }

    private function validGroup(mixed $group, Repository $repository): bool
    {
        if (!is_array($group)) {
            return false;
        }

        $operation = $group['operation'] ?? null;

        if (
            !is_string($operation)
            || !in_array($operation, [LimitTagGroup::CONNECTION_AND, LimitTagGroup::CONNECTION_OR], true)
        ) {
            return false;
        }

        $tagIds = $group['tags'] ?? null;

        if (!is_array($tagIds) || empty($tagIds)) {
            return false;
        }

        foreach ($tagIds as $tagId) {
            if (!is_int($tagId) && !(is_string($tagId) && ctype_digit($tagId))) {
                return false;
            }
        }

        return $repository->select()->wherePK(...$tagIds)->count() === count($tagIds);
    }
}
