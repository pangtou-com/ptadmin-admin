<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use PTAdmin\Admin\Services\CacheClearService;
use PTAdmin\Foundation\Response\AdminResponse;

class CacheController extends AbstractBackgroundController
{
    private CacheClearService $cacheClearService;

    public function __construct(CacheClearService $cacheClearService)
    {
        $this->cacheClearService = $cacheClearService;
    }

    public function clear(): JsonResponse
    {
        return AdminResponse::success($this->cacheClearService->clear());
    }
}
