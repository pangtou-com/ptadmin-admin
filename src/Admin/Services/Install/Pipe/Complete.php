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

namespace PTAdmin\Admin\Services\Install\Pipe;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PTAdmin\Admin\Support\Concerns\FormatInstallOutput;

class Complete
{
    use FormatInstallOutput;

    public function handle($data, \Closure $next): void
    {
        $installedMarkerWritten = false;

        try {
            $this->process('创建管理员账户');
            $status = Artisan::call('admin:init', ['-u' => $data['username'], '-p' => $data['password'], '-f' => true]);
            if (0 !== $status) {
                $this->error('创建管理员失败:'.Artisan::output());

                return;
            }

            $this->process('写入安装标记');
            $written = File::put(storage_path('installed'), date('Y-m-d H:i:s', time()));
            if (false === $written || !File::exists(storage_path('installed'))) {
                throw new \RuntimeException('安装标记写入失败');
            }
            $installedMarkerWritten = true;

            $this->persistEnvFile($data);
            $this->resetRuntimeCaches();
            $this->success('安装成功');

            $next($data);
        } catch (\Throwable $throwable) {
            if ($installedMarkerWritten && File::exists(storage_path('installed'))) {
                File::delete(storage_path('installed'));
            }
            $this->error('安装收尾失败: '.$throwable->getMessage());
        }
    }

    /**
     * 将安装阶段准备好的环境配置在最后一步写入磁盘，
     * 尽量避免影响正在执行的安装流程。
     */
    private function persistEnvFile($data): void
    {
        $envContent = $data['__install_env_content'] ?? null;
        if (!is_string($envContent) || '' === $envContent) {
            return;
        }

        $envPath = $data['__install_env_path'] ?? base_path('.env');
        $this->process('保存配置文件');
        $saved = File::put($envPath, $envContent);
        if (false === $saved || !File::exists($envPath)) {
            throw new \RuntimeException(sprintf('无法写入环境文件：%s', $envPath));
        }
    }

    /**
     * 安装完成后清理运行时缓存。
     * 某些宿主项目未集成权限缓存命令，跳过即可，不影响安装结果。
     */
    private function resetRuntimeCaches(): void
    {
        foreach (['cache:clear', 'config:clear', 'event:clear', 'route:clear', 'view:clear'] as $command) {
            Artisan::call($command);
        }

        if (array_key_exists('permission:cache-reset', Artisan::all())) {
            Artisan::call('permission:cache-reset');
        }
    }
}
