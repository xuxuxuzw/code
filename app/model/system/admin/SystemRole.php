<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\system\admin;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 管理员权限规则
 * Class SystemRole
 * @package app\model\system\admin
 */
class SystemRole extends BaseModel
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
    protected $name = 'system_role';

    /**
     * 规则修改器
     * @param Model $value
     * @return string
     */
    public static function setRulesAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 权限规格状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value != '') {
            $query->where('status', $value ?: 1);
        }
    }

    /**
     * 权限等级搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLevelAttr($query, $value)
    {
        $query->where('level', $value);
    }

    /**
     * id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 身份管理搜索
     * @param Model $query
     * @param $value
     */
    public function searchRoleNameAttr($query, $value)
    {
        if ($value) {
            $query->whereLike('role_name', '%' . $value . '%');
        }
    }
}