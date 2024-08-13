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
    'systems' => [
        'username' => '登录账户',
        'nickname' => '用户昵称',
        'is_founder' => '创始人',
        'mobile' => '手机号码',
        'role_id' => '角色',
        'password' => '账户密码',
        'avatar' => '头像',
        'status' => '是否启用',
        'login_at' => '登录时间',
        'login_ip' => '登录ID',
    ],
    'permissions' => [
        'name' => '菜单权限',
        'title' => '菜单名称',
        'route' => '路由地址',
        'parent_id' => '上级栏目',
        'type' => '导航类型',
        'icon' => '菜单图标',
        'sort' => '排序',
        'note' => '备注',
        'is_nav' => '是否导航',
        'status' => '是否启用',
        'is_inner' => '内部链接',
    ],
    'roles' => [
        'name' => '角色名称',
        'title' => '角色标题',
        'note' => '备注',
        'status' => '是否启用',
    ],
    'users' => [
        'username' => '登录账户',
        'nickname' => '用户昵称',
        'email' => '邮箱',
        'mobile' => '手机号码',
        'password' => '用户密码',
        'avatar' => '头像',
        'level' => '用户等级',
        'gender' => '性别',
        'birthday' => '生日',
        'bio' => '个人简介',
        'money' => '账户余额',
        'score' => '账户积分',
        'login_days' => '连续登录天数',
        'max_login_days' => '最大连续登录天数',
        'last_at' => '最后登录时间',
        'pre_at' => '上次登录时间',
        'login_ip' => '登录IP',
        'join_ip' => '注册IP',
        'join_at' => '注册时间',
        'status' => '是否启用',
    ],
    'attachments' => [
        'title' => '附件名称',
        'path' => '附件路径',
        'size' => '附件大小',
        'mime' => 'MIME类型',
        'driver' => '存储驱动',
        'groups' => '附件分组',
        'preview' => '预览',
    ],
    'categories' => [
        'title' => '栏目名称',
        'parent_id' => '上级栏目',
        'channel_model_id' => '模型',
        'dir_name' => '目录名称',
        'dir_path' => '目录路径',
        'intro' => '描述信息',
        'weight' => '权重',
        'status' => '是否启用',
        'link' => '栏目链接',
        'photo' => '栏目图片',
        'template_list' => '列表模版文件名',
        'template_view' => '详情模版文件名',
        'set_title' => 'SEO标题',
        'seo_keywords' => 'SEO关键词',
        'seo_description' => 'SEO描述',
        'is_part' => '外部链接',
    ],
    'channel_models' => [
        'title' => '模型名称',
        'table_name' => '表名称',
        'is_one_page' => '单页模型',
        'is_repeat_title' => '标题重复',
        'status' => '是否启用',
        'is_release' => '会员投稿',
        'weight' => '权重',
        'intro' => '描述',
        'module' => '所属模块',
    ],
    'channel_fields' => [
        'field' => '字段名称',
        'title' => '字段标题',
        'type' => '字段类型',
        'setup' => '字段配置',
        'default_val' => '默认值',
        'tips' => '提示信息',
        'is_required' => '是否必填',
        'is_search' => '是否搜索',
        'is_table' => '是否列表',
        'is_release' => '是否投稿',
        'status' => '是否启用',
        'weight' => '权重',
        'intro' => '描述',
        'channel_model_id' => '所属模型',
    ],
    'archives' => [
        'title' => '标题',
        'category_id' => '所属栏目',
        'channel_model_id' => '所属模型',
        'photo' => '封面图片',
        'content' => '内容',
        'status' => '状态',
        'views' => '浏览量',
    ],
    'setting_groups' => [
        'title' => '配置名称',
        'name' => '配置标识',
        'weight' => '权重',
        'status' => '是否启用',
        'intro' => '描述',
    ],
    'settings' => [
        'title' => '配置名称',
        'name' => '配置标识',
        'type' => '配置属性',
        'weight' => '权重',
        'intro' => '描述',
        'default_val' => '默认值',
        'extra' => '配置项',
    ],
    'addons' => [
        'title' => '插件名称',
        'code' => '插件标识',
        'label' => '标签',
        'type' => '应用分类',
        'version' => '当前版本',
        'require_version' => '依赖的框架版本',
        'requires' => '其他插件依赖信息',
        'rate' => '插件评分',
        'intro' => '插件介绍',
        'cover' => '封面图',
        'picture' => '产品图',
        'developer' => '开发者',
        'doc_url' => '文档地址',
        'views' => '浏览量',
        'real_views' => '真实浏览量',
        'download' => '下载量',
        'real_down' => '真实下载量',
        'amount' => '销售金额',
        'user_id' => '上传人员',
        'status' => '状态',
        'platform_setting' => '平台设置',
        'platform_title' => '平台名称',
        'platform' => '平台类型',
        'demo_url' => '示例地址',
        'demo_type' => '地址类型',
        'content' => '版本更新内容',
        'filepath' => '上传文件',
        'development_language' => '开发语言',
        'publish_status' => '是否发布',
        'published_at' => '发布时间',
        'last_version' => '最后上传版本',
        'weight' => '权重',
        'is_recommend' => '是否推荐',
    ],
    'local_addons' => [
        'title' => '应用名称',
        'code' => '应用标识',
        'version' => '应用版本',
        'framework' => '框架版本',
        'intro' => '插件介绍',
        'email' => '邮箱地址',
        'homepage' => '个人主页',
        'docs' => '文档地址',
        'developer' => '作者',
    ],
];
