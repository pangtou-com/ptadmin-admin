<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Http\UploadedFile;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\AddonApi;
use PTAdmin\Addon\Exception\AddonException;
use PTAdmin\Addon\Service\Action\AddonAction;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use Throwable;

class AddonPlatformService
{
    private SystemSettingsService $systemSettingsService;

    public function __construct(SystemSettingsService $systemSettingsService)
    {
        $this->systemSettingsService = $systemSettingsService;
    }

    /**
     * 返回云市场插件列表，并补齐当前宿主的安装态信息。
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function cloudMarket(array $filters = []): array
    {
        $payload = AddonApi::getCloudMarket($filters);

        return $this->normalizeCloudPayload(\is_array($payload) ? $payload : []);
    }

    /**
     * 返回当前云账号下的插件数据。
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function myCloudAddons(array $filters = []): array
    {
        $payload = AddonApi::getMyAddon($filters);

        return $this->normalizeCloudPayload(\is_array($payload) ? $payload : []);
    }

    /**
     * 返回本地已安装插件列表。
     *
     * @return array<string, mixed>
     */
    public function localAddons(): array
    {
        $results = [];
        $installedAddons = Addon::getInstalledAddons();
        $configurableCodes = $this->configurableAddonCodes(array_keys($installedAddons));

        foreach ($installedAddons as $code => $addonInfo) {
            if (!\is_array($addonInfo)) {
                continue;
            }

            $results[] = $this->normalizeLocalAddon(
                (string) $code,
                $addonInfo,
                isset($configurableCodes[(string) $code])
            );
        }

        usort($results, static function (array $left, array $right) {
            return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });

        return [
            'total' => \count($results),
            'results' => array_values($results),
        ];
    }

    /**
     * 从云平台安装插件。
     *
     * @return array<string, mixed>
     */
    public function installFromCloud(string $code, int $versionId = 0, bool $force = false): array
    {
        AddonAction::install($code, $versionId, $force);

        return $this->status($code);
    }

    /**
     * 从本地 zip 包安装插件。
     *
     * @return array<string, mixed>
     */
    public function installFromLocal(UploadedFile $file, bool $force = false): array
    {
        $directory = storage_path('app/addons/uploads');
        $this->ensureDirectoryWritable($directory);

        $filename = sprintf('%s_%s.%s', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), uniqid('', true), $file->getClientOriginalExtension());
        $target = $file->move($directory, $filename);
        $code = (string) AddonAction::installLocal($target->getPathname(), $force);
        @unlink($target->getPathname());

