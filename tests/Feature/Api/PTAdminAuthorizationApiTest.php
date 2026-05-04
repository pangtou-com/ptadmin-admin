<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\AddonManager;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Contracts\Auth\AdminGrantServiceInterface;
use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Support\ValueObjects\GrantPayload;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAuthorizationApiTest extends TestCase
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
        Addon::swap(new AddonManager());

        parent::tearDown();
    }

    public function test_authorization_initialization_routes_are_not_exposed_as_api_endpoints(): void
    {
        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/system/auth/bootstrap-founder', [
                'username' => 'root',
                'password' => 'secret123',
            ])
            ->assertNotFound();

        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/system/auth/bootstrap', [
                'role_code' => 'super_admin',
            ])
            ->assertNotFound();
    }

    public function test_status_endpoint_requires_admin_login(): void
    {
        $this->createAdminsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/auth/status')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_login_and_authenticated_authorization_endpoints_return_expected_payloads(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder',
            'nickname' => 'Founder',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);

        $loginResponse = $this->withHeaders(array_merge($this->jsonApiHeaders(), [
            'User-Agent' => 'PTAdminTest/1.0',
        ]))->postJson('/system/login', [
            'username' => 'founder',
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk();
        self::assertSame(0, $loginResponse->json('code'));
        self::assertSame('founder', $loginResponse->json('data.user.username'));

        $token = (string) $loginResponse->json('data.token');
        self::assertNotSame('', $token);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/status')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'admins' => 1,
                    'founders' => 1,
                    'admin_resources' => AdminResource::query()->count(),
                ],
            ]);

        $resourceResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/resources');

        $resourceResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'roles' => ['创始人'],
            ],
        ]);

        $resources = (array) $resourceResponse->json('data.resources');
        self::assertGreaterThan(0, count($resources));
        self::assertSame('console', $resources[0]['name'] ?? null);
        self::assertSame('/dashboard', $resources[0]['route'] ?? null);
        self::assertSame('console.dashboard', $resources[0]['page_key'] ?? null);
        self::assertSame('HomeFilled', $resources[0]['icon'] ?? null);
        self::assertSame(1, $resources[0]['keep_alive'] ?? null);
        self::assertSame('cloud', $resources[3]['name'] ?? null);
        self::assertSame('/cloud', $resources[3]['route'] ?? null);
        self::assertDatabaseHas('admin_login_logs', [
            'admin_id' => $founder->id,
            'login_account' => 'founder',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PTAdminTest/1.0',
        ]);
    }

    public function test_failed_login_attempts_are_logged_for_unknown_accounts(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $this->withHeaders(array_merge($this->jsonApiHeaders(), [
            'User-Agent' => 'PTAdminTest/1.0',
        ]))->postJson('/system/login', [
            'username' => 'ghost',
            'password' => 'secret123',
        ]);

        self::assertDatabaseHas('admin_login_logs', [
            'admin_id' => null,
            'login_account' => 'ghost',
            'status' => AdminLoginLog::STATUS_USER_NOT_FOUND,
            'reason' => 'account_not_found',
            'user_agent' => 'PTAdminTest/1.0',
        ]);
    }

    public function test_founder_auth_resources_merges_develop_addon_preview_resources(): void
    {
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder-dev',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        app(AdminResourceServiceInterface::class)->syncAddonResources('test', [
            [
                'name' => 'test',
                'title' => '测试插件',
                'type' => 'dir',
                'module' => 'test',
                'addon_code' => 'test',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 0,
            ],
            [
                'name' => 'test.dashboard',
                'title' => '旧概览',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.home',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/test-old',
                'icon' => 'OldIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 10,
            ],
        ]);

        $this->writeDevelopAddon('Test', [
            [
                'name' => 'test',
                'title' => '开发测试',
                'type' => 'dir',
                'module' => 'test',
                'addon_code' => 'test',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 0,
            ],
            [
                'name' => 'test.dashboard',
                'title' => '开发概览',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.home',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/dev-test',
                'icon' => 'NewIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 1,
            ],
            [
                'name' => 'test.preview',
                'title' => '预览页面',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.preview',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/test/preview',
                'icon' => 'PreviewIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 2,
            ],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/resources');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'roles' => ['创始人'],
            ],
        ]);

        $flat = $this->flattenResources((array) $response->json('data.resources'));

        self::assertSame('开发测试', $flat['test']['title'] ?? null);
        self::assertSame('开发概览', $flat['test.dashboard']['title'] ?? null);
        self::assertSame('/dev-test', $flat['test.dashboard']['route'] ?? null);
        self::assertSame('NewIcon', $flat['test.dashboard']['icon'] ?? null);
        self::assertSame('预览页面', $flat['test.preview']['title'] ?? null);
        self::assertSame('/test/preview', $flat['test.preview']['route'] ?? null);
    }

    public function test_non_founder_auth_resources_overlays_existing_develop_resources_without_exposing_preview_only_nodes(): void
    {
        $this->migratePackageTables();

        $member = $this->createAdminAccount([
            'username' => 'member-dev',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($member);

        app(AdminResourceServiceInterface::class)->syncAddonResources('test', [
            [
                'name' => 'test',
                'title' => '测试插件',
                'type' => 'dir',
                'module' => 'test',
                'addon_code' => 'test',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 0,
            ],
            [
                'name' => 'test.dashboard',
                'title' => '旧概览',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.home',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/test-old',
                'icon' => 'OldIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 10,
            ],
        ]);

        app(AdminGrantServiceInterface::class)->syncUserGrants($member->id, [
            new GrantPayload('test.dashboard'),
        ]);

        $this->writeDevelopAddon('Test', [
            [
                'name' => 'test',
                'title' => '开发测试',
                'type' => 'dir',
                'module' => 'test',
                'addon_code' => 'test',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 0,
            ],
            [
                'name' => 'test.dashboard',
                'title' => '开发概览',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.home',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/dev-test',
                'icon' => 'NewIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 1,
            ],
            [
                'name' => 'test.preview',
                'title' => '预览页面',
                'type' => 'nav',
                'module' => 'test',
                'page_key' => 'test.page.preview',
                'addon_code' => 'test',
                'parent' => 'test',
                'route' => '/test/preview',
                'icon' => 'PreviewIcon',
                'is_nav' => 1,
                'status' => 1,
                'sort' => 2,
            ],
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/resources');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'roles' => [],
            ],
        ]);

        $flat = $this->flattenResources((array) $response->json('data.resources'));

        self::assertSame('开发测试', $flat['test']['title'] ?? null);
        self::assertSame('开发概览', $flat['test.dashboard']['title'] ?? null);
        self::assertSame('/dev-test', $flat['test.dashboard']['route'] ?? null);
        self::assertArrayNotHasKey('test.preview', $flat);
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     *
     * @return array<string, array<string, mixed>>
     */
    private function flattenResources(array $resources): array
    {
        $results = [];

        foreach ($resources as $resource) {
            if (!\is_array($resource)) {
                continue;
            }

            $name = (string) ($resource['name'] ?? '');
            if ('' !== $name) {
                $results[$name] = $resource;
            }

            if (isset($resource['children']) && \is_array($resource['children'])) {
                $results = array_merge($results, $this->flattenResources($resource['children']));
            }
        }

        return $results;
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     */
    private function writeDevelopAddon(string $basePath, array $definitions): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory);

        File::put($directory.'/manifest.json', (string) json_encode([
            'id' => strtolower($basePath),
            'code' => strtolower($basePath),
            'name' => $basePath,
            'title' => $basePath,
            'version' => '1.0.0',
            'develop' => true,
            'providers' => [],
            'entry' => [
                'bootstrap' => 'Addon\\'.$basePath.'\\Bootstrap',
            ],
            'resources' => [
                'assets' => './Resources/assets',
                'routes' => './Routes',
                'views' => './Response/Views',
                'lang' => './Response/Lang',
                'config' => './Config',
                'functions' => './functions.php',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        File::put(
            $directory.'/Bootstrap.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Addon\\".$basePath.";\n\nuse PTAdmin\\Addon\\Service\\BaseBootstrap;\n\nclass Bootstrap extends BaseBootstrap\n{\n    public function getAdminResourceDefinitions(string \$addonCode, array \$addonInfo = array()): array\n    {\n        return ".var_export($definitions, true).";\n    }\n}\n"
        );

        File::ensureDirectoryExists($directory.'/Config');
        app()->instance('addon', new AddonManager());
        Addon::swap(app('addon'));
    }
}
