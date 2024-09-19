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

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists('admin_route_prefix')) {
    /**
     * 管理后台路由接口地址前缀
     *
     * @return string
     */
    function admin_route_prefix(): string
    {
        return config('app.prefix', 'system');
    }
}

if (!function_exists('get_table_name')) {
    /**
     * 返回数据表名称.
     * 通过模型或者DB操作数据库时laravel会自动添加上前缀信息，则无需这个使用这个方法。
     * 通过sql语句操作时则需要加上前缀信息.
     *
     * @param $tableName
     *
     * @return string
     */
    function get_table_name($tableName): string
    {
        $prefix = config('database.prefix');
        if (blank($prefix)) {
            return $tableName;
        }
        if (\Illuminate\Support\Str::startsWith($tableName, $prefix)) {
            return $tableName;
        }

        return $prefix.$tableName;
    }
}

if (!function_exists('table_to_prefix_empty')) {
    /**
     * 将表前缀替换为空的.
     *
     * @param mixed $tableName
     */
    function table_to_prefix_empty($tableName): string
    {
        $prefix = config('database.prefix');
        if (blank($prefix)) {
            return $tableName;
        }

        return \Illuminate\Support\Str::replaceFirst($prefix, '', $tableName);
    }
}

if (!function_exists('infinite_level')) {
    /**
     * 将无限分类进行分级别,设定lv值
     *
     * @param string     $primary
     * @param array      $arr
     * @param array      $arr2
     * @param int|string $perId
     * @param int        $lv
     * @param mixed      $parentName
     */
    function infinite_level(array $arr, array &$arr2 = [], string $primary = 'id', $parentName = 'parent_id', $perId = 0, int $lv = 0): void
    {
        if (0 === count($arr)) {
            return;
        }
        foreach ($arr as $value) {
            if (array_key_exists($parentName, $value) && $value[$parentName] === $perId) {
                $value['lv'] = $lv;
                $arr2[$value[$primary]] = $value;
                ++$lv;
                infinite_level($arr, $arr2, $primary, $parentName, $value[$primary], $lv--);
            }
        }
    }
}

if (!function_exists('infinite_tree')) {
    /**
     * 树形结构返回数据.
     *
     * @param array      $data
     * @param int|string $parentId
     * @param string     $parentIdName
     * @param string     $keyName
     * @param string     $childrenName
     *
     * @return array
     */
    function infinite_tree(array $data, $parentId = 0, string $parentIdName = 'parent_id', string $keyName = 'id', string $childrenName = 'children'): array
    {
        if (!$data) {
            return [];
        }
        $result = [];
        $i = 0;
        foreach ($data as $key => $val) {
            if ($parentId === $val[$parentIdName]) {
                unset($data[$key]);
                $result[$i] = $val;
                $result[$i][$childrenName] = infinite_tree($data, $val[$keyName], $parentIdName, $keyName, $childrenName);
                ++$i;
            }
        }

        return $result;
    }
}

if (!function_exists('array_to_map')) {
    /**
     * 返回数组key=>val的map映射关系.
     *
     * @param array  $data
     * @param string $key
     * @param string $val
     *
     * @return array
     */
    function array_to_map(array $data, string $key = 'id', string $val = ''): array
    {
        $result = [];
        if (0 === count($data)) {
            return $result;
        }
        foreach ($data as $record) {
            if (blank($val)) {
                $result[$record[$key]] = $record;
            } else {
                $result[$record[$key]] = $record[$val];
            }
        }

        return $result;
    }
}

if (!function_exists('array_filter_field')) {
    /**
     * 提取或排除数组中字段.
     *
     * @param $data
     * @param array $allow 允许的字段
     * @param array $deny  排除的字段
     * @param int   $level 嵌套层级
     *
     * @return mixed
     */
    function array_filter_field($data, array $allow = [], array $deny = [], int $level = 1)
    {
        if (is_object($data)) {
            $data = collect($data)->toArray();
        }
        if (!is_array($data) || 0 === count($data)) {
            return $data;
        }
        $results = [];
        foreach ($data as $key => $record) {
            if (!is_numeric($key)) {
                if (count($allow) > 0 && !in_array($key, $allow, true)) {
                    continue;
                }
                if (count($deny) > 0 && in_array($key, $deny, true)) {
                    continue;
                }
            }
            if (is_array($record) && $level > 0 && count($record) > 0) {
                $results[$key] = array_filter_field($record, $allow, $deny, $level--);

                continue;
            }
            $results[$key] = $record;
        }

        return $results;
    }
}

