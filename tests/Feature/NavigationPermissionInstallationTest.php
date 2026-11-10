<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PTAdmin\Admin\Auth\AuthorizationService;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Addon\Lifecycle\Installer;
use PTAdmin\Addon\Lifecycle\StateManager;
use PTAdmin\Core\Auth\Session\SessionManager;
use PTAdmin\Core\Install\AdminFrontendManifest;
use PTAdmin\Core\Install\EnvironmentFile;
use PTAdmin\Core\Install\InstallService;
use Tests\TestCase;

/** 使用真实基础壳启动与安装流程，所有数据库、配置、插件和公开文件均写入临时目录。 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NavigationPermissionInstallationTest extends TestCase
{
    private string $root;

    public function createApplication(): Application
    {
        if (!isset($this->root)) {
            $this->root = sys_get_temp_dir().'/ptadmin-navigation-install-'.bin2hex(random_bytes(6));
            foreach (['storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs',
                'public', 'bootstrap/cache', 'database', 'resources', 'package/resources'] as $directory) {
                mkdir($this->root.'/'.$directory, 0700, true);
            }
        }
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->useStoragePath($this->root.'/storage');
        $app->usePublicPath($this->root.'/public');
        $app->useEnvironmentPath($this->root);
        $app->useBootstrapPath($this->root.'/bootstrap');
        $app->useDatabasePath($this->root.'/database');
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['config']->set([
                'app.key' => (new EnvironmentFile($this->root.'/.env'))->get('APP_KEY') ?? '',
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $this->root.'/storage/database/install.sqlite',
                'database.connections.sqlite.prefix' => '',
                'database.connections.sqlite.foreign_key_constraints' => true,
                'cache.default' => 'array', 'session.driver' => 'array',
                'view.compiled' => $this->root.'/storage/framework/views',
                'ptadmin.addon.path' => $this->root.'/addons',
                'ptadmin.addon.require_signature' => false,
                'ptadmin.addon.download_path' => $this->root.'/staging',
            ]);
            // 发布前验收可显式指定候选 Shell；默认验证随 Core 分发的构建。
            $frontend = getenv('PTADMIN_TEST_FRONTEND_SOURCE');
            if (is_string($frontend) && $frontend !== '') {
                $app['config']->set('ptadmin.frontend.bundled_path', $frontend);
            }
        });
        $app->make(Kernel::class)->bootstrap();
        $app->setBasePath($this->root);

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem())->deleteDirectory($this->root);
    }

    public function testFreshInstallationAndAddonLifecycleUseIndependentNavigationAndPermissions(): void
    {
        self::assertSame([], $this->app['db']->getConnections());
        $this->getJson('/ptadmin/session')->assertRedirect('/__ptadmin__/install');
        self::assertSame([], $this->app['db']->getConnections());

        $result = app(InstallService::class)->install([
            'username' => 'founder', 'password' => 'test-password',
            'db_connection' => 'sqlite', 'db_database' => 'install.sqlite', 'db_prefix' => '',
        ]);
        self::assertTrue($result['installed']);
        $prefix = $result['result']['web_prefix'];
        $manifest = app(AdminFrontendManifest::class);
        self::assertSame(
            $manifest->verify(config('ptadmin.frontend.bundled_path'))['artifact_sha256'],
            $manifest->verify($this->root.'/public/'.$prefix)['artifact_sha256'],
        );
        self::assertFileExists($this->root.'/storage/ptadmin.json');
        self::assertFileExists($this->root.'/.env');
        // 安装前只注册安装路由；下一次应用启动才加载授权和业务接口。
        $this->app['db']->disconnect();
        $this->refreshApplication();
        $admin = Admin::query()->firstOrFail();
        $this->withToken(app(SessionManager::class)->issue('admin', $admin->id, 'admin')->toArray()['access_token']);
        $this->getJson('/ptadmin/session')->assertOk();
        $catalog = $this->getJson('/ptadmin/authorization/catalog')->assertOk()->json('data.resources');
        self::assertContains('permission', array_column($catalog, 'type'));
        self::assertNotContains('action', array_column($catalog, 'type'));
        self::assertGreaterThan(0, DB::table('admin_menus')->count());
        self::assertSame(['permission'], DB::table('admin_resources')->distinct()->pluck('type')->all());

        file_put_contents($this->root.'/package/manifest.json', json_encode(['code' => 'install-check', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        file_put_contents($this->root.'/package/resources/menu.json', json_encode([[
            'resource_key' => 'install-check.page', 'type' => 'page', 'title' => '验收页面', 'path' => 'entries',
            'entry_permission' => 'install-check.entries.list',
            'permissions' => [['resource_key' => 'install-check.entries', 'operations' => [['key' => 'list'], ['key' => 'delete']]]],
        ]], JSON_THROW_ON_ERROR));
        $installation = app(Installer::class)->install($this->root.'/package', $prefix);
        self::assertSame(1, $installation['resources']['admin_menus']);
        self::assertSame(1, $installation['resources']['admin_resources']);
        $authorization = app(AuthorizationService::class);
        self::assertTrue($authorization->allows($admin, 'list', 'install-check.entries'));
        self::assertFalse($authorization->allows($admin, 'access', 'install-check.entries'));
        $grants = DB::table('admin_grants')->orderBy('id')->get()->toArray();

        app(StateManager::class)->disable('install-check');
        self::assertFalse($authorization->allows($admin, 'list', 'install-check.entries'));
        $this->assertDatabaseHas('admin_menus', ['resource_key' => 'install-check.page', 'status' => 0]);
        app(StateManager::class)->enable('install-check');
        self::assertTrue($authorization->allows($admin, 'list', 'install-check.entries'));
        $this->assertDatabaseHas('admin_menus', ['resource_key' => 'install-check.page', 'status' => 1]);
        self::assertEquals($grants, DB::table('admin_grants')->orderBy('id')->get()->toArray());
    }
}
