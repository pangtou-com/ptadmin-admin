<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Models;

use PTAdmin\Foundation\Database\Models\AbstractModel;

class AdminGrant extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    protected $table = 'admin_grants';

    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'resource_id',
        'effect',
        'abilities_json',
        'scope_type',
        'scope_value_json',
        'conditions_json',
        'priority',
        'expires_at',
    ];

    protected $casts = [
        'abilities_json' => 'array',
        'scope_value_json' => 'array',
        'conditions_json' => 'array',
    ];

    public function resource(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminResource::class, 'resource_id');
    }
}
