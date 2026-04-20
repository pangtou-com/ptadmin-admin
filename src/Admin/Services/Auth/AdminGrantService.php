<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Contracts\Auth\AdminGrantServiceInterface;
use PTAdmin\Foundation\Exceptions\ServiceException;
use PTAdmin\Support\Enums\Ability;
use PTAdmin\Support\Enums\SubjectType;
use PTAdmin\Support\ValueObjects\GrantPayload;

class AdminGrantService implements AdminGrantServiceInterface
{
    public function grantToRole(int $roleId, GrantPayload $payload, ?int $tenantId = null)
    {
        return $this->upsertGrant(SubjectType::ROLE, $roleId, $payload, $tenantId);
    }

    public function grantToUser(int $userId, GrantPayload $payload, ?int $tenantId = null)
    {
        return $this->upsertGrant(SubjectType::USER, $userId, $payload, $tenantId);
    }

    public function revokeFromRole(int $roleId, string $resourceCode, ?int $tenantId = null): void
    {
        $this->deleteGrant(SubjectType::ROLE, $roleId, $resourceCode, $tenantId);
    }

    public function revokeFromUser(int $userId, string $resourceCode, ?int $tenantId = null): void
    {
        $this->deleteGrant(SubjectType::USER, $userId, $resourceCode, $tenantId);
    }

    public function syncRoleGrants(int $roleId, array $payloads, ?int $tenantId = null): void
    {
        AdminGrant::query()
            ->where('subject_type', SubjectType::ROLE)
            ->where('subject_id', $roleId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->delete();

        foreach ($payloads as $payload) {
            $this->grantToRole($roleId, $payload instanceof GrantPayload ? $payload : GrantPayload::fromArray((array) $payload), $tenantId);
        }
    }

    public function syncUserGrants(int $userId, array $payloads, ?int $tenantId = null): void
    {
        AdminGrant::query()
            ->where('subject_type', SubjectType::USER)
            ->where('subject_id', $userId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->delete();

        foreach ($payloads as $payload) {
            $this->grantToUser($userId, $payload instanceof GrantPayload ? $payload : GrantPayload::fromArray((array) $payload), $tenantId);
        }
    }

    public function getRoleGrants(int $roleId, ?int $tenantId = null): array
    {
        return $this->getGrants(SubjectType::ROLE, $roleId, $tenantId);
    }

    public function getUserDirectGrants(int $userId, ?int $tenantId = null): array
    {
        return $this->getGrants(SubjectType::USER, $userId, $tenantId);
    }

    private function upsertGrant(string $subjectType, int $subjectId, GrantPayload $payload, ?int $tenantId = null)
    {
        $resource = AdminResource::findByName($payload->resourceCode);
        if (null === $resource) {
            throw new ServiceException(sprintf('Resource [%s] does not exist.', $payload->resourceCode));
        }

        return AdminGrant::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'resource_id' => $resource->id,
            ],
            [
                'effect' => $payload->effect,
                'abilities_json' => 0 === \count($payload->abilities) ? [Ability::ACCESS] : $payload->abilities,
                'scope_type' => $payload->scopeType,
                'scope_value_json' => $this->normalizeJsonValue($payload->scopeValue),
                'conditions_json' => $payload->conditions,
                'priority' => $payload->priority,
                'expires_at' => $payload->expiresAt,
            ]
        );
    }

    private function deleteGrant(string $subjectType, int $subjectId, string $resourceCode, ?int $tenantId = null): void
    {
        $resourceId = AdminResource::query()->where('name', $resourceCode)->value('id');
        if (!$resourceId) {
            return;
        }

        AdminGrant::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('resource_id', $resourceId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->delete();
    }

    private function getGrants(string $subjectType, int $subjectId, ?int $tenantId = null): array
    {
        return AdminGrant::query()
            ->with('resource')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->map(static function (AdminGrant $grant): array {
                return [
                    'id' => (int) $grant->id,
                    'tenant_id' => null !== $grant->tenant_id ? (int) $grant->tenant_id : null,
                    'subject_type' => (string) $grant->subject_type,
                    'subject_id' => (int) $grant->subject_id,
                    'resource_id' => (int) $grant->resource_id,
                    'resource_code' => null !== $grant->resource ? (string) $grant->resource->name : '',
                    'resource_name' => null !== $grant->resource ? (string) $grant->resource->title : '',
                    'effect' => (string) $grant->effect,
                    'abilities' => (array) ($grant->abilities_json ?? []),
                    'scope_type' => $grant->scope_type,
                    'scope_value' => $grant->scope_value_json,
                    'conditions' => $grant->conditions_json,
                    'priority' => (int) $grant->priority,
                    'expires_at' => null !== $grant->expires_at ? (int) $grant->expires_at : null,
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeJsonValue($value)
    {
        if (null === $value) {
            return null;
        }

        if (\is_array($value)) {
            return $value;
        }

        return ['value' => $value];
    }
}
