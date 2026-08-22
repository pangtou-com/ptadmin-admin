<?php
/**
 * Author: Zane
 * Email: 873934580@qq.com
 * Date: 2026/4/21
 */

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PTAdmin\Admin\Services\AdminFrontendBuildService;
use PTAdmin\Admin\Services\Dashboard\DashboardComposerService;
use PTAdmin\Admin\Services\ApplicationStatusSyncService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Response\AdminResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController
{
    private DashboardComposerService $dashboardComposerService;
    private ApplicationStatusSyncService $applicationStatusSyncService;

    public function __construct(
        DashboardComposerService $dashboardComposerService,
        ApplicationStatusSyncService $applicationStatusSyncService
    ) {
        $this->dashboardComposerService = $dashboardComposerService;
        $this->applicationStatusSyncService = $applicationStatusSyncService;
    }

    public function console(Request $request): JsonResponse
    {
        $tenantId = $request->has('tenant_id') ? (int) $request->input('tenant_id') : null;

        return AdminResponse::success($this->dashboardComposerService->composeForUser(AdminAuth::user(), $tenantId));
    }

    public function syncApplicationStatus(): JsonResponse
    {
        $this->applicationStatusSyncService->sync(true);

        return AdminResponse::success($this->dashboardComposerService->summary(false));
    }

    /**
     * 通过后台流式更新并发布主应用前端构建包。
     */
    public function updateFrontend(Request $request, AdminFrontendBuildService $service): StreamedResponse
    {
        $data = $request->validate([
            'ref' => 'sometimes|string|max:100',
            'backend_version' => 'sometimes|string|max:100',
        ]);

        return response()->stream(function () use ($data, $service): void {
            $lock = null;

            try {
                if (!AdminAuth::isFounder()) {
                    $this->sendStreamMessage([
                        'type' => 'error',
                        'message' => '只有创始人可以更新后台前端。',
                        'data' => [],
                    ]);

                    return;
                }

                $lock = Cache::lock('ptadmin:admin-frontend-update', 900);
                if (!$lock->get()) {
                    $this->sendStreamMessage([
                        'type' => 'error',
                        'message' => '后台前端正在更新，请稍后再试。',
                        'data' => [],
                    ]);

                    return;
                }

                $this->sendStreamMessage([
                    'type' => 'process',
                    'message' => '正在下载后台前端构建包，请勿关闭当前页面。',
                    'data' => [],
                ]);

                $result = $service->update(
                    dirname(__DIR__, 3),
                    base_path(),
                    (string) ($data['ref'] ?? 'latest'),
                    (string) ($data['backend_version'] ?? '')
                );

                $this->sendStreamMessage([
                    'type' => 'success',
                    'message' => '后台前端更新完成，请刷新页面加载新版本。',
                    'data' => $result,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('PTAdmin admin frontend update stream failed', [
                    'message' => $throwable->getMessage(),
                ]);

                $this->sendStreamMessage([
                    'type' => 'error',
                    'message' => $throwable->getMessage(),
                    'data' => [],
                ]);
            } finally {
                if (null !== $lock) {
                    $lock->release();
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Powered-By' => 'ptadmin',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendStreamMessage(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}
