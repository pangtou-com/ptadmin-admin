<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知消息接收状态。
 *
 * @property int $id
 * @property int $notification_id
 * @property string $receiver_type
 * @property int $receiver_id
 * @property int|null $read_at
 * @property int|null $archived_at
 * @property int|null $deleted_at
 */
class NotificationReceipt extends AbstractModel
{
    public const RECEIVER_ADMIN = 'admin';
    public const RECEIVER_USER = 'user';

    protected $table = 'notification_receipts';

    protected $casts = [
        'id' => 'integer',
        'notification_id' => 'integer',
        'receiver_id' => 'integer',
        'read_at' => 'integer',
        'archived_at' => 'integer',
        'deleted_at' => 'integer',
    ];
}
