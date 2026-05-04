<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminRole extends AbstractModel
{
    protected $table = 'admin_roles';

    protected $fillable = [
        'code',
        'name',
        'description',
        'tenant_id',
        'scope_mode',
        'scope_value_json',
        'status',
        'sort',
    ];

    protected $casts = [
        'scope_value_json' => 'array',
    ];

    public function userRoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdminUserRole::class, 'role_id');
    }
}
