<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Services\AdminFrontendBuildService;
use PTAdmin\Admin\Services\ApplicationStatusSyncService;
use PTAdmin\Admin\Services\PlatformSnapshotService;

final class DashboardSummaryService
{
    private PlatformSnapshotService $platformSnapshotService;
    private ApplicationStatusSyncService $applicationStatusSyncService;
    private AdminFrontendBuildService $adminFrontendBuildService;

    public function __construct(
        PlatformSnapshotService $platformSnapshotService,
        ApplicationStatusSyncService $applicationStatusSyncService,
        ?AdminFrontendBuildService $adminFrontendBuildService = null
    ) {
        $this->platformSnapshotService = $platformSnapshotService;
        $this->applicationStatusSyncService = $applicationStatusSyncService;
        $this->adminFrontendBuildService = $adminFrontendBuildService ?? new AdminFrontendBuildService();
    }

    /** @return array<string, mixed> */
    public function summary(bool $scheduleSync = true): array
    {
        $backendVersion = get_frame_version();
        $frontendVersion = $this->adminFrontendBuildService->publishedVersion(base_path(), admin_web_prefix());
        $snapshot = $this->platformSnapshotService->read();
        $this->platformSnapshotService->scheduleRefresh();
        $applicationStatus = $this->applicationStatusSyncService->read();
        if ($scheduleSync) {
            $this->applicationStatusSyncService->scheduleSync();
        }
        $applicationSummary = $this->applicationStatusSyncService->publicSummary($applicationStatus);
        $latestFrontendVersion = trim((string) data_get($snapshot, 'latest.frontend_version', ''));
        $latestFrameworkVersion = trim((string) data_get($snapshot, 'latest.framework_version', ''));
        $applicationLatestFrontendVersion = trim((string) data_get($applicationStatus, 'response.latest.frontend_version', ''));
        $applicationLatestBackendVersion = trim((string) data_get($applicationStatus, 'response.latest.backend_version', ''));
        if ('' !== $applicationLatestFrontendVersion) {
            $latestFrontendVersion = $this->latestVersion($latestFrontendVersion, $applicationLatestFrontendVersion);
        }
        if ('' !== $applicationLatestBackendVersion) {
            $latestFrameworkVersion = $this->latestVersion($latestFrameworkVersion, $applicationLatestBackendVersion);
        }
        $addonSnapshots = (array) ($snapshot['addons'] ?? []);
        $addonUpdates = $this->mergeAddonUpdates(
            $this->pendingAddonUpdates($addonSnapshots),
            (array) ($applicationSummary['addon_updates'] ?? [])
        );
        $applicationSummary['addon_updates'] = $addonUpdates;
        $addonUpdatePending = [] !== $addonUpdates;
        $frameworkUpdateRequired = $this->isVersionOutdated($backendVersion, $latestFrameworkVersion);
        $frontendUpdateRequired = $this->isVersionOutdated($frontendVersion, $latestFrontendVersion);
        $securityAlertPending = $this->hasSecurityAlerts($snapshot, $addonSnapshots)
            || $this->hasDangerAdvice((array) ($applicationSummary['application_advice'] ?? []));

        return array_merge([
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
        ], $applicationSummary);
    }

    /** @param array<int, mixed> $advice */
    private function hasDangerAdvice(array $advice): bool
    {
        foreach ($advice as $item) {
            if (\is_array($item) && 'danger' === (string) ($item['level'] ?? '')) {
                return true;
            }
        }
        return false;
    }

    private function isVersionOutdated(string $currentVersion, string $latestVersion): bool
    {
        $current = $this->normalizeVersion($currentVersion);
        $latest = $this->normalizeVersion($latestVersion);
        return '' !== $current && '' !== $latest && version_compare($latest, $current, '>');
    }

    /**
     * @param array<int, mixed> $addonSnapshots
     * @return array<int, array<string, mixed>>
     */
    private function pendingAddonUpdates(array $addonSnapshots): array
    {
        $updates = [];
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
                $updates[] = [
                    'code' => $code,
                    'installed_version' => $installedVersion,
                    'latest_version' => $latestVersion,
                    'update_available' => true,
                    'authorized' => array_key_exists('authorized', $item) ? (bool) $item['authorized'] : null,
                    'security_alerts' => (array) ($item['security_alerts'] ?? []),
                ];
            }
        }
        return $updates;
    }

    /**
     * @param array<int, mixed> $left
     * @param array<int, mixed> $right
     * @return array<int, array<string, mixed>>
     */
    private function mergeAddonUpdates(array $left, array $right): array
    {
        $updates = [];
        foreach (array_merge($left, $right) as $item) {
            if (!\is_array($item)) {
                continue;
            }
            $code = trim((string) ($item['code'] ?? ''));
            if ('' !== $code) {
                $updates[$code] = $item;
            }
        }
        return array_values($updates);
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    private function latestVersion(string $left, string $right): string
    {
        $normalizedLeft = $this->normalizeVersion($left);
        $normalizedRight = $this->normalizeVersion($right);
        if ('' === $normalizedLeft) {
            return $right;
        }
        if ('' === $normalizedRight) {
            return $left;
        }
        return version_compare($normalizedRight, $normalizedLeft, '>') ? $right : $left;
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
}
