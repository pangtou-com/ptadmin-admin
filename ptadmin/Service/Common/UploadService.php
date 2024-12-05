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

namespace PTAdmin\Admin\Service\Common;

use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\Attachment;

class UploadService
{
    private $filename;
    private $group;

    public function upload($request)
    {
        if (!$request->hasFile($this->getFilename())) {
            throw new BackgroundException('无效的文件');
        }
        $file = $request->file($this->getFilename());
        if (!$file->isValid()) {
            throw new BackgroundException('无效的文件');
        }

        $data = [
            'md5' => hash_file('md5', $file->getPathname()),
            'title' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'suffix' => $file->clientExtension(),
            'size' => $file->getSize(),
            'driver' => $this->getDriver(),
            'groups' => $this->getGroup(),
        ];

        $dao = Attachment::byMd5($data['md5']);
        if ($dao) {
            if (file_exists(Storage::path($dao->path))) {
                return $dao;
            }
            $dao->delete();
        } else {
            $dao = new Attachment();
        }

        $path = Storage::putFile($this->getPath(), $file);
        if (false === $path) {
            throw new BackgroundException('文件保存失败');
        }
        $data['path'] = $path;
        $data['url'] = Storage::url($path);
        $dao->fill($data)->save();

        return $dao->toArray();
    }

    /**
     * 远程资源下载.
     *
     * @param string $url
     * @param mixed  $contentType
     * @param mixed  $contentLength
     *
     * @return HigherOrderBuilderProxy|mixed|string
     */
    public static function download(string $url, $contentType, $contentLength)
    {
        // 获取文件后缀
        $suffix = str_replace('image/', '', $contentType);
        // 获取文件内容
        $fileContent = file_get_contents($url);
        // 判断是否获取成功
        if (false === $fileContent) {
            return $url;
        }
        // 临时文件名
        $path = (new static())->getPath().\DIRECTORY_SEPARATOR.Str::random(12).time().'.'.$suffix;
        $tempPath = Storage::path($path);

        self::createTemplateDirectory($tempPath);

        return self::getFileUrl($tempPath, $fileContent, $suffix, $contentType, $contentLength);
    }

    /**
     * 创建临时目录.
     *
     * @param $tempPath
     */
    public static function createTemplateDirectory($tempPath): void
    {
        $dir = \dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * 若文件已存在，直接返回文件链接
     * 若文件不存在，保存文件并获取文件链接.
     *
     * @param string $tempPath
     * @param string $fileContent
     * @param string $suffix
     * @param string $contentType
     * @param mixed  $contentLength
     *
     * @return HigherOrderBuilderProxy|mixed
     */
    public static function getFileUrl(string $tempPath, string $fileContent, string $suffix, string $contentType, $contentLength)
    {
        // 获取文件hash
        $hashFileName = self::getHashFileName($tempPath, $fileContent);
        $cacheKey = "file_md5_{$hashFileName}";

        if (Cache::has($cacheKey)) {
            unlink($tempPath);

            return Cache::get($cacheKey);
        }
        // 判断文件是否已存在
        $dao = Attachment::byMd5($hashFileName);
        $storagePath = Storage::path('');
        $relativePath = str_replace($storagePath, '', $tempPath);

        if ($dao) {
            if (file_exists(Storage::path($dao->path))) {
                unlink($tempPath);

                return $dao->url;
            }
            $dao->delete();
        } else {
            $dao = new Attachment();
        }

        $data = [
            'md5' => $hashFileName,
            'title' => $hashFileName,
            'mime' => $contentType,
            'suffix' => $suffix,
            'driver' => (new self())->getDriver(),
            'groups' => (new self())->getGroup(),
            'path' => $relativePath,
            'size' => $contentLength,
            'url' => Storage::url($relativePath),
        ];

        $dao->fill($data)->save();
        Cache::put($cacheKey, $data['url'], 3600);

        return $data['url'];
    }

    protected function getPath(): string
    {
        $path = $this->getGroup();
        $public = Config::get('filesystems.disks.public.visibility');

        return $public.'/'.$path.'/'.date('Ymd', time());
    }

    /**
     * 写入临时文件并获取文件hash.
     *
     * @param $tempPath
     * @param $fileContent
     *
     * @return false|string
     */
    private static function getHashFileName($tempPath, $fileContent)
    {
        // 写入文件
        $stream = fopen($tempPath, 'w');
        fwrite($stream, $fileContent);
        fclose($stream);
        // 生成文件的 MD5
        return hash_file('md5', $tempPath);
    }

    /**
     * @return mixed
     */
    private function getDriver(): string
    {
        return Config::get('filesystems.default');
    }

    private function getFilename()
    {
        if (!$this->filename) {
            $this->filename = request()->get('filename', 'file');
        }

        return $this->filename;
    }

    /**
     * 上传分组.
     *
     * @return mixed
     */
    private function getGroup()
    {
        if (!$this->group) {
            $this->group = request()->get('group', 'default');
        }

        return $this->group;
    }
}
