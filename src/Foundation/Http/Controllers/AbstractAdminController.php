<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class AbstractAdminController extends BaseController
{
    protected $prefix;
    protected $actionToView = ['index' => 'index', 'edit' => 'form', 'store' => 'form'];
    protected $templatePath;
    private $modelName;
    private $path;
    private $namespace;
    private $addonName;

    public function __construct()
    {
        $this->initialize();
    }

    protected function getModel(bool $isComplex = true): string
    {
        if (property_exists($this, 'model')) {
            return $this->model;
        }
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

    protected function getIds(): array
    {
        $id = (int) request()->route('id');
        $ids = norm_ids(request()->get('ids'));
        if ($id) {
            $ids[] = $id;
        }

        return $ids;
    }

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
            $reflection = new ReflectionClass(static::class);
            $fileName = $reflection->getFileName() ?: '';
            $this->path = Str::contains($fileName, DIRECTORY_SEPARATOR.'ptadmin'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR) ? 'ptadmin/Admin' : 'ptadmin';
            $this->namespace = 'PTAdmin\\Admin';

            return;
        }
        $addon = $className[1] ?? '';
        $this->path = 'addons'.\DIRECTORY_SEPARATOR.$addon;
        $this->namespace = 'Addon\\'.ucfirst($addon);
        $this->addonName = $addon;
    }
}
