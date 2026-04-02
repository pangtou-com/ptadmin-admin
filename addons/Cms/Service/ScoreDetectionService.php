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

use Addon\Cms\Enum\ScoreEnum;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class ScoreDetectionService
{
    protected $allMessage = [
        'a' => [
            'all' => 0,
            'right' => 0,
        ],
        'title' => [
            'all' => 0,
            'right' => 0,
        ],
        'keywords' => [
            'all' => 0,
            'right' => 0,
        ],
        'description' => [
            'all' => 0,
            'right' => 0,
        ],
        'img' => [
            'all' => 0,
            'right' => 0,
        ],
    ];

    protected $detailMessage = [
    ];

    protected $keyMessage;

    protected $totalNumber = 0;

    /**
     * 获取全部目录.
     *
     * @param $dir
     * @param $suffix
     *
     * @return array
     */
    public function getAll($dir, $suffix = '.blade.php'): array
    {
        $paths = $this->getAllPath($dir, $suffix);
        foreach ($paths as $key => $path) {
            $this->keyMessage = $key;
            $content = file_get_contents($path);
            $pattern = '/<pre\b[^>]*>(.*?)<\/pre>/is';
            // 使用空字符串替换匹配到的内容
            $content = preg_replace($pattern, '', $content);
            ++$this->totalNumber;
            $this->detailMessage[$key] = [
                'path' => substr($path, \strlen($dir) + 1),
            ];
            $this->aImgTag($content);
            $this->seoAllTag($content);
        }

        return [
            'all' => $this->allMessage,
            'detail' => $this->detailMessage,
            'total' => $this->totalNumber,
            'score' => $this->getScore($this->allMessage),
        ];
    }

    /**
     * 获取文件.
     *
     * @param $dir
     * @param $suffix
     *
     * @return array
     */
    public function getAllFiles($dir, $suffix = '.blade.php')
    {
        $paths = $this->getAllPath($dir, $suffix);
        $data = [
            'num' => \count($paths),
            'file' => [],
        ];
        foreach ($paths as $key => $path) {
            $data['file'][] = [
                'path' => $path,
                'name' => substr($path, \strlen($dir) + 1),
                'score' => round(100 * ($key + 1) / ($data['num'])),
            ];
        }

        return $data;
    }

    /**
     * 单个内容.
     *
     * @param $data
     */
    public function putScore($data): void
    {
        $return = $this->getScoreJson();
        $results = [
            'title' => $data['name'],
            'path' => $data['path'],
            'last_time' => time(),
            'score' => $this->getScore($data['detail']['all']),
            'files' => array_column($data['detail']['detail'], 'path'),
            'desc' => $this->getDesc($data['detail']['all'], 'array'),
            'files_score' => $this->someScore($data['detail']['detail']),
        ];
        $return[$data['path']] = $results;
        $this->saveScoreJson($return);
    }

    /**
     * 获取保存的评分.
     *
     * @return array|mixed
     */
    public function getScoreJson()
    {
        $filePath = storage_path('framework'.\DIRECTORY_SEPARATOR.'template'.\DIRECTORY_SEPARATOR.'default.json');
        $return = [];
        if (is_file($filePath)) {
            $return = file_get_contents($filePath);
            $return = json_decode($return, true);
        }

        return $return;
    }

    /**
     * 保存到文件-.
     *
     * @param $return
     */
    public function saveScoreJson($return): void
    {
        $filePath = storage_path('framework'.\DIRECTORY_SEPARATOR.'template'.\DIRECTORY_SEPARATOR.'default.json');
        $jsonData = json_encode($return, JSON_UNESCAPED_UNICODE);
        $directory = \dirname($filePath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }
        File::put($filePath, $jsonData);
    }

    /**
     * 单个详情.
     *
     * @param $details
     *
     * @return array
     */
    public function someScore($details): array
    {
        $results = [];
        $num = 1;
        foreach ($details as $key => $detail) {
            $path = $detail['path'];
            unset($detail['path']);
            $score = $this->getScore($detail);
            $desc = $this->getDesc($detail);
            if (blank($desc)) {
                continue;
            }
            $results[$path] = [
                'id' => $num++,
                'score' => $score,
                'desc' => $desc,
                'title' => $path,
            ];
        }

        return $results;
    }

    /**
     * 描述.
     *
     * @param $data
     * @param $type
     *
     * @return array|string
     */
    public function getDesc($data, $type = 'str')
    {
        if ('array' === $type) {
            $desc = [];
        } else {
            $desc = '';
        }
        foreach ($data as $key => $value) {
            if ($value['right'] < $value['all']) {
                if ('str' === $type) {
                    $desc .= $key.':'.$value['all'].($value['right'] < $value['all'] ? '[<i class="layui-icon layui-icon-error"></i>'.($value['all'] - $value['right']).']' : '').'&emsp;';
                } elseif ('array' === $type) {
                    $desc[] = $key.':'.$value['all'].($value['right'] < $value['all'] ? '[错误：'.($value['all'] - $value['right']).']' : '');
                }
            }
        }

        return $desc;
    }

    /**
     * 评分.
     *
     * @param $data
     *
     * @return int
     */
    public function getScore($data)
    {
        $arr = array_column($data, 'all');
        $arrCount = \count(array_filter($arr, static function ($value) {
            return 0 !== $value;
        }));
        $total = 0;
        if ($arrCount <= 0) {
            return $total;
        }
        $avgScore = 100 / $arrCount;

        foreach ($data as $value) {
            $total += $value['all'] > 0 ? $avgScore * ($value['right'] / $value['all']) : 0;
        }

        return (int) $total;
    }

    /**
     * 全部文件.
     *
     * @param $path
     * @param $suffix
     *
     * @return array
     */
    public function getAllPath($path, $suffix = '')
    {
        $files = [];
        if (!is_dir($path)) {
            return $files;
        }
        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);

        $filesIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($filesIterator as $name => $fileInfo) {
            if ($fileInfo->isFile()) {
                $filePath = $fileInfo->getRealPath();
                $suffixCount = \strlen($suffix);
                if (!blank($suffix) && substr($filePath, -$suffixCount) !== $suffix) {
                    continue;
                }
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * a标签和img标签.
     *
     * @param $content
     */
    public function aImgTag($content): void
    {
        $totalATags = $this->countTags($content, 'a');
        $validTitleATagsCount = $this->countTagsWithNonEmptyAttribute($content, 'a', 'title');
        $this->detailMessage[$this->keyMessage]['a']['all'] = $totalATags;
        $this->detailMessage[$this->keyMessage]['a']['right'] = $validTitleATagsCount;
        $this->allMessage['a']['all'] += $totalATags;
        $this->allMessage['a']['right'] += $validTitleATagsCount;

        $totalImgTags = $this->countTags($content, 'img');
        $imgTagsWithNonEmptyAlt = $this->countTagsWithNonEmptyAttribute($content, 'img', 'alt');
        $this->detailMessage[$this->keyMessage]['img']['all'] = $totalImgTags;
        $this->detailMessage[$this->keyMessage]['img']['right'] = $imgTagsWithNonEmptyAlt;
        $this->allMessage['img']['all'] += $totalImgTags;
        $this->allMessage['img']['right'] += $imgTagsWithNonEmptyAlt;
    }

    public function countTags($string, $tag)
    {
        // Use preg_match_all to find all matches of the specified tag
        $pattern = "/<{$tag}[^>]*>/i"; // Case-insensitive match
        preg_match_all($pattern, $string, $matches);

        return \count($matches[0]);
    }

    public function countTagsWithNonEmptyAttribute($string, $tag, $attribute)
    {
        $pattern = "/<{$tag}[^>]*\\s{$attribute}\\s*=\\s*[\"'](?P<value>[^\"']+)[\"'][^>]*>/i";
        preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
        $count = 0;
        foreach ($matches as $match) {
            if (isset($match['value']) && !blank(trim($match['value']))) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * keyword和description.
     *
     * @param $key
     * @param $content
     */
    public function seoTag($key, $content): void
    {
        $allPattern = '/<meta\b[^>]*name=["\']'.$key.'["\'][^>]*>/i';
        $pattern = '/<meta\b[^>]*name=["\']'.$key.'["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i';
        if (preg_match($pattern, $content, $matches)) {
            ++$this->allMessage[$key]['all'];
            $this->detailMessage[$this->keyMessage][$key]['all'] = 1;
            ++$this->allMessage[$key]['right'];
            $this->detailMessage[$this->keyMessage][$key]['right'] = 1;
        } elseif (1 === preg_match($allPattern, $content)) {
            ++$this->allMessage[$key]['all'];
            $this->detailMessage[$this->keyMessage][$key]['all'] = 1;
            $this->detailMessage[$this->keyMessage][$key]['right'] = 0;
        }
    }

    public function seoAllTag($content): void
    {
        if (preg_match('/<title\b[^>]*>(.+?)<\/title>/i', $content)) {
            ++$this->allMessage['title']['all'];
            $this->detailMessage[$this->keyMessage]['title']['all'] = 1;
            ++$this->allMessage['title']['right'];
            $this->detailMessage[$this->keyMessage]['title']['right'] = 1;
        } elseif (preg_match('/<title\b[^>]*>(.*?)<\/title>/i', $content)) {
            ++$this->allMessage['title']['all'];
            $this->detailMessage[$this->keyMessage]['title']['all'] = 1;
            $this->detailMessage[$this->keyMessage]['title']['right'] = 0;
        }

        $this->seoTag('keywords', $content);

        $this->seoTag('description', $content);
    }

    /**
     * 单个详情.
     *
     * @param $path
     *
     * @return array[]|mixed
     */
    public function getPathScoreJson($path)
    {
        $data = $this->getScoreJson();

        return $data[$path] ?? ['desc' => []];
    }

    /**
     * 详细描述.
     *
     * @param $data
     *
     * @return array
     */
    public function detail($data): array
    {
        $path = $data['path'];
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 20;
        $message = [];
        $return = $this->getScoreJson();

        if (isset($return[$path])) {
            $message = $return[$path]['files_score'];
        }

        return [
            'total' => \count($message),
            'results' => \array_slice($message, ($page - 1) * $limit, $limit),
        ];
    }

    /**
     * 获取详细信息.
     *
     * @param $dir
     *
     * @return array[]
     */
    public function getAllKidSubdirectories($dir)
    {
        $data = $this->getFirstLevelSubdirectories($dir, [], ScoreEnum::notJoin());
        $messageAll = $this->getScoreJson();

        foreach ($data as $key => $value) {
            $data[$key]['message'] = $messageAll[$value['path']] ?? [];
            $data[$key]['score'] = $messageAll[$value['path']]['score'] ?? -1;
            $data[$key]['title'] = $value['name'];
        }

        return ['detail' => $data];
    }

    /**
     * 获取目录.
     *
     * @param $dir
     * @param $message
     * @param $extend
     *
     * @return array
     */
    public function getFirstLevelSubdirectories($dir, $message = [], $extend = [])
    {
        // 获取目录中的文件和子目录列表
        $items = scandir($dir);

        // 初始化一个数组来存储一级子目录
        $subdirectories = [];

        // 遍历所有文件和子目录
        foreach ($items as $item) {
            // 排除 '.' 和 '..'
            if ('.' === $item || '..' === $item || \in_array($item, $extend, true)) {
                continue;
            }

            // 构建完整的路径
            $path = $dir.\DIRECTORY_SEPARATOR.$item;
            $lowPath = substr($path, \strlen(base_path()) + 1);
            // 检查是否为目录
            if (is_dir($path)) {
                // 添加到子目录数组中
                $subdirectories[] = [
                    'name' => $item,
                    'path' => substr($path, \strlen(base_path()) + 1),
                    'score' => !isset($message[$lowPath]) ? -1 : $message[$lowPath]['score'],
                ];
            }
        }

        return $subdirectories;
    }
}
