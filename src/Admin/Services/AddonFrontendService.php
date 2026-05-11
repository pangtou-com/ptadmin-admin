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
    private const MANIFEST_NORMALIZER_VERSION = 'admin-modules-v4';
    private const PROJECT_FRONTEND_DEFAULT_CODE = '__app__';

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

        foreach ($this->extractProjectModuleDefinitions() as $definition) {
            $normalized = $this->normalizeModuleManifest($this->projectFrontendCode(), (array) $definition, $this->projectFrontendInfo());
            if ([] === $normalized) {
                continue;
            }

            $results[] = $normalized;
        }

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
        return 'ptadmin:admin:module-manifests:'.self::MANIFEST_NORMALIZER_VERSION.':'.$this->buildManifestFingerprint();
    }

    protected function buildManifestFingerprint(): string
    {
        $fingerprints = [];

        $projectDefinitions = $this->extractProjectModuleDefinitions();
        if ([] !== $projectDefinitions) {
            $fingerprints[] = [
                'code' => $this->projectFrontendCode(),
                'version' => (string) data_get($projectDefinitions, '0.version', ''),
                'develop' => $this->projectFrontendDevelop(),
                'modules' => array_values($projectDefinitions),
            ];
        }

        foreach ($this->getAvailableAddons() as $addonCode => $addonInfo) {
            $fingerprints[] = [
                'code' => (string) ($addonInfo['code'] ?? $addonCode),
                'version' => (string) ($addonInfo['version'] ?? ''),
                'develop' => !empty($addonInfo['develop']),
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
     * @return array<int, array<string, mixed>>
     */
    protected function extractProjectModuleDefinitions(): array
    {
        $file = $this->projectFrontendManifestPath();
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

        $definitions = [];
        if (isset($payload['modules']) && \is_array($payload['modules'])) {
            $definitions = array_values(array_filter($payload['modules'], static function ($item): bool {
                return \is_array($item);
            }));
        } elseif ($this->looksLikeManifestDefinition($payload)) {
            $definitions = [$payload];
        } else {
            $definitions = array_values(array_filter($payload, static function ($item): bool {
                return \is_array($item);
            }));
        }

        return array_values(array_map(function (array $definition): array {
            return $this->normalizeProjectDefinition($definition);
        }, $definitions));
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

        $isDevelop = $this->isFrontendDevelopMode($definition, $addonInfo, false);

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
                'develop' => $isDevelop,
            ],
            'entry' => $this->normalizeEntry($definition['entry'] ?? [], $runtime, $addonCode, array_replace($addonInfo, [
                'develop' => $isDevelop,
            ])),
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
        $enabled = !isset($definition['enabled']) || (bool)$definition['enabled'];
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

        $isDevelop = $this->isFrontendDevelopMode($definition, $addonInfo, true);

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
                'develop' => $isDevelop,
            ],
            'entry' => $this->normalizeEntry($definition['entry'] ?? [], $runtime, $code, array_replace($addonInfo, [
                'develop' => $isDevelop,
            ])),
            'capabilities' => $this->normalizeCapabilities((array) ($definition['capabilities'] ?? [])),
            'pages' => $this->normalizePages((array) ($definition['pages'] ?? []), $routeBase),
            'compatibility' => \is_array($definition['compatibility'] ?? null) ? $definition['compatibility'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    protected function normalizeProjectDefinition(array $definition): array
    {
        $projectCode = $this->projectFrontendCode();
        $definition['id'] = trim((string) ($definition['id'] ?? '')) ?: $projectCode;
        $definition['key'] = trim((string) ($definition['key'] ?? '')) ?: $projectCode;
        $definition['code'] = $projectCode;
        $definition['kind'] = trim((string) ($definition['kind'] ?? '')) ?: 'project-app';
        $definition['runtime'] = trim((string) ($definition['runtime'] ?? '')) ?: 'wujie';

        if (!isset($definition['name']) && !isset($definition['title'])) {
            $definition['name'] = (string) config('app.name', 'Application');
        }
        if (!isset($definition['name']) && isset($definition['title'])) {
            $definition['name'] = (string) $definition['title'];
        }

        if (!isset($definition['routeBase']) && !isset($definition['route_base'])) {
            $definition['routeBase'] = '/';
        }

        $entry = \is_array($definition['entry'] ?? null) ? $definition['entry'] : [];
        if ('wujie' === $definition['runtime'] && !isset($entry['wujie'])) {
            $entry['wujie'] = [];
        }
        if ('wujie' === $definition['runtime'] && \is_array($entry['wujie'] ?? null) && !isset($entry['wujie']['url'])) {
            $devUrl = trim((string) config('ptadmin-auth.project_frontend_dev_url', ''));
            $entry['wujie']['url'] = '' !== $devUrl ? $devUrl : $this->addonPublicModuleUrl($projectCode, 'dist/');
        }
        $definition['entry'] = $entry;

        $meta = \is_array($definition['meta'] ?? null) ? $definition['meta'] : [];
        if (!isset($meta['develop'])) {
            $meta['develop'] = $this->projectFrontendDevelop();
        }
        $definition['meta'] = $meta;

        return $definition;
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
     * @param mixed                $entry
     * @param array<string, mixed> $addonInfo
     *
     * @return array<string, mixed>
     */
    protected function normalizeEntry($entry, string $runtime, string $addonCode, array $addonInfo = []): array
    {
        if (!\is_array($entry)) {
            return [];
        }

        if ('federation' === $runtime) {
            $federation = \is_array($entry['federation'] ?? null) ? $entry['federation'] : [];

            return [
                'federation' => [
                    'remote' => (string) ($federation['remote'] ?? $addonCode),
                    'entry' => $this->normalizeFederationEntry((string) ($federation['entry'] ?? ''), $addonCode, $addonInfo),
                    'expose' => (string) ($federation['expose'] ?? './module'),
                ],
            ];
        }

        if ('wujie' === $runtime) {
            $wujie = \is_array($entry['wujie'] ?? null) ? $entry['wujie'] : [];

            return [
                'wujie' => [
                    'name' => (string) ($wujie['name'] ?? $addonCode),
                    'url' => $this->normalizeMicroAppUrl((string) ($wujie['url'] ?? ''), $addonCode, $addonInfo),
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

    /**
     * @param array<string, mixed> $addonInfo
     */
    protected function normalizeFederationEntry(string $entry, string $addonCode, array $addonInfo): string
    {
        $entry = trim($entry);
        if (!empty($addonInfo['develop'])) {
            return $entry;
        }

        if ('' === $entry || $this->isLocalDevelopmentUrl($entry)) {
            return $this->defaultFederationEntry($addonCode);
        }

        if (0 === strpos($entry, '/addons/'.$addonCode.'/')) {
            return $this->addonPublicModuleUrl($addonCode, substr($entry, \strlen('/addons/'.$addonCode.'/')));
        }

        if ($this->isAbsoluteUrl($entry)) {
            return $entry;
        }

        if ('/' === $entry[0]) {
            return $this->makeAbsoluteUrl($entry);
        }

        return $this->normalizeAssetUrl($entry, $addonCode);
    }

    protected function defaultFederationEntry(string $addonCode): string
    {
        return $this->addonPublicModuleUrl($addonCode, 'dist/assets/remoteEntry.js');
    }

    /**
     * frontend.json 可能来自开发构建，不能单独用 meta.develop 决定是否返回 localhost。
     * 只有插件自身处于 develop 模式时，前端模块才允许输出开发入口。
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $addonInfo
     */
    protected function isFrontendDevelopMode(array $definition, array $addonInfo, bool $default): bool
    {
        if (empty($addonInfo['develop'])) {
            return false;
        }

        return (bool) data_get($definition, 'meta.develop', $default);
    }

    /**
     * @param array<string, mixed> $addonInfo
     */
    protected function normalizeMicroAppUrl(string $url, string $addonCode, array $addonInfo): string
    {
        $url = trim($url);
        if (!empty($addonInfo['develop'])) {
            return $url;
        }

        if ('' === $url || $this->isLocalDevelopmentUrl($url)) {
            return $this->addonPublicModuleUrl($addonCode, 'dist/');
        }

        if (0 === strpos($url, '/addons/'.$addonCode.'/')) {
            return $this->addonPublicModuleUrl($addonCode, substr($url, \strlen('/addons/'.$addonCode.'/')));
        }

        if ('/' === $url[0]) {
            return $this->makeAbsoluteUrl($url);
        }

        return $url;
    }

    protected function isLocalDevelopmentUrl(string $value): bool
    {
        if (!$this->isAbsoluteUrl($value)) {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!\is_string($host)) {
            return false;
        }

        $host = strtolower($host);

        return \in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true);
    }

    protected function normalizeAssetUrl(string $path, string $addonCode): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '';
        }

        if (0 === strpos($path, '/addons/'.$addonCode.'/')) {
            return $this->addonPublicModuleUrl($addonCode, substr($path, \strlen('/addons/'.$addonCode.'/')));
        }

        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        if ('/' === $path[0]) {
            return $this->makeAbsoluteUrl($path);
        }

        return $this->addonPublicModuleUrl($addonCode, $path);
    }

    protected function makeAbsoluteUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        if ('' === $baseUrl) {
            $requestBaseUrl = request()->getSchemeAndHttpHost();
            if ('' !== trim((string) $requestBaseUrl)) {
                $baseUrl = rtrim((string) $requestBaseUrl, '/');
            }
        }

        if ('' === $baseUrl) {
            return $path;
        }

        return $baseUrl.$path;
    }

    protected function addonPublicModuleUrl(string $addonCode, string $path = ''): string
    {
        $url = admin_web_url('modules/'.$addonCode.'/'.ltrim($path, '/'));

        $url = '' !== $path && '/' === substr($path, -1) ? $url.'/' : $url;

        return $this->makeAbsoluteUrl($url);
    }

    protected function projectFrontendCode(): string
    {
        $code = trim((string) config('ptadmin-auth.project_frontend_code', self::PROJECT_FRONTEND_DEFAULT_CODE));

        return '' === $code ? self::PROJECT_FRONTEND_DEFAULT_CODE : $code;
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectFrontendInfo(): array
    {
        $code = $this->projectFrontendCode();
        $definitions = $this->extractProjectModuleDefinitions();
        $version = (string) data_get($definitions, '0.version', config('app.version', ''));

        return [
            'code' => $code,
            'title' => (string) config('app.name', 'Application'),
            'description' => '',
            'version' => $version,
            'develop' => $this->projectFrontendDevelop(),
            'base_path' => '',
        ];
    }

    protected function projectFrontendDevelop(): bool
    {
        return (bool) config('app.debug', false);
    }

    protected function projectFrontendManifestPath(): ?string
    {
        $path = trim((string) config('ptadmin-auth.project_frontend_manifest', ''));
        if ('' === $path) {
            return null;
        }

        if ($this->isAbsoluteFilesystemPath($path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function isAbsoluteFilesystemPath(string $path): bool
    {
        return '' !== $path
            && ('/' === $path[0] || '\\' === $path[0] || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path));
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
