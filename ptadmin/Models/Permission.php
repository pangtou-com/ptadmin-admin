<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace PTAdmin\Admin\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\Traits\ModelCache;
use PTAdmin\Admin\Utils\SystemAuth;

/**
 * @property int    $id
 * @property string $name
 * @property string $title
 * @property string $route
 * @property string $component
 * @property string $icon
 * @property string $parent_name
 * @property string $addon_code
 * @property string $guard_name
 * @property string $weight
 * @property string $note
 * @property array  $paths
 * @property int    $type
 * @property int    $status
 * @property int    $is_nav
 * @property string $controller
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends \Spatie\Permission\Models\Permission
{
    use ModelCache;
    use SoftDeletes;

    /** @var string 顶级菜单的名称，用于上下层级关联时定义 */
    public const TOP_PERMISSION_NAME = '0';

    protected $fillable = [
        'name', 'title', 'route', 'component', 'icon', 'parent_name', 'addon_code', 'guard_name', 'weight', 'note', 'type',
        'status', 'is_nav', 'controller', 'paths',
    ];

    protected $guard_name;
    protected $appends = ['icon_show'];
    protected $casts = ['paths' => 'array'];

    public function __construct(array $attributes = [])
    {
        $guardName = $attributes['guard_name'] ?? SystemAuth::getGuard();
        $this->guard_name = $guardName;
        $attributes['guard_name'] = $guardName;

        parent::__construct($attributes);
    }

    public function freshTimestamp(): int
    {
        return time();
    }

    public function fromDateTime($value)
    {
        return $value;
    }

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['updated_at']);
    }

    public function getIconShowAttribute(): ?string
    {
        return whenNotBlank(data_get($this->attributes, 'icon'), function ($val) {
            return '<i class="'.$val.'"></i> ';
        });
    }

    /**
     * 刷新子集路径信息.
     *
     * @param $parentName
     * @param array $paths
     */
    public static function renewChildrenPaths($parentName, array $paths): void
    {
        // 更新下级
        self::query()->where('parent_name', $parentName)->update(['paths' => $paths]);
        self::query()->where('parent_name', $parentName)->get()->map(function ($item) use ($paths): void {
            self::renewChildrenPaths($item->name, array_merge($paths, [$item->name]));
        });
    }

    /**
     * 获取层级数据.
     *
     * @return array
     */
    public static function getLevels(): array
    {
        $model = new self();

        return whenBlank($model->getAllCachedData('levels'), function () use ($model) {
            $value = [];
            infinite_level(self::getAllData(), $value, 'name', 'parent_name', self::TOP_PERMISSION_NAME);
            $model->setAllCachedData('levels', $value);

            return $value;
        });
    }

    /**
     * 获取树形结构数据.
     *
     * @return mixed
     */
    public static function getTrees()
    {
        $model = new self();

        return whenBlank($model->getAllCachedData('trees'), function () use ($model) {
            $value = infinite_tree(self::getAllData(), self::TOP_PERMISSION_NAME, 'parent_name', 'name');
            $model->setAllCachedData('trees', $value);

            return $value;
        });
    }

    /**
     * 获取整表数据.
     *
     * @param mixed $where
     * @param mixed $order
     *
     * @return array
     */
    public static function getAllData($where = [], $order = ['weight' => 'desc']): array
    {
        $filterMap = self::query()->select([
            'id', 'parent_name', 'title', 'name', 'status', 'route', 'component', 'weight', 'type', 'is_nav', 'icon',
        ])->where($where);

        if (\count($order) > 0) {
            foreach ($order as $key => $value) {
                $filterMap->orderBy($key, $value);
            }
        }

        return $filterMap->get()->toArray();
    }

    /**
     * 获取缓存整表数据的key.
     *
     * @param $type
     *
     * @return string
     */
    public static function getCacheAllKey($type): string
    {
        $table = (new self())->getTable();

        return "{$table}_all_{$type}";
    }

    protected static function booted(): void
    {
        foreach (['saved', 'deleted'] as $event) {
            static::registerModelEvent($event, function ($model): void {
                $model->setAllCachedData('trees', null);
                $model->setAllCachedData('levels', null);
            });
        }
    }

    /**
     * 获取缓存整表数据.
     *
     * @param $type
     *
     * @return null|array
     */
    protected function getAllCachedData($type): ?array
    {
        if (Cache::has(self::getCacheAllKey($type))) {
            $data = Cache::get(self::getCacheAllKey($type));
            $data = @unserialize($data);
            if (false !== $data) {
                return $data;
            }
        }

        return null;
    }

    /**
     * 设置缓存整表数据.
     *
     * @param string     $type
     * @param null|array $data
     */
    protected function setAllCachedData(string $type, ?array $data): void
    {
        if (null === $data) {
            Cache::forget(self::getCacheAllKey($type));

            return;
        }
        Cache::put(self::getCacheAllKey($type), serialize($data), 3600);
    }
}
