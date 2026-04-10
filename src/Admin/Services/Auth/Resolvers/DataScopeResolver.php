<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Support\Enums\GrantEffect;
use PTAdmin\Support\Enums\SubjectType;
use PTAdmin\Support\ValueObjects\ScopeDefinition;

class DataScopeResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return app(CapabilityServiceInterface::class)->enabled('data_scope')
            && (null !== $context->target || [] !== $context->payload || [] !== $context->attributes);
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        if (!$decision->allowed || null === $decision->effect) {
            return $decision;
        }

        if (!$decision->scope instanceof ScopeDefinition || 'self' !== $decision->scope->type) {
            return $decision;
        }

        $userId = $this->resolveUserId($subject);
        if (null === $userId) {
            return $decision;
        }

        $ownerIds = $this->resolveOwnerIds($context);
        if ([] === $ownerIds) {
            return $decision;
        }

        foreach ($ownerIds as $ownerId) {
            if ($ownerId !== $userId) {
                $decision->allowed = false;
                $decision->effect = GrantEffect::DENY;
                $decision->reason = 'Self scope does not allow access to other users\' data.';

                return $decision;
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

    private function resolveOwnerIds(AuthorizationContext $context): array
    {
        $ownerIds = [];

        foreach ([$context->payload, $context->attributes, $context->target] as $source) {
            foreach ($this->extractOwnerIds($source) as $ownerId) {
                $ownerIds[] = $ownerId;
            }
        }

        return array_values(array_unique($ownerIds));
    }

    private function extractOwnerIds($source): array
    {
        $keys = ['user_id', 'userId', 'owner_id', 'ownerId', 'created_by', 'createdBy'];

        if (\is_object($source)) {
            $resolved = [];
            foreach ($keys as $key) {
                if (!isset($source->{$key}) || null === $source->{$key} || '' === $source->{$key}) {
                    continue;
                }

                $resolved[] = (int) $source->{$key};
            }

            return $resolved;
        }

        if (!\is_array($source)) {
            return [];
        }

        $resolved = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $source) || null === $source[$key] || '' === $source[$key]) {
                continue;
            }

            $resolved[] = (int) $source[$key];
        }

        return $resolved;
    }
}
