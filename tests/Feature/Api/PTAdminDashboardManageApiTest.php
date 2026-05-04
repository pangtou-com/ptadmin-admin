<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\BaseBootstrap;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminDashboardManageApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Addon::swap(new ManageDashboardAddonManager(array(), array()));

        parent::tearDown();
    }

    public function test_role_and_user_dashboard_widget_configuration_endpoints_persist_selected_widgets(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount(array(
            'username' => 'founder_dashboard_manage',
            'nickname' => 'Founder Manage',
            'is_founder' => 1,
        ));
        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_manage',
            'nickname' => 'Member Manage',
        ));
        $token = $this->issueAdminToken($founder);

        $role = AdminRole::query()->create(array(
            'code' => 'dashboard_editor',
            'name' => 'Dashboard Editor',
            'description' => 'Dashboard editor role',
            'status' => 1,
            'sort' => 50,
        ));

        Addon::swap(new ManageDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ManageDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/dashboard/roles/'.$role->id.'/widgets', array(
                'widgets' => array(
                    array(
                        'widget_code' => 'cms.overview',
                        'enabled' => true,
                        'sort' => 20,
                        'layout' => array(
                            'x' => 0,
                            'y' => 0,
                            'w' => 6,
                            'h' => 4,
                        ),
                        'config' => array(
                            'range' => 'week',
                        ),
                    ),
                ),
            ))
            ->assertOk()
            ->assertJson(array(
                'code' => 0,
            ));

        $roleResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/dashboard/roles/'.$role->id.'/widgets');

        $roleResponse->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'selected_widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'config' => array(
                            'range' => 'week',
                        ),
                    ),
                ),
            ),
        ));

        self::assertSame('cms.overview', $roleResponse->json('data.available_widgets.0.code'));
        self::assertSame(20, $roleResponse->json('data.selected_widgets.0.sort'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/dashboard/users/'.$member->id.'/widgets', array(
                'widgets' => array(
                    array(
                        'widget_code' => 'cms.overview',
                        'enabled' => true,
                        'sort' => 88,
                        'layout' => array(
                            'x' => 2,
                            'y' => 1,
                            'w' => 8,
                            'h' => 5,
                        ),
                        'config' => array(
                            'range' => 'month',
                        ),
                    ),
                ),
            ))
            ->assertOk()
            ->assertJson(array(
                'code' => 0,
            ));

        $userResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/dashboard/users/'.$member->id.'/widgets');

        $userResponse->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'selected_widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'layout' => array(
                            'x' => 2,
                            'y' => 1,
                            'w' => 8,
                            'h' => 5,
                        ),
                        'config' => array(
                            'range' => 'month',
                        ),
                    ),
                ),
            ),
        ));
    }

    public function test_current_user_can_manage_personal_dashboard_widgets_and_console_uses_latest_override(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $member = $this->createAdminAccount(array(
            'username' => 'member_dashboard_self_manage',
            'nickname' => 'Member Self Manage',
            'is_founder' => 0,
        ));
        $token = $this->issueAdminToken($member);

        Addon::swap(new ManageDashboardAddonManager(
            array(
                'cms' => array(
                    'code' => 'cms',
                    'title' => '内容管理',
                ),
            ),
            array(
                'cms' => new ManageDashboardBootstrap(),
            )
        ));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/dashboard/me/widgets', array(
                'widgets' => array(
                    array(
                        'widget_code' => 'cms.overview',
                        'enabled' => true,
                        'sort' => 66,
                        'layout' => array(
                            'x' => 3,
                            'y' => 2,
                            'w' => 9,
                            'h' => 5,
                        ),
                        'config' => array(
                            'range' => 'month',
                        ),
                    ),
                ),
            ))
            ->assertOk()
            ->assertJson(array(
                'code' => 0,
            ));

        $meResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/dashboard/me/widgets');

        $meResponse->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'available_widgets' => array(
                    array(
                        'code' => 'cms.overview',
                    ),
                ),
                'selected_widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'sort' => 66,
                        'layout' => array(
                            'x' => 3,
                            'y' => 2,
                            'w' => 9,
                            'h' => 5,
                        ),
                        'config' => array(
                            'range' => 'month',
                        ),
                    ),
                ),
            ),
        ));

        $consoleResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/dashboard');

        $consoleResponse->assertOk()->assertJson(array(
            'code' => 0,
            'data' => array(
                'widgets' => array(
                    array(
                        'code' => 'cms.overview',
                        'sort' => 66,
                        'layout' => array(
                            'x' => 3,
                            'y' => 2,
                            'w' => 9,
                            'h' => 5,
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
    }
}

final class ManageDashboardAddonManager
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

    public function getAddon(string $addonCode): ManageDashboardAddonConfig
    {
        return new ManageDashboardAddonConfig($this->addons[$addonCode] ?? array());
    }
}

final class ManageDashboardAddonConfig
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

final class ManageDashboardBootstrap extends BaseBootstrap
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
                'sort' => 10,
                'resource_code' => '',
                'description' => 'Manage widget',
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
                'query_handler' => ManageDashboardWidgetHandler::class,
            ),
        );
    }
}

final class ManageDashboardWidgetHandler implements \PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface
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
