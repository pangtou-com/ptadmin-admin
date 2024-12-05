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

namespace PTAdmin\Admin\Utils;

use Illuminate\Support\Str;

class AesUtil
{
    /**
     * 加密方式. 通过函数openssl_get_cipher_methods()获取支持加密方式.
     *
     * @var
     */
    private $method;

    /**
     * 加密密钥.
     *
     * @var
     */
    private $secret_key;

    /**
     * 初始化向量.
     *
     * @var
     */
    private $iv;

    /**
     * @var int
     */
    private $options;

    public function __construct()
    {
        $this->secret_key = config('app.app_secret_key'); // 密钥
        $this->method = 'AES-128-CBC';
        $this->iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $this->options = 0;
        // 当密钥为空时设置一个默认密钥
        if (null === $this->secret_key || '' === $this->secret_key) {
            $this->secret_key = Str::random();
        }
    }

    /**
     * 加密.
     *
     * @param array $data
     *
     * @return string
     */
    public static function encrypt(array $data): string
    {
        $obj = new static();
        $data = $obj->parse($data);
        $encrypted = openssl_encrypt($data, $obj->method, $obj->secret_key, $obj->options, $obj->iv);

        return base64_encode($encrypted.'::'.$obj->iv);
    }

    /**
     * 解密.
     *
     * @param string $data
     *
     * @return false|string
     */
    public static function decrypt(string $data)
    {
        $obj = new static();
        $result = explode('::', $data);
        $string = openssl_decrypt($result[0], $obj->method, $obj->secret_key, $obj->options, $result[1]);

        return base64_decode($string, true);
    }

    /**
     * 将参数排序后重组.
     *
     * @param array $data
     *
     * @return string
     */
    private function parse(array $data): string
    {
        $keys = array_keys($data);
        sort($keys);
        $string = '';
        foreach ($keys as $key) {
            $string = "{$string}&{$key}={$data[$key]}";
        }

        return mb_substr($string, 1, mb_strlen($string) - 1);
    }
}
