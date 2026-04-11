<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminOperationRecordApiTest extends TestCase
{
    public function test_operation_endpoints_require_admin_login(): void
    {
        $this->createAdminsTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/operations')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_operation_list_endpoint_returns_paginated_results(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_operation_list',
            'nickname' => 'Founder Operation',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        OperationRecord::query()->create([
            'admin_id' => $founder->id,
            'nickname' => $founder->nickname,
            'ip' => (int) ip2long('127.0.0.1'),
            'url' => '/system/roles',
            'title' => '角色管理',
            'method' => 'GET',
            'controller' => 'PTAdmin\\Admin\\Controllers\\RoleController',
            'action' => 'index',
            'request' => json_encode(['page' => 1, 'limit' => 20], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response' => json_encode(['code' => 0, 'message' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sql_param' => json_encode([['sql' => 'select * from admin_roles']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => 200,
            'response_time' => 12.34,
        ]);

        OperationRecord::query()->create([
            'admin_id' => $founder->id,
            'nickname' => $founder->nickname,
            'ip' => (int) ip2long('127.0.0.2'),
            'url' => '/system/resources',
            'title' => '资源管理',
            'method' => 'POST',
            'controller' => 'PTAdmin\\Admin\\Controllers\\ResourceController',
            'action' => 'store',
            'request' => json_encode(['name' => '仪表盘'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response' => json_encode(['code' => 0, 'message' => 'created'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sql_param' => json_encode([['sql' => 'insert into admin_resources']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => 200,
            'response_time' => 23.45,
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/operations?limit=1');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 2,
            ],
        ]);

        self::assertCount(1, (array) $response->json('data.results'));
        self::assertSame('/system/resources', $response->json('data.results.0.url'));
        self::assertSame('POST', $response->json('data.results.0.method'));
    }

    public function test_operation_details_endpoint_returns_decoded_payloads(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_operation_details',
            'nickname' => 'Founder Operation Details',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        $record = OperationRecord::query()->create([
            'admin_id' => $founder->id,
            'nickname' => $founder->nickname,
            'ip' => (int) ip2long('127.0.0.10'),
            'url' => '/system/admins/10',
            'title' => '账号详情',
            'method' => 'PUT',
            'controller' => 'PTAdmin\\Admin\\Controllers\\AdminController',
            'action' => 'update',
            'request' => json_encode(['nickname' => '新昵称', 'status' => 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response' => json_encode(['code' => 0, 'message' => '操作成功'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sql_param' => json_encode([
                ['sql' => 'update admins set nickname = ? where id = ?', 'bindings' => ['新昵称', 10]],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => 200,
            'response_time' => 30.21,
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/operations/'.$record->id);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $record->id,
                'admin_id' => $founder->id,
                'nickname' => $founder->nickname,
                'url' => '/system/admins/10',
                'title' => '账号详情',
                'method' => 'PUT',
                'response_code' => 200,
            ],
        ]);

        self::assertSame('新昵称', $response->json('data.request.nickname'));
        self::assertSame(1, $response->json('data.request.status'));
        self::assertSame('操作成功', $response->json('data.response.message'));
        self::assertSame('update admins set nickname = ? where id = ?', $response->json('data.sql_param.0.sql'));
        self::assertSame(['新昵称', 10], $response->json('data.sql_param.0.bindings'));
    }
}
