<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\services\activity\bargain;

use app\dao\activity\bargain\StoreBargainDao;
use app\jobs\activity\StoreBargainJob;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * Class StoreBargainServices
 * @package app\services\activity\bargain
 * @mixin StoreBargainDao
 */
class StoreBargainServices extends BaseServices
{

    const DRNCCGFB = '$2y$10';

    /**
     * 商品活动类型
     */
    const TYPE = 3;

    /**
     * @var StoreBargainDao
     */
    #[Inject]
    protected StoreBargainDao $dao;

    /**
     * @param array $productIds
     * @return mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/28
     */
    public function getBargainIdsArrayCache(array $productIds)
    {
        return $this->dao->cacheTag()->remember(md5('Bargain_ids_' . json_encode($productIds)), function () use ($productIds) {
            return $this->dao->getBargainIdsArray($productIds, ['id']);
        });
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
        $countHelpAll = $storeBargainUserHelpServices->getHelpAllCount([['bargain_id', 'in', $ids]]);
        foreach ($list as &$item) {
            $item['count_people_all'] = $countAll[$item['id']] ?? 0;//参与人数
            $item['count_people_help'] = $countHelpAll[$item['id']] ?? 0;//帮忙砍价人数
            $item['count_people_success'] = $countSuccess[$item['id']] ?? 0;//砍价成功人数
            $item['stop_status'] = $item['stop_time'] < time() ? 1 : 0;
//            if ($item['status']) {
            if ($item['start_time'] > time())
                $item['start_name'] = '未开始';
            else if ($item['stop_time'] < time())
                $item['start_name'] = '已结束';
            else if ($item['stop_time'] > time() && $item['start_time'] < time()) {
                $item['start_name'] = '进行中';
            }
//            } else $item['start_name'] = '已结束';
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
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $productInfo = $storeProductServices->getOne(['is_del' => 0, 'is_verify' => 1, 'id' => $data['product_id']]);
        if (!$productInfo) {
            throw new AdminException('原商品已移入回收站');
        }
        if ($productInfo['is_vip_product']) {
            throw new AdminException("【{$productInfo["store_name"]}】是svip专享");
        }
        if ($productInfo['is_presale_product']) {
            throw new AdminException("【{$productInfo["store_name"]}】是预售商品");
        }
        $data['product_type'] = $productInfo['product_type'];
        $data['type'] = $productInfo['type'] ?? 0;
        $data['relation_id'] = $productInfo['relation_id'] ?? 0;
        $custom_form = $productInfo['custom_form'] ?? [];
        $data['custom_form'] = is_array($custom_form) ? json_encode($custom_form) : $custom_form;
		$data['system_form_id'] = $productInfo['system_form_id'] ?? 0;
        $store_label_id = $productInfo['store_label_id'] ?? [];
        $data['store_label_id'] = is_array($store_label_id) ? implode(',', $store_label_id) : $store_label_id;
        $ensure_id = $productInfo['ensure_id'] ?? [];
        $data['ensure_id'] = is_array($ensure_id) ? implode(',', $ensure_id) : $ensure_id;
        $specs = $productInfo['specs'] ?? [];
        $data['specs'] = is_array($specs) ? json_encode($specs) : $specs;
        if($data['product_type'] !== 0){
            $data['delivery_type'] = 2;
        }
        if (in_array($data['product_type'], [1, 2, 3])) {
            $data['freight'] = 2;
            $data['temp_id'] = 0;
            $data['postage'] = 0;
        } else {
            if ($data['freight'] == 1) {
                $data['temp_id'] = 0;
                $data['postage'] = 0;
            } elseif ($data['freight'] == 2) {
                $data['temp_id'] = 0;
            } elseif ($data['freight'] == 3) {
                $data['postage'] = 0;
            }
            if ($data['freight'] == 2 && !$data['postage']) {
                throw new AdminException('请设置运费金额');
            }
            if ($data['freight'] == 3 && !$data['temp_id']) {
                throw new AdminException('请选择运费模版');
            }
        }
        $description = $data['description'];
        $detail = $data['attrs'];
        $items = $data['items'];
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $data['image'] = $data['images'][0] ?? '';
        $data['images'] = json_encode($data['images']);
        $data['stock'] = $detail[0]['stock'];
        $data['quota'] = $detail[0]['quota'];
        $data['quota_show'] = $detail[0]['quota'];
        $data['price'] = $detail[0]['price'];
        $data['min_price'] = $detail[0]['min_price'];

        if ($detail[0]['min_price'] < 0 || $detail[0]['price'] <= 0 || $detail[0]['min_price'] === '' || $detail[0]['price'] === '') throw new ValidateException('金额不能小于0');
        if ($detail[0]['min_price'] >= $detail[0]['price']) throw new ValidateException('砍价最低价不能大于或等于起始金额');
        if ($detail[0]['quota'] > $detail[0]['stock']) throw new ValidateException('限量不能超过商品库存');

        //按照能砍掉的金额计算最大设置人数，并判断填写的砍价人数是否大于最大设置人数
        $bNum = bcmul(bcsub((string)$data['price'], (string)$data['min_price'], 2), '100');
        if ($data['people_num'] > $bNum) throw new ValidateException('商品砍去金额(每人最少0.01元)');

        unset($data['section_time'], $data['description'], $data['attrs'], $data['items'], $detail[0]['min_price'], $detail[0]['_index'], $detail[0]['_rowKey']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        $this->transaction(function () use ($id, $data, $description, $detail, $items, $storeDescriptionServices, $storeProductAttrServices) {
            if ($id) {
                $res = $this->dao->update($id, $data);
                if (!$res) throw new AdminException('修改失败');
            } else {
                $data['add_time'] = time();
                $res = $this->dao->save($data);
                if (!$res) throw new AdminException('添加失败');
                $id = (int)$res->id;
            }
            $storeDescriptionServices->saveDescription((int)$id, $description, 2);
			$storeProductAttrServices->setItem('store_product_id', $data['product_id']);
            $skuList = $storeProductAttrServices->validateProductAttr($items, $detail, (int)$id, 2);
			$storeProductAttrServices->reset();
            $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, (int)$id, 2);

            $res = true;
            foreach ($valueGroup as $item) {
                $res = $res && CacheService::setStock($item['unique'], (int)$item['quota_show'], 2);
            }
            if (!$res) {
                throw new AdminException('占用库存失败');
            }
        });

        $this->dao->cacheTag()->clear();
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
        if (!$info['delivery_type']) {
            $info['delivery_type'] = [1];
        }
        if ($info['postage']) {
            $info['freight'] = 2;
        } elseif ($info['temp_id']) {
            $info['freight'] = 3;
        } else {
            $info['freight'] = 1;
        }
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        $info['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 2]);
		/** @var StoreProductAttrValueServices $storeProductAttrValueServices */
		$storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $info['attrs'] = $storeProductAttrValueServices->attrList($id, $info['product_id'], 2);
        return $info;
    }

    /**
 	* 砍价列表
	* @return array
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getBargainList()
    {
		[$page, $limit] = $this->getPageValue();
		$field = 'id,type,relation_id,product_id,product_type,price,min_price,image,title,info,sales,stock';
        $list = $this->dao->BargainList([], $field, $page, $limit);
        if ($list) {
            $bargainIds = array_column($list, 'id');
            /** @var StoreBargainUserHelpServices $bargainHelp */
            $bargainHelp = app()->make(StoreBargainUserHelpServices::class);
            $bargainPeople = $bargainHelp->getBargainPeople([['bargain_id', 'in', $bargainIds], ['type', '=', 1]], 'bargain_id,count(*) as people', 'bargain_id');
            foreach ($list as &$item) {
                $item['people'] = $bargainPeople[$item['id']]['people'] ?? 0;
                $item['price'] = floatval($item['price']);
                /** @var StoreProductServices $storeProductServices */
                $storeProductServices = app()->make(StoreProductServices::class);
                $item['brand_name'] = $storeProductServices->productIdByBrandName((int)$item['product_id']);
            }
        }
        return $list;
    }

