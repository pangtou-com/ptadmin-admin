<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminDashboardUserWidget extends AbstractModel
{
    protected $table = 'admin_dashboard_user_widgets';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'widget_code',
        'enabled',
        'sort',
        'layout_json',
        'config_json',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'enabled' => 'boolean',
        'sort' => 'integer',
        'layout_json' => 'array',
        'config_json' => 'array',
    ];
}
