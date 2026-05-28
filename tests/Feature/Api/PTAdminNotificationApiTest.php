<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Mail;
use PTAdmin\Admin\Models\NotificationDelivery;
use PTAdmin\Admin\Models\NotificationMessage;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Services\SystemConfigGroupService;
use PTAdmin\Admin\Services\NotificationService;
use PTAdmin\Admin\Support\SystemConfigPreset;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminNotificationApiTest extends TestCase
{
    public function test_admin_notification_endpoints_return_current_admin_messages(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'notify_admin',
            'nickname' => 'Notify Admin',
            'is_founder' => 1,
        ]);
        $other = $this->createAdminAccount([
            'username' => 'notify_other',
            'nickname' => 'Notify Other',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = app(NotificationService::class);
        $message = $service->sendToAdmin((int) $admin->id, [
            'source_type' => 'addon',
            'source_code' => 'cms',
            'category' => 'todo',
            'level' => 'warning',
            'title' => '文章待审核',
            'content' => '有 1 篇文章需要审核',
            'action_type' => 'route',
            'action_url' => '/cms/archive',
            'biz_type' => 'cms.archive',
            'biz_id' => '1001',
            'biz_key' => 'cms.archive.pending.1001',
            'data' => ['archive_id' => 1001],
        ]);

        $service->sendToAdmin((int) $other->id, [
            'category' => 'notice',
            'level' => 'info',
            'title' => '其他管理员消息',
        ]);

        $unread = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message/unread');

        $unread->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
                'levels' => [
                    'warning' => 1,
                ],
                'categories' => [
                    'todo' => 1,
                ],
            ],
        ]);

        $list = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message?'.http_build_query([
                'keyword' => '文章',
                'limit' => 10,
            ]));

        $list->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
                'results' => [
                    [
                        'id' => $message['id'],
                        'title' => '文章待审核',
                        'source_code' => 'cms',
                        'category' => 'todo',
                        'level' => 'warning',
                        'is_read' => false,
                        'data' => [
                            'archive_id' => 1001,
                        ],
                    ],
                ],
            ],
        ]);

        $detail = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message/'.$message['id']);

        $detail->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $message['id'],
                'title' => '文章待审核',
                'action_type' => 'route',
                'action_url' => '/cms/archive',
                'biz_key' => 'cms.archive.pending.1001',
            ],
        ]);
    }

    public function test_admin_can_mark_notifications_read_and_delete_own_receipt(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'notify_read',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($admin);

        $service = app(NotificationService::class);
        $first = $service->sendToAdmin((int) $admin->id, [
            'title' => '第一条消息',
            'level' => 'info',
        ]);
        $second = $service->sendToAdmin((int) $admin->id, [
            'title' => '第二条消息',
            'level' => 'error',
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/message/'.$first['id'].'/read')
            ->assertOk()
            ->assertJson(['code' => 0]);

        self::assertNotNull(NotificationReceipt::query()->where('notification_id', $first['id'])->value('read_at'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message/unread')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.levels.error', 1);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/message/read')
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/message/'.$second['id'])
            ->assertOk()
            ->assertJson(['code' => 0]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_admin_notification_categories_return_current_admin_counts(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount([
            'username' => 'notify_categories',
            'is_founder' => 1,
        ]);
        $other = $this->createAdminAccount([
            'username' => 'notify_categories_other',
        ]);
        $token = $this->issueAdminToken($admin);

        $service = app(NotificationService::class);
        $notice = $service->sendToAdmin((int) $admin->id, [
            'title' => '通知消息',
            'category' => 'notice',
        ]);
        $service->sendToAdmin((int) $admin->id, [
            'title' => '待办消息',
            'category' => 'todo',
        ]);
        $service->sendToAdmin((int) $other->id, [
            'title' => '其他人的告警',
            'category' => 'alert',
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/message/'.$notice['id'].'/read')
            ->assertOk();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/message/categories');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'results' => [
                    [
                        'code' => 'all',
                        'title' => '全部',
                        'total' => 2,
                        'unread' => 1,
                    ],
                    [
                        'code' => 'notice',
                        'title' => '通知',
                        'total' => 1,
                        'unread' => 0,
                    ],
                    [
                        'code' => 'todo',
                        'title' => '待办',
                        'total' => 1,
                        'unread' => 1,
                    ],
                    [
                        'code' => 'alert',
                        'title' => '告警',
                        'total' => 0,
                        'unread' => 0,
                    ],
                ],
            ],
        ]);
    }

    public function test_notification_service_uses_shared_message_body_and_separate_receivers(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount(['username' => 'notify_idempotent_a']);
        $other = $this->createAdminAccount(['username' => 'notify_idempotent_b']);
        $service = app(NotificationService::class);

        $first = $service->sendToAdmin((int) $admin->id, [
            'title' => '共享消息',
            'biz_key' => 'shared.biz.key',
        ]);
        $second = $service->sendToAdmin((int) $other->id, [
            'title' => '共享消息更新不应重复创建主体',
            'biz_key' => 'shared.biz.key',
        ]);

        self::assertSame($first['id'], $second['id']);
        self::assertSame(1, NotificationMessage::query()->where('biz_key', 'shared.biz.key')->count());
        self::assertSame(2, NotificationReceipt::query()->where('notification_id', $first['id'])->count());
        self::assertSame(0, NotificationDelivery::query()->count());
    }

    public function test_notification_service_can_send_builtin_mail_delivery(): void
    {
        $this->migratePackageTables();
        Mail::fake();
        $this->configureSystemMail();

        $admin = $this->createAdminAccount([
            'username' => 'notify_mail',
            'email' => 'notify-mail@example.test',
        ]);

        $message = app(NotificationService::class)->sendToAdmin((int) $admin->id, [
            'title' => '邮件通知标题',
            'content' => '邮件通知正文',
            'channels' => ['mail'],
        ]);

        $delivery = NotificationDelivery::query()->where('notification_id', $message['id'])->first();

        self::assertInstanceOf(NotificationDelivery::class, $delivery);
        self::assertSame('mail', $delivery->channel);
        self::assertSame('mail', $delivery->provider);
        self::assertSame('sent', $delivery->status);
        self::assertNull($delivery->error_message);

        self::assertNotNull($delivery->accepted_at);
    }

    public function test_builtin_mail_delivery_failure_is_recorded_without_breaking_message_creation(): void
    {
        $this->migratePackageTables();
        Mail::fake();
        $this->configureSystemMail();

        $admin = $this->createAdminAccount([
            'username' => 'notify_mail_missing',
            'email' => null,
        ]);

        $message = app(NotificationService::class)->sendToAdmin((int) $admin->id, [
            'title' => '缺少邮箱',
            'channels' => ['mail'],
        ]);

        $delivery = NotificationDelivery::query()->where('notification_id', $message['id'])->first();

        self::assertInstanceOf(NotificationDelivery::class, $delivery);
        self::assertSame('failed', $delivery->status);
        self::assertSame('通知接收人邮箱为空', $delivery->error_message);

        Mail::assertNothingSent();
    }

    public function test_builtin_mail_delivery_uses_system_config_and_requires_enabled(): void
    {
        $this->migratePackageTables();
        Mail::fake();

        $admin = $this->createAdminAccount([
            'username' => 'notify_mail_disabled',
            'email' => 'notify-mail-disabled@example.test',
        ]);

        $message = app(NotificationService::class)->sendToAdmin((int) $admin->id, [
            'title' => '邮件未启用',
            'channels' => ['mail'],
        ]);

        $delivery = NotificationDelivery::query()->where('notification_id', $message['id'])->first();

        self::assertInstanceOf(NotificationDelivery::class, $delivery);
        self::assertSame('failed', $delivery->status);
        self::assertSame('内置邮件通知未启用', $delivery->error_message);
        Mail::assertNothingSent();
    }

    public function test_notification_helpers_send_admin_and_user_messages(): void
    {
        $this->migratePackageTables();

        $adminA = $this->createAdminAccount(['username' => 'notify_helper_a']);
        $adminB = $this->createAdminAccount(['username' => 'notify_helper_b']);

        $message = admin_notify([$adminA->id, $adminB->id], [
            'title' => '助手函数消息',
            'category' => 'notice',
        ]);

        self::assertSame('助手函数消息', $message['title']);
        self::assertSame(2, NotificationReceipt::query()
            ->where('notification_id', $message['id'])
            ->where('receiver_type', 'admin')
            ->count());

        $userMessage = user_notify(10001, [
            'title' => '用户助手函数消息',
            'category' => 'notice',
        ]);

        self::assertSame('用户助手函数消息', $userMessage['title']);
        self::assertDatabaseHas('notification_receipts', [
            'notification_id' => $userMessage['id'],
            'receiver_type' => 'user',
            'receiver_id' => 10001,
        ]);
    }

    private function configureSystemMail(): void
    {
        SystemConfigGroupService::installInitialize(SystemConfigPreset::definitions());

        foreach ([
            'enabled' => 1,
            'host' => 'smtp.example.test',
            'port' => 2525,
            'encryption' => '',
            'username' => 'mailer@example.test',
            'password' => 'secret',
            'from_address' => 'noreply@example.test',
            'from_name' => 'PTAdmin Test',
        ] as $name => $value) {
            SystemConfig::query()->where('name', $name)
                ->whereHas('category', static function ($query): void {
                    $query->where('name', 'mail');
                })
                ->update(['value' => $value]);
        }

        SystemConfigService::updateSystemConfigCache();
    }
}
