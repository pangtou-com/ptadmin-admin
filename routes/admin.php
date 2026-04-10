<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Controllers as Admin;

// 不登录接口地址
Route::group(['prefix' => admin_route_prefix()], function (): void {
    Route::match(['post'], 'login', [Admin\LoginController::class, 'login'])->name('admin_login');
    Route::post('auth/bootstrap-founder', [Admin\AuthorizationController::class, 'bootstrapFounder']);
    Route::post('upload', [Admin\UploadController::class, 'upload']);
    Route::post('upload/tiny', [Admin\UploadController::class, 'tiny']);
});

Route::group(['prefix' => admin_route_prefix(), 'middleware' => ['ptadmin.auth:'.\PTAdmin\Foundation\Auth\AdminAuth::getGuard()]], function (): void {
    // 获取授权菜单树
    Route::get('/user/resources', [Admin\LoginController::class, 'userResources']);
    Route::get('auth/status', [Admin\AuthorizationController::class, 'status']);
    Route::post('auth/bootstrap', [Admin\AuthorizationController::class, 'bootstrap']);

    Route::get('/', [Admin\HomeController::class, 'layout']);
    Route::get('layout', [Admin\HomeController::class, 'layout']);
    Route::get('console', [Admin\HomeController::class, 'console']);

    // 上传资源列表
    Route::get('assets', [Admin\AssetController::class, 'index']);
    Route::delete('assets/{id?}', [Admin\AssetController::class, 'delete']);
    Route::get('assets/picker', [Admin\AssetController::class, 'picker']);

    // 退出登录
    Route::get('logout', [Admin\LoginController::class, 'logout']);

    // 操作日志
    Route::get('operations', [Admin\OperationRecordController::class, 'index']);
    Route::get('operations/{id}', [Admin\OperationRecordController::class, 'details']);

    // 系统人员登录日志
    Route::get('system/login', [Admin\SystemController::class, 'loginLog']);

    // 消息通知
    Route::get('message/unread', [Admin\MessageController::class, 'unread']);

    // 系统人员管理
    Route::get('systems', [Admin\SystemController::class, 'index']);
    Route::get('systems/{id}', [Admin\SystemController::class, 'details']);
    Route::match(['put'], 'systems/{id}', [Admin\SystemController::class, 'edit']);
    Route::match(['post'], 'systems', [Admin\SystemController::class, 'store']);
    Route::match(['post'], 'systems/password', [Admin\SystemController::class, 'password']);
    Route::post('systems-role/{id}', [Admin\SystemController::class, 'setRole']);
    Route::put('systems-status/{id?}', [Admin\SystemController::class, 'status']);
    Route::delete('systems/{id?}', [Admin\SystemController::class, 'delete']);
    Route::get('my-resource', [Admin\SystemController::class, 'myResources']);
    Route::get('systems-org/{id}', [Admin\AdminOrganizationController::class, 'userRelations']);
    Route::post('systems-org/{id}', [Admin\AdminOrganizationController::class, 'syncUserRelations']);
    Route::put('systems-org-primary/{id}', [Admin\AdminOrganizationController::class, 'setPrimaryRelation']);

    // 系统角色管理
    Route::get('roles', [Admin\RoleController::class, 'index']);
    Route::match(['put'], 'roles/{id}', [Admin\RoleController::class, 'edit']);
    Route::match(['put'], 'roles-status/{id}', [Admin\RoleController::class, 'status']);
    Route::match(['post'], 'roles', [Admin\RoleController::class, 'store']);
    Route::delete('roles/{id?}', [Admin\RoleController::class, 'delete']);

    // 角色设置权限
    Route::post('roles-resource/{id}', [Admin\RoleController::class, 'syncRoleResources']);
    Route::get('roles-resource/{id}', [Admin\RoleController::class, 'getRoleResources']);

    // 权限管理
    Route::match(['get'], 'resources', [Admin\AdminResourceController::class, 'index']);
    Route::match(['post'], 'resources', [Admin\AdminResourceController::class, 'store']);
    Route::match(['get'], 'resources/{id}', [Admin\AdminResourceController::class, 'detail']);
    Route::match(['put'], 'resources/{id}', [Admin\AdminResourceController::class, 'edit']);
    Route::match(['put'], 'resource-field/{id}', [Admin\AdminResourceController::class, 'editField']);
    Route::delete('resources/{id?}', [Admin\AdminResourceController::class, 'delete']);
    Route::get('resources-role/{id}', [Admin\AdminResourceController::class, 'getRoleResources']);
    Route::post('resources-role/{id}', [Admin\AdminResourceController::class, 'syncRoleResources']);
    Route::get('resources-system/{id}', [Admin\AdminResourceController::class, 'getSystemResources']);
    Route::post('resources-system/{id}', [Admin\AdminResourceController::class, 'syncSystemResources']);
    Route::get('resources-tree', [Admin\AdminResourceController::class, 'tree']);
    Route::get('resources-lists', [Admin\AdminResourceController::class, 'lists']);

    // 扩展能力：租户、组织、部门
    Route::get('tenants', [Admin\AdminTenantController::class, 'index']);
    Route::post('tenants', [Admin\AdminTenantController::class, 'store']);
    Route::put('tenants/{id}', [Admin\AdminTenantController::class, 'edit']);
    Route::delete('tenants/{id?}', [Admin\AdminTenantController::class, 'delete']);
    Route::get('organizations', [Admin\AdminOrganizationController::class, 'organizations']);
    Route::post('organizations', [Admin\AdminOrganizationController::class, 'storeOrganization']);
    Route::put('organizations/{id}', [Admin\AdminOrganizationController::class, 'editOrganization']);
    Route::get('departments', [Admin\AdminOrganizationController::class, 'departments']);
    Route::post('departments', [Admin\AdminOrganizationController::class, 'storeDepartment']);
    Route::put('departments/{id}', [Admin\AdminOrganizationController::class, 'editDepartment']);

    // 用户管理
    Route::get('users', [Admin\UserController::class, 'index']);
    Route::match(['get', 'post'], 'user', [Admin\UserController::class, 'store']);
    Route::match(['get', 'put'], 'user/{id}', [Admin\UserController::class, 'edit']);
    Route::put('user-status/{id?}', [Admin\UserController::class, 'status']);
    Route::delete('user/{id?}', [Admin\UserController::class, 'delete']);

    // 环境分类配置
    Route::get('system-config-groups', [Admin\SystemConfigGroupController::class, 'index']);
    Route::get('system-config-groups/{id}/sections', [Admin\SystemConfigGroupController::class, 'sectionConfigs']);
    Route::get('system-config-groups/{id}/children', [Admin\SystemConfigGroupController::class, 'children']);
    Route::post('system-config-groups', [Admin\SystemConfigGroupController::class, 'store']);
    Route::put('system-config-groups/{id}', [Admin\SystemConfigGroupController::class, 'edit']);
    Route::delete('system-config-groups/{id}', [Admin\SystemConfigGroupController::class, 'delete']);

    // 系统配置
    Route::get('system-configs', [Admin\SystemConfigController::class, 'index']);
    Route::get('system-configs/{id}', [Admin\SystemConfigController::class, 'details']);
    Route::put('system-configs/{id}', [Admin\SystemConfigController::class, 'updateSection']);
    Route::get('system-config-items', [Admin\SystemConfigController::class, 'items']);
    Route::post('system-config-items/values', [Admin\SystemConfigController::class, 'saveValues']);
    Route::post('system-config-items', [Admin\SystemConfigController::class, 'store']);
    Route::put('system-config-items/{id}', [Admin\SystemConfigController::class, 'edit']);
    Route::delete('system-config-items/{id}', [Admin\SystemConfigController::class, 'delete']);

    // 插件管理
    Route::get('addons', [Admin\AddonController::class, 'index']);
    Route::post('addon-download', [Admin\AddonController::class, 'getAddonDownloadUrl']);
    Route::post('my-addon', [Admin\AddonController::class, 'myAddon']);
    Route::delete('addon-uninstall/{code}', [Admin\AddonController::class, 'uninstall']);
    Route::post('addon-cloud', [Admin\AddonController::class, 'addonCloud']);

    Route::match(['get', 'post'], 'cloud-login', [Admin\AddonCloudController::class, 'login']);
    Route::get('cloud-logout', [Admin\AddonCloudController::class, 'logout']);
});
