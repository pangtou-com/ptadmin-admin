<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminDatabaseMigrationTest extends TestCase
{
    public function test_notification_route_migration_upgrades_legacy_profile_routes(): void
    {
        Schema::create('notification_channel_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100);
            $table->string('channel', 50);
            $table->string('group', 50);
            $table->string('addon_code', 100)->nullable();
            $table->string('capability', 100);
            $table->unsignedTinyInteger('enabled')->default(1);
        });
        Schema::create('notification_scene_routes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scene_id');
            $table->string('channel', 50);
            $table->unsignedBigInteger('profile_id');
            $table->string('dispatch_mode', 20);
            $table->string('strategy', 30)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedInteger('weight')->default(100);
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unique(['scene_id', 'channel', 'profile_id'], 'uniq_notification_scene_route_profile');
            $table->index(['scene_id', 'channel', 'enabled'], 'idx_notification_scene_routes_scene');
        });
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 100)->nullable();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->unsignedInteger('route_revision')->nullable();
            $table->string('strategy', 30)->nullable();
        });

        DB::table('notification_channel_profiles')->insert([
            'id' => 7,
            'code' => 'primary',
            'channel' => 'mail',
            'group' => 'notify',
            'addon_code' => 'notify_mock',
            'capability' => 'mail',
            'enabled' => 1,
        ]);
        DB::table('notification_scene_routes')->insert([
            'scene_id' => 3,
            'channel' => 'mail',
            'profile_id' => 7,
            'dispatch_mode' => 'select_one',
        ]);
        DB::table('notification_deliveries')->insert(['provider' => null, 'profile_id' => 7]);

        $migration = require __DIR__.'/../../../database/Migrations/2026_08_17_100000_create_notification_scene_routes.php';
        $migration->up();
        $migration->up();

        self::assertFalse(Schema::hasColumn('notification_scene_routes', 'profile_id'));
        self::assertTrue(Schema::hasColumns('notification_scene_routes', [
            'addon_code',
            'provider_group',
            'provider',
            'instance_code',
        ]));
        $route = DB::table('notification_scene_routes')->first();
        self::assertSame('notify_mock', $route->addon_code);
        self::assertSame('notify', $route->provider_group);
        self::assertSame('mail', $route->provider);
        self::assertSame('primary', $route->instance_code);
        self::assertTrue(Schema::hasColumns('notification_deliveries', [
            'addon_code',
            'provider_group',
            'instance_code',
        ]));
        $delivery = DB::table('notification_deliveries')->first();
        self::assertSame('notify_mock', $delivery->addon_code);
        self::assertSame('notify', $delivery->provider_group);
        self::assertSame('primary', $delivery->instance_code);

        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_scene_routes');
        Schema::dropIfExists('notification_channel_profiles');
    }

    public function test_package_migrations_create_foundation_and_authorization_tables_and_seed_default_resources(): void
    {
        $this->migratePackageTables();

        foreach ([
            'admins',
            'admin_login_logs',
            'user_tokens',
            'operation_records',
            'system_config_groups',
            'system_configs',
            'admin_roles',
            'admin_resources',
            'admin_user_roles',
            'admin_grants',
            'admin_dashboard_role_widgets',
            'admin_dashboard_user_widgets',
            'admin_tenants',
            'admin_organizations',
            'admin_departments',
            'admin_user_org_relations',
            'assets',
            'mods',
            'mod_fields',
            'mod_versions',
            'audit_logs',
            'notification_scenes',
            'notification_templates',
            'notification_scene_routes',
        ] as $table) {
            self::assertTrue(Schema::hasTable($table), sprintf('Missing table [%s].', $table));
        }

        self::assertTrue(Schema::hasColumns('admin_resources', [
            'name',
            'title',
            'type',
            'module',
            'page_key',
            'parent_id',
            'route',
            'meta_json',
        ]));

        self::assertTrue(Schema::hasColumns('admin_login_logs', [
            'admin_id',
            'login_account',
            'login_at',
            'login_ip',
            'status',
            'reason',
            'user_agent',
        ]));

        self::assertTrue(Schema::hasColumns('system_config_groups', [
            'name',
            'title',
            'badge',
            'type',
            'access',
            'is_system',
            'sort',
            'addon_code',
            'intro',
        ]));

        self::assertTrue(Schema::hasColumns('notification_scenes', [
            'source_type',
            'source_code',
            'group_code',
            'group_title',
            'code',
            'default_channels',
            'enabled',
        ]));

        self::assertTrue(Schema::hasColumns('notification_scene_routes', [
            'scene_id',
            'channel',
            'addon_code',
            'provider_group',
            'provider',
            'instance_code',
            'dispatch_mode',
            'strategy',
            'revision',
        ]));

        self::assertTrue(Schema::hasColumns('notification_deliveries', [
            'addon_code',
            'provider_group',
            'instance_code',
            'route_revision',
            'strategy',
        ]));

        self::assertTrue(Schema::hasColumns('operation_records', [
            'admin_id',
            'admin_username',
            'nickname',
            'ip',
            'user_agent',
            'url',
            'title',
            'resource_name',
            'method',
            'controller',
            'action',
            'trace_id',
            'target_type',
            'target_id',
            'status',
            'request',
            'error_message',
            'response_code',
            'response_time',
        ]));

        $names = AdminResource::query()
            ->orderBy('id')
            ->pluck('name')
            ->all();

        self::assertSame([
            'system',
            'system.config',
            'system.admins',
            'system.role',
            'system.resources',
            'system.assets',
            'system.admin_login_logs',
            'system.operate',
            'system.notification_config',
        ], $names);

        /** @var AdminResource $system */
        $system = AdminResource::query()->where('name', 'system')->firstOrFail();
        self::assertSame('系统管理', $system->title);
        self::assertSame('Setting', $system->icon);
    }
}
