<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\user;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * Class UserLabelRelation
 * @package app\model\user
 */
class UserLabelRelation extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_label_relation';

    /**
     * @return \think\model\relation\HasOne
     */
    public function label()
    {
        return $this->hasOne(UserLabel::class, 'id', 'label_id')->bind([
            'label_name' => 'label_name'
        ]);
    }

    /**
     * uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->whereIn('uid', $value);
    }
}
