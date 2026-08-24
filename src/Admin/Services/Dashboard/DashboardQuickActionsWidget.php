<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\CardResult;
use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;

final class DashboardQuickActionsWidget implements DashboardWidget
{
    private CoreDashboardWidgetSupport $support;

    public function __construct(CoreDashboardWidgetSupport $support)
    {
        $this->support = $support;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.quick-actions', '快捷入口'))
            ->type('card')->group('core')->icon('LayoutGrid')->sort(50)
            ->description('快速进入当前账号常用后台资源')
            ->defaultLayout(['x' => 6, 'y' => 0, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(15);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): CardResult
    {
        $result = new CardResult();
        $count = 0;
        foreach ($this->support->visibleResources($context) as $resource) {
            if (1 !== (int) ($resource['is_nav'] ?? 0) || '' === (string) ($resource['route'] ?? '')) {
                continue;
            }
            $result->item(
                (string) $resource['name'],
                (string) $resource['title'],
                '',
                (string) $resource['route']
            );
            if (++$count >= 8) {
                break;
            }
        }

        return $result->meta(['count' => $count]);
    }
}
