<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\adminapi\controller\v1\cms;

use app\adminapi\controller\AuthController;
use app\services\article\ArticleCategoryServices;
use crmeb\services\CacheService;
use think\facade\App;

/**
 * 文章分类管理
 * Class ArticleCategory
 * @package app\adminapi\controller\v1\cms
 */
class ArticleCategory extends AuthController
{
    protected $service;

    public function __construct(App $app, ArticleCategoryServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取分类列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['title', ''],
            ['type', 0]
        ]);
        $type = $where['type'];
        unset($where['type']);
        $data = $this->service->getList($where);
        if ($type == 1) $data = $data['list'];
        return $this->success($data);
    }

    /**
     * 创建新增表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->service->createForm(0));
    }

    /**
     * 保存新建分类
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['title', ''],
            ['intr', ''],
            ['image', ''],
            ['sort', 0],
            ['status', 0]
        ]);
        $data['add_time'] = time();
        $this->service->save($data);
        return $this->success('添加分类成功!');
    }

    /**
     * 创建修改表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit($id)
    {
        return $this->success($this->service->createForm($id));
    }

    /**
     * 保存修改分类
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['title', ''],
            ['intr', ''],
            ['image', ''],
            ['sort', 0],
            ['status', 0]
        ]);
        $this->service->update($data);
        CacheService::delete('ARTICLE_CATEGORY');
        return $this->success('修改成功!');
    }

    /**
     * 删除文章分类
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->service->del($id);
        CacheService::delete('ARTICLE_CATEGORY');
        return $this->success('删除成功!');
    }

    /**
     * 修改文章分类状态
     * @param int $id
     * @param int $status
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set_status($id, $status)
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->service->setStatus($id, $status);
        CacheService::delete('ARTICLE_CATEGORY');
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }

    /**
     * 获取文章分类
     * @return mixed
     */
    public function categoryList()
    {
        return $this->success($this->service->getArticleCategory());
    }
}
