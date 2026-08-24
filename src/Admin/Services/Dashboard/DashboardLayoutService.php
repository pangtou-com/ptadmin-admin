<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\AdminDashboardWidget;

class DashboardLayoutService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoleWidgets(int $roleId, ?int $tenantId = null): array
    {
        return $this->queryWidgets(AdminDashboardWidget::SUBJECT_ROLE, $roleId, $tenantId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUserWidgets(int $userId, ?int $tenantId = null): array
    {
        return $this->queryWidgets(AdminDashboardWidget::SUBJECT_USER, $userId, $tenantId);
    }

    public function saveRoleWidgets(int $roleId, array $widgets, ?int $tenantId = null): void
    {
        $this->saveWidgets(AdminDashboardWidget::SUBJECT_ROLE, $roleId, $widgets, $tenantId);
    }

    public function saveUserWidgets(int $userId, array $widgets, ?int $tenantId = null): void
    {
        $this->saveWidgets(AdminDashboardWidget::SUBJECT_USER, $userId, $widgets, $tenantId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryWidgets(string $subjectType, int $subjectId, ?int $tenantId = null): array
    {
        return AdminDashboardWidget::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->when(null !== $tenantId, function ($builder) use ($tenantId): void {
                $builder->where('tenant_id', $tenantId);
            }, function ($builder): void {
                $builder->whereNull('tenant_id');
            })
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get()
            ->map(static function ($record): array {
                return [
                    'widget_code' => (string) $record->widget_code,
                    'enabled' => (bool) $record->enabled,
                    'sort' => (int) $record->sort,
                    'layout' => (array) ($record->layout_json ?? array()),
                    'config' => (array) ($record->config_json ?? array()),
                ];
            })->values()->all();
    }

    /**
     * @param array<int, array<string, mixed>> $widgets
     */
    private function saveWidgets(string $subjectType, int $subjectId, array $widgets, ?int $tenantId = null): void
    {
        $normalized = $this->normalizeWidgets($widgets);

        DB::transaction(function () use ($subjectType, $subjectId, $tenantId, $normalized): void {
            AdminDashboardWidget::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                    $query->where('tenant_id', $tenantId);
                }, function ($query): void {
                    $query->whereNull('tenant_id');
                })
                ->delete();

            foreach ($normalized as $item) {
                AdminDashboardWidget::query()->create([
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'tenant_id' => $tenantId,
                    'widget_code' => (string) $item['widget_code'],
                    'enabled' => (bool) $item['enabled'],
                    'sort' => (int) $item['sort'],
                    'layout_json' => [] === $item['layout'] ? null : $item['layout'],
                    'config_json' => [] === $item['config'] ? null : $item['config'],
                ]);
            }
        });
    }

    /**
     * @param array<int, array<string, mixed>> $widgets
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWidgets(array $widgets): array
    {
        $results = array();

        foreach ($widgets as $widget) {
            if (!\is_array($widget)) {
                continue;
            }

            $code = trim((string) ($widget['widget_code'] ?? ''));
            if ('' === $code) {
                continue;
            }

            $results[$code] = [
                'widget_code' => $code,
                'enabled' => (bool) ($widget['enabled'] ?? true),
                'sort' => (int) ($widget['sort'] ?? 0),
                'layout' => $this->normalizeLayout((array) ($widget['layout'] ?? array())),
                'config' => array_values((array) ($widget['config'] ?? array())) === (array) ($widget['config'] ?? array())
                    ? (array) ($widget['config'] ?? array())
                    : (array) ($widget['config'] ?? array()),
            ];
        }

        return array_values($results);
    }

    /**
     * @param array<string, mixed> $layout
     *
     * @return array<string, int>
     */
    private function normalizeLayout(array $layout): array
    {
        $results = array();

        foreach (['x', 'y', 'w', 'h', 'min_w', 'min_h', 'max_w', 'max_h'] as $field) {
            if (!array_key_exists($field, $layout)) {
                continue;
            }

            $results[$field] = (int) $layout[$field];
        }

        return $results;
    }
}
