<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Addon\Service\Action\AddonAction;

class ProjectFrontendPullCommand extends Command
{
    protected $signature = 'admin:project-frontend:pull
    {--template=micro-app : 前端模板标识，项目二开默认使用 micro-app}
    {--ref=main : 模板版本或分支，默认 main}
    {--source= : 指定模板源，仅支持 official，留空时使用 official}
    {--target= : 项目二开前端源码目录，默认 resources/ptadmin/frontend}
    {--code= : 项目二开前端模块标识，默认 __app__}
    {--f|force : 强制覆盖已存在目录}';

    protected $description = '拉取项目二开前端模板到 resources 目录';

    public function handle(): int
    {
        $target = $this->normalizePath((string) ($this->option('target') ?: \dirname((string) config('ptadmin-auth.project_frontend_manifest', base_path('resources/ptadmin/frontend/frontend.json')))));
        $code = $this->normalizeCode((string) ($this->option('code') ?: config('ptadmin-auth.project_frontend_code', '__app__')));

        if (!method_exists(AddonAction::class, 'pullProjectFrontend')) {
            $this->error('当前 ptadmin/addon 版本不支持项目二开前端模板拉取，请先升级 ptadmin/addon。');

            return 1;
        }

        try {
            $result = AddonAction::pullProjectFrontend(
                $target,
                (string) $this->option('template'),
                (string) $this->option('ref'),
                (string) $this->option('source'),
                (bool) $this->option('force'),
                $code
            );
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return 1;
        }

        $this->info('Project frontend template pulled.');
        $this->line('Code: '.$code);
        $this->line('Path: '.$result['path']);
        $this->line('Template: '.$result['template']);
        $this->line('Ref: '.$result['ref']);

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
            return base_path('resources/ptadmin/frontend');
        }

        if ('/' === $path[0] || '\\' === $path[0] || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return rtrim($path, \DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path($path), \DIRECTORY_SEPARATOR);
    }
}
