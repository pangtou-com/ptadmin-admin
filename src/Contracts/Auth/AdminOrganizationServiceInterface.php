<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

use PTAdmin\Admin\Models\AdminDepartment;
use PTAdmin\Admin\Models\AdminOrganization;
use PTAdmin\Admin\Models\AdminUserOrganizationRelation;

interface AdminOrganizationServiceInterface
{
    public function listOrganizations(array $filters = []): array;

    public function createOrganization(array $data): AdminOrganization;

    public function updateOrganization(int $id, array $data);

    public function listDepartments(array $filters = []): array;

    public function createDepartment(array $data): AdminDepartment;

    public function updateDepartment(int $id, array $data);

    public function syncUserRelations(int $userId, array $relations, ?int $tenantId = null): void;

    public function getUserRelations(int $userId, ?int $tenantId = null): array;

    public function setPrimaryRelation(int $relationId): AdminUserOrganizationRelation;
}
