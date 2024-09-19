<?php

declare(strict_types=1);

namespace Innobrain\OnOfficeAdapter\Exceptions;

use Exception;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeRequest;
use Throwable;

class StrayRequestException extends Exception
{
    public ?OnOfficeRequest $request;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?OnOfficeRequest $request = null)
    {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
    }
}