<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\BaseBootstrap;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Contracts\AdminDashboardWidgetActionHandlerInterface;
use PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface;

class PTAdminDashboardApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Addon::swap(new FakeDashboardAddonManager(array(), array()));

        parent::tearDown();
    }

    public function test_dashboard_endpoints_require_admin_login(): void
    {
        $this->createSystemsTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/dashboard/widgets')
            ->assertOk()
            ->assertJson(array(
                'code' => 10001,
                'message' => '未登录',
            ));
    }

    public function test_dashboard_widget_endpoints_return_registered_addon_widgets(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem(array(
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
            ->getJson('/system/dashboard/widgets?group=content');

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
            ->postJson('/system/dashboard/widgets/cms.overview/query', array(
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

    public function test_dashboard_query_returns_error_when_widget_does_not_exist(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem(array(
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
            ->postJson('/system/dashboard/widgets/unknown.widget/query', array(
                'query' => array(),
            ))
            ->assertOk()
            ->assertJson(array(
                'code' => 10000,
                'message' => '仪表盘组件不存在',
            ));
    }

    public function test_dashboard_action_endpoint_executes_registered_widget_action(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem(array(
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
            ->postJson('/system/dashboard/widgets/cms.overview/actions/reload_summary', array(
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
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem(array(
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
            ->postJson('/system/dashboard/widgets/cms.overview/actions/not_found', array())
            ->assertOk()
            ->assertJson(array(
                'code' => 10000,
                'message' => '仪表盘动作不存在',
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
            'type' => 'echo',
            'payload' => $query,
            'context' => $context,
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
