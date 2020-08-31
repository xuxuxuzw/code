<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\dao\wechat;

use app\dao\BaseDao;
use app\model\other\Cache;

/**
 * Class WechatMenuDao
 * @package app\dao\wechat
 */
class WechatMenuDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    public function setModel(): string
    {
        return Cache::class;
    }
}