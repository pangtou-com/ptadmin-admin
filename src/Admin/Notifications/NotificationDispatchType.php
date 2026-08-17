<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationDispatchType
{
    public const NOTIFICATION = 'notification';
    public const DIRECT = 'direct';

    private function __construct()
    {
    }
}
