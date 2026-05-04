<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Foundation\Exceptions\ServiceException;

class CapabilityService implements CapabilityServiceInterface
{
    private array $capabilities;

    public function __construct(?array $capabilities = null)
    {
        $this->capabilities = $capabilities ?? (array) config('ptadmin-auth.capabilities', [
            'rbac' => true,
            'organization' => false,
            'tenant' => false,
            'data_scope' => false,
            'workflow' => false,
        ]);
    }

    public function enabled(string $capability): bool
    {
        return (bool) ($this->capabilities[$capability] ?? false);
    }

    public function requireEnabled(string $capability): void
    {
        if (!$this->enabled($capability)) {
            throw new ServiceException(sprintf('Capability [%s] is not enabled.', $capability));
        }
    }

    public function all(): array
    {
        return $this->capabilities;
    }
}
