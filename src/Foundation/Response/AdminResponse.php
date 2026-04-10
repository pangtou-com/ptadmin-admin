<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminResponse
{
    private static $code = 0;
    private static $error_code = 10000;

    public static function success($data = null, string $message = ''): JsonResponse
    {
        $message = '' === $message ? __('ptadmin::common.success') : $message;
        $result = ['code' => self::$code, 'message' => $message];

        if (null !== $data) {
            $result['data'] = $data;
        }

        return response()->json($result);
    }

    public static function fail($error = '', int $code = 10000): JsonResponse
    {
        if (self::$code === $code) {
            $code = self::$error_code + 1;
        }
        if (\is_array($error)) {
            $result = array_merge($error, ['code' => $code]);
        } else {
            $result = ['code' => $code, 'message' => $error];
        }

        return response()->json($result);
    }

    public static function pages($data = null, string $message = ''): JsonResponse
    {
        $result = ['code' => self::$code, 'message' => $message];
        if ($data instanceof LengthAwarePaginator) {
            $result['data']['total'] = $data->total();
            $result['data']['results'] = $data->items();
        } else {
            $result['data']['total'] = $data['total'] ?? 0;
            $result['data']['results'] = $data['data'] ?? [];
        }

        return response()->json($result);
    }
}
