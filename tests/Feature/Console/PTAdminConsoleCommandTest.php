<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Console;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminConsoleCommandTest extends TestCase
{
    public function test_admin_auth_bootstrap_command_initializes_role_and_assigns_resources(): void
    {
        $this->createAdminsTable();
        $this->migratePackageTables();

        $admin = new Admin();
        $admin->username = 'console-user';
        $admin->nickname = 'Console User';
        $admin->status = 1;
        $admin->password = Hash::make('secret123');
        $admin->save();

        $this->artisan('admin:auth-bootstrap', [
            '--role-code' => 'ops_admin',
            '--role-name' => '运维管理员',
            '--assign-user-id' => (string) $admin->id,
            '--force' => true,
        ])
            ->expectsOutput(__('ptadmin::common.command.admin_auth_bound', ['role' => '运维管理员', 'user_id' => $admin->id]))
            ->expectsOutput(__('ptadmin::common.command.admin_auth_done', ['role' => '运维管理员']))
            ->expectsOutput(__('ptadmin::common.command.admin_auth_resource_count', ['count' => AdminResource::query()->count()]))
            ->assertExitCode(0);

        $role = AdminRole::query()->where('code', 'ops_admin')->firstOrFail();
        self::assertSame(AdminResource::query()->count(), AdminGrant::query()->where('subject_id', $role->id)->count());
        self::assertDatabaseHas('admin_user_roles', [
            'user_id' => $admin->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_admin_init_command_creates_founder_account(): void
    {
        $this->createAdminsTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->artisan('admin:init', [
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
        self::assertSame('local', SystemConfig::query()->where('name', 'storage_driver')->value('value'));
    }

    public function test_admin_init_command_rejects_invalid_mobile_number(): void
    {
        $this->createAdminsTable();

        $this->artisan('admin:init', [
            '--username' => 'root',
            '--nickname' => 'Root',
            '--password' => 'secret123',
            '--mobile' => '123',
        ])->assertExitCode(1);

        self::assertSame(0, Admin::query()->count());
    }

    public function test_admin_frontend_pull_command_is_registered(): void
    {
        self::assertArrayHasKey('admin:frontend:pull', \Illuminate\Support\Facades\Artisan::all());
    }

    public function test_project_frontend_pull_command_is_registered(): void
    {
        self::assertArrayHasKey('admin:project-frontend:pull', \Illuminate\Support\Facades\Artisan::all());
    }
}
