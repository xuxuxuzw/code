<?php

namespace app\adminapi\controller\v1\system;

use think\facade\App;
use think\facade\Session;
use app\adminapi\controller\AuthController;
use app\services\system\SystemDatabackupServices;


/**
 * 数据备份
 * Class SystemDatabackup
 * @package app\admin\controller\system
 *
 */
class SystemDatabackup extends AuthController
{
    /**
     * 构造方法
     * SystemDatabackup constructor.
     * @param App $app
     * @param SystemDatabackupServices $services
     */
    public function __construct(App $app, SystemDatabackupServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取数据库表
     */
    public function index()
    {
        return $this->success($this->services->getDataList());
    }

    /**
     * 查看表结构 详情
     */
    public function read()
    {
        $tablename = request()->param('tablename', '', 'htmlspecialchars');
        return $this->success($this->services->getRead($tablename));
    }

    /**
     * 优化表
     */
    public function optimize()
    {
        $tables = $this->request->param('tables', '', 'htmlspecialchars');
        $res = $this->services->getDbBackup()->optimize($tables);
        return $this->success($res ? '优化成功' : '优化失败');
    }

    /**
     * 修复表
     */
    public function repair()
    {
        $tables = $this->request->param('tables', '', 'htmlspecialchars');
        $res = $this->services->getDbBackup()->repair($tables);
        return $this->success($res ? '修复成功' : '修复失败');
    }

    /**
     * 备份表
     */
    public function backup()
    {
        $tables = $this->request->param('tables', '', 'htmlspecialchars');
        $data = $this->services->backup($tables);
        return $this->success($data ? '备份失败' . $data : '备份成功');
    }

    /**
     * 获取备份记录表
     */
    public function fileList()
    {
        return $this->success($this->services->getBackup());
    }

    /**
     * 删除备份记录表
     */
    public function delFile()
    {
        $filename = intval(request()->post('filename'));
        $files = $this->services->getDbBackup()->delFile($filename);
        return $this->success('删除成功');
    }

    /**
     * 导入备份记录表
     */
    public function import()
    {
        [$part, $start, $time] = $this->request->postMore([
            [['part', 'd'], 0],
            [['start', 'd'], 0],
            [['time', 'd'], 0],
        ], true);
        $db = $this->services->getDbBackup();
        if (is_numeric($time) && !$start) {
            $list = $db->getFile('timeverif', $time);
            if (is_array($list)) {
                session::set('backup_list', $list);
                return $this->success('初始化完成！', array('part' => 1, 'start' => 0));
            } else {
                return $this->fail('备份文件可能已经损坏，请检查！');
            }
        } else if (is_numeric($part) && is_numeric($start) && $part && $start) {
            $list = session::get('backup_list');
            $start = $db->setFile($list)->import($start);
            if (false === $start) {
                return $this->fail('还原数据出错！');
            } elseif (0 === $start) {
                if (isset($list[++$part])) {
                    $data = array('part' => $part, 'start' => 0);
                    return $this->success("正在还原...#{$part}", $data);
                } else {
                    session::delete('backup_list');
                    return $this->success('还原完成！');
                }
            } else {
                $data = array('part' => $part, 'start' => $start[0]);
                if ($start[1]) {
                    $rate = floor(100 * ($start[0] / $start[1]));
                    return $this->success("正在还原...#{$part}({$rate}%)", $data);
                } else {
                    $data['gz'] = 1;
                    return $this->success("正在还原...#{$part}", $data);
                }
                return $this->success("正在还原...#{$part}");
            }
        } else {
            return $this->fail('参数错误！');
        }
    }

    /**
     * 下载备份记录表
     */
    public function downloadFile()
    {
        $time = intval(request()->param('time'));
        $this->services->getDbBackup()->downloadFile($time);
    }
}
