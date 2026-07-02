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

    public function test_admin_frontend_build_service_can_publish_bundled_assets_to_current_storage(): void
    {
        $service = new AdminFrontendBuildService();
        $currentPath = storage_path('app/ptadmin/frontend/admin/current');
        $this->deletePath($currentPath);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        self::assertSame($currentPath, $result['current_path']);
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'ptconfig.js');
        self::assertDirectoryExists($currentPath.\DIRECTORY_SEPARATOR.'assets');
        $indexHtml = (string) file_get_contents($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertStringContainsString('window.__PTADMIN_PTCONFIG_READY__ = Promise.resolve()', $indexHtml);
        self::assertStringContainsString('window.ptconfig = Object.assign', $indexHtml);
        self::assertStringContainsString('"basePath": "/admin/"', $indexHtml);
        self::assertStringNotContainsString('/admin/ptconfig.js', $indexHtml);
        self::assertStringNotContainsString('__PTADMIN_RUNTIME_CONFIG_SCRIPT__', $indexHtml);
        self::assertStringNotContainsString('__PTADMIN_CONFIG_URL__', $indexHtml);
        self::assertSame(public_path('admin'), $result['public_path']);
        self::assertFileExists(public_path('admin/index.html'));
        self::assertFileExists(public_path('admin/ptconfig.js'));
        self::assertSame('generated', $result['runtime_config']);
        self::assertFalse(is_link($currentPath));
    }

    public function test_admin_frontend_build_service_regenerates_runtime_config_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $currentPath = storage_path('app/ptadmin/frontend/admin/current');
        $configPath = $currentPath.\DIRECTORY_SEPARATOR.'ptconfig.js';
        $runtimeConfig = "window.ptconfig = { baseURL: 'https://tenant.example.test/custom-api/' };\n";

        $this->deletePath($currentPath);
        mkdir($currentPath, 0755, true);
        file_put_contents($configPath, $runtimeConfig);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        self::assertSame('generated', $result['runtime_config']);
        self::assertStringNotContainsString('tenant.example.test', (string) file_get_contents($configPath));
        self::assertStringContainsString('window.ptconfig', (string) file_get_contents($configPath));
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        $indexHtml = (string) file_get_contents($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertStringContainsString('window.ptconfig = Object.assign', $indexHtml);
        self::assertStringContainsString('"basePath": "/admin/"', $indexHtml);
        self::assertStringNotContainsString('/admin/ptconfig.js', $indexHtml);
        self::assertStringNotContainsString('./ptconfig.js', $indexHtml);
        self::assertFileExists(public_path('admin/ptconfig.js'));
        self::assertDirectoryExists($currentPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($currentPath));
    }

    public function test_admin_frontend_build_service_preserves_runtime_modules_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $currentPath = storage_path('app/ptadmin/frontend/admin/current');
        $modulesPath = $currentPath.\DIRECTORY_SEPARATOR.'modules';
        $moduleFile = $modulesPath.\DIRECTORY_SEPARATOR.'cms'.\DIRECTORY_SEPARATOR.'dist'.\DIRECTORY_SEPARATOR.'index.js';

        $this->deletePath($currentPath);
        mkdir(\dirname($moduleFile), 0755, true);
        file_put_contents($moduleFile, 'console.log("cms");');
        chmod($modulesPath, 0770);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        clearstatcache(true, $modulesPath);

        self::assertSame('preserved', $result['modules']);
        self::assertSame('console.log("cms");', file_get_contents($moduleFile));
        self::assertSame('777', substr(sprintf('%o', fileperms($modulesPath)), -3));
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertDirectoryExists($currentPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($currentPath));
    }

    public function test_admin_frontend_build_service_creates_writable_runtime_modules_on_publish(): void
    {
        $service = new AdminFrontendBuildService();
        $currentPath = storage_path('app/ptadmin/frontend/admin/current');
        $modulesPath = $currentPath.\DIRECTORY_SEPARATOR.'modules';

        $this->deletePath($currentPath);

        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        clearstatcache(true, $modulesPath);

        self::assertSame('created', $result['modules']);
        self::assertDirectoryExists($modulesPath);
        self::assertSame('777', substr(sprintf('%o', fileperms($modulesPath)), -3));
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertDirectoryExists($currentPath.\DIRECTORY_SEPARATOR.'assets');
        self::assertFalse(is_link($currentPath));
    }

    public function test_project_frontend_pull_command_is_registered(): void
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        self::assertArrayHasKey('admin:pf:pull', $commands);
        self::assertArrayHasKey('admin:pf:publish', $commands);
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
