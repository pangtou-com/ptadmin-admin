<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PTAdmin\Admin\Models\Asset;
use PTAdmin\Admin\Services\AssetService;
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
        $this->createAdminsTable();
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
        self::assertStringStartsWith(url('/storage/'), (string) $uploadResponse->json('data.url'));

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

        $imageResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'images',
                'file' => UploadedFile::fake()->image('banner.png'),
            ]);

        $imageResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'title' => 'banner.png',
                'groups' => 'images',
                'suffix' => 'png',
            ],
        ]);
        self::assertStringStartsWith(url('/storage/'), (string) $imageResponse->json('data.url'));
        self::assertStringStartsWith(url('/storage/'), (string) $imageResponse->json('data.preview'));

        $tinyResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload/tiny', [
                'group' => 'editor',
                'file' => UploadedFile::fake()->createWithContent('editor.txt', 'tiny-content'),
            ]);

        $tinyResponse->assertOk();
        self::assertStringStartsWith(url('/storage/'), (string) $tinyResponse->json('location'));
        self::assertSame(3, Asset::query()->count());
    }

    public function test_upload_endpoint_returns_validation_error_for_missing_file(): void
    {
        $this->createAdminsTable();
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
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();

        $this->withHeaders($this->jsonApiHeaders())
            ->post('/system/upload', [
                'group' => 'docs',
                'file' => UploadedFile::fake()->createWithContent('manual.txt', 'same-file-content'),
            ])
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_remote_asset_endpoint_can_store_remote_url_without_downloading(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $url = 'https://www.pangtou.com/storage/default/20241012/UEjSEcVq108t1feZSFZaUzsw0M54p6KBRnnS3XC4.png';

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/asset/remote', [
                'url' => $url,
                'is_local_save' => false,
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'title' => 'UEjSEcVq108t1feZSFZaUzsw0M54p6KBRnnS3XC4.png',
                'driver' => 'remote',
                'path' => $url,
                'url' => $url,
                'preview' => $url,
                'groups' => 'default',
                'suffix' => 'png',
                'mime' => 'image/png',
            ],
        ]);

        self::assertSame(1, Asset::query()->count());
        self::assertSame($url, (string) Asset::query()->firstOrFail()->path);
    }

    public function test_remote_asset_endpoint_can_download_remote_url_to_local_storage(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $url = 'https://www.pangtou.com/storage/default/20241012/banner.png';
        $this->app->instance(AssetService::class, new class() extends AssetService {
            protected function fetchRemoteContent(string $url): array
            {
                return [
                    'content' => 'remote-image-content',
                    'content_type' => 'image/png',
                ];
            }
        });

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/asset/remote', [
                'url' => $url,
                'is_local_save' => true,
                'group' => 'remote',
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'title' => 'banner.png',
                'driver' => 'public',
                'groups' => 'remote',
                'suffix' => 'png',
                'mime' => 'image/png',
            ],
        ]);

        $asset = Asset::query()->findOrFail((int) $response->json('data.id'));
        Storage::disk('public')->assertExists((string) $asset->path);
        self::assertStringStartsWith('remote/', (string) $asset->path);
        self::assertStringStartsWith(url('/storage/'), (string) $response->json('data.url'));
        self::assertSame($response->json('data.url'), $response->json('data.preview'));
    }

    public function test_upload_endpoint_can_switch_to_addon_storage_by_system_config(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        Cache::forever('systemConfig', [
            '__sections__' => [
                'upload' => [
                    'storage_driver' => 'oss_storage',
                    'storage_disk' => 'oss',
                ],
            ],
            '__fields__' => [
                'upload.storage_driver' => 'oss_storage',
                'upload.storage_disk' => 'oss',
            ],
            '__public_fields__' => [],
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

    public function test_upload_endpoint_uses_public_disk_even_when_default_filesystem_is_local(): void
    {
        config()->set('filesystems.default', 'local');
        Storage::fake('local');

        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'docs',
                'file' => UploadedFile::fake()->createWithContent('manual.txt', 'public-only-content'),
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'driver' => 'public',
                'groups' => 'docs',
                'title' => 'manual.txt',
            ],
        ]);

        $asset = Asset::query()->findOrFail((int) $response->json('data.id'));

        Storage::disk('public')->assertExists((string) $asset->path);
        Storage::disk('local')->assertMissing((string) $asset->path);
        self::assertStringStartsWith(url('/storage/'), (string) $response->json('data.url'));
    }

    public function test_image_upload_preview_returns_original_image_url_when_default_filesystem_is_local(): void
    {
        config()->set('filesystems.default', 'local');
        Storage::fake('local');

        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->post('/system/upload', [
                'group' => 'images',
                'file' => UploadedFile::fake()->image('banner.png'),
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'driver' => 'public',
                'groups' => 'images',
                'title' => 'banner.png',
                'suffix' => 'png',
            ],
        ]);

        $asset = Asset::query()->findOrFail((int) $response->json('data.id'));
        Storage::disk('public')->assertExists((string) $asset->path);
        Storage::disk('local')->assertMissing((string) $asset->path);
        self::assertSame($response->json('data.url'), $response->json('data.preview'));
        self::assertStringStartsWith(url('/storage/'), (string) $response->json('data.preview'));
    }

    public function test_asset_endpoints_require_login_and_can_list_picker_and_delete(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/assets')
            ->assertOk()
            ->assertJson([
                'code' => 419,
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
        self::assertSame('', $listResponse->json('data.results.0.preview'));

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

    public function test_asset_listing_returns_original_image_url(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createAssetsTable();

        Storage::disk('public')->putFileAs('images', UploadedFile::fake()->image('banner.png'), 'banner.png');

        Asset::query()->create([
            'title' => 'banner.png',
            'md5' => md5('banner'),
            'mime' => 'image/png',
            'suffix' => 'png',
            'driver' => 'public',
            'size' => 6,
            'path' => 'images/banner.png',
            'groups' => 'images',
        ]);

        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/assets?groups=images');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        self::assertSame(url('/storage/images/banner.png'), $response->json('data.results.0.preview'));
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminAccount([
            'username' => 'founder_upload',
            'nickname' => 'Founder Upload',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
