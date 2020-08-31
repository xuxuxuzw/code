<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\system\log;


use app\services\BaseServices;

class ClearServices extends BaseServices
{
    /** 递归删除文件
     * @param $dirName
     * @param bool $subdir
     */
    protected function delDirAndFile($dirName)
    {
        $list = glob($dirName . '*');
        foreach ($list as $file) {
            if (is_dir($file))
                $this->delDirAndFile($file . DS);
            else
                @unlink($file);
        }
        @rmdir($dirName);
    }

    /**
     * 删除日志
     */
    public function deleteLog()
    {
        $root = app()->getRootPath() . 'runtime' . DS;
        $this->delDirAndFile($root . 'admin' . DS . 'log' . DS);
        $this->delDirAndFile($root . 'api' . DS . 'log' . DS);
        $this->delDirAndFile($root . 'log' . DS);
    }

    /**
     * 刷新数据缓存
     */
    public function refresCache()
    {
        $root = app()->getRootPath() . 'runtime' . DS;
        $adminRoute = $root . 'admin';
        $apiRoute = $root . 'api';
        $cacheRoute = $root . 'cache';
        $cache = [];

        if (is_dir($adminRoute))
            $cache[$adminRoute] = scandir($adminRoute);
        if (is_dir($apiRoute))
            $cache[$apiRoute] = scandir($apiRoute);
        if (is_dir($cacheRoute))
            $cache[$cacheRoute] = scandir($cacheRoute);

        foreach ($cache as $p => $list) {
            foreach ($list as $file) {
                if (!in_array($file, ['.', '..', 'log', 'schema', 'route.php'])) {
                    $path = $p . DS . $file;
                    if (is_file($path)) {
                        @unlink($path);
                    } else {
                        $this->delDirAndFile($path . DS);
                    }
                }
            }
        }
    }
}