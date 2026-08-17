<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationVariableType
{
    public const STRING = 'string';
    public const TEXT = 'text';
    public const INTEGER = 'integer';
    public const DECIMAL = 'decimal';
    public const FLOAT = 'float';
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const JSON = 'json';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [
            self::STRING,
            self::TEXT,
            self::INTEGER,
            self::DECIMAL,
            self::FLOAT,
            self::BOOLEAN,
            self::DATE,
            self::DATETIME,
            self::JSON,
        ];
    }
}
