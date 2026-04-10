<?php

declare(strict_types=1);

namespace PTAdmin\Support\Enums;

final class GrantEffect
{
    public const ALLOW = 'allow';
    public const DENY = 'deny';

    public static function all(): array
    {
        return [
            self::ALLOW,
            self::DENY,
        ];
    }
}
