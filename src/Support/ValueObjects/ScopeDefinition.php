<?php

declare(strict_types=1);

namespace PTAdmin\Support\ValueObjects;

final class ScopeDefinition
{
    public ?string $type = null;
    public $value = null;

    public function __construct(?string $type = null, $value = null)
    {
        $this->type = $type;
        $this->value = $value;
    }
}
