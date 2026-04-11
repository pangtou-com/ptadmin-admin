<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Admin\Models\UserToken;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;

class PTAdminSessionAndFieldApiTest extends TestCase
{
    public function test_logout_endpoint_returns_login_url_and_revokes_current_token(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_logout',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        self::assertNotNull(UserToken::findToken($token));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/logout');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'url' => route('admin_login'),
            ],
        ]);

        self::assertNull(UserToken::findToken($token));
    }

    public function test_password_endpoint_updates_password_and_invalidates_current_token(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_password',
            'nickname' => 'Founder',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/admins/password', [
            'old_password' => 'secret123',
            'password' => 'newSecret123',
            'password_confirmation' => 'newSecret123',
        ])->assertOk()->assertJson([
            'code' => 0,
            'message' => '操作成功',
        ]);

        $founder = $founder->fresh();

        self::assertTrue(Hash::check('newSecret123', (string) $founder->password));
        self::assertNull(UserToken::findToken($token));

        $loginResponse = $this->withHeaders($this->jsonApiHeaders())->postJson('/system/login', [
            'username' => 'founder_password',
            'password' => 'newSecret123',
        ]);

        $loginResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'user' => [
                    'username' => 'founder_password',
                ],
            ],
        ]);
    }

    public function test_login_log_and_my_resource_endpoints_return_founder_runtime_data(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_runtime',
            'nickname' => 'Founder Runtime',
            'is_founder' => 1,
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $founder->id,
            'login_at' => time(),
            'login_ip' => (int) ip2long('127.0.0.1'),
            'status' => 1,
        ]);

        $token = $this->issueAdminToken($founder);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/login-logs')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'total' => 1,
                ],
            ]);

        $resourceResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/my-resource');

        $resourceResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        $payload = json_encode($resourceResponse->json('data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::assertIsString($payload);
        self::assertStringContainsString('console', $payload);
        self::assertStringContainsString('system.resources', $payload);
    }

    public function test_edit_field_endpoint_returns_fail_when_field_acl_is_disabled(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_field_disabled',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);
        $pageResource = AdminResource::query()->where('code', 'system.resources')->firstOrFail();

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/resource-field/'.$pageResource->id, [
            'fields' => [
                [
                    'name' => 'status',
                    'title' => '状态',
                ],
            ],
        ])->assertOk()->assertJson([
            'code' => 10000,
            'message' => '字段权限能力尚未启用',
        ]);
    }

    public function test_edit_field_endpoint_can_sync_page_field_resources_when_capability_is_enabled(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        config()->set('ptadmin-auth.capabilities.field_acl', true);
        $this->app->forgetInstance(CapabilityServiceInterface::class);

        $founder = $this->createAdminAccount([
            'username' => 'founder_field_enabled',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);
        $pageResource = AdminResource::query()->where('code', 'system.resources')->firstOrFail();

        $response = $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/resource-field/'.$pageResource->id, [
            'fields' => [
                [
                    'name' => 'status',
                    'title' => '状态',
                    'abilities' => ['view', 'access'],
                    'sort' => 10,
                    'status' => 1,
                ],
                [
                    'name' => 'icon',
                    'title' => '图标',
                    'abilities' => ['view'],
                    'sort' => 20,
                    'status' => 1,
                ],
            ],
        ]);

        $response->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertDatabaseHas('admin_resources', [
            'parent_id' => $pageResource->id,
            'code' => 'system.resources.field.status',
            'name' => '状态',
            'type' => 'field',
            'status' => 1,
        ]);
        self::assertDatabaseHas('admin_resources', [
            'parent_id' => $pageResource->id,
            'code' => 'system.resources.field.icon',
            'name' => '图标',
            'type' => 'field',
            'status' => 1,
        ]);

        self::assertCount(2, (array) $response->json('data.results'));
    }
}
