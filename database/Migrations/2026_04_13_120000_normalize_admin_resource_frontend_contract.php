<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\AdminResourceService;
use PTAdmin\Support\Enums\ResourceType;

return new class extends Migration
{
    private array $definitions = [
        [
            'name' => 'console',
            'title' => '仪表盘',
            'type' => ResourceType::PAGE,
            'module' => 'dashboard',
            'page_key' => 'console.dashboard',
            'route' => '/dashboard',
            'icon' => 'HomeFilled',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 10,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'user',
            'title' => '用户管理',
            'type' => ResourceType::MENU,
            'icon' => 'UserFilled',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 15,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 0,
            ],
        ],
        [
            'name' => 'user.users',
            'title' => '会员列表',
            'parent' => 'user',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'user.users',
            'route' => '/users',
            'icon' => 'User',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 10,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system',
            'title' => '系统管理',
            'type' => ResourceType::MENU,
            'route' => '/admin',
            'icon' => 'Setting',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 20,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 0,
            ],
        ],
        [
            'name' => 'system.admins',
            'title' => '后台管理员',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.admins',
            'route' => 'admin',
            'icon' => 'User',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 10,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.role',
            'title' => '系统角色',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.role',
            'route' => 'role',
            'icon' => 'Avatar',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 20,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.resources',
            'title' => '菜单资源',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.resources',
            'route' => 'permission',
            'icon' => 'Lock',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 30,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.admin_login_logs',
            'title' => '登录日志',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.admin_login_logs',
            'route' => 'login-logs',
            'icon' => 'Clock',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 40,
            'meta_json' => [
                'note' => '查看后端用户的登录日志信息',
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.operate',
            'title' => '操作日志',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.operate',
            'route' => 'operations',
            'icon' => 'Operation',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 50,
            'meta_json' => [
                'note' => '查看后端管理操作日志信息',
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.config',
            'title' => '系统配置',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.config',
            'route' => 'system-configs',
            'icon' => 'Tools',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 60,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.assets',
            'title' => '资源管理',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'module' => 'admin',
            'page_key' => 'system.assets',
            'route' => 'assets',
            'icon' => 'FolderOpened',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 70,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'cloud',
            'title' => '云平台',
            'type' => ResourceType::MENU,
            'route' => '/cloud',
            'icon' => 'Connection',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 30,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 0,
            ],
        ],
        [
            'name' => 'cloud.market',
            'title' => '云市场',
            'parent' => 'cloud',
            'type' => ResourceType::PAGE,
            'module' => 'cloud',
            'page_key' => 'cloud.market',
            'route' => 'market',
            'icon' => 'Grid',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 10,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'cloud.apps',
            'title' => '本地应用中心',
            'parent' => 'cloud',
            'type' => ResourceType::PAGE,
            'module' => 'cloud',
            'page_key' => 'cloud.apps',
            'route' => 'apps',
            'icon' => 'Box',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 20,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
    ];

    public function up(): void
    {
        $this->renameLegacyCloudResources();

        $service = new AdminResourceService();
        $service->registerBatch($this->definitions);

        AdminResource::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->each(function (AdminResource $resource): void {
                $changes = [];
                $route = trim((string) ($resource->route ?? ''));
                if ('' !== $route && !$this->isExternalRoute($route) && 0 === (int) $resource->parent_id && !str_starts_with($route, '/')) {
                    $changes['route'] = '/'.$route;
                }

                $metaJson = (array) ($resource->meta_json ?? []);
                $normalizedMetaJson = $metaJson;
                if (array_key_exists('redirect', $normalizedMetaJson)) {
                    $redirect = trim((string) $normalizedMetaJson['redirect']);
                    if ('' === $redirect) {
                        $normalizedMetaJson['redirect'] = null;
                    } elseif (!$this->isExternalRoute($redirect) && 0 === (int) $resource->parent_id && !str_starts_with($redirect, '/')) {
                        $normalizedMetaJson['redirect'] = '/'.$redirect;
                    }
                }

                $normalizedMetaJson['hidden'] = isset($normalizedMetaJson['hidden']) ? (int) $normalizedMetaJson['hidden'] : 0;
                $normalizedMetaJson['keep_alive'] = isset($normalizedMetaJson['keep_alive'])
                    ? (int) $normalizedMetaJson['keep_alive']
                    : $this->defaultKeepAlive((string) $resource->type);

                if ($normalizedMetaJson !== $metaJson) {
                    $changes['meta_json'] = $normalizedMetaJson;
                }

                if ([] === $changes) {
                    return;
                }

                $changes['updated_at'] = time();
                $resource->fill($changes);
                $resource->save();
            });
    }

    public function down(): void
    {
    }

    private function renameLegacyCloudResources(): void
    {
        /** @var null|AdminResource $legacyCloud */
        $legacyCloud = AdminResource::query()->whereNull('deleted_at')->where('name', 'addon')->first();
        /** @var null|AdminResource $cloud */
        $cloud = AdminResource::query()->whereNull('deleted_at')->where('name', 'cloud')->first();

        if (null !== $legacyCloud && null === $cloud) {
            $legacyCloud->fill([
                'name' => 'cloud',
                'title' => '云平台',
                'module' => '',
                'page_key' => null,
                'route' => '/cloud',
                'icon' => 'Connection',
                'updated_at' => time(),
            ]);
            $legacyCloud->save();
            $cloud = $legacyCloud->fresh();
        }

        if (null === $cloud) {
            return;
        }

        /** @var null|AdminResource $legacyCloudApps */
        $legacyCloudApps = AdminResource::query()->whereNull('deleted_at')->where('name', 'addon.addons')->first();
        /** @var null|AdminResource $cloudApps */
        $cloudApps = AdminResource::query()->whereNull('deleted_at')->where('name', 'cloud.apps')->first();
        if (null !== $legacyCloudApps && null === $cloudApps) {
            $legacyCloudApps->fill([
                'name' => 'cloud.apps',
                'title' => '本地应用中心',
                'parent_id' => (int) $cloud->id,
                'module' => 'cloud',
                'page_key' => 'cloud.apps',
                'route' => 'apps',
                'icon' => 'Box',
                'updated_at' => time(),
            ]);
            $legacyCloudApps->save();
        }
    }

    private function defaultKeepAlive(string $type): int
    {
        return \in_array($type, [ResourceType::PAGE, ResourceType::ROUTE], true) ? 1 : 0;
    }

    private function isExternalRoute(string $route): bool
    {
        return str_starts_with($route, 'http://') || str_starts_with($route, 'https://');
    }
};