if (!function_exists('array_allow_field')) {
    /**
     * 提取数组中字段.
     *
     * @param $data
     * @param array $allow
     * @param int   $level
     *
     * @return array|mixed
     */
    function array_allow_field($data, array $allow, int $level = 1)
    {
        return array_filter_field($data, $allow, [], $level);
    }
}

if (!function_exists('array_deny_field')) {
    /**
     * 排除数组中字段.
     *
     * @param $data
     * @param array $deny  排除的字段
     * @param int   $level 嵌套层级
     *
     * @return mixed
     */
    function array_deny_field($data, array $deny, int $level = 1)
    {
        return array_filter_field($data, [], $deny, $level);
    }
}

if (!function_exists('model_build')) {
    /**
     * 模型构建. 支持指定数据库链接.
     * 有的时候特别懒 并不想为每个表建立模型文件，但是又希望使用模型相关的一些操作，则可以通过这个方法来操作.
     *
     * @param $model
     * @param string $connection
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed|object
     */
    function model_build($model, string $connection = '')
    {
        if ($model instanceof \Illuminate\Database\Eloquent\Model) {
            return $model;
        }
        if (is_string($model)) {
            // 如果带有命名空间则认为是一个 Model
            if (\Illuminate\Support\Str::startsWith($model, ['App', '\App', 'Addon', '\Addon', 'PTAdmin', '\PTAdmin'])) {
                try {
                    return app($model);
                } catch (\ReflectionException $e) {
                }
            }
            // 最后尝试使用model生成
            return \PTAdmin\Admin\Models\BuildModel::build($model, $connection);
        }

        throw new \PTAdmin\Admin\Exceptions\BackgroundException('模型不存在');
    }
}

if (!function_exists('norm_ids')) {
    /**
     * 规范化ID 可以将 1,2,3,4 类型参数转换为 [1,2,3,4].
     *
     * @param $ids
     *
     * @return array
     */
    function norm_ids($ids): array
    {
        if (!$ids) {
            return [];
        }
        if (is_string($ids) && false !== strpos($ids, ',')) {
            $ids = explode(',', $ids);
        }
        $ids = Arr::wrap($ids);

        return array_map(function ($val) {
            return (int) $val;
        }, $ids);
    }
}

if (!function_exists('byte_format')) {
    /**
     * 格式化字节大小.
     *
     * @param number $size      字节数
     * @param string $format    格式化类型，支持KB/MB/GB/TB/PB
     * @param string $delimiter 数字和单位分隔符
     *
     * @return string 格式化后的带单位的大小
     */
    function byte_format($size, string $format = 'mb', string $delimiter = ''): string
    {
        $format = strtoupper($format);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $index = array_flip($units);
        $index = $index[$format] ?? 0;
        $divisor = 1024;
        $i = $index;
        for (; 0 < $i; --$i) {
            $divisor = (1024 ** $i);
            if ($size > $divisor) {
                break;
            }
        }
        if (0 === $i) {
            return $size.$delimiter.$units[$i];
        }
        $size = $size / $divisor;

        return round($size, 2).$delimiter.$units[$i];
    }
}

if (!function_exists('number')) {
    /**
     * 生成组合编码类似: 【T000000001】.
     *
     * @param $prefix
     * @param $sn
     * @param int $len
     *
     * @return string
     */
    function number($prefix, $sn, int $len = 10): string
    {
        $prefix = strtoupper($prefix);
        $count = mb_strlen($prefix.$sn);
        if ($count >= $len) {
            return $prefix.$sn;
        }
        $temp = str_repeat('0', $len - $count);

        return $prefix.$temp.$sn;
    }
}

if (!function_exists('is_active')) {
    /**
     * 将当前菜单设置为选中状态[根据路由的别名验证].
     *
     * @param array|string $route     验证路由， 支持数组格式
     * @param string       $className 验证成功后返回的class
     * @param bool         $is_prefix 开启前缀的验证模式, 存在需要验证的信息即可返回
     *
     * @return string
     */
    function is_active($route, string $className = 'active', bool $is_prefix = false): string
    {
        if (is_array($route)) {
            if (
                in_array(\Illuminate\Support\Facades\Route::currentRouteName(), $route, true)
                || in_array(\Illuminate\Support\Facades\Route::current()->uri(), $route, true)
            ) {
                return $className;
            }
            if ($is_prefix) {
                foreach ($route as $value) {
                    if (strpos(\Illuminate\Support\Facades\URL::current(), $value)) {
                        return $className;
                    }
                }
            }

            return '';
        }
        if (\Illuminate\Support\Facades\Route::currentRouteName() === $route) {
            return $className;
        }
        if (\Illuminate\Support\Facades\Route::current()->uri() === $route) {
            return $className;
        }
        if ($route && strpos(\Illuminate\Support\Facades\URL::current(), $route) && $is_prefix && '/' !== $route) {
            return $className;
        }

        return '';
    }
}

