<?php

declare(strict_types=1);

namespace Innobrain\OnOfficeAdapter\Query;

use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeRequest;
use Innobrain\OnOfficeAdapter\Enums\OnOfficeAction;
use Innobrain\OnOfficeAdapter\Enums\OnOfficeResourceType;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\OnOfficeAdapter\Query\Concerns\NonFilterable;
use Innobrain\OnOfficeAdapter\Query\Concerns\NonOrderable;
use Innobrain\OnOfficeAdapter\Services\OnOfficeService;

class ImprintBuilder extends Builder
{
    use NonFilterable;
    use NonFilterable;
    use NonOrderable;

    /**
     * @throws OnOfficeException
     */
    public function get(): Collection
    {
        $request = new OnOfficeRequest(
            OnOfficeAction::Read,
            OnOfficeResourceType::Impressum,
            parameters: [
                OnOfficeService::DATA => $this->columns,
                ...$this->customParameters,
            ]
        );

        return $this->requestAll($request);
    }

    /**
     * @throws OnOfficeException
     */
    public function first(): ?array
    {
        $response = $this->onOfficeService->requestApi(
            OnOfficeAction::Read,
            OnOfficeResourceType::Impressum,
            parameters: [
                OnOfficeService::DATA => $this->columns,
                ...$this->customParameters,
            ]
        );

        return $response->json('response.results.0.data.records.0');
    }

    /**
     * @throws OnOfficeException
     */
    public function find(int $id): array
    {
        $response = $this->onOfficeService->requestApi(
            OnOfficeAction::Read,
            OnOfficeResourceType::Impressum,
            resourceId: $id,
            parameters: [
                OnOfficeService::DATA => $this->columns,
                ...$this->customParameters,
            ]
        );

        return $response->json('response.results.0.data.records.0');
    }
}
