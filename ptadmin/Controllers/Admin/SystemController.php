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

namespace PTAdmin\Admin\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\Role;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Models\SystemLog;
use PTAdmin\Admin\Request\SystemRequest;
use PTAdmin\Admin\Service\PermissionService;
use PTAdmin\Admin\Service\SystemService;
use PTAdmin\Admin\Utils\ResultsVo;
use PTAdmin\Admin\Utils\SystemAuth;

class SystemController extends AbstractBackgroundController
{
    protected $permissionService;
    protected $systemService;

    public function __construct(PermissionService $permissionService, SystemService $systemService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
        $this->systemService = $systemService;
    }

    public function index(Request $request)
    {
        if (request()->expectsJson()) {
            $data = $this->systemService->page($request->all());

            return ResultsVo::pages($data);
        }

        return $this->view();
    }

    /**
     * @throws \Exception
     */
    public function store(SystemRequest $request)
    {
        if ($request->expectsJson()) {
            $data = $request->all();
            DB::beginTransaction();

            try {
                $dao = (new System());
                $dao->password = Hash::make(trim($data['password']));
                $dao->fill($data)->save();
                // 默认情况下一个账户只有一个角色
                $roleId = (int) $request->get('role_id');
                $roleId && $dao->syncRoles($roleId);
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();

                throw $exception;
            }

            return ResultsVo::success();
        }
        $dao = new System();

        return $this->view(compact('dao'));
    }

    public function edit(SystemRequest $request, $id)
    {
        /** @var System $dao */
        $dao = System::query()->findOrFail($id);
        if ($request->expectsJson()) {
            $data = $request->all();
            if (isset($data['password']) && $data['password']) {
                $dao->password = Hash::make($data['password']);
            }
            $roleId = (int) $request->get('role_id');
            $roleId && $dao->syncRoles($roleId);
            $dao->update($data);

            return ResultsVo::success();
        }

        return $this->view(compact('dao'));
    }

    public function details($id): \Illuminate\Http\JsonResponse
    {
        $dao = System::query()->select(['id', 'nickname', 'username'])->firstOrFail($id);

        $results = [
            'id' => $dao->id,
            'nickname' => $dao->nickname,
            'username' => $dao->username,
            'role_id' => $dao->roles()->get()->map(function ($item) {
                return $item->id;
            })->toArray(),
            'role' => Role::query()->select(['id', 'title'])->where('status', StatusEnum::ENABLE)->get(),
        ];

        return ResultsVo::success($results);
    }

    /**
     * 设置角色权限. 这个方法可以设置一个账户多个角色.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setRole($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $roleId = $request->get('role_id', []);
        $dao = System::query()->select(['id', 'nickname', 'username'])->firstOrFail($id);
        $dao->syncRoles($roleId);

        return ResultsVo::success();
    }

    public function password(Request $request)
    {
        if ($request->expectsJson()) {
            $data = $request->validate([
                'password' => 'required|confirmed|min:6|max:20',
                'old_password' => 'required',
            ]);

            /** @var System $user */
            $user = SystemAuth::user();
            if (!Hash::check($data['old_password'], $user->password)) {
                throw new BackgroundException('原密码错误');
            }
            $user->password = Hash::make($data['password']);
            $user->update();

            Auth::guard(SystemAuth::getGuard())->logout();

            return ResultsVo::success();
        }

        return $this->view();
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        $ids = $this->getIds();
        System::query()->where('is_founder', 0)->whereIn('id', $ids)->delete();

        return ResultsVo::success();
    }

    /**
     * 登录日志.
     */
    public function loginLog()
    {
        if (request()->expectsJson()) {
            $id = SystemAuth::user()->id;
            $model = new SystemLog();
            $filterMap = $model->query()->select(['id', 'system_id', 'login_at', 'login_ip', 'status'])->where('system_id', $id);

            $results = $filterMap->with('system:id,nickname')->orderBy('id', 'desc')->paginate();

            return ResultsVo::pages($results);
        }

        return view('ptadmin.system.login_log');
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        $ids = $this->getIds();
        System::query()->where('is_founder', 0)->whereIn('id', $ids)->update([
            'status' => (int) request()->get('value'),
        ]);

        return ResultsVo::success();
    }

    /**
     * 获取我的权限.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPermission(): \Illuminate\Http\JsonResponse
    {
        return ResultsVo::success($this->permissionService->myPermission(SystemAuth::user()));
    }
}
