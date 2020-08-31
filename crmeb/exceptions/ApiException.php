<?php


namespace crmeb\exceptions;

/**
 * API应用错误信息
 * Class ApiException
 * @package crmeb\exceptions
 */
class ApiException extends \RuntimeException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        if (is_array($message)) {
            $errInfo = $message;
            $message = $errInfo[1] ?? '未知错误';
            $code = $errInfo[0] ?? 400;
        }

        parent::__construct($message, $code, $previous);
    }
}
