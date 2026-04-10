<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAuthorizationApiTest extends TestCase
{
    public function test_authorization_initialization_routes_are_not_exposed_as_api_endpoints(): void
    {
        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/system/auth/bootstrap-founder', [
                'username' => 'root',
                'password' => 'secret123',
            ])
            ->assertNotFound();

        $this->withHeaders($this->jsonApiHeaders())
            ->postJson('/system/auth/bootstrap', [
                'role_code' => 'super_admin',
            ])
            ->assertNotFound();
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
}
