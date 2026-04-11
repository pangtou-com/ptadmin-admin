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
                'title' => __('ptadmin::common.system_config.group.system_title'),
                'name' => 'system',
                'weight' => 100,
                'status' => 1,
                'children' => [
                    [
                        'title' => __('ptadmin::common.system_config.section.basic_title'),
                        'name' => 'basic',
                        'weight' => 100,
                        'intro' => __('ptadmin::common.system_config.section.basic_intro'),
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => __('ptadmin::common.system_config.field.site_title_title'),
                                'name' => 'site_title',
                                'type' => 'text',
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                                'intro' => __('ptadmin::common.system_config.field.site_title_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.site_description_title'),
                                'name' => 'site_description',
                                'type' => 'textarea',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 90,
                                'intro' => __('ptadmin::common.system_config.field.site_description_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.login_captcha_title'),
                                'name' => 'login_captcha',
                                'type' => 'switch',
                                'value' => '1',
                                'default_val' => '1',
                                'weight' => 80,
                                'intro' => __('ptadmin::common.system_config.field.login_captcha_intro'),
                            ],
                        ],
                    ],
                    [
                        'title' => __('ptadmin::common.system_config.section.upload_title'),
                        'name' => 'upload',
                        'weight' => 90,
                        'intro' => __('ptadmin::common.system_config.section.upload_intro'),
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_driver_title'),
                                'name' => 'storage_driver',
                                'type' => 'text',
                                'value' => 'local',
                                'default_val' => 'local',
                                'weight' => 100,
                                'intro' => __('ptadmin::common.system_config.field.storage_driver_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_code_title'),
                                'name' => 'storage_code',
                                'type' => 'text',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 90,
                                'intro' => __('ptadmin::common.system_config.field.storage_code_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_disk_title'),
                                'name' => 'storage_disk',
                                'type' => 'text',
                                'value' => 'oss',
                                'default_val' => 'oss',
                                'weight' => 80,
                                'intro' => __('ptadmin::common.system_config.field.storage_disk_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_bucket_title'),
                                'name' => 'storage_bucket',
                                'type' => 'text',
                                'value' => '',
                                'default_val' => '',
                                'weight' => 70,
                                'intro' => __('ptadmin::common.system_config.field.storage_bucket_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_visibility_title'),
                                'name' => 'storage_visibility',
                                'type' => 'radio',
                                'extra' => sprintf(
                                    "public=%s\nprivate=%s",
                                    __('ptadmin::common.system_config.option.visibility_public'),
                                    __('ptadmin::common.system_config.option.visibility_private')
                                ),
                                'value' => 'public',
                                'default_val' => 'public',
                                'weight' => 60,
                                'intro' => __('ptadmin::common.system_config.field.storage_visibility_intro'),
                            ],
                            [
                                'title' => __('ptadmin::common.system_config.field.storage_meta_title'),
                                'name' => 'storage_meta',
                                'type' => 'json',
                                'value' => json_encode([], JSON_UNESCAPED_UNICODE),
                                'default_val' => json_encode([], JSON_UNESCAPED_UNICODE),
                                'weight' => 50,
                                'intro' => __('ptadmin::common.system_config.field.storage_meta_intro'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
