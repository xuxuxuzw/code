<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\activity;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * TODO 砍价帮砍Model
 * Class StoreBargainUserHelp
 * @package app\model\activity
 */
class StoreBargainUserHelp extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_bargain_user_help';

    use ModelTrait;

    /**
     * 用户搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchUidAttr($query, $value, $data)
    {
        $query->where('uid', $value);
    }

    /**
     * 商品ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchBargainIdAttr($query, $value, $data)
    {
        $query->where('bargain_id', $value);
    }

    /**
     * 砍价ID搜索器
     * @param $query
     * @param $value
     */
    public function searchBargainUserIdAttr($query, $value)
    {
        $query->where('bargain_user_id', $value);
    }
}

