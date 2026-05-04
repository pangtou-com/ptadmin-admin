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

namespace PTAdmin\Admin\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PTAdmin\Admin\Support\Query\BuilderQueryApplier;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Foundation\Auth\AdminAuth;

class OperationRecordService
{
    public function details($id): array
    {
        /** @var OperationRecord $dao */
        $dao = OperationRecord::query()->findOrFail($id);

        return $dao->toArray();
    }

    public function page(array $query = []): array
    {
        $query['paginate'] = true;

        $logs = (new BuilderQueryApplier())->fetch(
            OperationRecord::query(),
            $query,
            [
                'allowed_filters' => ['id', 'admin_id', 'admin_username', 'nickname', 'ip', 'url', 'resource_name', 'method', 'action', 'status', 'response_code', 'target_type', 'target_id', 'created_at'],
                'allowed_sorts' => ['id', 'admin_id', 'response_code', 'response_time', 'created_at'],
                'allowed_keyword_fields' => ['admin_username', 'nickname', 'ip', 'url', 'title', 'resource_name', 'method', 'controller', 'action', 'trace_id', 'target_type', 'target_id', 'error_message'],
                'keyword_fields' => ['admin_username', 'nickname', 'ip', 'url', 'title', 'resource_name', 'method', 'controller', 'action', 'trace_id', 'target_type', 'target_id', 'error_message'],
                'default_order' => ['id' => 'desc'],
            ]
        );

        return $logs->toArray();
    }

    /**
     * 记录操作日志.
     *
     * @param $request
     * @param $response
     * @param $route
     */
    public static function record($request, $response, $route): void
    {
        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return;
        }
        $log = new self();
        if (!$log->isRecord((string) $request->getMethod(), (int) $response->getStatusCode())) {
            return;
        }
        $data = [];
        $data['controller'] = '';
        $data['action'] = '';
        if ($route instanceof Route) {
            $data['controller'] = \get_class($route->getController());
            $data['action'] = $route->getActionMethod();
        }
        if (AdminAuth::check()) {
            $data['admin_id'] = AdminAuth::user()->id;
            $data['admin_username'] = (string) AdminAuth::user()->username;
            $data['nickname'] = AdminAuth::user()->nickname;
        }
        $data['ip'] = $request->getClientIp();
        $data['user_agent'] = $log->normalizeString($request->userAgent(), 255);
        $data['url'] = $request->getPathInfo();
        ['resource_name' => $resourceName, 'title' => $title] = $log->resolveResourceMeta($route);
        $data['resource_name'] = $resourceName;
        $data['title'] = $title;
        $data['method'] = $request->getMethod();
        $data['trace_id'] = $log->resolveTraceId($request, $response);
        ['target_type' => $targetType, 'target_id' => $targetId] = $log->resolveTargetMeta($request);
        $data['target_type'] = $targetType;
        $data['target_id'] = $targetId;
        $data['request'] = $log->shouldRecordRequest((string) $data['method']) ? $log->getRequestContent($request->all()) : null;
        ['status' => $status, 'error_message' => $errorMessage] = $log->resolveResponseState($response);
        $data['error_message'] = $errorMessage;
        $data['response_code'] = $response->getStatusCode();
        $data['status'] = $status;
        $data['response_time'] = $log->getTime();

