<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Services\Settings\SettingsRegistryService;
use PTAdmin\Admin\Services\Settings\SystemSettingsService;
use PTAdmin\Foundation\Response\AdminResponse;
use Throwable;

class SettingsController extends AbstractBackgroundController
{
    private SystemSettingsService $systemSettingsService;
    private SettingsRegistryService $settingsRegistryService;

    public function __construct(SystemSettingsService $systemSettingsService, SettingsRegistryService $settingsRegistryService)
    {
        $this->systemSettingsService = $systemSettingsService;
        $this->settingsRegistryService = $settingsRegistryService;
    }

    public function systemCatalog(): JsonResponse
    {
        try {
            return AdminResponse::success($this->systemSettingsService->catalog());
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }

    public function systemSection(string $sectionKey): JsonResponse
    {
        try {
            return AdminResponse::success($this->systemSettingsService->section($sectionKey));
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }

    public function saveSystemSection(string $sectionKey, Request $request): JsonResponse
    {
        try {
            return AdminResponse::success($this->systemSettingsService->saveSection($sectionKey, $request->all()));
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }

    public function pluginCatalog(): JsonResponse
    {
        try {
            return AdminResponse::success($this->settingsRegistryService->pluginCatalog());
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }

    public function pluginSection(string $code, string $sectionKey): JsonResponse
    {
        try {
            return AdminResponse::success($this->settingsRegistryService->pluginSection($code, $sectionKey));
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }

    public function savePluginSection(string $code, string $sectionKey, Request $request): JsonResponse
    {
        try {
            return AdminResponse::success($this->settingsRegistryService->savePluginSection($code, $sectionKey, $request->all()));
        } catch (Throwable $exception) {
            return AdminResponse::fail($exception->getMessage(), (int) ($exception->getCode() ?: 10000));
        }
    }
}
