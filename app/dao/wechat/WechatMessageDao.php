<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-13
 */

namespace app\dao\wechat;


use app\dao\BaseDao;
use app\model\wechat\WechatMessage;

/**
 * Class WechatMessageDao
 * @package app\dao\wechat
 */
class WechatMessageDao extends BaseDao
{
    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WechatMessage::class;
    }
}
