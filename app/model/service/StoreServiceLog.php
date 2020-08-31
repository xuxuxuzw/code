<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\model\service;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 客服聊天记录
 * Class StoreServiceLog
 * @package app\model\service
 */
class StoreServiceLog extends BaseModel
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
    protected $name = 'store_service_log';

    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 一对一关联
     * @return mixed
     */
    public function service()
    {
        return $this->hasOne(StoreService::class, 'uid', 'uid')->field(['uid', 'nickname', 'avatar'])->bind([
            'nickname' => 'nickname',
            'avatar' => 'avatar'
        ]);
    }

    /**
     * uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid|to_uid', $value);
    }

    /**
     * 聊天记录搜索器
     * @param Model $query
     * @param $value
     */
    public function searchChatAttr($query, $value)
    {
        $query->whereIn('uid', $value)->whereIn('to_uid', $value);
    }
}