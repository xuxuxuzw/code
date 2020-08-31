<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\services\product\product;


use app\dao\product\product\StoreProductCateDao;
use app\services\BaseServices;

/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 */
class StoreProductCateServices extends BaseServices
{
    public function __construct(StoreProductCateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 商品添加修改商品分类关联
     * @param $id
     * @param $cateData
     */
    public function change($id, $cateData)
    {
        $this->dao->delete($id);
        $this->dao->saveAll($cateData);
    }


}
