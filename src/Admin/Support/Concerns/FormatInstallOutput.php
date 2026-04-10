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

namespace PTAdmin\Admin\Support\Concerns;

trait FormatInstallOutput
{
    protected function process($message, $data = []): void
    {
        $this->output('process', $message, $data);
    }

    protected function success($message, $data = []): void
    {
        $this->output('success', $message, $data);
    }

    protected function error($message, $data = []): void
    {
        $this->output('error', $message, $data);
    }

    protected function info($message, $data = []): void
    {
        $this->output('info', $message, $data);
    }

    protected function output(string $type, $message, $data = []): void
    {
        $data = ['type' => $type, 'message' => $message, 'data' => $data];
        /*
         * 标准sse输出格式为：
         * event: event name\n
         * data: message \n\n
         * 这个是标准的输出方式，我不需要这种标准输出，直接输出一个json字符串即可
         */
        echo app()->runningInConsole() ? $message."\n" : json_encode($data)."\n\n";
        /*
         * echo 'event:'.$type."\n";
         * echo 'data:'.$message."\n\n";
         */
        usleep(100000);
    }
}