if (!function_exists('_asset')) {
    /**
     * 生成资源访问路径.如设置了CDN访问将返回CND地址.
     *
     * @param string $path   资源路径
     * @param mixed  $secure 设置是否生成安全访问地址
     *
     * @return string
     */
    function _asset(string $path, $secure = null): string
    {
        if (true === config('app.debug')) {
            $path = $path.'?time='.time();
        }

        return asset($path, $secure);
    }
}

if (!function_exists('addon_asset')) {
    /**
     * 插件的静态资源访问路径.
     *
     * @param mixed $addon_code 所属插件code
     * @param mixed $path       资源路径
     * @param mixed $secure     设置是否生成安全访问地址
     *
     * @return string
     */
    function addon_asset($addon_code, $path, $secure = null): string
    {
        return _asset("addons/{$addon_code}/{$path}", $secure);
    }
}

if (!function_exists('addon_setting')) {
    /**
     * 获取插件配置信息.
     *
     * @return mixed
     */
    function addon_setting()
    {
        return '';
    }
}

if (!function_exists('admin_route')) {
    /**
     * 生成加上路由前缀地址的路由.管理后台使用.
     *
     * @param $url
     * @param mixed $params
     *
     * @return string
     */
    function admin_route($url, $params = []): string
    {
        $param = '';
        if (count($params) > 0) {
            $param = '?'.http_build_query($param);
        }

        return Str::start(admin_route_prefix(), '/').Str::start($url, '/').$param;
    }
}

if (!function_exists('addon_path')) {
    /**
     * 插件目录.
     *
     * @param $code
     * @param null $path
     *
     * @return string
     */
    function addon_path($code, $path = null): string
    {
        return base_path('addons'.\DIRECTORY_SEPARATOR.ucfirst($code).($path ? \DIRECTORY_SEPARATOR.$path : ''));
    }
}

if (!function_exists('addon_namespace')) {
    /**
     * 获取插件的命名空间.
     *
     * @param $code
     * @param $namespace
     *
     * @return string
     */
    function addon_namespace($code, $namespace = null): string
    {
        return 'Addon\\'.ucfirst($code).($namespace ? '\\'.$namespace : '');
    }
}

