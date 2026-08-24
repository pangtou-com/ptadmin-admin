<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

use PTAdmin\Admin\Services\Dashboard\DashboardWidgetQueryCache;

final class DashboardWidgetContext
{
    private int $userId;
    private ?int $tenantId;
    private bool $founder;
    private string $addonCode;
    private string $widgetCode;
    private string $resourceCode;
    private DashboardWidgetQueryCache $queryCache;

    public function __construct(int $userId, ?int $tenantId, bool $founder, string $addonCode, string $widgetCode, string $resourceCode, DashboardWidgetQueryCache $queryCache)
    {
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->founder = $founder;
        $this->addonCode = $addonCode;
        $this->widgetCode = $widgetCode;
        $this->resourceCode = $resourceCode;
        $this->queryCache = $queryCache;
    }

    public function userId(): int { return $this->userId; }
    public function tenantId(): ?int { return $this->tenantId; }
    public function isFounder(): bool { return $this->founder; }
    public function addonCode(): string { return $this->addonCode; }
    public function widgetCode(): string { return $this->widgetCode; }
    public function resourceCode(): string { return $this->resourceCode; }
    public function queryCache(): DashboardWidgetQueryCache { return $this->queryCache; }
}
