<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

interface CapabilityServiceInterface
{
    public function enabled(string $capability): bool;

    public function requireEnabled(string $capability): void;

    public function all(): array;
}
