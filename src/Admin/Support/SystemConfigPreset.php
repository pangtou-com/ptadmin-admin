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
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'title' => '基础设置',
                'name' => 'basic',
                'type' => 'system',
                'access' => 'public',
                'sort' => 100,
                'intro' => '站点基础信息与通用展示配置',
                'badge' => "基",
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题',
                        'name' => 'site_title',
                        'type' => 'text',
                        'value' => 'PTAdmin',
                        'default_val' => 'PTAdmin',
                        'sort' => 100,
                        'intro' => '用于站点页面标题、后台展示等场景',
                    ],
                    [
                        'title' => '站点描述',
                        'name' => 'site_description',
                        'type' => 'textarea',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 90,
                        'intro' => '用于站点介绍、SEO 描述等场景',
                    ],
                    [
                        'title' => '站点关键词',
                        'name' => 'site_keywords',
                        'type' => 'textarea',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 80,
                        'intro' => '用于站点 SEO 关键词配置，多个关键词可按换行或分隔符组织',
                    ],
                    [
                        'title' => '站点 Logo',
                        'name' => 'site_logo',
                        'type' => 'image',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 70,
                        'intro' => '用于站点品牌展示的主 Logo',
                    ],
                    [
                        'title' => '站点图标',
                        'name' => 'site_favicon',
                        'type' => 'image',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 60,
                        'intro' => '浏览器标签页、收藏夹等场景使用的图标',
                    ],
                    [
                        'title' => '版权信息',
                        'name' => 'copyright',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 50,
                        'intro' => '站点底部版权说明',
                    ],
                    [
                        'title' => 'ICP备案号',
                        'name' => 'icp_no',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 40,
                        'intro' => '网站备案信息展示',
                    ],
                ],
            ],
            [
                'title' => '安全设置',
                'name' => 'security',
                'type' => 'system',
                'access' => 'private',
                'sort' => 90,
                'intro' => '注册、登录与基础安全相关配置',
                'badge' => '安',
                'status' => 1,
                'fields' => [
                    [
                        'title' => '允许注册',
                        'name' => 'is_register',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 100,
                        'intro' => '控制前台用户是否允许自行注册',
                    ],
                    [
                        'title' => '登录验证码',
                        'name' => 'login_captcha',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 90,
                        'intro' => '控制登录时是否启用验证码校验',
                    ],
                ],
            ],
            [
                'title' => '上传设置',
                'name' => 'upload',
                'type' => 'system',
                'access' => 'private',
                'sort' => 80,
                'intro' => '上传存储与缩略图相关配置',
                'badge' => '传',
                'status' => 1,
                'fields' => [
                    [
                        'title' => '存储驱动',
                        'name' => 'storage_driver',
                        'type' => 'text',
                        'value' => 'local',
                        'default_val' => 'local',
                        'sort' => 100,
                        'intro' => '指定当前启用的存储驱动或插件编码',
                    ],
                    [
                        'title' => '存储插件编码',
                        'name' => 'storage_code',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 90,
                        'intro' => '当使用插件存储时填写对应插件编码',
                    ],
                    [
                        'title' => '磁盘标识',
                        'name' => 'storage_disk',
                        'type' => 'text',
                        'value' => 'public',
                        'default_val' => 'public',
                        'sort' => 80,
                        'intro' => '指定存储磁盘名称',
                    ],
                    [
                        'title' => 'Bucket',
                        'name' => 'storage_bucket',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 70,
                        'intro' => '对象存储服务对应的 bucket 名称',
                    ],
                    [
                        'title' => '访问权限',
                        'name' => 'storage_visibility',
                        'type' => 'radio',
                        'extra' => [
                            'options' => [
                                'public' => '公开',
                                'private' => '私有',
                            ],
                        ],
                        'value' => 'public',
                        'default_val' => 'public',
                        'sort' => 60,
                        'intro' => '控制上传文件默认访问权限',
                    ],
                    [
                        'title' => '扩展参数',
                        'name' => 'storage_meta',
                        'type' => 'key-value',
                        'value' => '{}',
                        'default_val' => '{}',
                        'sort' => 50,
                        'intro' => '以 JSON 格式填写存储驱动额外参数',
                    ],
                    [
                        'title' => '启用缩略图',
                        'name' => 'thumb_enabled',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 40,
                        'intro' => '控制图片是否启用缩略图能力',
                    ],
                    [
                        'title' => '缩略图宽度',
                        'name' => 'thumb_width',
                        'type' => 'text',
                        'value' => '100',
                        'default_val' => '100',
                        'sort' => 30,
                        'intro' => '默认缩略图宽度',
                    ],
                    [
                        'title' => '缩略图高度',
                        'name' => 'thumb_height',
                        'type' => 'text',
                        'value' => '100',
                        'default_val' => '100',
                        'sort' => 20,
                        'intro' => '默认缩略图高度',
                    ],
                    [
                        'title' => '缩略图模式',
                        'name' => 'thumb_mode',
                        'type' => 'radio',
                        'extra' => [
                            'options' => [
                                'middle' => '居中裁切',
                                'top' => '顶部裁切',
                                'bottom' => '底部裁切',
                            ],
                        ],
                        'value' => 'middle',
                        'default_val' => 'middle',
                        'sort' => 10,
                        'intro' => '控制缩略图裁切方式',
                    ],
                ],
            ],
        ];
    }
}
