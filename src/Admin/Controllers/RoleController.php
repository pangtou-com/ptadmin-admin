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
use PTAdmin\Admin\Requests\RoleRequest;
use PTAdmin\Admin\Services\AdminResourceService;
use PTAdmin\Foundation\Response\AdminResponse;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class RoleController extends AbstractBackgroundController
{
    protected $adminResourceService;
    private AdminRoleServiceInterface $adminRoleService;

    public function __construct(AdminResourceService $adminResourceService, AdminRoleServiceInterface $adminRoleService)
    {
        parent::__construct();
        $this->adminResourceService = $adminResourceService;
        $this->adminRoleService = $adminRoleService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::pages($this->adminRoleService->page());
    }

    public function store(RoleRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->adminRoleService->create([
            'code' => (string) $request->get('name'),
            'name' => (string) $request->get('title'),
            'description' => $request->get('note'),
            'status' => (int) $request->get('status', 1),
        ]);

        return AdminResponse::success();
    }

    public function edit(RoleRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->adminRoleService->update((int) $id, [
            'code' => (string) $request->get('name'),
            'name' => (string) $request->get('title'),
            'description' => $request->get('note'),
            'status' => (int) $request->get('status', 1),
        ]);

        return AdminResponse::success();
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        collect($this->getIds())->map(static function ($id): int {
            return (int) $id;
        })->each(function (int $id): void {
            $this->adminRoleService->delete($id);
        });

        return AdminResponse::success();
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        $this->adminRoleService->updateStatus($this->getIds(), (int) request()->get('value'));

        return AdminResponse::success();
    }

    /**
     * 角色设置权限.
     *
     * @param mixed   $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncRoleResources($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->syncRoleResourceSelection((int) $id, (array) $request->get('ids', []));

        return AdminResponse::success();
    }

    /**
     * 获取权限列表.
     *
     * @param mixed $id
     */
    public function getRoleResources($id): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminResourceService->getRoleResourceSelection((int) $id));
    }
}
