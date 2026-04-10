<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Auth;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Services\Auth\AuthorizationBootstrapService;
use PTAdmin\Admin\Tests\TestCase;

class AuthorizationBootstrapServiceTest extends TestCase
{
    public function test_bootstrap_creates_default_role_grants_and_user_binding(): void
    {
        $this->createSystemsTable();
        $this->migratePackageTables();

        $system = new System();
        $system->username = 'tester';
        $system->nickname = 'Tester';
        $system->status = 1;
        $system->password = Hash::make('secret123');
        $system->save();

        $service = app(AuthorizationBootstrapService::class);
        $result = $service->bootstrap('super_admin', '超级管理员', (int) $system->id, true);

        self::assertSame('super_admin', $result['role']['code']);
        self::assertSame('超级管理员', $result['role']['name']);
        self::assertSame((int) $system->id, $result['assigned_user_id']);
        self::assertSame(AdminResource::query()->count(), $result['resource_count']);

        $role = AdminRole::query()->where('code', 'super_admin')->firstOrFail();

        self::assertSame(AdminResource::query()->count(), AdminGrant::query()->where('subject_type', 'role')->where('subject_id', $role->id)->count());
        self::assertDatabaseHas('admin_user_roles', [
            'user_id' => $system->id,
            'role_id' => $role->id,
        ]);
        self::assertSame(1, AdminUserRole::query()->where('user_id', $system->id)->count());
    }

    public function test_bootstrap_founder_creates_founder_account_and_reports_status(): void
    {
        $this->createSystemsTable();
        $this->migratePackageTables();

        $service = app(AuthorizationBootstrapService::class);
        $result = $service->bootstrapFounder('founder', 'secret123', 'Root', 'root@example.com', '13800138000');

        self::assertSame('founder', $result['founder']['username']);
        self::assertSame('Root', $result['founder']['nickname']);
        self::assertTrue($result['founder']['is_founder']);
        self::assertSame('super_admin', $result['authorization']['role']['code']);

        $founder = System::query()->where('username', 'founder')->firstOrFail();

        self::assertSame(1, (int) $founder->is_founder);
        self::assertTrue(Hash::check('secret123', $founder->getAuthPassword()));

        self::assertSame([
            'systems' => 1,
            'founders' => 1,
            'admin_resources' => AdminResource::query()->count(),
            'admin_roles' => 1,
            'admin_user_roles' => 1,
            'admin_grants' => AdminGrant::query()->count(),
        ], $service->status());
    }
}
