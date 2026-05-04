<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\BaseBootstrap;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Services\Dashboard\DashboardLayoutService;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class PTAdminDashboardComposeApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Addon::swap(new ComposeDashboardAddonManager(array(), array()));

        parent::tearDown();
    }

    public function test_dashboard_console_falls_back_to_default_enabled_widgets_for_founder_without_assignments(): void
    {
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

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/dashboard');

        $response->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'key' => 'dashboard.default',
                'title' => '仪表盘',
                'widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'title' => '内容概览',
                        'layout' => array(
                            'x' => 0,
                            'y' => 0,
                            'w' => 6,
                            'h' => 4,
                        ),
                        'config' => array(
                            'range' => 'today',
                        ),
                        'source' => array(
                            'type' => 'default',
                        ),
                    ),
                ),
            ),
        ));

        self::assertCount(1, (array) $response->json('data.widgets'));
        self::assertSame('cms.overview', $response->json('data.widgets.0.code'));
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
            ->getJson('/system/dashboard');

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
            ->getJson('/system/dashboard?tenant_id=23');

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
