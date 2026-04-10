<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;

interface AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool;

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision;
}
