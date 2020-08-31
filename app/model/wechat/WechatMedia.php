<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\wechat;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * Class WechatMedia
 * @package app\model\wechat
 */
class WechatMedia extends BaseModel
{
    use ModelTrait;

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 表名
     * @var string
     */
    protected $name = 'wechat_media';

}