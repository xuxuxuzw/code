<?php

namespace app\api\controller\v1\store;

use app\services\product\product\StoreCategoryServices;

/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class CategoryController
{
    protected $services;

    public function __construct(StoreCategoryServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取分类列表
     * @return mixed
     */
    public function category()
    {
        $category = $this->services->getCategory();
        return app('json')->success($category);
    }
}
