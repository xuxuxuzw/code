<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

if (!function_exists('get_this_class_methods')) {
    /**获取当前类方法
     * @param $class
     * @return array
     */
    function get_this_class_methods($class, $unarray = [])
    {
        $arrayall = get_class_methods($class);
        if ($parent_class = get_parent_class($class)) {
            $arrayparent = get_class_methods($parent_class);
            $arraynow = array_diff($arrayall, $arrayparent);//去除父级的
        } else {
            $arraynow = $arrayall;
        }
        return array_diff($arraynow, $unarray);//去除无用的
    }
}

if (!function_exists('get_tree_children')) {
    /**
     * tree 子菜单
     * @param $data
     * @param int $pid
     * @return array
     */
    function get_tree_children($data, $childrenname = 'children')
    {
        $list = array();
        foreach ($data as $value) {
            $list[$value['id']] = $value;
        }
        static $tree = array(); //格式化好的树
        foreach ($list as $item) {
            if (isset($list[$item['pid']])) {
                $list[$item['pid']][$childrenname][] = &$list[$item['id']];
            } else {
                $tree[] = &$list[$item['id']];
            }
        }
        return $tree;
    }
}

if (!function_exists('attr_format')) {
    /**
     * 格式化属性
     * @param $arr
     * @return array
     */
    function attr_format($arr)
    {
        $data = [];
        $res = [];
        $count = count($arr);
        if ($count > 1) {
            for ($i = 0; $i < $count - 1; $i++) {
                if ($i == 0) $data = $arr[$i]['detail'];
                //替代变量1
                $rep1 = [];
                foreach ($data as $v) {
                    foreach ($arr[$i + 1]['detail'] as $g) {
                        //替代变量2
                        $rep2 = ($i != 0 ? '' : $arr[$i]['value'] . '_$_') . $v . '-$-' . $arr[$i + 1]['value'] . '_$_' . $g;
                        $tmp[] = $rep2;
//                        if ($i == $count - 2) {
                            foreach (explode('-$-', $rep2) as $k => $h) {
                                //替代变量3
                                $rep3 = explode('_$_', $h);
                                //替代变量4
                                $rep4['detail'][$rep3[0]] = isset($rep3[1]) ? $rep3[1] : '';
                            }
                            if ($count == count($rep4['detail']))
                                $res[] = $rep4;
//                        }
                    }
                }
                $data = isset($tmp) ? $tmp : [];
            }
        } else {
            $dataArr = [];
            foreach ($arr as $k => $v) {
                foreach ($v['detail'] as $kk => $vv) {
                    $dataArr[$kk] = $v['value'] . '_' . $vv;
                    $res[$kk]['detail'][$v['value']] = $vv;
                }
            }
            $data[] = implode('-', $dataArr);
        }
        return [$data, $res];
    }
}

if (!function_exists('verify_domain')) {

    /**
     * 验证域名是否合法
     * @param string $domain
     * @return bool
     */
    function verify_domain(string $domain): bool
    {
        $res = "/^(?=^.{3,255}$)(http(s)?:\/\/)(www\.)?[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(\.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+(:\d+)*(\/\w+\.\w+)*$/";
        if (preg_match($res, $domain))
            return true;
        else
            return false;
    }
}
