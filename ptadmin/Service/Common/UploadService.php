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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
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

    protected function getPath(): string
    {
        $path = $this->getGroup();
        $public = Config::get('filesystems.disks.public.visibility');

        return $public.'/'.$path.'/'.date('Ymd', time());
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
