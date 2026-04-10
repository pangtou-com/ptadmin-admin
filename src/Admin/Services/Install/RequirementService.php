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

namespace PTAdmin\Admin\Services\Install;

class RequirementService
{
    public function getCheckResults(): array
    {
        $results = [];
        $results[] = [
            'title' => 'PHP版本',
            'results' => $this->checkPhpVersion((string) config('ptadmin-install.core.min_php_version', '7.4.0')),
        ];

        $results[] = [
            'title' => '环境检测',
            'results' => $this->checkExtend(config('ptadmin-install.extend', [])),
        ];

        $results[] = [
            'title' => '函数检测',
            'results' => $this->checkFunc(config('ptadmin-install.func', [])),
        ];

        $results[] = [
            'title' => '目录权限',
            'results' => $this->checkFolders(config('ptadmin-install.folders', [])),
        ];

        $results[] = [
            'title' => '文件权限',
            'results' => $this->checkFiles(config('ptadmin-install.files', [])),
        ];

        return $results;
    }

    /**
     * 检测 PHP 版本是否满足最低安装要求。
     */
    private function checkPhpVersion(string $minVersion): array
    {
        return [[
            'title' => 'PHP',
            'config' => sprintf('>= %s，当前 %s', $minVersion, PHP_VERSION),
            'state' => version_compare(PHP_VERSION, $minVersion, '>='),
        ]];
    }

    /**
     * 检测php模块.
     *
     */
    private function checkExtend(array $extend): array
    {
        $results = [];
        foreach ($extend as $item) {
            $results[] = [
                'title' => $item,
                'config' => '',
                'state' => \extension_loaded($item),
            ];
        }

        return $results;
    }

    /**
     * 检测扩展函数.
     *
     */
    private function checkFunc(array $func): array
    {
        $results = [];
        foreach ($func as $requirement) {
            $results[] = [
                'title' => $requirement,
                'config' => '',
                'state' => \function_exists($requirement),
            ];
        }

        return $results;
    }

    /**
     * 检测文件夹权限.
     *
     */
    private function checkFolders(array $folders): array
    {
        $results = [];
        foreach ($folders as $folder => $permission) {
            if (!file_exists(base_path($folder))) {
                @mkdir(base_path($folder), 0755, true);
            }
            $perm = substr(sprintf('%o', @fileperms(base_path($folder))), -4);
            $results[] = [
                'title' => $folder,
                'config' => $perm,
                'state' => $perm >= $permission,
            ];
        }

        return $results;
    }

    /**
     * 检测安装阶段关键文件的读写能力。
     */
    private function checkFiles(array $files): array
    {
        $results = [];

        foreach ($files as $file => $mode) {
            $path = base_path($file);
            $exists = file_exists($path);

            if ('read' === $mode) {
                $results[] = [
                    'title' => $file,
                    'config' => '可读取',
                    'state' => $exists && is_readable($path),
                ];

                continue;
            }

            $state = $exists ? is_writable($path) : is_writable(dirname($path));
            $results[] = [
                'title' => $file,
                'config' => $exists ? '可写入' : '可创建',
                'state' => $state,
            ];
        }

        return $results;
    }
}
