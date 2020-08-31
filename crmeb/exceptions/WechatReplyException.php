<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/22
 */

namespace crmeb\exceptions;

use Throwable;

/**
 * 微信消息回复错误
 * Class WechatReplyException
 * @package crmeb\exceptions
 */
class WechatReplyException extends \RuntimeException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (is_array($message)) {
            $errInfo = $message;
            $message = $errInfo[1] ?? '未知错误';
            $code = $errInfo[0] ?? 400;
        }

        parent::__construct($message, $code, $previous);
    }
}