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
    [
        'name' => 'Config',
        'type' => 'dir',
        'children' => [
            [
                'name' => '',
                'type' => 'file',
                'suffix' => 'gitkeep',
                'text' => '',
            ],
            [
                'name' => 'config',
                'type' => 'file',
                'suffix' => 'php',
                'is_need' => true,
                'change' => [],
            ],
        ],
    ],
    [
        'name' => 'Exceptions',
        'type' => 'dir',
        'children' => [
            [
                'before_name' => 'baseName',
                'old_before' => 'Demo',
                'name' => 'Exception',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                ],
            ],
        ],
    ],
    [
        'name' => 'Http',
        'type' => 'dir',
        'children' => [
            [
                'name' => 'Controllers',
                'type' => 'dir',
                'children' => [
                    [
                        'name' => 'Admin',
                        'type' => 'dir',
                        'children' => [
                            [
                                'before_name' => 'baseName',
                                'old_before' => 'Demo',
                                'name' => 'Controller',
                                'type' => 'file',
                                'suffix' => 'php',
                                'text' => '',
                                'is_need' => true,
                                'change' => [
                                    'stup\Demo' => 'namespace',
                                    'Demo' => 'baseName',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Api',
                        'type' => 'dir',
                        'children' => [
                            [
                                'before_name' => 'baseName',
                                'old_before' => 'Demo',
                                'name' => 'Controller',
                                'type' => 'file',
                                'suffix' => 'php',
                                'text' => '',
                                'is_need' => true,
                                'change' => [
                                    'stup\Demo' => 'namespace',
                                    'Demo' => 'baseName',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Home',
                        'type' => 'dir',
                        'children' => [
                            [
                                'before_name' => 'baseName',
                                'old_before' => 'Demo',
                                'name' => 'Controller',
                                'type' => 'file',
                                'suffix' => 'php',
                                'text' => '',
                                'is_need' => true,
                                'change' => [
                                    'stup\Demo' => 'namespace',
                                    'Demo' => 'baseName',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '',
                'type' => 'file',
                'suffix' => 'gitkeep',
                'text' => '',
            ],
        ],
    ],
    [
        'name' => 'Models',
        'type' => 'dir',
        'children' => [
            [
                'before_name' => 'baseName',
                'old_before' => 'Demo',
                'name' => '',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                ],
            ],
        ],
    ],
    [
        'name' => 'Providers',
        'type' => 'dir',
        'children' => [
            [
                'before_name' => 'baseName',
                'old_before' => 'Demo',
                'name' => 'ServiceProvider',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                    'demo' => 'lowBaseName',
                ],
            ],
        ],
    ],
    [
        'name' => 'Response',
        'type' => 'dir',
        'children' => [
            [
                'name' => 'Lang',
                'type' => 'dir',
                'children' => [
                    [
                        'name' => '',
                        'type' => 'file',
                        'suffix' => 'gitkeep',
                        'text' => '',
                    ],
                ],
            ],
            [
                'name' => 'Views',
                'type' => 'dir',
                'children' => [
                    [
                        'name' => 'admin',
                        'type' => 'dir',
                        'children' => [
                            [
                                'name' => '',
                                'type' => 'file',
                                'suffix' => 'gitkeep',
                                'text' => '',
                            ],
                        ],
                    ],
                    [
                        'name' => 'home',
                        'type' => 'dir',
                        'children' => [
                            [
                                'name' => '',
                                'type' => 'file',
                                'suffix' => 'gitkeep',
                                'text' => '',
                            ],
                            [
                                'name' => 'index.blade',
                                'type' => 'file',
                                'suffix' => 'php',
                                'is_need' => true,
                                'change' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Routes',
        'type' => 'dir',
        'children' => [
            [
                'name' => 'admin',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                    'demo' => 'lowBaseName',
                ],
            ],
            [
                'name' => 'api',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                    'demo' => 'lowBaseName',
                ],
            ],
            [
                'name' => 'web',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                    'demo' => 'lowBaseName',
                ],
            ],
        ],
    ],
    [
        'name' => 'Service',
        'type' => 'dir',
        'children' => [
            [
                'before_name' => 'baseName',
                'old_before' => 'Demo',
                'name' => 'Service',
                'type' => 'file',
                'suffix' => 'php',
                'text' => '',
                'is_need' => true,
                'change' => [
                    'stup\Demo' => 'namespace',
                    'Demo' => 'baseName',
                    'demo' => 'lowBaseName',
                ],
            ],
        ],
    ],
    [
        'name' => 'Bootstrap',
        'type' => 'file',
        'suffix' => 'php',
        'is_need' => true,
        'change' => [
            'stup\Demo' => 'namespace',
        ],
    ],
    [
        'name' => 'info',
        'type' => 'file',
        'suffix' => 'ini',
        'is_need' => true,
        'change' => [
            '{name}' => 'name',
            '{description}' => 'description',
            '{code}' => 'code',
            '{version}' => 'version',
            '{framework}' => 'framework',
            '{author}' => 'author',
            '{email}' => 'email',
            '{homepage}' => 'homepage',
            '{docs}' => 'docs',
            '{licenseKey}' => 'licenseKey',
        ],
    ],
    [
        'name' => 'README',
        'type' => 'file',
        'suffix' => 'md',
        'is_need' => false,
        'text' => '# PTAdmin 插件开发dome',
    ],
];
