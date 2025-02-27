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


use app\jobs\activity\StorePromotionsJob;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\newcomer\StoreNewcomerServices;
use app\services\agent\AgentLevelServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\agent\DivisionServices;
use app\services\other\CityAreaServices;
use app\services\pay\PayServices;
use app\services\product\brand\StoreBrandServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\shipping\ShippingTemplatesFreeServices;
use app\services\product\shipping\ShippingTemplatesRegionServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\system\PrintDocumentServices;
use app\services\user\UserRechargeServices;
use app\services\wechat\WechatUserServices;
use app\services\BaseServices;
use crmeb\exceptions\PayException;
use crmeb\services\CacheService;
use app\dao\order\StoreOrderDao;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\exception\ValidateException;
use app\services\user\UserBillServices;
use app\services\user\UserAddressServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\store\SystemStoreServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\product\product\StoreProductServices;
use think\facade\Cache;
use think\facade\Log;

/**
 * 订单创建
 * Class StoreOrderCreateServices
 * @package app\services\order
 * @mixin StoreOrderDao
 */
class StoreOrderCreateServices extends BaseServices
{

    //秒杀购买次数数据缓存前缀
    const PAY_SECKILL_SUM_NAME = 'pay_sum_';

    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * @param int $uid
     * @param int $type
     * @param int $id
     * @param int $totalNum
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/3
     */
    public function setBuyCountCache(int $uid, int $type, int $id, int $totalNum)
    {
        $key = md5($uid . $type . $id);
        $num = $this->dao->cacheInfoById($key);
        $totalNum = ($num ? 1 : 0) + $totalNum;
        $this->dao->cacheUpdate(['type' => $type, 'uid' => $uid, 'product_id' => $id, 'totalNum' => $totalNum], $key);
    }

