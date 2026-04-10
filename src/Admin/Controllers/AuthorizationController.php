<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

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
}
