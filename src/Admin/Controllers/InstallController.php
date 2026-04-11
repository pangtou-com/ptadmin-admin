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

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PTAdmin\Admin\Services\Install\Pipe\Complete;
use PTAdmin\Admin\Services\Install\Pipe\ConfigEnv;
use PTAdmin\Admin\Services\Install\Pipe\DatabaseInitialize;
use PTAdmin\Admin\Services\Install\Pipe\ValidateData;
use PTAdmin\Admin\Services\Install\RequirementService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstallController
{
    private const AGREEMENT_TTL_SECONDS = 300;

    private RequirementService $requirementService;

    private array $tabs = [
        ['title' => '使用协议', 'icon' => 'layui-icon-read'],
        ['title' => '环境检查', 'icon' => 'layui-icon-survey'],
        ['title' => '参数配置', 'icon' => 'layui-icon-set'],
        ['title' => '完成安装', 'icon' => 'layui-icon-template-1'],
    ];

    public function __construct(RequirementService $requirementService)
    {
        if (file_exists(storage_path('installed'))) {
            abort(404);
        }

        $this->requirementService = $requirementService;
        view()->share(['tabs' => $this->tabs]);
    }

    public function welcome()
    {
        $step = 0;
        $installErrorMessage = $this->resolveInstallErrorMessage((string) request()->query('error', ''));
        $redirect = $this->normalizeInstallRedirect((string) request()->query('redirect', ''));

        return view('ptadmin-install::welcome', compact('step', 'installErrorMessage', 'redirect'));
    }

    public function accept(): RedirectResponse
    {
        File::ensureDirectoryExists(dirname($this->agreementMarkerPath()));
        File::put($this->agreementMarkerPath(), (string) time());

        $redirectTo = $this->normalizeInstallRedirect((string) request()->input('redirect', ''));
        if ('' === $redirectTo) {
            $redirectTo = route('ptadmin.install.requirements');
        }

        return redirect()->to($redirectTo);
    }

    public function requirements()
    {
        $redirect = $this->redirectIfAgreementNotAccepted(route('ptadmin.install.requirements'));
        if (null !== $redirect) {
            return $redirect;
        }

        $step = 1;
        $results = $this->requirementService->getCheckResults();
        $allPassed = !$this->requirementService->hasFailures();
        $failedItems = $this->requirementService->getFailedItems();
        $installErrorMessage = $this->resolveInstallErrorMessage((string) request()->query('error', ''));

        return view('ptadmin-install::requirements', compact('step', 'results', 'allPassed', 'failedItems', 'installErrorMessage'));
    }

    /**
     * @throws \Exception
     */
    public function environment()
    {
        $redirect = $this->redirectIfAgreementNotAccepted(route('ptadmin.install.environment'));
        if (null !== $redirect) {
            return $redirect;
        }

        $redirect = $this->redirectIfRequirementCheckFailed();
        if (null !== $redirect) {
            return $redirect;
        }

        $step = 2;
        $url = request()->getSchemeAndHttpHost();

        return view('ptadmin-install::env', compact('step', 'url'));
    }

    /**
     * 使用数据流方式执行安装流程.
     */
    public function stream(): StreamedResponse
    {
        $data = request()->all();

        return response()->stream(function () use ($data): void {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            try {
                if (!$this->hasAcceptedAgreementRecently()) {
                    $this->sendStreamMessage([
                        'type' => 'error',
                        'message' => '请先阅读并同意使用协议后再继续安装。',
                        'data' => [],
                    ]);

                    return;
                }

                if ($this->requirementService->hasFailures()) {
                    $this->sendStreamMessage([
                        'type' => 'error',
                        'message' => '环境检查未通过，请先修复失败项后再继续安装。',
                        'data' => [
                            'failed_items' => $this->requirementService->getFailedItems(),
                        ],
                    ]);

                    return;
                }

                app(Pipeline::class)
                    ->send($data)
                    ->through([
                        ValidateData::class,
                        ConfigEnv::class,
                        DatabaseInitialize::class,
                        Complete::class,
                    ])->thenReturn();
            } catch (\Throwable $throwable) {
                Log::error('PTAdmin install stream failed', [
                    'message' => $throwable->getMessage(),
                ]);
                $this->sendStreamMessage([
                    'type' => 'error',
                    'message' => '安装执行失败: '.$throwable->getMessage(),
                    'data' => [],
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Powered-By' => 'ptadmin',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * 输出安装流消息并立即刷新到客户端.
     */
    private function sendStreamMessage(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    private function hasAcceptedAgreementRecently(): bool
    {
        if (!File::exists($this->agreementMarkerPath())) {
            return false;
        }

        $acceptedAt = (int) trim((string) File::get($this->agreementMarkerPath()));
        if ($acceptedAt <= 0) {
            File::delete($this->agreementMarkerPath());
            return false;
        }

        if ($acceptedAt + self::AGREEMENT_TTL_SECONDS < time()) {
            File::delete($this->agreementMarkerPath());

            return false;
        }

        return true;
    }

    private function redirectIfAgreementNotAccepted(string $intendedUrl): ?RedirectResponse
    {
        if ($this->hasAcceptedAgreementRecently()) {
            return null;
        }

        return redirect()
            ->route('ptadmin.install.welcome', [
                'redirect' => $this->normalizeInstallRedirect($intendedUrl),
                'error' => 'protocol',
            ]);
    }

    private function redirectIfRequirementCheckFailed(): ?RedirectResponse
    {
        if (!$this->requirementService->hasFailures()) {
            return null;
        }

        return redirect()
            ->route('ptadmin.install.requirements', [
                'error' => 'requirements',
            ]);
    }

    private function agreementMarkerPath(): string
    {
        return storage_path('framework/ptadmin-install-agreement.lock');
    }

    private function normalizeInstallRedirect(string $target): string
    {
        $target = trim($target);
        if ('' === $target) {
            return '';
        }

        if (0 === strpos($target, '/install')) {
            return $target;
        }

        $parts = parse_url($target);
        if (!\is_array($parts)) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if (0 !== strpos($path, '/install')) {
            return '';
        }

        $query = isset($parts['query']) && '' !== (string) $parts['query']
            ? '?'.(string) $parts['query']
            : '';

        return $path.$query;
    }

    private function resolveInstallErrorMessage(string $errorCode): string
    {
        if ('protocol' === $errorCode) {
            return '请先阅读并同意使用协议后再继续安装。';
        }

        if ('requirements' === $errorCode) {
            return '环境检查未通过，请先修复失败项后再继续下一步。';
        }

        return '';
    }
}
