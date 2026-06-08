<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Tests\TestCase;

class PTAdminAuthenticateMiddlewareTest extends TestCase
{
    public function test_api_request_returns_json_when_admin_is_not_logged_in(): void
    {
        $this->createAdminsTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/ptadmin/message/unread')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_browser_request_redirects_to_login_notice_when_admin_is_not_logged_in(): void
    {
        $this->createAdminsTable();
        $this->createOperationRecordsTable();
        $this->migratePackageTables();

        $this->get('/ptadmin/message/unread')
            ->assertRedirect('/ptadmin/login?redirect=%2Fptadmin%2Fmessage%2Funread');
    }

    public function test_login_notice_page_can_be_opened_by_get_request(): void
    {
        $response = $this->get('/ptadmin/login');

        $response->assertOk();
        $response->assertSee('需要登录后台');
        $response->assertSee('/admin');
        $response->assertSee('/ptadmin/login');
    }
}
