<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

use PTAdmin\Support\ValueObjects\GrantPayload;

interface AdminGrantServiceInterface
{
    public function grantToRole(int $roleId, GrantPayload $payload, ?int $tenantId = null);

    public function grantToUser(int $userId, GrantPayload $payload, ?int $tenantId = null);

    public function revokeFromRole(int $roleId, string $resourceCode, ?int $tenantId = null): void;

    public function revokeFromUser(int $userId, string $resourceCode, ?int $tenantId = null): void;

    public function syncRoleGrants(int $roleId, array $payloads, ?int $tenantId = null): void;

    public function syncUserGrants(int $userId, array $payloads, ?int $tenantId = null): void;

    public function getRoleGrants(int $roleId, ?int $tenantId = null): array;

    public function getUserDirectGrants(int $userId, ?int $tenantId = null): array;
}
