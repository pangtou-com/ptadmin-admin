<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
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

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Models\Asset;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use Symfony\Component\Mime\MimeTypes;

class AssetService
{
    private const REMOTE_DRIVER = 'remote';

    /**
     * 返回资源分页列表。
     *
     * @param array<string, mixed> $search
     *
     * @return array<string, mixed>
     */
    public function page(array $search = []): array
    {
        $query = Asset::query();

        $title = trim((string) ($search['title'] ?? ''));
        if ('' !== $title) {
            $query->where('title', 'like', "%{$title}%");
        }

        $group = trim((string) ($search['groups'] ?? ''));
        if ('' !== $group) {
            $query->where('groups', $group);
        }

        $mime = trim((string) ($search['mime'] ?? ''));
        if ('' !== $mime) {
            $query->where('mime', 'like', "%{$mime}%");
        }

        $suffix = trim((string) ($search['suffix'] ?? ''));
        if ('' !== $suffix) {
            $query->where('suffix', $suffix);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate()
            ->toArray();
    }

    /**
     * 删除资源记录及物理文件。
     *
     * @param array<int, int|string>|int|string $ids
     */
    public function delete($ids): void
    {
        $ids = array_values(array_filter(array_map('intval', Arr::wrap($ids))));
        if (0 === \count($ids)) {
            return;
        }

        $assets = Asset::query()->whereIn('id', $ids)->get();

        foreach ($assets as $asset) {
            if ($this->isAddonDriver((string) $asset->driver)) {
                $addonDriver = $this->parseAddonDriver((string) $asset->driver);
                if (null === $addonDriver) {
                    throw new BackgroundException(__('ptadmin::background.remote_driver_invalid'));
                }

                $deleted = $this->executeAddonStorageDelete($addonDriver, [
                    'disk' => $addonDriver['disk'],
                    'bucket' => null,
                    'path' => (string) $asset->path,
                    'meta' => [],
                ]);

                if (!$deleted) {
                    throw new BackgroundException(__('ptadmin::background.remote_delete_failed'));
                }
            } else {
                $disk = $this->resolveLocalDisk((string) $asset->driver);
                if (!blank($asset->path) && Storage::disk($disk)->exists((string) $asset->path)) {
                    Storage::disk($disk)->delete((string) $asset->path);
                }
            }

            $asset->delete();
        }
    }

    /**
     * 通过远程 URL 写入资源库，可选择下载到本地存储。
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function remote(array $payload): array
    {
        $url = trim((string) ($payload['url'] ?? ''));
        $group = $this->normalizeGroup((string) ($payload['group'] ?? 'default'));
        $isLocalSave = filter_var($payload['is_local_save'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$isLocalSave) {
            $asset = Asset::query()->firstOrCreate([
                'driver' => self::REMOTE_DRIVER,
                'path' => $url,
            ], $this->buildRemoteAssetAttributes($url, $group));

            return $asset->fresh()->toArray();
        }

        return $this->downloadRemoteToLocal($url, $group);
    }

    /**
     * @param array{code:string,disk:string} $addonDriver
     * @param array<string, mixed> $payload
     */
    protected function executeAddonStorageDelete(array $addonDriver, array $payload): bool
    {
        return (bool) Addon::executeInject('storage', $addonDriver['code'], $payload, 'delete');
    }

    private function isAddonDriver(string $driver): bool
    {
        return Str::startsWith($driver, 'addon:');
    }

    /**
     * @return array{code:string,disk:string}|null
     */
    private function parseAddonDriver(string $driver): ?array
    {
        $segments = explode(':', $driver, 3);
        if (\count($segments) < 3 || '' === $segments[1] || '' === $segments[2]) {
            return null;
        }

        return [
            'code' => $segments[1],
            'disk' => $segments[2],
        ];
    }

    private function resolveLocalDisk(string $driver): string
    {
        $driver = trim($driver);

        return '' === $driver ? (string) config('ptadmin.upload_local_disk', 'public') : $driver;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRemoteAssetAttributes(string $url, string $group): array
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return [
            'title' => $this->titleFromUrl($url),
            'md5' => md5($url),
            'mime' => $this->mimeFromPath($path),
            'suffix' => $this->extensionFromPath($path),
            'driver' => self::REMOTE_DRIVER,
            'size' => 0,
            'path' => $url,
            'groups' => $group,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function downloadRemoteToLocal(string $url, string $group): array
    {
        $remote = $this->fetchRemoteContent($url);
        $content = $remote['content'];
        if ('' === $content) {
            throw new BackgroundException(__('ptadmin::background.remote_download_failed'));
        }

        $md5 = md5($content);
        $asset = Asset::byMd5($md5);
        if ($asset && $this->localAssetExists($asset)) {
            return $asset->toArray();
        }

        if ($asset) {
            $asset->delete();
        } else {
            $asset = new Asset();
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $mime = $this->normalizeMime($remote['content_type'], $path);
        $suffix = $this->resolveExtension($path, $mime);
        $title = $this->titleFromUrl($url, $suffix);
        $disk = (string) config('ptadmin.upload_local_disk', 'public');
        $objectPath = $this->buildLocalObjectPath($group, $md5, $suffix);

        if (!Storage::disk($disk)->put($objectPath, $content)) {
            throw new BackgroundException(__('ptadmin::background.file_save_failed'));
        }

        $asset->fill([
            'title' => $title,
            'md5' => $md5,
            'mime' => $mime,
            'suffix' => $suffix,
            'driver' => $disk,
            'size' => \strlen($content),
            'path' => $objectPath,
            'groups' => $group,
        ])->save();

        return $asset->fresh()->toArray();
    }

    /**
     * @return array{content:string,content_type:string}
     */
    protected function fetchRemoteContent(string $url): array
    {
        $headers = [];
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => "User-Agent: PTAdmin Remote Asset\r\n",
            ],
        ]);
        $content = @file_get_contents($url, false, $context);

        if (isset($http_response_header) && \is_array($http_response_header)) {
            $headers = $http_response_header;
        }

        if (false === $content || !$this->isSuccessfulRemoteResponse($headers)) {
            throw new BackgroundException(__('ptadmin::background.remote_download_failed'));
        }

        return [
            'content' => $content,
            'content_type' => $this->headerValue($headers, 'Content-Type'),
        ];
    }

    private function localAssetExists(Asset $asset): bool
    {
        if ($this->isAddonDriver((string) $asset->driver) || self::REMOTE_DRIVER === (string) $asset->driver) {
            return false;
        }

        return Storage::disk($this->resolveLocalDisk((string) $asset->driver))->exists((string) $asset->path);
    }

    private function buildLocalObjectPath(string $group, string $md5, string $suffix): string
    {
        $filename = '' === $suffix ? $md5 : $md5.'.'.$suffix;

        return trim($group.'/'.date('Ymd').'/'.$filename, '/');
    }

    private function normalizeGroup(string $group): string
    {
        $group = trim($group);

        return '' === $group ? 'default' : $group;
    }

    private function titleFromUrl(string $url, string $fallbackSuffix = ''): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $title = trim(basename($path));
        if ('' !== $title && '.' !== $title) {
            return $title;
        }

        return '' === $fallbackSuffix ? md5($url) : md5($url).'.'.$fallbackSuffix;
    }

