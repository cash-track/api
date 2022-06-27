<?php

namespace App\View;

trait Relations
{
    /**
     * @var array<array-key, string>
     */
    protected array $relations = [];

    /**
     * @param string $relation
     * @return $this
     */
    public function withRelation(string $relation)
    {
        $this->relations[] = $relation;

        return $this;
    }

    /**
     * @param array<array-key, string> $relations
     * @return $this
     */
    public function withRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * @param string $relation
     * @return bool
     */
    protected function loaded(string $relation): bool
    {
        return in_array($relation, $this->relations);
    }
}
