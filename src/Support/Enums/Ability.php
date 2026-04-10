<?php

declare(strict_types=1);

namespace PTAdmin\Support\Enums;

final class Ability
{
    public const ACCESS = 'access';
    public const VIEW = 'view';
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const EXECUTE = 'execute';
    public const EXPORT = 'export';
    public const AUDIT = 'audit';
    public const APPROVE = 'approve';
    public const MANAGE = 'manage';

    public static function all(): array
    {
        return [
            self::ACCESS,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::EXECUTE,
            self::EXPORT,
            self::AUDIT,
            self::APPROVE,
            self::MANAGE,
        ];
    }
}
