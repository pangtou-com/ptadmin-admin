<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationSceneConfigurationStatus
{
    public const PENDING = 'pending';
    public const INCOMPLETE = 'incomplete';
    public const COMPLETE = 'complete';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [self::PENDING, self::INCOMPLETE, self::COMPLETE];
    }

    public static function titles(): array
    {
        return [
            self::PENDING => '待配置',
            self::INCOMPLETE => '配置不完整',
            self::COMPLETE => '已完成',
        ];
    }
}
