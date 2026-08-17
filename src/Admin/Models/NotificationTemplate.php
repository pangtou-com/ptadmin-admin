<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知渠道模板。
 *
 * @property int $id
 * @property int $scene_id
 * @property string $channel
 * @property string $locale
 * @property string $mode
 * @property array|null $config
 * @property int $customized
 * @property int $enabled
 */
class NotificationTemplate extends AbstractModel
{
    protected $table = 'notification_templates';

    protected $casts = [
        'id' => 'integer',
        'scene_id' => 'integer',
        'config' => 'array',
        'customized' => 'integer',
        'enabled' => 'integer',
    ];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(NotificationScene::class, 'scene_id');
    }
}
