<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Support\Enums\Ability;
use PTAdmin\Support\Enums\GrantEffect;
use PTAdmin\Support\Enums\SubjectType;
use PTAdmin\Support\ValueObjects\ScopeDefinition;

class BasicGrantResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return true;
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        $resource = AdminResource::findByCode($resourceCode);
        if (null === $resource) {
            $decision->reason = sprintf('Resource [%s] was not found.', $resourceCode);

            return $decision;
        }

        $resolvedSubject = $this->resolveSubject($subject);
        if (null === $resolvedSubject) {
            $decision->reason = 'Unable to resolve authorization subject.';

            return $decision;
        }

        $tenantId = $context->tenantId;
        $decision->resourceCode = $resource->code;
        $directGrant = $this->findMatchedGrant($resolvedSubject['type'], $resolvedSubject['id'], $resource->id, $ability, $tenantId);

        if (null !== $directGrant) {
            return $this->applyDecision($decision, $directGrant, sprintf('Matched direct %s grant.', $resolvedSubject['type']));
        }

        if (SubjectType::USER !== $resolvedSubject['type']) {
            $decision->reason = 'No grant matched.';

            return $decision;
        }

        $roleIds = AdminUserRole::query()
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->where('user_id', $resolvedSubject['id'])
            ->pluck('role_id')
            ->filter()
            ->values()
            ->all()
        ;

        if (0 === \count($roleIds)) {
            $decision->reason = 'No user roles matched.';

            return $decision;
        }

        $roleGrant = $this->findMatchedRoleGrant($roleIds, $resource->id, $ability, $tenantId);
        if (null !== $roleGrant) {
            return $this->applyDecision($decision, $roleGrant, 'Matched role grant.');
        }

        $decision->reason = 'No grant matched.';

        return $decision;
    }

    private function resolveSubject($subject): ?array
    {
        if (\is_array($subject) && isset($subject['type'], $subject['id'])) {
            return [
                'type' => (string) $subject['type'],
                'id' => (int) $subject['id'],
            ];
        }

        if (\is_object($subject) && isset($subject->id)) {
            $type = \in_array(class_basename($subject), ['Role', 'AdminRole'], true) ? SubjectType::ROLE : SubjectType::USER;

            return [
                'type' => $type,
                'id' => (int) $subject->id,
            ];
        }

        if (is_numeric($subject)) {
            return [
                'type' => SubjectType::USER,
                'id' => (int) $subject,
            ];
        }

        return null;
    }

    private function findMatchedGrant(string $subjectType, int $subjectId, int $resourceId, string $ability, ?int $tenantId): ?AdminGrant
    {
        return AdminGrant::query()
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('resource_id', $resourceId)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', time())
                ;
            })
            ->orderByRaw("case when effect = 'deny' then 0 else 1 end")
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->first(function (AdminGrant $grant) use ($ability): bool {
                return $this->matchesAbility($grant, $ability);
            })
        ;
    }

    private function findMatchedRoleGrant(array $roleIds, int $resourceId, string $ability, ?int $tenantId): ?AdminGrant
    {
        return AdminGrant::query()
            ->when(null !== $tenantId, function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }, function ($query): void {
                $query->whereNull('tenant_id');
            })
            ->where('subject_type', SubjectType::ROLE)
            ->whereIn('subject_id', $roleIds)
            ->where('resource_id', $resourceId)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', time())
                ;
            })
            ->orderByRaw("case when effect = 'deny' then 0 else 1 end")
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->first(function (AdminGrant $grant) use ($ability): bool {
                return $this->matchesAbility($grant, $ability);
            })
        ;
    }

    private function matchesAbility(AdminGrant $grant, string $ability): bool
    {
        $abilities = (array) $grant->abilities_json;

        return \in_array($ability, $abilities, true)
            || \in_array(Ability::MANAGE, $abilities, true)
            || (Ability::ACCESS === $ability && \in_array(Ability::VIEW, $abilities, true));
    }

    private function applyDecision(AuthorizationDecision $decision, AdminGrant $grant, string $reason): AuthorizationDecision
    {
        $decision->allowed = GrantEffect::ALLOW === $grant->effect;
        $decision->effect = $grant->effect;
        $decision->matchedGrantId = $grant->id;
        $decision->scope = new ScopeDefinition($grant->scope_type, $grant->scope_value_json);
        $decision->reason = $reason;

        return $decision;
    }
}
