<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

use PTAdmin\Admin\Models\AdminTenant;

interface AdminTenantServiceInterface
{
    public function create(array $data): AdminTenant;

    public function update(int $id, array $data): AdminTenant;

    public function delete(int $id): void;

    public function lists(array $filters = []): array;
}
