<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

final class AuthorizationDecision
{
    public bool $allowed = false;
    public string $resourceCode = '';
    public string $ability = '';
    public ?string $effect = null;
    public $scope = null;
    public ?int $matchedGrantId = null;
    public string $reason = '';
}
