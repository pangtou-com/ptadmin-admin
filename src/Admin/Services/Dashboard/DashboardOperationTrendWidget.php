<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\TrendResult;

final class DashboardOperationTrendWidget implements DashboardWidget
{
    private CoreDashboardWidgetSupport $support;

    public function __construct(CoreDashboardWidgetSupport $support)
    {
        $this->support = $support;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.operation-trend', '操作趋势'))
            ->type('trend')->group('core')->icon('Activity')->sort(10)
            ->description('最近七天的后台操作数量')
            ->defaultLayout(['x' => 6, 'y' => 4, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(15);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): TrendResult
    {
        $snapshot = $this->support->operations($context, 1);
        return (new TrendResult($snapshot['categories'], $snapshot['series'], 'line'))
            ->meta(['range' => '7d']);
    }
}
