<?php

declare(strict_types=1);

return [
    'success' => '操作成功',
    'auth' => [
        'super_admin_role_name' => '超级管理员',
        'default_role_description' => '默认初始化角色',
    ],
    'system_config' => [
        'group' => [
            'system_title' => '系统配置',
        ],
        'section' => [
            'basic_title' => '基础配置',
            'basic_intro' => '站点基础信息与通用开关配置',
            'upload_title' => '上传配置',
            'upload_intro' => '文件上传与对象存储相关配置',
        ],
        'field' => [
            'site_title_title' => '站点标题',
            'site_title_intro' => '用于浏览器标题和后台展示',
            'site_description_title' => '站点描述',
            'site_description_intro' => '用于站点介绍与 SEO 描述',
            'login_captcha_title' => '登录验证码',
            'login_captcha_intro' => '控制后台登录是否显示验证码',
            'storage_driver_title' => '上传存储驱动',
            'storage_driver_intro' => 'local 使用本地磁盘；填写插件 storage inject code 时走插件上传',
            'storage_code_title' => '上传存储编码',
            'storage_code_intro' => '可选，显式指定 storage inject code；为空时默认使用上传存储驱动',
            'storage_disk_title' => '上传存储磁盘',
            'storage_disk_intro' => '对象存储磁盘标识，如 oss、cos、s3',
            'storage_bucket_title' => '上传存储桶',
            'storage_bucket_intro' => '对象存储桶名称，不需要时可留空',
            'storage_visibility_title' => '上传可见性',
            'storage_visibility_intro' => '对象存储文件默认可见性',
            'storage_meta_title' => '上传扩展参数',
            'storage_meta_intro' => '传递给插件 storage inject 的扩展参数，JSON 对象格式',
        ],
        'option' => [
            'visibility_public' => '公开',
            'visibility_private' => '私有',
        ],
    ],
    'resource' => [
        'top_level' => '顶级栏目',
    ],
    'command' => [
        'admin_init_success' => '管理员账户初始化成功',
        'admin_init_summary' => '管理员账户信息：',
        'admin_init_username' => '用户账户：:username',
        'admin_init_password' => '用户密码：:password',
        'admin_init_keep_safe' => '请妥善保管好您的账户信息，不要泄露给其他人',
        'admin_auth_bound' => '角色 [:role] 已绑定到后台用户 [:user_id]。',
        'admin_auth_done' => '默认角色 [:role] 初始化完成。',
        'admin_auth_resource_count' => '已授予资源数量：:count',
    ],
    'login_notice' => [
        'title' => '需要登录后台',
        'description' => '当前请求尚未完成后台登录认证。如果你是直接在浏览器中打开后台接口地址，这个页面仅用于辅助排查问题。',
        'api_path' => '后台登录接口：',
        'web_entry' => '后台前端入口：',
        'redirect_target' => '原始请求地址：',
        'open_admin' => '打开后台前端',
        'view_login_api' => '查看登录接口地址',
    ],
];
