<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\services\product\product;

use app\services\BaseServices;
use app\dao\product\product\StoreProductReplyStoreProductDao;

/**
 *
 * Class StoreProductReplyStoreProductServices
 * @package app\services\product\product
 */
class StoreProductReplyStoreProductServices extends BaseServices
{

    /**
     * StoreProductReplyStoreProductServices constructor.
     * @param StoreProductReplyStoreProductDao $dao
     */
    public function __construct(StoreProductReplyStoreProductDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取评论列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductReplyList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getProductReplyList($where, $page, $limit);
        $count = $this->dao->replyCount($where);
        return compact('list', 'count');
    }
}
