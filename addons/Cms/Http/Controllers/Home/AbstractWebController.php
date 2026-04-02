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

namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Enum\SEOEnum;
use App\Exceptions\ServiceException;
use PTAdmin\Admin\Service\SettingService;

/**
 * web端访问抽象类.
 */
abstract class AbstractWebController
{
    /** @var string 前端模版地址 */
    protected $template = 'default';

    public function __construct()
    {
//        $data = SettingService::getTemplateData();
        $this->init();
    }

    final protected function init(): void
    {
        // 1、获取seo相关信息
        // 2、模板相关资料
        // 3、列表控制器
    }

    protected function getTemplate(): void
    {
    }

    /**
     * 获取模版路径.
     *
     * @param $type
     * @param $data
     * @param string $path
     *
     * @return string
     */
    protected function getViewTemplate($type, $data, string $path = ''): string
    {
        $suffixFieldArr = [
            SEOEnum::LIST => 'dir_name',
            SEOEnum::DETAIL => 'dir_name',
            SEOEnum::SINGLE => 'dir_name',
            SEOEnum::CHANNEL => 'dir_name',
            SEOEnum::TOPIC => 'url',
        ];
        $template_path = config('view.template_path');
        $template_default_dir = config('view.template_default_dir');
        if (isset($data['template_'.SEOEnum::getLowerKey($type)]) && '' !== $data['template_'.SEOEnum::getLowerKey($type)]) {
//            dd($data['template_'.SEOEnum::getLowerKey($type)]);
            $filePath = str_replace('.', \DIRECTORY_SEPARATOR, $data['template_'.SEOEnum::getLowerKey($type)]);
            if (file_exists($template_path.\DIRECTORY_SEPARATOR.$template_default_dir.\DIRECTORY_SEPARATOR.$filePath.'.blade.php')) {
                return $data['template_'.SEOEnum::getLowerKey($type)];
            }
        }
        $path = '' !== $path ? $path : $template_default_dir;
        // 资源路径
        $resourcesPath = config('view.template_path').\DIRECTORY_SEPARATOR.$path;

        // 获取类型键和后缀
        $preRoute = SEOEnum::getLowerKey($type);
        $suffixField = $suffixFieldArr[$type] ?? '';
        $suffix = '' !== $suffixField ? $data[$suffixField] : 'default';
        $fileName = "{$preRoute}_{$suffix}";
        // 检查指定文件是否存在
        if (file_exists("{$resourcesPath}".\DIRECTORY_SEPARATOR.$fileName.'.blade.php')) {
            return str_replace(\DIRECTORY_SEPARATOR, '.', $path).'.'.$fileName;
        }
        // 检查默认文件是否存在
        $defaultFileName = "{$preRoute}_default";
        if (!file_exists("{$resourcesPath}".\DIRECTORY_SEPARATOR.$defaultFileName.'.blade.php')) {
            throw new ServiceException(SEOEnum::getDescription($type).'模板文件不存在！');
        }

        return str_replace(\DIRECTORY_SEPARATOR, '.', $path).'.'.$defaultFileName;
    }
}
