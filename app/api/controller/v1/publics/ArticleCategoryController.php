<?php

namespace app\api\controller\v1\publics;

use app\services\article\ArticleCategoryServices;
use crmeb\services\CacheService;

/**
 * 文章分类类
 * Class ArticleCategoryController
 * @package app\api\controller\publics
 */
class ArticleCategoryController
{
    protected $services;

    public function __construct(ArticleCategoryServices $services)
    {
        $this->services = $services;
    }
    /**
     * 文章分类列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lst()
    {
        $cateInfo = CacheService::get('ARTICLE_CATEGORY', function () {
            $cateInfo = $this->services->getArticleCategory();
            array_unshift($cateInfo, ['id' => 0, 'title' => '热门']);
            return $cateInfo;
        });
        return app('json')->successful($cateInfo);
    }
}
