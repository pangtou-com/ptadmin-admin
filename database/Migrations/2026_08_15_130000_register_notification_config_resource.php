<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\AdminResourceService;
use PTAdmin\Support\Enums\MenuTypeEnum;

return new class extends Migration
{
    private array $definitions = [[
        'name' => 'system.notification_config',
        'title' => '通知配置',
        'parent' => 'system',
        'type' => MenuTypeEnum::NAV,
        'module' => 'admin',
        'page_key' => 'admin.page.notification-config',
        'route' => 'notification-config',
        'icon' => 'Bell',
        'is_nav' => 1,
        'status' => 1,
        'sort' => 15,
        'meta_json' => [
            'hidden' => 0,
            'keep_alive' => 1,
        ],
    ]];

    public function up(): void
    {
        (new AdminResourceService())->registerBatch($this->definitions);
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
