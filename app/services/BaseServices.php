<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services;

use crmeb\utils\JwtAuth;
use think\facade\Db;
use think\facade\Config;
use think\facade\Route as Url;

/**
 * Class BaseServices
 * @package app\services
 */
abstract class BaseServices
{
    /**
     * 模型注入
     * @var object
     */
    protected $dao;

    /**
     * 获取分页配置
     * @param bool $isRelieve
     * @return array
     */
    public function getPageValue(bool $isRelieve = true)
    {
        $page = app()->request->param(Config::get('database.page.pageKey') . '/d', 0);
        $limit = app()->request->param(Config::get('database.page.limitKey') . '/d', 0);
        $limitMax = Config::get('database.page.limitMax');
        $defaultLimit = Config::get('database.page.defaultLimit', 10);
        if ($limit > $limitMax && $isRelieve) {
            $limit = $limitMax;
        }
        return [(int)$page, (int)$limit, (int)$defaultLimit];
    }

    /**
     * 数据库事务操作
     * @param callable $closure
     * @return mixed
     */
    public function transaction(callable $closure)
    {
        return Db::transaction($closure);
    }

    /**
     * 创建token
     * @param int $id
     * @param $type
     * @return array
     */
    public function createToken(int $id, $type)
    {
        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        return $jwtAuth->createToken($id, $type);
    }

    /**
     * 获取路由地址
     * @param string $path
     * @param array $params
     * @param bool $suffix
     * @return \think\route\Url
     */
    public function url(string $path, array $params = [], bool $suffix = false)
    {
        return Url::buildUrl($path, $params)->suffix($suffix)->build();
    }

    /**
     * 密码hash加密
     * @param string $password
     * @return false|string|null
     */
    public function passwordHash(string $password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return call_user_func_array([$this->dao, $name], $arguments);
    }
}
