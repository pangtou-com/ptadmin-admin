<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class NotificationDispatchResult implements Arrayable, JsonSerializable
{
    /** @var int */
    private $notificationId;

    /** @var int */
    private $recipientCount;

    /** @var int */
    private $deliveryCount;

    /** @var string */
    private $status;

    /** @var array<string, mixed> */
    private $message;

    public function __construct(int $notificationId, int $recipientCount, int $deliveryCount, array $message)
    {
        $this->notificationId = $notificationId;
        $this->recipientCount = $recipientCount;
        $this->deliveryCount = $deliveryCount;
        $this->status = NotificationDispatchStatus::SUBMITTED;
        $this->message = $message;
    }

    public function notificationId(): int
    {
        return $this->notificationId;
    }

    public function recipientCount(): int
    {
        return $this->recipientCount;
    }

    public function deliveryCount(): int
    {
        return $this->deliveryCount;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): array
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'recipient_count' => $this->recipientCount,
            'delivery_count' => $this->deliveryCount,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
