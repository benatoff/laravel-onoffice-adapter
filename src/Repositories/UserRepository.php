<?php

namespace Katalam\OnOfficeAdapter\Repositories;

use Katalam\OnOfficeAdapter\Query\UserBuilder;
use Katalam\OnOfficeAdapter\Services\OnOfficeService;

readonly class UserRepository
{
    public function __construct(
        private OnOfficeService $onOfficeService,
    ) {
    }

    /**
     * Returns a new relation builder instance.
     */
    public function query(): UserBuilder
    {
        return new UserBuilder($this->onOfficeService);
    }
}
