<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminTenant extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    protected $table = 'admin_tenants';

    protected $fillable = [
        'code',
        'name',
        'status',
        'settings_json',
        'deleted_at',
    ];

    protected $casts = [
        'settings_json' => 'array',
    ];

    public function organizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdminOrganization::class, 'tenant_id');
    }
}