    private function extensionFromPath(string $path): string
    {
        return strtolower(trim((string) pathinfo($path, PATHINFO_EXTENSION), '.'));
    }

    private function mimeFromPath(string $path): string
    {
        $extension = $this->extensionFromPath($path);
        if ('' === $extension) {
            return 'application/octet-stream';
        }

        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream';
    }

    private function normalizeMime(string $contentType, string $path): string
    {
        $mime = strtolower(trim(Str::before($contentType, ';')));
        if ('' !== $mime) {
            return $mime;
        }

        return $this->mimeFromPath($path);
    }

    private function resolveExtension(string $path, string $mime): string
    {
        $extension = $this->extensionFromPath($path);
        if ('' !== $extension) {
            return $extension;
        }

        return MimeTypes::getDefault()->getExtensions($mime)[0] ?? 'bin';
    }

    /**
     * @param array<int, string> $headers
     */
    private function isSuccessfulRemoteResponse(array $headers): bool
    {
        if (0 === \count($headers)) {
            return true;
        }

        $status = $headers[0] ?? '';
        if (!preg_match('/\s(\d{3})\s/', $status, $matches)) {
            return true;
        }

        $code = (int) $matches[1];

        return $code >= 200 && $code < 300;
    }

    /**
     * @param array<int, string> $headers
     */
    private function headerValue(array $headers, string $name): string
    {
        $prefix = strtolower($name).':';
        foreach ($headers as $header) {
            if (Str::startsWith(strtolower($header), $prefix)) {
                return trim(substr($header, \strlen($prefix)));
            }
        }

        return '';
    }
}
