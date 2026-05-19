<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Services\SystemSettingsService;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Response\AdminResponse;

class SettingsController extends AbstractBackgroundController
{
    private SystemSettingsService $systemSettingsService;
    private SystemConfigService $systemConfigService;

    public function __construct(SystemSettingsService $systemSettingsService, SystemConfigService $systemConfigService)
    {
        $this->systemSettingsService = $systemSettingsService;
        $this->systemConfigService = $systemConfigService;
    }
    
    /**
     * 获取配置一级目录
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return AdminResponse::success([
            'results' => $this->systemSettingsService->catalog(),
            "group_type" => config("ptadmin.setting_type")
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $payload = $this->validateStorePayload($request);

        $this->assertUniqueGroupName(
            $payload['name'],
            $payload['addon_code'] ?? null
        );

        $group = SystemConfigGroup::query()->create($payload);

        return AdminResponse::success($group);
    }
    
    public function edit(Request $request, $id): JsonResponse
    {
        $payload = $this->validateStorePayload($request);

        /** @var SystemConfigGroup $group */
        $group = SystemConfigGroup::query()->where("id", (int) $id)->firstOrFail();
        
        if ((int) $group->is_system > 0 && array_key_exists('name', $payload) && $payload['name'] !== $group->name) {
            throw new BackgroundException('系统分组不允许修改标识');
        }

        $targetAddonCode = array_key_exists('addon_code', $payload) ? $payload['addon_code'] : $group->addon_code;
        $targetName = array_key_exists('name', $payload) ? $payload['name'] : $group->name;

        $this->assertUniqueGroupName($targetName, $targetAddonCode, (int) $group->id);

        $group->fill($payload);
        $group->save();

        return AdminResponse::success();
        
    }
    
    /**
     * 分组详情
     * @param $id
     * @return JsonResponse
     */
    public function detail($id): JsonResponse
    {
        $group = SystemConfigGroup::query()->where('id', (int) $id)->firstOrFail();
        
        return AdminResponse::success($group);
    }
    
    /**
     * 删除分组数据
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        /** @var SystemConfigGroup $group */
        $group = SystemConfigGroup::query()->where('id', (int) $id)->firstOrFail();
        if ((int) $group->is_system > 0) {
            throw new BackgroundException('系统分组不允许删除');
        }
        // TODO 校验是否有下级
        
        $group->delete();
        
        return AdminResponse::success();
    }
    
    
    
    /**
     * 获取配置节点信息
     * @param string $name
     * @return JsonResponse
     */
    public function systemSection(string $name): JsonResponse
    {
        $schema = $this->systemSettingsService->section($name);
        
        return AdminResponse::success([
            'schema' => $schema,
            'fields' => $schema['fields']
        ]);
    }
    
    /**
     * 保存字段
     * @param Request $request
     * @param $name
     * @return JsonResponse
     */
    public function saveField(Request $request, $name): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'name' => 'required|string|max:20',
            'system_config_group_id' => 'required|integer',
            'type' => 'required',
        ]);
        
        $this->systemConfigService->store($request->all());
        
        return AdminResponse::success();
    }
    
    /**
     * 保存配置信息
     * @param Request $request
     * @param $name
     * @return JsonResponse
     */
    public function saveConfig(Request $request, $name): JsonResponse
    {
        $this->systemSettingsService->saveSection($name, $request->all());
        return AdminResponse::success();
    }
    
    
    
    /**
     * 修改配置字段
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function editField($id, Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'name' => 'required|string|max:20',
            'system_config_group_id' => 'required|integer',
            'type' => 'required',
        ]);
        
        $this->systemConfigService->edit((int)$id, $request->all());
        return AdminResponse::success();
    }
    
    /**
     * 字段编辑
     * @param $id
     * @return JsonResponse
     */
    public function detailField($id): JsonResponse
    {
        $data = $this->systemConfigService->detail((int) $id);
        
        return AdminResponse::success($data);
    }
    
    /**
     * 删除字段
     * @param $id
     * @return JsonResponse
     */
    public function deleteField($id): JsonResponse
    {
        $this->systemConfigService->delete((int)$id);
        return AdminResponse::success();
    }
    

    /**
     * @return array<string, mixed>
     */
    private function validateStorePayload(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'name' => 'required|string|max:32',
            'badge' => 'nullable|string|max:32',
            'type' => 'required|string|max:32',
            'access' => 'required|string|max:32',
            'sort' => 'sometimes|integer|min:0',
            'addon_code' => 'nullable|string|max:50',
            'intro' => 'nullable|string|max:255',
            'status' => 'sometimes|integer|in:0,1',
        ]);
    }
    
    private function assertUniqueGroupName(string $name, ?string $addonCode = null, int $ignoreId = 0): void
    {
        $query = SystemConfigGroup::query()
            ->whereNull('deleted_at')
            ->where('name', $name);

        if (null === $addonCode) {
            $query->whereNull('addon_code');
        } else {
            $query->where('addon_code', $addonCode);
        }

        if ($ignoreId > 0) {
            $query->where('id', '<>', $ignoreId);
        }

        if ($query->exists()) {
            throw new BackgroundException(sprintf('系统配置分组标识[%s]已存在', $name));
        }
    }
    
}
