<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\wechat;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 微信用户行为记录  model
 * Class WechatMessage
 * @package app\model\wechat
 */
class WechatMessage extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'wechat_message';

    protected $insert = ['add_time'];

    public static function setAddTimeAttr($value)
    {
        return time();
    }

}