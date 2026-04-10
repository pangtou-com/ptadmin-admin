<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Models\AdminDepartment;
use PTAdmin\Admin\Models\AdminOrganization;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Support\Enums\GrantEffect;

class TenantGrantResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return app(CapabilityServiceInterface::class)->enabled('tenant');
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        if (null === $context->tenantId) {
            return $this->deny($decision, 'Tenant capability is enabled, but tenant context was not provided.');
        }

        $organization = $this->findOrganization($context->organizationId);
        if (null !== $context->organizationId && null === $organization) {
            return $this->deny($decision, sprintf('Organization [%d] was not found.', $context->organizationId));
        }

        if (null !== $organization && (int) $organization->tenant_id !== $context->tenantId) {
            return $this->deny($decision, 'Organization tenant does not match the current authorization context.');
        }

        $department = $this->findDepartment($context->departmentId);
        if (null !== $context->departmentId && null === $department) {
            return $this->deny($decision, sprintf('Department [%d] was not found.', $context->departmentId));
        }

        if (null !== $department && (int) $department->tenant_id !== $context->tenantId) {
            return $this->deny($decision, 'Department tenant does not match the current authorization context.');
        }

        if (null !== $organization && null !== $department && (int) $department->organization_id !== (int) $organization->id) {
            return $this->deny($decision, 'Department does not belong to the current organization context.');
        }

        $mismatchedTenantId = $this->findMismatchedTenantId($context->tenantId, $context->payload, $context->attributes, $context->target);
        if (null !== $mismatchedTenantId) {
            return $this->deny($decision, sprintf('Tenant context mismatch detected. Expected [%d], got [%d].', $context->tenantId, $mismatchedTenantId));
        }

        return $decision;
    }

    private function findOrganization(?int $organizationId): ?AdminOrganization
    {
        if (null === $organizationId) {
            return null;
        }

        return AdminOrganization::query()
            ->whereNull('deleted_at')
            ->find($organizationId);
    }

    private function findDepartment(?int $departmentId): ?AdminDepartment
    {
        if (null === $departmentId) {
            return null;
        }

        return AdminDepartment::query()
            ->whereNull('deleted_at')
            ->find($departmentId);
    }

    private function findMismatchedTenantId(int $tenantId, array $payload, array $attributes, $target): ?int
    {
        foreach ([$payload, $attributes] as $source) {
            foreach (['tenant_id', 'tenantId'] as $key) {
                if (!array_key_exists($key, $source) || null === $source[$key] || '' === $source[$key]) {
                    continue;
                }

                if ((int) $source[$key] !== $tenantId) {
                    return (int) $source[$key];
                }
            }
        }

        if (\is_object($target)) {
            foreach (['tenant_id', 'tenantId'] as $key) {
                if (!isset($target->{$key}) || null === $target->{$key} || '' === $target->{$key}) {
                    continue;
                }

                if ((int) $target->{$key} !== $tenantId) {
                    return (int) $target->{$key};
                }
            }
        }

        return null;
    }

    private function deny(AuthorizationDecision $decision, string $reason): AuthorizationDecision
    {
        $decision->allowed = false;
        $decision->effect = GrantEffect::DENY;
        $decision->reason = $reason;

        return $decision;
    }
}
