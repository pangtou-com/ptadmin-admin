<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ProjectFrontendPublishCommand extends Command
{
    protected $signature = 'admin:project-frontend:publish
    {--source= : 项目二开前端构建产物目录，默认读取 ptadmin-auth.project_frontend_dist_path}
    {--target= : 项目二开前端运行目录，默认读取 ptadmin-auth.project_frontend_storage_path}
    {--code= : 项目二开前端模块标识，默认读取 ptadmin-auth.project_frontend_code}';

    protected $description = '发布项目二开前端构建产物到后台宿主模块目录';

    public function handle(Filesystem $filesystem): int
    {
        $code = $this->normalizeCode((string) ($this->option('code') ?: config('ptadmin-auth.project_frontend_code', '__app__')));
        $source = $this->normalizePath((string) ($this->option('source') ?: config('ptadmin-auth.project_frontend_dist_path', base_path('resources/ptadmin/frontend/dist'))));
        $target = $this->normalizePath((string) ($this->option('target') ?: config('ptadmin-auth.project_frontend_storage_path', storage_path('app/ptadmin/modules/'.$code))));
        $manifest = $this->normalizePath((string) config('ptadmin-auth.project_frontend_manifest', base_path('resources/ptadmin/frontend/frontend.json')));

        if (!is_dir($source)) {
            $this->error(sprintf('Project frontend build directory does not exist: %s', $source));

            return 1;
        }

        if (!is_file($manifest)) {
            $this->error(sprintf('Project frontend manifest does not exist: %s', $manifest));

            return 1;
        }

        $this->deletePath($filesystem, $target);
        $filesystem->ensureDirectoryExists(\dirname($target));
        $filesystem->ensureDirectoryExists($target);
        $filesystem->copyDirectory($source, $target.\DIRECTORY_SEPARATOR.'dist');
        $filesystem->copy($manifest, $target.\DIRECTORY_SEPARATOR.'frontend.json');

        $publicPath = public_path(trim(admin_web_prefix(), '/').\DIRECTORY_SEPARATOR.'modules'.\DIRECTORY_SEPARATOR.$code);
        $this->replaceLinkOrCopy($filesystem, $publicPath, $target);

        $this->info('Project frontend published.');
        $this->line('Code: '.$code);
        $this->line('Source: '.$source);
        $this->line('Target: '.$target);
        $this->line('Public: '.$publicPath);

        return 0;
    }

    private function normalizeCode(string $code): string
    {
        $code = trim($code);

        return '' === $code ? '__app__' : $code;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            return $path;
        }

        if ('/' === $path[0] || '\\' === $path[0] || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return rtrim($path, \DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path($path), \DIRECTORY_SEPARATOR);
    }

    private function replaceLinkOrCopy(Filesystem $filesystem, string $linkPath, string $targetPath): void
    {
        $this->deletePath($filesystem, $linkPath);
        $filesystem->ensureDirectoryExists(\dirname($linkPath));

        if (@symlink($targetPath, $linkPath)) {
            return;
        }

        $filesystem->copyDirectory($targetPath, $linkPath);
    }

    private function deletePath(Filesystem $filesystem, string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (is_dir($path)) {
            $filesystem->deleteDirectory($path);
        }
    }
}
