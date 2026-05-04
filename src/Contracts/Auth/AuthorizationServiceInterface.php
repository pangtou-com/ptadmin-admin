<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;

interface AuthorizationServiceInterface
{
    public function allows($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): bool;

    public function denies($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): bool;

    public function decision($subject, string $ability, string $resourceCode, ?AuthorizationContext $context = null): AuthorizationDecision;

    public function visibleResources($subject, array $resourceCodes, ?AuthorizationContext $context = null): array;

    public function allowedButtons($subject, string $resourceCode, ?AuthorizationContext $context = null): array;

    public function forTenant(?int $tenantId): self;
}
