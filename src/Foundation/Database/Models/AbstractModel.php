<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Database\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Database\Concerns\Searchable;

/**
 * @property int $id
 */
abstract class AbstractModel extends Model
{
    use Searchable;

    protected $dateFormat = 'U';
    protected $guarded = ['id'];

    public static function batchAdd(array $data): void
    {
        static::query()->insert($data);
    }

    public function freshTimestamp(): int
    {
        return time();
    }

    public function fromDateTime($value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        return is_numeric($value) ? (int) $value : (int) strtotime((string) $value);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $attribute) {
            if (null === $attribute) {
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s', (int) $this->attributes['created_at']);
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i:s', (int) $this->attributes['updated_at']);
    }

    public function getPerPage(): int
    {
        $limit = (int) request()->get('limit', 20);
        if (0 !== $limit) {
            return $limit;
        }

        return parent::getPerPage();
    }

    protected static function booted(): void
    {
        static::creating(function ($model): void {
            if (isset($model->fillable['creator_id'])) {
                $model->creator_id = AdminAuth::check() ? AdminAuth::user()->id : 0;
            }
        });

        static::updating(function ($model): void {
            if (isset($model->fillable['updater_id'])) {
                $model->updater_id = AdminAuth::check() ? AdminAuth::user()->id : 0;
            }
        });
    }
}
