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
    Route::get('login', [Admin\LoginController::class, 'notice'])->name('admin_login_notice');
    Route::match(['post'], 'login', [Admin\LoginController::class, 'login'])->name('admin_login');
});

Route::group(['prefix' => admin_route_prefix(), 'middleware' => ['ptadmin.auth:'.\PTAdmin\Foundation\Auth\AdminAuth::getGuard()]], function (): void {
    // 仪表盘配置
    admin_audit_route(Route::get('dashboard', [Admin\DashboardController::class, 'console']), 'console');
    
    // 模块加载，返回给前端已安装的模块信息
    Route::get('auth/frontends', [Admin\AddonController::class, 'moduleManifests']);
    // 获取授权菜单树
    Route::get('auth/resources', [Admin\LoginController::class, 'adminResources']);
    Route::get('auth/status', [Admin\AuthorizationController::class, 'status']);
    Route::get('auth/profile', [Admin\AuthorizationController::class, 'profile']);
    admin_audit_route(Route::get('auth/profile/login-logs', [Admin\AuthorizationController::class, 'loginLogs']), 'system.admin_login_logs');
    admin_audit_route(Route::get('auth/profile/operations', [Admin\AuthorizationController::class, 'operations']), 'system.operate');
    admin_audit_route(Route::get('auth/profile/operations/{id}', [Admin\AuthorizationController::class, 'operationDetails']), 'system.operate');
    admin_audit_route(Route::put('auth/password', [Admin\AuthorizationController::class, 'password']), 'system.admins');
    admin_audit_route(Route::put('auth/profile', [Admin\AuthorizationController::class, 'updateProfile']), 'system.admins');

    admin_audit_route(Route::post('upload', [Admin\UploadController::class, 'upload']), 'system.assets');
    admin_audit_route(Route::post('upload/tiny', [Admin\UploadController::class, 'tiny']), 'system.assets');

    // 上传资源列表
    admin_audit_route(Route::get('assets', [Admin\AssetController::class, 'index']), 'system.assets');
    admin_audit_route(Route::delete('assets/{id?}', [Admin\AssetController::class, 'delete']), 'system.assets');
    admin_audit_route(Route::get('assets/picker', [Admin\AssetController::class, 'picker']), 'system.assets');

    // 退出登录
    Route::get('logout', [Admin\LoginController::class, 'logout']);

    // 操作日志
    admin_audit_route(Route::get('operations', [Admin\OperationRecordController::class, 'index']), 'system.operate');
    admin_audit_route(Route::get('operations/{id}', [Admin\OperationRecordController::class, 'details']), 'system.operate');

    // 后台管理员登录日志
    admin_audit_route(Route::get('admins/login-logs', [Admin\AdminController::class, 'loginLog']), 'system.admin_login_logs');

    // 消息通知
    Route::get('message/unread', [Admin\MessageController::class, 'unread']);

    // 后台管理员管理
    admin_audit_route(Route::get('admins', [Admin\AdminController::class, 'index']), 'system.admins');
    admin_audit_route(Route::get('admins/{id}', [Admin\AdminController::class, 'details']), 'system.admins');
    admin_audit_route(Route::match(['put'], 'admins/{id}', [Admin\AdminController::class, 'edit']), 'system.admins');
    admin_audit_route(Route::match(['post'], 'admins', [Admin\AdminController::class, 'store']), 'system.admins');
    admin_audit_route(Route::match(['post'], 'admins/password', [Admin\AdminController::class, 'password']), 'system.admins');
    admin_audit_route(Route::post('admins-role/{id}', [Admin\AdminController::class, 'setRole']), 'system.admins');
    admin_audit_route(Route::put('admins-status/{id?}', [Admin\AdminController::class, 'status']), 'system.admins');
    admin_audit_route(Route::delete('admins/{id?}', [Admin\AdminController::class, 'delete']), 'system.admins');
    admin_audit_route(Route::get('my-resource', [Admin\AdminController::class, 'myResources']), 'system.admins');
    admin_audit_route(Route::get('admins-org/{id}', [Admin\AdminOrganizationController::class, 'userRelations']), 'system.admins');
    admin_audit_route(Route::post('admins-org/{id}', [Admin\AdminOrganizationController::class, 'syncUserRelations']), 'system.admins');
    admin_audit_route(Route::put('admins-org-primary/{id}', [Admin\AdminOrganizationController::class, 'setPrimaryRelation']), 'system.admins');

    // 系统角色管理
    admin_audit_route(Route::get('roles', [Admin\RoleController::class, 'index']), 'system.role');
    admin_audit_route(Route::match(['put'], 'roles/{id}', [Admin\RoleController::class, 'edit']), 'system.role');
    admin_audit_route(Route::match(['put'], 'roles-status/{id}', [Admin\RoleController::class, 'status']), 'system.role');
    admin_audit_route(Route::match(['post'], 'roles', [Admin\RoleController::class, 'store']), 'system.role');
    admin_audit_route(Route::delete('roles/{id?}', [Admin\RoleController::class, 'delete']), 'system.role');

    // 角色设置权限
    admin_audit_route(Route::post('roles-resource/{id}', [Admin\RoleController::class, 'syncRoleResources']), 'system.role');
    admin_audit_route(Route::get('roles-resource/{id}', [Admin\RoleController::class, 'getRoleResources']), 'system.role');

    // 权限管理
    admin_audit_route(Route::match(['get'], 'admin-resources', [Admin\AdminResourceController::class, 'index']), 'system.resources');
    admin_audit_route(Route::match(['post'], 'admin-resources', [Admin\AdminResourceController::class, 'store']), 'system.resources');
    admin_audit_route(Route::match(['get'], 'admin-resources/{id}', [Admin\AdminResourceController::class, 'detail']), 'system.resources');
    admin_audit_route(Route::match(['put'], 'admin-resources/{id}', [Admin\AdminResourceController::class, 'edit']), 'system.resources');
    admin_audit_route(Route::delete('admin-resources/{id?}', [Admin\AdminResourceController::class, 'delete']), 'system.resources');
    admin_audit_route(Route::get('resources-role/{id}', [Admin\AdminResourceController::class, 'getRoleResources']), 'system.resources');
    admin_audit_route(Route::post('resources-role/{id}', [Admin\AdminResourceController::class, 'syncRoleResources']), 'system.resources');
    admin_audit_route(Route::get('resources-admin/{id}', [Admin\AdminResourceController::class, 'getAdminResources']), 'system.resources');
    admin_audit_route(Route::post('resources-admin/{id}', [Admin\AdminResourceController::class, 'syncAdminResources']), 'system.resources');
    admin_audit_route(Route::get('admin-resources-tree', [Admin\AdminResourceController::class, 'tree']), 'system.resources');
    admin_audit_route(Route::get('resources-lists', [Admin\AdminResourceController::class, 'lists']), 'system.resources');

    // 后台仪表盘组件
    admin_audit_route(Route::get('dashboard/widgets', [Admin\AdminDashboardController::class, 'widgets']), 'console');
    admin_audit_route(Route::post('dashboard/widgets/{code}/query', [Admin\AdminDashboardController::class, 'query']), 'console');
    admin_audit_route(Route::post('dashboard/widgets/{code}/actions/{action}', [Admin\AdminDashboardController::class, 'action']), 'console');
    admin_audit_route(Route::get('dashboard/roles/{id}/widgets', [Admin\AdminDashboardManageController::class, 'roleWidgets']), 'console');
    admin_audit_route(Route::put('dashboard/roles/{id}/widgets', [Admin\AdminDashboardManageController::class, 'saveRoleWidgets']), 'console');
    admin_audit_route(Route::get('dashboard/users/{id}/widgets', [Admin\AdminDashboardManageController::class, 'userWidgets']), 'console');
    admin_audit_route(Route::put('dashboard/users/{id}/widgets', [Admin\AdminDashboardManageController::class, 'saveUserWidgets']), 'console');
    admin_audit_route(Route::get('dashboard/me/widgets', [Admin\AdminDashboardManageController::class, 'meWidgets']), 'console');
    admin_audit_route(Route::put('dashboard/me/widgets', [Admin\AdminDashboardManageController::class, 'saveMeWidgets']), 'console');

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
    admin_audit_route(Route::get('users', [Admin\UserController::class, 'index']), 'user.users');
    admin_audit_route(Route::match(['get', 'post'], 'user', [Admin\UserController::class, 'store']), 'user.users');
    admin_audit_route(Route::match(['get', 'put'], 'user/{id}', [Admin\UserController::class, 'edit']), 'user.users');
    admin_audit_route(Route::put('user-status/{id?}', [Admin\UserController::class, 'status']), 'user.users');
    admin_audit_route(Route::delete('user/{id?}', [Admin\UserController::class, 'delete']), 'user.users');

    // 配置管理模块
    admin_audit_route(Route::get('settings/catalog', [Admin\SettingsController::class, 'systemCatalog']), 'system.config');
    admin_audit_route(Route::get('settings/system/catalog', [Admin\SettingsController::class, 'systemCatalog']), 'system.config');
    admin_audit_route(Route::get('settings/system/sections/{sectionKey}', [Admin\SettingsController::class, 'systemSection']), 'system.config');
    admin_audit_route(Route::put('settings/system/sections/{sectionKey}', [Admin\SettingsController::class, 'saveSystemSection']), 'system.config');
    admin_audit_route(Route::get('settings/plugins/catalog', [Admin\SettingsController::class, 'pluginCatalog']), 'system.config');
    admin_audit_route(Route::get('settings/plugins/{code}/sections/{sectionKey}', [Admin\SettingsController::class, 'pluginSection']), 'system.config');
    admin_audit_route(Route::put('settings/plugins/{code}/sections/{sectionKey}', [Admin\SettingsController::class, 'savePluginSection']), 'system.config');

    // 本地服务
    admin_audit_route(Route::get('cloud/local/apps', [Admin\AddonController::class, 'local']), 'cloud.apps');
    // 云服务
    admin_audit_route(Route::get('cloud/market/services', [Admin\AddonController::class, 'cloud']), 'cloud.market');
    admin_audit_route(Route::get('addons/cloud/me', [Admin\AddonController::class, 'cloudMine']), 'cloud.market');
    admin_audit_route(Route::get('addons/{code}/status', [Admin\AddonController::class, 'status']), 'cloud.apps');
    admin_audit_route(Route::get('addons/{code}/config', [Admin\AddonController::class, 'config']), 'cloud.apps');
    admin_audit_route(Route::put('addons/{code}/config', [Admin\AddonController::class, 'saveConfig']), 'cloud.apps');
    admin_audit_route(Route::post('addons/{code}/enable', [Admin\AddonController::class, 'enable']), 'cloud.apps');
    admin_audit_route(Route::post('addons/{code}/disable', [Admin\AddonController::class, 'disable']), 'cloud.apps');
    admin_audit_route(Route::post('addons/{code}/upgrade', [Admin\AddonController::class, 'upgrade']), 'cloud.apps');
    admin_audit_route(Route::post('addons/{code}/frontend/pull', [Admin\AddonController::class, 'pullFrontend']), 'cloud.apps');
    admin_audit_route(Route::post('addons/{code}/resources/sync', [Admin\AddonController::class, 'syncResources']), 'cloud.apps');
    admin_audit_route(Route::post('addons/init', [Admin\AddonController::class, 'init']), 'cloud.apps');
    admin_audit_route(Route::post('addons/install/cloud', [Admin\AddonController::class, 'installCloud']), 'cloud.market');
    admin_audit_route(Route::post('addons/install/local', [Admin\AddonController::class, 'localInstall']), 'cloud.apps');
    admin_audit_route(Route::post('addon-download', [Admin\AddonController::class, 'getAddonDownloadUrl']), 'cloud.market');
    admin_audit_route(Route::get('my-addon', [Admin\AddonController::class, 'myAddon']), 'cloud.apps');
    admin_audit_route(Route::delete('addon-uninstall/{code}', [Admin\AddonController::class, 'uninstall']), 'cloud.apps');
    admin_audit_route(Route::post('addons/cloud/login', [Admin\AddonCloudController::class, 'login']), 'cloud.market');
    admin_audit_route(Route::post('addons/cloud/logout', [Admin\AddonCloudController::class, 'logout']), 'cloud.market');
});
