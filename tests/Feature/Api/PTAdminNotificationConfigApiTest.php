<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\NotificationTemplate;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Notifications\NotificationChannel;
use PTAdmin\Admin\Notifications\NotificationPurpose;
use PTAdmin\Admin\Notifications\NotificationSceneConfigurationStatus;
use PTAdmin\Admin\Notifications\NotificationSceneGroup;
use PTAdmin\Admin\Notifications\NotificationSceneRegistry;
use PTAdmin\Admin\Notifications\NotificationTemplateFormat;
use PTAdmin\Admin\Notifications\NotificationTemplateMode;
use PTAdmin\Admin\Notifications\NotificationVariableType;
use PTAdmin\Admin\Services\NotificationConfigService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminNotificationConfigApiTest extends TestCase
{
    public function test_notification_config_endpoints_require_admin_login(): void
    {
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/ptadmin/notification-config/scenes')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_founder_can_browse_and_update_notification_templates(): void
    {
        $this->migratePackageTables();
        app(NotificationSceneRegistry::class)->syncAddon('cms', $this->definition());
        $token = $this->issueAdminToken($this->createAdminAccount([
            'username' => 'notification_config_founder',
            'is_founder' => 1,
        ]));

        $scenes = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/notification-config/scenes?keyword=archive');
        $scenes->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.results.0.code', 'cms.archive.pending')
            ->assertJsonPath('data.results.0.group_code', NotificationSceneGroup::CONTENT)
            ->assertJsonPath('data.results.0.configuration_status', NotificationSceneConfigurationStatus::COMPLETE)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.results.0.template_count', 2);

        $sceneId = (int) $scenes->json('data.results.0.id');
        $detail = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/notification-config/scenes/'.$sceneId);
        $detail->assertOk()
            ->assertJsonPath('data.variables.0.name', 'archive_title')
            ->assertJsonCount(2, 'data.templates');

        $siteTemplate = NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail();
        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/ptadmin/notification-config/templates/'.$siteTemplate->id, [
                'config' => [
                    'format' => NotificationTemplateFormat::HTML,
                    'subject' => '待审核：{{ archive_title }}',
                    'content' => '<strong>{{ author_name }}</strong>',
                ],
                'enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.customized', true)
            ->assertJsonPath('data.config.format', NotificationTemplateFormat::HTML);

        self::assertSame(1, (int) $siteTemplate->fresh()->customized);
        self::assertSame('待审核：{{ archive_title }}', $siteTemplate->fresh()->config['subject']);

        $channels = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/notification-config/channels');
        $channels->assertOk()->assertJsonPath('code', 0);
        $channelRows = collect($channels->json('data.results'))->keyBy('code');
        self::assertTrue((bool) $channelRows->get(NotificationChannel::SITE)['enabled']);
        self::assertSame(1, (int) $channelRows->get(NotificationChannel::SITE)['template_count']);
        self::assertSame('mail', $channelRows->get(NotificationChannel::MAIL)['driver']);
        self::assertFalse($channelRows->has(NotificationChannel::SMS));
        self::assertFalse($channelRows->has(NotificationChannel::WECHAT_MINI_PROGRAM));
        self::assertSame(NotificationChannel::SITE, $channelRows->get(NotificationChannel::SITE)['providers'][0]['code']);
    }

    public function test_template_update_rejects_undeclared_placeholders_without_changing_config(): void
    {
        $this->migratePackageTables();
        app(NotificationSceneRegistry::class)->syncAddon('cms', $this->definition());
        $template = NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail();
        $original = $template->config;

        try {
            app(NotificationConfigService::class)->updateTemplate((int) $template->id, [
                    'format' => NotificationTemplateFormat::TEXT,
                    'subject' => '{{ unknown_name }}',
                    'content' => '正文',
            ]);
            self::fail('未声明占位符应拒绝保存');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('未声明变量 [unknown_name]', $exception->getMessage());
        }

        self::assertSame($original, $template->fresh()->config);
        self::assertSame(0, (int) $template->fresh()->customized);
    }

    public function test_default_channel_template_cannot_be_disabled(): void
    {
        $this->migratePackageTables();
        app(NotificationSceneRegistry::class)->syncAddon('cms', $this->definition());
        $template = NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail();

        try {
            app(NotificationConfigService::class)->updateTemplate(
                (int) $template->id,
                (array) $template->config,
                false
            );
            self::fail('默认渠道模板应拒绝禁用');
        } catch (\PTAdmin\Foundation\Exceptions\BackgroundException $exception) {
            self::assertSame('默认渠道模板不能禁用，请先调整场景默认渠道', $exception->getMessage());
        }

        self::assertSame(1, (int) $template->fresh()->enabled);
        self::assertSame(0, (int) $template->fresh()->customized);
    }

    public function test_template_status_can_be_updated_without_customizing_plugin_content(): void
    {
        $this->migratePackageTables();
        app(NotificationSceneRegistry::class)->syncAddon('cms', $this->definition());
        $template = NotificationTemplate::query()
            ->where('channel', NotificationChannel::WECHAT_MINI_PROGRAM)
            ->firstOrFail();
        $originalConfig = $template->config;
        $token = $this->issueAdminToken($this->createAdminAccount([
            'username' => 'notification_status_founder',
            'is_founder' => 1,
        ]));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/ptadmin/notification-config/templates/'.$template->id, [
                'enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.customized', false);

        self::assertSame(0, (int) $template->fresh()->enabled);
        self::assertSame(0, (int) $template->fresh()->customized);
        self::assertSame($originalConfig, $template->fresh()->config);
    }

    public function test_scene_list_filters_source_group_channel_status_and_configuration_state(): void
    {
        $this->migratePackageTables();
        $definition = $this->definition();

        $securityScene = $definition['scenes'][0];
        $securityScene['code'] = 'cms.login.notice';
        $securityScene['title'] = '登录通知';
        $securityScene['group'] = NotificationSceneGroup::SECURITY;

        $smsScene = $definition['scenes'][0];
        $smsScene['code'] = 'cms.password.reset';
        $smsScene['title'] = '找回密码';
        $smsScene['group'] = NotificationSceneGroup::ACCOUNT;
        $smsScene['default_channels'] = [NotificationChannel::SMS];
        $smsScene['templates'] = [[
            'channel' => NotificationChannel::SMS,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::REFERENCE,
            'template_key' => 'PASSWORD_RESET',
            'variable_map' => ['thing1' => 'archive_title'],
        ]];

        $definition['scenes'][] = $securityScene;
        $definition['scenes'][] = $smsScene;
        app(NotificationSceneRegistry::class)->syncAddon('cms', $definition);

        $securitySceneId = (int) \PTAdmin\Admin\Models\NotificationScene::query()
            ->where('code', 'cms.login.notice')
            ->value('id');
        NotificationTemplate::query()->where('scene_id', $securitySceneId)->update(['enabled' => 0]);

        $token = $this->issueAdminToken($this->createAdminAccount([
            'username' => 'notification_filter_founder',
            'is_founder' => 1,
        ]));
        $headers = $this->jsonApiHeaders($token);

        $this->withHeaders($headers)
            ->getJson('/ptadmin/notification-config/scenes?source_type=addon&source_code=cms&group_code=content&channel=site&configuration_status=complete&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.results.0.code', 'cms.archive.pending')
            ->assertJsonPath('data.filters.sources.0.source_code', 'cms')
            ->assertJsonFragment(['code' => NotificationSceneConfigurationStatus::INCOMPLETE, 'title' => '配置不完整']);

        $this->withHeaders($headers)
            ->getJson('/ptadmin/notification-config/scenes?configuration_status=pending')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.results.0.code', 'cms.login.notice');

        $this->withHeaders($headers)
            ->getJson('/ptadmin/notification-config/scenes?configuration_status=incomplete')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.results.0.code', 'cms.password.reset')
            ->assertJsonPath('data.results.0.missing_default_channels.0', NotificationChannel::SMS);
    }

    public function test_founder_can_route_a_scene_to_plugin_owned_instances(): void
    {
        $this->migratePackageTables();
        $this->registerPluginProvider();
        $definition = $this->definition();
        $definition['scenes'][0]['default_channels'] = [NotificationChannel::WEBHOOK];
        $definition['scenes'][0]['templates'] = [[
            'channel' => NotificationChannel::WEBHOOK,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::CONTENT,
            'format' => NotificationTemplateFormat::TEXT,
            'subject' => '文章 {{ archive_title }} 待审核',
            'content' => '作者：{{ author_name }}',
        ]];
        app(NotificationSceneRegistry::class)->syncAddon('cms', $definition);
        $token = $this->issueAdminToken($this->createAdminAccount([
            'username' => 'notification_profile_founder',
            'is_founder' => 1,
        ]));
        $headers = $this->jsonApiHeaders($token);

        $channels = $this->withHeaders($headers)->getJson('/ptadmin/notification-config/channels');
        $channels->assertOk()
            ->assertJsonFragment(['code' => 'order-group', 'name' => '订单群']);
        $webhook = collect($channels->json('data.results'))->firstWhere('code', NotificationChannel::WEBHOOK);
        self::assertArrayNotHasKey('profile_schema', $webhook['providers'][0]);
        self::assertArrayNotHasKey('config', $webhook['providers'][0]['instances'][0]);
        self::assertFalse(Schema::hasTable('notification_channel_profiles'));

        $sceneId = (int) \PTAdmin\Admin\Models\NotificationScene::query()
            ->where('code', 'cms.archive.pending')
            ->value('id');
        $this->withHeaders($headers)
            ->putJson('/ptadmin/notification-config/scenes/'.$sceneId.'/routes/webhook', [
                'dispatch_mode' => 'fan_out',
                'strategy' => null,
                'instances' => [[
                    'addon_code' => 'test_notify',
                    'group' => 'notify',
                    'provider' => 'test_provider',
                    'instance_code' => 'order-group',
                ], [
                    'addon_code' => 'test_notify',
                    'group' => 'notify',
                    'provider' => 'test_provider',
                    'instance_code' => 'operation-group',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.channel', NotificationChannel::WEBHOOK)
            ->assertJsonPath('data.instances.0.instance.code', 'order-group')
            ->assertJsonPath('data.instances.1.instance.code', 'operation-group');

        $this->withHeaders($headers)
            ->getJson('/ptadmin/notification-config/scenes/'.$sceneId)
            ->assertOk()
            ->assertJsonPath('data.configuration_status', NotificationSceneConfigurationStatus::COMPLETE)
            ->assertJsonPath('data.routes.0.dispatch_mode', 'fan_out');

        $this->withHeaders($headers)
            ->deleteJson('/ptadmin/notification-config/scenes/'.$sceneId.'/routes/webhook')
            ->assertOk()
            ->assertJsonPath('data.mode', 'automatic');

        $this->withHeaders($headers)
            ->getJson('/ptadmin/notification-config/scenes/'.$sceneId)
            ->assertOk()
            ->assertJsonCount(0, 'data.routes')
            ->assertJsonPath('data.configuration_status', NotificationSceneConfigurationStatus::INCOMPLETE);
    }

    private function definition(): array
    {
        return [
            'schema_version' => 1,
            'scenes' => [[
                'code' => 'cms.archive.pending',
                'title' => '文章待审核',
                'group' => NotificationSceneGroup::CONTENT,
                'purpose' => NotificationPurpose::TRANSACTIONAL,
                'default_channels' => [NotificationChannel::SITE],
                'variables' => [[
                    'name' => 'archive_title',
                    'type' => NotificationVariableType::STRING,
                    'required' => true,
                ], [
                    'name' => 'author_name',
                    'type' => NotificationVariableType::STRING,
                ]],
                'templates' => [[
                    'channel' => NotificationChannel::SITE,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::CONTENT,
                    'format' => NotificationTemplateFormat::TEXT,
                    'subject' => '文章 {{ archive_title }} 待审核',
                    'content' => '作者：{{ author_name }}',
                ], [
                    'channel' => NotificationChannel::WECHAT_MINI_PROGRAM,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::REFERENCE,
                    'template_key' => 'archive-pending',
                    'variable_map' => ['thing1' => 'archive_title'],
                ]],
            ]],
        ];
    }

    private function registerPluginProvider(): void
    {
        Addon::swap(new TestAddonManager());
    }
}

final class TestAddonManager
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
                'class' => TestNotificationProvider::class,
            ]],
        ];
    }
}

final class TestNotificationProvider
{
    public function ready(): bool
    {
        return true;
    }

    public function instances(): array
    {
        return [[
            'code' => 'order-group',
            'name' => '订单群',
            'target_mode' => 'fixed',
            'available' => true,
        ], [
            'code' => 'operation-group',
            'name' => '运维群',
            'target_mode' => 'fixed',
            'available' => true,
        ]];
    }
}
