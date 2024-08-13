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

namespace App\Service;

use App\Exceptions\CommonExceptionConstants;
use App\Exceptions\ServiceException;
// use App\Models\Addon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AddonService
{
    protected $unAddon;
    /*
     * public function __construct(UnAddon $unAddon)
     * {
     * $this->unAddon = $unAddon;
     * }
     *
     * public function page(array $data = [], string $order = 'id', string $asc = 'desc'): array
     * {
     * $where = [];
     * if (isset($data['title']) && !blank($data['title'])) {
     * $where[] = [
     * 'title', 'like', '%'.$data['title'].'%',
     * ];
     * }
     *
     * return Addon::query()
     * ->where($where)
     * ->orderBy($order, $asc)
     * ->paginate()->toArray();
     * }
     *
     *
     * public function store($data): bool
     * {
     * $data['name'] = $data['title'] ?? '';
     * $data['description'] = $data['intro'] ?? '';
     * $data['framework'] = $data['require_version'] ?? '';
     * DB::beginTransaction();
     * $data['code'] = ucfirst($data['code']);
     * $service = new CreateAddonService();
     *
     * try {
     * // 判断是否存在插件 开始
     * $addon = Addon::query()->where('code', $data['code'])->first();
     * if (null === $addon) {
     * throw new ServiceException('标识已存在');
     * }
     * // 判断是否存在插件 结束
     * $service->create($data);
     *
     * $model = new Addon();
     * $model->fill($data);
     * $model->save();
     *
     * DB::commit();
     * } catch (\Exception $e) {
     * DB::rollBack();
     * $dir = base_path('addons'.\DIRECTORY_SEPARATOR.$data['code']);
     * $this->unAddon->deleteDir($dir);
     *
     * throw new ServiceException($e->getMessage());
     * }
     *
     * return true;
     * }
     *
     * public function login($data): void
     * {
     * $response = AddonApi::login($data);
     * }
     *
     * public function uploadFile($request): void
     * {
     * $filename = $request->get('filename', 'file');
     * $path = 'addon_zip';
     *
     * if (!$request->hasFile($filename)) {
     * throw new ServiceException(CommonExceptionConstants::FILE_INVALID);
     * }
     * $file = $request->file($filename);
     * if (!$file->isValid()) {
     * throw new ServiceException(CommonExceptionConstants::FILE_INVALID);
     * }
     *
     * $path = Storage::putFile($path, $file);
     * $path = storage_path('app/'.$path);
     * $this->getUnZip($path);
     * }
     *
     * public function getUnZip(string $url): void
     * {
     * if (!is_file($url)) {
     * throw new ServiceException('文件不存在');
     * }
     * // 解压
     *
     * $unZip = $this->unAddon->unzipFile($url);
     *
     * $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($unZip));
     * $codeArr = [];
     * foreach ($files as $file) {
     * $filePath = $file->getRealPath();
     * if (!$file->isDir() && 'info.ini' === basename($filePath)) {
     * $codeArr = parse_ini_file($filePath);
     *
     * break;
     * }
     * }
     * if (!isset($codeArr['code']) || blank($codeArr['code'])) {
     * File::deleteDirectory($unZip);
     * File::delete($url);
     *
     * throw new ServiceException('插件编码不存在');
     * }
     * $sha256 = File::hash($url);
     *
     * // 接口判断是否购买
     * // 接口判断是否购买
     * $result = $this->unAddon->moveToDir($unZip, $codeArr['code']);
     * File::deleteDirectory($unZip);
     * File::delete($url);
     * if (!$result) {
     * throw new ServiceException('安装失败');
     * }
     * }
     *
     * public function getAddonArr(): array
     * {
     * $results = AddonManager::getInstance()->getInstalledAddons();
     * $data = [];
     * $index = 0;
     * foreach ($results as $value) {
     * // 封面图问题
     * $cover = base_path('addons'.\DIRECTORY_SEPARATOR.$value['dir'].\DIRECTORY_SEPARATOR.'cover.png');
     * $config = config(lcfirst($value['code'])) ?? [];
     * $data[] = [
     * 'id' => $index++,
     * 'title' => $value['name'],
     * 'code' => $value['code'],
     * 'versions' => [],
     * 'cover_url' => !is_file($cover) ? null : '/system/show-image/'.$value['code'],
     * 'amount' => 0,
     * 'is_local' => 1,
     * 'setting' => isset($config['extra']) ? 1 : 0,
     * ];
     * }
     *
     * return ['results' => $data, 'total' => \count($data)];
     * }
     *
     *
     * public function saveLocalAddon(array $data): void
     * {
     * $addon = Addon::query()->where('code', $data['code'])->first();
     * if (null === $addon) {
     * $model = new Addon();
     * $addon = [
     * 'title' => $data['name'] ?? '暂无',
     * 'code' => $data['code'],
     * 'version' => $data['version'] ?? '1.0.0',
     * 'require_version' => $data['framework'] ?? '1.0.0',
     * 'intro' => $data['description'] ?? '',
     * 'email' => $data['email'] ?? '',
     * 'homepage' => $data['homepage'] ?? '',
     * 'docs' => $data['docs'] ?? '',
     * 'is_upload' => 1,
     * 'is_local' => 1,
     * 'enabled' => 1,
     * ];
     * if (!is_numeric(substr($addon['version'], 0, 1))) {
     * $addon['version'] = substr($addon['version'], 1);
     * }
     *
     * if (!is_numeric(substr($addon['require_version'], 0, 1))) {
     * $addon['require_version'] = substr($addon['require_version'], 1);
     * }
     *
     * $model->fill($addon);
     * $model->save();
     * }
     * }
     *
     *
     * public function addonSetting($data): array
     * {
     * $model = Addon::query()->where('code', $data['code'])->first();
     * if (null === $model) {
     * $config = parser_addon_ini(ucfirst($data['code']));
     * $model = new Addon();
     * $model->title = $config['name'] ?? '暂无';
     * $model->code = $data['code'];
     * $model->version = $config['version'] ?? '1.0.0';
     * $model->require_version = $config['framework'] ?? '1.0.0';
     * $model->intro = $config['description'] ?? '';
     * $model->email = $config['email'] ?? '';
     * $model->docs = $config['docs'] ?? '';
     * }
     * $setting = [];
     * $extras = $data['extra'] ?? [];
     * foreach ($extras as $key => $value) {
     * $setting[] = ['key' => $key, 'value' => $value];
     * }
     * $model->extra = json_encode($setting);
     * $model->save();
     *
     * return $setting;
     * }
     *
     *
     * public function getSetting(string $code): string
     * {
     * $extraArr = Addon::query()->where('code', $code)->value('extra');
     * $extraArr = null === $extraArr ? [] : json_decode($extraArr, true);
     * $extra = [];
     * foreach ($extraArr as $value) {
     * $extra[$value['key']] = $value['value'];
     * }
     * $config = config($code.'.extra');
     * foreach ($config as $key => $value) {
     * $config[$key]['field'] = 'extra['.$value['field'].']';
     * $config[$key]['value'] = $extra[$value['field']] ?? '';
     * }
     * $return = Layui::make(null, $config);
     *
     * return $return->render();
     * }
     *
     *
     * public function uninstall($code): void
     * {
     * Addon::query()->where('code', $code)->delete();
     * AddonInstall::make($code)->uninstall(true);
     * BootstrapManage::reCache();
     * }
     */
}
