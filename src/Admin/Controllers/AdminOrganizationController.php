<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\Request;
use PTAdmin\Contracts\Auth\AdminOrganizationServiceInterface;
use PTAdmin\Foundation\Response\AdminResponse;

class AdminOrganizationController extends AbstractBackgroundController
{
    private AdminOrganizationServiceInterface $organizationService;

    public function __construct(AdminOrganizationServiceInterface $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function organizations(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->organizationService->listOrganizations($request->all()));
    }

    public function storeOrganization(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'parent_id' => 'sometimes|integer|min:0',
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'sort' => 'sometimes|integer|min:0',
            'meta_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->organizationService->createOrganization($data)->toArray());
    }

    public function editOrganization(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'parent_id' => 'sometimes|integer|min:0',
            'code' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'sort' => 'sometimes|integer|min:0',
            'meta_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->organizationService->updateOrganization($id, $data)->toArray());
    }

    public function departments(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->organizationService->listDepartments($request->all()));
    }

    public function storeDepartment(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'organization_id' => 'required|integer|min:1',
            'parent_id' => 'sometimes|integer|min:0',
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'sort' => 'sometimes|integer|min:0',
            'meta_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->organizationService->createDepartment($data)->toArray());
    }

    public function editDepartment(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'organization_id' => 'sometimes|integer|min:1',
            'parent_id' => 'sometimes|integer|min:0',
            'code' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'sort' => 'sometimes|integer|min:0',
            'meta_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->organizationService->updateDepartment($id, $data)->toArray());
    }

    public function userRelations(int $id, Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = $request->has('tenant_id') ? (int) $request->get('tenant_id') : null;

        return AdminResponse::success($this->organizationService->getUserRelations($id, $tenantId));
    }

    public function syncUserRelations(int $id, Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'relations' => 'required|array',
            'relations.*.tenant_id' => 'nullable|integer|min:1',
            'relations.*.organization_id' => 'required|integer|min:1',
            'relations.*.department_id' => 'nullable|integer|min:1',
            'relations.*.is_primary' => 'sometimes|integer|min:0|max:1',
        ]);

        $tenantId = isset($data['tenant_id']) ? (int) $data['tenant_id'] : null;
        $this->organizationService->syncUserRelations($id, (array) $data['relations'], $tenantId);

        return AdminResponse::success($this->organizationService->getUserRelations($id, $tenantId));
    }

    public function setPrimaryRelation(int $id): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->organizationService->setPrimaryRelation($id)->toArray());
    }
}
