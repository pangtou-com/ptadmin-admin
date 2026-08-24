<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;

/**
 * Request-scoped snapshots shared by the built-in dashboard widgets.
 */
final class CoreDashboardWidgetSupport
{
    private AuthorizationServiceInterface $authorizationService;

    public function __construct(AuthorizationServiceInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function roleCount(DashboardWidgetContext $context): int
    {
        return (int) $this->snapshot($context, 'roles', function () use ($context): int {
            if (!Schema::hasTable('admin_user_roles') || !Schema::hasTable('admin_roles')) {
                return 0;
            }

            return (int) AdminUserRole::query()
                ->where('user_id', $context->userId())
                ->when(null !== $context->tenantId(), function ($query) use ($context): void {
                    $query->where('tenant_id', $context->tenantId());
                }, function ($query): void {
                    $query->whereNull('tenant_id');
                })
                ->whereHas('role', function ($query): void {
                    $query->where('status', 1);
                })
                ->count();
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function visibleResources(DashboardWidgetContext $context): array
    {
        return (array) $this->snapshot($context, 'resources', function () use ($context): array {
            if (!Schema::hasTable('admin_resources')) {
                return [];
            }

            $resources = AdminResource::query()
                ->whereNull('deleted_at')
                ->where('status', 1)
                ->orderBy('sort')
                ->orderBy('id')
                ->get(['name', 'title', 'route', 'icon', 'parent_id', 'is_nav'])
                ->map(static function (AdminResource $resource): array {
                    return [
                        'name' => (string) $resource->name,
                        'title' => (string) $resource->title,
                        'route' => (string) ($resource->route ?? ''),
                        'icon' => (string) ($resource->icon ?? ''),
                        'parent_id' => (int) ($resource->parent_id ?? 0),
                        'is_nav' => (int) ($resource->is_nav ?? 0),
                    ];
                })->all();

            if ($context->isFounder() || [] === $resources) {
                return $resources;
            }

            $admin = $this->admin($context);
            if (!$admin instanceof Admin) {
                return [];
            }

            $visibleNames = array_flip($this->authorizationService->visibleResources(
                $admin,
                array_values(array_map(static function (array $resource): string {
                    return $resource['name'];
                }, $resources))
            ));

            return array_values(array_filter($resources, static function (array $resource) use ($visibleNames): bool {
                return isset($visibleNames[$resource['name']]);
            }));
        });
    }

    /** @return array{unread:int,items:array<int,array<string,mixed>>} */
    public function notifications(DashboardWidgetContext $context, int $limit = 8): array
    {
        $snapshot = (array) $this->snapshot($context, 'notifications', function () use ($context): array {
            if (!Schema::hasTable('notification_receipts') || !Schema::hasTable('notification_messages')) {
                return ['unread' => 0, 'items' => []];
            }

            $base = NotificationReceipt::query()
                ->join('notification_messages', 'notification_messages.id', '=', 'notification_receipts.notification_id')
                ->where('notification_receipts.receiver_type', NotificationReceipt::RECEIVER_ADMIN)
                ->where('notification_receipts.receiver_id', $context->userId())
                ->whereNull('notification_receipts.deleted_at')
                ->where(function ($query): void {
                    $query->whereNull('notification_messages.expires_at')
                        ->orWhere('notification_messages.expires_at', '>', time());
                });

            $unread = (int) (clone $base)->whereNull('notification_receipts.read_at')->count();
            $items = (clone $base)
                ->select([
                    'notification_messages.id',
                    'notification_messages.title',
                    'notification_messages.content',
                    'notification_messages.category',
                    'notification_messages.level',
                    'notification_messages.created_at',
                    'notification_receipts.read_at',
                ])
                ->orderByDesc('notification_messages.id')
                ->limit(20)
                ->get()
                ->map(static function ($item): array {
                    $createdAt = (int) ($item->created_at ?? 0);
                    return [
                        'id' => (string) $item->id,
                        'title' => (string) $item->title,
                        'content' => (string) ($item->content ?? ''),
                        'category' => (string) ($item->category ?? 'notice'),
                        'level' => (string) ($item->level ?? 'info'),
                        'read' => null !== $item->read_at,
                        'created_at' => $createdAt > 0 ? date('m-d H:i', $createdAt) : '',
                    ];
                })->all();

            return ['unread' => $unread, 'items' => $items];
        });

        $snapshot['items'] = array_slice((array) ($snapshot['items'] ?? []), 0, max(1, min($limit, 20)));

        return $snapshot;
    }

    /** @return array{items:array<int,array<string,mixed>>,categories:array<int,string>,series:array<int,int>} */
    public function operations(DashboardWidgetContext $context, int $limit = 8): array
    {
        $snapshot = (array) $this->snapshot($context, 'operations', function () use ($context): array {
            if (!Schema::hasTable('operation_records')) {
                return ['items' => [], 'categories' => [], 'series' => []];
            }

            $records = OperationRecord::query()
                ->where('admin_id', $context->userId())
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'title', 'resource_name', 'status', 'response_code', 'created_at', 'url']);

            $items = $records->map(static function (OperationRecord $record): array {
                $createdAt = (int) ($record->getRawOriginal('created_at') ?? 0);
                $title = trim((string) $record->title);
                if ('' === $title) {
                    $title = trim((string) $record->resource_name);
                }
                if ('' === $title) {
                    $title = trim((string) $record->url);
                }

                return [
                    'id' => (string) $record->id,
                    'title' => '' !== $title ? $title : '后台操作',
                    'status' => 'success' === (string) $record->status ? 'success' : 'danger',
                    'meta' => ($createdAt > 0 ? date('m-d H:i', $createdAt) : '').' · '.(string) $record->response_code,
                    'created_at' => $createdAt,
                ];
            })->all();

            if ([] === $items) {
                return ['items' => [], 'categories' => [], 'series' => []];
            }

            $from = strtotime('-6 days 00:00:00');
            $daily = [];
            for ($offset = 0; $offset < 7; $offset++) {
                $timestamp = $from + ($offset * 86400);
                $key = date('Y-m-d', $timestamp);
                $daily[$key] = 0;
            }

            $trendRecords = OperationRecord::query()
                ->where('admin_id', $context->userId())
                ->where('created_at', '>=', $from)
                ->get(['created_at']);
            foreach ($trendRecords as $record) {
                $key = date('Y-m-d', (int) $record->getRawOriginal('created_at'));
                if (array_key_exists($key, $daily)) {
                    $daily[$key]++;
                }
            }

            return [
                'items' => $items,
                'categories' => array_values(array_map(static function (string $key): string {
                    return date('m/d', strtotime($key));
                }, array_keys($daily))),
                'series' => array_values($daily),
            ];
        });

        $snapshot['items'] = array_slice((array) ($snapshot['items'] ?? []), 0, max(1, min($limit, 20)));

        return $snapshot;
    }

    /** @return mixed */
    private function snapshot(DashboardWidgetContext $context, string $name, callable $resolver)
    {
        return $context->queryCache()->remember('ptadmin.dashboard.core.'.$name.'.'.$context->userId().'.'.(string) $context->tenantId(), $resolver);
    }

    private function admin(DashboardWidgetContext $context): ?Admin
    {
        return $context->queryCache()->remember('ptadmin.dashboard.core.admin.'.$context->userId(), static function () use ($context): ?Admin {
            if (!Schema::hasTable('admins')) {
                return null;
            }

            return Admin::query()->find($context->userId());
        });
    }
}
