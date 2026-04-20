<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace PTAdmin\Admin\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Models\Asset;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class UploadService
{
    /**
     * 保存上传文件并返回资源数据。
     *
     * 相同文件内容按 md5 去重，若库中记录存在且物理文件仍在，
     * 则直接复用已有附件记录。
     *
     * @return array<string, mixed>
     */
    public function upload(Request $request): array
    {
        $fieldName = $this->getFilename($request);
        if (!$request->hasFile($fieldName)) {
            throw new BackgroundException(__('ptadmin::background.invalid_file'));
        }

        $file = $request->file($fieldName);
        if (null === $file || !$file->isValid()) {
            throw new BackgroundException(__('ptadmin::background.invalid_file'));
        }

        $data = [
            'md5' => hash_file('md5', $file->getPathname()),
            'title' => $file->getClientOriginalName(),
            'mime' => (string) $file->getMimeType(),
            'suffix' => (string) $file->clientExtension(),
            'size' => (int) $file->getSize(),
            'groups' => $this->getGroup($request),
        ];

        $asset = Asset::byMd5($data['md5']);
        if ($asset) {
            if ($this->assetExists($asset)) {
                return $asset->toArray();
            }

            $asset->delete();
        } else {
            $asset = new Asset();
        }

        $storageTarget = $this->resolveStorageTarget();
        $objectPath = $this->buildObjectPath($request, $data);
        $uploadResult = 'local' === $storageTarget['channel']
            ? $this->storeLocalFile($file, $objectPath)
            : $this->storeAddonFile($storageTarget, $file, $objectPath, $data);

        $data['driver'] = $uploadResult['driver'];
        $data['path'] = $uploadResult['path'];
        $asset->fill($data)->save();

        return $this->buildAssetResponse($asset->fresh(), $uploadResult['url'] ?? null);
    }

    /**
     * 远程资源下载.
     *
     * @param mixed $contentType
     * @param mixed $contentLength
     *
     * @return mixed
     */
    public static function download(string $url, $contentType, $contentLength)
    {
        $suffix = str_replace('image/', '', (string) $contentType);
        $fileContent = file_get_contents($url);
        if (false === $fileContent) {
            return $url;
        }

        $path = (new self())->getPath(request()).\DIRECTORY_SEPARATOR.Str::random(12).time().'.'.$suffix;
        $tempPath = Storage::path($path);

        self::createTemplateDirectory($tempPath);

        return self::getFileUrl($tempPath, $fileContent, $suffix, (string) $contentType, $contentLength);
    }

    /**
     * 创建临时目录。
     */
    public static function createTemplateDirectory(string $tempPath): void
    {
        $dir = \dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * 若文件已存在，直接返回文件链接；不存在则保存并返回链接。
     *
     * @param mixed $contentLength
     *
     * @return mixed
     */
    public static function getFileUrl(string $tempPath, string $fileContent, string $suffix, string $contentType, $contentLength)
    {
        $hashFileName = self::getHashFileName($tempPath, $fileContent);
        $cacheKey = "file_md5_{$hashFileName}";
        $service = new self();

        if (Cache::has($cacheKey)) {
            unlink($tempPath);

            return Cache::get($cacheKey);
        }

        $asset = Asset::byMd5($hashFileName);
        $storagePath = Storage::path('');
        $relativePath = str_replace($storagePath, '', $tempPath);

        if ($asset) {
            if (Storage::exists((string) $asset->path)) {
                unlink($tempPath);

                return $asset->url;
            }

            $asset->delete();
        } else {
            $asset = new Asset();
        }

        $data = [
            'md5' => $hashFileName,
            'title' => $hashFileName,
            'mime' => $contentType,
            'suffix' => $suffix,
            'driver' => $service->getDriver(),
            'groups' => $service->getGroup(request()),
            'path' => $relativePath,
            'size' => $contentLength,
            'url' => $service->toAbsoluteUrl(Storage::url($relativePath)),
        ];

        $asset->fill($data)->save();
        Cache::put($cacheKey, $data['url'], 3600);

        return $data['url'];
    }

    private function getPath(Request $request): string
    {
        return $this->getGroup($request).'/'.date('Ymd');
    }

    /**
     * 写入临时文件并计算 md5。
     */
    private static function getHashFileName(string $tempPath, string $fileContent): string
    {
        $stream = fopen($tempPath, 'w');
        fwrite($stream, $fileContent);
        fclose($stream);

        return hash_file('md5', $tempPath);
    }

    private function getDriver(): string
    {
        return (string) config('ptadmin-auth.upload_local_disk', 'public');
    }

    private function getFilename(Request $request): string
    {
        return (string) $request->get('filename', 'file');
    }

    /**
     * 上传分组。
     */
    private function getGroup(Request $request): string
    {
        $group = trim((string) $request->get('group', 'default'));

        return '' === $group ? 'default' : $group;
    }

    /**
     * 根据系统配置解析实际存储通道。
     *
     * 当前约定：
     * - `upload.storage_driver=local` 使用本地磁盘
     * - `upload.storage_driver=<inject_code>` 使用插件 storage 注入
     * - `upload.storage_code` 可显式指定插件注入编码
     */
    private function resolveStorageTarget(): array
    {
        $driver = strtolower(trim((string) $this->systemConfig('upload.storage_driver', 'local')));
        if ('' === $driver || \in_array($driver, ['local', 'public'], true)) {
            return [
                'channel' => 'local',
                'driver' => $this->getDriver(),
            ];
        }

        $code = trim((string) $this->systemConfig('upload.storage_code', $driver));
        if ('' === $code) {
            throw new BackgroundException(__('ptadmin::background.upload_storage_not_configured'));
        }

        return [
            'channel' => 'addon',
            'code' => $code,
            'disk' => (string) $this->systemConfig('upload.storage_disk', 'oss'),
            'bucket' => $this->systemConfig('upload.storage_bucket'),
            'visibility' => (string) $this->systemConfig('upload.storage_visibility', 'public'),
            'meta' => (array) $this->systemConfig('upload.storage_meta', []),
        ];
    }

    private function buildObjectPath(Request $request, array $data): string
    {
        $directory = $this->getPath($request);
        $extension = trim((string) ($data['suffix'] ?? ''), '.');
        $filename = '' === $extension ? $data['md5'] : $data['md5'].'.'.$extension;

        return trim($directory.'/'.$filename, '/');
    }

    /**
     * @return array{driver:string,path:string,url:string}
     */
    private function storeLocalFile(UploadedFile $file, string $objectPath): array
    {
        $disk = $this->getDriver();
        $path = Storage::disk($disk)->putFileAs(\dirname($objectPath), $file, basename($objectPath));
        if (false === $path) {
            throw new BackgroundException(__('ptadmin::background.file_save_failed'));
        }

        return [
            'driver' => $disk,
            'path' => $path,
            'url' => $this->toAbsoluteUrl(Storage::disk($disk)->url($path)),
        ];
    }

    /**
     * @param array<string, mixed> $storageTarget
     * @param array<string, mixed> $fileData
     *
     * @return array{driver:string,path:string,url:?string}
     */
    private function storeAddonFile(array $storageTarget, UploadedFile $file, string $objectPath, array $fileData): array
    {
        $stream = fopen($file->getPathname(), 'r');

        try {
            $response = $this->executeAddonStorageUpload($storageTarget, [
                'disk' => $storageTarget['disk'] ?? 'oss',
                'bucket' => $storageTarget['bucket'] ?? null,
                'path' => $objectPath,
                'content' => null,
                'stream' => $stream,
                'visibility' => $storageTarget['visibility'] ?? 'public',
                'meta' => array_merge((array) ($storageTarget['meta'] ?? []), [
                    'filename' => $fileData['title'] ?? '',
                    'mime_type' => $fileData['mime'] ?? '',
                    'size' => $fileData['size'] ?? 0,
                    'suffix' => $fileData['suffix'] ?? '',
                    'md5' => $fileData['md5'] ?? '',
                    'group' => $fileData['groups'] ?? 'default',
                ]),
            ]);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'driver' => $this->formatAddonDriver((string) $storageTarget['code'], (string) ($response['disk'] ?? $storageTarget['disk'] ?? 'oss')),
            'path' => (string) ($response['path'] ?? $objectPath),
            'url' => isset($response['url']) ? (string) $response['url'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $storageTarget
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function executeAddonStorageUpload(array $storageTarget, array $payload): array
    {
        return (array) Addon::executeInject('storage', (string) $storageTarget['code'], $payload, 'upload');
    }

    private function assetExists(Asset $asset): bool
    {
        $addonDriver = $this->parseAddonDriver((string) $asset->driver);
        if (null === $addonDriver) {
            return Storage::disk($this->resolveLocalDisk((string) $asset->driver))->exists((string) $asset->path);
        }

        return $this->executeAddonStorageExists($addonDriver, [
            'disk' => $addonDriver['disk'],
            'bucket' => null,
            'path' => (string) $asset->path,
            'meta' => [],
        ]);
    }

    /**
     * @param array{code:string,disk:string} $addonDriver
     * @param array<string, mixed> $payload
     */
    protected function executeAddonStorageExists(array $addonDriver, array $payload): bool
    {
        return (bool) Addon::executeInject('storage', $addonDriver['code'], $payload, 'exists');
    }

    private function buildAssetResponse(Asset $asset, ?string $resolvedUrl = null): array
    {
        $data = $asset->toArray();
        if (null !== $resolvedUrl) {
            $absoluteUrl = $this->toAbsoluteUrl($resolvedUrl);
            $data['url'] = $absoluteUrl;
            if (Str::startsWith((string) $asset->mime, 'image')) {
                $data['preview'] = $absoluteUrl;
            }
        }

        return $data;
    }

    private function formatAddonDriver(string $code, string $disk): string
    {
        return "addon:{$code}:{$disk}";
    }

    /**
     * @return array{code:string,disk:string}|null
     */
    private function parseAddonDriver(string $driver): ?array
    {
        if (!Str::startsWith($driver, 'addon:')) {
            return null;
        }

        $segments = explode(':', $driver, 3);
        if (\count($segments) < 3 || '' === $segments[1] || '' === $segments[2]) {
            return null;
        }

        return [
            'code' => $segments[1],
            'disk' => $segments[2],
        ];
    }

    /**
     * 上传模块允许在系统配置尚未初始化时直接工作，因此这里读取失败时回退默认值。
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function systemConfig(string $key, $default = null)
    {
        try {
            return system_config($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function resolveLocalDisk(string $driver): string
    {
        $driver = trim($driver);

        return '' === $driver ? $this->getDriver() : $driver;
    }

    private function toAbsoluteUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }

        $url = trim($url);
        if ('' === $url) {
            return $url;
        }

        if (Str::startsWith($url, ['http://', 'https://', '//', 'data:'])) {
            return $url;
        }

        return url($url);
    }
}
