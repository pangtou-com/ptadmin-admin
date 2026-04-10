<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;
use PTAdmin\Support\Enums\SubjectType;

class AdminRoleService implements AdminRoleServiceInterface
{
    public function page(): LengthAwarePaginator
    {
        $roles = AdminRole::query()
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();

        $roles->getCollection()->transform(static function (AdminRole $role): array {
            return [
                'id' => (int) $role->id,
                'name' => (string) $role->code,
                'title' => (string) $role->name,
                'note' => $role->description,
                'status' => (int) $role->status,
            ];
        });

        return $roles;
    }

    public function create(array $data)
    {
        $role = new AdminRole();
        $role->fill([
            'code' => (string) ($data['code'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'description' => $data['description'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'scope_mode' => $data['scope_mode'] ?? null,
            'scope_value_json' => $data['scope_value_json'] ?? null,
            'is_system' => (int) ($data['is_system'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
            'sort' => (int) ($data['sort'] ?? 0),
        ]);
        $role->save();

        return $role->refresh();
    }

    public function update(int $id, array $data)
    {
        $payload = array_filter([
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'scope_mode' => $data['scope_mode'] ?? null,
            'scope_value_json' => $data['scope_value_json'] ?? null,
            'is_system' => isset($data['is_system']) ? (int) $data['is_system'] : null,
            'status' => isset($data['status']) ? (int) $data['status'] : null,
            'sort' => isset($data['sort']) ? (int) $data['sort'] : null,
        ], static function ($value): bool {
            return null !== $value;
        });

        $role = AdminRole::query()->findOrFail($id);
        $role->fill($payload);
        $role->save();

        return $role->refresh();
    }

    public function delete(int $id): void
    {
        $role = AdminRole::query()->findOrFail($id);
        AdminGrant::query()
            ->where('subject_type', SubjectType::ROLE)
            ->where('subject_id', $id)
            ->delete();
        AdminUserRole::query()->where('role_id', $id)->delete();
        $role->delete();
    }

    public function updateStatus(array $ids, int $status): void
    {
        AdminRole::query()->whereIn('id', array_values(array_unique(array_map('intval', $ids))))->get()->each(function (AdminRole $role) use ($status): void {
            $role->update(['status' => $status]);
        });
    }

    public function assignUsers(int $roleId, array $userIds, ?int $tenantId = null): void
    {
        $time = time();
        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            AdminUserRole::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ],
                [
                    'created_at' => $time,
                ]
            );
        }
    }

    public function syncUserRoles(int $userId, array $roleIds, ?int $tenantId = null): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        $this->deleteUserRoles($userId, $tenantId);

        foreach ($roleIds as $roleId) {
            AdminUserRole::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => time(),
            ]);
        }
    }

    public function deleteUserRoles(int $userId, ?int $tenantId = null): void
    {
        AdminUserRole::query()
            ->where('user_id', $userId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->delete();
    }

    public function getUserRoles(int $userId, ?int $tenantId = null): array
    {
        return AdminUserRole::query()
            ->with('role')
            ->where('user_id', $userId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->orderBy('id')
            ->get()
            ->map(static function (AdminUserRole $userRole): ?array {
                if (null === $userRole->role) {
                    return null;
                }

                return [
                    'id' => (int) $userRole->role->id,
                    'code' => (string) $userRole->role->code,
                    'name' => (string) $userRole->role->name,
                    'description' => $userRole->role->description,
                    'status' => (int) $userRole->role->status,
                    'sort' => (int) $userRole->role->sort,
                ];
            })
            ->filter()
            ->sortBy(static function (array $item): string {
                return sprintf('%010d-%010d', (int) $item['sort'], (int) $item['id']);
            })
            ->values()
            ->all();
    }
}
