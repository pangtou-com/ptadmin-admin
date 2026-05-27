<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\AdminResourceService;
use PTAdmin\Support\Enums\MenuTypeEnum;

return new class extends Migration
{
    private array $definitions = [
        [
            'name' => 'system',
            'title' => '系统管理',
            'type' => MenuTypeEnum::DIR,
            'route' => '',
            'icon' => 'Setting',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 0,
            ],
        ],
        [
            'name' => 'system.config',
            'title' => '系统配置',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.system-settings',
            'route' => 'system-settings',
            'icon' => 'Tools',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 10,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.admins',
            'title' => '系统用户',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.management',
            'route' => 'admin',
            'icon' => 'User',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 20,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.role',
            'title' => '角色权限',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.role',
            'route' => 'role',
            'icon' => 'Avatar',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 30,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
       
        [
            'name' => 'system.resources',
            'title' => '后台导航',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.permission',
            'route' => 'permission',
            'icon' => 'Lock',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 40,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.assets',
            'title' => '资源管理',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.assets',
            'route' => 'assets',
            'icon' => 'FolderOpened',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 50,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.admin_login_logs',
            'title' => '登录日志',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.login-logs',
            'route' => 'login-logs',
            'icon' => 'Clock',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 60,
            'meta_json' => [
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
        [
            'name' => 'system.operate',
            'title' => '操作日志',
            'parent' => 'system',
            'type' => MenuTypeEnum::NAV,
            'module' => 'admin',
            'page_key' => 'admin.page.operations',
            'route' => 'operations',
            'icon' => 'Operation',
            'is_nav' => 1,
            'status' => 1,
            'sort' => 70,
            'meta_json' => [
                'note' => '查看后端管理操作日志信息',
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ],
    ];

    public function up(): void
    {
        $service = new AdminResourceService();
        $service->registerBatch($this->definitions);
    }

    public function down(): void
    {
        $names = array_column($this->definitions, 'name');
        $resourceIds = AdminResource::query()->whereIn('name', $names)->pluck('id')->all();

        if ([] !== $resourceIds) {
            AdminGrant::query()->whereIn('resource_id', $resourceIds)->delete();
        }

        AdminResource::query()->whereIn('name', $names)->delete();
    }
};
