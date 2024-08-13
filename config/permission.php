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

return [
    'models' => [
        // 权限表模型
        'permission' => \PTAdmin\Admin\Models\Permission::class,

        // 角色模型
        'role' => \PTAdmin\Admin\Models\Role::class,
    ],

    'table_names' => [
        // 角色表名称
        'roles' => 'roles',
        // 权限表名称
        'permissions' => 'permissions',
        // 模型关联权限表名称
        'model_has_permissions' => 'model_has_permissions',
        // 模型关联角色表名称
        'model_has_roles' => 'model_has_roles',
        // 角色关联权限表名称
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null, // 默认值 'role_id',
        'permission_pivot_key' => null, // 默认值 'permission_id',
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    /*
     * 当设置为 true 时使用守卫检测权限
     * 设置为false时使用自定义权限检测方法
     */

    'register_permission_check_method' => true,

    // 设置为true时 实现团队权限
    'teams' => false,

    // 设置为 true 会在异常信息中添加权限信息，为了避免信息泄漏，建议设置为 false
    'display_permission_in_exception' => false,

    // 设置为 true 会在异常信息中添加角色信息，为了避免信息泄漏，建议设置为 false
    'display_role_in_exception' => false,

    // 默认情况下禁止使用通配符查找权限
    'enable_wildcard_permission' => false,

    // 缓存配置
    'cache' => [
        // 默认情况下，所有权限都会缓存24小时，以提高性能。通过模型编辑权限时，缓存会自动刷新。
        // 当出现直接操作数据库等情况时，需要通过命令行手动刷新缓存。执行方法：php artisan permission:cache-reset 重置缓存
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        // 缓存key
        'key' => 'spatie.permission.cache',

        // 缓存的默认存储驱动
        'store' => 'default',
    ],
];
