<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\BaseBootstrap;
use PTAdmin\Admin\Models\NotificationMessage;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Admin\Services\ApplicationStatusSyncService;
use PTAdmin\Admin\Services\Dashboard\DashboardWidgetQueryCache;
use PTAdmin\Admin\Services\Dashboard\DashboardLayoutService;
use PTAdmin\Admin\Services\PlatformSnapshotService;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Contracts\AdminDashboardWidgetActionHandlerInterface;
use PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface;
use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetDefinition;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Contracts\Dashboard\StatResult;

class PTAdminDashboardApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Addon::swap(new FakeDashboardAddonManager(array(), array()));
        File::delete((string) config('ptadmin.platform_snapshot_path'));
        File::delete((string) config('ptadmin.application_status_path'));
        File::deleteDirectory(public_path('dashboard-sync-admin'));

        parent::tearDown();
    }

    public function test_dashboard_endpoints_require_admin_login(): void
    {
        $this->createAdminsTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/ptadmin/dashboard/widgets')
            ->assertOk()
            ->assertJson(array(
                'code' => 419,
                'message' => '未登录',
            ));

        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/ptadmin/dashboard/application-sync')
            ->assertOk()
            ->assertJson(array(
                'code' => 419,
                'message' => '未登录',
            ));

        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/ptadmin/dashboard/widgets/query', array('widgets' => array()))
            ->assertOk()
            ->assertJson(array(
                'code' => 419,
                'message' => '未登录',
            ));

        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/ptadmin/dashboard/frontend/update')
            ->assertOk()
            ->assertJson(array(
                'code' => 419,
                'message' => '未登录',
            ));
    }

    public function test_application_sync_refreshes_application_and_frontend_status(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_sync',
            'nickname' => 'Founder Dashboard Sync',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);
        config()->set('ptadmin.web_prefix', 'dashboard-sync-admin');
        $frontendVersion = '0.1.27';
        $publishedLockPath = public_path(admin_web_prefix().'/.release-lock.json');
        File::ensureDirectoryExists(dirname($publishedLockPath));
        File::put($publishedLockPath, json_encode(['version' => $frontendVersion], JSON_UNESCAPED_SLASHES));
        $versionParts = array_map('intval', explode('.', $frontendVersion));
        $versionParts[2] = ($versionParts[2] ?? 0) + 1;
        $latestFrontendVersion = implode('.', array_slice(array_pad($versionParts, 3, 0), 0, 3));

        $statusPath = (string) config('ptadmin.application_status_path');
        File::ensureDirectoryExists(dirname($statusPath));
        file_put_contents($statusPath, json_encode([
            'status' => 'success',
            'last_attempted_at' => date(DATE_ATOM),
            'last_succeeded_at' => date(DATE_ATOM),
            'next_attempt_at' => date(DATE_ATOM, time() + 3600),
            'failure_count' => 0,
            'last_error' => null,
            'response' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $applicationStatus = new class() extends ApplicationStatusSyncService {
            public bool $forced = false;

            public function __construct()
            {
            }

            public function sync(bool $force = false): array
            {
                $this->forced = $force;

                return $this->read();
            }
        };
        $platformSnapshot = new class($latestFrontendVersion) extends PlatformSnapshotService {
            public bool $refreshed = false;
            private string $latestFrontendVersion;

            public function __construct(string $latestFrontendVersion)
            {
                $this->latestFrontendVersion = $latestFrontendVersion;
            }

            public function refreshFrontendVersion(): array
            {
                $this->refreshed = true;
                $snapshot = [
                    'synced_at' => date(DATE_ATOM),
                    'latest' => ['frontend_version' => $this->latestFrontendVersion],
                    'framework' => ['security_alerts' => []],
                    'addons' => [],
                    'meta' => ['frontend_manifest_synced_at' => date(DATE_ATOM)],
                ];
                $path = (string) config('ptadmin.platform_snapshot_path');
                File::ensureDirectoryExists(dirname($path));
                file_put_contents($path, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                return $snapshot;
            }
        };
        $this->app->instance(ApplicationStatusSyncService::class, $applicationStatus);
        $this->app->instance(PlatformSnapshotService::class, $platformSnapshot);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/application-sync')
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.frontend_version', $frontendVersion)
            ->assertJsonPath('data.frontend_latest_version', $latestFrontendVersion)
            ->assertJsonPath('data.frontend_update_required', true);

        self::assertTrue($applicationStatus->forced);
        self::assertTrue($platformSnapshot->refreshed);
    }

    public function test_dashboard_widget_endpoints_return_registered_addon_widgets(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard',
            'nickname' => 'Founder Dashboard',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $widgetsResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/dashboard/widgets?group=content');

        $widgetsResponse->assertOk()
            ->assertJson(array(
                'code' => 0,
                'data' => array(
                    'results' => array(
                        array(
                            'code' => 'cms.overview',
                            'title' => '内容概览',
                            'group' => 'content',
                            'resource_code' => 'cms.dashboard',
                        ),
                    ),
                ),
            ));

        self::assertCount(1, (array) $widgetsResponse->json('data.results'));

        $queryResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/cms.overview/query', array(
                'query' => array(
                    'refresh' => 1,
                    'custom' => 'ok',
                ),
            ));

        $queryResponse->assertOk()
            ->assertJson(array(
                'code' => 0,
                'data' => array(
                    'widget' => array(
                        'code' => 'cms.overview',
                        'title' => '内容概览',
                        'group' => 'content',
                    ),
                    'data' => array(
                        'type' => 'echo',
                        'payload' => array(
                            'range' => 'today',
                            'refresh' => 1,
                            'custom' => 'ok',
                        ),
                        'context' => array(
                            'user_id' => $founder->id,
                            'is_founder' => true,
                            'resource_code' => 'cms.dashboard',
                            'addon_code' => 'cms',
                        ),
                    ),
                ),
            ));
    }

    public function test_typed_dashboard_widget_is_registered_and_serialized_for_console(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_typed_dashboard',
            'nickname' => 'Founder Typed Dashboard',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new FakeDashboardAddonManager(
            array('typed' => array('code' => 'typed', 'title' => 'Typed')),
            array('typed' => new TypedDashboardBootstrap())
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/typed.overview/query', array(
                'query' => array('range' => 'month'),
            ))
            ->assertOk()
            ->assertJsonPath('data.widget.code', 'typed.overview')
            ->assertJsonPath('data.widget.type', 'stats')
            ->assertJsonPath('data.data.type', 'stats')
            ->assertJsonPath('data.data.items.0.code', 'records')
            ->assertJsonPath('data.data.items.0.value', 3);
    }

    public function test_dashboard_query_returns_error_when_widget_does_not_exist(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_missing',
            'nickname' => 'Founder Dashboard Missing',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/unknown.widget/query', array(
                'query' => array(),
            ))
            ->assertStatus(500);
    }

    public function test_dashboard_batch_query_returns_partial_results_and_reuses_batch_cache(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_batch',
            'nickname' => 'Founder Dashboard Batch',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        FakeDashboardWidgetHandler::$snapshotCalls = 0;
        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/query', array(
                'widgets' => array(
                    array('code' => 'cms.overview', 'query' => array('range' => 'today')),
                    array('code' => 'cms.shortcut', 'query' => array('refresh' => 1)),
                    array('code' => 'missing.widget', 'query' => array()),
                ),
            ));

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data.results')
            ->assertJsonPath('data.results.0.code', 'cms.overview')
            ->assertJsonPath('data.results.0.error', null)
            ->assertJsonPath('data.results.1.code', 'cms.shortcut')
            ->assertJsonPath('data.results.1.error', null)
            ->assertJsonPath('data.results.2.code', 'missing.widget')
            ->assertJsonPath('data.results.2.data', null)
            ->assertJsonPath('data.results.2.error.code', 'dashboard_widget_query_failed');

        self::assertSame(1, FakeDashboardWidgetHandler::$snapshotCalls);
    }

    public function test_core_dashboard_widgets_read_notifications_operations_and_empty_resource_state(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'founder_dashboard_core_data',
            'nickname' => 'Founder Core Data',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($admin);
        $now = time();

        $notification = NotificationMessage::query()->create([
            'audience_type' => 'admin',
            'source_type' => 'system',
            'source_code' => 'system',
            'category' => 'notice',
            'level' => 'info',
            'title' => '欢迎使用 PTAdmin',
            'content' => '这是一个测试通知',
            'action_type' => 'none',
            'created_by' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        NotificationReceipt::query()->create([
            'notification_id' => $notification->id,
            'receiver_type' => NotificationReceipt::RECEIVER_ADMIN,
            'receiver_id' => $admin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        OperationRecord::query()->create([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'nickname' => $admin->nickname,
            'title' => '查看仪表盘',
            'url' => '/ptadmin/dashboard',
            'method' => 'POST',
            'status' => 'success',
            'response_code' => 200,
            'response_time' => 12.5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        app(DashboardLayoutService::class)->saveUserWidgets((int) $admin->id, [
            ['widget_code' => 'ptadmin.workspace-summary', 'enabled' => true],
            ['widget_code' => 'ptadmin.notifications', 'enabled' => true],
            ['widget_code' => 'ptadmin.recent-operations', 'enabled' => true],
            ['widget_code' => 'ptadmin.operation-trend', 'enabled' => true],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/query', ['widgets' => [
                ['code' => 'ptadmin.workspace-summary'],
                ['code' => 'ptadmin.version-notices'],
                ['code' => 'ptadmin.notifications'],
                ['code' => 'ptadmin.recent-operations'],
                ['code' => 'ptadmin.operation-trend'],
            ]]);

        $response->assertOk()
            ->assertJsonPath('data.results.0.data.items.0.code', 'roles')
            ->assertJsonPath('data.results.0.data.items.1.code', 'resources')
            ->assertJsonPath('data.results.0.data.items.1.value', 9)
            ->assertJsonPath('data.results.0.data.items.2.value', 1)
            ->assertJsonPath('data.results.2.data.items.0.title', '欢迎使用 PTAdmin')
            ->assertJsonPath('data.results.3.data.items.0.title', '查看仪表盘')
            ->assertJsonPath('data.results.4.data.type', 'trend');

        self::assertSame(1, array_sum((array) $response->json('data.results.4.data.series')));
    }

    public function test_dashboard_batch_query_rejects_more_than_24_widgets(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_batch_limit',
            'nickname' => 'Founder Dashboard Batch Limit',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        $widgets = array_map(static function (int $index): array {
            return array('code' => 'cms.widget.'.$index);
        }, range(1, 25));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/query', array('widgets' => $widgets))
            ->assertStatus(422);
    }

    public function test_dashboard_action_endpoint_executes_registered_widget_action(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_action',
            'nickname' => 'Founder Dashboard Action',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/cms.overview/actions/reload_summary', array(
                'payload' => array(
                    'source' => 'test',
                ),
            ))
            ->assertOk()
            ->assertJson(array(
                'code' => 0,
                'data' => array(
                    'widget' => array(
                        'code' => 'cms.overview',
                    ),
                    'action' => array(
                        'code' => 'reload_summary',
                        'label' => '刷新统计',
                        'type' => 'request',
                    ),
                    'data' => array(
                        'type' => 'action_result',
                        'action_code' => 'reload_summary',
                        'payload' => array(
                            'source' => 'test',
                        ),
                    ),
                ),
            ));
    }

    public function test_dashboard_action_returns_error_when_action_does_not_exist(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_action_missing',
            'nickname' => 'Founder Dashboard Action Missing',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($founder);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/cms.overview/actions/not_found', array())
            ->assertStatus(500);
    }

    public function test_dashboard_query_returns_forbidden_when_widget_is_not_assigned_to_current_user(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_forbidden',
            'nickname' => 'Member Dashboard Forbidden',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($member);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/cms.overview/query', array(
                'query' => array(),
            ))
            ->assertStatus(500);
    }

    public function test_dashboard_query_uses_saved_user_widget_config_and_tenant_scope(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_tenant_query',
            'nickname' => 'Member Dashboard Tenant Query',
            'is_founder' => 1,
        ));
        $token = $this->issueAdminToken($member);

        Addon::swap(new FakeDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                    'module' => 'cms',
                ),
            ),
            array(
                'cms' => new FakeDashboardBootstrap(),
            )
        ));

        /** @var DashboardLayoutService $layoutService */
        $layoutService = app(DashboardLayoutService::class);
        $layoutService->saveUserWidgets((int) $member->id, array(
            array(
                'widget_code' => 'cms.overview',
                'enabled' => true,
                'sort' => 18,
                'layout' => array(
                    'x' => 1,
                    'y' => 1,
                    'w' => 7,
                    'h' => 4,
                ),
                'config' => array(
                    'range' => 'month',
                    'channel' => 'private',
                ),
            ),
        ), 9);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/dashboard/widgets/cms.overview/query', array(
                'tenant_id' => 9,
                'query' => array(
                    'refresh' => 1,
                ),
            ));

        $response->assertOk()
            ->assertJson(array(
                'code' => 0,
                'data' => array(
                    'widget' => array(
                        'code' => 'cms.overview',
                        'sort' => 18,
                        'config' => array(
                            'range' => 'month',
                            'channel' => 'private',
                        ),
                        'source' => array(
                            'type' => 'user',
                        ),
                    ),
                    'data' => array(
                        'payload' => array(
                            'range' => 'month',
                            'channel' => 'private',
                            'refresh' => 1,
                        ),
                        'context' => array(
                            'user_id' => $member->id,
                            'tenant_id' => 9,
                            'widget_config' => array(
                                'range' => 'month',
                                'channel' => 'private',
                            ),
                        ),
                    ),
                ),
            ));
    }
}

