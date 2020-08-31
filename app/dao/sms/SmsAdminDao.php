<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\sms;

use app\dao\BaseDao;
use app\model\system\config\SystemConfig;

/**
 * 短信dao
 * Class SmsAdminDao
 * @package app\dao\sms
 */
class SmsAdminDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemConfig::class;
    }

}