<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminRole extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    protected $table = 'admin_roles';

    protected $fillable = [
        'code',
        'name',
        'description',
        'tenant_id',
        'scope_mode',
        'scope_value_json',
        'is_system',
        'status',
        'sort',
        'deleted_at',
    ];

    protected $casts = [
        'scope_value_json' => 'array',
    ];

    public function userRoles()
    {
        return $this->hasMany(AdminUserRole::class, 'role_id');
    }
}
