<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminUserRole extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    public $timestamps = false;

    protected $table = 'admin_user_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'tenant_id',
        'created_at',
    ];

    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }
}
