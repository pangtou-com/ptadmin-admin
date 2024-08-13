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

namespace PTAdmin\Admin\Service;

use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Utils\SystemAuth;

class SystemService
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function page($search = []): array
    {
        $model = System::query();

        return $model->orderBy('id', 'desc')->paginate()->toArray();
    }

    /**
     * 初始化创始人账户.
     *
     * @param $data
     */
    public static function initializeFounder($data): void
    {
        if (!app()->runningInConsole() && (!isset($data['force']) || true !== $data['force'])) {
            throw new BackgroundException('请在命令行模式下运行');
        }
        $model = System::query()->where('is_founder', 1)->first();
        if ($model && (!isset($data['force']) || false === $data['force'])) {
            throw new BackgroundException('已有创始人账户，如需重新初始化请使用 --force|-f 参数');
        }
        if (!$model) {
            $model = new System();
            $model->is_founder = 1;
        }
        $model->fill($data);
        $model->status = 1;
        $model->password = Hash::make($data['password']);
        $model->save();
    }

    /**
     * TODO 待处理.
     *
     * @return array
     */
    public function permissionsInner(): array
    {
        $user = SystemAuth::user()->id;
        $permissions = $this->permissionService->bySystemIdPermission($user->id);
        $permissionArr = [];
        foreach ($permissions as $value) {
            if (1 === (int) $value['is_inner'] && 'nav' === $value['type']) {
                $value['url'] = admin_route($value['route']);
                $permissionArr[] = $value;
            }
        }

        return $permissionArr;
    }
}
