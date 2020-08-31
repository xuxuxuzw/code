<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\article;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * TODO 文章分类Model
 * Class ArticleCategory
 * @package app\model\article
 */
class ArticleCategory extends BaseModel
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
    protected $name = 'article_category';

    /**
     * 分类状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if ($value !== '') $query->where('status', $value);
    }

    /**
     * 分类名称搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTitleAttr($query, $value, $data)
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 隐藏搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchHiddenAttr($query, $value, $data)
    {
        $query->where('hidden', $value);
    }

    /**
     * 删除搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsDelAttr($query, $value, $data)
    {
        $query->where('is_del', $value);
    }
}
