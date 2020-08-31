<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\system\config;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 组合数据配置模型
 * Class SystemGroup
 * @package app\model\system\config
 */
class SystemGroup extends BaseModel
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
    protected $name = 'system_group';

    /**
     * 配置名搜索器
     * @param Model $query
     * @param $value
     */
    public function searchConfigNameAttr($query, $value)
    {
        $query->where('config_name', $value);
    }

}