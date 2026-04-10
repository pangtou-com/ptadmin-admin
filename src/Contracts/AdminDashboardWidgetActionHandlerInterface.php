<?php

declare(strict_types=1);

namespace PTAdmin\Contracts;

interface AdminDashboardWidgetActionHandlerInterface
{
    /**
     * 执行仪表盘组件动作。
     *
     * @param string               $actionCode
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     * @param array<string, mixed> $actionDefinition
     *
     * @return array<string, mixed>
     */
    public function executeAction(string $actionCode, array $payload, array $definition, array $context = array(), array $actionDefinition = array()): array;
}
