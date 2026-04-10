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

namespace PTAdmin\Admin\Support;

class SystemConfigPreset
{
    /**
     * 返回后台默认系统配置预设。
     *
     * 这里仅维护包内必需的基础能力配置，
     * 业务系统可在安装后继续扩展自己的配置分组和配置项。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'title' => '系统配置',
                'name' => 'system',
                'weight' => 100,
                'status' => 1,
                'children' => [
                    [
                        'title' => '基础配置',
                        'name' => 'basic',
                        'weight' => 100,
                        'intro' => '站点基础信息与通用开关配置',
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => '站点标题',
                                'name' => 'site_title',
                                'type' => 'text',
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                                'intro' => '用于浏览器标题和后台展示',
                            ],
                            [
                                'title' => '站点描述',
                                'name' => 'site_description',
                                'type' => 'textarea',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 90,
                                'intro' => '用于站点介绍与 SEO 描述',
                            ],
                            [
                                'title' => '登录验证码',
                                'name' => 'login_captcha',
                                'type' => 'switch',
                                'value' => '1',
                                'default_val' => '1',
                                'weight' => 80,
                                'intro' => '控制后台登录是否显示验证码',
                            ],
                        ],
                    ],
                    [
                        'title' => '上传配置',
                        'name' => 'upload',
                        'weight' => 90,
                        'intro' => '文件上传与对象存储相关配置',
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => '上传存储驱动',
                                'name' => 'storage_driver',
                                'type' => 'text',
                                'value' => 'local',
                                'default_val' => 'local',
                                'weight' => 100,
                                'intro' => 'local 使用本地磁盘；填写插件 storage inject code 时走插件上传',
                            ],
                            [
                                'title' => '上传存储编码',
                                'name' => 'storage_code',
                                'type' => 'text',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 90,
                                'intro' => '可选，显式指定 storage inject code；为空时默认使用上传存储驱动',
                            ],
                            [
                                'title' => '上传存储磁盘',
                                'name' => 'storage_disk',
                                'type' => 'text',
                                'value' => 'oss',
                                'default_val' => 'oss',
                                'weight' => 80,
                                'intro' => '对象存储磁盘标识，如 oss、cos、s3',
                            ],
                            [
                                'title' => '上传存储桶',
                                'name' => 'storage_bucket',
                                'type' => 'text',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 70,
                                'intro' => '对象存储桶名称，不需要时可留空',
                            ],
                            [
                                'title' => '上传可见性',
                                'name' => 'storage_visibility',
                                'type' => 'radio',
                                'extra' => "public=公开\nprivate=私有",
                                'value' => 'public',
                                'default_val' => 'public',
                                'weight' => 60,
                                'intro' => '对象存储文件默认可见性',
                            ],
                            [
                                'title' => '上传扩展参数',
                                'name' => 'storage_meta',
                                'type' => 'json',
                                'value' => json_encode([], JSON_UNESCAPED_UNICODE),
                                'default_val' => json_encode([], JSON_UNESCAPED_UNICODE),
                                'weight' => 50,
                                'intro' => '传递给插件 storage inject 的扩展参数，JSON 对象格式',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
