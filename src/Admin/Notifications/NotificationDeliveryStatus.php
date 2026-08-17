<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationDeliveryStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const ACCEPTED = 'accepted';
    public const SENT = 'sent';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::ACCEPTED,
            self::SENT,
            self::DELIVERED,
            self::FAILED,
        ];
    }
}
