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

namespace Addon\Cms\Service\Extend\Tool;

class Trie
{
    private $root;

    public function __construct()
    {
        $this->root = new TrieNode();
    }

    /**
     * 插入单词.
     *
     * @param $word
     */
    public function insert($word): void
    {
        $node = $this->root;
        // 按字符分割
        foreach (mb_str_split($word) as $char) {
            if (!isset($node->children[$char])) {
                // 创建新节点
                $node->children[$char] = new TrieNode();
            }
            $node = $node->children[$char];
        }
        // 标记为完整单词
        $node->isEndOfWord = true;
        $node->title = $word;
    }

    /**
     * 匹配内容并替换.
     *
     * @param $text
     *
     * @return array
     */
    public function search($text): array
    {
        $node = $this->root;
        $textLength = mb_strlen($text);
        $result = [];
        for ($i = 0; $i < $textLength; ++$i) {
            $char = mb_substr($text, $i, 1);
            if (isset($node->children[$char])) {
                $node = $node->children[$char];
                // 如果到达完整单词节点
                if ($node->isEndOfWord) {
                    $result[] = mb_substr($text, $i - mb_strlen($node->title) + 1, mb_strlen($node->title));
                }
            } else {
                // 回到根节点
                $node = $this->root;
            }
        }
        // 返回匹配到的敏感词
        return $result;
    }
}
