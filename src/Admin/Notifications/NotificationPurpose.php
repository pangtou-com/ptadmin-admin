<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationPurpose
{
    public const TRANSACTIONAL = 'transactional';
    public const VERIFICATION = 'verification';
    public const SECURITY = 'security';
    public const MARKETING = 'marketing';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::TRANSACTIONAL, self::VERIFICATION, self::SECURITY, self::MARKETING];
    }
}
