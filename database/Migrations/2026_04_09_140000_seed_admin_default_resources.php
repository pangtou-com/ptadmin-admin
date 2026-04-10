<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\AdminResourceService;
use PTAdmin\Support\Enums\ResourceType;

return new class extends Migration
{
    private array $definitions = [
        [
            'code' => 'console',
            'name' => '仪表盘',
            'type' => ResourceType::PAGE,
            'route' => 'console',
            'icon' => 'layui-icon layui-icon-console',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'user',
            'name' => '用户管理',
            'type' => ResourceType::MENU,
            'icon' => 'layui-icon layui-icon-table',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'user.users',
            'name' => '会员列表',
            'parent' => 'user',
            'type' => ResourceType::PAGE,
            'route' => 'users',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system',
            'name' => '系统管理',
            'type' => ResourceType::MENU,
            'icon' => 'layui-icon layui-icon-engine',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system.role',
            'name' => '系统角色',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'roles',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system.system',
            'name' => '系统管理员',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'systems',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system.resources',
            'name' => '菜单资源',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'resources',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system.login',
            'name' => '登录日志',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'system/login',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
            'meta_json' => [
                'note' => '查看后端用户的登录日志信息',
            ],
        ],
        [
            'code' => 'system.operate',
            'name' => '操作日志',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'operations',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
            'meta_json' => [
                'note' => '查看后端管理操作日志信息',
            ],
        ],
        [
            'code' => 'system.config',
            'name' => '系统配置',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'system-configs',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'system.attachments',
            'name' => '附件管理',
            'parent' => 'system',
            'type' => ResourceType::PAGE,
            'route' => 'attachments',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'addon',
            'name' => '插件管理',
            'type' => ResourceType::MENU,
            'icon' => 'layui-icon layui-icon-align-left',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
        [
            'code' => 'addon.addons',
            'name' => '插件列表',
            'parent' => 'addon',
            'type' => ResourceType::PAGE,
            'route' => 'addons',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('admin_resources')) {
            return;
        }

        $service = new AdminResourceService();
        $service->registerBatch($this->definitions);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_resources')) {
            return;
        }

        $codes = array_column($this->definitions, 'code');
        $resourceIds = AdminResource::query()->whereIn('code', $codes)->pluck('id')->all();

        if ([] !== $resourceIds) {
            AdminGrant::query()->whereIn('resource_id', $resourceIds)->delete();
        }

        AdminResource::query()->whereIn('code', $codes)->delete();
    }
};
