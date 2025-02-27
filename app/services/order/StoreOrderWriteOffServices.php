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

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\dao\order\StoreOrderWriteoffDao;
use app\services\message\service\StoreServiceServices;
use app\services\pay\PayServices;
use app\services\store\DeliveryServiceServices;
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\activity\integral\StoreIntegralOrderStatusServices;
use app\services\activity\combination\StorePinkServices;
use app\services\BaseServices;
use app\services\store\SystemStoreStaffServices;
use app\services\supplier\SystemSupplierServices;
use app\services\system\admin\SystemAdminServices;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 核销订单
 * Class StoreOrderWriteOffServices
 * @package app\sservices\order
 * @mixin StoreOrderDao
 */
class StoreOrderWriteOffServices extends BaseServices
{
    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * @var StoreOrderWriteoffDao
     */
    #[Inject]
    protected StoreOrderWriteoffDao $writeoffdao;

    /**
     * 订单核销
     * @param string $code
     * @param int $confirm
     * @param int $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOffOrder(string $code, int $confirm, int $uid = 0)
    {
        //订单
        $orderInfo = $this->dao->getOne(['verify_code' => $code, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0], '*', ['pink']);
        $order_type = 'order';
        if (!$orderInfo) {
            //积分兑换订单
            /** @var StoreIntegralOrderServices $storeIntegralOrderServices */
            $storeIntegralOrderServices = app()->make(StoreIntegralOrderServices::class);
            $orderInfo = $storeIntegralOrderServices->getOne(['verify_code' => $code]);
            $order_type = 'integral';
        }
        if (!$orderInfo) {
            throw new ValidateException('Write off order does not exist');
        }
        if (!$orderInfo['verify_code'] || ($orderInfo->shipping_type != 2 && $orderInfo->delivery_type != 'send')) {
            throw new ValidateException('此订单不能被核销');
        }
        if ($uid) {
            $isAuth = true;
            switch ($orderInfo['shipping_type']) {
                case 1://配送订单
                    /** @var DeliveryServiceServices $deliverServiceServices */
                    $deliverServiceServices = app()->make(DeliveryServiceServices::class);
                    $isAuth = $deliverServiceServices->getCount(['uid' => $uid, 'status' => 1]) > 0;
                    break;
                case 2://自提订单
                    /** @var SystemStoreStaffServices $storeStaffServices */
                    $storeStaffServices = app()->make(SystemStoreStaffServices::class);
                    $isAuth = $storeStaffServices->getCount(['uid' => $uid, 'verify_status' => 1, 'status' => 1]) > 0;
                    break;
            }
            if (!$isAuth) {
                throw new ValidateException('您无权限核销此订单，请联系管理员');
            }
        }
        $orderInfo['order_type'] = $order_type;
        if ($order_type == 'order') {
            if ($orderInfo->status == 2) {
                throw new ValidateException('订单已核销');
            }
            if (isset($orderInfo['pinkStatus']) && $orderInfo['pinkStatus'] != 2) {
                throw new ValidateException('拼团未完成暂不能发货!');
            }
            /** @var StoreOrderCartInfoServices $orderCartInfo */
            $orderCartInfo = app()->make(StoreOrderCartInfoServices::class);
            $cartInfo = $orderCartInfo->getOne([
                ['cart_id', '=', $orderInfo['cart_id'][0]]
            ], 'cart_info');
            if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'];
            if ($orderInfo->shipping_type == 2) {
                if ($orderInfo->status > 0) {
                    throw new ValidateException('Order written off');
                }
            }
            if ($orderInfo['type'] == 3 && $orderInfo['activity_id'] && $orderInfo['pink_id']) {
                /** @var StorePinkServices $services */
                $services = app()->make(StorePinkServices::class);
                $res = $services->getCount([['id', '=', $orderInfo->pink_id], ['status', '<>', 2]]);
                if ($res) throw new ValidateException('Failed to write off the group order');
            }
            if ($confirm == 0) {
                /** @var UserServices $services */
                $services = app()->make(UserServices::class);
                $orderInfo['nickname'] = $services->value(['uid' => $orderInfo['uid']], 'nickname');
                return $orderInfo->toArray();
            }
            $orderInfo->status = 2;
            if ($uid) {
                if ($orderInfo->shipping_type == 2) {
                    $orderInfo->clerk_id = $uid;
                }
            }
            if ($orderInfo->save()) {
                /** @var StoreOrderTakeServices $storeOrdeTask */
                $storeOrdeTask = app()->make(StoreOrderTakeServices::class);
                $re = $storeOrdeTask->storeProductOrderUserTakeDelivery($orderInfo);
                if (!$re) {
                    throw new ValidateException('Write off failure');
                }
                //修改订单商品信息
                $cartData = ['writeoff_time' => time()];
                $cartData['is_writeoff'] = 1;
                $cartData['surplus_num'] = 0;
                $orderCartInfo->update(['oid' => $orderInfo['id']], $cartData);
                return $orderInfo->toArray();
            } else {
                throw new ValidateException('Write off failure');
            }
        } else {
            if ($orderInfo['status'] == 3) {
                throw new ValidateException('订单已核销');
            }
            if ($confirm == 0) {
                /** @var UserServices $services */
                $services = app()->make(UserServices::class);
                $orderInfo['nickname'] = $services->value(['uid' => $orderInfo['uid']], 'nickname');
                return $orderInfo->toArray();
            }
            if (!$storeIntegralOrderServices->update($orderInfo['id'], ['status' => 3])) {
                throw new ValidateException('Write off failure');
            } else {
                //增加收货订单状态
                /** @var StoreIntegralOrderStatusServices $statusService */
                $statusService = app()->make(StoreIntegralOrderStatusServices::class);
                $statusService->save([
                    'oid' => $orderInfo['id'],
                    'change_type' => 'take_delivery',
                    'change_message' => '已收货',
                    'change_time' => time()
                ]);
            }
            return $orderInfo->toArray();
        }
    }

    /**
     * 获取核销列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOffList($where)
    {
        [$page, $limit] = $this->getPageValue();

        $list = $this->writeoffdao->getList($where, '*', $page, $limit, [
            'userInfo',
            'staffInfo',
            'orderInfo' => function ($query) {
                $query->field('id,order_id,pay_type');
            },
            'cartInfo' => function ($query) {
                $query->field('id,cart_info');
            },
        ]);
        $count = $this->writeoffdao->count($where);
        if ($list) {
            $supplierIds = [];
            foreach ($list as $value) {
                switch ($value['type']) {
                    case 0:
                        break;
                    case 2://供应商
                        $supplierIds[] = $value['relation_id'];
                        break;
                }
            }
            $supplierIds = array_unique($supplierIds);
            $supplierList = [];
            if ($supplierIds) {
                /** @var SystemSupplierServices $supplierServices */
                $supplierServices = app()->make(SystemSupplierServices::class);
                $supplierList = $supplierServices->getColumn([['id', 'in', $supplierIds], ['is_del', '=', 0]], 'id,supplier_name', 'id');
            }
            foreach ($list as &$item) {
                $cartInfo = $item['cartInfo'] ?? [];
                $cartInfo = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
                $item['productInfo'] = $cartInfo['productInfo'] ?? [];
                $orderInfo = $item['orderInfo'] ?? [];
                $item['order_id'] = $orderInfo['order_id'] ?? '';
                $item['pay_type'] = PayServices::PAY_TYPE[$item['pay_type']] ?? '其他方式';
                $item['plate_name'] = '平台';
                switch ($item['type']) {
                    case 0:
                        $item['plate_name'] = '平台';
                        break;
                    case 2://供应商
                        $item['plate_name'] = '供应商：' . ($supplierList[$item['relation_id']]['supplier_name'] ?? '');
                        break;
                }
            }
        }
        return compact('list', 'count');
    }

    /**
     * 保存核销记录
     * @param int $oid
     * @param array $cartIds
     * @param array $data
     * @param array $orderInfo
     * @param array $cartInfo
     * @return bool
     */
    public function saveWriteOff(int $oid, array $cartIds = [], array $data = [], array $orderInfo = [], array $cartInfo = [], int $admin_id = 0)
    {
        if (!$oid) {
            throw new ValidateException('缺少核销订单信息');
        }
        if (!$orderInfo) {
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            $orderInfo = $storeOrderServices->get($oid);
        }
        if (!$orderInfo) {
            throw new ValidateException('核销订单不存在');
        }
        $orderInfo = is_object($orderInfo) ? $orderInfo->toArray() : $orderInfo;
        if (!$cartInfo) {
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            if ($cartIds) {//商城存在部分核销
                $ids = array_unique(array_column($cartIds, 'cart_id'));
                $cartIds = array_combine($ids, $cartIds);
                //订单下原商品信息
                $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id'], 'cart_id' => $ids], '*', 'cart_id');
            } else {//整单核销
                $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id']], '*', 'cart_id');
            }
        }

        $writeOffDataAll = [];
        $writeOffData = ['uid' => $orderInfo['uid'], 'oid' => $oid, 'writeoff_code' => $orderInfo['verify_code'], 'add_time' => time()];
        foreach ($cartInfo as $cart) {
            $write = $cartIds[$cart['cart_id']] ?? [];
            $info = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
            if (!$cartIds || $write) {
                $writeOffData['order_cart_id'] = $cart['id'];
                $writeOffData['writeoff_num'] = $write['cart_num'] ?? $cart['cart_num'];
                $writeOffData['type'] = $cart['type'];
                $writeOffData['relation_id'] = $cart['relation_id'];
                $writeOffData['product_id'] = $cart['product_id'];
                $writeOffData['product_type'] = $cart['product_type'];
                $writeOffData['writeoff_price'] = (float)bcmul((string)$info['truePrice'], (string)$writeOffData['writeoff_num'], 2);
                $writeOffData['staff_id'] = $data['staff_id'] ?? 0;
                if ($admin_id) {
                    $writeOffData['is_admin'] = 1;
                    $writeOffData['admin_id'] = $admin_id;
                }
                $writeOffDataAll[] = $writeOffData;
            }
        }
        if ($writeOffDataAll) {
            $this->writeoffdao->saveAll($writeOffDataAll);
        }
        return true;
    }

    /**
     * 核销记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userOrderWriteOffRecords(array $where = [], int $product_type = 0)
    {
        [$page, $limit] = $this->getPageValue();
        $count = 0;
        $times = [];
        if ($product_type == 4) {
            $list = $this->writeoffdao->getList($where + ['product_type' => 4], '*', $page, $limit);
            if ($list) {
                foreach ($list as &$item) {
                    $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
                }
            }
        } else {
            $list = $this->writeoffdao->getList($where, '*', $page, $limit, ['cartInfo']);
            $count = $this->writeoffdao->count($where);
            if ($list) {
                foreach ($list as &$item) {
                    $item['time_key'] = $item['time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
                    $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
                    $value = is_string($item['cartInfo']['cart_info']) ? json_decode($item['cartInfo']['cart_info'], true) : $item['cartInfo']['cart_info'];
                    $value['productInfo']['store_name'] = $value['productInfo']['store_name'] ?? "";
                    $value['productInfo']['store_name'] = substrUTf8($value['productInfo']['store_name'], 10, 'UTF-8', '');
                    $item['cartInfo'] = $value;
                }
                $times = array_merge(array_unique(array_column($list, 'time_key')));
            }
        }
        return ['count' => $count, 'list' => $list, 'time' => $times];
    }

    /**
     * 核销记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/9/4 16:51
     */
    public function getOrderWriteOffRecords(array $where = [])
    {
        $list = $this->writeoffdao->getList($where, '*', 0, 0, ['cartInfo']);
        if ($list) {
            $adminIds = array_unique(array_filter(array_column($list, 'admin_id')));
            $staffIds = array_unique(array_filter(array_column($list, 'staff_id')));
            $adminList = $staffList = [];
            if ($adminIds) {
                /** @var SystemAdminServices $adminServices */
                $adminServices = app()->make(SystemAdminServices::class);
                $adminList = $adminServices->getColumn(['id' => $adminIds], 'real_name', 'id',true);
            }
            if($staffIds){
                /** @var StoreServiceServices $storeServiceServices */
               $storeServiceServices = app()->make(StoreServiceServices::class);
               $staffList = $storeServiceServices->getColumn(['id' => $staffIds], 'nickname', 'uid',true);
            }
            foreach ($list as &$item) {
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
                $value = is_string($item['cartInfo']['cart_info']) ? json_decode($item['cartInfo']['cart_info'], true) : $item['cartInfo']['cart_info'];
                $item['store_name'] = $value['productInfo']['store_name'] ?? "";
                $item['image'] = $value['productInfo']['image'] ?? "";
                $item['writeoff_name'] = $item['is_admin'] == 1 ? ($adminList[$item['admin_id']] ?? '') : ($staffList[$item['staff_id']] ?? '');
                unset($item['cartInfo']);
            }
        }
        return $list;
    }
}
