<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationTemplateFormat
{
    public const TEXT = 'text';
    public const HTML = 'html';
    public const JSON = 'json';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::TEXT, self::HTML, self::JSON];
    }
}