        return $this->status($code);
    }

    private function ensureDirectoryWritable(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new BackgroundException(__('ptadmin::background.directory_not_writable', ['path' => $directory]));
            }

            return;
        }

        $parent = \dirname($directory);
        while ($parent !== \dirname($parent) && !is_dir($parent)) {
            $parent = \dirname($parent);
        }

        if (!is_dir($parent) || !is_writable($parent)) {
            throw new BackgroundException(__('ptadmin::background.directory_not_writable', ['path' => $directory]));
        }

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new BackgroundException(__('ptadmin::background.directory_not_writable', ['path' => $directory]));
        }
    }

    /**
     * 初始化插件开发脚手架。
     *
     * @return array<string, mixed>
     */
    public function initAddon(string $code, string $title = '', bool $force = false): array
    {
        $result = $this->performAddonInitialization($code, $title, $force);

        return $result;
    }

    /**
     * 拉取插件前端开发模板。
     *
     * @return array<string, mixed>
     */
    public function pullFrontend(string $code, string $template = 'vue3-admin', string $ref = 'main', string $source = '', bool $force = false): array
    {
        return (array) AddonAction::pullFrontend($code, $template, $ref, $source, $force);
    }

    /**
     * @return array<string, mixed>
     */
    protected function performAddonInitialization(string $code, string $title = '', bool $force = false): array
    {
        return (array) AddonAction::init($code, $title, $force);
    }

    /**
     * 同步插件后台资源定义。
     *
     * @return array<string, mixed>
     */
    public function syncResources(string $code): array
    {
        AddonAction::syncResources($code);

        return $this->status($code);
    }

    /**
     * 返回单个插件当前状态。
     *
     * @return array<string, mixed>
     */
    public function status(string $code): array
    {
        $installed = Addon::getInstalledAddons();
        if (!isset($installed[$code]) || !\is_array($installed[$code])) {
            throw new BackgroundException(sprintf('插件[%s]不存在', $code));
        }

        return $this->normalizeLocalAddon($code, $installed[$code], $this->hasAddonConfig($code));
    }

    /**
     * 卸载插件。
     *
     * @return array<string, mixed>
     */
    public function uninstall(string $code, bool $force = false): array
    {
        $addon = $this->status($code);
        $this->assertAddonCanUninstall($code, $force);
        AddonAction::uninstall($code, true);

        return [
            'code' => $code,
            'uninstalled' => true,
            'addon' => $addon,
        ];
    }

    /**
     * 启用插件。
     *
     * @return array<string, mixed>
     */
    public function enable(string $code): array
    {
        AddonAction::enable($code);

        return $this->status($code);
    }

    /**
     * 停用插件。
     *
     * @return array<string, mixed>
     */
    public function disable(string $code): array
    {
        AddonAction::disable($code);

        return $this->status($code);
    }

    /**
     * 升级插件。
     *
     * @return array<string, mixed>
     */
    public function upgrade(string $code, int $versionId = 0, bool $force = false): array
    {
        AddonAction::upgrade($code, $versionId, $force);

        return $this->status($code);
    }

    /**
     * 返回插件通用配置。
     *
     * @return array<string, mixed>
     */
    public function addonConfig(string $code): array
    {
        $groups = SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get();

        $sections = $groups->map(function (SystemConfigGroup $group): array {
            $section = $this->systemSettingsService->addonSection(
                (string) $group->addon_code,
                (string) $group->name
            );

            return $this->sanitizeAddonSection($group, $section);
        })->values()->all();

        return [
            'code' => $code,
            'sections' => $sections,
        ];
    }

    /**
     * 保存插件通用配置。
     *
     * @param array<string, mixed> $data
     *
     */
    public function saveAddonConfig(string $code, array $data): array
    {
        $sections = SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get();
        $sectionKey = trim((string) ($data['section'] ?? ''));

        if ('' === $sectionKey && 1 === $sections->count()) {
            $sectionKey = (string) $sections->first()->name;
        }

        /** @var SystemConfigGroup|null $section */
        $section = $sections->firstWhere('name', $sectionKey);
        if (!$section) {
            if ($sections->isEmpty()) {
                throw new BackgroundException(sprintf('插件[%s]未提供通用配置', $code));
            }

            throw new BackgroundException(sprintf('插件[%s]配置分组[%s]不存在', $code, $sectionKey));
        }

        $values = $data['values'] ?? $data;
        if (!\is_array($values)) {
            throw new BackgroundException(sprintf('插件[%s]配置内容无效', $code));
        }

        foreach ($section->configs()->whereIn('type', ['password', 'secret'])->pluck('name')->all() as $fieldName) {
            if (array_key_exists($fieldName, $values) && ('' === $values[$fieldName] || null === $values[$fieldName])) {
                unset($values[$fieldName]);
            }
        }

        $this->systemSettingsService->saveAddonSection($code, (string) $section->name, ['values' => $values]);

        return $this->addonConfig($code);
    }

    /**
     * @param array<string, mixed> $section
     *
     * @return array<string, mixed>
     */
    private function sanitizeAddonSection(SystemConfigGroup $group, array $section): array
    {
        $schema = \is_array($section['schema'] ?? null) ? $section['schema'] : [];
        $fields = \is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $values = \is_array($section['values'] ?? null) ? $section['values'] : [];
        $storedFields = collect($section['fields'] ?? [])->keyBy('name');

        $schema['fields'] = array_map(static function (array $field) use (&$values, $storedFields): array {
            if ('password' !== ($field['type'] ?? null)) {
                return $field;
            }

            $name = (string) ($field['name'] ?? '');
            $storedField = $storedFields->get($name);
            $configured = '' !== (string) data_get($storedField, 'value', '');
            $help = trim((string) ($field['help'] ?? ''));

            $field['configured'] = $configured;
            $field['defaultValue'] = '';
            $field['help'] = $configured
                ? trim($help.' 已配置，留空保持现有值。')
                : $help;

            if ('' !== $name) {
                $values[$name] = '';
            }

            return $field;
        }, $fields);

        return [
            'key' => (string) $group->name,
            'title' => (string) $group->title,
            'intro' => (string) ($group->intro ?? ''),
            'schema' => $schema,
            'values' => $values,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizeCloudPayload(array $payload): array
    {
        if (!isset($payload['results']) || !\is_array($payload['results'])) {
            return $payload;
        }

        $results = [];
        foreach ($payload['results'] as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $results[] = $this->normalizeCloudAddon($item);
        }

        $payload['results'] = array_values($results);
        $payload['total'] = isset($payload['total']) ? (int) $payload['total'] : \count($results);

        return $payload;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function normalizeCloudAddon(array $item): array
    {
        $code = (string) ($item['code'] ?? $item['addon_code'] ?? '');
        if ('' === $code) {
            return $item;
        }

        $installed = Addon::hasInstalledAddon($code);
        $enabled = Addon::hasAddon($code);
        $item['installed'] = $installed ? 1 : 0;
        $item['enabled'] = $enabled ? 1 : 0;
        $item['is_install'] = $installed ? 1 : (int) ($item['is_install'] ?? 0);
        $item['is_enable'] = $enabled ? 1 : (int) ($item['is_enable'] ?? 0);

        return $item;
    }

    /**
     * @param array<string, mixed> $addonInfo
     *
     * @return array<string, mixed>
     */
    private function normalizeLocalAddon(string $code, array $addonInfo, bool $configurable): array
    {
        $enabled = Addon::hasAddon($code);

        return [
            'code' => $code,
            'name' => (string) ($addonInfo['name'] ?? $code),
            'title' => (string) ($addonInfo['title'] ?? $addonInfo['name'] ?? $code),
            'description' => (string) ($addonInfo['description'] ?? $addonInfo['intro'] ?? ''),
            'version' => (string) ($addonInfo['version'] ?? ''),
            'base_path' => (string) ($addonInfo['base_path'] ?? ''),
            'authors' => \is_array($addonInfo['authors'] ?? null) ? $addonInfo['authors'] : [],
            'installed' => 1,
            'enabled' => $enabled ? 1 : 0,
            'disabled' => $enabled ? 0 : 1,
            'is_install' => 1,
            'is_enable' => $enabled ? 1 : 0,
            'develop' => !empty($addonInfo['develop']) ? 1 : 0,
            'configurable' => $configurable ? 1 : 0,
            'has_frontend_modules' => $this->hasAddonModuleManifest($addonInfo) ? 1 : 0,
            'dependencies' => (array) data_get($addonInfo, 'dependencies.plugins', $addonInfo['require'] ?? []),
            'required_satisfied' => $this->dependenciesSatisfied($code, $addonInfo, $enabled) ? 1 : 0,
        ];
    }

    /**
     * @param array<int|string, string> $codes
     *
     * @return array<string, bool>
     */
    private function configurableAddonCodes(array $codes): array
    {
        if ([] === $codes) {
            return [];
        }

        $configurableCodes = SystemConfigGroup::query()
            ->whereIn('addon_code', $codes)
            ->where('status', 1)
            ->pluck('addon_code')
            ->filter()
            ->all();

        return array_fill_keys($configurableCodes, true);
    }

    private function hasAddonConfig(string $code): bool
    {
        return SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('status', 1)
            ->exists();
    }

    /**
     * @param array<string, mixed> $addonInfo
     */
    private function dependenciesSatisfied(string $code, array $addonInfo, bool $enabled): bool
    {
        if ($enabled) {
            return Addon::addonRequired($code);
        }

        $required = (array) data_get($addonInfo, 'dependencies.plugins', $addonInfo['require'] ?? []);
        if ([] === $required) {
            return true;
        }

        foreach ($required as $dependencyCode => $version) {
            if (\is_int($dependencyCode)) {
                if (!Addon::hasInstalledAddon((string) $version)) {
                    return false;
                }

                continue;
            }

            $installedVersion = Addon::getAddonVersion((string) $dependencyCode);
            if (null === $installedVersion || !version_if($installedVersion, (string) $version)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $addonInfo
     */
    private function hasAddonModuleManifest(array $addonInfo): bool
    {
        $manifest = $this->resolveAddonModuleManifestPath($addonInfo);

        return null !== $manifest && is_file($manifest);
    }

    /**
     * @param array<string, mixed> $addonInfo
     */
    private function resolveAddonModuleManifestPath(array $addonInfo): ?string
    {
        $basePath = trim((string) ($addonInfo['base_path'] ?? ''));
        if ('' === $basePath) {
            return null;
        }

        $relative = (string) ($addonInfo['module_manifest'] ?? 'frontend.json');

        return base_path('addons/'.$basePath.'/'.ltrim($relative, '/'));
    }

    private function assertAddonCanUninstall(string $code, bool $force): void
    {
        if ($force) {
            return;
        }

        foreach (Addon::getInstalledAddons() as $addonCode => $addonInfo) {
            if ($addonCode === $code || !\is_array($addonInfo)) {
                continue;
            }

            $required = (array) data_get($addonInfo, 'dependencies.plugins', $addonInfo['require'] ?? []);
            foreach ($required as $dependencyCode => $version) {
                if (\is_int($dependencyCode) && (string) $version === $code) {
                    throw new AddonException(__('ptadmin-addon::messages.addon.dependency_uninstall_first', ['code' => $code]));
                }

                if (!$this->isMatchingAddonDependencyCode($dependencyCode, $code)) {
                    continue;
                }

                throw new AddonException(__('ptadmin-addon::messages.addon.dependency_uninstall_first', ['code' => $code]));
            }
        }
    }

    /**
     * @param int|string $dependencyCode
     */
    private function isMatchingAddonDependencyCode($dependencyCode, string $code): bool
    {
        return !\is_int($dependencyCode) && trim((string) $dependencyCode) === $code;
    }
    
}
