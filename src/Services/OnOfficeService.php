<?php

declare(strict_types=1);

namespace Katalam\OnOfficeAdapter\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Katalam\OnOfficeAdapter\Enums\OnOfficeAction;
use Katalam\OnOfficeAdapter\Enums\OnOfficeResourceId;
use Katalam\OnOfficeAdapter\Enums\OnOfficeResourceType;
use Katalam\OnOfficeAdapter\Exceptions\OnOfficeException;

class OnOfficeService
{
    use OnOfficeParameterConst;

    public function __construct() {}

    public function getToken(): string
    {
        return Config::get('onoffice.token', '') ?? '';
    }

    public function getSecret(): string
    {
        return Config::get('onoffice.secret', '') ?? '';
    }

    public function getApiClaim(): string
    {
        return Config::get('onoffice.api_claim', '') ?? '';
    }

    /*
     * Generates a HMAC for the onOffice API request.
     * The new HMAC is calculated by concatenating the values of the parameters
     * timestamp, token, resourcetype and actionid in this order.
     * A SHA256 hash is formed from this string (with the secret as the key)
     * and the resulting binary string must then be base64 encoded.
     *
     * Read more: https://apidoc.onoffice.de/onoffice-api-request/request-elemente/action/#hmac
     */
    private function getHmac(OnOfficeAction $actionId, OnOfficeResourceType $resourceType): string
    {
        return base64_encode(
            hash_hmac(
                'sha256',
                implode(
                    '',
                    [
                        'timestamp' => Carbon::now()->timestamp,
                        'token' => $this->getToken(),
                        'resourcetype' => $resourceType->value,
                        'actionid' => $actionId->value,
                    ]
                ),
                $this->getSecret(),
                true
            )
        );
    }

    /*
     * Makes a request to the onOffice API.
     * Throws an exception if the request fails.
     *
     * Read more: https://apidoc.onoffice.de/onoffice-api-request/aufbau/
     */
    /**
     * @throws OnOfficeException
     */
    public function requestApi(
        OnOfficeAction $actionId,
        OnOfficeResourceType $resourceType,
        OnOfficeResourceId|string|int $resourceId = OnOfficeResourceId::None,
        string|int $identifier = '',
        array $parameters = [],
    ): Response {
        if (! empty($this->getApiClaim())) {
            $parameters = array_replace([self::EXTENDEDCLAIM => $this->getApiClaim()], $parameters);
        }

        $response = Http::onOffice()
            ->post('/', [
                'token' => $this->getToken(),
                'request' => [
                    'actions' => [
                        [
                            'actionid' => $actionId->value,
                            'resourceid' => $resourceId instanceof OnOfficeResourceId ? $resourceId->value : $resourceId,
                            'resourcetype' => $resourceType->value,
                            'identifier' => $identifier,
                            'timestamp' => Carbon::now()->timestamp,
                            'hmac' => $this->getHmac($actionId, $resourceType),
                            'hmac_version' => 2,
                            'parameters' => $parameters,
                        ],
                    ],
                ],
            ]);

        $this->throwIfResponseIsFailed($response);

        return $response;
    }

    /**
     * Makes a paginated request to the onOffice API.
     * With a max page calculation based on
     * the total count of records,
     * of the first request.
     */
    public function requestAll(
        callable $request,
        string $resultPath = 'response.results.0.data.records',
        string $countPath = 'response.results.0.data.meta.cntabsolute',
        int $pageSize = 500,
        int $offset = 0,
        int $take = -1,
    ): Collection {
        $maxPage = 0;
        $data = collect();
        do {
            try {
                $response = $request($pageSize, $offset);
            } catch (OnOfficeException $exception) {
                Log::error("{$exception->getMessage()} - {$exception->getCode()}");

                return $data;
            }

            // If the maxPage is 0,
            // we need to calculate it from the total count of estates
            // and the page size,
            // the first time we get the response from the API
            if ($maxPage === 0) {
                $countAbsolute = $response->json($countPath, 0);
                $maxPage = ceil($countAbsolute / $pageSize);
            }
            $responseResult = $response->json($resultPath);

            if (is_array($responseResult)) {
                $data->push(...$responseResult);
            }

            // if the take parameter is set,
            // and we have more records than the take parameter,
            // we break the loop and return the data except the
            // records that are more than the take parameter
            if ($take > -1 && $data->count() > $take) {
                $data = $data->take($take);
                break;
            }

            $offset += $pageSize;
            $currentPage = $offset / $pageSize;
        } while ($maxPage > $currentPage);

        return $data;
    }

    /**
     * Makes a paginated request to the onOffice API.
     * With a max page calculation based on
     * the total count of records,
     * of the first request.
     *
     * The request will not return a collection containing the records,
     * but will call the given callback function with the records of each page.
     */
    public function requestAllChunked(
        callable $request,
        callable $callback,
        string $resultPath = 'response.results.0.data.records',
        string $countPath = 'response.results.0.data.meta.cntabsolute',
        int $pageSize = 500,
        int $offset = 0,
        int $take = -1,
    ): void {
        $maxPage = 0;
        $elementCount = 0;
        do {
            try {
                $response = $request($pageSize, $offset);
            } catch (OnOfficeException $exception) {
                Log::error("{$exception->getMessage()} - {$exception->getCode()}");

                return;
            }

            // If the maxPage is 0,
            // we need to calculate it from the total count of estates
            // and the page size,
            // the first time we get the response from the API
            if ($maxPage === 0) {
                $countAbsolute = $response->json($countPath, 0);

                // if the take parameter is set,
                // and we have more records than the take parameter,
                // we set the countAbsolute to the take parameter
                if ($take > -1 && $countAbsolute > $take) {
                    $countAbsolute = $take;
                }

                $maxPage = ceil($countAbsolute / $pageSize);
            }

            // If the take parameter is set,
            // and we have more records than the take parameter.
            // We break the loop and return the sliced records
            // because it is not guaranteed that the record page size
            // will be the same as the take parameter
            $elements = $response->json($resultPath);
            $elementCount += count($elements ?? []);
            if ($take > -1 && $elementCount > $take) {
                $elements = array_slice($elements, 0, $take - $elementCount);
            }

            $callback($elements);

            $offset += $pageSize;
            $currentPage = $offset / $pageSize;
        } while ($maxPage > $currentPage);
    }

    /**
     * Returns true if the response has a status code greater than 300
     * inside the status dot code key in the response.
     *
     * @throws OnOfficeException
     */
    private function throwIfResponseIsFailed(Response $response): void
    {
        $statusCode = $response->json('status.code', 500);
        $statusErrorCode = $response->json('status.errorcode', 0);
        $responseStatusCode = $response->json('response.results.0.status.errorcode', 0);

        $errorMessage = $response->json('status.message', '');
        if ($errorMessage === '') {
            $errorMessage = "Status code: $statusCode";
        }
        $responseErrorMessage = $response->json('response.results.0.status.message', '');
        if ($responseErrorMessage === '') {
            $responseErrorMessage = "Status code: $responseStatusCode";
        }

        match (true) {
            $statusCode >= 300 && $statusErrorCode > 0 => throw new OnOfficeException($errorMessage, $statusErrorCode, isResponseError: true),
            $statusCode >= 300 && $statusErrorCode <= 0 => throw new OnOfficeException($errorMessage, $statusCode),
            $responseStatusCode > 0 => throw new OnOfficeException($responseErrorMessage, $responseStatusCode, isResponseError: true),
            default => null,
        };
    }
}
