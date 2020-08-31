<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\services\article;

use app\dao\article\ArticleContentDao;
use app\services\BaseServices;

/**
 * Class ArticleContentServices
 * @package app\services\article
 * @method save(array $data)保存
 * @method update($id, array $data, ?string $key = null)
 */
class ArticleContentServices extends BaseServices
{
    /**
     * ArticleContentServices constructor.
     * @param ArticleContentDao $dao
     */
    public function __construct(ArticleContentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     */
    public function del(int $id)
    {
        return $this->dao->del($id);
    }
}
