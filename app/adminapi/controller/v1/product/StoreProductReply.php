<?php

namespace app\adminapi\controller\v1\product;

use app\adminapi\controller\AuthController;
use app\services\product\product\StoreProductReplyServices;
use think\facade\App;

/**
 * 评论管理 控制器
 * Class StoreProductReply
 * @package app\admin\controller\store
 */
class StoreProductReply extends AuthController
{
    public function __construct(App $app, StoreProductReplyServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_reply', ''],
            ['store_name', ''],
            ['account', ''],
            ['data', ''],
            ['product_id', 0]
        ]);
        $list = $this->services->sysPage($where);
        return $this->success($list);
    }

    /**
     * 删除评论
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->services->del($id);
        return $this->success('删除成功!');
    }

    /**
     * 回复评论
     * @param $id
     * @return mixed
     */
    public function set_reply($id)
    {
        [$content] = $this->request->postMore([
            ['content', '']
        ], true);
        $this->services->setReply($id, $content);
        return $this->success('回复成功!');
    }

    /**
     * 创建虚拟评论表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function fictitious_reply()
    {
        list($product_id) = $this->request->postMore([
            ['product_id', 0],
        ], true);
        return $this->success($this->services->createForm($product_id));
    }

    /**
     * 保存虚拟评论
     * @return mixed
     */
    public function save_fictitious_reply()
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['nickname', ''],
            ['avatar', ''],
            ['comment', ''],
            ['pics', []],
            ['product_score', 0],
            ['service_score', 0],
        ]);
        $data['product_id'] = $data['image']['product_id'] ?? '';
        $this->validate(['product_id' => $data['product_id'], 'nickname' => $data['nickname'], 'avatar' => $data['avatar'], 'comment' => $data['comment'], 'product_score' => $data['product_score'], 'service_score' => $data['service_score']], \app\adminapi\validate\product\StoreProductReplyValidate::class, 'save');
        $this->services->saveReply($data);
        return $this->success('添加成功!');
    }
}
