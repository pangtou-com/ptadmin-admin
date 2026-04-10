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
        parent::__construct();
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
            return AdminResponse::success(
                $this->adminDashboardService->queryWidget(AdminAuth::user(), $code, (array) $request->input('query', $request->all()))
            );
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
    }

    public function action(string $code, string $action, Request $request): JsonResponse
    {
        try {
            return AdminResponse::success(
                $this->adminDashboardService->executeWidgetAction(
                    AdminAuth::user(),
                    $code,
                    $action,
                    (array) $request->input('payload', $request->all())
                )
            );
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
    }
}
