<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知消息外部投递记录。
 *
 * @property int $id
 * @property int|null $notification_id
 * @property int|null $receipt_id
 * @property string|null $receiver_type
 * @property int|null $receiver_id
 * @property string $channel
 * @property string|null $provider
 * @property string|null $message_id
 * @property string|null $batch_id
 * @property string $status
 * @property int|null $accepted_at
 * @property int|null $delivered_at
 * @property string|null $error_message
 * @property array|null $meta
 * @property mixed $raw
 */
class NotificationDelivery extends AbstractModel
{
    protected $table = 'notification_deliveries';

    protected $casts = [
        'id' => 'integer',
        'notification_id' => 'integer',
        'receipt_id' => 'integer',
        'receiver_id' => 'integer',
        'accepted_at' => 'integer',
        'delivered_at' => 'integer',
        'meta' => 'array',
        'raw' => 'array',
    ];
}
