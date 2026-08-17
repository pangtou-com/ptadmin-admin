<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationDispatchMode
{
    public const SELECT_ONE = 'select_one';
    public const FAN_OUT = 'fan_out';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::SELECT_ONE, self::FAN_OUT];
    }
}
