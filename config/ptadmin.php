<?php

declare(strict_types=1);

use PTAdmin\Admin\Services\Auth\Resolvers\BasicGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\DataScopeResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\OrganizationResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\TenantGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\WorkflowGuardResolver;

return [
    'guard' => env('PTADMIN_GUARD', config('auth.app_guard_name', 'api')),
    'api_prefix' => 'ptadmin',
    'web_prefix' => env('PTADMIN_WEB_PREFIX', 'admin'),
    'web_asset_path' => env('PTADMIN_WEB_ASSET_PATH', 'vendor/ptadmin/admin'),
    'module_manifest_cache_ttl' => (int) env('PTADMIN_MODULE_MANIFEST_CACHE_TTL', 300),
    'project_frontend_code' => env('PTADMIN_PROJECT_FRONTEND_CODE', '__app__'),
    'project_frontend_dev_url' => env('PTADMIN_PROJECT_FRONTEND_DEV_URL', ''),
    'project_frontend_manifest' => env('PTADMIN_PROJECT_FRONTEND_MANIFEST', base_path('resources/ptadmin/frontend/frontend.json')),
    'project_frontend_dist_path' => env('PTADMIN_PROJECT_FRONTEND_DIST_PATH', base_path('resources/ptadmin/frontend/dist')),
    'project_frontend_storage_path' => env('PTADMIN_PROJECT_FRONTEND_STORAGE_PATH', storage_path('app/ptadmin/modules/'.env('PTADMIN_PROJECT_FRONTEND_CODE', '__app__'))),
    'platform_snapshot_path' => env('PTADMIN_PLATFORM_SNAPSHOT_PATH', storage_path('app/ptadmin/platform/snapshot.json')),
    'platform_snapshot_ttl' => (int) env('PTADMIN_PLATFORM_SNAPSHOT_TTL', 86400),
    'route_prefix' => 'ptadmin',
    'addons_path' => env('PTADMIN_ADDONS_PATH', base_path('addons')),
    'addons_storage_path' => env('PTADMIN_ADDONS_STORAGE_PATH', storage_path('app/ptadmin/modules')),
    'upload_local_disk' => env('PTADMIN_UPLOAD_LOCAL_DISK', 'public'),
    'fix_directory_mode' => env('PTADMIN_FIX_DIRECTORY_MODE', '0775'),
    'fix_file_mode' => env('PTADMIN_FIX_FILE_MODE', '0664'),
    'fix_paths' => [
        'storage' => [
            'path' => storage_path(),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
        'bootstrap_cache' => [
            'path' => base_path('bootstrap/cache'),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
        'bootstrap_addons_cache' => [
            'path' => base_path('bootstrap/cache/addons.php'),
            'type' => 'file',
        ],
        'ptadmin_storage' => [
            'path' => storage_path('app/ptadmin'),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
        'admin_frontend_current' => [
            'path' => storage_path('app/ptadmin/frontend/admin/current'),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
        'admin_public' => [
            'path' => public_path(env('PTADMIN_WEB_PREFIX', 'admin')),
            'type' => 'directory',
            'recursive' => true,
            'create' => false,
        ],
        'addons_storage' => [
            'path' => env('PTADMIN_ADDONS_STORAGE_PATH', storage_path('app/ptadmin/modules')),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
        'project_frontend_storage' => [
            'path' => env('PTADMIN_PROJECT_FRONTEND_STORAGE_PATH', storage_path('app/ptadmin/modules/'.env('PTADMIN_PROJECT_FRONTEND_CODE', '__app__'))),
            'type' => 'directory',
            'recursive' => true,
            'create' => true,
        ],
    ],

    'notifications' => [
        'delivery' => [
            'enabled' => (bool) env('PTADMIN_NOTIFICATION_DELIVERY_ENABLED', true),
            'sync' => (bool) env('PTADMIN_NOTIFICATION_DELIVERY_SYNC', true),
        ],
        'channels' => [
            'admin' => [],
            'user' => [],
        ],
        'providers' => [
            'mail' => [
                'driver' => 'mail',
                'enabled' => (bool) env('PTADMIN_NOTIFICATION_MAIL_ENABLED', true),
            ],
        ],
    ],

    'capabilities' => [
        'rbac' => true,
        'organization' => false,
        'tenant' => false,
        'data_scope' => false,
        'workflow' => false,
    ],

    'resolvers' => [
        BasicGrantResolver::class,
        TenantGrantResolver::class,
        OrganizationResolver::class,
        DataScopeResolver::class,
        WorkflowGuardResolver::class,
    ],
    'setting_type' => [
        ['label' => "系统设置", "value" => "system", "sort" => 0],
        ['label' => "授权服务", "value" => "auth", "sort" => 1],
        ['label' => "短信服务", "value" => "sms", "sort" => 2],
        ['label' => "支付服务", "value" => "pay", "sort" => 3],
        ['label' => "存储服务", "value" => "storage", "sort" => 4],
        ['label' => "AI服务", "value" => "ai", "sort" => 5],
    ],
];
