<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\CacheClearService;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminCacheClearApiTest extends TestCase
{
    public function test_clear_cache_endpoint_refreshes_system_cache_and_forgets_admin_resource_meta(): void
    {
        $this->migratePackageTables();

        $commands = [];
        $this->app->bind(CacheClearService::class, static function () use (&$commands): CacheClearService {
            return new CacheClearService(static function (string $command) use (&$commands): int {
                $commands[] = $command;

                return 0;
            });
        });

        $group = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'type' => 'system',
            'access' => 'public',
            'sort' => 100,
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'site_title',
            'system_config_group_id' => $group->id,
            'sort' => 100,
            'type' => 'text',
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
            'status' => 1,
            'is_system' => 1,
        ]);

        SystemConfigService::updateSystemConfigCache();
        SystemConfig::query()->where('name', 'site_title')->update(['value' => 'PTAdmin Updated']);
        Cache::forever(AdminResource::AUDIT_META_CACHE_KEY, ['system.config' => ['title' => 'old']]);

        $token = $this->issueAdminToken($this->createAdminAccount([
            'username' => 'cache-founder',
            'nickname' => 'Cache Founder',
            'is_founder' => 1,
        ]));

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/ptadmin/cache/clear');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.items.0.code', 'laravel.cache_store')
            ->assertJsonPath('data.items.0.status', 'cleared')
            ->assertJsonPath('data.items.0.command', 'cache:clear')
            ->assertJsonPath('data.items.7.code', 'system.config')
            ->assertJsonPath('data.items.7.status', 'cleared')
            ->assertJsonPath('data.items.9.code', 'addon.cache_hooks')
            ->assertJsonPath('data.items.9.status', 'skipped');

        self::assertSame([
            'cache:clear',
            'config:clear',
            'event:clear',
            'route:clear',
            'view:clear',
            'permission:cache-reset',
            'addon:cache-clear',
        ], $commands);

        self::assertFalse(Cache::has(AdminResource::AUDIT_META_CACHE_KEY));
        self::assertSame('PTAdmin Updated', SystemConfigService::value('basic.site_title'));
    }
}
