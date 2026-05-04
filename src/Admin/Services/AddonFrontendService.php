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

        foreach ($this->getAvailableAddons() as $addonCode => $addonInfo) {
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

        foreach ($this->getAvailableAddons() as $addonCode => $addonInfo) {
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

        if (isset($payload['modules']) && \is_array($payload['modules'])) {
            return array_values(array_filter($payload['modules'], static function ($item): bool {
                return \is_array($item);
            }));
        }

        if ($this->looksLikeManifestDefinition($payload)) {
            return [$payload];
        }

        return array_values(array_filter($payload, static function ($item): bool {
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
        if ($this->looksLikeFrontendDefinition($definition)) {
            return $this->normalizeFrontendDefinition($addonCode, $definition, $addonInfo);
        }

        return $this->normalizeLegacyModuleManifest($addonCode, $definition, $addonInfo);
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $addonInfo
     *
     * @return array<string, mixed>
     */
    protected function normalizeLegacyModuleManifest(string $addonCode, array $definition, array $addonInfo): array
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
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $addonInfo
     *
     * @return array<string, mixed>
     */
    protected function normalizeFrontendDefinition(string $addonCode, array $definition, array $addonInfo): array
    {
        $enabled = isset($definition['enabled']) ? (bool) $definition['enabled'] : true;
        if (!$enabled) {
            return [];
        }

        $code = trim((string) ($definition['code'] ?? $addonInfo['code'] ?? $addonCode));
        if ('' === $code) {
            return [];
        }

        $name = trim((string) ($definition['name'] ?? $addonInfo['title'] ?? $code));
        $runtime = trim((string) ($definition['runtime'] ?? 'local'));
        $routeBase = $this->normalizeRoute((string) ($definition['routeBase'] ?? $definition['route_base'] ?? ''));
        if ('' === $routeBase) {
            $routeBase = '/'.$code;
        }

        return [
            'id' => trim((string) ($definition['id'] ?? $code)),
            'key' => trim((string) ($definition['key'] ?? $code)),
            'code' => $code,
            'name' => '' === $name ? $code : $name,
            'title' => '' === $name ? $code : $name,
            'description' => (string) data_get($definition, 'meta.description', $addonInfo['description'] ?? ''),
            'version' => (string) ($definition['version'] ?? $addonInfo['version'] ?? ''),
            'enabled' => 1,
            'kind' => (string) ($definition['kind'] ?? 'module'),
            'runtime' => '' === $runtime ? 'local' : $runtime,
            'routeBase' => $routeBase,
            'route_base' => $routeBase,
            'meta' => [
                'icon' => $this->normalizeNullableString(data_get($definition, 'meta.icon')),
                'order' => (int) data_get($definition, 'meta.order', 0),
                'preload' => (bool) data_get($definition, 'meta.preload', false),
                'develop' => (bool) data_get($definition, 'meta.develop', !empty($addonInfo['develop'])),
            ],
            'entry' => $this->normalizeEntry($definition['entry'] ?? [], $runtime, $code),
            'capabilities' => $this->normalizeCapabilities((array) ($definition['capabilities'] ?? [])),
            'pages' => $this->normalizePages((array) ($definition['pages'] ?? []), $routeBase),
            'compatibility' => \is_array($definition['compatibility'] ?? null) ? $definition['compatibility'] : [],
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

        if ('federation' === $runtime) {
            $federation = \is_array($entry['federation'] ?? null) ? $entry['federation'] : [];

            return [
                'federation' => [
                    'remote' => (string) ($federation['remote'] ?? $addonCode),
                    'entry' => (string) ($federation['entry'] ?? ''),
                    'expose' => (string) ($federation['expose'] ?? './module'),
                ],
            ];
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

    /**
     * @param array<string, mixed> $capabilities
     *
     * @return array<string, bool>
     */
    protected function normalizeCapabilities(array $capabilities): array
    {
        return [
            'routes' => (bool) ($capabilities['routes'] ?? false),
            'pages' => (bool) ($capabilities['pages'] ?? false),
            'widgets' => (bool) ($capabilities['widgets'] ?? false),
            'settings' => (bool) ($capabilities['settings'] ?? false),
        ];
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
     * @param array<string, mixed> $definition
     */
    protected function looksLikeManifestDefinition(array $definition): bool
    {
        return $this->looksLikeFrontendDefinition($definition)
            || isset($definition['key'])
            || isset($definition['route_base'])
            || isset($definition['pages']);
    }

    /**
     * @param array<string, mixed> $definition
     */
    protected function looksLikeFrontendDefinition(array $definition): bool
    {
        return isset($definition['kind'])
            || isset($definition['routeBase'])
            || isset($definition['capabilities'])
            || isset($definition['entry']);
    }

    /**
     * 模块清单使用独立文件，方便前端构建阶段直接生成。
     *
     * 开发阶段优先读取 `Frontend/frontend.json`。
     * 部署阶段优先读取插件根目录 `frontend.json`。
     * 也支持通过 manifest 显式指定 `module_manifest` 相对路径。
     *
     * @param array<string, mixed> $addonInfo
     */
    protected function resolveModuleManifestPath(string $addonCode, array $addonInfo): ?string
    {
        $candidates = [];
        $configuredPath = trim((string) ($addonInfo['module_manifest'] ?? ''));
        $isDevelop = !empty($addonInfo['develop']);

        if ('' !== $configuredPath) {
            $candidates[] = $configuredPath;
        }

        if ($isDevelop) {
            $candidates[] = 'Frontend/frontend.json';
            $candidates[] = 'frontend.json';
        } else {
            $candidates[] = 'frontend.json';
            $candidates[] = 'Frontend/frontend.json';
        }

        foreach (array_unique(array_filter($candidates, static function (string $path): bool {
            return '' !== trim($path);
        })) as $relativePath) {
            $resolvedPath = $this->resolveAddonFilePath($addonCode, $addonInfo, $relativePath);
            if (is_file($resolvedPath)) {
                return $resolvedPath;
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getAvailableAddons(): array
    {
        $addons = [];

        foreach (Addon::getAddons() as $addonCode => $addon) {
            if (!\is_array($addon)) {
                continue;
            }

            $addonInfo = \is_array($addon['addons'] ?? null) ? $addon['addons'] : $addon;
            if (!\is_array($addonInfo) || !empty($addonInfo['disable'])) {
                continue;
            }

            $addons[(string) $addonCode] = $addonInfo;
        }

        return $addons;
    }

    /**
     * @param array<string, mixed> $addonInfo
     */
    protected function resolveAddonFilePath(string $addonCode, array $addonInfo, string $relativePath): string
    {
        $basePath = trim((string) ($addonInfo['base_path'] ?? ''));
        if ('' !== $basePath) {
            return base_path('addons/'.$basePath.'/'.ltrim($relativePath, '/'));
        }

        if (Addon::hasAddon($addonCode)) {
            return Addon::getAddon($addonCode)->getAddonPath($relativePath);
        }

        return base_path('addons/'.ltrim($relativePath, '/'));
    }
}
