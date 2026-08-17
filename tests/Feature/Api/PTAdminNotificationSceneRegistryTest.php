<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\NotificationScene;
use PTAdmin\Admin\Models\NotificationTemplate;
use PTAdmin\Admin\Notifications\NotificationChannel;
use PTAdmin\Admin\Notifications\NotificationPurpose;
use PTAdmin\Admin\Notifications\NotificationSceneGroup;
use PTAdmin\Admin\Notifications\NotificationSceneRegistry;
use PTAdmin\Admin\Notifications\NotificationTemplateFormat;
use PTAdmin\Admin\Notifications\NotificationTemplateMode;
use PTAdmin\Admin\Notifications\NotificationVariableType;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminNotificationSceneRegistryTest extends TestCase
{
    public function test_sync_addon_creates_and_updates_owned_scenes_idempotently(): void
    {
        $this->migratePackageTables();
        $registry = app(NotificationSceneRegistry::class);

        $registry->syncAddon('cms', $this->definition('文章 {{ archive_title }} 待审核'));
        $registry->syncAddon('cms', $this->definition('文章 {{ archive_title }} 等待处理'));

        self::assertSame(1, NotificationScene::query()->count());
        self::assertSame(2, NotificationTemplate::query()->count());
        self::assertDatabaseHas('notification_scenes', [
            'code' => 'cms.archive.pending',
            'source_type' => 'addon',
            'source_code' => 'cms',
            'group_code' => NotificationSceneGroup::CONTENT,
            'group_title' => '内容通知',
            'enabled' => 1,
        ]);
        $template = NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail();
        self::assertSame('文章 {{ archive_title }} 等待处理', $template->config['subject']);
    }

    public function test_sync_preserves_customized_template_and_disables_removed_definitions(): void
    {
        $this->migratePackageTables();
        $registry = app(NotificationSceneRegistry::class);
        $registry->syncAddon('cms', $this->definition('初始标题'));

        $template = NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail();
        $template->config = ['format' => 'text', 'subject' => '用户标题', 'content' => '用户正文'];
        $template->customized = 1;
        $template->save();

        $registry->syncAddon('cms', $this->definition('插件升级标题'));
        self::assertSame('用户标题', $template->fresh()->config['subject']);

        $registry->syncAddon('cms', ['schema_version' => 1, 'scenes' => []]);
        self::assertSame(0, (int) NotificationScene::query()->firstOrFail()->enabled);
        self::assertSame(0, (int) NotificationTemplate::query()->where('channel', NotificationChannel::SITE)->firstOrFail()->enabled);

        $registry->disableAddon('cms');
        $registry->disableAddon('unknown');
    }

    public function test_sync_rejects_scene_ownership_conflicts_without_partial_writes(): void
    {
        $this->migratePackageTables();
        $registry = app(NotificationSceneRegistry::class);
        $registry->syncAddon('cms', $this->definition('CMS 标题'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('已由 [addon:cms] 注册');

        try {
            $registry->syncAddon('shop', $this->definition('商城标题'));
        } finally {
            self::assertSame(0, NotificationScene::query()->where('source_code', 'shop')->count());
        }
    }

    /**
     * @dataProvider invalidDefinitionProvider
     */
    public function test_sync_rejects_invalid_protocol_definitions(callable $mutate, string $message): void
    {
        $this->migratePackageTables();
        $definition = $this->definition('文章 {{ archive_title }} 待审核');
        $mutate($definition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        app(NotificationSceneRegistry::class)->syncAddon('cms', $definition);
    }

    public function invalidDefinitionProvider(): array
    {
        return [
            'unknown placeholder' => [static function (array &$definition): void {
                $definition['scenes'][0]['templates'][0]['content'] = '{{ missing_name }}';
            }, '引用了未声明变量 [missing_name]'],
            'invalid variable mapping' => [static function (array &$definition): void {
                $definition['scenes'][0]['templates'][1]['variable_map']['thing1'] = 'missing_name';
            }, 'variable_map 引用了无效变量'],
            'invalid variable type' => [static function (array &$definition): void {
                $definition['scenes'][0]['variables'][0]['type'] = 'unknown';
            }, '变量 [archive_title] 类型无效'],
            'invalid required flag' => [static function (array &$definition): void {
                $definition['scenes'][0]['variables'][0]['required'] = 1;
            }, 'required 必须是布尔值'],
            'invalid secret flag' => [static function (array &$definition): void {
                $definition['scenes'][0]['variables'][0]['secret'] = 1;
            }, 'secret 必须是布尔值'],
            'invalid rules' => [static function (array &$definition): void {
                $definition['scenes'][0]['variables'][0]['rules'] = ['max:100', 1];
            }, 'rules 必须是字符串数组'],
            'unknown group' => [static function (array &$definition): void {
                $definition['scenes'][0]['group'] = 'unknown_group';
            }, '引用了未注册分组 [unknown_group]'],
        ];
    }

    public function test_sync_accepts_addon_defined_scene_group(): void
    {
        $this->migratePackageTables();
        $definition = $this->definition('测试通知');
        $definition['groups'] = [[
            'code' => 'testing',
            'title' => '协议测试',
        ]];
        $definition['scenes'][0]['group'] = 'testing';

        app(NotificationSceneRegistry::class)->syncAddon('cms', $definition);

        self::assertDatabaseHas('notification_scenes', [
            'code' => 'cms.archive.pending',
            'group_code' => 'testing',
            'group_title' => '协议测试',
        ]);
    }

    public function test_sync_rejects_in_place_template_mode_changes(): void
    {
        $this->migratePackageTables();
        $registry = app(NotificationSceneRegistry::class);
        $registry->syncAddon('cms', $this->definition('初始标题'));
        $definition = $this->definition('升级标题');
        $definition['scenes'][0]['templates'][0] = [
            'channel' => NotificationChannel::SITE,
            'locale' => 'zh-CN',
            'mode' => NotificationTemplateMode::REFERENCE,
            'template_key' => 'site-template',
            'variable_map' => [],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('模板模式不能原地修改');
        $registry->syncAddon('cms', $definition);
    }

    private function definition(string $subject): array
    {
        return [
            'schema_version' => 1,
            'scenes' => [[
                'code' => 'cms.archive.pending',
                'title' => '文章待审核',
                'description' => 'CMS 文章进入待审核状态后通知管理员。',
                'group' => NotificationSceneGroup::CONTENT,
                'purpose' => NotificationPurpose::TRANSACTIONAL,
                'default_channels' => [NotificationChannel::SITE],
                'variables' => [[
                    'name' => 'archive_title',
                    'label' => '文章标题',
                    'type' => NotificationVariableType::STRING,
                    'required' => true,
                    'rules' => ['max:255'],
                ], [
                    'name' => 'author_name',
                    'label' => '作者名称',
                    'type' => NotificationVariableType::STRING,
                    'required' => false,
                    'default' => '未知作者',
                ]],
                'templates' => [[
                    'channel' => NotificationChannel::SITE,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::CONTENT,
                    'format' => NotificationTemplateFormat::TEXT,
                    'subject' => $subject,
                    'content' => '作者：{{ author_name }}',
                ], [
                    'channel' => NotificationChannel::WECHAT_MINI_PROGRAM,
                    'locale' => 'zh-CN',
                    'mode' => NotificationTemplateMode::REFERENCE,
                    'template_key' => 'archive-pending-template',
                    'variable_map' => ['thing1' => 'archive_title'],
                ]],
            ]],
        ];
    }
}
