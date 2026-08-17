<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationTemplateMode
{
    public const CONTENT = 'content';
    public const REFERENCE = 'reference';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::CONTENT, self::REFERENCE];
    }
}
