<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Admin\Models\UserToken;
use PTAdmin\Admin\Tests\TestCase;

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

    public function test_profile_password_endpoint_updates_password_and_invalidates_current_token(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'profile_password_user',
            'nickname' => 'Profile Password User',
            'password' => 'secret123',
        ]);
        $token = $this->issueAdminToken($admin);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/auth/password', [
                'old_password' => 'secret123',
                'password' => 'newSecret123',
                'password_confirmation' => 'newSecret123',
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'message' => '操作成功',
            ]);

        $admin = $admin->fresh();

        self::assertTrue(Hash::check('newSecret123', (string) $admin->password));
        self::assertNull(UserToken::findToken($token));
    }

    public function test_custom_token_expiration_uses_absolute_timestamp_semantics(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_expiration',
            'nickname' => 'Founder Expiration',
            'is_founder' => 1,
        ]);

        $validToken = $founder->createToken(config('ptadmin-auth.guard'), time() + 60)->plainTextToken;
        $expiredToken = $founder->createToken(config('ptadmin-auth.guard'), time() - 60)->plainTextToken;

        $this->withHeaders($this->jsonApiHeaders($validToken))
            ->getJson('/system/auth/profile')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'username' => 'founder_expiration',
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($expiredToken))
            ->getJson('/system/auth/profile')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
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
            'login_account' => 'founder_runtime',
            'login_at' => time(),
            'login_ip' => '127.0.0.1',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
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

    public function test_profile_endpoints_return_only_current_admin_runtime_data(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $currentAdmin = $this->createAdminAccount([
            'username' => 'current_profile_user',
            'nickname' => 'Current Profile User',
        ]);
        $otherAdmin = $this->createAdminAccount([
            'username' => 'other_profile_user',
            'nickname' => 'Other Profile User',
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $currentAdmin->id,
            'login_account' => 'current_profile_user',
            'login_at' => time(),
            'login_ip' => '127.0.0.10',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);
        AdminLoginLog::query()->create([
            'admin_id' => $otherAdmin->id,
            'login_account' => 'other_profile_user',
            'login_at' => time(),
            'login_ip' => '127.0.0.20',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);

        $ownRecord = OperationRecord::query()->create([
            'admin_id' => $currentAdmin->id,
            'admin_username' => $currentAdmin->username,
            'nickname' => $currentAdmin->nickname,
            'ip' => '127.0.0.10',
            'user_agent' => 'PHPUnit',
            'url' => '/system/auth/profile',
            'title' => '个人资料',
            'resource_name' => 'system.admins',
            'method' => 'PUT',
            'controller' => 'PTAdmin\\Admin\\Controllers\\AuthorizationController',
            'action' => 'updateProfile',
            'trace_id' => 'profile-own',
            'target_type' => 'admins',
            'target_id' => (string) $currentAdmin->id,
            'status' => 'success',
            'request' => json_encode(['nickname' => 'Current Profile User'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'response_code' => 200,
            'response_time' => 18.21,
        ]);
        $otherRecord = OperationRecord::query()->create([
            'admin_id' => $otherAdmin->id,
            'admin_username' => $otherAdmin->username,
            'nickname' => $otherAdmin->nickname,
            'ip' => '127.0.0.20',
            'user_agent' => 'PHPUnit',
            'url' => '/system/admins/2',
            'title' => '编辑账户',
            'resource_name' => 'system.admins',
            'method' => 'PUT',
            'controller' => 'PTAdmin\\Admin\\Controllers\\AdminController',
            'action' => 'edit',
            'trace_id' => 'profile-other',
            'target_type' => 'admins',
            'target_id' => (string) $otherAdmin->id,
            'status' => 'success',
            'request' => json_encode(['nickname' => 'Other Profile User'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'response_code' => 200,
            'response_time' => 10.01,
        ]);

        $token = $this->issueAdminToken($currentAdmin);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/profile/login-logs')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'total' => 1,
                    'results' => [
                        [
                            'admin_id' => $currentAdmin->id,
                            'login_account' => 'current_profile_user',
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/profile/operations')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'total' => 1,
                    'results' => [
                        [
                            'id' => $ownRecord->id,
                            'admin_id' => $currentAdmin->id,
                            'trace_id' => 'profile-own',
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/profile/operations/'.$ownRecord->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $ownRecord->id,
                    'admin_id' => $currentAdmin->id,
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/auth/profile/operations/'.$otherRecord->id)
            ->assertOk()
            ->assertJson([
                'code' => 10000,
                'message' => '数据不存在',
            ]);
    }

    public function test_profile_update_endpoint_updates_current_admin_profile(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'profile_update_user',
            'nickname' => 'Old Nickname',
            'email' => 'old@example.com',
            'mobile' => '13800000000',
        ]);
        $token = $this->issueAdminToken($admin);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/auth/profile', [
                'nickname' => 'New Nickname',
                'email' => 'new@example.com',
                'mobile' => '13900000000',
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $admin->id,
                'username' => 'profile_update_user',
                'nickname' => 'New Nickname',
                'email' => 'new@example.com',
                'mobile' => '13900000000',
            ],
        ]);

        self::assertDatabaseHas('admins', [
            'id' => $admin->id,
            'nickname' => 'New Nickname',
            'email' => 'new@example.com',
            'mobile' => '13900000000',
        ]);
    }

    public function test_founder_can_view_all_login_logs_with_list_query_filters(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_logs',
            'nickname' => 'Founder Logs',
            'is_founder' => 1,
        ]);
        $memberA = $this->createAdminAccount([
            'username' => 'member_a',
            'nickname' => 'Member A',
        ]);
        $memberB = $this->createAdminAccount([
            'username' => 'member_b',
            'nickname' => 'Member B',
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $memberA->id,
            'login_account' => 'member_a',
            'login_at' => time(),
            'login_ip' => '127.0.0.1',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);
        AdminLoginLog::query()->create([
            'admin_id' => $memberB->id,
            'login_account' => 'member_b',
            'login_at' => time(),
            'login_ip' => '127.0.0.2',
            'status' => AdminLoginLog::STATUS_DISABLED,
            'reason' => 'account_disabled',
            'user_agent' => 'PHPUnit',
        ]);

        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/login-logs?'.http_build_query([
                'filters' => [
                    ['field' => 'admin_login_logs.status', 'operator' => '=', 'value' => AdminLoginLog::STATUS_SUCCESS],
                ],
                'keyword' => 'member_a',
                'limit' => 10,
                'page' => 1,
            ]));

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
                'results' => [
                    [
                        'admin_id' => $memberA->id,
                        'login_account' => 'member_a',
                        'status' => AdminLoginLog::STATUS_SUCCESS,
                        'reason' => 'login_success',
                        'admin' => [
                            'id' => $memberA->id,
                            'username' => 'member_a',
                            'nickname' => 'Member A',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_login_logs_endpoint_supports_simple_status_filter_alias_from_frontend(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_status_filter',
            'nickname' => 'Founder Status Filter',
            'is_founder' => 1,
        ]);
        $memberSuccess = $this->createAdminAccount([
            'username' => 'member_success',
            'nickname' => 'Member Success',
        ]);
        $memberFailed = $this->createAdminAccount([
            'username' => 'member_failed',
            'nickname' => 'Member Failed',
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $memberSuccess->id,
            'login_account' => 'member_success',
            'login_at' => time(),
            'login_ip' => '127.0.0.1',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);
        AdminLoginLog::query()->create([
            'admin_id' => $memberFailed->id,
            'login_account' => 'member_failed',
            'login_at' => time(),
            'login_ip' => '127.0.0.2',
            'status' => AdminLoginLog::STATUS_FAILED,
            'reason' => 'password_invalid',
            'user_agent' => 'PHPUnit',
        ]);

        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/login-logs?'.http_build_query([
                'filters' => json_encode([
                    ['field' => 'status', 'operator' => 'eq', 'value' => AdminLoginLog::STATUS_FAILED],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'page' => 1,
                'limit' => 20,
            ]));

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
                'results' => [
                    [
                        'admin_id' => $memberFailed->id,
                        'login_account' => 'member_failed',
                        'status' => AdminLoginLog::STATUS_FAILED,
                        'reason' => 'password_invalid',
                    ],
                ],
            ],
        ]);
    }

    public function test_login_logs_endpoint_supports_admin_nickname_filter_alias_with_plain_like_value(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_nickname_filter',
            'nickname' => 'Founder Nickname Filter',
            'is_founder' => 1,
        ]);
        $memberMatched = $this->createAdminAccount([
            'username' => 'member_matched',
            'nickname' => '张三测试用户',
        ]);
        $memberOther = $this->createAdminAccount([
            'username' => 'member_other',
            'nickname' => '李四',
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $memberMatched->id,
            'login_account' => 'member_matched',
            'login_at' => time(),
            'login_ip' => '127.0.0.1',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);
        AdminLoginLog::query()->create([
            'admin_id' => $memberOther->id,
            'login_account' => 'member_other',
            'login_at' => time(),
            'login_ip' => '127.0.0.2',
            'status' => AdminLoginLog::STATUS_FAILED,
            'reason' => 'password_invalid',
            'user_agent' => 'PHPUnit',
        ]);

        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/login-logs?'.http_build_query([
                'filters' => json_encode([
                    ['field' => 'admin.nickname', 'operator' => 'like', 'value' => '测试'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'page' => 1,
                'limit' => 20,
            ]));

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
                'results' => [
                    [
                        'admin_id' => $memberMatched->id,
                        'login_account' => 'member_matched',
                        'admin' => [
                            'id' => $memberMatched->id,
                            'username' => 'member_matched',
                            'nickname' => '张三测试用户',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_non_founder_login_logs_endpoint_only_returns_current_admin_logs(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $memberA = $this->createAdminAccount([
            'username' => 'member_self',
            'nickname' => 'Member Self',
        ]);
        $memberB = $this->createAdminAccount([
            'username' => 'member_other',
            'nickname' => 'Member Other',
        ]);

        AdminLoginLog::query()->create([
            'admin_id' => $memberA->id,
            'login_account' => 'member_self',
            'login_at' => time(),
            'login_ip' => '127.0.0.1',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);
        AdminLoginLog::query()->create([
            'admin_id' => $memberB->id,
            'login_account' => 'member_other',
            'login_at' => time(),
            'login_ip' => '127.0.0.2',
            'status' => AdminLoginLog::STATUS_SUCCESS,
            'reason' => 'login_success',
            'user_agent' => 'PHPUnit',
        ]);

        $token = $this->issueAdminToken($memberA);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/login-logs?'.http_build_query([
                'filters' => [
                    ['field' => 'admin_login_logs.admin_id', 'operator' => '=', 'value' => $memberB->id],
                ],
                'limit' => 10,
                'page' => 1,
            ]));

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 0,
                'results' => [],
            ],
        ]);
    }
}
