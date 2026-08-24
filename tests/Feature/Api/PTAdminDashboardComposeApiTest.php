<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\BaseBootstrap;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Services\Dashboard\DashboardLayoutService;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class PTAdminDashboardComposeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('ptadmin.web_prefix', 'dashboard-compose-admin');
        $this->writePublishedFrontendLock($this->bundledFrontendVersion());
        $this->writeApplicationStatus([
            'status' => 'success',
            'last_attempted_at' => date(DATE_ATOM),
            'last_succeeded_at' => date(DATE_ATOM),
            'last_error' => null,
            'response' => [],
        ]);
    }

    protected function tearDown(): void
    {
        Addon::swap(new ComposeDashboardAddonManager(array(), array()));
        Cache::forget('ptadmin:addon_user_keys');
        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
        File::delete((string) config('ptadmin.platform_snapshot_path'));
        File::delete(dirname((string) config('ptadmin.platform_snapshot_path')).'/snapshot.lock');
        File::delete((string) config('ptadmin.application_status_path'));
        File::delete(dirname((string) config('ptadmin.application_status_path')).'/application-status.lock');
        File::deleteDirectory(public_path('dashboard-compose-admin'));

        parent::tearDown();
    }

    public function test_dashboard_console_falls_back_to_default_enabled_widgets_for_founder_without_assignments(): void
    {
        $frontendVersion = $this->currentFrontendVersion();

        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_compose',
            'nickname' => 'Founder Compose',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new ComposeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ComposeDashboardBootstrap(),
            )
        ));
        $this->writePlatformSnapshot([
            'synced_at' => date(DATE_ATOM),
            'latest' => [
                'frontend_version' => '0.1.12',
                'framework_version' => '1.1.8',
            ],
            'addons' => [],
            'framework' => [
                'security_alerts' => [],
            ],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'key' => 'dashboard.default',
                'title' => '仪表盘',
                'summary' => array(
                    'frontend_version' => $frontendVersion,
                    'frontend_latest_version' => '0.1.12',
                    'frontend_update_required' => false,
                    'backend_version' => $this->currentPackageVersion(),
                    'backend_latest_version' => '1.1.8',
                    'backend_update_required' => false,
                    'update_required' => false,
                    'addon_update_pending' => false,
                    'security_alert_pending' => false,
                ),
            ),
        ));

        self::assertCount(5, (array) $response->json('data.widgets'));
        self::assertNotContains('ptadmin.version-notices', array_column((array) $response->json('data.widgets'), 'code'));
        self::assertNotContains('ptadmin.notifications', array_column((array) $response->json('data.widgets'), 'code'));
        self::assertNotContains('cms.shortcuts', array_column((array) $response->json('data.widgets'), 'code'));
    }

    public function test_dashboard_console_has_core_widgets_without_installed_addons(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $admin = $this->createAdminAccount(array(
            'username' => 'admin_dashboard_without_addons',
            'nickname' => 'Dashboard Without Addons',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($admin);
        Addon::swap(new ComposeDashboardAddonManager(array(), array()));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.widgets.0.code', 'ptadmin.workspace-summary')
            ->assertJsonPath('data.widgets.0.source.type', 'default')
            ->assertJsonPath('data.widgets.2.code', 'ptadmin.recent-operations')
            ->assertJsonPath('data.widgets.3.code', 'ptadmin.operation-trend');

        self::assertCount(4, (array) $response->json('data.widgets'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/query', array('widgets' => array(
                array('code' => 'ptadmin.workspace-summary'),
                array('code' => 'ptadmin.quick-actions'),
                array('code' => 'ptadmin.recent-operations'),
                array('code' => 'ptadmin.operation-trend'),
            )))
            ->assertOk()
            ->assertJsonPath('data.results.0.data.type', 'stats')
            ->assertJsonPath('data.results.0.data.items.0.value', 0)
            ->assertJsonPath('data.results.1.data.type', 'card')
            ->assertJsonPath('data.results.1.data.items', [])
            ->assertJsonPath('data.results.2.data.type', 'list')
            ->assertJsonPath('data.results.2.data.items', [])
            ->assertJsonPath('data.results.3.data.type', 'trend')
            ->assertJsonPath('data.results.3.data.categories', [])
            ->assertJsonPath('data.results.3.data.series', []);
    }

    public function test_dashboard_console_summary_marks_authorized_and_pending_addon_updates(): void
    {
        $frontendVersion = $this->currentFrontendVersion();

        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $admin = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_summary',
            'nickname' => 'Founder Summary',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($admin);

        Addon::swap(new class()
        {
            public function getAddons(): array
            {
                return [];
            }

            public function hasInstalledAddon(string $code): bool
            {
                return 'cms' === $code;
            }

            public function getAddonVersion(string $code): ?string
            {
                return 'cms' === $code ? '1.0.0' : null;
            }
        });
        $this->writePlatformSnapshot([
            'synced_at' => date(DATE_ATOM),
            'latest' => [
                'frontend_version' => '0.1.12',
                'framework_version' => '1.1.8',
            ],
            'addons' => [
                [
                    'code' => 'cms',
                    'latest_version' => '1.2.0',
                    'authorized' => false,
                    'security_alerts' => [],
                ],
            ],
            'framework' => [
                'security_alerts' => [],
            ],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.summary.addon_update_pending', true)
            ->assertJsonPath('data.summary.addon_updates.0.code', 'cms')
            ->assertJsonPath('data.summary.frontend_version', $frontendVersion)
            ->assertJsonPath('data.summary.backend_version', $this->currentPackageVersion());

        app(DashboardLayoutService::class)->saveUserWidgets((int) $admin->id, array(
            array(
                'widget_code' => 'ptadmin.version-notices',
                'enabled' => true,
            ),
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/ptadmin.version-notices/query', array())
            ->assertOk()
            ->assertJsonPath('data.data.type', 'list')
            ->assertJsonPath('data.data.items.2.id', 'addon-cms')
            ->assertJsonPath('data.data.items.2.status', 'warning');
    }

    public function test_dashboard_console_summary_marks_update_required_when_platform_has_newer_versions(): void
    {
        $frontendVersion = $this->currentFrontendVersion();
        $newerFrontendVersion = $this->nextPatchVersion($frontendVersion);
        $backendVersion = $this->currentPackageVersion();
        $newerBackendVersion = $this->nextPatchVersion($backendVersion);

        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_update_required',
            'nickname' => 'Founder Update Required',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new ComposeDashboardAddonManager(array(), array()));
        $this->writePlatformSnapshot([
            'synced_at' => date(DATE_ATOM),
            'latest' => [
                'frontend_version' => $newerFrontendVersion,
                'framework_version' => $newerBackendVersion,
            ],
            'addons' => [],
            'framework' => [
                'security_alerts' => [],
            ],
        ]);
        $this->writeApplicationStatus([
            'status' => 'success',
            'last_attempted_at' => date(DATE_ATOM),
            'last_succeeded_at' => date(DATE_ATOM),
            'next_attempt_at' => date(DATE_ATOM, time() + 3600),
            'failure_count' => 0,
            'last_error' => null,
            'response' => [
                'latest' => [
                    'frontend_version' => $frontendVersion,
                    'backend_version' => '',
                ],
            ],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.summary.frontend_version', $frontendVersion)
            ->assertJsonPath('data.summary.frontend_latest_version', $newerFrontendVersion)
            ->assertJsonPath('data.summary.backend_version', $backendVersion)
            ->assertJsonPath('data.summary.backend_latest_version', $newerBackendVersion)
            ->assertJsonPath('data.summary.backend_update_required', true)
            ->assertJsonPath('data.summary.update_required', true);
    }

    public function test_dashboard_console_merges_role_defaults_and_user_overrides_and_filters_unavailable_widgets(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_compose',
            'nickname' => 'Member Compose',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($member);

        $role = AdminRole::query()->create(array(
            'code' => 'dashboard_operator',
            'name' => 'Dashboard Operator',
            'description' => 'Dashboard role',
            'status' => 1,
            'sort' => 100,
        ));

        app(AdminRoleServiceInterface::class)->syncUserRoles((int) $member->id, array((int) $role->id));

        Addon::swap(new ComposeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ComposeDashboardBootstrap(),
            )
        ));

        /** @var DashboardLayoutService $layoutService */
        $layoutService = app(DashboardLayoutService::class);
        $layoutService->saveRoleWidgets((int) $role->id, array(
            array(
                'widget_code' => 'cms.overview',
                'enabled' => true,
                'sort' => 10,
                'layout' => array(
                    'x' => 1,
                    'w' => 8,
                ),
                'config' => array(
                    'range' => 'week',
                ),
            ),
            array(
                'widget_code' => 'cms.secret',
                'enabled' => true,
                'sort' => 5,
            ),
        ));
        $layoutService->saveUserWidgets((int) $member->id, array(
            array(
                'widget_code' => 'cms.overview',
                'enabled' => true,
                'sort' => 99,
                'layout' => array(
                    'y' => 2,
                ),
                'config' => array(
                    'range' => 'month',
                ),
            ),
        ));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'sort' => 99,
                        'layout' => array(
                            'x' => 1,
                            'y' => 2,
                            'w' => 8,
                            'h' => 4,
                        ),
                        'config' => array(
                            'range' => 'month',
                        ),
                        'source' => array(
                            'type' => 'user',
                        ),
                    ),
                ),
            ),
        ));

        self::assertCount(1, (array) $response->json('data.widgets'));
        self::assertSame('cms.overview', $response->json('data.widgets.0.code'));
    }

    public function test_dashboard_console_supports_tenant_scoped_role_assignments(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_tenant_compose',
            'nickname' => 'Member Tenant Compose',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($member);

        $role = AdminRole::query()->create(array(
            'code' => 'dashboard_tenant_operator',
            'name' => 'Dashboard Tenant Operator',
            'description' => 'Dashboard tenant role',
            'status' => 1,
            'sort' => 80,
        ));

        app(AdminRoleServiceInterface::class)->syncUserRoles((int) $member->id, array((int) $role->id), 23);

        Addon::swap(new ComposeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ComposeDashboardBootstrap(),
            )
        ));

        /** @var DashboardLayoutService $layoutService */
        $layoutService = app(DashboardLayoutService::class);
        $layoutService->saveRoleWidgets((int) $role->id, array(
            array(
                'widget_code' => 'cms.overview',
                'enabled' => true,
                'sort' => 35,
                'layout' => array(
                    'x' => 2,
                    'y' => 1,
                    'w' => 10,
                    'h' => 6,
                ),
                'config' => array(
                    'range' => 'quarter',
                ),
            ),
        ), 23);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard?tenant_id=23');

        $response->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'sort' => 35,
                        'layout' => array(
                            'x' => 2,
                            'y' => 1,
                            'w' => 10,
                            'h' => 6,
                        ),
                        'config' => array(
                            'range' => 'quarter',
                        ),
                        'source' => array(
                            'type' => 'role',
                            'role_ids' => array($role->id),
                        ),
                    ),
                ),
            ),
        ));
    }

    public function test_dashboard_console_does_not_restore_default_preset_when_saved_widgets_are_not_visible(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_hidden_layout',
            'nickname' => 'Member Hidden Layout',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($member);

        Addon::swap(new ComposeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ComposeDashboardBootstrap(),
            )
        ));

        app(DashboardLayoutService::class)->saveUserWidgets((int) $member->id, array(
            array(
                'widget_code' => 'cms.secret',
                'enabled' => true,
            ),
        ));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(0, 'data.widgets');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writePlatformSnapshot(array $payload): void
    {
        $path = (string) config('ptadmin.platform_snapshot_path');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /** @param array<string, mixed> $payload */
    private function writeApplicationStatus(array $payload): void
    {
        $path = (string) config('ptadmin.application_status_path');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function currentPackageVersion(): string
    {
        return get_frame_version();
    }

    private function currentFrontendVersion(): string
    {
        $lockPath = public_path(admin_web_prefix().'/.release-lock.json');
        $lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);

        return (string) ($lock['version'] ?? '');
    }

    private function bundledFrontendVersion(): string
    {
        $lockPath = dirname(__DIR__, 3).'/resources/admin-frontend/.release-lock.json';
        $lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);

        return (string) ($lock['version'] ?? '');
    }

    private function writePublishedFrontendLock(string $version): void
    {
        $path = public_path(admin_web_prefix().'/.release-lock.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(['version' => $version], JSON_UNESCAPED_SLASHES));
    }

    private function nextPatchVersion(string $version): string
    {
        $parts = array_map('intval', explode('.', $version));

        while (\count($parts) < 3) {
            $parts[] = 0;
        }

        $parts[2]++;

        return implode('.', array_slice($parts, 0, 3));
    }

}

