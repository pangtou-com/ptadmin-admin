<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationChannel
{
    public const SITE = 'site';
    public const MAIL = 'mail';
    public const SMS = 'sms';
    public const WECHAT_WORK = 'wechat_work';
    public const WECHAT_MINI_PROGRAM = 'wechat_mini_program';
    public const WEBHOOK = 'webhook';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [
            self::SITE,
            self::MAIL,
            self::SMS,
            self::WECHAT_WORK,
            self::WECHAT_MINI_PROGRAM,
            self::WEBHOOK,
        ];
    }

    public static function normalize(string $channel): string
    {
        $channel = trim($channel);
        if (1 !== preg_match('/\A[a-z][a-z0-9_-]{0,49}\z/', $channel)) {
            throw new \InvalidArgumentException('通知渠道编码无效');
        }

        return $channel;
    }
}
