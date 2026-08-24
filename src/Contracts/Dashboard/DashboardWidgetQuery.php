<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class DashboardWidgetQuery
{
    /** @var array<string, mixed> */
    private array $values;

    /** @param array<string, mixed> $values */
    public function __construct(array $values = array()) { $this->values = $values; }
    public function has(string $key): bool { return array_key_exists($key, $this->values); }
    public function get(string $key, $default = null) { return $this->values[$key] ?? $default; }
    public function range(string $default = 'all'): string { return (string) $this->get('range', $default); }
    /** @return array<string, mixed> */
    public function all(): array { return $this->values; }
}
