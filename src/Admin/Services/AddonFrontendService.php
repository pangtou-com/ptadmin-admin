<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Addon\Addon;

/**
 * 后台前端模块清单聚合服务。
 *
 * 模块清单属于前端发布描述，不参与权限判定。
 * 该服务负责从插件独立模块文件中读取定义，并对外输出统一协议。
 */
class AddonFrontendService
{
    /**
     * 获取公开可读的后台模块清单。
     *
     * @return array<string, mixed>
     */
    public function manifests(): array
    {
        $cacheKey = $this->buildCacheKey();
        $ttl = max(0, (int) config('ptadmin-auth.module_manifest_cache_ttl', 300));

        if ($ttl <= 0) {
            return $this->buildManifestPayload();
        }

        return Cache::remember($cacheKey, $ttl, function (): array {
            return $this->buildManifestPayload();
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildManifestPayload(): array
    {
        $results = [];

        foreach (array_keys(Addon::getAddons()) as $addonCode) {
            $addonInfo = Addon::getAddon((string) $addonCode)->getAddons();
            foreach ($this->extractModuleDefinitions((string) $addonCode, $addonInfo) as $definition) {
                $normalized = $this->normalizeModuleManifest((string) $addonCode, (array) $definition, $addonInfo);
                if ([] === $normalized) {
                    continue;
                }

                $results[] = $normalized;
            }
        }

        usort($results, function (array $left, array $right) {
            $orderCompare = ((int) data_get($left, 'meta.order', 0)) <=> ((int) data_get($right, 'meta.order', 0));
            if (0 !== $orderCompare) {
                return $orderCompare;
            }

            return strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? ''));
        });

        return array_values($results);
    }

    protected function buildCacheKey(): string
    {
        return 'ptadmin:admin:module-manifests:'.$this->buildManifestFingerprint();
    }

    protected function buildManifestFingerprint(): string
    {
        $fingerprints = [];

        foreach (array_keys(Addon::getAddons()) as $addonCode) {
            $addonInfo = (array) Addon::getAddon((string) $addonCode)->getAddons();
            $fingerprints[] = [
                'code' => (string) ($addonInfo['code'] ?? $addonCode),
                'version' => (string) ($addonInfo['version'] ?? ''),
                'modules' => array_values($this->extractModuleDefinitions((string) $addonCode, $addonInfo)),
            ];
        }

        return md5((string) json_encode($fingerprints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractModuleDefinitions(string $addonCode, array $addonInfo): array
    {
        $file = $this->resolveModuleManifestPath($addonCode, $addonInfo);
        if (null === $file || !is_file($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if (false === $content || '' === trim($content)) {
            return [];
        }

        $payload = @json_decode($content, true);
        if (!\is_array($payload)) {
            return [];
        }

        $definitions = isset($payload['modules']) && \is_array($payload['modules'])
            ? $payload['modules']
            : $payload;

        return array_values(array_filter($definitions, static function ($item): bool {
            return \is_array($item);
        }));
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $addonInfo
     *
     * @return array<string, mixed>
     */
    protected function normalizeModuleManifest(string $addonCode, array $definition, array $addonInfo): array
    {
        $key = trim((string) ($definition['key'] ?? ''));
        if ('' === $key) {
            return [];
        }

        $enabled = isset($definition['enabled']) ? (int) $definition['enabled'] : 1;
        if (1 !== $enabled) {
            return [];
        }

        $runtime = trim((string) ($definition['runtime'] ?? 'local'));
        $routeBase = $this->normalizeRoute((string) ($definition['route_base'] ?? ''));
        $pages = $this->normalizePages((array) ($definition['pages'] ?? []), $routeBase);
        if ([] === $pages) {
            return [];
        }

        return [
            'key' => $key,
            'title' => (string) ($definition['title'] ?? $addonInfo['title'] ?? $key),
            'description' => (string) ($definition['description'] ?? $addonInfo['description'] ?? ''),
            'version' => (string) ($definition['version'] ?? $addonInfo['version'] ?? ''),
            'enabled' => 1,
            'runtime' => '' === $runtime ? 'local' : $runtime,
            'route_base' => $routeBase,
            'meta' => [
                'icon' => $this->normalizeNullableString(data_get($definition, 'meta.icon')),
                'order' => (int) data_get($definition, 'meta.order', 0),
                'preload' => (bool) data_get($definition, 'meta.preload', false),
                'develop' => (bool) data_get($definition, 'meta.develop', false),
            ],
            'entry' => $this->normalizeEntry($definition['entry'] ?? [], $runtime, $addonCode),
            'pages' => $pages,
        ];
    }

    /**
     * @param array<int, mixed> $pages
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePages(array $pages, string $routeBase): array
    {
        $results = [];

        foreach ($pages as $page) {
            if (!\is_array($page)) {
                continue;
            }

            $key = trim((string) ($page['key'] ?? ''));
            $path = $this->normalizeRoute((string) ($page['path'] ?? ''));
            if ('' === $key || '' === $path) {
                continue;
            }

            if ('' !== $routeBase && 0 !== strpos($path, $routeBase)) {
                continue;
            }

            $results[] = [
                'key' => $key,
                'path' => $path,
                'route_name' => (string) ($page['route_name'] ?? ''),
                'title' => (string) ($page['title'] ?? $key),
                'keep_alive' => (bool) ($page['keep_alive'] ?? false),
            ];
        }

        return array_values($results);
    }

    /**
     * @param mixed  $entry
     * @param string $runtime
     *
     * @return array<string, mixed>
     */
    protected function normalizeEntry($entry, string $runtime, string $addonCode): array
    {
        if (!\is_array($entry)) {
            return [];
        }

        if ('wujie' === $runtime) {
            $wujie = \is_array($entry['wujie'] ?? null) ? $entry['wujie'] : [];

            return [
                'wujie' => [
                    'name' => (string) ($wujie['name'] ?? $addonCode),
                    'url' => (string) ($wujie['url'] ?? ''),
                    'alive' => (bool) ($wujie['alive'] ?? false),
                    'sync' => (bool) ($wujie['sync'] ?? false),
                    'degrade' => (bool) ($wujie['degrade'] ?? false),
                ],
            ];
        }

        $local = \is_array($entry['local'] ?? null) ? $entry['local'] : [];

        return [
            'local' => [
                'type' => (string) ($local['type'] ?? 'module'),
                'js' => $this->normalizeAssetUrl((string) ($local['js'] ?? ''), $addonCode),
                'css' => $this->normalizeAssetList((array) ($local['css'] ?? []), $addonCode),
            ],
        ];
    }

    protected function normalizeAssetUrl(string $path, string $addonCode): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '';
        }

        if ($this->isAbsoluteUrl($path) || '/' === $path[0]) {
            return $path;
        }

        return '/addons/'.$addonCode.'/'.ltrim($path, '/');
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return array<int, string>
     */
    protected function normalizeAssetList(array $items, string $addonCode): array
    {
        $results = [];

        foreach ($items as $item) {
            if (!\is_scalar($item)) {
                continue;
            }

            $path = $this->normalizeAssetUrl((string) $item, $addonCode);
            if ('' === $path) {
                continue;
            }

            $results[] = $path;
        }

        return array_values(array_unique($results));
    }

    protected function normalizeRoute(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '';
        }

        return '/'.trim($path, '/');
    }

    /**
     * @param mixed $value
     */
    protected function normalizeNullableString($value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    protected function isAbsoluteUrl(string $value): bool
    {
        return false !== strpos($value, '://') || 0 === strpos($value, '//');
    }

    /**
     * 模块清单使用独立文件，方便前端构建阶段直接生成。
     *
     * 默认读取插件根目录 `frontend.json`。
     * 也支持通过 manifest 显式指定 `module_manifest` 相对路径。
     *
     * @param array<string, mixed> $addonInfo
     */
    protected function resolveModuleManifestPath(string $addonCode, array $addonInfo): ?string
    {
        $relativePath = trim((string) ($addonInfo['module_manifest'] ?? 'frontend.json'));
        if ('' === $relativePath) {
            return null;
        }

        return Addon::getAddon($addonCode)->getAddonPath($relativePath);
    }
}