    /**
     * 使用雪花算法生成订单ID
     * @return string
     * @throws \Exception
     */
    public function getNewOrderId(string $prefix = 'wx')
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake();
        $is_callable = function ($currentTime) {
            $redis = Cache::store('redis');
            $swooleSequenceResolver = new \Godruoyi\Snowflake\RedisSequenceResolver($redis->handler());
            return $swooleSequenceResolver->sequence($currentTime);
        };
        //32位
        if (PHP_INT_SIZE == 4) {
            $id = abs($snowflake->setSequenceResolver($is_callable)->id());
        } else {
            $id = $snowflake->setStartTimeStamp(strtotime('2020-06-05') * 1000)->setSequenceResolver($is_callable)->id();
        }
        return $prefix . $id;
    }

    /**
     * 核销订单生成核销码
     * @return false|string
     */
    public function getStoreCode()
    {
        mt_srand();
        [$msec, $sec] = explode(' ', microtime());
        $num = time() + mt_rand(10, 999999) . '' . substr($msec, 2, 3);//生成随机数
        if (strlen($num) < 12)
            $num = str_pad((string)$num, 12, 0, STR_PAD_RIGHT);
        else
            $num = substr($num, 0, 12);
        if ($this->dao->count(['verify_code' => $num])) {
            return $this->getStoreCode();
        }
        return $num;
    }

    /**
     * 创建订单
     * @param int $uid
     * @param string $key
     * @param array $cartGroup
     * @param int $addressId
     * @param string $payType
     * @param array $addressInfo
     * @param array $userInfo
     * @param bool $useIntegral
     * @param int $couponId
     * @param string $mark
     * @param int $pinkId
     * @param int $isChannel
     * @param int $shippingType
     * @param int $storeId
     * @param false $news
     * @param array $customForm
     * @param int $invoice_id
     * @param string $from
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(int $uid, string $key, array $cartGroup, int $addressId, string $payType, array $addressInfo, array $userInfo = [], bool $useIntegral = false, $couponId = 0, $mark = '', $pinkId = 0, $isChannel = 0, $shippingType = 1, $storeId = 0, $news = false, $customForm = [], int $invoice_id = 0, string $from = '')
    {
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
        $deduction = $cartGroup['deduction'];
        $other = $cartGroup['other'];
        $promotions_give = [
            'give_integral' => $other['give_integral'] ?? 0,
            'give_coupon' => $other['give_coupon'] ?? [],
            'give_product' => $other['give_product'] ?? [],
            'promotions' => $other['promotions'] ?? []
        ];
        $type = (int)$deduction['type'] ?? 0;
        $activity_id = (int)$deduction['activity_id'] ?? 0;
        $product_type = (int)$deduction['product_type'] ?? 0;
        if ($type != 0) {
            $couponId = 0;
            if ($type != 5) $useIntegral = false;
            $systemPayType = PayServices::PAY_TYPE;
            unset($systemPayType['offline']);
            if ($from != 'pc' && !array_key_exists($payType, $systemPayType)) {
                throw new ValidateException('营销商品不能使用线下支付!');
            }
        } else if ($type == 8) {
            $gainIntegral = 0;
        }
        //$shipping_type = 1 快递发货 $shipping_type = 2 门店自提
        if (!sys_config('store_func_status', 1) || !sys_config('store_self_mention', 1)) $shippingType = 1;

        $userAddress = $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['street'] . ' ' . $addressInfo['detail'];
        $userLocation = $addressInfo['longitude'] . ' ' . $addressInfo['latitude'];

        $orderInfo = [
            'uid' => $uid,
            'type' => $type,
            'order_id' => $this->getNewOrderId(),
            'real_name' => $addressInfo['real_name'],
            'user_phone' => $addressInfo['phone'],
            'user_address' => $userAddress,
            'user_location' => $userLocation,
            'cart_id' => $cartIds,
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
            'change_price' => $priceData['change_price'] ?? 0.00,
            'paid' => 0,
            'pay_type' => $payType,
            'use_integral' => $priceData['usedIntegral'],
            'gain_integral' => $gainIntegral,
            'mark' => htmlspecialchars($mark),
            'product_type' => $product_type,
            'activity_id' => $activity_id,
            'pink_id' => $pinkId,
            'cost' => $priceGroup['costPrice'],
            'is_channel' => $isChannel,
            'add_time' => time(),
            'unique' => $key,
            'shipping_type' => $shippingType,
            'channel_type' => $userInfo['user_type'],
            'province' => '',
            'spread_uid' => 0,
            'spread_two_uid' => 0,
            'custom_form' => json_encode($customForm),
            'promotions_give' => json_encode($promotions_give),
            'give_integral' => $promotions_give['give_integral'] ?? 0,
            'give_coupon' => implode(',', $promotions_give['give_coupon'] ?? []),
            'store_id' => $storeId,
            'division_id' => $userInfo['division_id'],
            'division_agent_id' => $userInfo['agent_id'],
            'division_staff_id' => $userInfo['staff_id'],
        ];
        if ($userInfo['user_type'] == 'wechat' || $userInfo['user_type'] == 'routine') {
            /** @var WechatUserServices $wechatServices */
            $wechatServices = app()->make(WechatUserServices::class);
            $orderInfo['province'] = $wechatServices->value(['uid' => $uid, 'user_type' => $userInfo['user_type']], 'province') ?: '';
        }
        if ($shippingType == 2) {
            $orderInfo['verify_code'] = $this->getStoreCode();
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['store_id'] = $storeServices->getStoreDisposeCache($storeId, 'id');
            if (!$orderInfo['store_id']) {
                throw new ValidateException('暂无门店无法选择门店自提');
            }
        }

        $priceData['coupon_id'] = $couponId;
        $order = $this->transaction(function () use ($cartIds, $couponId, $orderInfo, $cartInfo, $key, $userInfo, $useIntegral, $priceData, $type, $activity_id, $uid, $addressId, $promotions_give) {
            //创建订单
            $order = $this->dao->save($orderInfo);
            if ($couponId) {
                /** @var StoreCouponUserServices $couponServices */
                $couponServices = app()->make(StoreCouponUserServices::class);
                $couponServices->useCoupon($couponId, (int)$userInfo['uid'], $cartInfo);
            }
            //抵扣积分
            $this->deductIntegral($userInfo, $useIntegral, $priceData, (int)$userInfo['uid'], $key);
            //扣库存
            $this->decGoodsStock($cartInfo, $type, $activity_id, $orderInfo['store_id'] ?? 0);
            //保存购物车商品信息
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $cartServices->setCartInfo($order['id'], $cartInfo, (int)$uid, $promotions_give['promotions'] ?? []);

            return $order;
        });
        //扣除优惠活动赠品限量
        StorePromotionsJob::dispatchDo('changeGiveLimit', [$promotions_give]);
        //订单创建事件
        event('order.create', [$order, $userInfo, compact('cartInfo', 'priceData', 'addressId', 'cartIds', 'news'), compact('type', 'activity_id'), $invoice_id]);

        // 订单生成小票打印
        try {
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $product = $cartServices->getCartInfoPrintProduct((int)$order['id']);
            if (!$product) {
                throw new ValidateException('订单商品获取失败,无法打印!');
            }
            app()->make(PrintDocumentServices::class)->startPrint(is_object($order) ? $order->toArray() : $order, $product, 2, 0);

        } catch (\Throwable $e) {
            Log::error('生成订单打印小票错误:' . $e->getMessage());
        }
        return $order;
    }


    /**
     * 抵扣积分
     * @param array $userInfo
     * @param bool $useIntegral
     * @param array $priceData
     * @param int $uid
     * @param string $key
     */
    public function deductIntegral(array $userInfo, bool $useIntegral, array $priceData, int $uid, string $key)
    {
        $res2 = true;
        if (sys_config('integral_ratio_status', 1) && $userInfo && $useIntegral && $userInfo['integral'] > 0) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if (!$priceData['SurplusIntegral']  && $priceData['usedIntegral'] >= $userInfo['integral']) {
                $integral = 0;
            } else {
                $integral = bcsub((string)$userInfo['integral'], (string)$priceData['usedIntegral']);
            }
            $res2 = false !== $userServices->update($uid, ['integral' => $integral]);
            /** @var UserBillServices $userBillServices */
            $userBillServices = app()->make(UserBillServices::class);
            $res3 = $userBillServices->income('deduction', $uid, [
                'number' => (int)$priceData['usedIntegral'],
                'deductionPrice' => $priceData['deduction_price']
            ], $integral, $key);

            $res2 = $res2 && false != $res3;
        }
        if (!$res2) {
            throw new ValidateException('使用积分抵扣失败!');
        }
    }

    /**
     * 扣库存
     * @param array $cartInfo
     * @param int $type
     * @param int $activity_id
     * @param int $store_id
     */
    public function decGoodsStock(array $cartInfo, int $type, int $activity_id = 0, int $store_id = 0)
    {
        $res5 = true;
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        /** @var StoreCombinationServices $pinkServices */
        $pinkServices = app()->make(StoreCombinationServices::class);
        /** @var StoreBargainServices $bargainServices */
        $bargainServices = app()->make(StoreBargainServices::class);
        /** @var StoreDiscountsServices $discountServices */
        $discountServices = app()->make(StoreDiscountsServices::class);
        /** @var StoreNewcomerServices $storeNewcomerServices */
        $storeNewcomerServices = app()->make(StoreNewcomerServices::class);
        /** @var StoreIntegralServices $storeIntegralServices */
        $storeIntegralServices = app()->make(StoreIntegralServices::class);
        try {
            foreach ($cartInfo as $cart) {
                $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
                $cart_num = (int)$cart['cart_num'];
                //减库存加销量
                switch ($type) {
                    case 0://普通
                    case 6://预售
                    case 8://抽奖
                        $res5 = $res5 && $services->decProductStock($cart_num, (int)$cart['productInfo']['id'], $unique);
                        break;
                    case 1://秒杀
                        $res5 = $res5 && $seckillServices->decSeckillStock($cart_num, $activity_id, $unique, $store_id);
                        break;
                    case 2://砍价
                        $res5 = $res5 && $bargainServices->decBargainStock($cart_num, $activity_id, $unique, $store_id);
                        break;
                    case 3://拼团
                        $res5 = $res5 && $pinkServices->decCombinationStock($cart_num, $activity_id, $unique, $store_id);
                        break;
                    case 4://积分
                        $res5 = $res5 && $storeIntegralServices->decIntegralStock($cart_num, $activity_id, $unique);
                        break;
                    case 5://套餐
                        $res5 = $res5 && $discountServices->decDiscountStock($cart_num, $activity_id, (int)($cart['discount_product_id'] ?? 0), (int)($cart['product_id'] ?? 0), $unique, $store_id);
                        break;
                    case 7://新人专享
                        $res5 = $res5 && $storeNewcomerServices->decNewcomerStock($cart_num, $activity_id, $unique, $store_id);
                        break;
                    default:
                        $res5 = $res5 && $services->decProductStock($cart_num, (int)$cart['productInfo']['id'], $unique);
                        break;

                }
            }
            if ($type == 5 && $activity_id) {
                //改变套餐限量
                $res5 = $res5 && $discountServices->changeDiscountLimit($activity_id);
            }
            if (!$res5) {
                throw new ValidateException('库存不足!');
            }
        } catch (\Throwable $e) {
            throw new ValidateException('库存不足!');
        }
    }

    /**
     * 设置用户默认地址
     * @param UserAddressServices $addressServices
     * @param $order
     * @param array $group
     */
    public function updateUserAddress($order, array $group)
    {
        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        //设置用户默认地址
        if ($order['uid'] && isset($group['addressId']) && $group['addressId'] && !$addressServices->be(['is_default' => 1, 'uid' => $order['uid']])) {
            $addressServices->setDefaultAddress($group['addressId'], $order['uid']);
        }
    }

    /**
     * 订单创建后的后置事件
     * @param UserAddressServices $addressServices
     * @param $order
     * @param array $group
     */
    public function delCart(array $group)
    {
        //删除购物车
        if (isset($group['news']) && $group['news']) {
            array_map(function ($key) {
                CacheService::redisHandler()->delete($key);
            }, $group['cartIds'] ?? []);
        } else {
            if (!isset($group['delCart']) || $group['delCart']) {
                /** @var StoreCartServices $cartServices */
                $cartServices = app()->make(StoreCartServices::class);
                $cartServices->deleteCartStatus($group['cartIds'] ?? []);
            }
        }
    }

    /**
     * 计算订单每个商品真实付款价格
     * @param array $orderInfo
     * @param array $cartInfo
     * @param array $priceData
     * @param $addressId
     * @param int $uid
     * @param $userInfo
     * @return array
     */
    public function computeOrderProductTruePrice($orderInfo, array $cartInfo, array $priceData, int $uid, $userInfo)
    {
        //统一放入默认数据
        foreach ($cartInfo as &$cart) {
            $cart['use_integral'] = 0;
            $cart['integral_price'] = 0.00;
            if (!isset($cart['coupon_price'])) {
                $cart['coupon_price'] = 0.00;
            }
            $cart['first_order_price'] = 0.00;
            $cart['one_brokerage'] = 0.00;
            $cart['two_brokerage'] = 0.00;
        }
        try {
            $promotionsGice = isset($orderInfo['promotions_give']) ? (is_string($orderInfo['promotions_give']) ? json_decode($orderInfo['promotions_give'], true) : $orderInfo['promotions_give']) : [];
            $promotions = [];
            if (isset($promotionsGice['promotions']) && $promotionsGice['promotions']) {
                $promotions = $promotionsGice['promotions'];
            }
            //$cartInfo = $this->computeOrderProductCoupon($cartInfo, $priceData, $promotions);
            $cartInfo = $this->computeOrderProductIntegral($cartInfo, $priceData);
            $cartInfo = $this->computeOrderProductFirstDiscount($cartInfo, $priceData);
//            $cartInfo = $this->computeOrderProductPostage($cartInfo, $priceData, $addressId);
        } catch (\Throwable $e) {
            Log::error('订单商品结算失败,File：' . $e->getFile() . ',Line：' . $e->getLine() . ',Message：' . $e->getMessage());
            throw new ValidateException('订单商品结算失败');
        }
        //truePice实际支付单价（存在）
        //几件商品总体优惠 以及积分抵扣金额
        foreach ($cartInfo as &$cart) {
            $coupon_price = $cart['coupon_price'] ?? 0;
            $integral_price = $cart['integral_price'] ?? 0;
            $first_order_price = $cart['first_order_price'] ?? 0;
            $cart['sum_true_price'] = $cart['pay_price'];
//            $cart['sum_true_price'] = bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2);

            if ($coupon_price) {
                $cart['sum_true_price'] = bcsub((string)$cart['sum_true_price'], (string)$coupon_price, 2);
                $cart['pay_price'] = bcsub((string)$cart['pay_price'], (string)$coupon_price, 2);
                $uni_coupon_price = (string)bcdiv((string)$coupon_price, (string)$cart['cart_num'], 4);
                $cart['truePrice'] = $cart['truePrice'] > $uni_coupon_price ? bcsub((string)$cart['truePrice'], $uni_coupon_price, 2) : 0;
            }
            if ($integral_price) {
                $cart['sum_true_price'] = bcsub((string)$cart['sum_true_price'], (string)$integral_price, 2);
                $cart['pay_price'] = bcsub((string)$cart['pay_price'], (string)$integral_price, 2);
                $uni_integral_price = (string)bcdiv((string)$integral_price, (string)$cart['cart_num'], 4);
                $cart['truePrice'] = $cart['truePrice'] > $uni_integral_price ? bcsub((string)$cart['truePrice'], $uni_integral_price, 2) : 0;
            }
            if ($first_order_price) {
                $cart['sum_true_price'] = bcsub((string)$cart['sum_true_price'], (string)$first_order_price, 2);
                $cart['pay_price'] = bcsub((string)$cart['pay_price'], (string)$first_order_price, 2);
                $uni_integral_price = (string)bcdiv((string)$first_order_price, (string)$cart['cart_num'], 4);
                $cart['truePrice'] = $cart['truePrice'] > $uni_integral_price ? bcsub((string)$cart['truePrice'], $uni_integral_price, 2) : 0;
            }
        }
        //计算佣金：1售价2实际支付金额
        [$cartInfo, $spread_ids] = $this->computeOrderProductBrokerage($uid, $cartInfo, $userInfo);
        return [$cartInfo, $spread_ids];
    }

    /**
     * 计算每个商品实际支付运费
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductPostage(array $cartInfo, array $priceData, $addressId)
    {
        $storePostage = $priceData['pay_postage'] ?? 0;
        if ($storePostage) {
            /** @var UserAddressServices $addressServices */
            $addressServices = app()->make(UserAddressServices::class);
            $addr = $addressServices->getAdderssCache($addressId);
            if ($addr) {
                //按照运费模板计算每个运费模板下商品的件数/重量/体积以及总金额 按照首重倒序排列
                $cityId = $addr['city_id'] ?? 0;
                $ids = [];
                if ($cityId) {
                    /** @var CityAreaServices $cityAreaServices */
                    $cityAreaServices = app()->make(CityAreaServices::class);
                    $ids = $cityAreaServices->getRelationCityIds($cityId);
                }
                $cityIds = array_merge([0], $ids);

                $tempIds[] = 1;
                foreach ($cartInfo as $key_c => $item_c) {
                    $tempIds[] = $item_c['productInfo']['temp_id'];
                }
                $tempIds = array_unique($tempIds);
                /** @var ShippingTemplatesServices $shippServices */
                $shippServices = app()->make(ShippingTemplatesServices::class);
                $temp = $shippServices->getShippingColumn(['id' => $tempIds], 'type,appoint', 'id');
                /** @var ShippingTemplatesRegionServices $regionServices */
                $regionServices = app()->make(ShippingTemplatesRegionServices::class);
                $regions = $regionServices->getTempRegionList($tempIds, $cityIds, 'temp_id,first,first_price,continue,continue_price', 'temp_id');
                $temp_num = [];
                foreach ($cartInfo as $cart) {
                    $tempId = $cart['productInfo']['temp_id'] ?? 1;
                    $type = isset($temp[$tempId]['type']) ? $temp[$tempId]['type'] : $temp[1]['type'];
                    if ($type == 1) {
                        $num = $cart['cart_num'];
                    } elseif ($type == 2) {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['weight'];
                    } else {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['volume'];
                    }
                    $region = isset($regions[$tempId]) ? $regions[$tempId] : $regions[1];
                    if (!isset($temp_num[$tempId])) {
                        $temp_num[$tempId] = [
                            'cart_id' => [$cart['id']],
                            'number' => $num,
                            'type' => $type,
                            'price' => bcmul($cart['cart_num'], $cart['truePrice'], 2),
                            'first' => $region['first'],
                            'first_price' => $region['first_price'],
                            'continue' => $region['continue'],
                            'continue_price' => $region['continue_price'],
                            'temp_id' => $tempId
                        ];
                    } else {
                        $temp_num[$tempId]['cart_id'][] = $cart['id'];
                        $temp_num[$tempId]['number'] += $num;
                        $temp_num[$tempId]['price'] += bcmul($cart['cart_num'], $cart['truePrice'], 2);
                    }
                }
                $cartInfo = array_combine(array_column($cartInfo, 'id'), $cartInfo);
                /** @var ShippingTemplatesFreeServices $freeServices */
                $freeServices = app()->make(ShippingTemplatesFreeServices::class);
                $freeList = $freeServices->isFreeList($tempIds, $cityIds, 0, 'temp_id,number,price', 'temp_id');
                if ($freeList) {
                    foreach ($temp_num as $k => $v) {
                        if (isset($temp[$v['temp_id']]['appoint']) && $temp[$v['temp_id']]['appoint'] && isset($freeList[$v['temp_id']])) {
                            $free = $freeList[$v['temp_id']];
                            $condition = $free['number'] <= $v['number'];
                            if ($free['price'] <= $v['price'] && $condition) {
                                //免运费
                                foreach ($v['cart_id'] as $c_id) {
                                    if (isset($cartInfo[$c_id])) $cartInfo[$c_id]['postage_price'] = 0.00;
                                }
                            }
                        }
                    }
                }
                $count = 0;
                $compute_price = 0.00;
                $total_price = 0;
                $postage_price = 0.00;
                foreach ($cartInfo as $cart) {
                    if (isset($cart['postage_price'])) {//免运费
                        continue;
                    }
                    if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                        continue;
                    }
                    $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                    $count++;
                }
                foreach ($cartInfo as &$cart) {
                    if (isset($cart['postage_price'])) {//免运费
                        continue;
                    }
                    if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                        continue;
                    }
                    if ($count > 1) {
                        $postage_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$storePostage, 2);
                        $compute_price = bcadd((string)$compute_price, (string)$postage_price, 2);
                    } else {
                        $postage_price = bcsub((string)$storePostage, $compute_price, 2);
                    }
                    $cart['postage_price'] = $postage_price;
                    $count--;
                }
                $cartInfo = array_merge($cartInfo);
            }
        }
        return $cartInfo;
    }


    /**
     * 计算订单商品新人首单实际抵扣金额
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductFirstDiscount(array $cartInfo, array $priceData)
    {
        $first_order_price = $priceData['first_order_price'] ?? 0;
        if ($first_order_price) {
            $count = 0;
            $total_price = 0.00;
            $compute_price = 0.00;
            $price = 0.00;
            foreach ($cartInfo as $cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                $count++;
            }
            if ($total_price == $first_order_price) {
                $ratio = 1;
            } else {
                $ratio = bcdiv((string)$first_order_price, (string)$total_price, 4);
            }
            foreach ($cartInfo as &$cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                if ($count > 1) {
                    $discount_price = bcmul((string)$cart['pay_price'], (string)$ratio, 2);
                    $compute_price = bcadd((string)$compute_price, (string)$price, 2);
                } else {
                    $discount_price = bcsub((string)$first_order_price, $compute_price, 2);
                }
                $count--;
                $cart['first_order_price'] = $discount_price;
            }
        }
        return $cartInfo;
    }

    /**
     * 计算订单商品积分实际抵扣金额
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductIntegral(array $cartInfo, array $priceData)
    {
        $usedIntegral = $priceData['usedIntegral'] ?? 0;
        $deduction_price = $priceData['deduction_price'] ?? 0;
        if ($deduction_price) {
            $count = 0;
            $total_price = 0.00;
            $compute_price = 0.00;
            $integral_price = 0.00;
            $use_integral = 0;
            $compute_integral = 0;
            foreach ($cartInfo as $cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                $count++;
            }
            if ($total_price == $deduction_price) {
                $ratio = 1;
            } else {
                $ratio = bcdiv((string)$deduction_price, (string)$total_price, 4);
            }
            foreach ($cartInfo as &$cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                if ($count > 1) {
                    $integral_price = bcmul((string)$cart['pay_price'], (string)$ratio, 2);
                    $compute_price = bcadd((string)$compute_price, (string)$integral_price, 2);
                    $use_integral = bcmul((string)bcdiv((string)$cart['pay_price'], (string)$total_price, 4), (string)$usedIntegral, 0);
                    $compute_integral = bcadd((string)$compute_integral, $use_integral, 0);
                } else {
                    $integral_price = bcsub((string)$deduction_price, $compute_price, 2);
                    $use_integral = bcsub((string)$usedIntegral, $compute_integral, 0);
                }
                $count--;
                $cart['integral_price'] = $integral_price;
                $cart['use_integral'] = $use_integral;
            }
        }
        return $cartInfo;
    }

    /**
     * 计算订单商品优惠券实际抵扣金额
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductCoupon(array $cartInfo, array $priceData, array $promotions = [])
    {
        if ($priceData['coupon_id'] && $priceData['coupon_price'] ?? 0) {
            $count = 0;
            $total_price = 0.00;
            $compute_price = 0.00;
            $coupon_price = 0.00;
            /** @var StoreCouponUserServices $couponServices */
            $couponServices = app()->make(StoreCouponUserServices::class);
            $couponInfo = $couponServices->getOne(['id' => $priceData['coupon_id']], '*', ['issue']);
            if ($couponInfo) {
                $promotionsList = [];
                if ($promotions) {
                    $promotionsList = array_combine(array_column($promotions, 'id'), $promotions);
                }
                $isOverlay = function ($cart) use ($promotionsList) {
                    $productInfo = $cart['productInfo'] ?? [];
                    if (!$productInfo) {
                        return false;
                    }
                    if (isset($cart['promotions_id']) && $cart['promotions_id']) {
                        foreach ($cart['promotions_id'] as $key => $promotions_id) {
                            $promotions = $promotionsList[$promotions_id] ?? [];
                            if ($promotions && $promotions['promotions_type'] != 4) {
                                $overlay = is_string($promotions['overlay']) ? explode(',', $promotions['overlay']) : $promotions['overlay'];
                                if (!in_array(5, $overlay)) {
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                };
                $type = $couponInfo['applicable_type'] ?? 0;
                $counpon_id = $couponInfo['id'];
                switch ($type) {
                    case 0:
                        foreach ($cartInfo as $cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                            $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                            $count++;
                        }
                        foreach ($cartInfo as &$cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                            if ($count > 1) {
                                $coupon_price = bcmul((string)bcdiv((string)$cart['pay_price'], (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                            } else {
                                $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                            }
                            $cart['coupon_price'] = $coupon_price;
                            $cart['coupon_id'] = $counpon_id;
                            $count--;
                        }
                        break;
                    case 1://品类券
                        /** @var StoreProductCategoryServices $storeCategoryServices */
                        $storeCategoryServices = app()->make(StoreProductCategoryServices::class);
                        $cateGorys = $storeCategoryServices->getAllById((int)$couponInfo['category_id']);
                        if ($cateGorys) {
                            $cateIds = array_column($cateGorys, 'id');
                            foreach ($cartInfo as $cart) {
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                                if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                    $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                                    $count++;
                                }
                            }
                            foreach ($cartInfo as &$cart) {
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                                $cart['coupon_id'] = 0;
                                $cart['coupon_price'] = 0;
                                if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                    if ($count > 1) {
                                        $coupon_price = bcmul((string)bcdiv((string)$cart['pay_price'], (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                        $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                                    } else {
                                        $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                                    }
                                    $cart['coupon_id'] = $counpon_id;
                                    $cart['coupon_price'] = $coupon_price;
                                    $count--;
                                }
                            }
                        }
                        break;
                    case 2://商品劵
                        foreach ($cartInfo as $cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                            if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                                $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                                $count++;
                            }
                        }
                        foreach ($cartInfo as &$cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                            $cart['coupon_id'] = 0;
                            $cart['coupon_price'] = 0;
                            if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                                if ($count > 1) {
                                    $coupon_price = bcmul((string)bcdiv((string)$cart['pay_price'], (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                    $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                                } else {
                                    $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                                }
                                $cart['coupon_id'] = $counpon_id;
                                $cart['coupon_price'] = $coupon_price;
                                $count--;
                            }
                        }
                        break;
                    case 3://品牌券
                        /** @var StoreBrandServices $storeBrandServices */
                        $storeBrandServices = app()->make(StoreBrandServices::class);
                        $brands = $storeBrandServices->getAllById((int)$couponInfo['brand_id']);
                        if ($brands) {
                            $brandIds = array_column($brands, 'id');
                            foreach ($cartInfo as $cart) {
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                                if (isset($cart['productInfo']['brand_id']) && in_array($cart['productInfo']['brand_id'], $brandIds)) {
                                    $total_price = bcadd((string)$total_price, (string)$cart['pay_price'], 2);
                                    $count++;
                                }
                            }
                            foreach ($cartInfo as &$cart) {
                                $cart['coupon_id'] = 0;
                                $cart['coupon_price'] = 0;
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1)) continue;
                                if (isset($cart['productInfo']['brand_id']) && in_array($cart['productInfo']['brand_id'], $brandIds)) {
                                    if ($count > 1) {
                                        $coupon_price = bcmul((string)bcdiv((string)$cart['pay_price'], (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                        $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                                    } else {
                                        $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                                    }
                                    $cart['coupon_id'] = $counpon_id;
                                    $cart['coupon_price'] = $coupon_price;
                                    $count--;
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $cartInfo;
    }

    /**
     * 计算实际佣金
     * @param int $uid
     * @param array $cartInfo
     * @param $userInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computeOrderProductBrokerage(int $uid, array $cartInfo, $userInfo)
    {
        //佣金计算方式
        $brokerageComputeType = sys_config('brokerage_compute_type', 1);
        /** @var AgentLevelServices $agentLevelServices */
        $agentLevelServices = app()->make(AgentLevelServices::class);
        [$storeBrokerageRatio, $storeBrokerageTwo, $spread_uid, $spread_two_uid] = $agentLevelServices->getAgentLevelBrokerage($uid, $userInfo);
        [$storeBrokerageRatio, $storeBrokerageTwo, $staffPercent, $agentPercent, $divisionPercent] = app()->make(DivisionServices::class)->divisionBrokerage($uid, $storeBrokerageRatio, $storeBrokerageTwo);

        // 二级分销开关
        if (sys_config('brokerage_level', 2) == 1) {
            $storeBrokerageTwo = $spread_two_uid = 0;
        }
        foreach ($cartInfo as &$cart) {
            $oneBrokerage = '0';//一级返佣金额
            $twoBrokerage = '0';//二级返佣金额
            $staffBrokerage = '0';//店员返佣金额
            $agentBrokerage = '0';//代理商返佣金额
            $divisionBrokerage = '0';//事业部返佣金额
            $cartNum = (string)$cart['cart_num'] ?? '0';
            if (isset($cart['productInfo']) && isset($cart['is_gift']) && $cart['is_gift'] == 0) {
                $productInfo = $cart['productInfo'];
                if($productInfo['is_brokerage'] == 0){
                    continue;
                }
                //指定返佣金额
                if (isset($productInfo['is_sub']) && $productInfo['is_sub'] == 1) {
                    $oneBrokerage = bcmul((string)($productInfo['attrInfo']['brokerage'] ?? '0'), $cartNum, 2);
                    $twoBrokerage = bcmul((string)($productInfo['attrInfo']['brokerage_two'] ?? '0'), $cartNum, 2);
                } else {
                    $price = 0;
                    switch ($brokerageComputeType) {
                        case 1://售价
                            if (isset($productInfo['attrInfo'])) {
                                $price = bcmul((string)($productInfo['attrInfo']['price'] ?? '0'), $cartNum, 4);
                            } else {
                                $price = bcmul((string)($productInfo['price'] ?? '0'), $cartNum, 4);
                            }
                            break;
                        case 2://实付金额
                            $price = $cart['pay_price'] ?? 0;
                            break;
                        case 3://商品利润
                            $price = bcsub((string)($cart['pay_price'] ?? 0), bcmul((string)($cart['costPrice'] ?? 0), $cartNum, 2), 2);
                            break;
                    }
                    if ($price > 0) {
                        //一级返佣比例 小于等于零时直接返回 不返佣
                        if ($storeBrokerageRatio > 0) {
                            //计算获取一级返佣比例
                            $brokerageRatio = bcdiv($storeBrokerageRatio, 100, 4);
                            $oneBrokerage = bcmul((string)$price, (string)$brokerageRatio, 2);
                        }
                        //二级返佣比例小于等于0 直接返回
                        if ($storeBrokerageTwo > 0) {
                            //计算获取二级返佣比例
                            $brokerageTwo = bcdiv($storeBrokerageTwo, 100, 4);
                            $twoBrokerage = bcmul((string)$price, (string)$brokerageTwo, 2);
                        }
                        $staffBrokerage = bcmul((string)$price, (string)bcdiv($staffPercent, 100, 4), 2);
                        $agentBrokerage = bcmul((string)$price, (string)bcdiv($agentPercent, 100, 4), 2);
                        $divisionBrokerage = bcmul((string)$price, (string)bcdiv($divisionPercent, 100, 4), 2);
                    }
                }
            }
            $cart['one_brokerage'] = $oneBrokerage;
            $cart['two_brokerage'] = $twoBrokerage;
            $cart['division_brokerage'] = $divisionBrokerage;
            $cart['division_agent_brokerage'] = $agentBrokerage;
            $cart['division_staff_brokerage'] = $staffBrokerage;
        }
        return [$cartInfo, [$spread_uid, $spread_two_uid]];
    }

    /**
     * 订单收银台
     * @param int $uid
     * @param string $orderId
     * @param string $type
     * @return bool[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCashierInfo(int $uid, string $orderId, string $type)
    {
        //支付类型开关
        $data = [];
        $data['offline_pay_status'] = 2;
        $data['yue_pay_status'] = 2;
        $data['ali_pay_status'] = (int)sys_config('ali_pay_status');//支付宝支付 1 开启 0 关闭
        $data['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭

        $data['order_id'] = $orderId;
        $data['pay_price'] = '0';
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($uid, ['uid', 'now_money', 'integral']);
        $data['now_money'] = $userInfo['now_money'];
        $data['integral'] = $userInfo['integral'];
        //默认订单取消时间
        $secs = 30;
        switch ($type) {
            case 'order':
                $data['offline_pay_status'] = (int)sys_config('offline_pay_status') ?? (int)2;
                $data['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
                $info = $this->dao->get(['order_id' => $orderId], ['pay_price', 'add_time', 'type', 'pay_postage', 'type']);
                if (!$info) {
                    throw new PayException('您支付的订单不存在');
                }
                /** @var StoreOrderServices $storeOrderServices */
                $storeOrderServices = app()->make(StoreOrderServices::class);
                //系统预设取消订单时间段
                $secs = $storeOrderServices->getOrderCancelTime((int)$info['type']);

                $data['pay_price'] = $info['pay_price'];
                $data['pay_postage'] = $info['pay_postage'];
                $data['offline_postage'] = (int)sys_config('offline_postage', 0);
                break;
            case 'vip':
                /** @var OtherOrderServices $OtherOrderServices */
                $OtherOrderServices = app()->make(OtherOrderServices::class);
                $info = $OtherOrderServices->get(['order_id' => $orderId], ['pay_price', 'add_time']);
                if (!$info) {
                    throw new PayException('您支付的订单不存在');
                }
                $data['pay_price'] = $info['pay_price'];
                break;
            case 'recharge':
                /** @var UserRechargeServices $rechargeServices */
                $rechargeServices = app()->make(UserRechargeServices::class);
                $info = $rechargeServices->get(['order_id' => $orderId], ['price', 'add_time']);
                if (!$info) {
                    throw new PayException('您支付的订单不存在');
                }
                $data['pay_price'] = $info['price'];
                break;
            default:
                throw new PayException('暂不支持其他类型订单支付');
        }
        $time = $secs * 60 * 60 + (int)$info['add_time'];
        if ($time < 0) {
            $time = 0;
        }
        $data['invalid_time'] = $time;
        return $data;
    }
}
