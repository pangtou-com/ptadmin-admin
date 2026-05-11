<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Services\AddonFrontendService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminAddonFrontendServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        File::deleteDirectory(base_path('addons'));
        File::deleteDirectory(base_path('resources/ptadmin'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Addon::swap(new FakeAddonFrontendServiceManager([]));
        File::deleteDirectory(base_path('addons'));
        File::deleteDirectory(base_path('resources/ptadmin'));

        parent::tearDown();
    }

    public function test_manifests_reads_frontend_manifest_from_frontend_directory_for_develop_addon(): void
    {
        $this->writeManifest('Test', [
            'id' => 'test',
            'code' => 'test',
            'name' => 'Test',
            'version' => '0.1.2',
            'enabled' => true,
            'kind' => 'module',
            'runtime' => 'federation',
            'routeBase' => '/test',
            'meta' => [
                'icon' => 'Grid',
                'description' => '测试插件',
                'order' => 100,
                'develop' => true,
                'preload' => false,
            ],
            'entry' => [
                'federation' => [
                    'remote' => 'test_remote',
                    'entry' => 'http://localhost:4179/assets/remoteEntry.js',
                    'expose' => './module',
                ],
            ],
            'capabilities' => [
                'routes' => true,
                'pages' => true,
                'widgets' => false,
                'settings' => false,
            ],
        ], 'Frontend/frontend.json');

        Addon::swap(new FakeAddonFrontendServiceManager([
            'test' => [
                'code' => 'test',
                'title' => 'Test',
                'version' => '1.0.0',
                'develop' => true,
                'base_path' => 'Test',
            ],
        ]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertSame('test', $results[0]['code']);
        self::assertSame('federation', $results[0]['runtime']);
        self::assertSame('/test', $results[0]['routeBase']);
        self::assertSame('http://localhost:4179/assets/remoteEntry.js', data_get($results[0], 'entry.federation.entry'));
    }

    public function test_manifests_reads_root_frontend_manifest_for_deploy_addon(): void
    {
        $this->writeManifest('Test', [
            'id' => 'test',
            'code' => 'test',
            'name' => 'Test',
            'version' => '0.1.2',
            'enabled' => true,
            'kind' => 'micro-app',
            'runtime' => 'wujie',
            'routeBase' => '/test',
            'meta' => [
                'icon' => 'Monitor',
                'description' => '测试微应用',
                'order' => 90,
                'develop' => false,
                'preload' => false,
            ],
            'entry' => [
                'wujie' => [
                    'name' => 'test_micro',
                    'url' => 'https://demo.example.com/admin/modules/test/dist/',
                    'alive' => true,
                    'sync' => true,
                    'degrade' => false,
                ],
            ],
            'capabilities' => [
                'routes' => false,
                'pages' => false,
                'widgets' => false,
                'settings' => false,
            ],
        ]);

        Addon::swap(new FakeAddonFrontendServiceManager([
            'test' => [
                'code' => 'test',
                'title' => 'Test',
                'version' => '1.0.0',
                'develop' => false,
                'base_path' => 'Test',
            ],
        ]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertSame('wujie', $results[0]['runtime']);
        self::assertSame('/test', $results[0]['routeBase']);
        self::assertSame('https://demo.example.com/admin/modules/test/dist/', data_get($results[0], 'entry.wujie.url'));
    }

    public function test_deploy_federation_manifest_rewrites_local_entry_to_public_addon_asset_url(): void
    {
        config()->set('app.url', 'https://demo.example.com');

        $this->writeManifest('Test', [
            'id' => 'test',
            'code' => 'test',
            'name' => 'Test',
            'version' => '0.1.2',
            'enabled' => true,
            'kind' => 'module',
            'runtime' => 'federation',
            'routeBase' => '/test',
            'entry' => [
                'federation' => [
                    'remote' => 'test_remote',
                    'entry' => 'http://localhost:4179/assets/remoteEntry.js',
                    'expose' => './module',
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendServiceManager([
            'test' => [
                'code' => 'test',
                'title' => 'Test',
                'version' => '1.0.0',
                'develop' => false,
                'base_path' => 'Test',
            ],
        ]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertSame('https://demo.example.com/admin/modules/test/dist/assets/remoteEntry.js', data_get($results[0], 'entry.federation.entry'));
    }

    public function test_deploy_addon_ignores_frontend_manifest_develop_flag_when_rewriting_local_entry(): void
    {
        config()->set('app.url', 'https://demo.example.com');

        $this->writeManifest('Test', [
            'id' => 'test',
            'code' => 'test',
            'name' => 'Test',
            'version' => '0.1.2',
            'enabled' => true,
            'kind' => 'module',
            'runtime' => 'federation',
            'routeBase' => '/test',
            'meta' => [
                'develop' => true,
            ],
            'entry' => [
                'federation' => [
                    'remote' => 'test_remote',
                    'entry' => 'http://localhost:4179/assets/remoteEntry.js',
                    'expose' => './module',
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendServiceManager([
            'test' => [
                'code' => 'test',
                'title' => 'Test',
                'version' => '1.0.0',
                'develop' => false,
                'base_path' => 'Test',
            ],
        ]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertFalse((bool) data_get($results[0], 'meta.develop'));
        self::assertSame('https://demo.example.com/admin/modules/test/dist/assets/remoteEntry.js', data_get($results[0], 'entry.federation.entry'));
    }

    public function test_manifests_include_project_frontend_manifest_as_reserved_app_module(): void
    {
        config()->set('app.url', 'https://demo.example.com');
        config()->set('app.debug', false);
        config()->set('app.name', 'Demo App');

        $this->writeProjectManifest([
            'name' => '项目二开',
            'version' => '1.2.3',
            'enabled' => true,
            'runtime' => 'wujie',
            'routeBase' => '/',
            'meta' => [
                'icon' => 'Grid',
                'order' => 5,
                'preload' => false,
            ],
            'entry' => [
                'wujie' => [
                    'name' => 'ptadmin_project_app',
                    'url' => 'http://localhost:4180/',
                    'alive' => true,
                    'sync' => true,
                    'degrade' => false,
                ],
            ],
            'capabilities' => [
                'pages' => true,
            ],
        ]);

        Addon::swap(new FakeAddonFrontendServiceManager([]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertSame('__app__', $results[0]['code']);
        self::assertSame('__app__', $results[0]['key']);
        self::assertSame('project-app', $results[0]['kind']);
        self::assertSame('项目二开', $results[0]['name']);
        self::assertSame('1.2.3', $results[0]['version']);
        self::assertSame('wujie', $results[0]['runtime']);
        self::assertSame('/', $results[0]['routeBase']);
        self::assertSame('https://demo.example.com/admin/modules/__app__/dist/', data_get($results[0], 'entry.wujie.url'));
        self::assertFalse((bool) data_get($results[0], 'meta.develop'));
    }

    public function test_project_frontend_manifest_can_use_dev_url_in_debug_mode(): void
    {
        config()->set('app.debug', true);
        config()->set('ptadmin-auth.project_frontend_dev_url', 'http://localhost:4180/');

        $this->writeProjectManifest([
            'title' => '项目二开',
            'enabled' => true,
            'runtime' => 'wujie',
            'entry' => [
                'wujie' => [
                    'name' => 'ptadmin_project_app',
                    'alive' => true,
                    'sync' => true,
                ],
            ],
        ]);

        Addon::swap(new FakeAddonFrontendServiceManager([]));

        $results = app(AddonFrontendService::class)->manifests();

        self::assertCount(1, $results);
        self::assertSame('__app__', $results[0]['code']);
        self::assertSame('项目二开', $results[0]['name']);
        self::assertSame('http://localhost:4180/', data_get($results[0], 'entry.wujie.url'));
        self::assertTrue((bool) data_get($results[0], 'meta.develop'));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeManifest(string $basePath, array $payload, string $relativePath = 'frontend.json'): void
    {
        $targetPath = base_path('addons/'.$basePath.'/'.ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeProjectManifest(array $payload): void
    {
        $targetPath = base_path('resources/ptadmin/frontend/frontend.json');

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

final class FakeAddonFrontendServiceManager
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
        $results = [];

        foreach ($this->addons as $addonCode => $addonInfo) {
            $results[$addonCode] = [
                'addons' => $addonInfo,
                'providers' => [],
                'response' => [],
                'inject' => [],
                'directives' => [],
                'hooks' => [],
            ];
        }

        return $results;
    }
}
