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

class AssetService
{
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
                    throw new BackgroundException('远程存储驱动配置错误');
                }

                $deleted = $this->executeAddonStorageDelete($addonDriver, [
                    'disk' => $addonDriver['disk'],
                    'bucket' => null,
                    'path' => (string) $asset->path,
                    'meta' => [],
                ]);

                if (!$deleted) {
                    throw new BackgroundException('远程文件删除失败');
                }
            } elseif (!blank($asset->path) && Storage::exists((string) $asset->path)) {
                Storage::delete((string) $asset->path);
            }

            $asset->delete();
        }
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
}
