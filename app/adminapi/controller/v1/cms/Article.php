<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\adminapi\controller\v1\cms;

use app\adminapi\controller\AuthController;
use app\services\article\ArticleServices;
use think\facade\App;

/**
 * 文章管理
 * Class Article
 * @package app\adminapi\controller\v1\cms
 */
class Article extends AuthController
{
    protected $service;

    public function __construct(App $app, ArticleServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['title', ''],
            ['pid', 0, '', 'cid'],
        ]);
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 保存文章数据
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['cid', ''],
            ['title', ''],
            ['author', ''],
            ['image_input', ''],
            ['content', ''],
            ['synopsis', 0],
            ['share_title', ''],
            ['share_synopsis', ''],
            ['visit', 0],
            ['sort', 0],
            ['url', ''],
            ['is_banner', 0],
            ['is_hot', 0],
            ['status', 1]
        ]);
        $this->service->save($data);
        return $this->success('添加成功!');
    }

    /**
     * 获取单个文章数据
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if ($id) {
            $info = $this->service->read($id);
            return $this->success($info);
        } else {
            return $this->fail('参数错误');
        }

    }

    /**
     * 删除文章
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function delete($id)
    {
        if ($id) {
            $this->service->del($id);
            return $this->success('删除成功!');
        } else {
            return $this->fail('参数错误');
        }
    }

    /**
     * 文章关联商品
     * @param int $id
     * @return mixed
     */
    public function relation($id)
    {
        if (!$id) return $this->fail('缺少参数');
        list($product_id) = $this->request->postMore([
            ['product_id', 0]
        ], true);
        $res = $this->service->bindProduct($id, $product_id);
        if ($res) {
            return $this->success('关联成功');
        } else {
            return $this->fail('关联失败');
        }
    }

    /**
     * 取消商品关联
     * @param int $id
     * @return mixed
     */
    public function unrelation($id)
    {
        if (!$id) return $this->fail('缺少参数');
        $res = $this->service->bindProduct($id);
        if ($res) {
            return $this->success('取消关联成功！');
        } else {
            return $this->fail('取消失败');
        }
    }
}
