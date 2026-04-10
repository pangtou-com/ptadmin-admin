<?php

declare(strict_types=1);

namespace PTAdmin\Support\ValueObjects;

final class ResourceIdentifier
{
    public string $code;
    public ?int $id;

    public function __construct(string $code, ?int $id = null)
    {
        $this->code = $code;
        $this->id = $id;
    }
}
