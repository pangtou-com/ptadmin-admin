<?php

declare(strict_types=1);

namespace PTAdmin\Support\Enums;

final class ResourceType
{
    public const MENU = 'menu';
    public const PAGE = 'page';
    public const BUTTON = 'button';
    public const FIELD = 'field';
    public const ROUTE = 'route';
    public const DATA = 'data';
    public const PLUGIN = 'plugin';
    public const CAPABILITY = 'capability';

    public static function all(): array
    {
        return [
            self::MENU,
            self::PAGE,
            self::BUTTON,
            self::FIELD,
            self::ROUTE,
            self::DATA,
            self::PLUGIN,
            self::CAPABILITY,
        ];
    }
}
