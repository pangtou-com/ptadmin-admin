<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\Request;
use PTAdmin\Admin\Services\Auth\AuthorizationBootstrapService;
use PTAdmin\Foundation\Response\AdminResponse;

class AuthorizationController extends AbstractBackgroundController
{
    private AuthorizationBootstrapService $bootstrapService;

    public function __construct(AuthorizationBootstrapService $bootstrapService)
    {
        parent::__construct();
        $this->bootstrapService = $bootstrapService;
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->bootstrapService->status());
    }

    public function bootstrapFounder(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:4|max:20',
            'password' => 'required|string|min:6|max:32',
            'nickname' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'mobile' => ['nullable', 'max:30', 'regex:/^1\d{10}$/'],
            'bootstrap_authorization' => 'sometimes|boolean',
            'role_code' => 'sometimes|string|max:100',
            'role_name' => 'sometimes|string|max:100',
        ]);

        $result = $this->bootstrapService->bootstrapFounder(
            (string) $data['username'],
            (string) $data['password'],
            (string) ($data['nickname'] ?? 'root'),
            $data['email'] ?? null,
            $data['mobile'] ?? null,
            (bool) ($data['bootstrap_authorization'] ?? true),
            (string) ($data['role_code'] ?? 'super_admin'),
            (string) ($data['role_name'] ?? '超级管理员')
        );

        return AdminResponse::success($result);
    }

    public function bootstrap(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'role_code' => 'sometimes|string|max:100',
            'role_name' => 'sometimes|string|max:100',
            'assign_user_id' => 'nullable|integer|min:1',
            'force' => 'sometimes|boolean',
        ]);

        $result = $this->bootstrapService->bootstrap(
            (string) ($data['role_code'] ?? 'super_admin'),
            (string) ($data['role_name'] ?? '超级管理员'),
            isset($data['assign_user_id']) ? (int) $data['assign_user_id'] : null,
            (bool) ($data['force'] ?? false)
        );

        return AdminResponse::success($result);
    }
}
