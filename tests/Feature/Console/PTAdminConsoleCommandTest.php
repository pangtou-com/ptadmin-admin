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

        self::assertArrayHasKey('admin:fe:pull', $commands);
        self::assertArrayHasKey('admin:fe:update', $commands);
    }

    public function test_admin_frontend_build_service_can_publish_bundled_assets_to_current_storage(): void
    {
        $service = new AdminFrontendBuildService();
        $currentPath = storage_path('app/ptadmin/frontend/admin/current');
        $result = $service->publishBundled(dirname(__DIR__, 3), base_path());

        self::assertSame($currentPath, $result['current_path']);
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'index.html');
        self::assertFileExists($currentPath.\DIRECTORY_SEPARATOR.'ptconfig.js');
        self::assertDirectoryExists($currentPath.\DIRECTORY_SEPARATOR.'assets');
    }

    public function test_project_frontend_pull_command_is_registered(): void
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        self::assertArrayHasKey('admin:pf:pull', $commands);
        self::assertArrayHasKey('admin:pf:publish', $commands);
    }
}
