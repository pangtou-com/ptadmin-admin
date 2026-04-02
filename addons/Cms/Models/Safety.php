<?php

namespace Addon\Cms\Models;

use PTAdmin\Admin\Models\AbstractModel;

/**
 * @property int $type
 * @property string $title
 * @property string $subtitle
 * @property string $cover
 * @property string $banner
 * @property string $overview
 * @property string $overview_desc
 * @property string $process
 * @property string $advantage
 * @property int $weight
 * @property int $status
 */
class Safety extends AbstractModel
{
    protected $table = "safety";
    protected $casts = ['process' => "array", "advantage" => "array"];

    protected $appends = ["type_text"];

    protected $fillable = [
        "type", 'title', "subtitle", 'cover', 'cover_title', 'banner', 'overview',
        'overview_desc', 'process', 'advantage', 'weight', 'status'
    ];

    public function getTypeTextAttribute()
    {
        $val = $this->attributes['type'] ?? 0;
        $types = config("cms.safety_type");
        foreach ($types as $type) {
            if ($type['value'] === $val) {
                return $type['label'];
            }
        }

        return "--";
    }
}
