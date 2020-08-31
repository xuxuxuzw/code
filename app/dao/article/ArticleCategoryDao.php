<?php


namespace app\dao\article;


use app\dao\BaseDao;
use app\model\article\ArticleCategory;

/**
 * 文章分类
 * Class ArticleCategoryDao
 * @package app\dao\article
 */
class ArticleCategoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ArticleCategory::class;
    }

    /**
     * 获取文章列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList(array $where, int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 前台获取文章分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getArticleCategory()
    {
        return $this->search(['hidden'=>0,'is_del'=>0,'status'=>1])
            ->order('sort DESC')
            ->field('id,title')
            ->select()->toArray();
    }
}
