<?php

declare(strict_types=1);

use PTAdmin\Admin\Services\Auth\Resolvers\BasicGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\DataScopeResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\FieldAclResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\OrganizationResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\TenantGrantResolver;
use PTAdmin\Admin\Services\Auth\Resolvers\WorkflowGuardResolver;

return [
    'guard' => env('PTADMIN_GUARD', config('auth.app_guard_name', 'api')),
    'route_prefix' => env('PTADMIN_ROUTE_PREFIX', config('app.prefix', 'system')),
    'addons_path' => env('PTADMIN_ADDONS_PATH', base_path('addons')),
    'addons_storage_path' => env('PTADMIN_ADDONS_STORAGE_PATH', storage_path('app/addons')),

    'capabilities' => [
        'rbac' => true,
        'organization' => false,
        'tenant' => false,
        'data_scope' => false,
        'field_acl' => false,
        'workflow' => false,
    ],

    'resolvers' => [
        BasicGrantResolver::class,
        TenantGrantResolver::class,
        OrganizationResolver::class,
        DataScopeResolver::class,
        FieldAclResolver::class,
        WorkflowGuardResolver::class,
    ],
];
