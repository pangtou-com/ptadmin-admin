<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

/**
 * @property int         $id
 * @property string      $subject_type
 * @property int         $subject_id
 * @property int|null    $tenant_id
 * @property string      $widget_code
 * @property bool        $enabled
 * @property int         $sort
 * @property array|null  $layout_json
 * @property array|null  $config_json
 */
class AdminDashboardWidget extends AbstractModel
{
    public const SUBJECT_ROLE = 'role';
    public const SUBJECT_USER = 'user';

    protected $table = 'admin_dashboard_widgets';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'tenant_id',
        'widget_code',
        'enabled',
        'sort',
        'layout_json',
        'config_json',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'tenant_id' => 'integer',
        'enabled' => 'boolean',
        'sort' => 'integer',
        'layout_json' => 'array',
        'config_json' => 'array',
    ];
}
