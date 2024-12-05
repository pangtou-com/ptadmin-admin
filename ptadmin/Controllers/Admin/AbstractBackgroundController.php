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

namespace PTAdmin\Admin\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

abstract class AbstractBackgroundController extends BaseController
{
    /** @var string 模版页面前缀路径 */
    protected $prefix;

    /** @var string[] 操作对应的模版 */
    protected $actionToView = ['index' => 'index', 'edit' => 'form', 'store' => 'form'];

    /** @var string 自定义模版路径 */
    protected $templatePath;

    /** @var string 解析出模型名称，规则为 SystemController 解析为 System */
    private $modelName;

    /** @var string 文件路径前缀 */
    private $path;

    /** @var string 命名空间前缀 */
    private $namespace;

    /** @var string 如果为插件当前插件的名称 */
    private $addonName;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * 根据控制器名称按照约定获取到table名称.
     *
     * @param bool $isComplex 是否将表名称转换为复数形式
     *
     * @return string
     */
    protected function getModel(bool $isComplex = true): string
    {
        // 如果设置了model 则直接使用
        if (property_exists($this, 'model')) {
            return $this->model;
        }
        // 获取model的命名空间
        $namespaceName = $this->getNamespaceName($this->getModelName());

        return null !== $namespaceName ? $namespaceName : ($isComplex ? Str::plural($this->getModelName()) : $this->getModelName());
    }

    protected function getModelName(): string
    {
        return $this->modelName;
    }

    protected function getPath(): ?string
    {
        return $this->path;
    }

    protected function getNamespace(): ?string
    {
        return $this->namespace;
    }

    protected function getAddonName(): ?string
    {
        return $this->addonName;
    }

    protected function getNamespaceName($modelName, $folder = 'Models'): ?string
    {
        $fileName = $modelName.'.php';
        $name = $this->getNamespace().'\\'.Str::replace(\DIRECTORY_SEPARATOR, '\\', $folder).'\\'.$modelName;
        $path = $this->getPath().\DIRECTORY_SEPARATOR.$folder.\DIRECTORY_SEPARATOR.$fileName;
        $path = base_path($path);

        return file_exists($path) ? $name : null;
    }

    /**
     * 实现通过路由传参和get参数.
     *
     * @return array
     */
    protected function getIds(): array
    {
        $id = (int) request()->route('id');
        $ids = norm_ids(request()->get('ids'));
        if ($id) {
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * 获取默认约定的模版信息.
     *
     * @param $path
     *
     * @return string
     */
    protected function getViewPath($path = null): string
    {
        if (null !== $this->templatePath) {
            return $this->templatePath;
        }
        $action = app('request')->route()->getActionName();
        $action = explode('\\', $action);
        $action = array_pop($action);
        if (null === $path) {
            $path = Str::after($action, 'Controller@');
            $path = $this->actionToView[$path] ?? $path;
        }

        $action = Str::before($action, 'Controller@');

        return $this->getPrefix().lcfirst($action).'.'.$path;
    }

    protected function view(...$args)
    {
        return view($this->getViewPath(), ...$args);
    }

    /**
     * 获取模版前缀.
     *
     * @return string
     */
    protected function getPrefix(): string
    {
        $namespace = '';
        $prefix = 'ptadmin.';
        if (is_addon_running()) {
            $namespace = get_running_addon_info('code').'::';
            if (null !== $this->prefix) {
                $prefix = $this->prefix;
            }
        }

        return "{$namespace}{$prefix}";
    }

    private function initialize(): void
    {
        $className = explode('\\', static::class);
        $modelName = Str::before(array_pop($className), 'Controller');
        $this->modelName = ucfirst($modelName);
        $first = reset($className);
        if ('App' === $first) {
            $this->path = 'app';
            $this->namespace = 'App';

            return;
        }
        if ('PTAdmin' === $first) {
            $this->path = 'ptadmin';
            $this->namespace = 'PTAdmin\\Admin';

            return;
        }
        $addon = $className[1] ?? '';
        $this->path = 'addons'.\DIRECTORY_SEPARATOR.$addon;
        $this->namespace = 'Addon\\'.ucfirst($addon);
        $this->addonName = $addon;
    }
}
