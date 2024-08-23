<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
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
use PTAdmin\Admin\Controllers\Admin;

// 不登录接口地址
Route::group(['prefix' => admin_route_prefix()], function (): void {
    Route::match(['get', 'post'], 'login', [Admin\LoginController::class, 'login'])->name('admin_login');
    Route::post('upload', [Admin\UploadController::class, 'upload']);
    Route::post('upload/tiny', [Admin\UploadController::class, 'tiny']);
});

Route::group(['prefix' => admin_route_prefix(), 'middleware' => ['auth:'.\PTAdmin\Admin\Utils\SystemAuth::getGuard()]], function (): void {
    Route::get('layout', [Admin\HomeController::class, 'layout']);
    Route::get('console', [Admin\HomeController::class, 'console']);
    Route::match(['get', 'post'], 'quick-nav', [Admin\HomeController::class, 'quickNav']);

    Route::get('icon', [Admin\HomeController::class, 'icon']);
    // 附件列表
    Route::get('attachments', [Admin\AttachmentController::class, 'index']);
    Route::delete('attachment/{id?}', [Admin\AttachmentController::class, 'delete']);
    // 选择或者上传附件
    Route::get('attachment', [Admin\AttachmentController::class, 'attachment']);

    // 退出登录
    Route::get('logout', [Admin\LoginController::class, 'logout']);

    // 操作日志
    Route::get('operations', [Admin\OperationRecordController::class, 'index']);
    Route::get('operations/{id}', [Admin\OperationRecordController::class, 'details']);

    // 系统人员登录日志
    Route::get('system/login', [Admin\SystemController::class, 'loginLog']);

    // 系统人员管理
    Route::get('systems', [Admin\SystemController::class, 'index']);
    Route::match(['get', 'put'], 'system/{id}', [Admin\SystemController::class, 'edit']);
    Route::match(['get', 'post'], 'system', [Admin\SystemController::class, 'store']);
    Route::match(['get', 'post'], 'system/password', [Admin\SystemController::class, 'password']);
    Route::post('system-role/{id}', [Admin\SystemController::class, 'setRole']);
    Route::put('systems-status/{id?}', [Admin\SystemController::class, 'status']);
    Route::delete('system/{id?}', [Admin\SystemController::class, 'delete']);
    Route::get('my-permission', [Admin\SystemController::class, 'myPermission']);

    // 系统角色管理
    Route::get('roles', [Admin\RoleController::class, 'index']);
    Route::match(['get', 'put'], 'role/{id}', [Admin\RoleController::class, 'edit']);
    Route::match(['get', 'post'], 'role', [Admin\RoleController::class, 'store']);
    Route::delete('role/{id?}', [Admin\RoleController::class, 'delete']);

    // 角色设置权限
    Route::post('roles-permission/{id}', [Admin\RoleController::class, 'setPermission']);
    Route::get('roles-permission/{id}', [Admin\RoleController::class, 'getPermission']);

    // 权限管理
    Route::match(['get'], 'permissions', [Admin\PermissionController::class, 'index']);
    Route::match(['get', 'post'], 'permission', [Admin\PermissionController::class, 'store']);
    Route::match(['get', 'put'], 'permission/{id}', [Admin\PermissionController::class, 'edit']);
    Route::match(['put'], 'permission-field/{id}', [Admin\PermissionController::class, 'editField']);
    Route::delete('permission/{id?}', [Admin\PermissionController::class, 'delete']);
    Route::get('permissions-lists', [Admin\PermissionController::class, 'lists']);

    // 用户管理
    Route::get('users', [Admin\UserController::class, 'index']);
    Route::match(['get', 'post'], 'user', [Admin\UserController::class, 'store']);
    Route::match(['get', 'put'], 'user/{id}', [Admin\UserController::class, 'edit']);
    Route::put('user-status/{id?}', [Admin\UserController::class, 'status']);
    Route::delete('user/{id?}', [Admin\UserController::class, 'delete']);

    // 环境分类配置
    Route::get('setting-groups', [Admin\SettingGroupController::class, 'index']);
    Route::get('setting-group-full/{id}', [Admin\SettingGroupController::class, 'byConfigureCategoryId']);
    Route::get('setting-group-root/{id}', [Admin\SettingGroupController::class, 'getRootConfigureCategoryId']);
    Route::match(['get', 'post'], 'setting-group', [Admin\SettingGroupController::class, 'store']);
    Route::match(['get', 'put'], 'setting-group/{id}', [Admin\SettingGroupController::class, 'edit']);
    Route::delete('setting-group/{id}', [Admin\SettingGroupController::class, 'delete']);

    // 环境字段配置
    Route::get('settings', [Admin\SettingController::class, 'index']);
    Route::match(['get', 'post'], 'setting', [Admin\SettingController::class, 'store']);
    Route::match(['get', 'put'], 'setting/{id}', [Admin\SettingController::class, 'edit']);
    Route::post('setting-val', [Admin\SettingController::class, 'saveValue']);
    Route::delete('setting/{id}', [Admin\SettingController::class, 'delete']);

    // 插件管理
    Route::get('addons', [Admin\AddonController::class, 'index']);
//    Route::post('addon-local', [Admin\AddonController::class, 'getAddonLocal']);
//    Route::match(['get', 'post'], 'local-addon', [Admin\AddonController::class, 'store']);
//    Route::match(['get', 'post'], 'local-install', [Admin\AddonController::class, 'localInstall']);
//    Route::match(['get', 'put'], 'local-addon-upload/{id}', [Admin\AddonController::class, 'upLoadAddon']);
//    Route::post('local-addon-sql', [Admin\AddonController::class, 'setSql']);
    Route::post('addon-download', [Admin\AddonController::class, 'getAddonDownloadUrl']);
    Route::post('my-addon', [Admin\AddonController::class, 'myAddon']);
//    Route::match(['get', 'post'], 'addon-setting', [Admin\AddonController::class, 'addonSetting']);
//    Route::get('show-image/{code}', [Admin\AddonController::class, 'showImage']);
    Route::delete('addon-uninstall/{code}', [Admin\AddonController::class, 'uninstall']);
    Route::post('addon-cloud', [Admin\AddonController::class, 'addonCloud']);

    Route::match(['get', 'post'], 'cloud-login', [Admin\AddonCloudController::class, 'login']);
    Route::get('cloud-logout', [Admin\AddonCloudController::class, 'logout']);
});
