<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

interface AdminRoleServiceInterface
{
    /**
     * 角色列表
     * @return mixed
     */
    public function page(array $query = []);
    
    /**
     * 创建角色
     * @param array $data
     * @return mixed
     */
    public function create(array $data);
    
    /**
     * 修改角色
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);
    
    /**
     * 删除角色
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;
    
    /**
     * 修改角色状态
     * @param array $ids
     * @param int $status
     * @return void
     */
    public function updateStatus(array $ids, int $status): void;
    
    /**
     * 分配用户角色
     * @param int $roleId
     * @param array $userIds
     * @param int|null $tenantId
     * @return void
     */
    public function assignUsers(int $roleId, array $userIds, ?int $tenantId = null): void;
    
    /**
     * 同步用户角色
     * @param int $userId
     * @param array $roleIds
     * @param int|null $tenantId
     * @return void
     */
    public function syncUserRoles(int $userId, array $roleIds, ?int $tenantId = null): void;
    
    /**
     * 删除用户角色
     * @param int $userId
     * @param int|null $tenantId
     * @return void
     */
    public function deleteUserRoles(int $userId, ?int $tenantId = null): void;
    
    /**
     * 根据用户ID获取用户角色
     * @param int $userId
     * @param int|null $tenantId
     * @return array
     */
    public function getUserRoles(int $userId, ?int $tenantId = null): array;
}
