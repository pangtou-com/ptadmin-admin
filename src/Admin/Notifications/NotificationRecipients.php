<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\User;
use Traversable;

final class NotificationRecipients implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int, NotificationRecipient> */
    private $recipients;

    /** @param array<int, NotificationRecipient> $recipients */
    private function __construct(array $recipients)
    {
        if ([] === $recipients) {
            throw new \InvalidArgumentException('通知接收人不能为空');
        }

        $indexed = [];
        $type = null;
        foreach ($recipients as $recipient) {
            if (null !== $type && $recipient->type() !== $type) {
                throw new \InvalidArgumentException('一次通知不能混合管理员和前台用户');
            }
            $type = $recipient->type();
            $indexed[$recipient->type().':'.$recipient->id()] = $recipient;
        }

        $this->recipients = array_values($indexed);
    }

    public static function admin(Admin $admin): self
    {
        return new self([NotificationRecipient::admin($admin)]);
    }

    public static function adminId(int $adminId): self
    {
        return new self([NotificationRecipient::adminId($adminId)]);
    }

    public static function admins(iterable $admins): self
    {
        $recipients = [];
        foreach ($admins as $admin) {
            if (!$admin instanceof Admin) {
                throw new \InvalidArgumentException('toAdmins() 只接受 Admin 对象');
            }
            $recipients[] = NotificationRecipient::admin($admin);
        }

        return new self($recipients);
    }

    public static function adminIds(array $adminIds): self
    {
        return new self(array_map(static function ($adminId): NotificationRecipient {
            return NotificationRecipient::adminId((int) $adminId);
        }, $adminIds));
    }

    public static function user(User $user): self
    {
        return new self([NotificationRecipient::user($user)]);
    }

    public static function userId(int $userId): self
    {
        return new self([NotificationRecipient::userId($userId)]);
    }

    public static function users(iterable $users): self
    {
        $recipients = [];
        foreach ($users as $user) {
            if (!$user instanceof User) {
                throw new \InvalidArgumentException('toUsers() 只接受 User 对象');
            }
            $recipients[] = NotificationRecipient::user($user);
        }

        return new self($recipients);
    }

    public static function userIds(array $userIds): self
    {
        return new self(array_map(static function ($userId): NotificationRecipient {
            return NotificationRecipient::userId((int) $userId);
        }, $userIds));
    }

    public function type(): string
    {
        return $this->recipients[0]->type();
    }

    public function ids(): array
    {
        return array_map(static function (NotificationRecipient $recipient): int {
            return $recipient->id();
        }, $this->recipients);
    }

    public function count(): int
    {
        return count($this->recipients);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->recipients);
    }

    public function toArray(): array
    {
        return array_map(static function (NotificationRecipient $recipient): array {
            return $recipient->toArray();
        }, $this->recipients);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
