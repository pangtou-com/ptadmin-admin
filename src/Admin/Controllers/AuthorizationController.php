<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use PTAdmin\Admin\Services\Auth\AuthorizationBootstrapService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Response\AdminResponse;

class AuthorizationController extends AbstractBackgroundController
{
    private AuthorizationBootstrapService $bootstrapService;

    public function __construct(AuthorizationBootstrapService $bootstrapService)
    {
        $this->bootstrapService = $bootstrapService;
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->bootstrapService->status());
    }
    
    public function profile(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success(AdminAuth::user());
    }
}
