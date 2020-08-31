<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-07
 */

namespace app\model\other;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * Class Qrcode
 * @package app\model\other
 */
class Qrcode extends BaseModel
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
    protected $name = 'qrcode';

    /**
     * type 搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value != '') {
            $query->whereLike('type', $value);
        }
    }

    /**
     * status 搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value != '') {
            $query->whereLike('status', $value);
        }
    }

    /**
     * third_type 搜索器
     * @param Model $query
     * @param $value
     */
    public function searchThirdTypeAttr($query, $value)
    {
        if ($value != '') {
            $query->whereLike('third_type', $value);
        }
    }

    /**
     * third_id 搜索器
     * @param Model $query
     * @param $value
     */
    public function searchThirdIdAttr($query, $value)
    {
        if ($value != '') {
            $query->whereLike('third_id', $value);
        }
    }

}