if (!function_exists('to_sql')) {
    /**
     * 根据对象输出SQL语句.
     *
     * @param mixed $builder
     *
     * @return null|string|string[]
     */
    function to_sql($builder)
    {
        $sql = $builder->toSql();
        foreach ($builder->getBindings() as $binding) {
            $value = is_numeric($binding) ? $binding : "'".$binding."'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}

if (!function_exists('setting')) {
    /**
     * 系统配置信息.
     *
     * @param $key
     * @param $default
     * @param bool $cache
     *
     * @return mixed
     */
    function setting($key, $default = null, bool $cache = true)
    {
        $val = \Illuminate\Support\Facades\Cache::get($key);
        if (null !== $val && $cache) {
            return $val;
        }

        return \PTAdmin\Admin\Service\SettingService::byNameValue($key, $default);
    }
}

if (!function_exists('is_email')) {
    /**
     * 验证是否为邮箱.
     *
     * @param $value
     *
     * @return bool|false
     */
    function is_email($value): bool
    {
        if (null === FILTER_FLAG_EMAIL_UNICODE) {
            return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
        }

        return false !== filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
    }
}

if (!function_exists('is_mobile')) {
    /**
     * 验证是否为手机号码.
     *
     * @param $value
     *
     * @return bool|false
     */
    function is_mobile($value): bool
    {
        return (bool) preg_match('/^1\d{10}$/', $value);
    }
}

if (!function_exists('pt_submit')) {
    /**
     * 生成layui隐藏的表单提交按钮.
     *
     * @param mixed $display
     *
     * @return string
     */
    function pt_submit($display = 'none'): string
    {
        return '<button type="button" style="display: '.$display.'" lay-submit lay-filter="PT-submit"></button>';
    }
}

if (!function_exists('get_current_user')) {
    /**
     * 获取用户信息.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    function get_current_user($key = null)
    {
        $user = request()->user();
        if (null !== $user) {
            if (!blank($key)) {
                return data_get($user, $key);
            }

            return $user;
        }

        return null;
    }
}

if (!function_exists('random')) {
    /**
     * 取随机数.
     *
     * @param int  $length  生成长度
     * @param bool $numeric 是否为纯数字
     *
     * @return string
     */
    function random(int $length = 6, bool $numeric = false): string
    {
        $seed = base_convert(md5(print_r($_SERVER, true).microtime()), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $hash .= $seed[random_int(0, $max)];
        }

        return $hash;
    }
}

if (!function_exists('get_frame_version')) {
    /**
     * 获取框架版本.
     *
     * @return string
     */
    function get_frame_version(): string
    {
        if (false === defined('PTADMIN_FRAME_VERSION')) {
            return 'v1.0.0';
        }

        return PTADMIN_FRAME_VERSION;
    }
}

if (!function_exists('user_avatar')) {
    /**
     * 获取默认用户头像.
     *
     * @param int $userId
     *
     * @return string
     */
    function user_avatar(int $userId = 0): string
    {
        $avatars = [
            '/static/images/avatar/avatar_1.png',
            '/static/images/avatar/avatar_2.png',
            '/static/images/avatar/avatar_3.png',
            '/static/images/avatar/avatar_4.png',
            '/static/images/avatar/avatar_5.png',
            '/static/images/avatar/avatar_6.png',
            '/static/images/avatar/avatar_7.png',
            '/static/images/avatar/avatar_8.png',
        ];
        if (0 === $userId) {
            return url($avatars[array_rand($avatars)]);
        }

        return url($avatars[$userId % count($avatars)]);
    }
}

if (!function_exists('get_mix_user_id')) {
    /**
     * 对UserID混淆.
     *
     * @param mixed $userId
     *
     * @return string
     */
    function get_mix_user_id($userId): string
    {
        $str = md5(random(36));
        $temp = '';
        for ($i = 0; $i < 4; ++$i) {
            $temp .= $str[$i];
        }

        return strtoupper("PT{$temp}Z".base_convert($userId, 10, 34));
    }
}

if (!function_exists('whenBlank')) {
    /**
     * 当值为空时执行回调.
     *
     * @param $value
     * @param null|callable $callback
     *
     * @return mixed
     */
    function whenBlank($value, callable $callback = null)
    {
        if (null === $callback) {
            return $value;
        }

        return blank($value) ? $callback() : $value;
    }
}

if (!function_exists('whenNotBlank')) {
    /**
     * 当值不为空时执行回调.
     *
     * @param $value
     * @param null|callable $callback
     *
     * @return mixed
     */
    function whenNotBlank($value, callable $callback = null)
    {
        if (null === $callback) {
            return $value;
        }

        return blank($value) ? $value : $callback($value);
    }
}

/*
 * =======================
 * hook处理
 * =======================
 */
if (!function_exists('add_hook_filter')) {
    /**
     * 添加筛选器.
     *
     * @param string $hook      Hook 名称
     * @param mixed  $callback  回调函数
     * @param int    $priority  执行优先级
     * @param int    $arguments 参数个数
     */
    function add_hook_filter(string $hook, $callback, int $priority = 20, int $arguments = 1): void
    {
        \PTAdmin\Admin\Utils\Events::addFilter($hook, $callback, $priority, $arguments);
    }
}

if (!function_exists('add_hook_action')) {
    /**
     * 添加PHP执行动作.
     *
     * @param string $hook      Hook 名称
     * @param mixed  $callback  回调函数
     * @param int    $priority  执行优先级
     * @param int    $arguments 参数个数
     */
    function add_hook_action(string $hook, $callback, int $priority = 20, int $arguments = 1): void
    {
        \PTAdmin\Admin\Utils\Events::addAction($hook, $callback, $priority, $arguments);
    }
}

if (!function_exists('hook_filter')) {
    /**
     * 执行过滤器 Hook.
     *
     * @param string $hook          hook 名称
     * @param mixed  $value         原数据
     * @param mixed  ...$parameters
     *
     * @return mixed
     */
    function hook_filter(string $hook, $value, ...$parameters)
    {
        if (true === config('app.debug') && class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::log('【HOOK】hook_filter: '.$hook);
        }

        return \PTAdmin\Admin\Utils\Events::filter($hook, $value, ...$parameters);
    }
}

if (!function_exists('hook_action')) {
    /**
     * 执行Hook.
     *
     * @param string $hook hook 名称
     * @param ...$parameters
     */
    function hook_action(string $hook, ...$parameters): void
    {
        if (true === config('app.debug') && class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::log('【HOOK】hook_action: '.$hook);
        }
        \PTAdmin\Admin\Utils\Events::action($hook, ...$parameters);
    }
}
