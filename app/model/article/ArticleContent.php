<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\article;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * TODO 文章详情Model
 * Class ArticleContent
 * @package app\model\article
 */
class ArticleContent extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'article_content';

}
