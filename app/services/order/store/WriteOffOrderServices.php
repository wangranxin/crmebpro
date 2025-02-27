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

namespace app\services\order\store;


use app\dao\order\StoreOrderDao;
use app\services\message\service\StoreServiceServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderTakeServices;
use app\services\activity\combination\StorePinkServices;
use app\services\BaseServices;
use app\services\store\SystemStoreStaffServices;
use app\services\store\DeliveryServiceServices;
use app\services\user\UserServices;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 核销订单
 * Class StoreOrderWriteOffServices
 * @package app\sservices\order
 * @mixin StoreOrderDao
 */
class WriteOffOrderServices extends BaseServices
{
    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * 验证核销订单权限
     * @param int $uid
     * @param array $orderInfo
     * @param int $auth
     * @param int $staff_id
     * @return bool
     */
    public function checkAuth(int $uid, array $orderInfo, int $auth = 1)
    {
        if (($auth > 0 && !$uid) || !$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $info = $this->checkUserAuth($uid, $auth);
        $isAuth = false;
        switch ($auth) {
            case 0://平台
                $isAuth = true;
                break;
            case 1://前端客服
                if (isset($info['user_service']) && $info['user_service']) {
                    $isAuth = true;
                }
                break;
            case 2://配送员
                if (in_array($orderInfo['shipping_type'], [1, 3]) && $info && $orderInfo['delivery_type'] == 'send' && $orderInfo['delivery_uid'] == $uid) {
                    $isAuth = true;
                }
                break;
        }
        if (!$isAuth) {
            throw new ValidateException('您无权限核销此订单，请联系管理员');
        }
        return true;
    }

    /**
     * 验证核销权限
     * @param int $uid
     * @param int $auth
     * @return array|\think\Model
     */
    public function checkUserAuth(int $uid, int $auth = 1)
    {
        if ($auth > 0 && !$uid) {
            throw new ValidateException('用户不存在');
        }
        $isAuth = false;
        $info = [];
        switch ($auth) {
            case 0://平台客服
                $isAuth = true;
                break;
            case 1://前端客服
                /** @var StoreServiceServices $storeService */
                $storeService = app()->make(StoreServiceServices::class);
                $userService = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'account_status' => 1, 'customer' => 1]);
                if ($userService) {
                    $isAuth = true;
                    $info['user_service'] = $userService;
                }
                break;
            case 2://配送员
                /** @var DeliveryServiceServices $deliverServiceServices */
                $deliverServiceServices = app()->make(DeliveryServiceServices::class);
                try {
                    $info = $deliverServiceServices->getDeliveryInfoByUid($uid, 0);
                } catch (\Throwable $e) {

                }
                if ($info) {
                    $isAuth = true;
                }
                break;
        }
        if (!$isAuth) {
            throw new ValidateException('您无权限核销，请联系超级管理员');
        }
        return $info;
    }

    /**
     * 用户码获取待核销订单列表
     * @param int $uid
     * @param string $code
     * @param int $auth
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userUnWriteoffOrder(int $uid, string $code, int $auth = 1, array $userInfo = [])
    {
        if (!$userInfo) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getOne(['bar_code' => $code]);
        }
        if (!$userInfo) {
            throw new ValidateException('该用户不存在');
        }
        $info = $this->checkUserAuth($uid, $auth);
        $where = [];
        if ($info && $auth > 0) {
            if ($auth == 1 && isset($info['user_service']) && $info['user_service']) {
                $where = ['delivery_uid' => 0];
            } elseif ($auth == 2) {
                $where = ['delivery_uid' => $info['uid']];
            }
            $unWriteoffOrder = $this->dao->getUnWirteOffList(['uid' => $userInfo['uid']] + $where, ['id']);
        } else {
            $unWriteoffOrder = $this->dao->getUnWirteOffList(['uid' => $userInfo['uid']], ['id']);
        }
        $data = [];
        if ($unWriteoffOrder) {
            foreach ($unWriteoffOrder as $item) {
                try {
                    $orderInfo = $this->writeoffOrderInfo($uid, '', $auth, $item['id']);
                } catch (\Throwable $e) {//无权限或其他异常不返回订单信息
                    $orderInfo = [];
                }
                if ($orderInfo) $data[] = $orderInfo;
            }
        }
        return $data;
    }

    /**
     * 获取核销订单信息
     * @param int $uid
     * @param string $code
     * @param int $auth
     * @param int $oid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeoffOrderInfo(int $uid, string $code = '', int $auth = 1, int $oid = 0, int $staff_id = 0)
    {
        if ($oid) {
            //订单
            $orderInfo = $this->dao->getOne(['id' => $oid, 'is_del' => 0], '*', ['user', 'pink']);
        } else {
            //订单
            $orderInfo = $this->dao->getOne(['verify_code' => $code, 'is_del' => 0], '*', ['user', 'pink']);
        }
        if (!$orderInfo) {
            throw new ValidateException('Write off order does not exist');
        }
        if (!$orderInfo['paid']) {
            throw new ValidateException('订单还未完成支付');
        }
        if ($orderInfo['refund_status'] != 0) {
            throw new ValidateException('该订单状态暂不支持核销');
        }
        $orderInfo = $orderInfo->toArray();
        //验证权限
        $this->checkAuth($uid, $orderInfo, $auth);
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartServices->getCartInfoList(['oid' => $orderInfo['id']], ['id', 'oid', 'write_times', 'write_surplus_times', 'write_start', 'write_end']);
        $orderInfo['write_off'] = $orderInfo['write_times'] = 0;
        $orderInfo['write_day'] = '';
        $cart = $cartInfo[0] ?? [];
        if ($orderInfo['product_type'] == 4 && $cart) {//次卡商品
            $orderInfo['write_off'] = max(bcsub((string)$cart['write_times'], (string)$cart['write_surplus_times'], 0), 0);
            $orderInfo['write_times'] = $cart['write_times'] ?? 0;
            $start = $cart['write_start'] ?? 0;
            $end = $cart['write_end'] ?? 0;
            if (!$start && !$end) {
                $orderInfo['write_day'] = '不限时';
            } else {
                $orderInfo['write_day'] = ($start ? date('Y-m-d', $start) : '') . '/' . ($end ? date('Y-m-d', $end) : '');
            }
        }
        return $orderInfo;
    }

    /**
     * 获取订单商品信息
     * @param int $uid
     * @param int $id
     * @param int $auth
     * @param int $staff_id
     * @param bool $isCasher
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderCartInfo(int $uid, int $id, int $auth = 1, int $staff_id = 0, bool $isCasher = false)
    {
        if ($isCasher) {//获取订单信息 暂时不验证权限
            $orderInfo = $this->dao->getOne(['id' => $id], '*', ['user', 'pink']);
            if (!$orderInfo) {
                throw new ValidateException('Write off order does not exist');
            }
            $orderInfo = $orderInfo->toArray();
        } else {
            $orderInfo = $this->writeoffOrderInfo($uid, '', $auth, $id, $staff_id);
        }
        $writeoff_count = 0;
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id']], 'id,cart_id,cart_num,surplus_num,is_writeoff,cart_info,product_type,is_support_refund,is_gift,write_times,write_surplus_times');
        foreach ($cartInfo as &$item) {
            $_info = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
            if (!isset($_info['productInfo'])) $_info['productInfo'] = [];
            //缩略图处理
            if (isset($_info['productInfo']['attrInfo'])) {
                $_info['productInfo']['attrInfo'] = get_thumb_water($_info['productInfo']['attrInfo']);
            }
            $_info['productInfo'] = get_thumb_water($_info['productInfo']);
            $item['cart_info'] = $_info;
            if ($item['write_times'] > $item['write_surplus_times']) {
                $writeoff_count = bcadd((string)$writeoff_count, (string)bcsub((string)$item['write_times'], (string)$item['write_surplus_times']));
            }
            $item['surplus_num'] = $item['write_surplus_times'];
            unset($_info);
        }
        $orderInfo['cart_count'] = count($cartInfo);
        $orderInfo['writeoff_count'] = $writeoff_count;
        $orderInfo['cart_info'] = $cartInfo;
        return $orderInfo;
    }

    /**
     * 核销订单
     * @param int $uid
     * @param array $orderInfo
     * @param array $cartIds
     * @param int $auth
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeoffOrder(int $uid, array $orderInfo, array $cartIds = [], int $auth = 1, int $staff_id = 0, int $admin_id = 0)
    {
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $time = time();
        if (!$orderInfo['verify_code'] || ($orderInfo['shipping_type'] != 2 && $orderInfo['delivery_type'] != 'send')) {
            throw new ValidateException('此订单不能被核销');
        }
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        if ($storeOrderRefundServices->count(['store_order_id' => $orderInfo['id'], 'refund_type' => [1, 2, 4, 5, 6], 'is_cancel' => 0, 'is_del' => 0])) {
            throw new ValidateException('订单有售后申请请先处理');
        }
        if (isset($orderInfo['pinkStatus']) && $orderInfo['pinkStatus'] != 2) {
            throw new ValidateException('拼团未完成暂不能发货!');
        }
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        if ($orderInfo['status'] >= 2 && !$cartInfoServices->count(['oid' => $orderInfo['id'], 'is_writeoff' => 0])) {
            throw new ValidateException('订单已核销');
        }
        $store_id = $orderInfo['store_id'];
        if ($orderInfo['type'] == 3 && $orderInfo['activity_id'] && $orderInfo['pink_id']) {
            /** @var StorePinkServices $services */
            $services = app()->make(StorePinkServices::class);
            $res = $services->getCount([['id', '=', $orderInfo['pink_id']], ['status', '<>', 2]]);
            if ($res) throw new ValidateException('Failed to write off the group order');
        }

        $cartInfo = [];
        if ($cartIds) {//商城存在部分核销
            $ids = array_unique(array_column($cartIds, 'cart_id'));
            //订单下原商品信息
            $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id'], 'cart_id' => $ids, 'is_writeoff' => 0], 'cart_id,cart_num,surplus_num,product_id,write_times,write_surplus_times,write_start,write_end', 'cart_id');
            if (count($ids) != count($cartInfo)) {
                throw new ValidateException('订单中有商品已核销');
            }
            foreach ($cartIds as $cart) {
                $info = $cartInfo[$cart['cart_id']] ?? [];
                if (!$info) {
                    throw new ValidateException('核销商品不存在');
                }
                if ($cart['cart_num'] > $info['write_surplus_times']) {
                    throw new ValidateException('核销数量超出剩余总核销次数');
                }
            }
        } else {//整单核销
            $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id'], 'is_writeoff' => 0], 'id,cart_id,cart_num,surplus_num,product_id,write_times,write_surplus_times,write_start,write_end', 'cart_id');
        }
        foreach ($cartInfo as $info) {
            if ($info['write_start'] && $time < $info['write_start']) {
                throw new ValidateException('还未到指定核销的开始时间，无法核销');
            }
            if ($info['write_end'] && $time > $info['write_end']) {
                throw new ValidateException('已经超过指定核销的结束时间，无法核销');
            }
        }

        $data = ['clerk_id' => $uid];
        $cartData = ['writeoff_time' => time()];
        if ($auth == 1) {//前端客服  下面数据暂时记录前端客服uid
            $data['staff_id'] = $uid ?? 0;
            $cartData['staff_id'] = $uid ?? 0;
        } else if ($auth == 2) {//配送员
            /** @var DeliveryServiceServices $deliverServiceServices */
            $deliverServiceServices = app()->make(DeliveryServiceServices::class);
            $deliveryInfo = $deliverServiceServices->getDeliveryInfoByUid($uid, $store_id);
            $cartData['delivery_id'] = $deliveryInfo['id'] ?? 0;
        }
        $data = $this->transaction(function () use ($orderInfo, $staff_id, $data, $cartIds, $cartInfoServices, $cartData, $auth, $cartInfo) {
            if ($cartIds) {//选择商品、件数核销
                foreach ($cartIds as $cart) {
                    $write_surplus_num = $cartInfo[$cart['cart_id']]['write_surplus_times'] ?? 0;
                    if (!isset($cartInfo[$cart['cart_id']]) || !$write_surplus_num) continue;
                    if ($cart['cart_num'] >= $write_surplus_num) {//拆分完成
                        $cartData['write_surplus_times'] = 0;
                        $cartData['is_writeoff'] = 1;
                    } else {//拆分部分数量
                        $cartData['write_surplus_times'] = bcsub((string)$write_surplus_num, $cart['cart_num'], 0);
                        $cartData['is_writeoff'] = 0;
                    }
                    //修改原来订单商品信息
                    $cartInfoServices->update(['oid' => $orderInfo['id'], 'cart_id' => $cart['cart_id']], $cartData);
                }
            } else {//整单核销
                //修改原来订单商品信息
                $cartData['is_writeoff'] = 1;
                $cartData['write_surplus_times'] = 0;
                $cartInfoServices->update(['oid' => $orderInfo['id']], $cartData);
            }
            if (!$cartInfoServices->count(['oid' => (int)$orderInfo['id'], 'is_writeoff' => 0])) {//全部核销
                $data['status'] = 2;
                /** @var StoreOrderTakeServices $storeOrdeTask */
                $storeOrdeTask = app()->make(StoreOrderTakeServices::class);
                $re = $storeOrdeTask->storeProductOrderUserTakeDelivery($orderInfo, true, false);
                if (!$re) {
                    throw new ValidateException('Write off failure');
                }
            } else {//部分核销
                /** @var StoreOrderCreateServices $storeOrderCreateServices */
                $storeOrderCreateServices = app()->make(StoreOrderCreateServices::class);
                $data['verify_code'] = $storeOrderCreateServices->getStoreCode();
                $data['status'] = 5;
            }
            if (!$this->dao->update($orderInfo['id'], $data)) {
                throw new ValidateException('Write off failure');
            }
            return $data;
        });
        event('order.writeoff', [$orderInfo, $auth, $data, $cartIds, $cartInfo,$admin_id]);
        return $orderInfo;
    }

    /**
     * 次卡商品核销表单
     * @param int $id
     * @param int $staffId
     * @param int $cart_num
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOrderFrom(int $id, int $cart_num = 1)
    {
        $orderInfo = $this->getOrderCartInfo(0, (int)$id, 0);
        $cartInfo = $orderInfo['cart_info'] ?? [];
        if (!$cartInfo) {
            throw new ValidateException('核销订单商品信息不存在');
        }
        if ($orderInfo['product_type'] != 4) {
            throw new ValidateException('订单商品不支持此类型核销');
        }
        $name = ($cartInfo[0]['write_surplus_times'] ?? 0) . '/' . ($cartInfo[0]['write_times'] ?? 0);
        $f[] = Form::hidden('cart_id', $cartInfo[0]['cart_id'] ?? 0);
        $f[] = Form::input('name', '核销数', $name)->disabled(true);
        $f[] = Form::number('cart_num', '本次核销数量', min(max($cart_num, 1), $cartInfo[0]['write_surplus_times'] ?? 0))->min(1)->max($cartInfo[0]['write_surplus_times'] ?? 1);
        return create_form('次卡核销', $f, $this->url('/order/write/form/' . $id), 'POST');
    }

}
