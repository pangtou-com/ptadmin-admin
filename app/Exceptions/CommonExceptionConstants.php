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

namespace App\Exceptions;

use PTAdmin\Admin\Enum\AbstractEnum;

/**
 * 通用异常常量.
 */
class CommonExceptionConstants extends AbstractEnum
{
    /** @var int 数据为空 */
    public const NO_FIND_DATA = 100001;

    /** @var int 未登录 */
    public const NO_LOGIN = 401;

    /** @var int 限制登录 */
    public const LIMIT_LOGIN = 100002;

    /** @var int 账户密码错误 */
    public const ACCOUNT_ERROR = 100003;

    /** @var int 数据保存失败 */
    public const DATA_SAVE_FAIL = 100004;

    /** @var int 无访问权限 */
    public const PERMISSION_DENIED = 100005;

    /** @var int 数据异常 */
    public const DATA_EXCEPTION = 100006;

    /** @var int 参数错误 */
    public const ERROR_PARAM = 100008;

    /** 文件异常 */
    /** @var int 无效的文件 */
    public const FILE_INVALID = 100009;
    /** @var int 文件存储失败 */
    public const FILE_STORAGE_FAIL = 100009;

    /** @var int 不允许的文件后缀 */
    public const NO_FILE_EXT = 100010;

    /** @var int 模型错误-不存在的模型 */
    public const MODEL_ERROR = 100011;

    /** @var int 强制规则验证 */
    public const FORCE_VALIDATED = 100012;

    /** @var int 数据已经存在，不允许重复创建 */
    public const DATA_IS_EXISTS = 100013;

    /** @var int 系统字段禁止编辑 */
    public const SYSTEM_FIELD_BAN = 100014;

    /** @var int 方法未实现 */
    public const UNREALIZED_FUNCTION = 100015;

    /** @var int 原密码错误 */
    public const OLD_PASSWORD_ERROR = 100016;

    /** @var int 数据为空 */
    public const DATA_INVALID = 100017;
}
