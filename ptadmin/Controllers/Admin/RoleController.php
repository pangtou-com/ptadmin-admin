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
use PTAdmin\Admin\Controllers\Traits\EditTrait;
use PTAdmin\Admin\Controllers\Traits\ExtendTrait;
use PTAdmin\Admin\Controllers\Traits\IndexTrait;
use PTAdmin\Admin\Controllers\Traits\StoreTrait;
use PTAdmin\Admin\Controllers\Traits\ValidateTrait;
use PTAdmin\Admin\Models\Permission;
use PTAdmin\Admin\Models\Role;
use PTAdmin\Admin\Service\PermissionService;
use PTAdmin\Admin\Utils\ResultsVo;

class RoleController extends AbstractBackgroundController
{
    use EditTrait;
    use ExtendTrait;
    use IndexTrait;
    use StoreTrait;
    use ValidateTrait;

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    /**
     * 角色设置权限.
     *
     * @param mixed   $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPermission($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = $request->get('ids');
        $role = Role::query()->findOrFail($id);
        if (blank($ids)) {
            $role->permissions()->detach();

            return ResultsVo::success();
        }

        $permission = Permission::query()->whereIn('id', $ids)->get();
        $role->syncPermissions(data_get($permission->toArray(), '*.id'));

        return ResultsVo::success();
    }

    /**
     * 获取权限列表.
     *
     * @param mixed $id
     */
    public function getPermission($id)
    {
        $permission = Permission::getAllData();
        $role = Role::findById((int) $id, config('auth.app_guard_name'));
        $results = $checked = [];

        foreach ($permission as $item) {
            $item['checked'] = false;
            if ($role->hasPermissionTo($item['name'])) {
                $checked[] = $item['id'];
                $item['checked'] = true;
            }
            $results[] = $item;
        }
        $results = infinite_tree($results, null, 'parent_name', 'name');
        $result = [
            'checked' => $checked,
            'results' => $results,
        ];

        if (request()->expectsJson()) {
            return ResultsVo::success($result);
        }
        $view = $this->permissionService->getRolePermissionHtml($results);

        return view('ptadmin.role.permission', compact('results', 'checked', 'id', 'view'));
    }
}
