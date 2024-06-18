<?php

declare(strict_types=1);

namespace Katalam\OnOfficeAdapter\Facades\Testing\RecordFactories;

use Illuminate\Support\Str;
use Katalam\OnOfficeAdapter\Facades\Testing\RecordFactories\Concerns\SuccessTrait;

class FileUploadFactory extends BaseFactory
{
    use SuccessTrait;

    public function id(int $id): static
    {
        return $this;
    }

    public function type(string $type): static
    {
        return $this;
    }

    public function elements(): static
    {
        return $this;
    }

    public function tmpUploadId(string $tmpUploadId = ''): static
    {
        if ($tmpUploadId === '') {
            $tmpUploadId = Str::uuid()->toString();
        }

        $this->elements['tmpUploadId'] = $tmpUploadId;

        return $this;
    }
}
