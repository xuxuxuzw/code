<?php

namespace app\adminapi\validate\notification;

use think\Validate;

/**
 *
 * Class SmsConfigValidate
 * @package app\adminapi\validates
 */
class SmsConfigValidate extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'sms_account' => ['require'],
        'sms_token' => ['require'],
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'sms_account.require' => '短信账号必须填写',
        'sms_token.require' => '短信密码必须填写',
    ];
}