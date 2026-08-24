<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

abstract class DashboardWidgetResult
{
    /** @var array<string, mixed> */
    protected array $payload;

    /** @param array<string, mixed> $payload */
    protected function __construct(string $type, array $payload = array())
    {
        $this->payload = array_merge(array('type' => $type), $payload);
    }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->payload; }

    /** @param array<string, mixed> $meta */
    public function meta(array $meta): self { $this->payload['meta'] = $meta; return $this; }
}
