<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\activity;

use app\dao\activity\StoreBargainDao;
use app\Request;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\wechat\WechatServices;
use crmeb\exceptions\AdminException;
use crmeb\jobs\RoutineTemplateJob;
use crmeb\jobs\WechatTemplateJob;
use crmeb\utils\Queue;
use think\exception\ValidateException;

/**
 *
 * Class StoreBargainServices
 * @package app\services\activity
 * @method get(int $id, array $field) 获取一条数据
 * @method getBargainIdsArray(array $ids, array $field)
 * @method sum(array $where, string $field)
 * @method update(int $id, array $data)
 * @method addBargain(int $id, string $field)
 * @method value(array $where, string $field)
 * @method validWhere()
 * @method getList(array $where, int $page = 0, int $limit = 0) 获取砍价列表
 */
class StoreBargainServices extends BaseServices
{

    /**
     * StoreCombinationServices constructor.
     * @param StoreBargainDao $dao
     */
    public function __construct(StoreBargainDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 判断砍价商品是否开启
     * @param int $bargainId
     * @return int|string
     */
    public function validBargain($bargainId = 0)
    {
        $where = [];
        $time = time();
        $where[] = ['is_del', '=', 0];
        $where[] = ['status', '=', 1];
        $where[] = ['start_time', '<', $time];
        $where[] = ['stop_time', '>', $time - 85400];
        if ($bargainId) $where[] = ['id', '=', $bargainId];
        return $this->dao->getCount($where);
    }

    /**
     * 获取后台列表
     * @param array $where
     * @return array
     */
    public function getStoreBargainList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where);
        /** @var StoreBargainUserServices $storeBargainUserServices */
        $storeBargainUserServices = app()->make(StoreBargainUserServices::class);
        $ids = array_column($list, 'id');
        $countAll = $storeBargainUserServices->getAllCount([['bargain_id', 'in', $ids]]);
        $countSuccess = $storeBargainUserServices->getAllCount([
            ['status', '=', 3],
            ['bargain_id', 'in', $ids]
        ]);
        /** @var StoreBargainUserHelpServices $storeBargainUserHelpServices */
        $storeBargainUserHelpServices = app()->make(StoreBargainUserHelpServices::class);
        $countHelpAll = $storeBargainUserHelpServices->getHelpAllCount([
            ['bargain_id', 'in', $ids]
        ]);
        foreach ($list as &$item) {
            $item['count_people_all'] = $countAll[$item['id']] ?? 0;//参与人数
            $item['count_people_help'] = $countHelpAll[$item['id']] ?? 0;//帮忙砍价人数
            $item['count_people_success'] = $countSuccess[$item['id']] ?? 0;//砍价成功人数
        }
        return compact('list', 'count');
    }

    /**
     * 保存数据
     * @param int $id
     * @param array $data
     */
    public function saveData(int $id, array $data)
    {
        $description = $data['description'];
        $detail = $data['attrs'];
        $items = $data['items'];
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $data['images'] = json_encode($data['images']);
        $data['stock'] = $detail[0]['stock'];
        $data['quota'] = $detail[0]['quota'];
        $data['quota_show'] = $detail[0]['quota'];
        $data['price'] = $detail[0]['price'];
        $data['min_price'] = $detail[0]['min_price'];
        unset($data['section_time'], $data['description'], $data['attrs'], $data['items'], $detail[0]['min_price'], $detail[0]['_index'], $detail[0]['_rowKey']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $this->transaction(function () use ($id, $data, $description, $detail, $items, $storeDescriptionServices, $storeProductAttrServices, $storeProductServices) {
            if ($id) {
                $res = $this->dao->update($id, $data);
                $storeDescriptionServices->saveDescription((int)$id, $description, 2);
                $skuList = $storeProductServices->validateProductAttr($items, $detail, (int)$id, 2);
                $storeProductAttrServices->saveProductAttr($skuList, (int)$id, 2);
                if (!$res) throw new AdminException('修改失败');
            } else {
                $data['add_time'] = time();
                $res = $this->dao->save($data);
                $storeDescriptionServices->saveDescription((int)$res->id, $description, 2);
                $skuList = $storeProductServices->validateProductAttr($items, $detail, (int)$res->id, 2);
                $storeProductAttrServices->saveProductAttr($skuList, (int)$res->id, 2);
                if (!$res) throw new AdminException('添加失败');
            }
        });
    }

    /**
     * 获取砍价详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id);
        if ($info) {
            if ($info['start_time'])
                $start_time = date('Y-m-d H:i:s', $info['start_time']);

            if ($info['stop_time'])
                $stop_time = date('Y-m-d H:i:s', $info['stop_time']);
            if (isset($start_time) && isset($stop_time))
                $info['section_time'] = [$start_time, $stop_time];
            else
                $info['section_time'] = [];
            unset($info['start_time'], $info['stop_time']);
        }
        $info['give_integral'] = intval($info['give_integral']);
        $info['price'] = floatval($info['price']);
        $info['postage'] = floatval($info['postage']);
        $info['cost'] = floatval($info['cost']);
        $info['bargain_max_price'] = floatval($info['bargain_max_price']);
        $info['bargain_min_price'] = floatval($info['bargain_min_price']);
        $info['min_price'] = floatval($info['min_price']);
        $info['weight'] = floatval($info['weight']);
        $info['volume'] = floatval($info['volume']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        $info['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 2]);
        $info['attrs'] = $this->attrList($id, $info['product_id']);
        return $info;
    }

    /**
     * 获取规格
     * @param int $id
     * @param int $pid
     * @return mixed
     */
    public function attrList(int $id, int $pid)
    {
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        $bargainResult = $storeProductAttrResultServices->value(['product_id' => $id, 'type' => 2], 'result');
        $items = json_decode($bargainResult, true)['attr'];
        $productAttr = $this->getattr($items, $pid, 0);
        $bargainAttr = $this->getattr($items, $id, 2);
        foreach ($productAttr as $pk => $pv) {
            foreach ($bargainAttr as &$sv) {
                if ($pv['detail'] == $sv['detail']) {
                    $productAttr[$pk] = $sv;
                }
            }
            $productAttr[$pk]['detail'] = json_decode($productAttr[$pk]['detail']);
        }
        $attrs['items'] = $items;
        $attrs['value'] = $productAttr;
        foreach ($items as $key => $item) {
            $header[] = ['title' => $item['value'], 'key' => 'value' . ($key + 1), 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '砍价起始金额', 'slot' => 'price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '砍价最低价', 'slot' => 'min_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '限量', 'slot' => 'quota', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编号', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $attrs['header'] = $header;
        return $attrs;
    }

    /**
     * 获取规格
     * @param $attr
     * @param $id
     * @param $type
     * @return array
     */
    public function getattr($attr, $id, $type)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        if ($type == 2) {
            $min_price = $this->dao->value(['id' => $id], 'min_price');
        } else {
            $min_price = 0;
        }
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            $sukValue = $storeProductAttrValueServices->getColumn(['product_id' => $id, 'type' => $type, 'suk' => $suk], 'bar_code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota', 'suk');
            if (count($sukValue)) {
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                }
                $valueNew[$count]['detail'] = json_encode($detail);
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['min_price'] = $min_price ? floatval($min_price) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
                $valueNew[$count]['quota'] = $sukValue[$suk]['quota'] ? intval($sukValue[$suk]['quota']) : 0;
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
                $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
                $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
                $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
                $valueNew[$count]['opt'] = $type != 0 ? true : false;
                $count++;
            }
        }
        return $valueNew;
    }

    /**
     * TODO 获取砍价表ID
     * @param int $bargainId $bargainId 砍价商品
     * @param int $bargainUserUid $bargainUserUid  开启砍价用户编号
     * @param int $status $status  砍价状态 1参与中 2 活动结束参与失败 3活动结束参与成功
     * @return mixed
     */
    public function getBargainUserTableId($bargainId = 0, $bargainUserUid = 0)
    {
        return $this->dao->value(['bargain_id' => $bargainId, 'uid' => $bargainUserUid, 'is_del' => 0], 'id');
    }

    /**
     * TODO 获取用户可以砍掉的价格
     * @param $id $id 用户参与砍价表编号
     * @return float
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBargainUserDiffPriceFloat($id)
    {
        $price = $this->dao->get($id, ['bargain_price,bargain_price_min']);
        return (float)bcsub($price['bargain_price'], $price['bargain_price_min'], 2);
    }

    /**
     * TODO 获取用户砍掉的价格
     * @param int $id $id 用户参与砍价表编号
     * @return float
     */
    public function getBargainUserPrice($id = 0)
    {
        return (float)$this->dao->value(['id' => $id], 'price');
    }

    /**
     * 获取一条砍价商品
     * @param int $bargainId
     * @param string $field
     * @return array
     */
    public function getBargainOne($bargainId = 0, $field = 'id,product_id,title,price,min_price,image')
    {
        if (!$bargainId) return [];
        $bargain = $this->dao->getOne(['id' => $bargainId], $field);
        if ($bargain) return $bargain->toArray();
        else return [];
    }

    /**
     * 砍价列表
     * @return array
     */
    public function getBargainList()
    {
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->BargainList($page, $limit);
        foreach ($list as &$item) {
            $item['people'] = count($bargainUserService->getUserIdList($item['id']));
            $item['price'] = floatval($item['price']);
        }
        return $list;
    }

    /**获取单条砍价
     * @param Request $request
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBargain(Request $request, int $id)
    {
        $bargain = $this->dao->getOne(['id' => $id], '*', ['description']);
        if (!$bargain) throw new ValidateException('砍价商品不存在');
        $this->dao->addBargain($id, 'look');
        $bargain['time'] = time();
//        $bargain['stop_time'] = $bargain['stop_time'] + 86400;
        $user = $request->user();
        $data['userInfo']['uid'] = $user['uid'];
        $data['userInfo']['nickname'] = $user['nickname'];
        $data['userInfo']['avatar'] = $user['avatar'];

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        list($productAttr, $productValue) = $storeProductAttrServices->getProductAttrDetail($id, $user->uid, 0, 2, $bargain['product_id']);
        foreach ($productValue as $v) {
            $bargain['attr'] = $v;
        }

        $data['bargain'] = $bargain;

        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $data['bargainSumCount'] = $orderService->count(['bargain_id' => $id, 'paid' => 1, 'refund_status' => 0]);

        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $data['userBargainStatus'] = $bargainUserService->count(['bargain_id' => $id, 'uid' => $user->uid, 'is_del' => 0]);

//        StoreVisit::setView($user['uid'], $id, 'bargain',$bargain['product_id'], 'viwe');
        return $data;
    }

    /**
     * 验证砍价是否能支付
     * @param int $bargainId
     * @param int $uid
     */
    public function checkBargainUser(int $bargainId, int $uid)
    {
        /** @var StoreBargainUserServices $bargainUserServices */
        $bargainUserServices = app()->make(StoreBargainUserServices::class);
        // 获取用户参与砍价表编号
        $bargainUserTableId = $bargainUserServices->value(['bargain_id' => $bargainId, 'uid' => $uid, 'is_del' => 0], 'id');
        if (!$bargainUserTableId)
            throw new ValidateException('砍价失败');
        $status = $bargainUserServices->value(['id' => $bargainUserTableId], 'status');
        if ($status == 3)
            throw new ValidateException('砍价已支付');
        $this->setBargainUserStatus($bargainId, $uid, $bargainUserTableId); //修改砍价状态
    }

    /**
     * 修改砍价状态
     * @param int $bargainId
     * @param int $uid
     * @param int $bargainUserTableId
     * @return bool|\crmeb\basic\BaseModel
     */
    public function setBargainUserStatus(int $bargainId, int $uid, int $bargainUserTableId)
    {
        if (!$bargainId || !$uid) return false;
        if (!$bargainUserTableId) return false;
        /** @var StoreBargainUserServices $bargainUserServices */
        $bargainUserServices = app()->make(StoreBargainUserServices::class);
        $count = $bargainUserServices->count(['id' => $bargainUserTableId, 'uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1]);
        if (!$count) return false;
        $userPrice = $bargainUserServices->value(['id' => $bargainUserTableId, 'uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1], 'price');
        $price = $bargainUserServices->get($bargainUserTableId, ['bargain_price', 'bargain_price_min']);
        $price = bcsub($price['bargain_price'], $price['bargain_price_min'], 2);
        if (bcsub($price, $userPrice, 2) > 0) {
            return false;
        }
        return $bargainUserServices->updateBargainStatus($bargainUserTableId);
    }

    /**
     * 参与砍价
     * @param int $uid
     * @param int $bargainId
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setBargain(int $uid, int $bargainId)
    {
        if (!$bargainId) throw new ValidateException('参数错误');
        $bargainInfo = $this->dao->getOne([
            ['is_del', '=', 0],
            ['status', '=', 1],
            ['start_time', '<', time()],
            ['stop_time', '>', time()],
            ['id', '=', $bargainId],
        ]);
        if (!$bargainInfo) throw new ValidateException('砍价已结束');
        $bargainInfo = $bargainInfo->toArray();
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $count = $bargainUserService->count(['bargain_id' => $bargainId, 'uid' => $uid, 'is_del' => 0]);

        if ($count === false) {
            throw new ValidateException('参数错误');
        } elseif ($count) {
            return 'SUCCESSFUL';
        } else {
            $res = $bargainUserService->setBargain($bargainId, $uid, $bargainInfo);
        }
        if (!$res) {
            throw new ValidateException('参与失败');
        } else {
            return 'SUCCESS';
        }
    }

    /**
     * @param Request $request
     * @param int $bargainId
     * @param int $bargainUserUid
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setHelpBargain(int $uid, int $bargainId, int $bargainUserUid)
    {
        if (!$bargainId || !$bargainUserUid) throw new ValidateException('参数错误');

        /** @var StoreBargainUserHelpServices $userHelpService */
        $userHelpService = app()->make(StoreBargainUserHelpServices::class);
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserTableId = $bargainUserService->getBargainUserTableId($bargainId, $bargainUserUid);
        $count = $userHelpService->isBargainUserHelpCount($bargainId, $bargainUserTableId, $uid);
        if (!$count) return 'SUCCESSFUL';
        $res = $userHelpService->setBargainUserHelp($bargainId, $bargainUserTableId, $uid);


        if ($res) {
            if (!$bargainUserService->getSurplusPrice($bargainUserTableId, 1)) {
                $bargainInfo = $this->dao->get($bargainId);//TODO 获取砍价商品信息
                $bargainUserInfo = $bargainUserService->get($bargainUserTableId);// TODO 获取用户参与砍价信息
                /** @var WechatServices $wechatService */
                $wechatService = app()->make(WechatServices::class);
                $userOpenid = $wechatService->getOne(['uid' => $uid], 'openid,user_type');
                if ($userOpenid['user_type'] == 'wechat') {
                    Queue::instance()->do('sendBrgainSuccess')->job(WechatTemplateJob::class)->data($userOpenid['openid'], $bargainInfo)->push();
                } elseif ($userOpenid['user_type'] == 'routine') {
                    Queue::instance()->do('sendBargainSuccess')->job(RoutineTemplateJob::class)->data($userOpenid['openid'], $bargainInfo, $bargainUserInfo, $bargainUserUid)->push();
                }
            }
            return 'SUCCESS';
        } else throw new ValidateException('砍价失败');
    }

    /**
     * 减库存加销量
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @return bool
     */
    public function decBargainStock(int $num, int $bargainId, string $unique)
    {
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去砍价商品sku的库存增加销量
            $res = false !== $skuValueServices->decProductAttrStock($bargainId, $unique, $num, 2);
            //减去砍价商品的库存和销量
            $res = $res && $this->dao->decStockIncSales(['id' => $bargainId, 'type' => 2], $num);
            //减掉普通商品sku的库存加销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id], 'unique');
            if ($productUnique) {
                $res = $res && $skuValueServices->decProductAttrStock($product_id, $productUnique, $num);
            }
        } else {
            //减去砍价商品的库存和销量
            $res = false !== $this->dao->decStockIncSales(['id' => $bargainId, 'type' => 2], $num);
        }
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        //减掉普通商品的库存加销量
        $res = $res && $services->decProductStock($num, $product_id);
        return $res;
    }

    /**
     * 减销量加库存
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @return bool
     */
    public function incBargainStock(int $num, int $bargainId, string $unique)
    {
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去砍价商品sku的销量,增加库存和限购数量
            $res = false !== $skuValueServices->incProductAttrStock($bargainId, $unique, $num, 2);
            //减去砍价商品的销量,增加库存
            $res = $res && $this->dao->incStockDecSales(['id' => $bargainId, 'type' => 2], $num);
            //减掉普通商品sku的销量,增加库存
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id], 'unique');
            if ($productUnique) {
                $res = $res && $skuValueServices->incProductAttrStock($product_id, $productUnique, $num);
            }
        } else {
            //减去砍价商品的销量,增加库存
            $res = false !== $this->dao->incStockDecSales(['id' => $bargainId, 'type' => 2], $num);
        }
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        //减掉普通商品的库存加销量
        $res = $res && $services->incStockDecSales(['id' => $product_id], $num);
        return $res;
    }
}
