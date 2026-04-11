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
            'code',
            'name',
            'type',
            'module',
            'page_key',
            'parent_id',
            'route',
            'ability_hint_json',
            'meta_json',
        ]));

        $codes = AdminResource::query()
            ->orderBy('id')
            ->pluck('code')
            ->all();

        self::assertSame([
            'console',
            'user',
            'user.users',
            'system',
            'system.role',
            'system.admins',
            'system.resources',
            'system.admin_login_logs',
            'system.operate',
            'system.config',
            'system.assets',
            'addon',
            'addon.addons',
        ], $codes);
    }
}
