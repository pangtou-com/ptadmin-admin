<?php

declare(strict_types=1);

use PTAdmin\Admin\Services\Auth\Resolvers\BasicGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\DataScopeResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\OrganizationResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\TenantGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\WorkflowGuardResolver;

return [
    'guard' => env('PTADMIN_GUARD', config('auth.app_guard_name', 'api')),
    'api_prefix' => env('PTADMIN_API_PREFIX', env('PTADMIN_ROUTE_PREFIX', config('app.prefix', 'system'))),
    'web_prefix' => env('PTADMIN_WEB_PREFIX', 'admin'),
    'web_asset_path' => env('PTADMIN_WEB_ASSET_PATH', 'vendor/ptadmin/admin'),
    'module_manifest_cache_ttl' => (int) env('PTADMIN_MODULE_MANIFEST_CACHE_TTL', 300),
    'project_frontend_code' => env('PTADMIN_PROJECT_FRONTEND_CODE', '__app__'),
    'project_frontend_dev_url' => env('PTADMIN_PROJECT_FRONTEND_DEV_URL', ''),
    'project_frontend_manifest' => env('PTADMIN_PROJECT_FRONTEND_MANIFEST', base_path('resources/ptadmin/frontend/frontend.json')),
    'project_frontend_dist_path' => env('PTADMIN_PROJECT_FRONTEND_DIST_PATH', base_path('resources/ptadmin/frontend/dist')),
    'project_frontend_storage_path' => env('PTADMIN_PROJECT_FRONTEND_STORAGE_PATH', storage_path('app/ptadmin/modules/'.env('PTADMIN_PROJECT_FRONTEND_CODE', '__app__'))),
    'route_prefix' => env('PTADMIN_API_PREFIX', env('PTADMIN_ROUTE_PREFIX', config('app.prefix', 'system'))),
    'addons_path' => env('PTADMIN_ADDONS_PATH', base_path('addons')),
    'addons_storage_path' => env('PTADMIN_ADDONS_STORAGE_PATH', storage_path('app/ptadmin/modules')),
    'upload_local_disk' => env('PTADMIN_UPLOAD_LOCAL_DISK', 'public'),

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
        ['label' => "授权能力", "value" => "auth", "sort" => 1],
        ['label' => "短信能力", "value" => "sms", "sort" => 2],
        ['label' => "支付能力", "value" => "pay", "sort" => 3],
        ['label' => "业务扩展", "value" => "addon", "sort" => 4],
    ],
];
