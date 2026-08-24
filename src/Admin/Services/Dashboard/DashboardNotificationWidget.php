<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\ListResult;

final class DashboardNotificationWidget implements DashboardWidget
{
    private CoreDashboardWidgetSupport $support;

    public function __construct(CoreDashboardWidgetSupport $support)
    {
        $this->support = $support;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.notifications', '我的通知/待办'))
            ->type('list')->group('core')->icon('Bell')->sort(30)
            ->description('当前账号的通知和待处理消息')
            ->defaultEnabled(false)
            ->defaultLayout(['x' => 6, 'y' => 4, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(15);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): ListResult
    {
        $snapshot = $this->support->notifications($context, 8);
        $result = new ListResult();
        foreach ($snapshot['items'] as $item) {
            $status = !empty($item['read']) ? 'default' : ('danger' === $item['level'] ? 'danger' : 'warning');
            $meta = trim((string) ($item['created_at'] ?? '')).' · '.(string) ($item['category'] ?? 'notice');
            $result->item((string) $item['id'], (string) $item['title'], trim($meta, ' ·'), $status);
        }
        return $result->meta(['unread' => $snapshot['unread']]);
    }
}
