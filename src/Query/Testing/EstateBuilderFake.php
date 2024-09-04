<?php

declare(strict_types=1);

namespace Katalam\OnOfficeAdapter\Query\Testing;

use Throwable;

class EstateBuilderFake extends BaseFake
{
    /**
     * @throws Throwable
     */
    public function create(array $data): array
    {
        return $this->get()->first();
    }
}
