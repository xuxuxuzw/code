<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\wechat;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 关键词
 * Class WechatReply
 * @package app\model\wechat
 */
class WechatReply extends BaseModel
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
    protected $name = 'wechat_reply';

    /**
     * 消息类型
     * @var string[]
     */
    public static $replyType = ['text', 'image', 'news', 'voice'];


    public function wechatKeys()
    {
        return $this->hasMany(WechatKey::class, 'reply_id', 'id');
    }
}