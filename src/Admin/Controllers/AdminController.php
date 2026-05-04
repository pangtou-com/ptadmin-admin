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

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Requests\AdminRequest;
use PTAdmin\Admin\Services\AdminResourceService;
use PTAdmin\Admin\Services\AdminService;
use PTAdmin\Admin\Support\Query\AdminListQuery;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Response\AdminResponse;

class AdminController extends AbstractBackgroundController
{
    protected AdminResourceService $adminResourceService;
    protected AdminService $adminService;

    public function __construct(AdminResourceService $adminResourceService, AdminService $adminService)
    {
        $this->adminResourceService = $adminResourceService;
        $this->adminService = $adminService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->adminService->page(AdminListQuery::fromRequest($request));

        return AdminResponse::pages($data);
    }

    /**
     * @throws \Exception
     */
    public function store(AdminRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->adminService->create($request->validated());

        return AdminResponse::success();
    }

    public function edit(AdminRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->adminService->update((int) $id, $request->validated());

        return AdminResponse::success();
    }

    public function details($id): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminService->details((int) $id));
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
        $this->adminService->syncRoles((int) $id, (array) $request->get('role_id', []));

        return AdminResponse::success();
    }

    /**
     * 修改密码
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function password(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'password' => 'required|confirmed|min:6|max:20',
            'old_password' => 'required',
        ]);

        $this->adminService->updatePassword(AdminAuth::user(), (string) $data['old_password'], (string) $data['password']);

        Auth::guard(AdminAuth::getGuard())->logout();

        return AdminResponse::success();
    }

    /**
     * 删除账户.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(): \Illuminate\Http\JsonResponse
    {
        $this->adminService->deleteAdmins($this->getIds());

        return AdminResponse::success();
    }

    /**
     * 登录日志.
     */
    public function loginLog(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::pages($this->adminService->loginLogs(AdminAuth::user(), AdminListQuery::fromRequest($request)));
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        $this->adminService->updateStatus($this->getIds(), (int) request()->get('value'));

        return AdminResponse::success();
    }

    /**
     * 获取我的权限.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myResources(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminResourceService->myResources(AdminAuth::user()));
    }
}
