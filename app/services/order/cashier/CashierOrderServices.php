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

namespace app\services\order\cashier;


use app\dao\order\StoreOrderDao;
use app\jobs\activity\StorePromotionsJob;
use app\jobs\user\MicroPayOrderJob;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderComputedServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\pay\PayServices;
use app\services\pay\YuePayServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\level\UserLevelServices;
use app\services\user\UserAddressServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\traits\OptionTrait;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 收银台订单
 * Class CashierOrderServices
 * @package app\services\order\cashier
 */
class CashierOrderServices extends BaseServices
{

    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    use OptionTrait;

    //余额支付
    const YUE_PAY = 1;
    //线上支付
    const ONE_LINE_PAY = 2;
    //现金支付
    const CASH_PAY = 3;

    /**
     * 缓存订单信息
     * @param int $uid
     * @param array $cartInfo
     * @param array $priceGroup
     * @param array $other
     * @param array $addr
     * @param array $invalidCartInfo
     * @param array $deduction
     * @param int $cacheTime
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cacheOrderInfo(int $uid, array $cartInfo, array $priceGroup, array $other = [], array $addr = [], array $invalidCartInfo = [], array $deduction = [], int $cacheTime = 600)
    {
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $key = md5($storeOrderCreateService->getNewOrderId((string)$uid) . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8));
        CacheService::redisHandler()->set('admin_user_order_' . $uid . $key, compact('cartInfo', 'priceGroup', 'other', 'addr', 'invalidCartInfo', 'deduction'), $cacheTime);
        return $key;
    }

    /**
     * 获取订单缓存信息
     * @param int $uid
     * @param string $key
     * @return |null
     */
    public function getCacheOrderInfo(int $uid, string $key)
    {
        $cacheName = 'admin_user_order_' . $uid . $key;
        if (!CacheService::redisHandler()->has($cacheName)) return null;
        return CacheService::redisHandler()->get($cacheName);
    }

