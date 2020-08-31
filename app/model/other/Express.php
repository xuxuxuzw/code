<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\other;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * 物流公司Model
 * Class Express
 * @package app\model\other
 */
class Express extends BaseModel
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
    protected $name = 'express';

    /**
     * 物流公司是否显示
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsShowAttr($query, $value, $data)
    {
        $query->where('is_show', $value);
    }

    /**
     * keyword 搜索器
     * @param Model $query
     * @param $value
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->whereLike('name|code', '%' . $value . '%');
        }
    }
}
