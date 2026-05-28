<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知消息主体。
 *
 * @property int $id
 * @property string $audience_type
 * @property string $source_type
 * @property string $source_code
 * @property string $category
 * @property string $level
 * @property string $title
 * @property string|null $content
 * @property string $action_type
 * @property string|null $action_url
 * @property string|null $biz_type
 * @property string|null $biz_id
 * @property string|null $biz_key
 * @property array|null $data
 * @property int $created_by
 * @property int|null $expires_at
 */
class NotificationMessage extends AbstractModel
{
    public const AUDIENCE_ADMIN = 'admin';
    public const AUDIENCE_USER = 'user';
    public const AUDIENCE_MIXED = 'mixed';

    protected $table = 'notification_messages';

    protected $casts = [
        'id' => 'integer',
        'data' => 'array',
        'created_by' => 'integer',
        'expires_at' => 'integer',
    ];
}
