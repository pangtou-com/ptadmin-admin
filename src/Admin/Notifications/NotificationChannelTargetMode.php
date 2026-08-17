<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationChannelTargetMode
{
    public const DYNAMIC = 'dynamic';
    public const FIXED = 'fixed';
    public const HYBRID = 'hybrid';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::DYNAMIC, self::FIXED, self::HYBRID];
    }
}
