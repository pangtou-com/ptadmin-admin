<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PTAdmin\Admin\Models\Asset;
use PTAdmin\Admin\Services\UploadService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminUploadApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('systemConfig');
    }

    public function test_upload_endpoints_can_store_files_deduplicate_and_return_tiny_response(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $uploadResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'docs',
                'file' => UploadedFile::fake()->createWithContent('manual.txt', 'same-file-content'),
            ]);

        $uploadResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'title' => 'manual.txt',
                'groups' => 'docs',
                'suffix' => 'txt',
            ],
        ]);

        $firstId = (int) $uploadResponse->json('data.id');
        $firstPath = (string) Asset::query()->findOrFail($firstId)->path;
        Storage::disk('public')->assertExists($firstPath);
        self::assertSame(1, Asset::query()->count());

        $duplicateResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'docs',
                'file' => UploadedFile::fake()->createWithContent('copy.txt', 'same-file-content'),
            ]);

        $duplicateResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertSame($firstId, (int) $duplicateResponse->json('data.id'));
        self::assertSame(1, Asset::query()->count());

        $tinyResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload/tiny', [
                'group' => 'editor',
                'file' => UploadedFile::fake()->createWithContent('editor.txt', 'tiny-content'),
            ]);

        $tinyResponse->assertOk();
        self::assertStringContainsString('/storage/', (string) $tinyResponse->json('location'));
        self::assertSame(2, Asset::query()->count());
    }

    public function test_upload_endpoint_returns_validation_error_for_missing_file(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/upload', [
                'group' => 'docs',
            ])
            ->assertStatus(200)
            ->assertJson([
                'code' => 20000,
            ]);
    }

    public function test_upload_endpoint_requires_login(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();

        $this->withHeaders($this->jsonApiHeaders())
            ->post('/system/upload', [
                'group' => 'docs',
                'file' => UploadedFile::fake()->createWithContent('manual.txt', 'same-file-content'),
            ])
            ->assertOk()
            ->assertJson([
                'code' => 10001,
                'message' => '未登录',
            ]);
    }

    public function test_upload_endpoint_can_switch_to_addon_storage_by_system_config(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        Cache::forever('systemConfig', [
            'system' => [
                'upload' => [
                    'storage_driver' => 'oss_storage',
                    'storage_disk' => 'oss',
                ],
            ],
            '__group_names__' => [
                'system' => 'system',
                'upload' => 'system',
            ],
        ]);

        $this->app->instance(UploadService::class, new class() extends UploadService {
            protected function executeAddonStorageUpload(array $storageTarget, array $payload): array
            {
                return [
                    'disk' => $payload['disk'],
                    'path' => 'remote/'.$payload['path'],
                    'url' => 'https://oss.example.test/'.ltrim((string) $payload['path'], '/'),
                ];
            }
        });

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'images',
                'file' => UploadedFile::fake()->createWithContent('banner.png', 'oss-content'),
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'driver' => 'addon:oss_storage:oss',
            ],
        ]);

        self::assertStringStartsWith('remote/images/', (string) $response->json('data.path'));
        self::assertStringStartsWith('https://oss.example.test/images/', (string) $response->json('data.url'));

        $asset = Asset::query()->firstOrFail();
        self::assertSame('addon:oss_storage:oss', $asset->driver);
        self::assertStringStartsWith('remote/images/', (string) $asset->path);
    }

    public function test_asset_endpoints_require_login_and_can_list_picker_and_delete(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/assets')
            ->assertOk()
            ->assertJson([
                'code' => 10001,
                'message' => '未登录',
            ]);

        Storage::disk('public')->put('public/docs/manual.txt', 'manual');
        Storage::disk('public')->put('public/images/banner.txt', 'banner');

        $document = Asset::query()->create([
            'title' => '使用手册',
            'md5' => md5('manual'),
            'mime' => 'text/plain',
            'suffix' => 'txt',
            'driver' => 'public',
            'size' => 6,
            'path' => 'public/docs/manual.txt',
            'groups' => 'docs',
        ]);

        Asset::query()->create([
            'title' => '站点横幅',
            'md5' => md5('banner'),
            'mime' => 'text/plain',
            'suffix' => 'txt',
            'driver' => 'public',
            'size' => 6,
            'path' => 'public/images/banner.txt',
            'groups' => 'images',
        ]);

        $token = $this->issueFounderToken();

        $listResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/assets?groups=docs');

        $listResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        self::assertSame('使用手册', $listResponse->json('data.results.0.title'));
        self::assertSame('docs', $listResponse->json('data.results.0.groups'));

        $pickerResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/assets/picker?groups=docs');

        $pickerResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/assets/'.$document->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        Storage::disk('public')->assertMissing('public/docs/manual.txt');
        self::assertNull(Asset::query()->find($document->id));
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminSystem([
            'username' => 'founder_upload',
            'nickname' => 'Founder Upload',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
