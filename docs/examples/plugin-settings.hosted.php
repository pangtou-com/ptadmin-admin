<?php

declare(strict_types=1);

return [
    // Whether the plugin should appear in the settings center.
    'enabled' => true,

    // hosted: render by PTAdmin settings center via pt-render
    // external_route: jump to plugin-owned route
    // none: hide from settings center
    'mode' => 'hosted',

    // Optional icon shown in plugin settings catalog.
    'icon' => 'Document',

    // system: saved by PTAdmin settings center
    // plugin: displayed by PTAdmin but managed by the plugin itself
    'managed_by' => 'system',

    // merge: create or merge hosted config resources
    // overwrite: refresh hosted config definitions by registration
    // skip: keep existing hosted config resources untouched
    'injection' => [
        'strategy' => 'merge',
    ],

    // retain: keep hosted settings after uninstall
    // purge: delete hosted settings with the plugin
    'cleanup' => [
        'on_uninstall' => 'retain',
    ],

    // Hosted mode can expose one or more independently saved sections.
    'sections' => [
        [
            'key' => 'basic',
            'title' => '基础配置',
            'description' => '插件基础运行参数',
            'icon' => 'Setting',
            'order' => 10,
            'schema' => [
                'layout' => [
                    'mode' => 'block',
                    'labelWidth' => 140,
                ],
                'fields' => [
                    [
                        'name' => 'site_name',
                        'type' => 'text',
                        'label' => '站点名称',
                        'meta' => [
                            'placeholder' => '请输入站点名称',
                            'required' => true,
                            'min' => 2,
                            'max' => 30,
                            'expose' => 'public',
                        ],
                    ],
                    [
                        'name' => 'api_base_url',
                        'type' => 'text',
                        'label' => '接口地址',
                        'meta' => [
                            'placeholder' => 'https://api.example.com',
                            'pattern' => '/^https?:\\/\\//',
                        ],
                    ],
                    [
                        'name' => 'enabled',
                        'type' => 'switch',
                        'label' => '启用状态',
                        'meta' => [
                            'expose' => 'public',
                        ],
                    ],
                ],
            ],
            'defaults' => [
                'site_name' => 'Demo Plugin',
                'api_base_url' => '',
                'enabled' => true,
            ],
        ],
        [
            'key' => 'security',
            'title' => '安全设置',
            'description' => '鉴权与风控参数',
            'icon' => 'Lock',
            'order' => 20,
            'schema' => [
                'layout' => [
                    'mode' => 'block',
                    'labelWidth' => 140,
                ],
                'fields' => [
                    [
                        'name' => 'access_key',
                        'type' => 'text',
                        'label' => 'Access Key',
                        'meta' => [
                            'expose' => 'protected',
                        ],
                    ],
                    [
                        'name' => 'secret_key',
                        'type' => 'text',
                        'label' => 'Secret Key',
                        'meta' => [
                            'expose' => 'private',
                        ],
                    ],
                    [
                        'name' => 'signature_enabled',
                        'type' => 'switch',
                        'label' => '启用签名校验',
                    ],
                ],
            ],
            'defaults' => [
                'access_key' => '',
                'secret_key' => '',
                'signature_enabled' => false,
            ],
        ],
    ],
];
