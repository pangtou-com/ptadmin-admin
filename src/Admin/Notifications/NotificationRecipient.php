<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\User;

final class NotificationRecipient implements Arrayable, JsonSerializable
{
    /** @var string */
    private $type;

    /** @var int */
    private $id;

    private function __construct(string $type, int $id)
    {
        if (!in_array($type, [NotificationReceipt::RECEIVER_ADMIN, NotificationReceipt::RECEIVER_USER], true)) {
            throw new \InvalidArgumentException('通知接收人类型无效');
        }
        if ($id <= 0) {
            throw new \InvalidArgumentException('通知接收人 ID 必须大于 0');
        }

        $this->type = $type;
        $this->id = $id;
    }

    public static function admin(Admin $admin): self
    {
        return self::adminId((int) $admin->getKey());
    }

    public static function adminId(int $adminId): self
    {
        return new self(NotificationReceipt::RECEIVER_ADMIN, $adminId);
    }

    public static function user(User $user): self
    {
        return self::userId((int) $user->getKey());
    }

    public static function userId(int $userId): self
    {
        return new self(NotificationReceipt::RECEIVER_USER, $userId);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return ['type' => $this->type, 'id' => $this->id];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
