<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/13
 */

namespace app\dao\wechat;

use app\dao\BaseDao;
use app\model\wechat\WechatMedia;

/**
 * 微信媒体
 * Class WechatMediaDao
 * @package app\dao\wechat
 */
class WechatMediaDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return WechatMedia::class;
    }
}