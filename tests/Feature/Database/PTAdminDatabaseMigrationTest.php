<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminDatabaseMigrationTest extends TestCase
{
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
            'console',
            'system',
            'system.role',
            'system.admins',
            'system.resources',
            'system.admin_login_logs',
            'system.operate',
            'system.config',
            'system.assets',
        ], $names);

        /** @var AdminResource $console */
        $console = AdminResource::query()->where('name', 'console')->firstOrFail();
        self::assertSame('仪表盘', $console->title);
        self::assertSame('dashboard', $console->module);
        self::assertSame('dashboard.page.home', $console->page_key);
        self::assertSame('/dashboard', $console->route);

        /** @var AdminResource $system */
        $system = AdminResource::query()->where('name', 'system')->firstOrFail();
        self::assertSame('系统管理', $system->title);
        self::assertSame('Setting', $system->icon);
    }
}
