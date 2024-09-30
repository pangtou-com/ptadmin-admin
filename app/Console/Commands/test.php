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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Service\SettingGroupService;

class test extends Command
{
    protected $signature = 'test';
    protected $description = 'Command description';

    private $setting = [
        ['title' => '基础设置', 'name' => 'base', 'weight' => '99', 'intro' => null, 'children' => [
            ['title' => '站点设置', 'name' => 'website', 'weight' => '99', 'intro' => null, 'fields' => [
                ['title' => '站点状态', 'name' => 'website_status', 'value' => 'PTAdmin 建站工具', 'type' => 'radio', 'extra' => ['options' => ['关闭', '启用']]],
                ['title' => 'LOGO', 'name' => 'website_logo', 'value' => '', 'type' => 'img'],
                ['title' => '站点名称', 'name' => 'website_title', 'value' => 'PTAdmin 建站工具', 'type' => 'text'],
                ['title' => '关键词', 'name' => 'website_keyword', 'value' => '', 'type' => 'text'],
                ['title' => '描述', 'name' => 'website_description', 'value' => '', 'type' => 'textarea'],
            ]],
        ]],
        ['title' => '第三方授权', 'name' => 'title', 'weight' => '99', 'intro' => null, 'children' => [
            ['title' => 'QQ登录', 'name' => 'qq_login', 'weight' => '99', 'intro' => null, 'fields' => [
                ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
                ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
                ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
            ]],
            ['title' => '微信登录', 'name' => 'wechat_login', 'weight' => '99', 'intro' => null, 'fields' => [
                ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
                ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
                ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
            ]],
            ['title' => '微博登录', 'name' => 'weibo_login', 'weight' => '0', 'intro' => null, 'fields' => [
                ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
                ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
                ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
            ]],
        ]],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        SettingGroupService::installInitialize($this->setting);

        return 0;
    }
}
