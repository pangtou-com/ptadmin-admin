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
use PTAdmin\Admin\Requests\AdminResourceRequest;
use PTAdmin\Admin\Services\AdminResourceService;
use PTAdmin\Foundation\Response\AdminResponse;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;

class AdminResourceController extends AbstractBackgroundController
{
    protected $adminResourceService;

    public function __construct(AdminResourceService $adminResourceService)
    {
        $this->adminResourceService = $adminResourceService;
        parent::__construct();
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success(['results' => $this->adminResourceService->resourceTree()]);
    }

    public function store(AdminResourceRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->store($request->all());

        return AdminResponse::success();
    }

    public function detail($id): \Illuminate\Http\JsonResponse
    {
        $data = $this->adminResourceService->detail($id);

        return AdminResponse::success($data);
    }

    public function edit(AdminResourceRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->edit($request->all(), $id);

        return AdminResponse::success();
    }

    public function tree(): \Illuminate\Http\JsonResponse
    {
        $data = $this->adminResourceService->resourceTree();

        return AdminResponse::success($data);
    }

    public function lists(): \Illuminate\Http\JsonResponse
    {
        $data = $this->adminResourceService->getOption();

        return AdminResponse::success($data);
    }

    public function editField(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        if (!app(CapabilityServiceInterface::class)->enabled('field_acl')) {
            return AdminResponse::fail(__('ptadmin::background.field_acl_not_enabled'));
        }

        $data = $request->validate([
            'fields' => 'sometimes|array',
            'fields.*.name' => 'required|string|max:100',
            'fields.*.title' => 'nullable|string|max:100',
            'fields.*.status' => 'sometimes|integer|min:0|max:1',
            'fields.*.sort' => 'sometimes|integer|min:0',
            'fields.*.weight' => 'sometimes|integer|min:0',
            'fields.*.abilities' => 'sometimes|array',
            'fields.*.abilities.*' => 'sometimes|string|max:50',
            'fields.*.note' => 'nullable|string|max:255',
        ]);

        return AdminResponse::success([
            'results' => $this->adminResourceService->syncFieldResources((int) $id, (array) ($data['fields'] ?? [])),
        ]);
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->deleteResourceIds($this->getIds());

        return AdminResponse::success();
    }

    public function getRoleResources($id): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminResourceService->getRoleResourceAssignment((int) $id));
    }

    public function getAdminResources($id): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminResourceService->getAdminResourceAssignment((int) $id));
    }

    public function syncRoleResources($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->syncRoleResourceAssignment((int) $id, (array) $request->get('resource_ids', []));

        return AdminResponse::success();
    }

    public function syncAdminResources($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->adminResourceService->syncAdminResourceAssignment((int) $id, (array) $request->get('resource_ids', []));

        return AdminResponse::success();
    }
}
