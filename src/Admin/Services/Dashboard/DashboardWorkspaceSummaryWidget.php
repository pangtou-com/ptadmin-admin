<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\StatResult;

final class DashboardWorkspaceSummaryWidget implements DashboardWidget
{
    private CoreDashboardWidgetSupport $support;

    public function __construct(CoreDashboardWidgetSupport $support)
    {
        $this->support = $support;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.workspace-summary', '我的工作概览'))
            ->type('stats')->group('core')->icon('Briefcase')->sort(60)
            ->description('当前账号可用资源与待处理信息')
            ->defaultLayout(['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(15);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): StatResult
    {
        $notifications = $this->support->notifications($context, 1);
        return (new StatResult())
            ->metric('roles', '我的角色', $this->support->roleCount($context), '个')
            ->metric('resources', '可访问资源', count($this->support->visibleResources($context)), '项')
            ->metric('unread_notifications', '未读通知', $notifications['unread'], '条', $notifications['unread'] > 0 ? 'warning' : 'success')
            ->meta(['user_id' => $context->userId()]);
    }
}
