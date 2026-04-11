<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Services\AddonFrontendService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAddonFrontendApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        File::deleteDirectory(base_path('addons'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Addon::swap(new FakeAddonFrontendManager([]));
        File::deleteDirectory(base_path('addons'));

        parent::tearDown();
    }

    public function test_module_manifests_endpoint_is_public_and_returns_empty_results_by_default(): void
    {
        Addon::swap(new FakeAddonFrontendManager([]));

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/modules')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'results' => [],
                ],
            ]);
    }

    public function test_module_manifests_endpoint_returns_normalized_manifest_payload(): void
    {
        $this->writeAddonModuleManifest('Cms', [
            'modules' => [
                [
                    'key' => 'cms',
                    'title' => '内容管理',
                    'description' => '内容管理与站点资源维护模块。',
                    'version' => '0.1.0',
                    'enabled' => 1,
                    'runtime' => 'local',
                    'route_base' => '/cms',
                    'meta' => [
                        'icon' => 'Document',
                        'order' => 40,
                        'preload' => false,
                        'develop' => true,
                    ],
                    'entry' => [
                        'local' => [
                            'type' => 'module',
                            'js' => 'dist/admin/index.js',
                            'css' => ['dist/admin/index.css'],
                        ],
                    ],
                    'pages' => [
                        [
                            'key' => 'cms.article',
                            'path' => '/cms/article',
                            'route_name' => 'cms-article',
                            'title' => '文章管理',
                            'keep_alive' => true,
                        ],
                        [
                            'key' => 'cms.category',
                            'path' => '/cms/category',
                            'route_name' => 'cms-category',
                            'title' => '栏目分类',
                            'keep_alive' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $this->writeAddonModuleManifest('Workspace', [
            'modules' => [
                [
                    'key' => 'workspace',
                    'title' => '生态工作台',
                    'description' => '用于演示无界子应用接入的生态工作台示例。',
                    'version' => '0.1.0',
                    'enabled' => 1,
                    'runtime' => 'wujie',
                    'route_base' => '/workspace',
                    'meta' => [
                        'icon' => 'Monitor',
                        'order' => 50,
                        'preload' => false,
                        'develop' => true,
                    ],
                    'entry' => [
                        'wujie' => [
                            'name' => 'pangtou_workspace_micro',
                            'url' => 'http://localhost:5181/',
                            'alive' => true,
                            'sync' => true,
                            'degrade' => false,
                        ],
                    ],
                    'pages' => [
                        [
                            'key' => 'workspace.home',
                            'path' => '/workspace',
                            'route_name' => 'workspace-home',
                            'title' => '生态工作台',
                        ],
                    ],
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendManager([
            'cms' => [
                'code' => 'cms',
                'title' => '内容管理',
                'description' => '内容管理与站点资源维护模块。',
                'version' => '0.1.0',
                'base_path' => 'Cms',
            ],
            'workspace' => [
                'code' => 'workspace',
                'title' => '生态工作台',
                'description' => '用于演示无界子应用接入的生态工作台示例。',
                'version' => '0.1.0',
                'base_path' => 'Workspace',
            ],
        ]));

        $response = $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/modules');

        $response->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'results' => [
                        [
                            'key' => 'cms',
                            'title' => '内容管理',
                            'description' => '内容管理与站点资源维护模块。',
                            'version' => '0.1.0',
                            'enabled' => 1,
                            'runtime' => 'local',
                            'route_base' => '/cms',
                            'meta' => [
                                'icon' => 'Document',
                                'order' => 40,
                                'preload' => false,
                                'develop' => true,
                            ],
                            'entry' => [
                                'local' => [
                                    'type' => 'module',
                                    'js' => '/addons/cms/dist/admin/index.js',
                                    'css' => ['/addons/cms/dist/admin/index.css'],
                                ],
                            ],
                            'pages' => [
                                [
                                    'key' => 'cms.article',
                                    'path' => '/cms/article',
                                    'route_name' => 'cms-article',
                                    'title' => '文章管理',
                                    'keep_alive' => true,
                                ],
                            ],
                        ],
                        [
                            'key' => 'workspace',
                            'title' => '生态工作台',
                            'runtime' => 'wujie',
                            'route_base' => '/workspace',
                            'entry' => [
                                'wujie' => [
                                    'name' => 'pangtou_workspace_micro',
                                    'url' => 'http://localhost:5181/',
                                    'alive' => true,
                                    'sync' => true,
                                    'degrade' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        self::assertCount(2, (array) $response->json('data.results'));
        self::assertSame('cms.category', $response->json('data.results.0.pages.1.key'));
        self::assertSame('workspace.home', $response->json('data.results.1.pages.0.key'));
    }

    public function test_module_manifests_endpoint_uses_cache_when_cache_key_is_stable(): void
    {
        config()->set('ptadmin-auth.module_manifest_cache_ttl', 300);

        $this->app->instance(AddonFrontendService::class, new class() extends AddonFrontendService {
            protected function buildManifestFingerprint(): string
            {
                return 'fixed-manifest-key';
            }
        });

        $this->writeAddonModuleManifest('Cms', [
            'modules' => [
                [
                    'key' => 'cms',
                    'title' => '内容管理',
                    'enabled' => 1,
                    'runtime' => 'local',
                    'route_base' => '/cms',
                    'pages' => [
                        [
                            'key' => 'cms.article',
                            'path' => '/cms/article',
                            'route_name' => 'cms-article',
                            'title' => '文章管理',
                        ],
                    ],
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendManager([
            'cms' => [
                'code' => 'cms',
                'title' => '内容管理',
                'version' => '0.1.0',
                'base_path' => 'Cms',
            ],
        ]));

        $first = $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/modules');

        $first->assertOk();
        self::assertSame('内容管理', $first->json('data.results.0.title'));

        $this->writeAddonModuleManifest('Cms', [
            'modules' => [
                [
                    'key' => 'cms',
                    'title' => '内容管理-已变化',
                    'enabled' => 1,
                    'runtime' => 'local',
                    'route_base' => '/cms',
                    'pages' => [
                        [
                            'key' => 'cms.article',
                            'path' => '/cms/article',
                            'route_name' => 'cms-article',
                            'title' => '文章管理-已变化',
                        ],
                    ],
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendManager([
            'cms' => [
                'code' => 'cms',
                'title' => '内容管理-已变化',
                'version' => '9.9.9',
                'base_path' => 'Cms',
            ],
        ]));

        $second = $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/modules');

        $second->assertOk();
        self::assertSame('内容管理', $second->json('data.results.0.title'));
        self::assertSame('文章管理', $second->json('data.results.0.pages.0.title'));
    }

    public function test_module_manifests_endpoint_skips_addons_without_module_file_or_without_pages(): void
    {
        File::ensureDirectoryExists(base_path('addons/NoPages'));
        File::put(base_path('addons/NoPages/modules.json'), (string) json_encode([
            'modules' => [
                [
                    'key' => 'no-pages',
                    'title' => '无页面模块',
                    'enabled' => 1,
                    'runtime' => 'local',
                    'route_base' => '/no-pages',
                    'pages' => [],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        Addon::swap(new FakeAddonFrontendManager([
            'empty' => [
                'code' => 'empty',
                'title' => '空插件',
                'version' => '1.0.0',
                'base_path' => 'Empty',
            ],
            'no-pages' => [
                'code' => 'no-pages',
                'title' => '无页面模块',
                'version' => '1.0.0',
                'base_path' => 'NoPages',
            ],
        ]));

        $response = $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/modules');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'results' => [],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeAddonModuleManifest(string $basePath, array $payload): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory);
        File::put($directory.'/modules.json', (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

final class FakeAddonFrontendManager
{
    /** @var array<string, array<string, mixed>> */
    private array $addons;

    /**
     * @param array<string, array<string, mixed>> $addons
     */
    public function __construct(array $addons)
    {
        $this->addons = $addons;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }

    public function getAddon(string $addonCode): FakeAddonFrontendConfig
    {
        return new FakeAddonFrontendConfig($this->addons[$addonCode] ?? []);
    }
}

final class FakeAddonFrontendConfig
{
    /** @var array<string, mixed> */
    private array $addon;

    /**
     * @param array<string, mixed> $addon
     */
    public function __construct(array $addon)
    {
        $this->addon = $addon;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddons(): array
    {
        return $this->addon;
    }

    public function getAddonPath($path = null): string
    {
        $basePath = (string) ($this->addon['base_path'] ?? '');

        return base_path('addons/'.$basePath.(null !== $path ? '/'.$path : ''));
    }
}
