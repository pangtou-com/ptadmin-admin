<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Models\AdminUserOrganizationRelation;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Support\Enums\GrantEffect;
use PTAdmin\Support\Enums\SubjectType;
use PTAdmin\Support\ValueObjects\ScopeDefinition;

class OrganizationResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return app(CapabilityServiceInterface::class)->enabled('organization');
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        if (!$decision->allowed || null === $decision->effect) {
            return $decision;
        }

        $userId = $this->resolveUserId($subject);
        if (null === $userId) {
            return $decision;
        }

        $relations = AdminUserOrganizationRelation::query()
            ->where('user_id', $userId)
            ->when(null !== $context->tenantId, function ($query) use ($context): void {
                $query->where('tenant_id', $context->tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->get();

        $organizationIds = $relations->pluck('organization_id')->filter()->map(static function ($id): int {
            return (int) $id;
        })->unique()->values()->all();

        $departmentIds = $relations->pluck('department_id')->filter()->map(static function ($id): int {
            return (int) $id;
        })->unique()->values()->all();

        if (null !== $context->organizationId && !\in_array($context->organizationId, $organizationIds, true)) {
            return $this->deny($decision, 'User is not assigned to the requested organization.');
        }

        if (null !== $context->departmentId && !\in_array($context->departmentId, $departmentIds, true)) {
            return $this->deny($decision, 'User is not assigned to the requested department.');
        }

        if (!$decision->scope instanceof ScopeDefinition || null === $decision->scope->type) {
            return $decision;
        }

        if ('organization' === $decision->scope->type) {
            $scopeOrganizationIds = $this->normalizeScopeIds($decision->scope->value, ['organization_id', 'organization_ids', 'id', 'ids']);
            if ([] === $scopeOrganizationIds) {
                return $decision;
            }

            if (null !== $context->organizationId && !\in_array($context->organizationId, $scopeOrganizationIds, true)) {
                return $this->deny($decision, 'Grant organization scope does not cover the requested organization.');
            }

            if ([] === array_intersect($organizationIds, $scopeOrganizationIds)) {
                return $this->deny($decision, 'Grant organization scope does not match any assigned organization.');
            }
        }

        if ('department' === $decision->scope->type) {
            $scopeDepartmentIds = $this->normalizeScopeIds($decision->scope->value, ['department_id', 'department_ids', 'id', 'ids']);
            if ([] === $scopeDepartmentIds) {
                return $decision;
            }

            if (null !== $context->departmentId && !\in_array($context->departmentId, $scopeDepartmentIds, true)) {
                return $this->deny($decision, 'Grant department scope does not cover the requested department.');
            }

            if ([] === array_intersect($departmentIds, $scopeDepartmentIds)) {
                return $this->deny($decision, 'Grant department scope does not match any assigned department.');
            }
        }

        return $decision;
    }

    private function resolveUserId($subject): ?int
    {
        if (\is_array($subject) && (($subject['type'] ?? null) === SubjectType::USER) && isset($subject['id'])) {
            return (int) $subject['id'];
        }

        if (\is_numeric($subject)) {
            return (int) $subject;
        }

        if (!\is_object($subject) || !isset($subject->id)) {
            return null;
        }

        return 'Role' === class_basename($subject) || 'AdminRole' === class_basename($subject)
            ? null
            : (int) $subject->id;
    }

    private function normalizeScopeIds($value, array $keys): array
    {
        if (\is_numeric($value)) {
            return [(int) $value];
        }

        if (!\is_array($value)) {
            return [];
        }

        $resolved = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            $candidate = $value[$key];
            $candidateValues = \is_array($candidate) ? $candidate : [$candidate];
            foreach ($candidateValues as $candidateValue) {
                if (null === $candidateValue || '' === $candidateValue) {
                    continue;
                }

                $resolved[] = (int) $candidateValue;
            }
        }

        if ([] === $resolved) {
            foreach ($value as $candidateValue) {
                if (null === $candidateValue || '' === $candidateValue || \is_array($candidateValue)) {
                    continue;
                }

                $resolved[] = (int) $candidateValue;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function deny(AuthorizationDecision $decision, string $reason): AuthorizationDecision
    {
        $decision->allowed = false;
        $decision->effect = GrantEffect::DENY;
        $decision->reason = $reason;

        return $decision;
    }
}
