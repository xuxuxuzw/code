<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-15
 */

namespace app\adminapi\controller\v1\diy;


use app\adminapi\controller\AuthController;
use app\services\diy\DiyServices;
use app\services\other\CacheServices;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use crmeb\exceptions\AdminException;
use think\facade\App;

class Diy extends AuthController
{
    protected $services;

    public function __construct(App $app, DiyServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * DIY列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['type', ''],
            ['name', ''],
            ['version', ''],
        ]);
        $data = $this->services->getDiyList($where);
        return $this->success($data);
    }

    /**
     * 保存资源
     * @param int $id
     * @return mixed
     */
    public function saveData(int $id = 0)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['value', ''],
            ['type', ''],
        ]);
        $value = is_string($data['value']) ? json_decode($data['value'], true) : $data['value'];
        $data['value'] = json_encode($value);
        $data['version'] = '1.0';
        $this->services->saveData($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 删除模板
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        $this->services->del($id);
        return $this->success('删除成功');
    }

    /**
     * 使用模板
     * @param $id
     * @return mixed
     */
    public function setStatus($id)
    {
        $this->services->setStatus($id);
        return $this->success('设置成功');
    }

    /**
     * 获取一条数据
     * @param int $id
     * @return mixed
     */
    public function getInfo(int $id)
    {
        if (!$id) throw new AdminException('参数错误');
        $info = $this->services->get($id);
        if ($info) {
            $info->toArray();
        } else {
            throw new AdminException('模板不存在');
        }
        $info['value'] = json_decode($info['value'], true);
        return $this->success(compact('info'));
    }

    /**
     * 获取uni-app路径
     * @return mixed
     */
    public function getUrl()
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        $url = $cache->getDbCache('uni_app_url', null);
        return $this->success(compact('url'));
    }

    /**
     * 获取商品分类
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategory()
    {
        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $list = $categoryService->getTierList(1, 1);
        $data = [];
        foreach ($list as $value) {
            $data[] = [
                'id' => $value['id'],
                'title' => $value['html'] . $value['cate_name']
            ];
        }
        return $this->success($data);
    }

    /**
     * 获取商品
     * @return mixed
     */
    public function getProduct()
    {
        $where = $this->request->getMore([
            ['id', 0],
            ['salesOrder', ''],
            ['priceOrder', ''],
        ]);
        $id = $where['id'];
        unset($where['id']);
        /** @var StoreCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreCategoryServices::class);
        if ($storeCategoryServices->value(['id' => $id], 'pid')) {
            $where['sid'] = $id;
        } else {
            $where['cid'] = $id;
        }
        [$page, $limit] = $this->services->getPageValue();
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $list = $productService->getSearchList($where, $page, $limit);
        return $this->success($list);
    }
}
