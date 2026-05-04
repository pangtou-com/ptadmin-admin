<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Services\Dashboard\DashboardLayoutService;
use PTAdmin\Admin\Services\Dashboard\DashboardWidgetRegistryService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Response\AdminResponse;

class AdminDashboardManageController extends AbstractBackgroundController
{
    private DashboardLayoutService $layoutService;
    private DashboardWidgetRegistryService $registry;

    public function __construct(DashboardLayoutService $layoutService, DashboardWidgetRegistryService $registry)
    {
        $this->layoutService = $layoutService;
        $this->registry = $registry;
    }

    public function roleWidgets(int $id, Request $request): JsonResponse
    {
        AdminRole::query()->findOrFail($id);
        $tenantId = $this->resolveTenantId($request);
        $selected = $this->expandSelectedWidgets($this->layoutService->getRoleWidgets($id, $tenantId));

        return AdminResponse::success([
            'available_widgets' => $this->registry->allPublic(),
            'selected_widgets' => $selected,
        ]);
    }

    public function saveRoleWidgets(int $id, Request $request): JsonResponse
    {
        AdminRole::query()->findOrFail($id);
        $tenantId = $this->resolveTenantId($request);
        $payload = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_code' => 'required|string|max:150',
            'widgets.*.enabled' => 'sometimes|boolean',
            'widgets.*.sort' => 'sometimes|integer',
            'widgets.*.layout' => 'sometimes|array',
            'widgets.*.config' => 'sometimes|array',
        ]);

        $widgets = (array) ($payload['widgets'] ?? array());
        $this->assertWidgetCodesExist($widgets);
        $this->layoutService->saveRoleWidgets($id, $widgets, $tenantId);

        return AdminResponse::success();
    }

    public function userWidgets(int $id, Request $request): JsonResponse
    {
        Admin::query()->findOrFail($id);
        $tenantId = $this->resolveTenantId($request);
        $selected = $this->expandSelectedWidgets($this->layoutService->getUserWidgets($id, $tenantId));

        return AdminResponse::success([
            'available_widgets' => $this->registry->allPublic(),
            'selected_widgets' => $selected,
        ]);
    }

    public function saveUserWidgets(int $id, Request $request): JsonResponse
    {
        Admin::query()->findOrFail($id);
        $tenantId = $this->resolveTenantId($request);
        $payload = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_code' => 'required|string|max:150',
            'widgets.*.enabled' => 'sometimes|boolean',
            'widgets.*.sort' => 'sometimes|integer',
            'widgets.*.layout' => 'sometimes|array',
            'widgets.*.config' => 'sometimes|array',
        ]);

        $widgets = (array) ($payload['widgets'] ?? array());
        $this->assertWidgetCodesExist($widgets);
        $this->layoutService->saveUserWidgets($id, $widgets, $tenantId);

        return AdminResponse::success();
    }

    public function meWidgets(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $user = AdminAuth::user();
        $selected = $this->expandSelectedWidgets($this->layoutService->getUserWidgets((int) $user->id, $tenantId));

        return AdminResponse::success([
            'available_widgets' => $this->registry->visiblePublicFor($user),
            'selected_widgets' => $selected,
        ]);
    }

    public function saveMeWidgets(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $user = AdminAuth::user();
        $payload = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_code' => 'required|string|max:150',
            'widgets.*.enabled' => 'sometimes|boolean',
            'widgets.*.sort' => 'sometimes|integer',
            'widgets.*.layout' => 'sometimes|array',
            'widgets.*.config' => 'sometimes|array',
        ]);

        $widgets = (array) ($payload['widgets'] ?? array());
        $this->assertWidgetCodesExist($widgets);
        $this->layoutService->saveUserWidgets((int) $user->id, $widgets, $tenantId);

        return AdminResponse::success();
    }

    private function resolveTenantId(Request $request): ?int
    {
        return $request->has('tenant_id') ? (int) $request->input('tenant_id') : null;
    }

    /**
     * @param array<int, array<string, mixed>> $widgets
     */
    private function assertWidgetCodesExist(array $widgets): void
    {
        foreach ($widgets as $widget) {
            $code = trim((string) ($widget['widget_code'] ?? ''));
            if ('' === $code) {
                continue;
            }

            $this->registry->find($code);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $selected
     *
     * @return array<int, array<string, mixed>>
     */
    private function expandSelectedWidgets(array $selected): array
    {
        $results = [];

        foreach ($selected as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $code = trim((string) ($item['widget_code'] ?? ''));
            if ('' === $code) {
                continue;
            }

            try {
                $definition = $this->registry->toPublicDefinition($this->registry->find($code));
            } catch (BackgroundException $exception) {
                continue;
            }

            $results[] = array_merge($definition, [
                'enabled' => (bool) ($item['enabled'] ?? true),
                'sort' => (int) ($item['sort'] ?? 0),
                'layout' => (array) ($item['layout'] ?? array()),
                'config' => (array) ($item['config'] ?? array()),
            ]);
        }

        return $results;
    }
}
