<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Console;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Services\AdminFrontendBuildService;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Support\SystemConfigPreset;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminConsoleCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->deletePath(storage_path('app/ptadmin/frontend/admin/current'));
        $this->deletePath(public_path('admin'));

        parent::tearDown();
    }

    public function test_admin_auth_command_creates_founder_account(): void
    {
        $this->createAdminsTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        \PTAdmin\Admin\Services\SystemConfigGroupService::installInitialize(SystemConfigPreset::definitions());

        $this->artisan('admin:auth', [
            '--username' => 'root',
            '--nickname' => 'Root',
            '--password' => 'secret123',
            '--email' => 'root@example.com',
            '--mobile' => '13800138000',
        ])
            ->expectsOutput(__('ptadmin::common.command.admin_init_success'))
            ->expectsOutput(__('ptadmin::common.command.admin_init_summary'))
            ->expectsOutput(__('ptadmin::common.command.admin_init_username', ['username' => 'root']))
            ->expectsOutput(__('ptadmin::common.command.admin_init_password', ['password' => 'secret123']))
            ->assertExitCode(0);

        $founder = Admin::query()->where('username', 'root')->firstOrFail();

        self::assertSame(1, (int) $founder->is_founder);
        self::assertSame('Root', $founder->nickname);
        self::assertTrue(Hash::check('secret123', $founder->getAuthPassword()));
        self::assertNotNull(SystemConfigGroup::query()->where('name', 'upload')->first());
        self::assertSame('1', SystemConfig::query()->where('name', 'enabled')->value('value'));
    }

    public function test_admin_auth_command_rejects_invalid_mobile_number(): void
    {
        $this->createAdminsTable();

        $this->artisan('admin:auth', [
            '--username' => 'root',
            '--nickname' => 'Root',
            '--password' => 'secret123',
            '--mobile' => '123',
        ])->assertExitCode(1);

        self::assertSame(0, Admin::query()->count());
    }

    public function test_admin_frontend_short_commands_are_registered(): void
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        self::assertArrayHasKey('admin:fix', $commands);
        self::assertArrayHasKey('admin:fe:pull', $commands);
        self::assertArrayHasKey('admin:fe:update', $commands);
    }

    public function test_admin_fix_command_repairs_configured_directory_and_file_permissions(): void
    {
        $directory = storage_path('app/ptadmin-fix-test');
        $nested = $directory.\DIRECTORY_SEPARATOR.'nested';
        $file = $nested.\DIRECTORY_SEPARATOR.'cache.php';

        $this->deletePath($directory);
        mkdir($nested, 0700, true);
        file_put_contents($file, '<?php return [];');
        chmod($directory, 0700);
        chmod($nested, 0700);
        chmod($file, 0600);

        config()->set('ptadmin.fix_directory_mode', '0775');
        config()->set('ptadmin.fix_file_mode', '0664');
        config()->set('ptadmin.fix_paths', [
            'test_runtime' => [
                'path' => $directory,
                'type' => 'directory',
                'recursive' => true,
            ],
        ]);

        $this->artisan('admin:fix')
            ->expectsOutput('PTAdmin fix completed. fixed=3 skipped=0 failed=0')
            ->assertExitCode(0);

        clearstatcache(true, $directory);
        clearstatcache(true, $nested);
        clearstatcache(true, $file);

        self::assertSame('775', substr(sprintf('%o', fileperms($directory)), -3));
        self::assertSame('775', substr(sprintf('%o', fileperms($nested)), -3));
        self::assertSame('664', substr(sprintf('%o', fileperms($file)), -3));
    }

    public function test_admin_frontend_build_service_can_publish_bundled_assets_to_public_path(): void
    {
        $service = new AdminFrontendBuildService();
        $publicPath = public_path('admin');
        $legacyCurrentPath = storage_path('app/ptadmin/frontend/admin/current');
        $this->deletePath($publicPath);
        $this->deletePath($legacyCurrentPath);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        self::assertSame($publicPath, $result['public_path']);
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'ptconfig.js');
        self::assertDirectoryExists($publicPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertDirectoryExists($publicPath.\DIRECTORY_SEPARATOR.'modules');
        $indexHtml = (string) file_get_contents($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertStringContainsString('window.__PTADMIN_PTCONFIG_READY__ = Promise.resolve()', $indexHtml);
        self::assertStringContainsString('window.ptconfig = Object.assign', $indexHtml);
        self::assertStringContainsString('"basePath": "/admin/"', $indexHtml);
        self::assertStringNotContainsString('/admin/ptconfig.js', $indexHtml);
        self::assertStringNotContainsString('__PTADMIN_RUNTIME_CONFIG_SCRIPT__', $indexHtml);
        self::assertStringNotContainsString('__PTADMIN_CONFIG_URL__', $indexHtml);
        self::assertDirectoryDoesNotExist($legacyCurrentPath);
        self::assertSame('generated', $result['runtime_config']);
        self::assertFalse(is_link($publicPath));
    }

    public function test_admin_frontend_build_service_regenerates_runtime_config_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $publicPath = public_path('admin');
        $configPath = $publicPath.\DIRECTORY_SEPARATOR.'ptconfig.js';
        $runtimeConfig = "window.ptconfig = { baseURL: 'https://tenant.example.test/custom-api/' };\n";

        $this->deletePath($publicPath);
        mkdir($publicPath, 0755, true);
        file_put_contents($configPath, $runtimeConfig);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        self::assertSame('generated', $result['runtime_config']);
        self::assertStringNotContainsString('tenant.example.test', (string) file_get_contents($configPath));
        self::assertStringContainsString('window.ptconfig', (string) file_get_contents($configPath));
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        $indexHtml = (string) file_get_contents($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertStringContainsString('window.ptconfig = Object.assign', $indexHtml);
        self::assertStringContainsString('"basePath": "/admin/"', $indexHtml);
        self::assertStringNotContainsString('/admin/ptconfig.js', $indexHtml);
        self::assertStringNotContainsString('./ptconfig.js', $indexHtml);
        self::assertDirectoryExists($publicPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($publicPath));
    }

    public function test_admin_frontend_build_service_preserves_runtime_modules_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $publicPath = public_path('admin');
        $modulesPath = $publicPath.\DIRECTORY_SEPARATOR.'modules';
        $moduleFile = $modulesPath.\DIRECTORY_SEPARATOR.'cms'.\DIRECTORY_SEPARATOR.'dist'.\DIRECTORY_SEPARATOR.'index.js';

        $this->deletePath($publicPath);
        mkdir(\dirname($moduleFile), 0755, true);
        file_put_contents($moduleFile, 'console.log("cms");');
        chmod($modulesPath, 0770);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        clearstatcache(true, $modulesPath);

        self::assertSame('preserved', $result['modules']);
        self::assertSame('console.log("cms");', file_get_contents($moduleFile));
        self::assertSame('777', substr(sprintf('%o', fileperms($modulesPath)), -3));
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertDirectoryExists($publicPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($publicPath));
    }

    public function test_admin_frontend_build_service_creates_writable_runtime_modules_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $publicPath = public_path('admin');
        $modulesPath = $publicPath.\DIRECTORY_SEPARATOR.'modules';

        $this->deletePath($publicPath);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        clearstatcache(true, $modulesPath);

        self::assertSame('created', $result['modules']);
        self::assertDirectoryExists($modulesPath);
        self::assertSame('777', substr(sprintf('%o', fileperms($modulesPath)), -3));
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertDirectoryExists($publicPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($publicPath));
    }

    public function test_project_frontend_pull_command_is_registered(): void
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        self::assertArrayHasKey('admin:pf:pull', $commands);
        self::assertArrayHasKey('admin:pf:publish', $commands);
    }

    public function test_project_frontend_publish_command_writes_directly_to_public_modules(): void
    {
        $sourcePath = storage_path('app/project-frontend-dist');
        $manifestPath = storage_path('app/project-frontend.json');
        $publicPath = public_path('admin/modules/custom-app');

        $this->deletePath($sourcePath);
        $this->deletePath($publicPath);
        if (!is_dir($sourcePath.\DIRECTORY_SEPARATOR.'assets')) {
            mkdir($sourcePath.\DIRECTORY_SEPARATOR.'assets', 0755, true);
        }
        file_put_contents($sourcePath.\DIRECTORY_SEPARATOR.'assets'.\DIRECTORY_SEPARATOR.'app.js', 'console.log("app");');
        file_put_contents($manifestPath, '{"code":"custom-app","version":"1.0.0"}');

        config()->set('ptadmin.project_frontend_manifest', $manifestPath);

        $this->artisan('admin:pf:publish', [
            '--source' => $sourcePath,
            '--code' => 'custom-app',
        ])->assertExitCode(0);

        self::assertDirectoryExists($publicPath);
        self::assertFalse(is_link($publicPath));
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'frontend.json');
        self::assertFileExists($publicPath.\DIRECTORY_SEPARATOR.'dist'.\DIRECTORY_SEPARATOR.'assets'.\DIRECTORY_SEPARATOR.'app.js');

        $this->deletePath($sourcePath);
        @unlink($manifestPath);
    }

    private function deletePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
