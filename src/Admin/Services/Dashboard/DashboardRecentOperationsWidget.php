<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\ListResult;

final class DashboardRecentOperationsWidget implements DashboardWidget
{
    private CoreDashboardWidgetSupport $support;

    public function __construct(CoreDashboardWidgetSupport $support)
    {
        $this->support = $support;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.recent-operations', '最近操作'))
            ->type('list')->group('core')->icon('History')->sort(20)
            ->description('当前账号最近执行的后台操作')
            ->defaultLayout(['x' => 0, 'y' => 4, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(15);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): ListResult
    {
        $snapshot = $this->support->operations($context, 8);
        $result = new ListResult();
        foreach ($snapshot['items'] as $item) {
            $result->item((string) $item['id'], (string) $item['title'], (string) $item['meta'], (string) $item['status']);
        }
        return $result;
    }
}
