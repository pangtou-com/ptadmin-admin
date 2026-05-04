<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminDashboardRoleWidget extends AbstractModel
{
    protected $table = 'admin_dashboard_role_widgets';

    protected $fillable = [
        'role_id',
        'tenant_id',
        'widget_code',
        'enabled',
        'sort',
        'layout_json',
        'config_json',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'tenant_id' => 'integer',
        'enabled' => 'boolean',
        'sort' => 'integer',
        'layout_json' => 'array',
        'config_json' => 'array',
    ];
}