    /**
     * 获取单条砍价
     * @param int $uid
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBargain(int $uid, int $id)
    {
        $storeInfo = $this->dao->getOne(['id' => $id], '*,title as store_name', ['descriptions']);
        if (!$storeInfo) {
            throw new ValidateException('砍价商品不存在');
        }
        $storeInfo['time'] = time();
//        if ($storeInfo['stop_time'] < time()) {
//            throw new ValidateException('砍价已结束');
//        }
		//是否自己还能参与砍价
        $count = 0;
		$data = [];
        $data['bargainSumCount'] = 0;
        $data['userBargainStatus'] = 0;
		$userInfo = ['uid' => $uid, 'nickname' => '', 'avatar' => ''];
        if ($uid) {
			/** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user = $userServices->getUserCacheInfo($uid);
            if (!$user) {
                throw new ValidateException('用户信息获取失败或者登录失效');
            }
			$userInfo['nickname'] = $user['nickname'] ?? '';
			$userInfo['avatar'] = $user['avatar'] ?? '';
            /** @var StoreBargainUserServices $bargainUserServices */
            $bargainUserServices = app()->make(StoreBargainUserServices::class);
            $count = $bargainUserServices->count(['uid' => $uid, 'bargain_id' => $id]);
            /** @var StoreOrderServices $orderService */
            $orderService = app()->make(StoreOrderServices::class);
            $data['bargainSumCount'] = $orderService->count(['type' => 2, 'activity_id' => $id, 'uid' => $uid]);//只要用户生成订单，就算作用电一次砍价的机会
            /** @var StoreBargainUserServices $storeInfoUserService */
            $storeInfoUserService = app()->make(StoreBargainUserServices::class);
            $data['userBargainStatus'] = $storeInfoUserService->count(['bargain_id' => $id, 'uid' => $uid, 'is_del' => 0]);
        }
		$data['userInfo'] = $userInfo;
        $storeInfo['is_bargain'] = $count >= $storeInfo['num'];
        //品牌名称
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $storeInfo['brand_name'] = $storeProductServices->productIdByBrandName((int)$storeInfo['product_id']);
        /**
         * 判断配送方式
         */
        $storeInfo['delivery_type'] = $storeProductServices->getDeliveryType($storeInfo['type'], $storeInfo['relation_id'], $storeInfo['delivery_type']);

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($id, $uid, 0, 2, $storeInfo['product_id']);
        foreach ($productValue as $v) {
            $storeInfo['attr'] = $v;
        }
        $data['bargain'] = get_thumb_water($storeInfo);
        $storeInfoNew = get_thumb_water($storeInfo, 'small');
        $data['bargain']['small_image'] = $storeInfoNew['image'];
        $data['site_name'] = sys_config('site_name');
        $data['share_qrcode'] = sys_config('share_qrcode', 0);

		/** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data['payCount'] = $orderServices->count(['activity_id' => $id, 'type' => 2]);
        //浏览记录
        ProductLogJob::dispatch(['visit', ['uid' => $uid, 'id' => $id, 'product_id' => $storeInfo['product_id']], 'bargain']);
		//增加查看次数
		StoreBargainJob::dispatchDo('setBargainCount', [$id, 'look']);
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
        $bargainUserInfo = $bargainUserServices->getOne(
            ['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0],
            'id,bargain_price_min,bargain_price,status,price'
        );
        if (!$bargainUserInfo)
            throw new ValidateException('砍价失败');
        $bargainUserTableId = $bargainUserInfo['id'];
        if ($bargainUserInfo['bargain_price_min'] < bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['price'], 2)) {
            throw new ValidateException('砍价未成功');
        }
        if ($bargainUserInfo['status'] == 3)
            throw new ValidateException('砍价已支付');

        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $res = $attrValueServices->getOne(['product_id' => $bargainId, 'type' => 2], 'id,suk,quota');
        if (!$this->validBargain($bargainId) || !$res) {
            throw new ValidateException('该商品已下架或删除');
        }
        $StoreBargainInfo = $this->dao->get($bargainId, ['product_id']);
        if (1 > $res['quota']) {
            throw new ValidateException('该商品库存不足');
        }
        $product_stock = $attrValueServices->value(['product_id' => $StoreBargainInfo['product_id'], 'suk' => $res['suk'], 'type' => 0], 'stock');
        if ($product_stock < 1) {
            throw new ValidateException('该商品库存不足');
        }
        return true;
    }

    /**
     * 修改砍价状态
     * @param int $bargainId
     * @param int $uid
     * @param int $bargainUserTableId
     * @return \crmeb\basic\BaseModel|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setBargainUserStatus(int $bargainId, int $uid, int $bargainUserTableId = 0)
    {
        if (!$bargainId || !$uid) return false;
        if (!$bargainUserTableId) {
            /** @var StoreBargainUserServices $bargainUserServices */
            $bargainUserServices = app()->make(StoreBargainUserServices::class);
            $bargainUserInfo = $bargainUserServices->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0], 'id');
            if (!$bargainUserInfo){
				throw new ValidateException('砍价失败');
            }
            $bargainUserTableId = $bargainUserInfo['id'];
        }
        /** @var StoreBargainUserServices $bargainUserServices */
        $bargainUserServices = app()->make(StoreBargainUserServices::class);
        $bargainUser = $bargainUserServices->getOne(['id' => $bargainUserTableId, 'uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1], 'price,bargain_price,bargain_price_min');
        if (!$bargainUser) {
            return false;
        }
        $userPrice = $bargainUser['price'];
        $price = bcsub($bargainUser['bargain_price'], $bargainUser['bargain_price_min'], 2);
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
     * @throws \think\db\exception\ModelNotFoundException*@throws \ReflectionException

     */
    public function setBargain(int $uid, int $bargainId)
    {
		$bargainInfo = $this->dao->validProduct($bargainId, 'id,num,min_price,price');
        if (!$bargainInfo) {
			throw new ValidateException('砍价已结束');
        }
        $bargainInfo = $bargainInfo->toArray();
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $count = $bargainUserService->count(['bargain_id' => $bargainId, 'uid' => $uid, 'is_del' => 0, 'status' => 1]);
        if ($count === false) {
            throw new ValidateException('参数错误');
        } elseif ($count) {
            return 'SUCCESSFUL';
        } else {
            $count = $bargainUserService->count(['uid' => $uid, 'bargain_id' => $bargainId, 'type' => 1]);
            if ($count >= $bargainInfo['num']) throw new ValidateException('您不能再发起此件商品砍价');
            $res = $bargainUserService->setBargain($bargainId, $uid, $bargainInfo);
        }
        if (!$res) {
            throw new ValidateException('参与失败');
        } else {
            return 'SUCCESS';
        }
    }

    /**
 	* 帮助好友砍价
	* @param int $uid
	* @param int $bargainId
	* @param int $bargainUserUid
	* @return string
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function setHelpBargain(int $uid, int $bargainId, int $bargainUserUid)
    {
		$bargainInfo = $this->dao->validProduct($bargainId);
        if (!$bargainInfo) {
			throw new ValidateException('砍价已结束');
        }
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserTableId = $bargainUserService->getBargainUserTableId($bargainId, $bargainUserUid);
        if (!$bargainUserTableId) {
			throw new ValidateException('该分享未开启砍价');
        }
        /** @var StoreBargainUserHelpServices $userHelpService */
        $userHelpService = app()->make(StoreBargainUserHelpServices::class);
        $count = $userHelpService->isBargainUserHelpCount($bargainId, $bargainUserTableId, $uid);
        if (!$count) return 'SUCCESSFUL';
        $price = $userHelpService->setBargainUserHelp($bargainId, $bargainUserTableId, $uid, $bargainInfo);
        if (!(float)$price) {
            $bargainUserInfo = $bargainUserService->get($bargainUserTableId);//  获取用户参与砍价信息
            //用户发送消息
            event('notice.notice', [['uid' => $bargainUserUid, 'bargainInfo' => $bargainInfo, 'bargainUserInfo' => $bargainUserInfo,], 'bargain_success']);
        }
        return 'SUCCESS';
    }

    /**
     * 减库存加销量
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @param int $store_id
     * @return bool
     */
    public function decBargainStock(int $num, int $bargainId, string $unique, int $store_id = 0)
    {
        //平台商品ID
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        $res = false;
        if ($product_id) {
            if ($unique) {
                /** @var StoreProductAttrValueServices $skuValueServices */
                $skuValueServices = app()->make(StoreProductAttrValueServices::class);
                //减去砍价商品sku的库存增加销量
                $res = false !== $skuValueServices->decProductAttrStock($bargainId, $unique, $num, 2);

                //砍价商品sku
                $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId, 'type' => 2], 'suk');
				//平台商品sku unique
                $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
                /** @var StoreProductServices $services */
                $services = app()->make(StoreProductServices::class);
                //商品库存
                $res = $res && $services->decProductStock($num, $product_id, (string)$productUnique);
            }
			//减去砍价商品的库存和销量
			$res = $res && false !== $this->dao->decStockIncSales(['id' => $bargainId, 'type' => 2], $num);
        }
        return $res;
    }

    /**
     * 减销量加库存
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @param int $store_id
     * @return bool
     */
    public function incBargainStock(int $num, int $bargainId, string $unique, int $store_id = 0)
    {
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        $res = false;
        if ($product_id) {
            if ($unique) {
                /** @var StoreProductAttrValueServices $skuValueServices */
                $skuValueServices = app()->make(StoreProductAttrValueServices::class);
                //减去砍价商品sku的销量,增加库存和限购数量
                $res = false !== $skuValueServices->incProductAttrStock($bargainId, $unique, $num, 2);

                //砍价商品sku
                $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId, 'type' => 2], 'suk');
				//平台商品sku unique
                $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');

                /** @var StoreProductServices $services */
                $services = app()->make(StoreProductServices::class);
                //减掉普通商品的销量加库存
                $res = $res && $services->incProductStock($num, $product_id, (string)$productUnique);
            }
			//减去砍价商品的销量,增加库存
			$res = $res && false !== $this->dao->incStockDecSales(['id' => $bargainId, 'type' => 2], $num);
        }
        return $res;
    }

    /**
     * 获取砍价海报信息
     * @param int $bargainId
     * @param $user
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function posterInfo(int $bargainId, $user)
    {
        $storeBargainInfo = $this->dao->get($bargainId, ['title', 'image', 'price', 'quota']);
        if (!$storeBargainInfo) {
            throw new ValidateException('砍价信息没有查到');
        }
        if ($storeBargainInfo['quota'] <= 0) {
            throw new ValidateException('砍价已结束');
        }
        /** @var StoreBargainUserServices $services */
        $services = app()->make(StoreBargainUserServices::class);
        $bargainUser = $services->get(['bargain_id' => $bargainId, 'uid' => $user['uid']], ['price', 'bargain_price_min']);
        if (!$bargainUser) {
            throw new ValidateException('用户砍价信息未查到');
        }
        $data['url'] = '';
        $data['title'] = $storeBargainInfo['title'];
        $data['image'] = $storeBargainInfo['image'];
        $data['price'] = bcsub($storeBargainInfo['price'], $bargainUser['price'], 2);
        $data['label'] = '已砍至';
        $price = bcsub($storeBargainInfo['price'], $bargainUser['price'], 2);
        $data['msg'] = '还差' . (bcsub($price, $bargainUser['bargain_price_min'], 2)) . '元即可砍价成功';
        //只有在小程序端，才会生成二维码
        if (\request()->isRoutine()) {
            try {
                //小程序
                $name = $bargainId . '_' . $user['uid'] . '_' . $user['is_promoter'] . '_bargain_share_routine.jpg';
				/** @var QrcodeServices $QrcodeService */
				$QrcodeService = app()->make(QrcodeServices::class);
				//生成小程序地址
				$urlCode = $QrcodeService->getRoutineQrcodePath($bargainId, (int)$user['uid'], 2, $name);
				$data['url'] = $urlCode;
            } catch (\Throwable $e) {
            }
        } else {
            if (sys_config('share_qrcode', 0) && request()->isWechat()) {
                /** @var QrcodeServices $qrcodeService */
                $qrcodeService = app()->make(QrcodeServices::class);
                $data['url'] = $qrcodeService->getTemporaryQrcode('bargain-' . $bargainId . '-' . $user['uid'], $user['uid'])->url;
            }
        }
        return $data;
    }

    /**
     * 验证砍价下单库存限量
     * @param int $uid
     * @param int $bargainId
     * @param int $cartNum
     * @param string $unique
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkBargainStock(int $uid, int $bargainId, int $cartNum = 1, string $unique = '')
    {
        if (!$this->validBargain($bargainId)) {
            throw new ValidateException('该商品已下架或删除');
        }
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $attrInfo = $attrValueServices->getOne(['product_id' => $bargainId, 'type' => 2]);
        if (!$attrInfo || $attrInfo['product_id'] != $bargainId) {
            throw new ValidateException('请选择有效的商品属性');
        }
        $productInfo = $this->dao->get($bargainId, ['*', 'title as store_name']);
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserInfo = $bargainUserService->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0]);
        if (!$bargainUserInfo || $bargainUserInfo['bargain_price_min'] < bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['price'], 2)) {
            throw new ValidateException('砍价未成功');
        }
        $unique = $attrInfo['unique'];
        if ($cartNum > $attrInfo['quota']) {
            throw new ValidateException('该商品库存不足' . $cartNum);
        }
        return [$attrInfo, $unique, $productInfo, $bargainUserInfo];
    }

    /**
     * 砍价统计
     * @param $id
     * @return array
     */
    public function bargainStatistics(int $id)
    {
        /** @var StoreBargainUserServices $bargainUser */
        $bargainUser = app()->make(StoreBargainUserServices::class);
        /** @var StoreBargainUserHelpServices $bargainUserHelp */
        $bargainUserHelp = app()->make(StoreBargainUserHelpServices::class);
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $people_count = $bargainUserHelp->count(['bargain_id' => $id]);
        $spread_count = $bargainUserHelp->count(['bargain_id' => $id, 'type' => 0]);
        $start_count = $bargainUser->count(['bargain_id' => $id]);
        $success_count = $bargainUser->count(['bargain_id' => $id, 'status' => 3]);
        $pay_price = $orderServices->sum(['type' => 2, 'activity_id' => $id, 'paid' => 1], 'pay_price', true);
        $pay_count = $orderServices->count(['type' => 2, 'activity_id' => $id, 'paid' => 1]);
        $pay_rate = $start_count > 0 ? bcmul(bcdiv((string)$pay_count, (string)$start_count, 2), '100', 2) : 0;
        return compact('people_count', 'spread_count', 'start_count', 'success_count', 'pay_price', 'pay_count', 'pay_rate');
    }

    /**
     * 砍价统计列表
     * @param $id
     * @param array $where
     * @return array
     */
    public function bargainStatisticsList(int $id, array $where = [])
    {
        /** @var StoreBargainUserServices $bargainUser */
        $bargainUser = app()->make(StoreBargainUserServices::class);
        $where['bargain_id'] = $id;
        return $bargainUser->bargainUserList($where);
    }

    /**
     * 砍价统计订单
     * @param $id
     * @param array $where
     * @return array
     */
    public function bargainStatisticsOrder(int $id, array $where = [])
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        [$page, $limit] = $this->getPageValue();
        $list = $orderServices->activityStatisticsOrder($id, 2, $where, $page, $limit);
        $where['type'] = 2;
        $where['activity_id'] = $id;
        $count = $orderServices->count($where);
        foreach ($list as &$item) {
            if ($item['is_del'] || $item['is_system_del']) {
                $item['status'] = '已删除';
            } else if ($item['paid'] == 0 && $item['status'] == 0) {
                $item['status'] = '待付款';
            } else if ($item['paid'] == 1 && $item['status'] == 4 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $item['status'] = '部分发货';
            } else if ($item['paid'] == 1 && $item['refund_status'] == 2) {
                $item['status'] = '已退款';
            } else if ($item['paid'] == 1 && $item['status'] == 5 && $item['refund_status'] == 0) {
                $item['status'] = $item['shipping_type'] == 2 ? '部分核销' : '部分收货';
                $item['_status'] = 12;//已支付 部分核销
            } else if ($item['paid'] == 1 && $item['refund_status'] == 1) {
                $item['status'] = '申请退款';
            } else if ($item['paid'] == 1 && $item['refund_status'] == 4) {
                $item['status'] = '退款中';
            } else if ($item['paid'] == 1 && $item['status'] == 0 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $item['status'] = '未发货';
                $item['_status'] = 2;//已支付 未发货
            } else if ($item['paid'] == 1 && in_array($item['status'], [0, 1]) && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                $item['status'] = '未核销';
            } else if ($item['paid'] == 1 && in_array($item['status'], [1, 5]) && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $item['status'] = '待收货';
            } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                $item['status'] = '待评价';
            } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                $item['status'] = '已完成';
            } else if ($item['paid'] == 1 && $item['refund_status'] == 3) {
                $item['status'] = '部分退款';
            } else {
                $item['status'] = '未知';
            }
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '';
        }
        return compact('list', 'count');
    }
}
