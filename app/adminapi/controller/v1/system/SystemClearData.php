<?php

namespace app\adminapi\controller\v1\system;

use think\facade\App;
use app\adminapi\controller\AuthController;
use app\services\system\SystemClearServices;
use app\services\product\product\StoreProductServices;
use app\services\system\attachment\SystemAttachmentServices;


/**
 * 清除默认数据理控制器
 * Class SystemClearData
 * @package app\admin\controller\system
 */
class SystemClearData extends AuthController
{
    /**
     * 构造方法
     * SystemClearData constructor.
     * @param App $app
     * @param SystemClearServices $services
     */
    public function __construct(App $app, SystemClearServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 统一方法
     * @param $type
     */
    public function index($type)
    {
        switch ($type) {
            case 'temp':
                return $this->userTemp();
                break;
            case 'recycle':
                return $this->recycleProduct();
                break;
            case 'user':
                return $this->userRelevantData();
                break;
            case 'store':
                return $this->storeData();
                break;
            case 'category':
                return $this->categoryData();
                break;
            case 'order':
                return $this->orderData();
                break;
            case 'kefu':
                return $this->kefuData();
                break;
            case 'wechat':
                return $this->wechatData();
                break;
            case 'attachment':
                return $this->attachmentData();
                break;
            case 'wechatuser':
                return $this->wechatuserData();
                break;
            case 'article':
                return $this->articledata();
                break;
            case 'system':
                return $this->systemdata();
                break;
            default:
                return $this->fail('参数有误');
        }
    }

    /**
     * 清除用户生成的临时附件
     * @param int $type
     * @throws \Exception
     */
    public function userTemp()
    {
        /** @var SystemAttachmentServices $services */
        $services = app()->make(SystemAttachmentServices::class);
        $services->delete(2, 'module_type');
        return $this->success('清除数据成功!');
    }

    //清除回收站商品
    public function recycleProduct()
    {
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        $services->delete(1, 'is_del');
        return $this->success('清除数据成功!');
    }

    /**
     * 清除用户数据
     * @return mixed
     */
    public function userRelevantData()
    {
        $this->services->clearData([
            'user_recharge', 'user_address', 'user_bill', 'user_enter', 'user_extract',
            'user_notice', 'user_notice_see', 'wechat_message', 'store_visit',
            'store_coupon_user', 'store_coupon_issue_user', 'store_bargain_user', 'store_bargain_user_help',
            'store_product_reply', 'store_product_cate', 'user_sign', 'user_task_finish',
            'user_level', 'user_group', 'user_visit', 'user_label', 'user_label_relation', 'user_label_relation',
            'store_product_relation', 'sms_record', 'system_file', 'system_store', 'system_store_staff',

        ], true);
        $this->services->delDirAndFile('./public/uploads/store/comment');
        return $this->success('清除数据成功!');
    }

    /**
     * 清除商城数据
     * @return mixed
     */
    public function storeData()
    {
        $this->services->clearData([
            'store_coupon', 'store_coupon_issue', 'store_bargain', 'store_combination', 'store_product_attr',
            'store_product_attr_result', 'store_product_cate', 'store_product_attr_value', 'store_product_description',
            'store_product_rule', 'store_seckill', 'store_product', 'store_visit'
        ], true);
        return $this->success('清除数据成功!');
    }

    /**
     * 清除商品分类
     * @return mixed
     */
    public function categoryData()
    {
        $this->services->clearData(['store_category'], true);
        return $this->success('清除数据成功!');
    }

    /**
     * 清除订单数据
     * @return mixed
     */
    public function orderData()
    {
        $this->services->clearData(['store_order', 'store_order_cart_info', 'store_order_status', 'store_pink',
            'store_cart', 'store_order_status'
        ], true);
        return $this->success('清除数据成功!');
    }

    /**
     * 清除客服数据
     * @return mixed
     */
    public function kefuData()
    {
        $this->services->clearData([
            'store_service', 'store_service_log'
        ], true);
        $this->services->delDirAndFile('./public/uploads/store/service');
        return $this->success('清除数据成功!');
    }

    /**
     * 清除微信管理数据
     * @return mixed
     */
    public function wechatData()
    {
        $this->services->clearData([
            'wechat_media', 'wechat_reply', 'cache', 'wechat_key',
            'wechat_news_category'
        ], true);
        $this->services->delDirAndFile('./public/uploads/wechat');
        return $this->success('清除数据成功!');
    }

    /**
     * 清除所有附件
     * @return mixed
     */
    public function attachmentData()
    {
        $this->services->clearData([
            'system_attachment', 'system_attachment_category'
        ], true);
        $this->services->delDirAndFile('./public/uploads/');
        return $this->success('清除上传文件成功!');
    }

    /**
     * 清除微信用户
     * @return mixed
     */
    public function wechatuserData()
    {
        $this->services->clearData([
            'user', 'wechat_user'
        ], true);
        return $this->success('清除数据成功!');
    }

    //清除内容分类
    public function articledata()
    {
        $this->services->clearData([
            'article_category', 'article', 'article_content'
        ], true);
        return $this->success('清除数据成功!');
    }

    //清除系统记录
    public function systemdata()
    {
        $this->services->clearData([
            'system_notice_admin', 'system_log'
        ], true);
        return $this->success('清除数据成功!');
    }

    /**
     * 替换域名方法
     * @return mixed
     */
    public function replaceSiteUrl()
    {
        list($url) = $this->request->postMore([
            ['url', '']
        ], true);
        if (!$url)
            return $this->fail('请输入需要更换的域名');
        if (!verify_domain($url))
            return $this->fail('域名不合法');
        $this->services->replaceSiteUrl($url);
        return $this->success('替换成功！');
    }
}