final class FakeDashboardAddonManager
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

    public function getAddonBootstrap(string $addonCode): ?BaseBootstrap
    {
        return $this->bootstraps[$addonCode] ?? null;
    }

    public function getAddon(string $addonCode): FakeDashboardAddonConfig
    {
        return new FakeDashboardAddonConfig($this->addons[$addonCode] ?? array());
    }
}

final class TypedDashboardBootstrap extends BaseBootstrap
{
    /** @return array<int, object> */
    public function getAdminDashboardWidgetDefinitions(string $addonCode, array $addonInfo = array()): array
    {
        return array(new TypedDashboardWidget());
    }
}

final class TypedDashboardWidget implements DashboardWidget
{
    public function definition(): DashboardWidgetDefinition
    {
        return (new DashboardWidgetDefinition('typed.overview', 'Typed Overview'))
            ->type('stats')
            ->group('typed')
            ->cacheFor(60);
    }

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): StatResult
    {
        return (new StatResult())
            ->metric('records', 'Records', 3)
            ->meta(array('range' => $query->range(), 'user_id' => $context->userId()));
    }
}

final class FakeDashboardAddonConfig
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

final class FakeDashboardBootstrap extends BaseBootstrap
{
    /**
     * @param string               $addonCode
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
                'icon' => 'layui-icon-chart',
                'sort' => 100,
                'resource_code' => 'cms.dashboard',
                'description' => 'CMS 内容统计概览',
                'default_query' => array(
                    'range' => 'today',
                ),
                'capabilities' => array(
                    'refresh' => true,
                    'range' => true,
                    'filters' => false,
                    'drilldown' => false,
                ),
                'actions' => array(
                    array(
                        'code' => 'open_cms',
                        'label' => '进入 CMS',
                        'type' => 'link',
                        'target' => '/cms',
                    ),
                    array(
                        'code' => 'reload_summary',
                        'label' => '刷新统计',
                        'type' => 'request',
                        'confirm_text' => '确认刷新当前统计吗？',
                        'meta' => array(
                            'intent' => 'refresh',
                        ),
                    ),
                ),
                'query_handler' => FakeDashboardWidgetHandler::class,
                'cache_ttl' => 0,
            ),
            array(
                'code' => 'cms.shortcut',
                'title' => '快捷入口',
                'type' => 'card',
                'group' => 'shortcut',
                'sort' => 50,
                'resource_code' => '',
                'description' => 'CMS 快捷入口卡片',
                'default_query' => array(),
                'capabilities' => array(
                    'refresh' => false,
                    'range' => false,
                    'filters' => false,
                    'drilldown' => false,
                ),
                'actions' => array(),
                'query_handler' => FakeDashboardWidgetHandler::class,
                'cache_ttl' => 0,
            ),
        );
    }
}

final class FakeDashboardWidgetHandler implements AdminDashboardWidgetHandlerInterface, AdminDashboardWidgetActionHandlerInterface
{
    public static int $snapshotCalls = 0;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function query(array $query, array $definition, array $context = array()): array
    {
        $queryCache = $context['query_cache'] ?? null;
        $snapshot = $queryCache instanceof DashboardWidgetQueryCache
            ? $queryCache->remember('fake.dashboard', static function (): string {
                self::$snapshotCalls++;

                return 'shared';
            })
            : 'uncached';

        return array(
            'type' => 'echo',
            'payload' => $query,
            'context' => $context,
            'snapshot' => $snapshot,
            'definition_code' => (string) ($definition['code'] ?? ''),
        );
    }

    /**
     * @param string               $actionCode
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     * @param array<string, mixed> $actionDefinition
     *
     * @return array<string, mixed>
     */
    public function executeAction(string $actionCode, array $payload, array $definition, array $context = array(), array $actionDefinition = array()): array
    {
        return array(
            'type' => 'action_result',
            'action_code' => $actionCode,
            'payload' => $payload,
            'context' => $context,
            'definition_code' => (string) ($definition['code'] ?? ''),
            'action' => $actionDefinition,
        );
    }
}
