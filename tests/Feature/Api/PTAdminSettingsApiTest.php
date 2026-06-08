<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\AddonManager;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSettingsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
        Addon::swap(new AddonManager());

        parent::tearDown();
    }

    public function test_settings_index_returns_group_type_and_grouped_results(): void
    {
        $this->seedSystemSettingFixtures();
        SystemConfigGroup::query()->create([
            'title' => '支付设置',
            'name' => 'payment',
            'type' => 'pay',
            'access' => 'private',
            'sort' => 90,
            'status' => 1,
            'is_system' => 1,
        ]);

        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/settings');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.group_type.0.value', 'system')
            ->assertJsonPath('data.results.0.type', 'system')
            ->assertJsonPath('data.results.0.title', '系统设置')
            ->assertJsonPath('data.results.0.items.0.name', 'basic')
            ->assertJsonPath('data.results.1.type', 'pay')
            ->assertJsonPath('data.results.1.items.0.name', 'payment');
    }

    public function test_system_section_detail_returns_schema_fields_and_runtime_values(): void
    {
        [, $section] = $this->seedSystemSettingFixtures();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/settings/'.$section->name);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.schema.schema.name', 'basic')
            ->assertJsonPath('data.schema.schema.title', '基础配置')
            ->assertJsonPath('data.schema.schema.fields.0.name', 'site_title')
            ->assertJsonPath('data.schema.schema.fields.0.type', 'text')
            ->assertJsonPath('data.schema.schema.fields.1.type', 'switch')
            ->assertJsonPath('data.fields.0.name', 'site_title')
            ->assertJsonPath('data.fields.1.name', 'login_captcha');

        $payload = $response->json('data.schema');
        self::assertSame('PTAdmin', $payload['values']['site_title'] ?? null);
        self::assertSame(1, $payload['values']['login_captcha'] ?? null);
    }

    public function test_system_setting_config_save_updates_database_and_public_cache(): void
    {
        $this->seedSystemSettingFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/setting-config/basic', [
                'site_title' => 'PTAdmin Next',
                'login_captcha' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('code', 0);

        self::assertSame('PTAdmin Next', SystemConfig::query()->where('name', 'site_title')->value('value'));
        self::assertSame('0', SystemConfig::query()->where('name', 'login_captcha')->value('value'));
        self::assertSame('PTAdmin Next', SystemConfigService::public()['basic.site_title'] ?? null);
        self::assertSame(0, SystemConfigService::value('basic.login_captcha'));
    }

    public function test_addon_config_endpoint_returns_empty_when_plugin_has_no_initialized_group(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonManifest('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/addons/cms/config')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [],
            ]);
    }

    public function test_addon_config_endpoint_reads_and_saves_initialized_group_via_common_flow(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonManifest('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ]);

        $group = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'type' => 'addon',
            'access' => 'private',
            'sort' => 100,
            'addon_code' => 'cms',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'title',
            'system_config_group_id' => $group->id,
            'sort' => 100,
            'type' => 'text',
            'value' => 'CMS Demo',
            'default_val' => 'CMS Demo',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '启用状态',
            'name' => 'enabled',
            'system_config_group_id' => $group->id,
            'sort' => 90,
            'type' => 'switch',
            'value' => '1',
            'default_val' => '0',
            'status' => 1,
            'is_system' => 1,
        ]);

        Addon::swap(new AddonManager());

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/addons/cms/config');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.schema.name', 'basic')
            ->assertJsonPath('data.schema.fields.0.name', 'title')
            ->assertJsonPath('data.schema.fields.1.type', 'switch');

        self::assertSame('CMS Demo', $response->json('data.values.title'));
        self::assertSame(1, $response->json('data.values.enabled'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/ptadmin/addons/cms/config', [
                'values' => [
                    'title' => 'CMS Updated',
                    'enabled' => 0,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 0);

        self::assertSame('CMS Updated', SystemConfig::query()->where('system_config_group_id', $group->id)->where('name', 'title')->value('value'));
        self::assertSame('0', SystemConfig::query()->where('system_config_group_id', $group->id)->where('name', 'enabled')->value('value'));
        self::assertSame('CMS Updated', SystemConfigService::addonValue('cms', 'basic.title'));
        self::assertSame(0, SystemConfigService::addonValue('cms', 'basic.enabled'));
    }

    public function test_addon_config_save_rejects_when_plugin_has_no_initialized_group(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonManifest('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ]);

        Addon::swap(new AddonManager());

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/ptadmin/addons/cms/config', [
                'values' => [
                    'title' => 'CMS Updated',
                ],
            ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', '插件[cms]未提供通用配置');
    }

    /**
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup}
     */
    private function seedSystemSettingFixtures(): array
    {
        $this->migratePackageTables();

        $group = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'type' => 'system',
            'access' => 'public',
            'sort' => 100,
            'intro' => '站点基础配置',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'site_title',
            'system_config_group_id' => $group->id,
            'sort' => 100,
            'type' => 'text',
            'extra' => [
                'meta' => [
                    'placeholder' => '请输入站点标题',
                ],
            ],
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '登录验证码',
            'name' => 'login_captcha',
            'system_config_group_id' => $group->id,
            'sort' => 90,
            'type' => 'switch',
            'value' => '1',
            'default_val' => '0',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfigService::updateSystemConfigCache();

        return [$group->refresh(), $group->refresh()];
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function writeAddonManifest(string $basePath, array $manifest): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory);
        File::put($directory.'/manifest.json', (string) json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminAccount([
            'username' => 'settings-founder',
            'nickname' => 'Settings Founder',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
