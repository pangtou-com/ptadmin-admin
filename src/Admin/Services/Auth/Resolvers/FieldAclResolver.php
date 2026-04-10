<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Support\Enums\GrantEffect;

class FieldAclResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return app(CapabilityServiceInterface::class)->enabled('field_acl')
            && [] !== $this->extractRequestedFields($context);
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        if (!$decision->allowed || null === $decision->effect) {
            return $decision;
        }

        $requestedFields = $this->extractRequestedFields($context);
        if ([] === $requestedFields) {
            return $decision;
        }

        $cleanContext = $this->withoutFieldConstraints($context);
        $deniedFields = [];

        foreach ($requestedFields as $fieldName) {
            $fieldResourceCode = $this->resolveFieldResourceCode($resourceCode, $fieldName, $context);
            if (null === $fieldResourceCode || null === AdminResource::findByCode($fieldResourceCode)) {
                continue;
            }

            if (
                app(AuthorizationServiceInterface::class)->allows($subject, 'view', $fieldResourceCode, $cleanContext)
                || app(AuthorizationServiceInterface::class)->allows($subject, 'access', $fieldResourceCode, $cleanContext)
            ) {
                continue;
            }

            $deniedFields[] = $fieldName;
        }

        if ([] !== $deniedFields) {
            $decision->allowed = false;
            $decision->effect = GrantEffect::DENY;
            $decision->reason = sprintf('Field access denied: %s.', implode(', ', $deniedFields));
        }

        return $decision;
    }

    private function extractRequestedFields(AuthorizationContext $context): array
    {
        foreach ([$context->attributes, $context->payload] as $source) {
            foreach (['fields', 'field_names', 'fieldNames'] as $key) {
                if (!array_key_exists($key, $source) || null === $source[$key] || '' === $source[$key]) {
                    continue;
                }

                $values = \is_array($source[$key]) ? $source[$key] : [$source[$key]];

                return array_values(array_unique(array_filter(array_map(static function ($value): ?string {
                    if (null === $value || '' === $value) {
                        return null;
                    }

                    return (string) $value;
                }, $values))));
            }
        }

        return [];
    }

    private function resolveFieldResourceCode(string $resourceCode, string $fieldName, AuthorizationContext $context): ?string
    {
        foreach ([$context->attributes, $context->payload] as $source) {
            if (!isset($source['field_resources']) || !\is_array($source['field_resources'])) {
                continue;
            }

            if (isset($source['field_resources'][$fieldName]) && null !== $source['field_resources'][$fieldName] && '' !== $source['field_resources'][$fieldName]) {
                return (string) $source['field_resources'][$fieldName];
            }
        }

        if (str_ends_with($resourceCode, '.page')) {
            return substr($resourceCode, 0, -5).'.field.'.$fieldName;
        }

        return $resourceCode.'.field.'.$fieldName;
    }

    private function withoutFieldConstraints(AuthorizationContext $context): AuthorizationContext
    {
        $clone = new AuthorizationContext();
        $clone->tenantId = $context->tenantId;
        $clone->organizationId = $context->organizationId;
        $clone->departmentId = $context->departmentId;
        $clone->target = $context->target;
        $clone->payload = $context->payload;
        $clone->attributes = $context->attributes;

        foreach (['fields', 'field_names', 'fieldNames', 'field_resources'] as $key) {
            unset($clone->payload[$key], $clone->attributes[$key]);
        }

        return $clone;
    }
}
