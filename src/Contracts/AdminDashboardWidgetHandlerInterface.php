<?php

declare(strict_types=1);

namespace PTAdmin\Contracts;

interface AdminDashboardWidgetHandlerInterface
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function query(array $query, array $definition, array $context = array()): array;
}