        OperationRecord::query()->create($data);
    }

    /**
     * 返回请求参数内容.
     *
     * @param $data
     *
     * @return string
     */
    private function getRequestContent($data): string
    {
        $data = $this->sanitizeSensitiveData(\is_array($data) ? $data : []);

        return $this->encodeSummary($data);
    }

    /**
     * @return array{status:string,error_message:?string}
     *
     * @param $response
     */
    private function resolveResponseState($response): array
    {
        $status = $response->getStatusCode() >= 400 ? 'failed' : 'success';
        $errorMessage = null;

        if ($response instanceof JsonResponse) {
            $payload = @json_decode($response->getContent(), true);
            if (\is_array($payload)) {
                $code = $payload['code'] ?? null;
                if (\is_numeric($code) && (int) $code !== 0) {
                    $status = 'failed';
                }

                if ('failed' === $status && isset($payload['message']) && '' !== trim((string) $payload['message'])) {
                    $errorMessage = $this->normalizeString((string) $payload['message'], 1000);
                }
            }
        }

        if ($response->getStatusCode() >= 400 && isset($response->exception)) {
            $status = 'failed';
            $errorMessage = $this->normalizeString((string) $response->exception->getMessage(), 1000);
        }

        return [
            'status' => $status,
            'error_message' => $errorMessage,
        ];
    }

    /**
     * 获取请求资源信息.
     *
     * @param mixed $route
     *
     * @return array<string, string>
     */
    private function resolveResourceMeta($route): array
    {
        if (!$route instanceof Route) {
            return [ 'resource_name' => '', 'title' => '' ];
        }

        $resourceName = trim((string) ($route->defaults['__audit_resource__'] ?? ''));
        if ('' === $resourceName) {
            return [ 'resource_name' => '', 'title' => '' ];
        }

        $metaMap = Cache::rememberForever(AdminResource::AUDIT_META_CACHE_KEY, static function (): array {
            return AdminResource::query()
                ->whereNull('deleted_at')
                ->select(['name', 'title'])
                ->get()
                ->mapWithKeys(static function (AdminResource $resource): array {
                    return [
                        $resource->name => [
                            'resource_name' => $resource->name,
                            'title' => $resource->title,
                        ],
                    ];
                })->all();
        });

        if (isset($metaMap[$resourceName]) && \is_array($metaMap[$resourceName])) {
            return [
                'resource_name' => ($metaMap[$resourceName]['resource_name'] ?? $resourceName),
                'title' => ($metaMap[$resourceName]['title'] ?? ''),
            ];
        }

        return [ 'resource_name' => $resourceName, 'title' => '' ];
    }

    /**
     * 获取运行时间.
     *
     * @return float
     */
    private function getTime(): float
    {
        if (false === \defined('PTADMIN_START')) {
            return 0;
        }

        return round((microtime(true) - PTADMIN_START) * 1000, 2);
    }

    /**
     * 是否记录日志.
     *
     * @param string $method
     * @param int    $code
     *
     * @return bool
     */
    private function isRecord(string $method, int $code): bool
    {
        if (200 !== $code) {
            return true;
        }
        // get 方法不进行日志记录
        return \in_array($method, [ 'POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function shouldRecordRequest(string $method): bool
    {
        return \in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitizeSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', 'token', 'authorization', 'access_token', 'refresh_token'];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (\in_array($normalizedKey, $sensitiveKeys, true)) {
                $data[$key] = '***';

                continue;
            }

            if (\is_array($value)) {
                $data[$key] = $this->sanitizeSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeSummary(array $data): string
    {
        $json = @json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!\is_string($json)) {
            return '';
        }

        return mb_substr($json, 0, 5000);
    }

    /**
     * @return array{target_type:?string,target_id:?string}
     */
    private function resolveTargetMeta($request): array
    {
        $segments = array_values(array_filter(explode('/', trim(Str::after($request->getPathInfo(), '/'.admin_route_prefix()), '/'))));

        return [
            'target_type' => $segments[0] ?? null,
            'target_id' => $segments[1] ?? null,
        ];
    }

    private function resolveTraceId($request, $response): ?string
    {
        $traceId = $request->headers->get('X-Request-Id') ?? $request->headers->get('X-Trace-Id');

        if (null === $traceId && isset($response->headers)) {
            $traceId = $response->headers->get('X-Request-Id') ?? $response->headers->get('X-Trace-Id');
        }

        return $this->normalizeString($traceId, 100);
    }

    private function normalizeString($value, int $length): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : mb_substr($value, 0, $length);
    }
}
