<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use PTAdmin\Admin\Models\System;
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
        $app['config']->set('app.prefix', 'system');
        $app['config']->set('app.locale', 'zh-cn');
        $app['config']->set('app.fallback_locale', 'zh-cn');
        $app['config']->set('auth.defaults.guard', 'api');
        $app['config']->set('auth.app_guard_name', 'admin');
        $app['config']->set('auth.guards.api.driver', 'ptadmin');
        $app['config']->set('auth.guards.api.provider', 'systems');
        $app['config']->set('auth.guards.api.expires_at', 24 * 60 * 60);
        $app['config']->set('auth.providers.systems.driver', 'eloquent');
        $app['config']->set('auth.providers.systems.model', System::class);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('ptadmin-auth.guard', 'api');
    }

    protected function migratePackageTables(): void
    {
        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--path' => realpath(__DIR__.'/../database/Migrations'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    protected function createSystemsTable(): void
    {
        if (Schema::hasTable('systems')) {
            return;
        }

        Schema::create('systems', function (Blueprint $table): void {
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

    protected function createSystemLogsTable(): void
    {
        if (Schema::hasTable('system_logs')) {
            return;
        }

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->unsignedInteger('login_at')->default(0);
            $table->unsignedInteger('login_ip')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
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
            $table->unsignedBigInteger('system_id')->default(0);
            $table->string('nickname', 50)->default('');
            $table->unsignedInteger('ip')->default(0);
            $table->string('url', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('method', 20)->default('');
            $table->string('controller', 255)->default('');
            $table->string('action', 100)->default('');
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->text('sql_param')->nullable();
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

    protected function createAdminSystem(array $overrides = []): System
    {
        $system = new System();
        $system->username = $overrides['username'] ?? 'admin_'.uniqid();
        $system->nickname = $overrides['nickname'] ?? 'Admin';
        $system->status = $overrides['status'] ?? 1;
        $system->is_founder = $overrides['is_founder'] ?? 0;
        $system->email = $overrides['email'] ?? null;
        $system->mobile = $overrides['mobile'] ?? null;
        $system->password = Hash::make($overrides['password'] ?? 'secret123');
        $system->save();

        return $system->refresh();
    }

    protected function issueAdminToken(System $system): string
    {
        $this->createUserTokensTable();

        return app('auth')->guard(config('ptadmin-auth.guard'))->login($system);
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
