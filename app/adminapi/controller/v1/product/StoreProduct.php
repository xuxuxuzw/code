<?php

namespace app\adminapi\controller\v1\product;


use app\adminapi\controller\AuthController;
use app\services\order\StoreCartServices;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use crmeb\services\UploadService;
use think\facade\App;

/**
 * Class StoreProduct
 * @package app\adminapi\controller\v1\product
 */
class StoreProduct extends AuthController
{
    protected $service;

    public function __construct(App $app, StoreProductServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header()
    {
        $list = $this->service->getHeader();
        return $this->success(compact('list'));
    }

    /**
     * 显示资源列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', 1]
        ]);
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '')
    {
        $this->service->setShow([$id], $is_show);
        return $this->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 设置批量商品上架
     * @return mixed
     */
    public function product_show()
    {
        [$ids] = $this->request->postMore([
            ['ids', []]
        ], true);
        if (empty($ids)) return $this->fail('请选择需要上架的商品');
        $this->service->setShow($ids, 1);
        return $this->success('上架成功');
    }

    /**
     * 设置批量商品下架
     * @return mixed
     */
    public function product_unshow()
    {
        [$ids] = $this->request->postMore([
            ['ids', []]
        ], true);
        if (empty($ids)) return $this->fail('请选择需要下架的商品');
        $this->service->setShow($ids, 0);
        return $this->success('下架成功');
    }

    /**
     * 获取规格模板
     * @return mixed
     */
    public function get_rule()
    {
        $list = $this->service->getRule();
        return $this->success($list);
    }

    /**
     * 获取商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_product_info($id = 0)
    {
        return $this->success($this->service->getInfo((int)$id));
    }

    /**
     * 保存新建或编辑
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['cate_id', []],
            ['store_name', ''],
            ['store_info', ''],
            ['keyword', ''],
            ['unit_name', '件'],
            ['image', []],
            ['slider_image', []],
            ['postage', 0],
            ['is_sub', 0],
            ['sort', 0],
            ['sales', 0],
            ['ficti', 100],
            ['give_integral', 0],
            ['is_show', 0],
            ['temp_id', 0],
            ['is_hot', 0],
            ['is_benefit', 0],
            ['is_best', 0],
            ['is_new', 0],
            ['mer_use', 0],
            ['is_postage', 0],
            ['is_good', 0],
            ['description', ''],
            ['spec_type', 0],
            ['video_link', ''],
            ['items', []],
            ['attrs', []],
            ['activity', []],
            ['coupon_ids', []],
            ['label_id', []]
        ]);
        $this->service->save((int)$id, $data);
        return $this->success('添加商品成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $res = $this->service->del($id);
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cartService->changeStatus($id, 1);
        return $this->success($res);
    }

    /**
     * 生成规格列表
     * @param int $id
     * @param int $type
     * @return mixed
     */
    public function is_format_attr($id = 0, $type = 0)
    {
        $data = $this->request->postMore([
            ['attrs', []],
            ['items', []]
        ]);
        $info = $this->service->getAttr($data, $id, $type);
        return $this->success(compact('info'));
    }


    /**
     * 获取选择的商品列表
     * @return mixed
     */
    public function search_list()
    {
        $where = $this->request->getMore([
            ['cate_id', 0],
            ['store_name', ''],
            ['type', 1]
        ]);
        /** @var StoreCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreCategoryServices::class);
        if ($storeCategoryServices->value(['id' => $where['cate_id']], 'pid')) {
            $where['sid'] = $where['cate_id'];
        } else {
            $where['cid'] = $where['cate_id'];
        }
        unset($where['cate_id']);
        $list = $this->service->searchList($where);
        return $this->success($list);
    }

    /**
     * 获取某个商品规格
     * @return mixed
     */
    public function get_attrs()
    {
        [$id, $type] = $this->request->getMore([
            [['id', 'd'], 0],
            [['type', 'd'], 0],
        ], true);
        $info = $this->service->getProductRules($id, $type);
        return $this->success(compact('info'));
    }

    /**
     * 获取运费模板列表
     * @return mixed
     */
    public function get_template()
    {
        return $this->success($this->service->getTemp());
    }

    public function getTempKeys()
    {
        $upload = UploadService::init();
        $re = $upload->getTempKeys();
        return  $re ? $this->success($re) : $this->fail($upload->getError());
    }

    /**
     * 检测商品是否开活动
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function check_activity($id)
    {
        $this->service->checkActivity($id);
        return $this->success('删除成功');
    }
}
