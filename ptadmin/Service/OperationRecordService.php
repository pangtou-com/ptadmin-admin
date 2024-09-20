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

namespace PTAdmin\Admin\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PTAdmin\Admin\Models\OperationRecord;
use PTAdmin\Admin\Models\Permission;
use PTAdmin\Admin\Utils\SystemAuth;

class OperationRecordService
{
    public function details($id): array
    {
        /** @var OperationRecord $dao */
        $dao = OperationRecord::query()->findOrFail($id);
        $dao->request = @json_decode($dao->request);
        $dao->response = @json_decode($dao->response);

        return $dao->toArray();
    }

    public function page($search = []): array
    {
        $model = OperationRecord::query();

        return $model->orderBy('id', 'desc')->paginate()->toArray();
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
        if (false !== strpos(\PHP_SAPI, 'cli')) {
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
        if (SystemAuth::check()) {
            $data['system_id'] = SystemAuth::user()->id;
            $data['nickname'] = SystemAuth::user()->nickname;
        }
        $data['ip'] = (int) ip2long($request->getClientIp());
        $data['url'] = $request->getPathInfo();
        $data['title'] = $log->getTitle($data['url']);
        $data['method'] = $request->getMethod();
        $data['request'] = Str::limit($log->getRequestContent($request), 1020);
        $data['response'] = Str::limit($log->getResponseContent($response), 1020);
        $data['response_code'] = $response->getStatusCode();
        $data['response_time'] = $log->getTime();

        // $data['sql_param'] = json_encode(LogSqlService::getSqlResults());

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
        $notAllow = ['password', 'password_confirmation', 'current_password'];
        foreach ($notAllow as $value) {
            if (isset($data[$value])) {
                unset($data[$value]);
            }
        }

        return @json_encode($data);
    }

    /**
     * 返回响应参数.如果有结果级则不返回.
     *
     * @param $response
     *
     * @return string
     */
    private function getResponseContent($response): string
    {
        $res = '';
        if ($response instanceof JsonResponse) {
            $res = $response->getContent();

            try {
                $res = @json_decode($res, true);
                if (isset($res['data']) && !blank($res['data'])) {
                    unset($res['data']);
                }
                $res = @json_encode($res);
            } catch (\Exception $e) {
                $res = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                ];
                $res = @json_encode($res);
            }
        }
        if (200 !== $response->getStatusCode() && $response->exception) {
            $error = [
                'code' => $response->exception->getCode(),
                'file' => $response->exception->getFile(),
                'line' => $response->exception->getLine(),
                'message' => $response->exception->getMessage(),
            ];
            $res = @json_encode($error);
        }

        return $res;
    }

    /**
     * 获取请求名称.
     *
     * @param mixed $route
     *
     * @return string
     */
    private function getTitle($route): string
    {
        $data = Permission::getCacheMap();
        $key = Str::after($route, '/'.admin_route_prefix().'/');
        $results = [];
        foreach ($data as $datum) {
            if (blank($datum['route'])) {
                continue;
            }

            $results[$datum['route']] = $datum['title'];
        }

        return $results[$key] ?? '';
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
        $methods = Config::get('logging.custom.method', ['GET', 'POST', 'PUT', 'DELETE']);
        if (200 !== $code) {
            return true;
        }
        if (\is_array($methods) && \in_array($method, $methods, true)) {
            return true;
        }

        return false;
    }
}
