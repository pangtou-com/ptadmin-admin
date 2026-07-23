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
                        'title' => '站点地址',
                        'name' => 'site_url',
                        'type' => 'text',
                        'value' => config("app.url"),
                        'default_val' => config("app.url"),
                        'sort' => 100,
                        'intro' => '站点域名地址',
                    ],
                    [
                        'title' => '站点描述',
                        'name' => 'site_description',
                        'type' => 'textarea',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 90,
                        'intro' => '用于站点介绍、SEO 描述等场景',
                        'extra' => [
                            'maxlength' => 2000,
                        ],
                    ],
                    [
                        'title' => '站点关键词',
                        'name' => 'site_keywords',
                        'type' => 'textarea',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 80,
                        'intro' => '用于站点 SEO 关键词配置，多个关键词可按换行或分隔符组织',
                        'extra' => [
                            'maxlength' => 2000,
                        ],
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
                'title' => '上传规则',
                'name' => 'upload',
                'type' => 'system',
                'access' => 'private',
                'sort' => 80,
                'intro' => '文件上传安全策略与行为规则配置',
                'badge' => '传',
                'status' => 1,
                'fields' => [
                    // 基础上传
                    [
                        'title' => '允许上传',
                        'name' => 'enabled',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 1000,
                        'intro' => '全局控制是否允许文件上传',
                    ],
                    [
                        'title' => '需要登录',
                        'name' => 'require_auth',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 990,
                        'intro' => '未登录用户是否允许上传文件',
                    ],
                    [
                        'title' => '单文件最大大小',
                        'name' => 'max_size',
                        'type' => 'text',
                        'value' => '20MB',
                        'default_val' => '20MB',
                        'sort' => 980,
                        'intro' => '支持 KB、MB、GB 单位',
                    ],
                    [
                        'title' => '单次最大文件数',
                        'name' => 'max_files',
                        'type' => 'number',
                        'value' => 9,
                        'default_val' => 9,
                        'sort' => 970,
                        'intro' => '一次请求允许上传的最大文件数量',
                    ],
                    // 文件类型
                    [
                        'title' => '允许后缀',
                        'name' => 'allowed_ext',
                        'type' => 'text',
                        'value' => "jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip",
                        'default_val' => "",
                        'sort' => 900,
                        'intro' => '允许上传的文件后缀列表,多个使用逗号分隔，如未设置则不限制',
                    ],
                    [
                        'title' => '允许 MIME',
                        'name' => 'allowed_mime',
                        'type' => 'textarea',
                        'value' => "",
                        'default_val' => '',
                        'sort' => 890,
                        'intro' => '允许上传的MIME类型，多个使用逗号分隔，如未设置则不限制',
                        'extra' => [
                            'maxlength' => 5000,
                        ],
                    ],
                    // 分片上传
                    [
                        'title' => '启用分片上传',
                        'name' => 'chunk_enabled',
                        'type' => 'switch',
                        'value' => 0,
                        'default_val' => 0,
                        'sort' => 700,
                        'intro' => '大文件上传时启用分片机制',
                    ],
                    [
                        'title' => '分片大小',
                        'name' => 'chunk_size',
                        'type' => 'text',
                        'value' => '2MB',
                        'default_val' => '2MB',
                        'sort' => 690,
                        'intro' => '单个分片文件大小',
                    ],
                    [
                        'title' => '分片有效期',
                        'name' => 'chunk_expire',
                        'type' => 'text',
                        'value' => '24h',
                        'default_val' => '24h',
                        'sort' => 680,
                        'intro' => '未完成分片自动清理时间',
                    ],
                    // 安全控制
                    [
                        'title' => '校验 MIME',
                        'name' => 'validate_mime',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 600,
                        'intro' => '检测文件真实 MIME 类型',
                    ],
                    [
                        'title' => '校验文件头',
                        'name' => 'validate_signature',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 590,
                        'intro' => '检测文件魔数防止伪造文件',
                    ],
                    [
                        'title' => '禁止脚本文件',
                        'name' => 'block_script',
                        'type' => 'switch',
                        'value' => 1,
                        'default_val' => 1,
                        'sort' => 580,
                        'intro' => '阻止脚本类文件上传',
                    ],
                ],
            ],
            [
                'title' => '邮件通知',
                'name' => 'mail',
                'type' => 'system',
                'access' => 'private',
                'sort' => 70,
                'intro' => '内置邮箱通知发送配置',
                'badge' => '邮',
                'status' => 1,
                'fields' => [
                    [
                        'title' => '启用邮件通知',
                        'name' => 'enabled',
                        'type' => 'switch',
                        'value' => 0,
                        'default_val' => 0,
                        'sort' => 100,
                        'intro' => '控制内置邮箱通知通道是否启用',
                    ],
                    [
                        'title' => 'SMTP 主机',
                        'name' => 'host',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 90,
                        'intro' => 'SMTP 服务地址',
                    ],
                    [
                        'title' => 'SMTP 端口',
                        'name' => 'port',
                        'type' => 'number',
                        'value' => 587,
                        'default_val' => 587,
                        'sort' => 80,
                        'intro' => 'SMTP 服务端口',
                    ],
                    [
                        'title' => '加密方式',
                        'name' => 'encryption',
                        'type' => 'radio',
                        'value' => 'tls',
                        'default_val' => 'tls',
                        'sort' => 70,
                        'intro' => 'SMTP 加密方式',
                        'extra' => [
                            'options' => [
                                ['label' => '无', 'value' => ''],
                                ['label' => 'TLS', 'value' => 'tls'],
                                ['label' => 'SSL', 'value' => 'ssl'],
                            ],
                        ],
                    ],
                    [
                        'title' => '邮箱账号',
                        'name' => 'username',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 60,
                        'intro' => 'SMTP 登录账号',
                    ],
                    [
                        'title' => '邮箱密码',
                        'name' => 'password',
                        'type' => 'secret',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 50,
                        'intro' => 'SMTP 登录密码或授权码',
                    ],
                    [
                        'title' => '发件邮箱',
                        'name' => 'from_address',
                        'type' => 'text',
                        'value' => '',
                        'default_val' => '',
                        'sort' => 40,
                        'intro' => '邮件 From 地址，未填写时使用邮箱账号',
                    ],
                    [
                        'title' => '发件名称',
                        'name' => 'from_name',
                        'type' => 'text',
                        'value' => 'PTAdmin',
                        'default_val' => 'PTAdmin',
                        'sort' => 30,
                        'intro' => '邮件 From 名称',
                    ],
                ],
            ],
        ];
    }
}
