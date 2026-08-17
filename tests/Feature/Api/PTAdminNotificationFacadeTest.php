<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Mail;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Models\NotificationDelivery;
use PTAdmin\Admin\Models\NotificationMessage;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Notifications\NotificationChannel;
use PTAdmin\Admin\Notifications\NotificationDispatchResult;
use PTAdmin\Admin\Notifications\NotificationDispatchStatus;
use PTAdmin\Admin\Notifications\NotificationManager;
use PTAdmin\Admin\Notifications\NotificationPurpose;
use PTAdmin\Admin\Notifications\NotificationSceneRegistry;
use PTAdmin\Admin\Notifications\NotificationTemplateFormat;
use PTAdmin\Admin\Notifications\NotificationTemplateMode;
use PTAdmin\Admin\Notifications\NotificationVariableType;
use PTAdmin\Admin\Services\NotificationConfigService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminNotificationFacadeTest extends TestCase
{
    public function test_notify_resolves_the_registered_manager_singleton(): void
    {
        self::assertInstanceOf(NotificationManager::class, notify());
        self::assertSame(notify(), app(NotificationManager::class));
    }

    public function test_fluent_notification_sends_one_message_to_a_deduplicated_batch(): void
    {
        $this->migratePackageTables();
        $this->registerScene();

        $adminA = $this->createAdminAccount(['username' => 'notify_facade_a']);
        $adminB = $this->createAdminAccount(['username' => 'notify_facade_b']);

        $result = notify()
            ->toAdminIds([(int) $adminA->id, (int) $adminB->id, (int) $adminA->id])
            ->channel(NotificationChannel::MAIL)
            ->send('cms.archive.pending', [
                'archive_id' => 1001,
                'operator' => 'system',
            ], [
                'title' => '调用方标题不会覆盖模板',
                'data' => ['operator' => 'admin'],
            ]);

        self::assertInstanceOf(NotificationDispatchResult::class, $result);
        self::assertSame(NotificationDispatchStatus::SUBMITTED, $result->status());
        self::assertSame(2, $result->recipientCount());
        self::assertSame(2, $result->deliveryCount());
        self::assertSame('cms.archive.pending', $result->message()['biz_type']);
        self::assertSame('邮件：文章 1001 待审核', $result->message()['title']);
        self::assertSame('邮件操作人：system', $result->message()['content']);
        self::assertSame([
            'archive_id' => 1001,
            'operator' => 'admin',
        ], $result->message()['data']);

        self::assertSame(1, NotificationMessage::query()->count());
        self::assertSame(2, NotificationReceipt::query()
            ->where('notification_id', $result->notificationId())
            ->where('receiver_type', NotificationReceipt::RECEIVER_ADMIN)
            ->count());
        self::assertSame(2, NotificationDelivery::query()
            ->where('notification_id', $result->notificationId())
            ->where('channel', NotificationChannel::MAIL)
            ->count());
        self::assertSame($result->toArray(), $result->jsonSerialize());
    }

    public function test_fluent_notification_accepts_admin_and_user_objects_and_ids(): void
    {
        $this->migratePackageTables();
        $this->registerScene('target.generic');

        $adminA = $this->createAdminAccount(['username' => 'notify_target_a']);
        $adminB = $this->createAdminAccount(['username' => 'notify_target_b']);
        $userA = $this->userWithId(10001);
        $userB = $this->userWithId(10002);

        $cases = [
            [notify()->toAdmin($adminA), NotificationReceipt::RECEIVER_ADMIN, [(int) $adminA->id]],
            [notify()->toAdminId((int) $adminB->id), NotificationReceipt::RECEIVER_ADMIN, [(int) $adminB->id]],
            [notify()->toAdmins([$adminA, $adminB, $adminA]), NotificationReceipt::RECEIVER_ADMIN, [(int) $adminA->id, (int) $adminB->id]],
            [notify()->toUser($userA), NotificationReceipt::RECEIVER_USER, [10001]],
            [notify()->toUserId(10002), NotificationReceipt::RECEIVER_USER, [10002]],
            [notify()->toUsers([$userA, $userB, $userA]), NotificationReceipt::RECEIVER_USER, [10001, 10002]],
            [notify()->toUserIds([10001, 10002, 10001]), NotificationReceipt::RECEIVER_USER, [10001, 10002]],
        ];

        foreach ($cases as [$pending, $receiverType, $receiverIds]) {
            $result = $pending->send('target.generic', ['archive_id' => 1]);

            self::assertSame(count($receiverIds), $result->recipientCount());
            self::assertSame($receiverIds, NotificationReceipt::query()
                ->where('notification_id', $result->notificationId())
                ->where('receiver_type', $receiverType)
                ->orderBy('receiver_id')
                ->pluck('receiver_id')
                ->map(static function ($id): int {
                    return (int) $id;
                })
                ->all());
        }
    }

    public function test_site_channel_uses_the_receipt_without_creating_external_delivery(): void
    {
        $this->migratePackageTables();
        $this->registerScene('site.only');

        $result = notify()
            ->toUserId(10001)
            ->channel(NotificationChannel::SITE)
            ->send('site.only', ['archive_id' => 1]);

        self::assertSame(1, $result->recipientCount());
        self::assertSame(0, $result->deliveryCount());
        self::assertDatabaseHas('notification_receipts', [
            'notification_id' => $result->notificationId(),
            'receiver_type' => NotificationReceipt::RECEIVER_USER,
            'receiver_id' => 10001,
        ]);
        self::assertSame(0, NotificationDelivery::query()->count());
    }

    public function test_fluent_notification_validates_recipients_scene_message_and_channels(): void
    {
        $this->migratePackageTables();

        try {
            notify()->pending()->send('missing.recipient', [], ['title' => '无接收人']);
            self::fail('未指定接收人时应拒绝发送');
        } catch (\LogicException $exception) {
            self::assertSame('发送通知前必须指定接收人', $exception->getMessage());
        }

        try {
            notify()->toUserId(10001)->send('missing.scene');
            self::fail('未注册场景应拒绝发送');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('通知场景 [missing.scene] 未注册或已禁用', $exception->getMessage());
        }

        try {
            notify()->toUserId(10001)
                ->channel('Invalid Channel')
                ->send('invalid.channel', [], ['title' => '无效渠道']);
            self::fail('无效渠道编码应被拒绝');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('通知渠道编码无效', $exception->getMessage());
        }

        $admin = $this->createAdminAccount(['username' => 'notify_invalid_target']);
        try {
            notify()->toUsers([$this->userWithId(10001), $admin]);
            self::fail('混合接收人对象应被拒绝');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('toUsers() 只接受 User 对象', $exception->getMessage());
        }
    }

    public function test_scene_send_rejects_a_declared_channel_without_an_installed_provider(): void
    {
        $this->migratePackageTables();
        $definition = $this->sceneDefinition('unavailable.channel');
        $definition['scenes'][0]['default_channels'] = [NotificationChannel::WECHAT_MINI_PROGRAM];
        $definition['scenes'][0]['templates'][0]['channel'] = NotificationChannel::WECHAT_MINI_PROGRAM;
        app(NotificationSceneRegistry::class)->syncAddon('test', $definition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('通知渠道 [wechat_mini_program] 没有已安装并启用的实现');

        notify()->toUserId(10001)
            ->channel(NotificationChannel::WECHAT_MINI_PROGRAM)
            ->send('unavailable.channel', [
                'archive_id' => 2,
                'operator' => 'system',
            ]);
    }

    public function test_legacy_helpers_keep_their_array_result_contract(): void
    {
        $this->migratePackageTables();

        $admin = $this->createAdminAccount(['username' => 'notify_legacy']);
        $adminMessage = admin_notify((int) $admin->id, ['title' => '管理员兼容通知']);
        $userMessage = user_notify([20001, 20002, 20001], ['title' => '用户兼容通知']);

        self::assertIsArray($adminMessage);
        self::assertSame('管理员兼容通知', $adminMessage['title']);
        self::assertIsArray($userMessage);
        self::assertSame('用户兼容通知', $userMessage['title']);
        self::assertSame(2, NotificationReceipt::query()
            ->where('notification_id', $userMessage['id'])
            ->where('receiver_type', NotificationReceipt::RECEIVER_USER)
            ->count());
    }

    public function test_scene_runtime_applies_defaults_and_only_validates_required_values(): void
    {
        $this->migratePackageTables();
        $this->registerScene();

        foreach ([0, false] as $archiveId) {
            $result = notify()->toUserId(10001)->send('cms.archive.pending', [
                'archive_id' => $archiveId,
                'operator' => ['arbitrary' => 'value'],
            ]);
            self::assertSame($archiveId, $result->message()['data']['archive_id']);
        }

        $result = notify()->toUserId(10001)->send('cms.archive.pending', ['archive_id' => 2]);
        self::assertSame('system', $result->message()['data']['operator']);

        foreach ([null, '', []] as $missing) {
            try {
                notify()->toUserId(10001)->send('cms.archive.pending', ['archive_id' => $missing]);
                self::fail('必填变量为空时应拒绝发送');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame('通知场景 [cms.archive.pending] 缺少必填变量 [archive_id]', $exception->getMessage());
            }
        }
    }

    public function test_fan_out_route_creates_one_delivery_for_each_plugin_instance(): void
    {
        $this->migratePackageTables();
        Addon::swap(new FacadeTestAddonManager());
        config()->set('ptadmin.notifications.delivery.sync', false);
        $definition = $this->sceneDefinition('webhook.fanout');
        $definition['scenes'][0]['default_channels'] = [NotificationChannel::WEBHOOK];
        $definition['scenes'][0]['templates'] = [[
            'channel' => NotificationChannel::WEBHOOK,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::CONTENT,
            'format' => NotificationTemplateFormat::JSON,
            'subject' => 'Webhook 通知',
            'content' => '{"archive_id": {{ archive_id }}}',
        ]];
        app(NotificationSceneRegistry::class)->syncAddon('test', $definition);

        $sceneId = (int) \PTAdmin\Admin\Models\NotificationScene::query()
            ->where('code', 'webhook.fanout')
            ->value('id');
        app(NotificationConfigService::class)->updateRoutes($sceneId, NotificationChannel::WEBHOOK, [
            'dispatch_mode' => 'fan_out',
            'strategy' => null,
            'instances' => array_map(static function (string $instanceCode): array {
                return [
                    'addon_code' => 'test_notify',
                    'group' => 'notify',
                    'provider' => 'test_provider',
                    'instance_code' => $instanceCode,
                ];
            }, ['primary', 'backup']),
        ]);
        $admin = $this->createAdminAccount(['username' => 'notify_fanout']);

        $result = notify()->toAdminId((int) $admin->id)->send('webhook.fanout', [
            'archive_id' => 7,
            'operator' => 'system',
        ]);

        self::assertSame(2, $result->deliveryCount());
        self::assertSame(['backup', 'primary'], NotificationDelivery::query()
            ->where('notification_id', $result->notificationId())
            ->orderBy('instance_code')
            ->pluck('instance_code')
            ->all());
        self::assertSame(2, NotificationDelivery::query()
            ->where('notification_id', $result->notificationId())
            ->where('status', 'pending')
            ->count());
    }

    public function test_html_templates_escape_variables_and_secret_values_are_not_persisted(): void
    {
        $this->migratePackageTables();
        $definition = $this->sceneDefinition('secure.html');
        $definition['scenes'][0]['templates'][0]['format'] = NotificationTemplateFormat::HTML;
        $definition['scenes'][0]['templates'][0]['content'] = '{{ operator }} / {{ verification_code }}';
        $definition['scenes'][0]['templates'][1]['content'] = '验证码：{{ verification_code }}';
        app(NotificationSceneRegistry::class)->syncAddon('test', $definition);

        $result = notify()
            ->toUserId(10001)
            ->channels([NotificationChannel::SITE, NotificationChannel::MAIL])
            ->send('secure.html', [
                'archive_id' => 1,
                'operator' => '<b>admin</b>',
                'verification_code' => '938201',
            ]);

        self::assertSame('&lt;b&gt;admin&lt;/b&gt; /', $result->message()['content']);
        self::assertArrayNotHasKey('verification_code', $result->message()['data']);
        self::assertStringNotContainsString('938201', json_encode($result->message()));
        $delivery = NotificationDelivery::query()->firstOrFail();
        self::assertStringNotContainsString('938201', json_encode($delivery->toArray()));
        self::assertTrue((bool) $delivery->meta['channel']['sensitive']);
    }

    public function test_automatic_route_uses_a_replacement_sms_plugin_without_reconfiguration(): void
    {
        $this->migratePackageTables();
        config()->set('ptadmin.notifications.delivery.sync', false);
        Addon::swap(new AutomaticSmsAddonManager('sms_a', 'sms_provider_a', AutomaticSmsProviderA::class));

        $definition = $this->sceneDefinition('sms.automatic');
        $definition['scenes'][0]['default_channels'] = [NotificationChannel::SMS];
        $definition['scenes'][0]['templates'] = [[
            'channel' => NotificationChannel::SMS,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::CONTENT,
            'format' => NotificationTemplateFormat::TEXT,
            'subject' => '短信通知',
            'content' => '文章 {{ archive_id }}',
        ]];
        app(NotificationSceneRegistry::class)->syncAddon('test', $definition);
        $admin = $this->createAdminAccount(['username' => 'notify_automatic_sms']);

        $first = notify()->toAdminId((int) $admin->id)->send('sms.automatic', ['archive_id' => 9]);
        $firstDelivery = NotificationDelivery::query()
            ->where('notification_id', $first->notificationId())
            ->firstOrFail();
        self::assertSame('sms_a', $firstDelivery->addon_code);
        self::assertSame('sms_provider_a', $firstDelivery->provider);
        self::assertSame('alpha', $firstDelivery->instance_code);

        Addon::swap(new AutomaticSmsAddonManager('sms_b', 'sms_provider_b', AutomaticSmsProviderB::class));
        $second = notify()->toAdminId((int) $admin->id)->send('sms.automatic', ['archive_id' => 10]);
        $secondDelivery = NotificationDelivery::query()
            ->where('notification_id', $second->notificationId())
            ->firstOrFail();
        self::assertSame('sms_b', $secondDelivery->addon_code);
        self::assertSame('sms_provider_b', $secondDelivery->provider);
        self::assertSame('replacement', $secondDelivery->instance_code);
        self::assertDatabaseCount('notification_scene_routes', 0);
    }

    public function test_removed_plugin_instance_keeps_the_route_and_records_a_failed_delivery(): void
    {
        $this->migratePackageTables();
        Addon::swap(new FacadeTestAddonManager());
        $definition = $this->sceneDefinition('webhook.removed');
        $definition['scenes'][0]['default_channels'] = [NotificationChannel::WEBHOOK];
        $definition['scenes'][0]['templates'] = [[
            'channel' => NotificationChannel::WEBHOOK,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::CONTENT,
            'format' => NotificationTemplateFormat::JSON,
            'subject' => 'Webhook 通知',
            'content' => '{"archive_id": {{ archive_id }}}',
        ]];
        app(NotificationSceneRegistry::class)->syncAddon('test', $definition);
        $sceneId = (int) \PTAdmin\Admin\Models\NotificationScene::query()
            ->where('code', 'webhook.removed')
            ->value('id');
        app(NotificationConfigService::class)->updateRoutes($sceneId, NotificationChannel::WEBHOOK, [
            'dispatch_mode' => 'select_one',
            'strategy' => 'fixed',
            'instances' => [[
                'addon_code' => 'test_notify',
                'group' => 'notify',
                'provider' => 'test_provider',
                'instance_code' => 'primary',
            ]],
        ]);

        Addon::swap(new EmptyFacadeTestAddonManager());
        $admin = $this->createAdminAccount(['username' => 'notify_removed_instance']);
        $result = notify()->toAdminId((int) $admin->id)->send('webhook.removed', [
            'archive_id' => 8,
        ]);

        $delivery = NotificationDelivery::query()
            ->where('notification_id', $result->notificationId())
            ->firstOrFail();
        self::assertSame('test_notify', $delivery->addon_code);
        self::assertSame('primary', $delivery->instance_code);
        self::assertSame('failed', $delivery->status);
        self::assertSame('插件渠道实例 [test_provider/primary] 不存在或不可用', $delivery->error_message);
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $user->setAttribute('id', $id);

        return $user;
    }

    private function registerScene(string $code = 'cms.archive.pending'): void
    {
        app(NotificationSceneRegistry::class)->syncAddon('test', $this->sceneDefinition($code));
    }

    private function sceneDefinition(string $code): array
    {
        return [
            'schema_version' => 1,
            'scenes' => [[
                'code' => $code,
                'title' => '文章待审核',
                'purpose' => NotificationPurpose::TRANSACTIONAL,
                'default_channels' => [NotificationChannel::SITE],
                'variables' => [[
                    'name' => 'archive_id',
                    'type' => NotificationVariableType::INTEGER,
                    'required' => true,
                ], [
                    'name' => 'operator',
                    'type' => NotificationVariableType::STRING,
                    'default' => 'system',
                ], [
                    'name' => 'verification_code',
                    'type' => NotificationVariableType::STRING,
                    'secret' => true,
                ]],
                'templates' => [[
                    'channel' => NotificationChannel::SITE,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::CONTENT,
                    'format' => NotificationTemplateFormat::TEXT,
                    'subject' => '文章 {{ archive_id }} 待审核',
                    'content' => '操作人：{{ operator }}',
                ], [
                    'channel' => NotificationChannel::MAIL,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::CONTENT,
                    'format' => NotificationTemplateFormat::TEXT,
                    'subject' => '邮件：文章 {{ archive_id }} 待审核',
                    'content' => '邮件操作人：{{ operator }}',
                ]],
            ]],
        ];
    }
}

final class FacadeTestNotificationProvider
{
    public function ready(): bool
    {
        return true;
    }

    public function instances(): array
    {
        return [[
            'code' => 'primary',
            'name' => '主通道',
            'target_mode' => 'fixed',
        ], [
            'code' => 'backup',
            'name' => '备用通道',
            'target_mode' => 'fixed',
        ]];
    }
}

final class FacadeTestAddonManager
{
    public function getAddons(): array
    {
        return ['test_notify' => []];
    }

    public function getInject(string $addonCode): array
    {
        return [
            'notify' => [[
                'code' => 'test_provider',
                'title' => '测试通知渠道',
                'type' => [NotificationChannel::WEBHOOK],
                'class' => FacadeTestNotificationProvider::class,
            ]],
        ];
    }

    public function executeInject(string $group, string $code, array $payload = [], ?string $action = null): array
    {
        return [
            'status' => 'delivered',
            'accepted_at' => time(),
            'delivered_at' => time(),
        ];
    }
}

final class EmptyFacadeTestAddonManager
{
    public function getAddons(): array
    {
        return [];
    }

    public function getInject(string $addonCode): array
    {
        return [];
    }
}

final class AutomaticSmsAddonManager
{
    /** @var string */
    private $addonCode;

    /** @var string */
    private $providerCode;

    /** @var string */
    private $handler;

    public function __construct(string $addonCode, string $providerCode, string $handler)
    {
        $this->addonCode = $addonCode;
        $this->providerCode = $providerCode;
        $this->handler = $handler;
    }

    public function getAddons(): array
    {
        return [$this->addonCode => []];
    }

    public function getInject(string $addonCode): array
    {
        return [
            'sms' => [[
                'code' => $this->providerCode,
                'title' => $this->providerCode,
                'type' => ['notice'],
                'class' => $this->handler,
            ]],
        ];
    }
}

final class AutomaticSmsProviderA
{
    public function ready(): bool
    {
        return true;
    }

    public function instances(): array
    {
        return [[
            'code' => 'beta',
            'name' => '备用短信通道',
            'target_mode' => 'dynamic',
        ], [
            'code' => 'alpha',
            'name' => '主短信通道',
            'target_mode' => 'dynamic',
        ]];
    }
}

final class AutomaticSmsProviderB
{
    public function ready(): bool
    {
        return true;
    }

    public function instances(): array
    {
        return [[
            'code' => 'replacement',
            'name' => '替换短信通道',
            'target_mode' => 'dynamic',
        ]];
    }
}
