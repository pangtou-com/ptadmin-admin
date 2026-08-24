<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

/**
 * Stores values shared by widget handlers during one dashboard query batch.
 *
 * The cache is intentionally request-scoped. It must never be registered as a
 * global singleton because the values can contain tenant- or user-specific
 * data.
 */
final class DashboardWidgetQueryCache
{
    /** @var array<string, mixed> */
    private array $values = array();

    /**
     * @param callable(): mixed $resolver
     *
     * @return mixed
     */
    public function remember(string $key, callable $resolver)
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        $this->values[$key] = $resolver();

        return $this->values[$key];
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }

    public function clear(): void
    {
        $this->values = array();
    }
}
