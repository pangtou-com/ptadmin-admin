<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\AddonManager;
use PTAdmin\Admin\Services\AddonPlatformService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAddonManagementApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));

        parent::tearDown();
    }

    public function test_local_addon_list_status_and_config_endpoints_work_with_installed_addons(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $this->writeAddon('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'develop' => true,
            'providers' => [],
            'entry' => [
                'installer' => 'Addon\\Cms\\Installer',
                'bootstrap' => 'Addon\\Cms\\Bootstrap',
            ],
            'resources' => [
                'config' => './Config',
            ],
        ], [
            'code' => 'cms',
            'name' => '内容管理系统',
            'admin_route_prefix' => 'cms',
            'api_route_prefix' => 'api/cms',
        ], true, false);

        $this->writeAddon('Seo', [
            'id' => 'seo',
            'code' => 'seo',
            'name' => 'SEO工具',
            'title' => 'SEO工具',
            'version' => '0.2.0',
            'providers' => [],
        ], [], false, true);

        Addon::swap(new AddonManager());

        $listResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/cloud/local/apps');

        $listResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 2,
                'results' => [
                    [
                        'code' => 'cms',
                        'enabled' => 1,
                        'configurable' => 1,
                        'has_frontend_modules' => 1,
                    ],
                    [
                        'code' => 'seo',
                        'enabled' => 0,
                        'configurable' => 0,
                        'has_frontend_modules' => 0,
                    ],
                ],
            ],
        ]);

        $statusResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/cms/status');

        $statusResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'code' => 'cms',
                'enabled' => 1,
                'configurable' => 1,
            ],
        ]);

        $configResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/cms/config');

        $configResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'code' => 'cms',
                'supported' => true,
                'values' => [
                    'admin_route_prefix' => 'cms',
                    'api_route_prefix' => 'api/cms',
                ],
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/addons/cms/config', [
                'values' => [
                    'admin_route_prefix' => 'cms-admin',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'admin_route_prefix' => 'cms-admin',
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/cms/config')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'admin_route_prefix' => 'cms-admin',
                        'api_route_prefix' => 'api/cms',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'addon_cms',
        ]);
        $this->assertDatabaseHas('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'basic',
        ]);
        $this->assertDatabaseHas('system_configs', [
            'name' => 'admin_route_prefix',
            'value' => 'cms-admin',
        ]);
    }

    public function test_local_addon_list_marks_settings_registration_addon_as_configurable(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-settings-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $this->writeAddon('Shop', [
            'id' => 'shop',
            'code' => 'shop',
            'name' => '商城插件',
            'title' => '商城插件',
            'version' => '1.1.0',
            'providers' => [],
        ]);
        $this->writeAddonSettingsRegistration('Shop', [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'layout' => [
                            'mode' => 'block',
                        ],
                        'fields' => [
                            [
                                'name' => 'app_name',
                                'type' => 'text',
                                'label' => '应用名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'app_name' => '商城插件',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/cloud/local/apps')
            ->assertOk()
            ->assertJsonPath('data.results.0.code', 'shop')
            ->assertJsonPath('data.results.0.configurable', 1);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/shop/status')
            ->assertOk()
            ->assertJsonPath('data.code', 'shop')
            ->assertJsonPath('data.configurable', 1);
    }

    public function test_local_addon_list_excludes_none_mode_settings_registration_from_configurable(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-settings-none-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $this->writeAddon('Toolbox', [
            'id' => 'toolbox',
            'code' => 'toolbox',
            'name' => '工具插件',
            'title' => '工具插件',
            'version' => '0.9.0',
            'providers' => [],
        ]);
        $this->writeAddonSettingsRegistration('Toolbox', [
            'enabled' => true,
            'mode' => 'none',
            'sections' => [],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/cloud/local/apps')
            ->assertOk()
            ->assertJsonPath('data.results.0.code', 'toolbox')
            ->assertJsonPath('data.results.0.configurable', 0);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/toolbox/status')
            ->assertOk()
            ->assertJsonPath('data.code', 'toolbox')
            ->assertJsonPath('data.configurable', 0);
    }

    public function test_cloud_install_endpoint_streams_progress_messages(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-stream-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = new class() extends AddonPlatformService
        {
            public function __construct()
            {
            }

            public function installFromCloud(string $code, int $versionId = 0, bool $force = false): array
            {
                echo json_encode([
                    'type' => 'info',
                    'message' => '开始安装插件',
                    'data' => [
                        'code' => $code,
                        'addon_version_id' => $versionId,
                        'force' => $force,
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                return [
                    'code' => $code,
                    'installed' => true,
                ];
            }
        };
        $this->app->instance(AddonPlatformService::class, $service);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/addons/install/cloud', [
                'code' => 'cms',
                'addon_version_id' => 12,
                'force' => true,
            ]);

        $response->assertOk();
        self::assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        self::assertStringContainsString('"type":"info"', $content);
        self::assertStringContainsString('"message":"开始安装插件"', $content);
        self::assertStringContainsString('"type":"success"', $content);
        self::assertStringContainsString('"installed":true', $content);
        self::assertStringNotContainsString('"code":0,"message":"操作成功","data"', $content);
    }

    public function test_init_endpoint_streams_progress_messages(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-init-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = new class() extends AddonPlatformService
        {
            public function __construct()
            {
            }

            protected function performAddonInitialization(string $code, string $title = '', bool $force = false): array
            {
                $directory = base_path('addons/'.ucfirst($code));
                File::ensureDirectoryExists($directory.'/Config');

                echo json_encode([
                    'type' => 'info',
                    'message' => '开始初始化插件',
                    'data' => [
                        'code' => $code,
                        'title' => $title,
                        'force' => $force,
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                return [
                    'code' => $code,
                    'title' => $title,
                    'path' => $directory,
                ];
            }
        };
        $this->app->instance(AddonPlatformService::class, $service);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/addons/init', [
                'code' => 'cms',
                'title' => 'CMS Demo',
                'force' => true,
            ]);

        $response->assertOk();
        self::assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        self::assertStringContainsString('"type":"info"', $content);
        self::assertStringContainsString('"message":"开始初始化插件"', $content);
        self::assertStringContainsString('"type":"success"', $content);
        self::assertStringContainsString('"title":"CMS Demo"', $content);
        self::assertTrue(File::exists(base_path('addons/Cms/Config/settings.php')));
        self::assertStringContainsString("'mode' => 'hosted'", (string) File::get(base_path('addons/Cms/Config/settings.php')));
        self::assertStringContainsString("'app_name' => 'CMS Demo'", (string) File::get(base_path('addons/Cms/Config/settings.php')));
    }

    public function test_frontend_pull_endpoint_streams_progress_messages(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-frontend-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = new class() extends AddonPlatformService
        {
            public function __construct()
            {
            }

            public function pullFrontend(string $code, string $template = 'vue3-admin', string $ref = 'main', string $source = '', bool $force = false): array
            {
                echo json_encode([
                    'type' => 'info',
                    'message' => '开始拉取前端模板',
                    'data' => [
                        'code' => $code,
                        'template' => $template,
                        'ref' => $ref,
                        'source' => $source,
                        'force' => $force,
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                return [
                    'code' => $code,
                    'source' => 'official',
                    'template' => $template,
                    'ref' => $ref,
                    'path' => '/tmp/'.$code.'/Frontend',
                ];
            }
        };
        $this->app->instance(AddonPlatformService::class, $service);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/addons/cms/frontend/pull', [
                'template' => 'vue3-admin',
                'ref' => 'main',
                'source' => 'gitee',
                'force' => true,
            ]);

        $response->assertOk();
        self::assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        self::assertStringContainsString('"type":"info"', $content);
        self::assertStringContainsString('"message":"开始拉取前端模板"', $content);
        self::assertStringContainsString('"type":"success"', $content);
        self::assertStringContainsString('"source":"official"', $content);
        self::assertStringContainsString('"path":"/tmp/cms/Frontend"', $content);
    }

    public function test_sync_resources_endpoint_delegates_to_platform_service(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-sync-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = new class() extends AddonPlatformService
        {
            public function __construct()
            {
            }

            public function syncResources(string $code): array
            {
                return [
                    'code' => $code,
                    'synced' => true,
                    'enabled' => 1,
                ];
            }
        };
        $this->app->instance(AddonPlatformService::class, $service);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/addons/cms/resources/sync')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'code' => 'cms',
                    'synced' => true,
                    'enabled' => 1,
                ],
            ]);
    }

    public function test_uninstall_purges_hosted_settings_when_cleanup_strategy_is_purge(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'addon-uninstall-admin',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $this->writeAddon('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ]);
        $this->writeAddonSettingsRegistration('Cms', [
            'enabled' => true,
            'mode' => 'hosted',
            'managed_by' => 'system',
            'cleanup' => [
                'on_uninstall' => 'purge',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'app_name',
                                'type' => 'text',
                                'label' => '应用名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'app_name' => '内容管理系统',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk();

        $this->assertDatabaseHas('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'addon_cms_basic',
        ]);
        $this->assertDatabaseHas('system_configs', [
            'name' => 'app_name',
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/addon-uninstall/cms')
            ->assertOk()
            ->assertJsonPath('data.code', 'cms')
            ->assertJsonPath('data.uninstalled', true);

        $this->assertDatabaseMissing('system_config_groups', [
            'addon_code' => 'cms',
        ]);
        $this->assertDatabaseMissing('system_configs', [
            'name' => 'app_name',
        ]);
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $config
     */
    private function writeAddon(string $basePath, array $manifest, array $config = [], bool $withModules = false, bool $disabled = false): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory.'/Config');

        File::put($directory.'/manifest.json', (string) json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ([] !== $config) {
            File::put(
                $directory.'/Config/config.php',
                "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n"
            );
        }

        if ($withModules) {
            File::put($directory.'/frontend.json', (string) json_encode([
                'modules' => [
                    [
                        'key' => strtolower($manifest['code'] ?? $basePath),
                        'title' => $manifest['title'] ?? $manifest['name'] ?? $basePath,
                        'enabled' => 1,
                        'runtime' => 'local',
                        'route_base' => '/'.strtolower($manifest['code'] ?? $basePath),
                        'pages' => [
                            [
                                'key' => strtolower($manifest['code'] ?? $basePath).'.index',
                                'path' => '/'.strtolower($manifest['code'] ?? $basePath),
                                'route_name' => strtolower($manifest['code'] ?? $basePath).'-index',
                                'title' => $manifest['title'] ?? $manifest['name'] ?? $basePath,
                            ],
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if ($disabled) {
            File::put($directory.'/disable', '');
        }
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function writeAddonSettingsRegistration(string $basePath, array $settings): void
    {
        $directory = base_path('addons/'.$basePath.'/Config');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/settings.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($settings, true).";\n"
        );
    }
}
