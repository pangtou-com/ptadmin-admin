<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Services\NotificationConfigService;
use PTAdmin\Foundation\Response\AdminResponse;

final class NotificationConfigController extends AbstractBackgroundController
{
    /** @var NotificationConfigService */
    private $service;

    public function __construct(NotificationConfigService $service)
    {
        $this->service = $service;
    }

    public function scenes(Request $request): JsonResponse
    {
        return AdminResponse::success($this->service->scenes($request->only([
            'keyword',
            'source_type',
            'source_code',
            'group_code',
            'enabled',
            'channel',
            'configuration_status',
            'page',
            'per_page',
        ])));
    }

    public function scene($id): JsonResponse
    {
        return AdminResponse::success($this->service->scene((int) $id));
    }

    public function updateTemplate(Request $request, $id): JsonResponse
    {
        $payload = $request->validate([
            'config' => 'required_without:enabled|array',
            'enabled' => 'required_without:config|boolean',
        ]);

        return AdminResponse::success($this->service->updateTemplate(
            (int) $id,
            array_key_exists('config', $payload) ? $payload['config'] : null,
            array_key_exists('enabled', $payload) ? (bool) $payload['enabled'] : null
        ));
    }

    public function channels(): JsonResponse
    {
        return AdminResponse::success([
            'results' => $this->service->channels(),
        ]);
    }

    public function updateRoutes(Request $request, $id, string $channel): JsonResponse
    {
        $payload = $request->validate([
            'dispatch_mode' => 'required|string|max:20',
            'strategy' => 'nullable|string|max:30',
            'instances' => 'required|array|min:1',
            'instances.*.addon_code' => 'nullable|string|max:100',
            'instances.*.group' => 'required|string|max:50',
            'instances.*.provider' => 'required|string|max:100',
            'instances.*.instance_code' => 'required|string|max:100',
        ]);

        return AdminResponse::success($this->service->updateRoutes((int) $id, $channel, $payload));
    }

    public function clearRoutes($id, string $channel): JsonResponse
    {
        return AdminResponse::success($this->service->clearRoutes((int) $id, $channel));
    }
}
