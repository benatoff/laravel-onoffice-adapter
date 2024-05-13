<?php

namespace Katalam\OnOfficeAdapter\Query;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Katalam\OnOfficeAdapter\Enums\OnOfficeAction;
use Katalam\OnOfficeAdapter\Enums\OnOfficeRelationType;
use Katalam\OnOfficeAdapter\Enums\OnOfficeResourceType;
use Katalam\OnOfficeAdapter\Exceptions\OnOfficeException;
use Katalam\OnOfficeAdapter\Services\OnOfficeService;

class RelationBuilder extends Builder
{
    public array $parentIds = [];

    public array $childIds = [];

    public OnOfficeRelationType $relationType;

    public function __construct(
        private readonly OnOfficeService $onOfficeService,
    ) {
    }

    public function get(): Collection
    {
        $records = $this->onOfficeService->requestAll(/**
         * @throws OnOfficeException
         */ function () {
            return $this->onOfficeService->requestApi(
                OnOfficeAction::Get,
                OnOfficeResourceType::IdsFromRelation,
                parameters: [
                    OnOfficeService::RELATIONTYPE => $this->relationType,
                    OnOfficeService::PARENTIDS => $this->parentIds,
                    OnOfficeService::CHILDIDS => $this->childIds,
                ],
            );
        }, pageSize: $this->limit, offset: $this->offset);

        // $records is always an array containing a single element
        return collect(data_get($records->first(), 'elements'));
    }

    /**
     * @throws OnOfficeException
     */
    public function first(): array
    {
        throw new OnOfficeException('Not implemented in onOffice');
    }

    /**
     * @throws OnOfficeException
     */
    public function find(int $id): array
    {
        throw new OnOfficeException('Not implemented in onOffice');
    }

    public function each(callable $callback): void
    {
        $records = $this->get();

        $callback($records);
    }

    public function parentIds(int|array $parentIds): self
    {
        $this->parentIds = Arr::wrap($parentIds);

        return $this;
    }

    public function addParentIds(int|array $parentIds): self
    {
        $this->parentIds = array_merge($this->parentIds, Arr::wrap($parentIds));

        return $this;
    }

    public function childIds(int|array $childIds): self
    {
        $this->childIds = Arr::wrap($childIds);

        return $this;
    }

    public function addChildIds(int|array $childIds): self
    {
        $this->childIds = array_merge($this->childIds, Arr::wrap($childIds));

        return $this;
    }

    public function relationType(OnOfficeRelationType $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }
}