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

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use PTAdmin\Admin\Utils\ResultsVo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        ServiceException::class,
        DataEmptyException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e): \Symfony\Component\HttpFoundation\Response
    {
        $data = [];
        $data['code'] = $e->getCode();
        if ($e instanceof ValidationException) {
            $msg = $e->validator->getMessageBag()->toArray();
            $msg = collect($msg)->map(function ($item) {
                return implode('|', $item);
            })->toArray();
            $data['message'] = implode('', $msg);
            $data['code'] = 20000;
        } elseif ($e instanceof NotFoundHttpException) {
            $data['message'] = __('exception.exception.view_404');
            $e->render = function () {
                return view('errors.404');
            };
        } else {
            $data['message'] = $e->getMessage();
            if (true === config('app.debug')) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
        }

        if ('api' === $request->header('X-Method') || $request->expectsJson() || $request->is('api/*')) {
            return ResultsVo::fail($data, $data['code']);
        }

        return parent::render($request, $e);
    }
}
