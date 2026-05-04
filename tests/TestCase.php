<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Providers\PTAdminServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PTAdminServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.prefix', 'system');
        $app['config']->set('app.locale', 'zh-cn');
        $app['config']->set('app.fallback_locale', 'zh-cn');
        $app['config']->set('auth.defaults.guard', 'api');
        $app['config']->set('auth.app_guard_name', 'admin');
        $app['config']->set('auth.guards.api.driver', 'ptadmin');
        $app['config']->set('auth.guards.api.provider', 'admins');
        $app['config']->set('auth.guards.api.expires_at', 24 * 60 * 60);
        $app['config']->set('auth.providers.admins.driver', 'eloquent');
        $app['config']->set('auth.providers.admins.model', Admin::class);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('filesystems.default', 'public');
        $app['config']->set('ptadmin-auth.guard', 'api');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function migratePackageTables(): void
    {
        $this->dropPackageTables();

        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);
    }

    protected function dropPackageTables(): void
    {
        foreach ([
            'assets',
            'audit_logs',
            'mod_versions',
            'mod_fields',
            'mods',
            'admin_user_org_relations',
            'admin_departments',
            'admin_organizations',
            'admin_tenants',
            'admin_grants',
            'admin_user_roles',
            'admin_resources',
            'admin_roles',
            'admin_dashboard_user_widgets',
            'admin_dashboard_role_widgets',
            'system_configs',
            'system_config_groups',
            'operation_records',
            'user_tokens',
            'admin_login_logs',
            'admins',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    protected function createAdminsTable(): void
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 20)->nullable();
            $table->string('nickname', 20)->default('');
            $table->string('password', 255);
            $table->string('mobile', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('avatar', 255)->nullable();
            $table->unsignedInteger('login_at')->default(0);
            $table->string('login_ip', 50)->nullable();
            $table->unsignedTinyInteger('is_founder')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    protected function createAdminLoginLogsTable(): void
    {
        if (Schema::hasTable('admin_login_logs')) {
            return;
        }

        Schema::create('admin_login_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('login_account', 100)->default('');
            $table->unsignedInteger('login_at')->default(0);
            $table->string('login_ip', 45)->nullable();
            $table->string('status', 50)->default('failed');
            $table->string('reason', 100)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
        });
    }

    protected function createUserTokensTable(): void
    {
        if (Schema::hasTable('user_tokens')) {
            return;
        }

        Schema::create('user_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('guard_name', 50);
            $table->string('token', 64);
            $table->unsignedInteger('ip')->default(0);
            $table->unsignedInteger('expires_at')->default(0);
            $table->unsignedInteger('last_used_at')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->index(['target_type', 'target_id']);
        });
    }

    protected function createOperationRecordsTable(): void
    {
        if (Schema::hasTable('operation_records')) {
            return;
        }

        Schema::create('operation_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_username', 100)->default('');
            $table->string('nickname', 50)->default('');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('resource_name', 150)->nullable();
            $table->string('method', 20)->default('');
            $table->string('controller', 255)->default('');
            $table->string('action', 100)->default('');
            $table->string('trace_id', 100)->nullable();
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 150)->nullable();
            $table->string('status', 50)->default('success');
            $table->text('request')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('response_code')->default(200);
            $table->decimal('response_time', 10, 2)->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
        });
    }

    protected function createSystemConfigGroupsTable(): void
    {
        if (Schema::hasTable('system_config_groups')) {
            return;
        }

        Schema::create('system_config_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('name', 32);
            $table->unsignedInteger('weight')->default(0);
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('addon_code', 50)->nullable();
            $table->string('intro', 255)->nullable();
            $table->json('extra')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    protected function createSystemConfigsTable(): void
    {
        if (Schema::hasTable('system_configs')) {
            return;
        }

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('name', 32);
            $table->unsignedBigInteger('system_config_group_id');
            $table->unsignedInteger('weight')->default(0);
            $table->string('type', 20)->default('text');
            $table->string('intro', 255)->nullable();
            $table->text('extra')->nullable();
            $table->text('value')->nullable();
            $table->text('default_val')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    protected function createAssetsTable(): void
    {
        if (Schema::hasTable('assets')) {
            return;
        }

        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255)->default('');
            $table->string('md5', 32)->default('');
            $table->string('mime', 100)->default('');
            $table->string('suffix', 20)->default('');
            $table->string('driver', 50)->default('public');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path', 255)->default('');
            $table->string('groups', 50)->default('default');
            $table->unsignedInteger('quote')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    protected function createAdminAccount(array $overrides = []): Admin
    {
        $admin = new Admin();
        $admin->username = $overrides['username'] ?? 'admin_'.uniqid();
        $admin->nickname = $overrides['nickname'] ?? 'Admin';
        $admin->status = $overrides['status'] ?? 1;
        $admin->is_founder = $overrides['is_founder'] ?? 0;
        $admin->email = $overrides['email'] ?? null;
        $admin->mobile = $overrides['mobile'] ?? null;
        $admin->password = Hash::make($overrides['password'] ?? 'secret123');
        $admin->save();

        return $admin->refresh();
    }

    protected function issueAdminToken(Admin $admin): string
    {
        $this->createUserTokensTable();

        return app('auth')->guard(config('ptadmin-auth.guard'))->login($admin);
    }

    protected function jsonApiHeaders(?string $token = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Method' => 'api',
        ];

        if (null !== $token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }
}
