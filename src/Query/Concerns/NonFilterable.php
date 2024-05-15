<?php

namespace Katalam\OnOfficeAdapter\Query\Concerns;

trait NonFilterable
{
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        return $this;
    }
}