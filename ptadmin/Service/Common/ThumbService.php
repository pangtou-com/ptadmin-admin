<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2023 重庆胖头网络技术有限公司，并保留所有权利。
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

namespace PTAdmin\Admin\Service\Common;

use Illuminate\Support\Facades\Storage;

/**
 * 缩略图.
 */
class ThumbService
{
    // 当前支持这些类型，后续在考虑其他的
    private static $imageToFunc = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];

    // 图片类型
    private static $type;

    /**
     * 生成图片缩略图，并保存文件. 暂时只支持本地文件的处理.
     *
     * @param string $filepath
     * @param int    $width
     * @param int    $height
     * @param string $valign
     *
     * @return null|string
     */
    public static function save(string $filepath, int $width = 100, int $height = 100, string $valign = 'middle'): ?string
    {
        // 已经存在缩略图直接返回
        $thumbPath = self::thumbName($filepath, $width, $height);
        if (file_exists(Storage::path($thumbPath))) {
            return $thumbPath;
        }
        $thumb = self::make(Storage::path($filepath), $width, $height, $valign);
        if (!$thumb) {
            return null;
        }
        $fh = @fopen(Storage::path($thumbPath), 'w');
        fwrite($fh, $thumb);
        fclose($fh);

        return $thumbPath;
    }

    /**
     * 返回文件名称.
     *
     * @param $filepath
     * @param $width
     * @param $height
     *
     * @return string
     */
    public static function thumbName($filepath, $width, $height): string
    {
        $info = getimagesize(Storage::path($filepath));
        if (!$info) {
            return '';
        }
        $type = self::$imageToFunc[$info[2]];
        $name = basename($filepath, '.'.$type);
        $path = pathinfo($filepath, PATHINFO_DIRNAME);

        return $path.\DIRECTORY_SEPARATOR.$name.'_thumb_'.$width.'_'.$height.'.'.$type;
    }

    /**
     * 缩略图生成函数.
     *
     * @param string $filename 原图路径
     * @param int    $width    预生成缩略图宽度
     * @param int    $height   预生成缩略图高度
     * @param string $valign   [middle|top|bottom],默认 居中
     *
     * @return false|string|void 原始图象流
     */
    public static function make(string $filename, int $width, int $height, string $valign = 'middle')
    {
        ini_set('gd.jpeg_ignore_warning', '1');
        $imgInfo = getimagesize($filename);
        // 无法获取图片信息或不支持转换时直接返回
        if (!$imgInfo || !isset(self::$imageToFunc[$imgInfo[2]])) {
            return;
        }

        self::$type = self::$imageToFunc[$imgInfo[2]];
        $img_w = $imgInfo[0];
        $img_h = $imgInfo[1];

        $thumb_h = $height; // 固定背景画布的高度
        $height = (int) ($img_h / ($img_w / $width)); // 图片等比例缩放后的高度 = 原图的高度 ÷ (原图的宽度 ÷ 背景画布固定宽带)

        // 创建新的背景画布
        if ($height >= $thumb_h) {
            $thumb = imagecreatetruecolor($width, $thumb_h);
        } else {
            $thumb = imagecreatetruecolor($width, $height);
            $thumb_h = $height;
        }

        $func = 'imagecreatefrom'.self::$type;
        $tmp_img = $func($filename);

        switch ($valign) {
            case 'middle':
                $dst_y = ($img_h - $img_w / $width * $thumb_h) / 2;

                break;

            case 'bottom':
                $dst_y = $img_h - $img_w / $width * $thumb_h;

                break;

            case 'top':
            default:
                $dst_y = 0;

                break;
        }

        // 合成缩略图
        imagecopyresampled($thumb, $tmp_img, 0, 0, 0, (int) $dst_y, $width, $height, $img_w, $img_h);

        ob_clean();
        ob_start();
        $outFunc = 'image'.self::$type;
        $outFunc($thumb);
        $thumb_img = ob_get_clean();

        imagedestroy($tmp_img);
        imagedestroy($thumb);

        return $thumb_img;
    }
}
