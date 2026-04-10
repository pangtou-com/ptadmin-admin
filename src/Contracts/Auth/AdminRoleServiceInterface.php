<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

interface AdminRoleServiceInterface
{
    public function page();

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;

    public function updateStatus(array $ids, int $status): void;

    public function assignUsers(int $roleId, array $userIds, ?int $tenantId = null): void;

    public function syncUserRoles(int $userId, array $roleIds, ?int $tenantId = null): void;

    public function deleteUserRoles(int $userId, ?int $tenantId = null): void;

    public function getUserRoles(int $userId, ?int $tenantId = null): array;
}
