<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminUserOrganizationRelation extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    protected $table = 'admin_user_org_relations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'organization_id',
        'department_id',
        'is_primary',
    ];

    public function tenant()
    {
        return $this->belongsTo(AdminTenant::class, 'tenant_id');
    }

    public function organization()
    {
        return $this->belongsTo(AdminOrganization::class, 'organization_id');
    }

    public function department()
    {
        return $this->belongsTo(AdminDepartment::class, 'department_id');
    }
}
