<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\ListResult;

final class DashboardVersionNoticeWidget implements DashboardWidget
{
    private DashboardSummaryService $summaryService;

    public function __construct(DashboardSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('ptadmin.version-notices', '版本与更新'))
            ->type('list')->group('core')->icon('RefreshCw')->sort(40)
            ->description('后端、前端、插件和安全状态')
            ->defaultEnabled(false)
            ->defaultLayout(['x' => 0, 'y' => 8, 'w' => 6, 'h' => 4, 'min_w' => 4, 'min_h' => 4])
            ->cacheFor(30);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): ListResult
    {
        $summary = $context->queryCache()->remember('ptadmin.dashboard.core.version-summary', function (): array {
            return $this->summaryService->summary(false);
        });
        $result = new ListResult();
        $backendUpdate = (bool) ($summary['backend_update_required'] ?? false);
        $frontendUpdate = (bool) ($summary['frontend_update_required'] ?? false);
        $result->item('backend', '后台内核', $this->versionMeta($summary['backend_version'] ?? '', $summary['backend_latest_version'] ?? ''), $backendUpdate ? 'warning' : 'success');
        $result->item('frontend', '后台前端', $this->versionMeta($summary['frontend_version'] ?? '', $summary['frontend_latest_version'] ?? ''), $frontendUpdate ? 'warning' : 'success');
        foreach ((array) ($summary['addon_updates'] ?? []) as $addon) {
            if (!is_array($addon)) {
                continue;
            }
            $code = (string) ($addon['code'] ?? 'addon');
            $result->item('addon-'.$code, '插件更新：'.$code, $this->versionMeta($addon['installed_version'] ?? '', $addon['latest_version'] ?? ''), 'warning');
        }
        $result->item('security', '安全检查', (bool) ($summary['security_alert_pending'] ?? false) ? '发现安全提醒，请及时处理' : '暂无安全提醒', (bool) ($summary['security_alert_pending'] ?? false) ? 'danger' : 'success');

        return $result;
    }

    private function versionMeta($current, $latest): string
    {
        $current = trim((string) $current);
        $latest = trim((string) $latest);
        if ('' === $latest || '' === $current || $current === $latest) {
            return '' !== $current ? '当前 '.$current : '版本信息暂不可用';
        }
        return '当前 '.$current.'，最新 '.$latest;
    }
}
