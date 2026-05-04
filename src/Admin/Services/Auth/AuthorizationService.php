<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Admin\Services\Auth\Resolvers\BasicGrantResolver;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;

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
}