    /**
     * 获取订单确认数据
     * @param array $user
     * @param $cartId
     * @param bool $new
     * @param int $addressId
     * @param int $shipping_type
     * @param int $store_id
     * @param int $coupon_id
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderConfirmData(int $uid, $cartId, bool $new, int $addressId, int $shipping_type = 1, int $coupon_id = 0)
    {
        $addr = $data = $user = [];
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user = $userServices->getUserCacheInfo($uid);
        }
        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        if ($addressId) {
            $addr = $addressServices->getAdderssCache($addressId);
        }
        //没传地址id或地址已删除未找到 ||获取默认地址
        if (!$addr && $uid) {
            $addr = $addressServices->getUserDefaultAddressCache($uid);
        }
        /** @var StoreCartServices $cartServices */
        $cartServices = app()->make(StoreCartServices::class);
        //获取购物车信息
        $cartGroup = $cartServices->getUserProductCartListV1($uid, $cartId, $new, $addr, $shipping_type, $coupon_id);
        $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;//满额包邮金额
        $data['storeFreePostage'] = $storeFreePostage;
        $validCartInfo = $cartGroup['valid'];
        $giveCartList = $cartGroup['giveCartList'] ?? [];
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceGroup = $computedServices->getOrderPriceGroup($uid, $validCartInfo, $addr, $storeFreePostage);
        $priceGroup['couponPrice'] = $cartGroup['couponPrice'] ?? 0;
        $priceGroup['firstOrderPrice'] = $cartGroup['firstOrderPrice'] ?? 0;
        $validCartInfo = array_merge($priceGroup['cartInfo'] ?? $validCartInfo, $giveCartList);
        $other = [
            'offlinePostage' => sys_config('offline_postage'),
            'integralRatio' => sys_config('integral_ratio'),
            'give_integral' => $cartGroup['giveIntegral'] ?? 0,
            'give_coupon' => $cartGroup['giveCoupon'] ?? [],
            'give_product' => $cartGroup['giveProduct'],
            'promotions' => $cartGroup['promotions']
        ];
        $deduction = $cartGroup['deduction'];
        $data['product_type'] = $deduction['product_type'] ?? 0;
        $data['valid_count'] = count($validCartInfo);
        $data['addressInfo'] = $addr;
        $data['type'] = $deduction['type'] ?? 0;
        $data['activity_id'] = $deduction['activity_id'] ?? 0;
        $data['seckill_id'] = $deduction['type'] == 1 ? $deduction['activity_id'] : 0;
        $data['bargain_id'] = $deduction['type'] == 2 ? $deduction['activity_id'] : 0;
        $data['combination_id'] = $deduction['type'] == 3 ? $deduction['activity_id'] : 0;
        $data['storeIntegralId'] = $deduction['type'] == 4 ? $deduction['activity_id'] : 0;
        $data['discount_id'] = $deduction['type'] == 5 ? $deduction['activity_id'] : 0;
        $data['newcomer_id'] = $deduction['type'] == 7 ? $deduction['activity_id'] : 0;
        $data['deduction'] = in_array($deduction['product_type'], [1, 2]) || $deduction['activity_id'] > 0;
        $data['cartInfo'] = array_merge($cartGroup['cartInfo'], $giveCartList);
        // $data['giveCartInfo'] = $giveCartList;
        $data['custom_form'] = [];
        $data['give_integral'] = $other['give_integral'];
        $data['give_coupon'] = [];
        if ($other['give_coupon']) {
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $data['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $other['give_coupon']]], 'id,coupon_title');
        }
        $data['orderKey'] = $this->cacheOrderInfo($uid, $validCartInfo, $priceGroup, $other, $addr, $cartGroup['invalid'] ?? [], $deduction);
        unset($priceGroup['cartInfo']);
        $data['priceGroup'] = $priceGroup;

        $userInfo = ['uid' => $user['uid'] ?? 0, 'nickname' => $user['nickname'] ?? '', 'avatar' => $user['avatar'] ?? '', 'phone' => $user['phone'] ?? '', 'now_money' => $user['now_money'] ?? 0, 'integral' => $user['integral'] ?? 0];
        //会员
        $userInfo['isMember'] = isset($user['is_money_level']) && $user['is_money_level'] > 0 ? 1 : 0;
        //等级
        $userInfo['level'] = $user['level'] ?? 0;
        $userInfo['level_status'] = 0;
        $userInfo['level_grade'] = '';
        $userInfo['vip'] = isset($priceGroup['vipPrice']) && $priceGroup['vipPrice'] > 0;
        $userInfo['vip_id'] = 0;
        $userInfo['discount'] = 0;
        //用户等级是否开启
        if (sys_config('member_func_status', 1)) {
            /** @var UserLevelServices $levelServices */
            $levelServices = app()->make(UserLevelServices::class);
            $userLevel = $levelServices->getUerLevelInfoByUid($uid);
            if ($userInfo['vip'] || $userLevel) {
                $userInfo['vip'] = true;
                $userInfo['vip_id'] = $userLevel['id'] ?? 0;
                $userInfo['discount'] = $userLevel['discount'] ?? 0;
            }
            if ($userInfo['level']) {
                /** @var SystemUserLevelServices $levelServices */
                $levelServices = app()->make(SystemUserLevelServices::class);
                $levelInfo = $levelServices->getOne(['id' => $userInfo['level']], 'id,name,grade');
                $userInfo['level_grade'] = $levelInfo['grade'] ?? '';
                $userInfo['level_status'] = 1;
            }
        }
        $userInfo['real_name'] = $user['real_name'] ?? $user['nickname'] ?? '';
        $userInfo['record_pone'] = $user['record_pone'] ?? $user['phone'] ?? '';
        $data['userInfo'] = $userInfo;
        $data['offlinePostage'] = $other['offlinePostage'];
        $data['integralRatio'] = $other['integralRatio'];
        $data['integral_ratio_status'] = (int)(sys_config('integral_ratio_status', 1) && in_array($data['type'], [0, 6]));
        $data['store_func_status'] = (int)(sys_config('store_func_status', 1));//门店是否开启
        $data['store_self_mention'] = (int)sys_config('store_self_mention');
        /** @var UserInvoiceServices $userInvoice */
        $userInvoice = app()->make(UserInvoiceServices::class);
        $invoice_func = $userInvoice->invoiceFuncStatus();
        $data['invoice_func'] = $invoice_func['invoice_func'];
        $data['special_invoice'] = $invoice_func['special_invoice'];
        return $data;
    }