final class ComposeDashboardAddonManager
{
    /** @var array<string, array<string, mixed>> */
    private array $addons;

    /** @var array<string, BaseBootstrap> */
    private array $bootstraps;

    /**
     * @param array<string, array<string, mixed>> $addons
     * @param array<string, BaseBootstrap>        $bootstraps
     */
    public function __construct(array $addons, array $bootstraps)
    {
        $this->addons = $addons;
        $this->bootstraps = $bootstraps;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }

    /** @return array<string, array<string, mixed>> */
    public function getInstalledAddons(): array
    {
        return $this->addons;
    }

    public function getAddonVersion(string $code): ?string
    {
        return isset($this->addons[$code]) ? (string) ($this->addons[$code]['version'] ?? '') : null;
    }

    public function getAddonBootstrap(string $addonCode): ?BaseBootstrap
    {
        return $this->bootstraps[$addonCode] ?? null;
    }

    public function getAddon(string $addonCode): ComposeDashboardAddonConfig
    {
        return new ComposeDashboardAddonConfig($this->addons[$addonCode] ?? array());
    }
}

final class ComposeDashboardAddonConfig
{
    /** @var array<string, mixed> */
    private array $addon;

    /**
     * @param array<string, mixed> $addon
     */
    public function __construct(array $addon)
    {
        $this->addon = $addon;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddons(): array
    {
        return $this->addon;
    }
}

final class ComposeDashboardBootstrap extends BaseBootstrap
{
    /**
     * @param array<string, mixed> $addonInfo
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAdminDashboardWidgetDefinitions(string $addonCode, array $addonInfo = array()): array
    {
        return array(
            array(
                'code' => 'cms.overview',
                'title' => '内容概览',
                'type' => 'stats',
                'group' => 'content',
                'sort' => 20,
                'resource_code' => '',
                'description' => 'CMS Overview',
                'default_enabled' => true,
                'default_query' => array(
                    'range' => 'today',
                ),
                'default_layout' => array(
                    'x' => 0,
                    'y' => 0,
                    'w' => 6,
                    'h' => 4,
                ),
                'query_handler' => ComposeDashboardWidgetHandler::class,
            ),
            array(
                'code' => 'cms.secret',
                'title' => '隐藏概览',
                'type' => 'stats',
                'group' => 'content',
                'sort' => 10,
                'resource_code' => 'system.resources',
                'description' => 'Hidden widget',
                'default_enabled' => false,
                'default_layout' => array(
                    'x' => 6,
                    'y' => 0,
                    'w' => 6,
                    'h' => 4,
                ),
                'query_handler' => ComposeDashboardWidgetHandler::class,
            ),
        );
    }
}

final class ComposeDashboardWidgetHandler implements \PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function query(array $query, array $definition, array $context = array()): array
    {
        return array(
            'query' => $query,
            'definition' => $definition,
            'context' => $context,
        );
    }
}
