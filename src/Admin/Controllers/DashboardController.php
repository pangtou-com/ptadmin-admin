<?php
/**
 * Author: Zane
 * Email: 873934580@qq.com
 * Date: 2026/4/21
 */

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Services\Dashboard\DashboardComposerService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Response\AdminResponse;

class DashboardController
{
    private DashboardComposerService $dashboardComposerService;

    public function __construct(DashboardComposerService $dashboardComposerService)
    {
        $this->dashboardComposerService = $dashboardComposerService;
    }

    public function console(Request $request): JsonResponse
    {
        $tenantId = $request->has('tenant_id') ? (int) $request->input('tenant_id') : null;

        return AdminResponse::success($this->dashboardComposerService->composeForUser(AdminAuth::user(), $tenantId));
    }
}
