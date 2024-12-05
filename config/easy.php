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
    'table_name' => [
        'mod' => 'mods',
        'mod_field' => 'mod_fields',
    ],
    'cache' => [
        // 缓存key
        'key' => '__ptadmin.easy.cache__',
        // 缓存驱动
        'store' => 'file',
        // 缓存过期时间默认为30天
        'expiration_time' => \DateInterval::createFromDateString('30 days'),
    ],
    // 模型组件在新增时除默认字段外还会存在每个组件所独有的信息.
    // 如：`text`组件, 会存在一个`length`字段用于表示字符串长度。
    // 各个组件的扩展字段信息可以在这里配置
    // 配置方式支持：
    // 1、数组形式，2、类形式, 3、模版页面
    'extend' => [
        'text' => [
            ['type' => 'number', 'title' => '长度', 'name' => 'length', 'default' => 255],
        ],
    ],
    // 模型构建的时候默认的字段信息
    'data_form' => [
        'mod' => [
            ['type' => 'text', 'title' => '模型名称', 'name' => 'title'],
            ['type' => 'text', 'title' => '模型标识', 'name' => 'table_name'],
            ['type' => 'text', 'title' => '所属模块', 'name' => 'mod_name'],
            ['type' => 'text', 'title' => '描述信息', 'name' => 'intro'],
            ['type' => 'radio', 'title' => '状态', 'name' => 'status', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'number', 'title' => '权重', 'name' => 'weight', 'default' => 99],
        ],
        'mod_field' => [
            ['type' => 'text', 'title' => '字段名称', 'name' => 'title'],
            ['type' => 'text', 'title' => '字段标识', 'name' => 'name'],
            ['type' => 'select', 'title' => '字段类型', 'name' => 'type'],
            ['type' => 'text', 'title' => '默认值', 'name' => 'default_val'],
            ['type' => 'text', 'title' => '提示信息', 'name' => 'tips'],
            ['type' => 'text', 'title' => '备注描述', 'name' => 'intro'],
            ['type' => 'radio', 'title' => '是否发布', 'name' => 'is_release', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'radio', 'title' => '是否搜索', 'name' => 'is_search', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'radio', 'title' => '列表展示', 'name' => 'is_table', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'radio', 'title' => '是否必填', 'name' => 'is_required', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'radio', 'title' => '状态', 'name' => 'status', 'options' => [
                ['label' => '否', 'value' => 0],
                ['label' => '是', 'value' => 1],
            ], 'default' => 1],
            ['type' => 'number', 'title' => '权重', 'name' => 'weight', 'default' => 99],
        ],
    ],
];
