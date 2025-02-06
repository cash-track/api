<?php

namespace App\View;

trait Relations
{
    /**
     * @var array<array-key, string>
     */
    protected array $relations = [];

    public function withRelation(string $relation): self
    {
        $this->relations[] = $relation;

        return $this;
    }

    /**
     * @param array<array-key, string> $relations
     * @return $this
     */
    public function withRelations(array $relations): self
    {
        $this->relations = $relations;

        return $this;
    }

    protected function loaded(string $relation): bool
    {
        return in_array($relation, $this->relations);
    }
}
