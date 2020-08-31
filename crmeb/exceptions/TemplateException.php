<?php


namespace crmeb\exceptions;


use Throwable;

/**
 * Class TemplateException
 * @package crmeb\exceptions
 */
class TemplateException extends \RuntimeException
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