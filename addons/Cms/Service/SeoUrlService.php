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

namespace Addon\Cms\Service;

use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Models\Seo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SeoUrlService
{
    private $method = [];

    private $title = '<?php

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

use Illuminate\\Support\\Facades\\Route;

';

    /**
     * 生成seo的route.
     *
     * @param $type 生成seo的url类型
     */
    public function getUrlArr($type = 1): void
    {
        $seo = Seo::query()->orderBy('id', 'desc')->first();
        $this->method = SEOEnum::getRouteConfig()[$type];
        if (null === $seo) {
            $defaultConfig = SEOEnum::getSupportParams($type);
            $pre = $defaultConfig['prefix'];
            $config = $defaultConfig['default_url'];
        } else {
            $config = $seo->config;
            $pre = $config[$type]['pre_route'];
            $config = $config[$type]['url'];
        }

        $iniDir = base_path('bootstrap'.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR.'cms.php');
        $getCmsInis = $this->getCmsIni($iniDir);
        $php = $this->title;
        $newUrl = $this->getPhp($pre, $config);

        $php .= $newUrl;

        foreach ($getCmsInis as $key => $getCmsIni) {
            if ($key !== $type && is_numeric($key)) {
                $php .= "\n\n".$getCmsIni;
            }
        }

        $getCmsInis[$type] = $newUrl;

        $this->saveCmsIni($getCmsInis, $iniDir);

        $dir = base_path('addons'.\DIRECTORY_SEPARATOR.'Cms'.\DIRECTORY_SEPARATOR.'Routes'.\DIRECTORY_SEPARATOR.'cms.php');
        File::put($dir, $php);
        Artisan::call('route:cache');
    }

    /**
     * 生成全部.
     */
    public function getAllPhp(): void
    {
        $seo = Seo::query()->orderBy('id', 'desc')->first();
        if (blank($seo)) {
            $seo = SEOEnum::getSupportParams();
        } else {
            $seo = $seo->config;
        }
        $php = $this->title;
        $dir = base_path('addons'.\DIRECTORY_SEPARATOR.'Cms'.\DIRECTORY_SEPARATOR.'Routes'.\DIRECTORY_SEPARATOR.'cms.php');
        $iniDir = base_path('bootstrap'.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR.'cms.php');
        $getCmsInis = [];
        $method = SEOEnum::getRouteConfig();
        foreach ($seo as $key => $defaultConfig) {
            $this->method = $method[$key];
            $pre = $defaultConfig['prefix'] ?? $defaultConfig['pre_route'];
            $config = $defaultConfig['default_url'] ?? $defaultConfig['url'];
            $newUrl = $this->getPhp($pre, $config);
            $php .= $newUrl."\n\n";
            $getCmsInis[$key] = $newUrl;
        }
        File::put($dir, $php);
        $this->saveCmsIni($getCmsInis, $iniDir);
        Artisan::call('route:cache');
    }

    /**
     * 单个数据.
     *
     * @param $pre
     * @param $config
     *
     * @return string
     */
    public function getPhp($pre, $config): string
    {
        preg_match_all('/\{([^\{\}]+)\}/', $config, $matches);

        $tables = $this->getTables($matches[1], $config);
        $otherConfig = $tables['other'];
        $tables = $tables['table'];

        $urlIds = $this->getUrlIds($tables);
        $otherUrlIds = $urlIds['other'];
        $urlIds = $urlIds['ids'];

        $middlewareString = AutoRouteUtils::autoRouteConfigString(['middleware'], 'cms');
        $newUrl = "\tRoute::group(['prefix' => '/".$pre.('' !== $middlewareString ? "', ".$middlewareString : '')."], function (): void {";

        $newUrl .= $this->getUrl($config, $urlIds);

        if (!blank($otherConfig)) {
            $newUrl .= $this->getUrl($otherConfig, $otherUrlIds);
        }

        $newUrl .= "\n\t});";

        return $newUrl;
    }

    /**
     * 表单数据.
     *
     * @param $matches
     * @param $config
     *
     * @return array
     */
    public function getTables($matches, $config)
    {
        $keys = SEOEnum::allKeys();
        $tables = [];
        $otherConfig = '';
        foreach ($matches as $matche) {
            if (!isset($keys[$matche])) {
                continue;
            }
            if (!\in_array($keys[$matche]['table'], $tables, true)) {
                $tables[] = [
                    'table' => $keys[$matche]['table'],
                    'where' => $keys[$matche]['where'],
                    'key' => $matche,
                    'id' => $keys[$matche]['id'],
                    'isMust' => $keys[$matche]['isMust'],
                ];
            }
            if (!$keys[$matche]['isMust']) {
                $otherConfig = str_replace('/{'.$matche.'}', '', $config);
            }
        }

        return [
            'table' => $tables,
            'other' => $otherConfig,
        ];
    }

    /**
     * 查询条件.
     *
     * @param $tables
     *
     * @return array[]
     */
    public function getUrlIds($tables)
    {
        $urlIds = [];
        $otherUrlIds = [];
        if (\count($tables) > 0) {
            foreach ($tables as $table) {
                if (\is_array($table['where'])) {
                    $arr = DB::table($table['table'])->where([$table['where']])->get()->toArray();
                    $urlIds[$table['key']] = array_column($arr, $table['id']);
                    if ($table['isMust']) {
                        $otherUrlIds[$table['key']] = array_column($arr, $table['id']);
                    }
                } else {
                    $urlIds[$table['key']] = $table['where'];
                    if ($table['isMust']) {
                        $otherUrlIds[$table['key']] = $table['where'];
                    }
                }
            }
        }

        return [
            'ids' => $urlIds,
            'other' => $otherUrlIds,
        ];
    }

    /**
     * 获取路由.
     *
     * @param $url
     * @param $urlArr
     *
     * @return string
     */
    public function getUrl($url, $urlArr): string
    {
        $newUrl = "\n\t\tRoute::get('".$url."', [".$this->method[0]."::class, '".$this->method[1]."'])";
        foreach ($urlArr as $key => $urlId) {
            $urlType = \is_array($urlId) ? implode('|', array_filter($urlId)) : $urlId;
            $newUrl .= "\n\t\t->where('".$key."','".$urlType."')";
        }
        $newUrl .= ';';

        return $newUrl;
    }

    /**
     * 保存进文件.
     *
     * @param $arrs
     * @param $dir
     */
    private function saveCmsIni($arrs, $dir): void
    {
        $phpCode = "<?php\n\nreturn ".var_export($arrs, true).";\n";
        File::put($dir, $phpCode);
    }

    /**
     * 读取文件内容.
     *
     * @param $dir
     *
     * @return array|mixed
     */
    private function getCmsIni($dir)
    {
        return is_file($dir) ? include($dir) : [];
    }
}
