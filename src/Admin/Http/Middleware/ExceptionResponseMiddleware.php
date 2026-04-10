<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Http\Middleware;

use Closure;
use Illuminate\Validation\ValidationException;
use PTAdmin\Foundation\Response\AdminResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            if (!$this->shouldFormat($request)) {
                throw $e;
            }

            return $this->formatException($e);
        }
    }

    private function shouldFormat($request): bool
    {
        return 'api' === $request->header('X-Method')
            || $request->expectsJson()
            || $request->is('api/*')
            || $request->is(admin_route_prefix().'/*');
    }

    private function formatException(Throwable $e)
    {
        $data = ['code' => (int) $e->getCode()];

        if ($e instanceof ValidationException) {
            $message = collect($e->validator->getMessageBag()->toArray())
                ->map(static function (array $item): string {
                    return implode('|', $item);
                })
                ->implode('');

            $data['message'] = $message;
            $data['code'] = 20000;
        } elseif ($e instanceof HttpExceptionInterface) {
            $data['message'] = '' !== $e->getMessage()
                ? $e->getMessage()
                : $this->resolveHttpExceptionMessage($e->getStatusCode());
        } else {
            $data['message'] = $e->getMessage();
            if (true === config('app.debug')) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
        }

        return AdminResponse::fail($data, (int) $data['code']);
    }

    private function resolveHttpExceptionMessage(int $statusCode): string
    {
        switch ($statusCode) {
            case 401:
                return __('ptadmin::background.401');
            case 403:
                return __('ptadmin::background.403');
            case 404:
                return __('ptadmin::background.404');
            default:
                return __('ptadmin::background.500');
        }
    }
}
