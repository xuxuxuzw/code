<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\dao\wechat;

use app\dao\BaseDao;
use app\model\wechat\WechatNewsCategory;

/**
 *
 * Class UserWechatUserDao
 * @package app\dao\user
 */
class WechatNewsCategoryDao extends BaseDao
{
    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WechatNewsCategory::class;
    }

    /**新闻分类 $model
     * @param array $where
     * @return \crmeb\basic\BaseModel
     */
    public function getNewCtae(array $where)
    {
        return parent::getModel()::when(isset($where['cate_name']), function ($query) use ($where) {
            $query->where('cate_name', 'LIKE', "%$where[cate_name]%");
        })->where('status', 1)->order('add_time desc');
    }


}
