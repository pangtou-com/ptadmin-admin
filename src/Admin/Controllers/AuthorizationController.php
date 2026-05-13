<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Requests\ProfilePasswordRequest;
use PTAdmin\Admin\Requests\ProfileUpdateRequest;
use PTAdmin\Admin\Services\AdminService;
use PTAdmin\Admin\Services\Auth\AuthorizationBootstrapService;
use PTAdmin\Admin\Services\OperationRecordService;
use PTAdmin\Admin\Support\Query\AdminListQuery;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Response\AdminResponse;

class AuthorizationController extends AbstractBackgroundController
{
    private AuthorizationBootstrapService $bootstrapService;
    private AdminService $adminService;
    private OperationRecordService $operationRecordService;

    public function __construct(
        AuthorizationBootstrapService $bootstrapService,
        AdminService $adminService,
        OperationRecordService $operationRecordService
    )
    {
        $this->bootstrapService = $bootstrapService;
        $this->adminService = $adminService;
        $this->operationRecordService = $operationRecordService;
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->bootstrapService->status());
    }
    
    public function profile(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success(AdminAuth::user());
    }

    public function loginLogs(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::pages($this->adminService->loginLogs(AdminAuth::user(), AdminListQuery::fromRequest($request)));
    }

    public function operations(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::pages($this->operationRecordService->page(AdminListQuery::fromRequest($request), AdminAuth::user()));
    }

    public function operationDetails(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            return AdminResponse::success($this->operationRecordService->details($id, AdminAuth::user()));
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
    }

    public function password(ProfilePasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $this->adminService->updatePassword(AdminAuth::user(), (string) $data['old_password'], (string) $data['password']);
        Auth::guard(AdminAuth::getGuard())->logout();

        return AdminResponse::success();
    }

    public function updateProfile(ProfileUpdateRequest $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->adminService->updateProfile(AdminAuth::user(), $request->validated()));
    }
}
