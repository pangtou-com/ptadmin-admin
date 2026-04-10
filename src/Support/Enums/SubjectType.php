<?php

declare(strict_types=1);

namespace PTAdmin\Support\Enums;

final class SubjectType
{
    public const USER = 'user';
    public const ROLE = 'role';

    public static function all(): array
    {
        return [
            self::USER,
            self::ROLE,
        ];
    }
}
