<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知场景到渠道实例的投递路由。
 *
 * @property int $id
 * @property int $scene_id
 * @property string $channel
 * @property string|null $addon_code
 * @property string $provider_group
 * @property string $provider
 * @property string $instance_code
 * @property string $dispatch_mode
 * @property string|null $strategy
 * @property int $priority
 * @property int $weight
 * @property int $revision
 * @property int $enabled
 */
class NotificationSceneRoute extends AbstractModel
{
    protected $table = 'notification_scene_routes';

    protected $casts = [
        'id' => 'integer',
        'scene_id' => 'integer',
        'priority' => 'integer',
        'weight' => 'integer',
        'revision' => 'integer',
        'enabled' => 'integer',
    ];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(NotificationScene::class, 'scene_id');
    }

}
