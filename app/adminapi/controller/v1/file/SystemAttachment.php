<?php

namespace app\adminapi\controller\v1\file;

use app\adminapi\controller\AuthController;
use app\services\system\attachment\SystemAttachmentServices;
use think\facade\App;

/**
 * 图片管理类
 * Class SystemAttachment
 * @package app\adminapi\controller\v1\file
 */
class SystemAttachment extends AuthController
{
    protected $service;

    public function __construct(App $app, SystemAttachmentServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 显示列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['pid', 0]
        ]);
        return $this->success($this->service->getImageList($where));
    }

    /**
     * 删除指定资源
     *
     * @param string $ids
     * @return \think\Response
     */
    public function delete()
    {
        [$ids] = $this->request->postMore([
            ['ids', '']
        ], true);
        $this->service->del($ids);
        return $this->success('删除成功');
    }

    /**
     * 图片上传
     * @param int $upload_type
     * @param int $type
     * @return mixed
     */
    public function upload($upload_type = 0, $type = 0)
    {
        [$pid, $file] = $this->request->postMore([
            ['pid', 0],
            ['file', 'file'],
        ], true);
        $res = $this->service->upload((int)$pid, $file, $upload_type, $type);
        return $this->success('上传成功', ['src' => $res]);
    }

    /**
     * 移动图片
     * @return mixed
     */
    public function moveImageCate()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['images', '']
        ]);
        $this->service->move($data);
        return $this->success('移动成功');
    }

}
