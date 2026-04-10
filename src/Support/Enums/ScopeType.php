<?php

declare(strict_types=1);

namespace PTAdmin\Support\Enums;

final class ScopeType
{
    public const ALL = 'all';
    public const ORGANIZATION = 'organization';
    public const DEPARTMENT = 'department';
    public const SELF = 'self';
    public const CUSTOM = 'custom';

    public static function all(): array
    {
        return [
            self::ALL,
            self::ORGANIZATION,
            self::DEPARTMENT,
            self::SELF,
            self::CUSTOM,
        ];
    }
}