    /**
     * 计算订单金额
     * @param int $uid
     * @param array $userInfo
     * @param array $cartGroup
     * @param int $addressId
     * @param string $payType
     * @param bool $useIntegral
     * @param int $couponId
     * @param int $shippingType
     * @return array
     */
    public function computeOrder(int $uid, array $cartGroup, int $addressId, string $payType, bool $useIntegral = false, int $shippingType = 1)
    {
        $userInfo = [];
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserCacheInfo($uid);
            if (!$userInfo) {
                throw new ValidateException('用户不存在!');
            }
        }
        $cartInfo = $cartGroup['cartInfo'];
        $priceGroup = $cartGroup['priceGroup'];
        $deduction = $cartGroup['deduction'];
        $other = $cartGroup['other'];
        $promotions = $other['promotions'] ?? [];
        $payPrice = (float)$priceGroup['totalPrice'];
        $payIntegral = (int)$priceGroup['totalIntegral'] ?? 0;
        $couponPrice = (float)$priceGroup['couponPrice'];
        $firstOrderPrice = (float)$priceGroup['firstOrderPrice'];
        $type = (int)$deduction['type'] ?? 0;
        /** @var StoreOrderComputedServices $computeOrderService */
        $computeOrderService = app()->make(StoreOrderComputedServices::class);
        $promotionsPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'promotions_true_price');//优惠活动优惠
        $promotionsDetail = [];
        if ($promotions) {
            foreach ($promotions as $key => $value) {
                if (isset($value['details']['sum_promotions_price']) && $value['details']['sum_promotions_price']) {
                    $promotionsDetail[] = ['id' => $value['id'], 'name' => $value['name'], 'title' => $value['title'], 'desc' => $value['desc'], 'promotions_price' => $value['details']['sum_promotions_price'], 'promotions_type' => $value['promotions_type']];
                }
            }
            if ($promotionsDetail) {
                $typeArr = array_column($promotionsDetail, 'promotions_type');
                array_multisort($typeArr, SORT_ASC, $promotionsDetail);
            }
        }
        $addr = $cartGroup['addr'] ?? [];
        $postage = $priceGroup;
        if (!$addr || $addr['id'] != $addressId) {
            /** @var UserAddressServices $addressServices */
            $addressServices = app()->make(UserAddressServices::class);
            $addr = $addressServices->getAdderssCache($addressId);
            //改变地址重新计算邮费
            $postage = [];
        }
        [$p, $payPostage, $storePostageDiscount, $storeFreePostage, $isStoreFreePostage] = $computeOrderService->computedPayPostage($uid, $shippingType, $payType, $cartInfo, $addr, $payPrice, $postage, $other);
        if ($couponPrice < $payPrice) {//优惠券金额
            $payPrice = bcsub((string)$payPrice, (string)$couponPrice, 2);
        } else {
            $payPrice = 0;
        }
        if ($firstOrderPrice < $payPrice) {//首单优惠金额
            $payPrice = bcsub((string)$payPrice, (string)$firstOrderPrice, 2);
        } else {
            $payPrice = 0;
        }
        if (sys_config('integral_ratio_status') && in_array($type, [0, 6])) {
            //使用积分
            [$payPrice, $deductionPrice, $usedIntegral, $SurplusIntegral] = $computeOrderService->useIntegral($useIntegral, $userInfo, $payPrice, $other);
        }
        $payPrice = (float)bcadd((string)$payPrice, (string)$payPostage, 2);
        return [
            'total_price' => $priceGroup['totalPrice'],
            'pay_price' => max($payPrice, 0),
            'pay_integral' => max($payIntegral, 0),
            'total_postage' => bcadd((string)$payPostage, (string)($storePostageDiscount ?? 0), 2),
            'pay_postage' => $payPostage,
            'first_order_price' => $firstOrderPrice ?? 0,
            'coupon_price' => $couponPrice ?? 0,
            'promotions_price' => $promotionsPrice,
            'promotions_detail' => $promotionsDetail,
            'deduction_price' => $deductionPrice ?? 0,
            'usedIntegral' => $usedIntegral ?? 0,
            'SurplusIntegral' => $SurplusIntegral ?? 0,
            'storePostageDiscount' => $storePostageDiscount ?? 0,
            'isStoreFreePostage' => $isStoreFreePostage ?? false,
            'storeFreePostage' => $storeFreePostage ?? 0,
//            'cartInfo' => $cartInfo
        ];
    }

    /**
     * 收银台用户优惠券
     * @param int $uid
     * @param int $storeId
     * @param array $cartIds
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponList(int $uid, int $storeId, array $cartIds)
    {
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cart = $cartService->getUserCartList($uid, 1, $cartIds, $storeId, 0, 4, 0, 0, 0, false);
        $cartInfo = $cart['valid'];
        if (!$cartInfo) {
            throw new ValidateException('购物车暂无货物！');
        }
        /** @var StoreCouponissueServices $couponIssueServices */
        $couponIssueServices = app()->make(StoreCouponissueServices::class);
        return $couponIssueServices->getCanUseCoupon($uid, $cartInfo, $cart['promotions'] ?? [], $storeId, false);
    }


    /**
     * 生成订单
     * @param int $uid
     * @param array $userInfo
     * @param array $computeData
     * @param int $staffId
     * @param array $cartIds
     * @param string $payType
     * @param bool $integral
     * @param bool $coupon
     * @param string $remarks
     * @param string $changePrice
     * @param bool $isPrice
     * @param int $coupon_id
     * @param int $seckillId
     * @param $collate_code_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(int $uid, string $key, array $cartGroup, int $addressId, string $payType, array $addressInfo, int $staffId, bool $useIntegral = false, $couponId = 0, $mark = '', $shippingType = 1, string $from = '')
    {
        //兼容门店虚拟用户下单
        $field = ['real_name', 'phone', 'province', 'city', 'district', 'street', 'detail'];
        $userInfo = [];
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserCacheInfo($uid);
            if (!$userInfo) {
                throw new ValidateException('用户不存在!');
            }
            $userInfo = $userInfo->toArray();
        }
        if (!$addressInfo) {
            foreach ($field as $key) {
                $addressInfo[$key] = '';
            }
        }
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceData = $computedServices->computedOrder($uid, $userInfo, $cartGroup, $addressId, $payType, $useIntegral, $couponId, $shippingType);
        $cartInfo = $cartGroup['cartInfo'];
        $priceGroup = $cartGroup['priceGroup'];
        $cartIds = [];
        $totalNum = 0;
        $gainIntegral = 0;
        foreach ($cartInfo as $cart) {
            $cartIds[] = $cart['id'];
            $totalNum += $cart['cart_num'];
            $cartInfoGainIntegral = isset($cart['productInfo']['give_integral']) ? bcmul((string)$cart['cart_num'], (string)$cart['productInfo']['give_integral'], 0) : 0;
            $gainIntegral = bcadd((string)$gainIntegral, (string)$cartInfoGainIntegral, 0);
        }

        $cartInfo = $cartGroup['cartInfo'];
        $deduction = $cartGroup['deduction'];
        $promotions_give = [
            'give_integral' => $cartGroup['give_integral'] ?? 0,
            'give_coupon' => $cartGroup['giveCoupon'] ?? [],
            'give_product' => $cartGroup['giveProduct'] ?? [],
            'promotions' => $cartGroup['promotions'] ?? []
        ];
        $type = (int)$deduction['type'] ?? 0;
        /** @var StoreOrderCreateServices $orderServices */
        $orderServices = app()->make(StoreOrderCreateServices::class);
        $product_type = (int)$deduction['product_type'] ?? 0;
        $orderInfo = [
            'uid' => $uid,
            'type' => $type,
            'order_id' => $orderServices->getNewOrderId(),
            'real_name' => $addressInfo['real_name'] ?: $userInfo['nickname'] ?? '',
            'user_phone' => $addressInfo['phone'] ?: $userInfo['phone'] ?? '',
            'user_address' => isset($addressInfo['addressInfo']) && $addressInfo['addressInfo'] ? $addressInfo['addressInfo'] : $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['street'] . ' ' . $addressInfo['detail'],
            'cart_id' => $cartIds,
            'clerk_id' => 0,
            'staff_id' => $staffId,
            'total_num' => $totalNum,
            'total_price' => $priceGroup['sumPrice'] ?? $priceGroup['totalPrice'],
            'total_postage' => $priceData['total_postage'] ?? $priceGroup['storePostage'],
            'coupon_id' => $couponId,
            'coupon_price' => $priceData['coupon_price'],
            'first_order_price' => $priceData['first_order_price'],
            'promotions_price' => $priceData['promotions_price'],
            'pay_integral' => $priceData['pay_integral'],
            'pay_price' => $priceData['pay_price'],
            'pay_postage' => $priceData['pay_postage'],
            'deduction_price' => $priceData['deduction_price'],
            'paid' => 0,
            'pay_type' => $payType,
            'use_integral' => $priceData['usedIntegral'],
            'gain_integral' => $gainIntegral,
            'mark' => htmlspecialchars($mark),
            'product_type' => $product_type,
            'activity_id' => 0,
            'pink_id' => 0,
            'cost' => $priceGroup['costPrice'],
            'is_channel' => 2,
            'unique' => $key,
            'add_time' => time(),
            'shipping_type' => $shippingType,
            'channel_type' => $userInfo['user_type'] ?? '',
            'province' => '',
            'spread_uid' => 0,
            'spread_two_uid' => 0,
            'promotions_give' => json_encode($promotions_give),
            'give_integral' => $promotions_give['give_integral'] ?? 0,
            'give_coupon' => implode(',', $promotions_give['give_coupon'] ?? []),
            'division_id' => $userInfo['division_id'] ?? 0,
            'division_agent_id' => $userInfo['agent_id'] ?? 0,
            'division_staff_id' => $userInfo['staff_id'] ?? 0,
        ];
        if ($product_type == 4 || $shippingType == 2) {//次卡商品收银台购买
            $orderInfo['verify_code'] = $orderServices->getStoreCode();
            $orderInfo['shipping_type'] = 2;//修改门店自提
        }
        $order = $this->transaction(function () use ($key, $cartInfo, $type, $priceData, $orderInfo, $orderServices, $couponId, $userInfo, $useIntegral, $promotions_give, $payType) {
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $order = $orderServices->save($orderInfo);
            if (!$order) {
                throw new ValidateException('订单生成失败');
            }
            //使用优惠券
            if ($couponId) {
                /** @var StoreCouponUserServices $couponServices */
                $couponServices = app()->make(StoreCouponUserServices::class);
                $res1 = $couponServices->useCoupon($couponId, (int)($userInfo['uid'] ?? 0), $cartInfo, []);
                if (!$res1) {
                    throw new ValidateException('使用优惠劵失败!');
                }
            }
            //积分抵扣
            $orderServices->deductIntegral($userInfo, $useIntegral, $priceData, (int)($userInfo['uid'] ?? 0), $key);
            //修改门店库存
            $orderServices->decGoodsStock($cartInfo, $type);
            //保存购物车商品信息
            $cartServices->setCartInfo($order['id'], $cartInfo, $userInfo['uid'] ?? 0, $promotions_give['promotions'] ?? []);
            return $order->toArray();
        });


        $news = false;
        $addressId = $type = $activity_id = 0;
        $delCart = true;
        //扣除优惠活动赠品限量
        StorePromotionsJob::dispatchDo('changeGiveLimit', [$promotions_give]);
        $group = compact('cartInfo', 'priceData', 'addressId', 'cartIds', 'news', 'delCart');
        $orderServices->delCart($group);
        event('order.create', [$order, $userInfo, $group, compact('type', 'activity_id'), 0]);
        return $order;
    }

    /**
     * 收银台支付
     * @param string $orderId
     * @param string $payType
     * @param string $userCode
     * @param string $authCode
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function paySuccess(string $orderId, string $payType, string $userCode, string $authCode = '')
    {
        /** @var StoreOrderSuccessServices $orderService */
        $orderService = app()->make(StoreOrderSuccessServices::class);
        $orderInfo = $orderService->get(['order_id' => $orderId]);
        if (!$orderInfo) {
            throw new ValidateException('没有查询到订单信息');
        }
        if ($orderInfo->paid) {
            throw new ValidateException('订单已支付');
        }
        if ($orderInfo->is_del) {
            throw new ValidateException('订单已取消');
        }
        switch ($payType) {
            case PayServices::YUE_PAY://余额支付
                $is_cashier_yue_pay_verify = (int)sys_config('is_cashier_yue_pay_verify'); // 收银台余额支付是否需要验证【是/否】
                if (!$orderInfo['uid']) {
                    throw new ValidateException('余额支付用户信息不存在无法支付');
                }
                if (!$userCode && $is_cashier_yue_pay_verify) {
                    throw new ValidateException('缺少扫码支付参数');
                }
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                $userInfo = $userService->getUserInfo($orderInfo->uid, ['uid', 'rand_code']);
                //读取缓存用户code
                $rand_code = CacheService::redisHandler()->get('user_rand_code' . $orderInfo->uid);
                CacheService::redisHandler()->delete('user_rand_code' . $orderInfo->uid);
                if (!$userInfo) {
                    throw new ValidateException('余额支付用户不存在');
                }
                if ($rand_code != $userCode && $is_cashier_yue_pay_verify) {
                    throw new ValidateException('二维码已使用或不正确，请确认后重新扫码');
                }
                /** @var YuePayServices $payService */
                $payService = app()->make(YuePayServices::class);
                $pay = $payService->yueOrderPay($orderInfo->toArray(), $orderInfo->uid);
                if ($pay['status'] === true)
                    return ['status' => 'SUCCESS'];
                else if ($pay['status'] === 'pay_deficiency') {
                    throw new ValidateException($pay['msg']);
                } else {
                    return ['status' => 'ERROR', 'message' => is_array($pay) ? $pay['msg'] ?? '余额支付失败' : $pay];
                }
            case PayServices::WEIXIN_PAY://微信支付
            case PayServices::ALIAPY_PAY://支付宝支付
                if (!$authCode) {
                    throw new ValidateException('缺少支付付款二维码CODE');
                }

                $pay = new PayServices();
                /** @var StoreOrderCartInfoServices $orderInfoServices */
                $orderInfoServices = app()->make(StoreOrderCartInfoServices::class);
                $body = $orderInfoServices->getCarIdByProductTitle((int)$orderInfo['id']);
                $body = substrUTf8($body, 30);
                try {
                    //扫码支付
                    $response = $pay->setAuthCode($authCode)->pay($payType, '', $orderInfo->order_id, $orderInfo->pay_price, 'product', $body);
                } catch (\Throwable $e) {
                    \think\facade\Log::error('收银端' . $payType . '扫码支付失败，原因：' . $e->getMessage());
                    return ['status' => 'ERROR', 'message' => '支付失败，原因：' . $e->getMessage()];
                }
                //支付成功paid返回1
                if ($response['paid']) {
                    if (!$orderService->paySuccess($orderInfo->toArray(), $payType, ['trade_no' => $response['payInfo']['transaction_id'] ?? ''])) {
                        return ['status' => 'ERROR', 'message' => '支付失败'];
                    }
                    //支付成功刪除購物車
                    /** @var StoreCartServices $cartServices */
                    $cartServices = app()->make(StoreCartServices::class);
                    $cartServices->deleteCartStatus($orderInfo['cart_id'] ?? []);
                    return ['status' => 'SUCCESS'];
                } else {
                    if ($payType === PayServices::WEIXIN_PAY) {
                        if (isset($response['payInfo']['err_code']) && in_array($response['payInfo']['err_code'], ['AUTH_CODE_INVALID', 'NOTENOUGH'])) {
                            return ['status' => 'ERROR', 'message' => '支付失败'];
                        }
                        //微信付款码支付需要同步更改状态
                        $secs = 5;
                        if (isset($order_info['payInfo']['err_code']) && $order_info['payInfo']['err_code'] === 'USERPAYING') {
                            $secs = 10;
                        }
                        //放入队列执行
                        MicroPayOrderJob::dispatchSece($secs, [$orderInfo['order_id'], 0]);
                    }
                    return ['status' => 'PAY_ING', 'message' => $response['message']];
                }
                break;
            case PayServices::CASH_PAY://收银台现金支付
                if (!$orderService->paySuccess($orderInfo->toArray(), $payType)) {
                    return ['status' => 'ERROR', 'message' => '支付失败'];
                } else {
                    return ['status' => 'SUCCESS'];
                }
                break;
            default:
                throw new ValidateException('暂无支付方式，无法支付');
        }
    }


}
