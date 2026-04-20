<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\AdminDepartment;
use PTAdmin\Admin\Models\AdminOrganization;
use PTAdmin\Admin\Models\AdminUserOrganizationRelation;
use PTAdmin\Contracts\Auth\AdminOrganizationServiceInterface;

class AdminOrganizationService implements AdminOrganizationServiceInterface
{
    public function listOrganizations(array $filters = []): array
    {
        return AdminOrganization::query()
            ->when(array_key_exists('tenant_id', $filters), function ($query) use ($filters): void {
                null !== $filters['tenant_id'] ? $query->where('tenant_id', (int) $filters['tenant_id']) : $query->whereNull('tenant_id');
            })
            ->when(isset($filters['parent_id']), function ($query) use ($filters): void {
                $query->where('parent_id', (int) $filters['parent_id']);
            })
            ->when(isset($filters['status']), function ($query) use ($filters): void {
                $query->where('status', (int) $filters['status']);
            })
            ->whereNull('deleted_at')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function createOrganization(array $data): AdminOrganization
    {
        $organization = new AdminOrganization();
        $organization->fill($this->normalizeOrganizationPayload($data));
        $organization->save();

        return $organization->refresh();
    }

    public function updateOrganization(int $id, array $data)
    {
        $organization = AdminOrganization::query()->findOrFail($id);
        $organization->fill($this->normalizeOrganizationPayload($data, false));
        $organization->save();

        return $organization->refresh();
    }

    public function listDepartments(array $filters = []): array
    {
        return AdminDepartment::query()
            ->when(array_key_exists('tenant_id', $filters), function ($query) use ($filters): void {
                null !== $filters['tenant_id'] ? $query->where('tenant_id', (int) $filters['tenant_id']) : $query->whereNull('tenant_id');
            })
            ->when(isset($filters['organization_id']), function ($query) use ($filters): void {
                $query->where('organization_id', (int) $filters['organization_id']);
            })
            ->when(isset($filters['parent_id']), function ($query) use ($filters): void {
                $query->where('parent_id', (int) $filters['parent_id']);
            })
            ->when(isset($filters['status']), function ($query) use ($filters): void {
                $query->where('status', (int) $filters['status']);
            })
            ->whereNull('deleted_at')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function createDepartment(array $data): AdminDepartment
    {
        $department = new AdminDepartment();
        $department->fill($this->normalizeDepartmentPayload($data));
        $department->save();

        return $department->refresh();
    }

    public function updateDepartment(int $id, array $data)
    {
        $department = AdminDepartment::query()->findOrFail($id);
        $department->fill($this->normalizeDepartmentPayload($data, false));
        $department->save();

        return $department->refresh();
    }

    public function syncUserRelations(int $userId, array $relations, ?int $tenantId = null): void
    {
        DB::transaction(function () use ($userId, $relations, $tenantId): void {
            AdminUserOrganizationRelation::query()
                ->where('user_id', $userId)
                ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                    $query->where('tenant_id', $tenantId);
                }, function ($query): void {
                    $query->whereNull('tenant_id');
                })
                ->delete();

            foreach ($relations as $relation) {
                $payload = \is_array($relation) ? $relation : [];
                if (!isset($payload['organization_id'])) {
                    continue;
                }

                AdminUserOrganizationRelation::query()->create([
                    'tenant_id' => $payload['tenant_id'] ?? $tenantId,
                    'user_id' => $userId,
                    'organization_id' => (int) $payload['organization_id'],
                    'department_id' => isset($payload['department_id']) ? (int) $payload['department_id'] : null,
                    'is_primary' => isset($payload['is_primary']) ? (int) $payload['is_primary'] : 0,
                ]);
            }
        });
    }

    public function getUserRelations(int $userId, ?int $tenantId = null): array
    {
        return AdminUserOrganizationRelation::query()
            ->with(['organization', 'department'])
            ->where('user_id', $userId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function setPrimaryRelation(int $relationId): AdminUserOrganizationRelation
    {
        /** @var AdminUserOrganizationRelation $relation */
        $relation = AdminUserOrganizationRelation::query()->findOrFail($relationId);

        DB::transaction(function () use ($relation): void {
            AdminUserOrganizationRelation::query()
                ->where('user_id', $relation->user_id)
                ->when(null !== $relation->tenant_id, function ($query) use ($relation): void {
                    $query->where('tenant_id', $relation->tenant_id);
                }, function ($query): void {
                    $query->whereNull('tenant_id');
                })
                ->update(['is_primary' => 0]);

            $relation->is_primary = 1;
            $relation->save();
        });

        return $relation->refresh();
    }

    private function normalizeOrganizationPayload(array $data, bool $withDefaults = true): array
    {
        $payload = [
            'tenant_id' => $data['tenant_id'] ?? null,
            'parent_id' => isset($data['parent_id']) ? (int) $data['parent_id'] : ($withDefaults ? 0 : null),
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : ($withDefaults ? 1 : null),
            'sort' => isset($data['sort']) ? (int) $data['sort'] : ($withDefaults ? 0 : null),
            'meta_json' => $data['meta_json'] ?? null,
        ];

        if (!$withDefaults) {
            return array_filter($payload, static function ($value): bool {
                return null !== $value;
            });
        }

        return $payload;
    }

    private function normalizeDepartmentPayload(array $data, bool $withDefaults = true): array
    {
        $payload = [
            'tenant_id' => $data['tenant_id'] ?? null,
            'organization_id' => isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            'parent_id' => isset($data['parent_id']) ? (int) $data['parent_id'] : ($withDefaults ? 0 : null),
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : ($withDefaults ? 1 : null),
            'sort' => isset($data['sort']) ? (int) $data['sort'] : ($withDefaults ? 0 : null),
            'meta_json' => $data['meta_json'] ?? null,
        ];

        if (!$withDefaults) {
            return array_filter($payload, static function ($value): bool {
                return null !== $value;
            });
        }

        return $payload;
    }
}
