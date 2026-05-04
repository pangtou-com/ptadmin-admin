<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Http\Request;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Admin\Services\OperationRecordService;
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
            'admin_username' => $founder->username,
            'nickname' => $founder->nickname,
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'url' => '/system/roles',
            'title' => '角色管理',
            'resource_name' => 'system.role',
            'method' => 'GET',
            'controller' => 'PTAdmin\\Admin\\Controllers\\RoleController',
            'action' => 'index',
            'trace_id' => 'trace-list-1',
            'target_type' => 'roles',
            'target_id' => null,
            'status' => 'success',
            'request' => null,
            'error_message' => null,
            'response_code' => 200,
            'response_time' => 12.34,
        ]);

        OperationRecord::query()->create([
            'admin_id' => $founder->id,
            'admin_username' => $founder->username,
            'nickname' => $founder->nickname,
            'ip' => '127.0.0.2',
            'user_agent' => 'PHPUnit',
            'url' => '/system/resources',
            'title' => '资源管理',
            'resource_name' => 'system.resources',
            'method' => 'POST',
            'controller' => 'PTAdmin\\Admin\\Controllers\\ResourceController',
            'action' => 'store',
            'trace_id' => 'trace-list-2',
            'target_type' => 'resources',
            'target_id' => null,
            'status' => 'success',
            'request' => json_encode(['name' => '仪表盘'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'response_code' => 200,
            'response_time' => 23.45,
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/operations?'.http_build_query([
                'filters' => [
                    ['field' => 'status', 'operator' => 'eq', 'value' => 'success'],
                ],
                'keyword' => 'resource',
                'limit' => 1,
                'page' => 1,
            ]));

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        self::assertCount(1, (array) $response->json('data.results'));
        self::assertSame('founder_operation_list', $response->json('data.results.0.admin_username'));
        self::assertSame('/system/resources', $response->json('data.results.0.url'));
        self::assertSame('POST', $response->json('data.results.0.method'));
        self::assertSame('system.resources', $response->json('data.results.0.resource_name'));
        self::assertSame('资源管理', $response->json('data.results.0.title'));
        self::assertSame('success', $response->json('data.results.0.status'));
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
            'admin_username' => $founder->username,
            'nickname' => $founder->nickname,
            'ip' => '127.0.0.10',
            'user_agent' => 'PHPUnit',
            'url' => '/system/admins/10',
            'title' => '账号详情',
            'resource_name' => 'system.admins',
            'method' => 'PUT',
            'controller' => 'PTAdmin\\Admin\\Controllers\\AdminController',
            'action' => 'update',
            'trace_id' => 'trace-details-1',
            'target_type' => 'admins',
            'target_id' => '10',
            'status' => 'success',
            'request' => json_encode(['nickname' => '新昵称', 'status' => 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => null,
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
                'admin_username' => 'founder_operation_details',
                'nickname' => $founder->nickname,
                'url' => '/system/admins/10',
                'title' => '账号详情',
                'resource_name' => 'system.admins',
                'method' => 'PUT',
                'status' => 'success',
                'target_type' => 'admins',
                'target_id' => '10',
                'response_code' => 200,
            ],
        ]);

        self::assertSame('新昵称', $response->json('data.request.nickname'));
        self::assertSame(1, $response->json('data.request.status'));
        self::assertNull($response->json('data.error_message'));
    }

    public function test_operation_record_resolves_audit_resource_from_route_defaults(): void
    {
        $this->migratePackageTables();

        $request = Request::create('/system/operations', 'GET');
        $route = app('router')->getRoutes()->match($request);
        $service = new OperationRecordService();
        $method = new \ReflectionMethod(OperationRecordService::class, 'resolveResourceMeta');
        $method->setAccessible(true);
        $meta = $method->invoke($service, $route);

        self::assertSame('system.operate', $meta['resource_name'] ?? null);
        self::assertSame('操作日志', $meta['title'] ?? null);
    }
}
