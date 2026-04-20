<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\AddonApi;
use PTAdmin\Addon\Service\Action\AddonAction;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class AddonPlatformService
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
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

        foreach (Addon::getInstalledAddons() as $code => $addonInfo) {
            if (!\is_array($addonInfo)) {
                continue;
            }

            $results[] = $this->normalizeLocalAddon((string) $code, $addonInfo);
        }

        usort($results, static function (array $left, array $right): int {
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
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = sprintf('%s_%s.%s', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), uniqid('', true), $file->getClientOriginalExtension());
        $target = $file->move($directory, $filename);
        $code = (string) AddonAction::installLocal($target->getPathname(), $force);
        @unlink($target->getPathname());

        return $this->status($code);
    }

    /**
     * 初始化插件开发脚手架。
     *
     * @return array<string, mixed>
     */
    public function initAddon(string $code, string $title = '', bool $force = false): array
    {
        return (array) AddonAction::init($code, $title, $force);
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

        return $this->normalizeLocalAddon($code, $installed[$code]);
    }

    /**
     * 卸载插件。
     *
     * @return array<string, mixed>
     */
    public function uninstall(string $code, bool $force = false): array
    {
        $addon = $this->status($code);
        AddonAction::uninstall($code, $force);

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
        $section = $this->ensureAddonConfigSection($code);
        if (null === $section) {
            return [
                'code' => $code,
                'supported' => false,
                'group' => null,
                'section' => null,
                'schema' => [
                    'fields' => [],
                ],
                'values' => [],
            ];
        }

        $payload = $this->systemConfigService->section((int) $section->id);
        $payload['code'] = $code;
        $payload['supported'] = true;

        return $payload;
    }

    /**
     * 保存插件通用配置。
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function saveAddonConfig(string $code, array $data): array
    {
        $section = $this->ensureAddonConfigSection($code);
        if (null === $section) {
            throw new BackgroundException(sprintf('插件[%s]未提供通用配置', $code));
        }

        $this->systemConfigService->saveSection((int) $section->id, $data);

        return $this->addonConfig($code);
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
    private function normalizeLocalAddon(string $code, array $addonInfo): array
    {
        $enabled = Addon::hasAddon($code);
        $configurable = $this->hasAddonConfigSection($code) || [] !== $this->getAddonConfigDefaults($code);

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

    private function ensureAddonConfigSection(string $code): ?SystemConfigGroup
    {
        if (!Addon::hasInstalledAddon($code)) {
            throw new BackgroundException(sprintf('插件[%s]不存在', $code));
        }

        if (!Schema::hasTable('system_config_groups') || !Schema::hasTable('system_configs')) {
            return null;
        }

        $defaults = $this->getAddonConfigDefaults($code);
        $section = $this->findAddonConfigSection($code);
        if ([] === $defaults && null === $section) {
            return null;
        }

        $addon = Addon::getInstalledAddons()[$code] ?? [];
        $root = SystemConfigGroup::query()->firstOrCreate(
            [
                'addon_code' => $code,
                'name' => $this->rootGroupName($code),
            ],
            [
                'title' => (string) ($addon['title'] ?? $addon['name'] ?? $code),
                'parent_id' => 0,
                'intro' => (string) ($addon['description'] ?? ''),
                'status' => 1,
                'weight' => 0,
            ]
        );

        $section = SystemConfigGroup::query()->firstOrCreate(
            [
                'addon_code' => $code,
                'name' => 'basic',
            ],
            [
                'title' => '基础配置',
                'parent_id' => (int) $root->id,
                'intro' => '插件通用配置',
                'status' => 1,
                'weight' => 0,
            ]
        );

        if ((int) $section->parent_id !== (int) $root->id) {
            $section->parent_id = (int) $root->id;
            $section->save();
        }

        foreach ($defaults as $name => $value) {
            $this->syncAddonConfigItem($section, (string) $name, $value);
        }

        return $section->refresh();
    }

    private function findAddonConfigSection(string $code): ?SystemConfigGroup
    {
        if (!Schema::hasTable('system_config_groups')) {
            return null;
        }

        return SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('name', 'basic')
            ->first();
    }

    private function hasAddonConfigSection(string $code): bool
    {
        return null !== $this->findAddonConfigSection($code);
    }

    /**
     * @return array<string, mixed>
     */
    private function getAddonConfigDefaults(string $code): array
    {
        $installed = Addon::getInstalledAddons();
        $addon = $installed[$code] ?? null;
        if (!\is_array($addon)) {
            return [];
        }

        $path = base_path('addons/'.($addon['base_path'] ?? '').'/Config/config.php');
        if (!is_file($path)) {
            return [];
        }

        $defaults = require $path;

        return \is_array($defaults) ? $defaults : [];
    }

    /**
     * @param mixed $value
     */
    private function syncAddonConfigItem(SystemConfigGroup $section, string $name, $value): void
    {
        $name = trim($name);
        if ('' === $name) {
            return;
        }

        $type = $this->inferConfigFieldType($value);
        $serialized = $this->serializeConfigValue($value, $type);
        /** @var SystemConfig $config */
        $config = SystemConfig::query()->firstOrNew([
            'system_config_group_id' => (int) $section->id,
            'name' => $name,
        ]);

        $isNew = !$config->exists;
        $config->title = $this->humanizeConfigName($name);
        $config->type = $type;
        $config->intro = '';
        $config->extra = [];
        $config->default_val = $serialized;
        $config->weight = 0;
        if ($isNew) {
            $config->value = $serialized;
        }

        $config->save();
    }

    /**
     * @param mixed $value
     */
    private function inferConfigFieldType($value): string
    {
        if (\is_bool($value)) {
            return 'switch';
        }

        if (\is_array($value)) {
            return 'json';
        }

        return 'text';
    }

    /**
     * @param mixed  $value
     */
    private function serializeConfigValue($value, string $type): string
    {
        if ('switch' === $type) {
            return (bool) $value ? '1' : '0';
        }

        if ('json' === $type) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    private function humanizeConfigName(string $name): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $name));
    }

    private function rootGroupName(string $code): string
    {
        return 'addon_'.$code;
    }
}
