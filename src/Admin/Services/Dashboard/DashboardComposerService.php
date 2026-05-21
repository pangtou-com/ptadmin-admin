<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Services\PlatformSnapshotService;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class DashboardComposerService
{
    private DashboardWidgetRegistryService $registry;
    private DashboardLayoutService $layoutService;
    private AdminRoleServiceInterface $adminRoleService;
    private PlatformSnapshotService $platformSnapshotService;

    public function __construct(
        DashboardWidgetRegistryService $registry,
        DashboardLayoutService $layoutService,
        AdminRoleServiceInterface $adminRoleService,
        PlatformSnapshotService $platformSnapshotService
    ) {
        $this->registry = $registry;
        $this->layoutService = $layoutService;
        $this->adminRoleService = $adminRoleService;
        $this->platformSnapshotService = $platformSnapshotService;
    }

    /**
     * @param mixed $user
     *
     * @return array<string, mixed>
     */
    public function composeForUser($user, ?int $tenantId = null): array
    {
        return [
            'key' => 'dashboard.default',
            'title' => '仪表盘',
            'description' => '平台核心经营数据与工作台概览',
            'updatedAt' => date('Y-m-d H:i:s'),
            'summary' => $this->buildSummary(),
            'widgets' => $this->widgetsForUser($user, $tenantId),
        ];
    }

    /**
     * @param mixed $user
     *
     * @return array<int, array<string, mixed>>
     */
    public function widgetsForUser($user, ?int $tenantId = null): array
    {
        $definitions = $this->registry->visibleFor($user);
        $definitionMap = [];
        foreach ($definitions as $definition) {
            $definitionMap[(string) $definition['code']] = $definition;
        }

        $results = [];

        foreach ($this->resolveRoleAssignments((int) data_get($user, 'id', 0), $tenantId) as $item) {
            $code = (string) ($item['widget_code'] ?? '');
            if ('' === $code || !isset($definitionMap[$code])) {
                continue;
            }

            $results[$code] = $this->mergeWithDefinition($definitionMap[$code], $item, [
                'type' => 'role',
                'role_ids' => array_values(array_unique(array_map('intval', (array) ($item['role_ids'] ?? array())))),
            ]);
        }

        foreach ($this->layoutService->getUserWidgets((int) data_get($user, 'id', 0), $tenantId) as $item) {
            $code = (string) ($item['widget_code'] ?? '');
            if ('' === $code || !isset($definitionMap[$code])) {
                continue;
            }

            if (isset($results[$code])) {
                $results[$code] = $this->mergeWithOverride($results[$code], $item, ['type' => 'user']);
                continue;
            }

            $results[$code] = $this->mergeWithDefinition($definitionMap[$code], $item, ['type' => 'user']);
        }

        if ([] === $results && $this->isFounder($user)) {
            foreach ($definitionMap as $code => $definition) {
                if (!(bool) ($definition['default_enabled'] ?? true)) {
                    continue;
                }

                $results[$code] = $this->buildDefaultWidget($definition);
            }
        }

        $widgets = array_values(array_filter($results, static function (array $widget): bool {
            return (bool) ($widget['enabled'] ?? true);
        }));

        usort($widgets, static function (array $left, array $right) {
            $sortCompare = ((int) ($right['sort'] ?? 0)) <=> ((int) ($left['sort'] ?? 0));
            if (0 !== $sortCompare) {
                return $sortCompare;
            }

            $leftY = (int) data_get($left, 'layout.y', PHP_INT_MAX);
            $rightY = (int) data_get($right, 'layout.y', PHP_INT_MAX);
            if ($leftY !== $rightY) {
                return $leftY <=> $rightY;
            }

            $leftX = (int) data_get($left, 'layout.x', PHP_INT_MAX);
            $rightX = (int) data_get($right, 'layout.x', PHP_INT_MAX);
            if ($leftX !== $rightX) {
                return $leftX <=> $rightX;
            }

            return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });

        return $widgets;
    }

    /**
     * @param mixed $user
     */
    public function hasWidgetForUser($user, string $widgetCode, ?int $tenantId = null): bool
    {
        foreach ($this->widgetsForUser($user, $tenantId) as $widget) {
            if ((string) ($widget['code'] ?? '') === $widgetCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveRoleAssignments(int $userId, ?int $tenantId = null): array
    {
        $results = [];

        foreach ($this->adminRoleService->getUserRoles($userId, $tenantId) as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            foreach ($this->layoutService->getRoleWidgets($roleId, $tenantId) as $item) {
                $code = (string) ($item['widget_code'] ?? '');
                if ('' === $code) {
                    continue;
                }

                if (!isset($results[$code])) {
                    $results[$code] = array_merge($item, [
                        'role_ids' => [$roleId],
                    ]);
                    continue;
                }

                $results[$code] = array_merge($results[$code], $item);
                $results[$code]['role_ids'][] = $roleId;
            }
        }

        foreach ($results as &$item) {
            $item['role_ids'] = array_values(array_unique(array_map('intval', (array) ($item['role_ids'] ?? array()))));
        }
        unset($item);

        return array_values($results);
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $item
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function mergeWithDefinition(array $definition, array $item, array $source): array
    {
        $widget = $this->registry->toPublicDefinition($definition);
        $widget['enabled'] = array_key_exists('enabled', $item)
            ? (bool) $item['enabled']
            : (bool) ($definition['default_enabled'] ?? true);
        $widget['sort'] = array_key_exists('sort', $item) ? (int) $item['sort'] : (int) ($definition['sort'] ?? 0);
        $widget['layout'] = array_merge((array) ($definition['default_layout'] ?? array()), (array) ($item['layout'] ?? array()));
        $widget['config'] = array_merge((array) ($definition['default_query'] ?? array()), (array) ($item['config'] ?? array()));
        $widget['source'] = $source;

        return $widget;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $item
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function mergeWithOverride(array $base, array $item, array $source): array
    {
        $base['enabled'] = array_key_exists('enabled', $item) ? (bool) $item['enabled'] : (bool) ($base['enabled'] ?? true);
        $base['sort'] = array_key_exists('sort', $item) ? (int) $item['sort'] : (int) ($base['sort'] ?? 0);
        $base['layout'] = array_merge((array) ($base['layout'] ?? array()), (array) ($item['layout'] ?? array()));
        $base['config'] = array_merge((array) ($base['config'] ?? array()), (array) ($item['config'] ?? array()));
        $base['source'] = array_merge((array) ($base['source'] ?? array()), $source);

        return $base;
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function buildDefaultWidget(array $definition): array
    {
        $widget = $this->registry->toPublicDefinition($definition);
        $widget['enabled'] = (bool) ($definition['default_enabled'] ?? true);
        $widget['layout'] = (array) ($definition['default_layout'] ?? array());
        $widget['config'] = (array) ($definition['default_query'] ?? array());
        $widget['source'] = ['type' => 'default'];

        return $widget;
    }

    /**
     * @param mixed $user
     */
    private function isFounder($user): bool
    {
        return 1 === (int) data_get($user, 'is_founder', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(): array
    {
        $backendVersion = get_frame_version();
        $frontendLock = $this->readAdminFrontendLock();
        $frontendVersion = trim((string) ($frontendLock['version'] ?? ''));
        $snapshot = $this->platformSnapshotService->read();
        $this->platformSnapshotService->scheduleRefresh();
        $latestFrontendVersion = trim((string) data_get($snapshot, 'latest.frontend_version', ''));
        $latestFrameworkVersion = trim((string) data_get($snapshot, 'latest.framework_version', ''));
        $addonSnapshots = (array) ($snapshot['addons'] ?? []);
        $addonUpdatePending = $this->hasPendingAddonUpdates($addonSnapshots);
        $frameworkUpdateRequired = $this->isVersionOutdated($backendVersion, $latestFrameworkVersion);
        $frontendUpdateRequired = $this->isVersionOutdated($frontendVersion, $latestFrontendVersion);
        $securityAlertPending = $this->hasSecurityAlerts($snapshot, $addonSnapshots);

        return [
            'frontend_version' => $frontendVersion,
            'frontend_latest_version' => $latestFrontendVersion,
            'frontend_update_required' => $frontendUpdateRequired,
            'backend_version' => $backendVersion,
            'backend_latest_version' => $latestFrameworkVersion,
            'backend_update_required' => $frameworkUpdateRequired,
            'update_required' => $frontendUpdateRequired || $frameworkUpdateRequired || $addonUpdatePending || $securityAlertPending,
            'addon_update_pending' => $addonUpdatePending,
            'security_alert_pending' => $securityAlertPending,
            'last_platform_sync_at' => (string) ($snapshot['synced_at'] ?? ''),
            'platform_snapshot_stale' => $this->platformSnapshotService->isStale($snapshot),
        ];
    }
    
    /**
     * @return array<string, mixed>
     */
    private function readAdminFrontendLock(): array
    {
        $lockPath = $this->resolvePackagePath('resources/admin-frontend/.release-lock.json');
        if (!is_file($lockPath) || !is_readable($lockPath)) {
            return [];
        }

        $content = file_get_contents($lockPath);
        $payload = false === $content ? null : json_decode($content, true);

        return \is_array($payload) ? $payload : [];
    }

    private function isVersionOutdated(string $currentVersion, string $latestVersion): bool
    {
        $current = $this->normalizeVersion($currentVersion);
        $latest = $this->normalizeVersion($latestVersion);
        if ('' === $current || '' === $latest) {
            return false;
        }

        return version_compare($latest, $current, '>');
    }

    /**
     * @param array<int, mixed> $addonSnapshots
     */
    private function hasPendingAddonUpdates(array $addonSnapshots): bool
    {
        foreach ($addonSnapshots as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? ''));
            if ('' === $code || !Addon::hasInstalledAddon($code)) {
                continue;
            }

            $installedVersion = $this->normalizeVersion((string) Addon::getAddonVersion($code));
            $latestVersion = $this->normalizeVersion((string) ($item['latest_version'] ?? ''));
            if ('' !== $installedVersion && '' !== $latestVersion && version_compare($latestVersion, $installedVersion, '>')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeVersion(string $version): string
    {
        $normalized = trim($version);
        if ('' === $normalized) {
            return '';
        }

        return ltrim($normalized, 'vV');
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<int, mixed> $addonSnapshots
     */
    private function hasSecurityAlerts(array $snapshot, array $addonSnapshots): bool
    {
        foreach ((array) data_get($snapshot, 'framework.security_alerts', []) as $item) {
            if (\is_array($item) || \is_string($item)) {
                return true;
            }
        }

        foreach ($addonSnapshots as $item) {
            if (!\is_array($item)) {
                continue;
            }

            foreach ((array) ($item['security_alerts'] ?? []) as $alert) {
                if (\is_array($alert) || \is_string($alert)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolvePackagePath(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $basePath = base_path($relativePath);
        if (is_file($basePath) || is_dir($basePath)) {
            return $basePath;
        }

        return dirname(__DIR__, 4).DIRECTORY_SEPARATOR.$relativePath;
    }
}
