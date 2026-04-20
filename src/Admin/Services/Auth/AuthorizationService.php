<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Services\Auth\Resolvers\BasicGrantResolver;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    private ?int $tenantId = null;
    private array $resolvers;

    public function __construct(?array $resolvers = null)
    {
        $this->resolvers = null === $resolvers ? $this->resolveDefaultResolvers() : $resolvers;
    }

    public function allows($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): bool
    {
        return $this->decision($subject, $ability, $resourceCode, $context)->allowed;
    }

    public function denies($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): bool
    {
        return !$this->allows($subject, $ability, $resourceCode, $context);
    }

    public function decision($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): AuthorizationDecision
    {
        $context = $context ?? new AuthorizationContext();
        if (null === $context->tenantId && null !== $this->tenantId) {
            $context->tenantId = $this->tenantId;
        }

        $decision = new AuthorizationDecision();
        $decision->resourceCode = $resourceCode;
        $decision->ability = $ability;
        $decision->reason = 'No grant matched.';

        if ($this->isFounder($subject)) {
            $decision->allowed = true;
            $decision->effect = 'allow';
            $decision->reason = 'Founder bypass.';

            return $decision;
        }

        foreach ($this->resolvers as $resolver) {
            if (!$resolver instanceof AuthorizationResolverInterface) {
                continue;
            }
            if (!$resolver->supports($context)) {
                continue;
            }

            $decision = $resolver->resolve($subject, $ability, $resourceCode, $context, $decision);
            if ('deny' === $decision->effect) {
                return $decision;
            }
        }

        return $decision;
    }

    public function visibleResources($subject, array $resourceCodes, ?AuthorizationContext $context = null): array
    {
        return array_values(array_filter($resourceCodes, function (string $resourceCode) use ($subject, $context): bool {
            return $this->allows($subject, 'access', $resourceCode, $context);
        }));
    }

    public function allowedButtons($subject, string $resourceCode, ?AuthorizationContext $context = null): array
    {
        return $this->allows($subject, 'access', $resourceCode, $context) ? ['access'] : [];
    }

    public function allowedFields($subject, string $resourceCode, ?AuthorizationContext $context = null): array
    {
        $context = $context ?? new AuthorizationContext();
        $cleanContext = $this->withoutFieldConstraints($context);

        if (
            !$this->allows($subject, 'view', $resourceCode, $cleanContext)
            && !$this->allows($subject, 'access', $resourceCode, $cleanContext)
        ) {
            return [];
        }

        if (!$this->fieldAclEnabled()) {
            return ['*'];
        }

        $requestedFields = $this->extractRequestedFields($context);
        if ([] === $requestedFields) {
            return ['*'];
        }

        $allowedFields = [];
        foreach ($requestedFields as $fieldName) {
            $fieldResourceCode = $this->resolveFieldResourceCode($resourceCode, $fieldName, $context);
            if (null === $fieldResourceCode || null === AdminResource::findByName($fieldResourceCode)) {
                $allowedFields[] = $fieldName;

                continue;
            }

            if (
                $this->allows($subject, 'view', $fieldResourceCode, $cleanContext)
                || $this->allows($subject, 'access', $fieldResourceCode, $cleanContext)
            ) {
                $allowedFields[] = $fieldName;
            }
        }

        return array_values(array_unique($allowedFields));
    }

    public function forTenant(?int $tenantId): AuthorizationServiceInterface
    {
        $clone = clone $this;
        $clone->tenantId = $tenantId;

        return $clone;
    }

    private function isFounder($subject): bool
    {
        return \is_object($subject) && isset($subject->is_founder) && (bool) $subject->is_founder;
    }

    private function resolveDefaultResolvers(): array
    {
        $resolverClasses = (array) config('ptadmin-auth.resolvers', [BasicGrantResolver::class]);
        $resolvers = [];

        foreach ($resolverClasses as $resolverClass) {
            if (!\is_string($resolverClass) || '' === $resolverClass || !class_exists($resolverClass)) {
                continue;
            }

            $resolver = app($resolverClass);
            if ($resolver instanceof AuthorizationResolverInterface) {
                $resolvers[] = $resolver;
            }
        }

        if (0 === \count($resolvers)) {
            $resolvers[] = app(BasicGrantResolver::class);
        }

        return $resolvers;
    }

    private function fieldAclEnabled(): bool
    {
        $container = Container::getInstance();
        if (null === $container || !$container->bound(CapabilityServiceInterface::class)) {
            return false;
        }

        return $container->make(CapabilityServiceInterface::class)->enabled('field_acl');
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

        if (Str::endsWith($resourceCode, '.page')) {
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
