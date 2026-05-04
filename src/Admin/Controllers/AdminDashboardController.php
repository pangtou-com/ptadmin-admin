<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Services\Dashboard\AdminDashboardService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Response\AdminResponse;

class AdminDashboardController extends AbstractBackgroundController
{
    private AdminDashboardService $adminDashboardService;

    public function __construct(AdminDashboardService $adminDashboardService)
    {
        $this->adminDashboardService = $adminDashboardService;
    }

    public function widgets(Request $request): JsonResponse
    {
        return AdminResponse::success(array(
            'results' => $this->adminDashboardService->widgets(AdminAuth::user(), $request->all()),
        ));
    }

    public function query(string $code, Request $request): JsonResponse
    {
        try {
            $tenantId = $request->has('tenant_id') ? (int) $request->input('tenant_id') : null;

            return AdminResponse::success(
                $this->adminDashboardService->queryWidget(
                    AdminAuth::user(),
                    $code,
                    (array) $request->input('query', $request->all()),
                    $tenantId
                )
            );
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
    }

    public function action(string $code, string $action, Request $request): JsonResponse
    {
        try {
            $tenantId = $request->has('tenant_id') ? (int) $request->input('tenant_id') : null;

            return AdminResponse::success(
                $this->adminDashboardService->executeWidgetAction(
                    AdminAuth::user(),
                    $code,
                    $action,
                    (array) $request->input('payload', $request->all()),
                    $tenantId
                )
            );
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
    }
}
