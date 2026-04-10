<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAuthorizationApiTest extends TestCase
{
    public function test_bootstrap_founder_endpoint_creates_founder_and_default_authorization(): void
    {
        $this->createSystemsTable();
        $this->migratePackageTables();

        $response = $this->withHeaders($this->jsonApiHeaders())->postJson('/system/auth/bootstrap-founder', [
            'username' => 'root',
            'password' => 'secret123',
            'nickname' => 'Root',
            'email' => 'root@example.com',
            'mobile' => '13800138000',
        ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'message' => '操作成功',
            'data' => [
                'founder' => [
                    'username' => 'root',
                    'nickname' => 'Root',
                    'is_founder' => true,
                ],
                'authorization' => [
                    'role' => [
                        'code' => 'super_admin',
                        'name' => '超级管理员',
                    ],
                    'assigned_user_id' => 1,
                    'resource_count' => AdminResource::query()->count(),
                ],
            ],
        ]);

        $founder = System::query()->where('username', 'root')->firstOrFail();
        $role = AdminRole::query()->where('code', 'super_admin')->firstOrFail();

        self::assertSame(1, (int) $founder->is_founder);
        self::assertSame(1, (int) $role->status);
        self::assertSame(
            AdminResource::query()->count(),
            AdminGrant::query()->where('subject_type', 'role')->where('subject_id', $role->id)->count()
        );
        self::assertDatabaseHas('admin_user_roles', [
            'user_id' => $founder->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_status_endpoint_requires_admin_login(): void
    {
        $this->createSystemsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/auth/status')
            ->assertOk()
            ->assertJson([
                'code' => 10001,
                'message' => '未登录',
            ]);
    }

    public function test_login_and_authenticated_authorization_endpoints_return_expected_payloads(): void
    {
        $this->createSystemsTable();
        $this->createSystemLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem([
            'username' => 'founder',
            'nickname' => 'Founder',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);

        $loginResponse = $this->withHeaders($this->jsonApiHeaders())->postJson('/system/login', [
            'username' => 'founder',
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk();
        self::assertSame(0, $loginResponse->json('code'));
        self::assertSame('founder', $loginResponse->json('data.user.username'));

        $token = (string) $loginResponse->json('data.token');
        self::assertNotSame('', $token);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/status')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'systems' => 1,
                    'founders' => 1,
                    'admin_resources' => AdminResource::query()->count(),
                ],
            ]);

        $resourceResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/user/resources');

        $resourceResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'roles' => ['创始人'],
            ],
        ]);

        self::assertGreaterThan(0, count((array) $resourceResponse->json('data.resources')));
        self::assertDatabaseHas('system_logs', [
            'system_id' => $founder->id,
            'status' => 1,
        ]);
    }

    public function test_bootstrap_endpoint_assigns_default_role_to_specified_user(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem([
            'username' => 'founder_bootstrap',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $target = $this->createAdminSystem([
            'username' => 'operator',
            'nickname' => 'Operator',
        ]);

        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/auth/bootstrap', [
            'role_code' => 'ops_admin',
            'role_name' => '运维管理员',
            'assign_user_id' => $target->id,
            'force' => true,
        ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'role' => [
                    'code' => 'ops_admin',
                    'name' => '运维管理员',
                ],
                'assigned_user_id' => $target->id,
                'resource_count' => AdminResource::query()->count(),
            ],
        ]);

        $role = AdminRole::query()->where('code', 'ops_admin')->firstOrFail();

        self::assertSame(
            AdminResource::query()->count(),
            AdminGrant::query()->where('subject_type', 'role')->where('subject_id', $role->id)->count()
        );
        self::assertDatabaseHas('admin_user_roles', [
            'user_id' => $target->id,
            'role_id' => $role->id,
        ]);
    }
}
