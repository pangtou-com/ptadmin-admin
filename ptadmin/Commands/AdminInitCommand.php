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

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PTAdmin\Admin\Service\SystemService;

class AdminInitCommand extends Command
{
    protected $signature = 'admin:init
    {--u|username=root : 管理员用户名}
    {--N|nickname= : 管理员昵称}
    {--p|password= : 管理员密码}
    {--e|email= : 管理员邮箱}
    {--m|mobile= : 管理员手机号}
    {--f|force : 强制初始化}';

    protected $description = '初始化管理员账户';

    public function handle(): int
    {
        $data = [];
        $data['username'] = $this->option('username');
        $data['password'] = $this->option('password');
        $data['nickname'] = $this->option('nickname') ?? 'root';
        $data['email'] = $this->option('email');
        $data['mobile'] = $this->option('mobile');
        $data['force'] = (bool) $this->option('force');

        if (null === $data['username']) {
            $data['username'] = Str::random(8);
        }
        if (null === $data['password']) {
            $data['password'] = Str::random(8);
        }
        if (null === $data['mobile']) {
            unset($data['mobile']);
        }
        if (null === $data['email']) {
            unset($data['email']);
        }
        if (false === $this->check($data)) {
            return 1;
        }

        try {
            SystemService::initializeFounder($data);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info('管理员账户初始化成功!');
        $this->info('管理员账户信息:');
        $this->info('用户账户: '.$data['username']);
        $this->info('用户密码: '.$data['password']);
        $this->info('请妥善保管好您的账户信息，不要泄露给其他人');

        return 0;
    }

    private function check($data): bool
    {
        $valid = Validator::make($data, [
            'username' => 'required|min:4|max:20',
            'password' => 'required|min:6|max:32',
            'email' => 'email|max:255',
            'mobile' => 'max:30|regex:/^1\d{10}$/',
            'nickname' => 'max:20',
        ]);

        if ($valid->fails()) {
            foreach ($valid->errors()->toArray() as $error) {
                array_walk($error, function ($msg): void {
                    $this->error($msg);
                });
            }

            return false;
        }

        return true;
    }
}
