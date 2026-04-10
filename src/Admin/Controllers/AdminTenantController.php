<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\Request;
use PTAdmin\Contracts\Auth\AdminTenantServiceInterface;
use PTAdmin\Foundation\Response\AdminResponse;

class AdminTenantController extends AbstractBackgroundController
{
    private AdminTenantServiceInterface $tenantService;

    public function __construct(AdminTenantServiceInterface $tenantService)
    {
        parent::__construct();
        $this->tenantService = $tenantService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->tenantService->lists($request->all()));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'settings_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->tenantService->create($data)->toArray());
    }

    public function edit(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'code' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:100',
            'status' => 'sometimes|integer|min:0|max:1',
            'settings_json' => 'nullable|array',
        ]);

        return AdminResponse::success($this->tenantService->update($id, $data)->toArray());
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        foreach ($this->getIds() as $id) {
            $this->tenantService->delete((int) $id);
        }

        return AdminResponse::success();
    }
}
