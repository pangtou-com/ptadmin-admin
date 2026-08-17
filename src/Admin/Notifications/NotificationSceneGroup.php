<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationSceneGroup
{
    public const GENERAL = 'general';
    public const ACCOUNT = 'account';
    public const SECURITY = 'security';
    public const REVIEW = 'review';
    public const CONTENT = 'content';
    public const ORDER = 'order';
    public const SYSTEM = 'system';

    private function __construct()
    {
    }

    public static function titles(): array
    {
        return [
            self::GENERAL => '常规通知',
            self::ACCOUNT => '账户通知',
            self::SECURITY => '安全通知',
            self::REVIEW => '审核通知',
            self::CONTENT => '内容通知',
            self::ORDER => '订单通知',
            self::SYSTEM => '系统通知',
        ];
    }

    public static function title(string $code): ?string
    {
        return self::titles()[$code] ?? null;
    }
}
