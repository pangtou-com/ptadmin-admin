<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Support\Query\BuilderQueryApplier;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;
use PTAdmin\Support\Enums\SubjectType;

class AdminRoleService implements AdminRoleServiceInterface
{
    public function page(array $query = []): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator $roles */
        $roles = (new BuilderQueryApplier())->fetch(
            AdminRole::query()->whereNull('deleted_at'),
            $query,
            [
                'allowed_filters' => ['id', 'code', 'name', 'status', 'sort'],
                'allowed_sorts' => ['id', 'code', 'name', 'status', 'sort'],
                'allowed_keyword_fields' => ['code', 'name', 'description'],
                'keyword_fields' => ['code', 'name', 'description'],
                'default_order' => ['id' => 'desc'],
                'default_limit' => 15,
            ]
        );

        $roles->getCollection()->transform(static function (AdminRole $role): array {
            return [
                'id' =>  $role->id,
                'code' => (string) $role->code,
                'name' => (string) $role->name,
                'description' => $role->description,
                'status' => (int) $role->status,
            ];
        });

        return $roles;
    }

    public function create(array $data): AdminRole
    {
        $role = new AdminRole();
        $role->fill($data);
        $role->save();

        return $role->refresh();
    }

    public function update(int $id, array $data)
    {
        $payload = array_filter($data, static function ($value): bool {
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
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
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
    
    /**
     * 删除用户角色信息
     * @param int $userId
     * @param int|null $tenantId
     * @return void
     */
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
    
    /**
     * 根据用户ID获取用户角色
     * @param int $userId
     * @param int|null $tenantId
     * @return array
     */
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
