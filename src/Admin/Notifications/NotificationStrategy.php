<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationStrategy
{
    public const FIXED = 'fixed';
    public const PRIORITY = 'priority';
    public const ROUND_ROBIN = 'round_robin';
    public const WEIGHTED = 'weighted';
    public const FANOUT = 'fanout';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::FIXED, self::PRIORITY, self::ROUND_ROBIN, self::WEIGHTED, self::FANOUT];
    }
}
