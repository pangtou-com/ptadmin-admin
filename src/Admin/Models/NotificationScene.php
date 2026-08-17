<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * 通知场景定义。
 *
 * @property int $id
 * @property string $source_type
 * @property string $source_code
 * @property string $group_code
 * @property string $group_title
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property string $purpose
 * @property array|null $variables
 * @property array|null $default_channels
 * @property int $enabled
 */
class NotificationScene extends AbstractModel
{
    protected $table = 'notification_scenes';

    protected $casts = [
        'id' => 'integer',
        'variables' => 'array',
        'default_channels' => 'array',
        'enabled' => 'integer',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class, 'scene_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(NotificationSceneRoute::class, 'scene_id');
    }
}
