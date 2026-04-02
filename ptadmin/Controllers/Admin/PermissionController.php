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

namespace PTAdmin\Admin\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\Permission;
use PTAdmin\Admin\Models\Role;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Request\PermissionRequest;
use PTAdmin\Admin\Service\PermissionService;
use PTAdmin\Admin\Utils\ResultsVo;

class PermissionController extends AbstractBackgroundController
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
        parent::__construct();
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return ResultsVo::success(['results' => Permission::getTrees()]);
    }

    public function store(PermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->permissionService->store($request->all());

        return ResultsVo::success();
    }

    public function detail($id): \Illuminate\Http\JsonResponse
    {
        $data = $this->permissionService->detail($id);

        return ResultsVo::success($data);
    }

    public function edit(PermissionRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->permissionService->edit($request->all(), $id);

        return ResultsVo::success();
    }

    public function tree(): \Illuminate\Http\JsonResponse
    {
        $data = Permission::getTrees();

        return ResultsVo::success($data);
    }

    public function lists(): \Illuminate\Http\JsonResponse
    {
        $data = $this->permissionService->getOption();

        return ResultsVo::success($data);
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        $model = Permission::query();
        $filterMap = $model->whereIn('id', $this->getIds())->get();
        $filterMap->map(function ($item): void {
            $item->delete();
        });

        return ResultsVo::success();
    }

    /**
     * 获取角色权限信息.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRolePermission($id): \Illuminate\Http\JsonResponse
    {
        /** @var mixed $role */
        $role = Role::query()
            ->select(['id', 'title', 'origin_id', 'department_id', 'scope'])->findOrFail($id);
        $perm = $role->permissions()->pluck('id')->toArray();

        return ResultsVo::success([
            'results' => Permission::getTrees(),
            'detail' => $role,
            'perm' => $perm,
        ]);
    }

    /**
     * 获取用户权限信息.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemPermission($id): \Illuminate\Http\JsonResponse
    {
        /** @var mixed $system */
        $system = System::query()
            ->select(['id', 'nickname', 'origin_id', 'department_id', 'scope'])->findOrFail($id);
        $perm = $system->permissions()->pluck('id')->toArray();
        $system->title = $system->nickname;

        return ResultsVo::success([
            'results' => Permission::getTrees(),
            'detail' => $system,
            'perm' => $perm,
        ]);
    }

    public function saveRolePermission($id, Request $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            /** @var Role $role */
            $role = Role::query()->findOrFail($id);
            $role->scope = (int) $request->get('scope');
            $role->save();
            $role->syncScope((array) $request->get('scope_ids', []));
            $role->syncPermissions((array) $request->get('perm'));
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return ResultsVo::fail($exception->getMessage());
        }

        return ResultsVo::success();
    }

    public function saveSystemPermission($id, Request $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            /** @var System $system */
            $system = System::query()->findOrFail($id);
            $system->scope = (int) $request->get('scope');
            $system->save();
            $system->syncScope((array) $request->get('scope_ids', []));
            $system->syncPermissions((array) $request->get('perm'));
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return ResultsVo::fail($exception->getMessage());
        }

        return ResultsVo::success();
    }
}
