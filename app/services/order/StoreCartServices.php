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

namespace app\services\order;

use app\dao\order\StoreCartDao;
use app\services\activity\discounts\StoreDiscountsProductsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\lottery\LuckLotteryRecordServices;
use app\services\activity\newcomer\StoreNewcomerServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\other\CityAreaServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\product\shipping\ShippingTemplatesNoDeliveryServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserAddressServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\services\CacheService;
use crmeb\traits\OptionTrait;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 *
 * Class StoreCartServices
 * @package app\services\order
 * @mixin StoreCartDao
 */
class StoreCartServices extends BaseServices
{

    use OptionTrait;

    //库存字段比对
    const STOCK_FIELD = 'sum_stock';
    //购物车最大数量
    protected int $maxCartNum = 100;

    /**
     * @var StoreCartDao
     */
    #[Inject]
    protected StoreCartDao $dao;

    /**
     * 获取某个用户下的购物车数量
     * @param array $unique
     * @param int $productId
     * @param int $uid
     * @param string $userKey
     * @return array
     */
    public function getUserCartNums(array $unique, int $productId, int $uid, string $userKey = 'uid')
    {
        $where['is_pay'] = 0;
        $where['is_del'] = 0;
        $where['is_new'] = 0;
        $where['product_id'] = $productId;
        $where[$userKey] = $uid;
        return $this->dao->getUserCartNums($where, $unique);
    }

    /**
     * 计算首单优惠
     * @param int $uid
     * @param array $cartInfo
     * @param array $newcomerArr
     * @return array
     */
    public function computedFirstDiscount(int $uid, array $cartInfo, array $newcomerArr = [])
    {
        $first_order_price = $first_discount = $first_discount_limit = 0;
        if ($uid && $cartInfo) {
            if (!$newcomerArr) {
                /** @var StoreNewcomerServices $newcomerServices */
                $newcomerServices = app()->make(StoreNewcomerServices::class);
                $newcomerArr = $newcomerServices->checkUserFirstDiscount($uid);
            }
            if ($newcomerArr) {//首单优惠
                [$first_discount, $first_discount_limit] = $newcomerArr;
                /** @var StoreOrderComputedServices $orderServices */
                $orderServices = app()->make(StoreOrderComputedServices::class);
                $totalPrice = $orderServices->getOrderSumPrice($cartInfo, 'pay_price', false);//获取订单svip、用户等级优惠之后总金额
                $first_discount =(string)bcdiv($first_discount, '100', 2);
                $first_order_price = (float)bcmul((string)$totalPrice, (string)$first_discount, 2);
                $first_order_price = min($first_order_price, $first_discount_limit, $totalPrice);
            }
        }
        return [$cartInfo, $first_order_price, $first_discount, $first_discount_limit];
    }

    /**
     * 计算商品优惠
     * @param int $uid
     * @param array $valid
     * @param int $couponId
     * @param bool $isCart
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computedProductPromotion(int $uid, array $valid, int $couponId = 0, bool $isCart = false)
    {
        $promotions = $giveCoupon = $giveCartList = $useCoupon = $giveProduct = [];
        $giveIntegral = $couponPrice = $firstOrderPrice = 0;
        /** @var StoreNewcomerServices $newcomerServices */
        $newcomerServices = app()->make(StoreNewcomerServices::class);
        $newcomerArr = $newcomerServices->checkUserFirstDiscount($uid);
        if ($newcomerArr) {//首单优惠
            //计算首单优惠
            [$valid, $firstOrderPrice, $first_discount, $first_discount_limit] = $this->computedFirstDiscount($uid, $valid, $newcomerArr);
        } else {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            //计算相关优惠活动
            $storePromotionsServices->setItem('isVip', $this->getItem('isVip', 0));
            [$valid, $couponPrice, $useCoupon, $promotions, $giveIntegral, $giveCoupon, $giveCartList] = $storePromotionsServices->computedPromotions($uid, $valid, $couponId, $isCart);
            $storePromotionsServices->reset();
            if ($giveCartList) {
                foreach ($giveCartList as $key => $give) {
                    $giveProduct[] = [
                        'promotions_id' => $give['promotions_id'][0] ?? 0,
                        'product_id' => $give['product_id'] ?? 0,
                        'unique' => $give['product_attr_unique'] ?? '',
                        'cart_num' => $give['cart_num'] ?? 1,
                    ];
                }
            }
        }
        return compact('valid', 'couponPrice', 'useCoupon', 'promotions', 'giveCartList', 'giveIntegral', 'giveCoupon', 'giveProduct', 'firstOrderPrice');
    }


    /**
     * 获取用户下的购物车列表
     * @param int $uid
     * @param $cartIds
     * @param bool $new
     * @param array $addr
     * @param int $shipping_type
     * @param int $coupon_id
     * @param bool $isCart
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserProductCartListV1(int $uid, $cartIds, bool $new, array $addr = [], int $shipping_type = 1, int $coupon_id = 0, bool $isCart = false)
    {
        if ($new) {
            $cartIds = $cartIds && is_string($cartIds) ? explode(',', $cartIds) : (is_array($cartIds) ? $cartIds : []);
            $cartInfo = [];
            if ($cartIds) {
                foreach ($cartIds as $key) {
                    $info = CacheService::redisHandler()->get((string)$key);
                    if ($info) {
                        $cartInfo[] = $info;
                    }
                }
            }
        } else {
            $cartInfo = $this->dao->getCartList(['uid' => $uid, 'status' => 1, 'id' => $cartIds], 0, 0, ['productInfo', 'attrInfo']);
        }
        if (!$cartInfo) {
            throw new ValidateException('获取购物车信息失败');
        }
        if ($isCart && $uid) {//购物车页面不验证
            foreach ($cartInfo as $cart) {
                //检查限购
                $this->checkLimit($uid, $cart['product_id'] ?? 0, $cart['cart_num'] ?? 1, true);
            }
        }
        //整合购物车商品数据
        [$cartInfo, $valid, $invalid] = $this->handleCartList($uid, $cartInfo, $addr, $shipping_type);

        $type = array_unique(array_column($cartInfo, 'type'))[0] ?? 0;
        $product_type = array_unique(array_column($cartInfo, 'product_type'))[0] ?? 0;
        $activity_id = array_unique(array_column($cartInfo, 'activity_id'))[0] ?? 0;
        $deduction = ['product_type' => $product_type, 'type' => $type, 'activity_id' => $activity_id];
        $promotions = $giveCoupon = $giveCartList = $useCoupon = $giveProduct = [];
        $giveIntegral = $couponPrice = $firstOrderPrice = 0;

        if (!$deduction['activity_id'] && $deduction['type'] != 6) {
            //计算优惠
            $data = $this->computedProductPromotion($uid, $valid, $coupon_id, $isCart);
            extract($data);
        }
        return compact('cartInfo', 'valid', 'invalid', 'deduction', 'couponPrice', 'useCoupon', 'promotions', 'giveCartList', 'giveIntegral', 'giveCoupon', 'giveProduct', 'firstOrderPrice');
    }

    /**
     * 验证库存
     * @param int $uid
     * @param int $productId
     * @param int $cartNum
     * @param string $unique
     * @param bool $new
     * @param int $type
     * @param int $activity_id
     * @param int $discount_product_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkProductStock(int $uid, int $productId, int $cartNum = 1, string $unique = '', bool $new = false, int $type = 0, int $activity_id = 0, $discount_product_id = 0, bool $is_show = false)
    {
        //验证限量
        $this->checkLimit($uid, $productId, $cartNum, $new);
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $isSet = $this->getItem('is_set', 0);
        $tourist_uid = $this->getItem('tourist_uid', '');
        switch ($type) {
            case 0://普通
                if ($unique == '') {
                    $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
                }
                /** @var StoreProductServices $productServices */
                $productServices = app()->make(StoreProductServices::class);
                $productInfo = $productServices->isValidProduct($productId, $is_show);
                if (!$productInfo) {
                    throw new ValidateException('该商品已下架或删除');
                }
                if ($productInfo['is_vip_product'] && $uid) {
                    /** @var UserServices $userServices */
                    $userServices = app()->make(UserServices::class);
                    if (!$userServices->checkUserIsSvip($uid)) {
                        throw new ValidateException('该商品为付费会员专享商品');
                    }
                }
                //预售商品
                if ($productInfo['is_presale_product']) {
                    if ($productInfo['presale_start_time'] > time()) throw new ValidateException('预售活动未开始');
                    if ($productInfo['presale_end_time'] < time()) throw new ValidateException('预售活动已结束');
                }

                $attrInfo = $attrValueServices->getOne(['unique' => $unique, 'type' => 0]);
                if (!$unique || !$attrInfo || $attrInfo['product_id'] != $productId) {
                    throw new ValidateException('请选择有效的商品属性');
                }
                $nowStock = $attrInfo['stock'];//现有平台库存
                if ($cartNum > $nowStock) {
                    throw new ValidateException('该商品库存不足' . $cartNum);
                }
                //直接设置购物车商品数量
                if ($isSet) {
                    $stockNum = 0;
                } else {
                    $stockNum = $this->dao->value(['product_id' => $productId, 'product_attr_unique' => $unique, 'uid' => $uid, 'status' => 1, 'tourist_uid' => $tourist_uid], 'cart_num') ?: 0;
                }
                if ($nowStock < ($cartNum + $stockNum)) {
                    $surplusStock = $nowStock - $cartNum;//剩余库存
                    if ($surplusStock < $stockNum) {
                        $this->dao->update(['product_id' => $productId, 'product_attr_unique' => $unique, 'uid' => $uid, 'status' => 1, 'tourist_uid' => $tourist_uid], ['cart_num' => $surplusStock]);
                    }
                }
                break;
            case 1://秒杀
                /** @var StoreSeckillServices $seckillService */
                $seckillService = app()->make(StoreSeckillServices::class);
                [$attrInfo, $unique, $productInfo] = $seckillService->checkSeckillStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 2://砍价
                /** @var StoreBargainServices $bargainService */
                $bargainService = app()->make(StoreBargainServices::class);
                [$attrInfo, $unique, $productInfo, $bargainUserInfo] = $bargainService->checkBargainStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 3://拼团
                /** @var StoreCombinationServices $combinationService */
                $combinationService = app()->make(StoreCombinationServices::class);
                [$attrInfo, $unique, $productInfo] = $combinationService->checkCombinationStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 4://积分
                /** @var StoreIntegralServices $integralServices */
                $integralServices = app()->make(StoreIntegralServices::class);
                [$attrInfo, $unique, $productInfo] = $integralServices->checkoutProductStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 5://套餐
                /** @var StoreDiscountsProductsServices $discountProduct */
                $discountProduct = app()->make(StoreDiscountsProductsServices::class);
                [$attrInfo, $unique, $productInfo] = $discountProduct->checkDiscountsStock($uid, $discount_product_id, $cartNum, $unique);
                break;
            case 7://新人专享
                if ($cartNum > 1) {
                    throw new ValidateException('新人专享商品限购一件');
                }
                /** @var StoreNewcomerServices $newcomerServices */
                $newcomerServices = app()->make(StoreNewcomerServices::class);
                [$attrInfo, $unique, $productInfo] = $newcomerServices->checkNewcomerStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 8://抽奖
                if (!$activity_id) {
                    throw new ValidateException('缺少中奖信息，请返回刷新重试');
                }
                /** @var LuckLotteryRecordServices $luckRecordServices */
                $luckRecordServices = app()->make(LuckLotteryRecordServices::class);
                $record = $luckRecordServices->get($activity_id);
                if (!$record) {
                    throw new ValidateException('缺少中奖信息，请返回刷新重试');
                }
                if ($record['oid']) {
                    throw new ValidateException('已经领取成功，不要重复领取');
                }
                if ($unique == '') {
                    $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
                }
                /** @var StoreProductServices $productServices */
                $productServices = app()->make(StoreProductServices::class);
                $productInfo = $productServices->isValidProduct($productId,true);
                if (!$productInfo) {
                    throw new ValidateException('该商品已删除');
                }
                $attrInfo = $attrValueServices->getOne(['unique' => $unique, 'type' => 0]);
                if (!$unique || !$attrInfo || $attrInfo['product_id'] != $productId) {
                    throw new ValidateException('请选择有效的商品属性');
                }
                $nowStock = $attrInfo['stock'];//现有平台库存
                if ($cartNum > $nowStock) {
                    throw new ValidateException('该商品库存不足' . $cartNum);
                }
                break;
            default:
                throw new ValidateException('请刷新后重试');
                break;
        }
        if (in_array($type, [1, 2, 3, 4])) {
            //根商品规格库存
            $product_stock = $attrValueServices->value(['product_id' => $productInfo['product_id'], 'suk' => $attrInfo['suk'], 'type' => 0], 'stock');
            if ($product_stock < $cartNum) {
                throw new ValidateException('商品库存不足' . $cartNum);
            }
            if (!CacheService::checkStock($unique, (int)$cartNum, $type)) {
                throw new ValidateException('商品库存不足' . $cartNum . ',无法购买请选择其他商品!');
            }
        }
        return [$attrInfo, $unique, $bargainUserInfo['bargain_price_min'] ?? 0, $cartNum, $productInfo];
    }

    /**
     * 添加购物车
     * @param int $uid
     * @param int $product_id
     * @param int $cart_num
     * @param string $product_attr_unique
     * @param int $type
     * @param bool $new
     * @param int $activity_id
     * @param int $discount_product_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCart(int $uid, int $product_id, int $cart_num = 1, string $product_attr_unique = '', int $type = 0, bool $new = true, int $activity_id = 0, int $discount_product_id = 0)
    {
        if ($cart_num < 1) $cart_num = 1;
        //检测库存限量
        $store_id = 0;
        $staff_id = $this->getItem('staff_id', 0);
        $tourist_uid = $this->getItem('tourist_uid', '');
        $key = $this->getItem('key', '');
        $isSet = $this->getItem('is_set', 0);
        [$attrInfo, $product_attr_unique, $bargainPriceMin, $cart_num, $productInfo] = $this->checkProductStock(
            $uid,
            $product_id,
            $cart_num,
            $product_attr_unique,
            $new,
            $type, $activity_id,
            $discount_product_id
        );
        $product_type = $productInfo['product_type'];
        if ($new) {
            if (!$key) {
                /** @var StoreOrderCreateServices $storeOrderCreateService */
                $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
                $key = $storeOrderCreateService->getNewOrderId((string)$uid);
            }
            //普通订单 && 商品是预售商品 订单类型改为预售订单
            if ($type == 0 && $productInfo['is_presale_product']) {
                $type = 6;
            }
            $info['id'] = $key;
            $info['type'] = $type;
            $info['store_id'] = $store_id;
            $info['tourist_uid'] = $tourist_uid;
            $info['product_type'] = $product_type;
            $info['activity_id'] = $activity_id;
            $info['discount_product_id'] = $discount_product_id;
            $info['product_id'] = $product_id;
            $info['product_attr_unique'] = $product_attr_unique;
            $info['cart_num'] = $cart_num;
            $info['productInfo'] = [];
            if ($productInfo) {
                $info['productInfo'] = is_object($productInfo) ? $productInfo->toArray() : $productInfo;
            }
            $info['attrInfo'] = $attrInfo->toArray();
            $info['productInfo']['attrInfo'] = $info['attrInfo'];
            $info['sum_price'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
            //砍价
            if ($type == 2 && $activity_id) {
                $info['truePrice'] = $bargainPriceMin;
                $info['productInfo']['attrInfo']['price'] = $bargainPriceMin;
            } else {
                $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
            }
            //积分
            if ($type == 4 && $activity_id) {
                $info['integral'] = $info['productInfo']['attrInfo']['integral'] ?? 0;
            } else {
                $info['integral'] = 0;
            }
            //活动商品不参与会员价
            if ($type > 0 && $activity_id) {
                $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? 0;
                $info['vip_truePrice'] = 0;
            }
            if ($type == 8){
                $info['is_luck'] = true;
                $info['truePrice'] = 0;
                $info['vip_truePrice'] = 0;
            }
            $info['trueStock'] = $info['productInfo']['attrInfo']['stock'] ?? 0;
            $info['costPrice'] = $info['productInfo']['attrInfo']['cost'] ?? 0;
            try {
                CacheService::redisHandler()->set($key, $info, 3600);
            } catch (\Throwable $e) {
                throw new ValidateException($e->getMessage());
            }
            return [$key, $cart_num];
        } else {//加入购物车记录
            ProductLogJob::dispatch(['cart', ['uid' => $uid, 'product_id' => $product_id, 'cart_num' => $cart_num]]);
            $cart = $this->dao->getOne(['type' => $type, 'uid' => $uid, 'tourist_uid' => $tourist_uid, 'product_id' => $product_id, 'product_attr_unique' => $product_attr_unique, 'is_del' => 0, 'is_new' => 0, 'is_pay' => 0, 'status' => 1, 'store_id' => $store_id, 'staff_id' => $staff_id]);
            if ($cart) {
                if ($isSet) {//直接修改购物车数量
                    $cart->cart_num = $cart_num;
                } else {
                    $cart->cart_num = $cart_num + $cart->cart_num;
                }
                $cart->add_time = time();
                $cart->save();
                return [$cart->id, $cart->cart_num];
            } else {
                $add_time = time();
                $id = $this->dao->save(compact('uid', 'tourist_uid', 'store_id', 'staff_id', 'product_id', 'product_type', 'cart_num', 'product_attr_unique', 'type', 'activity_id', 'add_time'))->id;
                event('cart.add', [$uid, $tourist_uid, $store_id, $staff_id]);
                return [$id, $cart_num];
            }

        }
    }

    /**
     * @param int $id
     * @param int $number
     * @param int $uid
     * @param int $storeId
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeCashierCartNum(int $id, int $number, int $uid)
    {
        if (!$id || !$number) return false;
        $where = ['uid' => $uid, 'id' => $id];
        $carInfo = $this->dao->getOne($where, 'product_id,product_attr_unique,cart_num');
        /** @var StoreBranchProductServices $storeProduct */
        $storeProduct = app()->make(StoreBranchProductServices::class);
        $stock = $storeProduct->getProductStock($carInfo->product_id, 0, $carInfo->product_attr_unique);
        if (!$stock) throw new ValidateException('暂无库存');
        if ($stock < $number) throw new ValidateException('库存不足' . $number);
        if ($carInfo->cart_num == $number) return true;
        $this->setItem('is_set', 1);
        $this->checkProductStock($uid, (int)$carInfo->product_id, $number, $carInfo->product_attr_unique, true);
        $this->reset();
        return $this->dao->changeUserCartNum(['uid' => $uid, 'id' => $id], (int)$number);
    }

    /**
     * 移除购物车商品
     * @param int $uid
     * @param array $ids
     * @return bool
     */
    public function removeUserCart(int $uid, array $ids)
    {
        return $this->dao->removeUserCart($uid, $ids) !== false;
    }

    /**
     * 购物车 修改商品数量
     * @param $id
     * @param $number
     * @param $uid
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserCartNum($id, $number, $uid)
    {
        if (!$id || !$number) return false;
        $where = ['uid' => $uid, 'id' => $id];
        $carInfo = $this->dao->getOne($where, 'product_id,type,activity_id,product_attr_unique,cart_num');
        if (!$carInfo) {
            throw new ValidateException('数据错误，请刷新页面');
        }
        /** @var StoreProductServices $StoreProduct */
        $StoreProduct = app()->make(StoreProductServices::class);
        $stock = $StoreProduct->getProductStock($carInfo->product_id, $carInfo->product_attr_unique);
        if (!$stock) throw new ValidateException('暂无库存');
        if (!$number) throw new ValidateException('库存错误');
        if ($stock < $number) throw new ValidateException('库存不足' . $number);
        if ($carInfo->cart_num == $number) return true;
        $this->checkProductStock($uid, (int)$carInfo->product_id, (int)$number, $carInfo->product_attr_unique, true);
        return $this->dao->changeUserCartNum(['uid' => $uid, 'id' => $id], (int)$number);
    }

    /**
     * 修改购物车状态
     * @param int $productId
     * @param int $status 0 商品下架
     */
    public function changeStatus($productId, $status = 0)
    {
        $this->dao->search(['product_id' => $productId])->update(['status' => $status]);
    }

    /**
     * 获取购物车列表
     * @param int $uid
     * @param int $status
     * @param array $cartIds
     * @param int $shipping_type
     * @param int $numType
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartList(int $uid, int $status, array $cartIds = [], int $shipping_type = -1, int $numType = 0, bool $new = false)
    {
        // [$page, $limit] = $this->getPageValue();
        $touristUid = $this->getItem('tourist_uid', 0);
        $staff_id = $this->getItem('staff_id', 0);
        if ($new) {
            $cartIds = $cartIds && is_string($cartIds) ? explode(',', $cartIds) : (is_array($cartIds) ? $cartIds : []);
            $list = [];
            if ($cartIds) {
                foreach ($cartIds as $key) {
                    $info = CacheService::redisHandler()->get($key);
                    if ($info) {
                        $list[] = $info;
                    }
                }
            }
        } else {
            $where = ['uid' => $uid, 'tourist_uid' => $touristUid, 'cart_ids' => $cartIds];
            //有店员就证明在收银台中
            if ($staff_id) {
                $where['staff_id'] = $staff_id;
            }
            if ($status != -1) $where = array_merge($where, ['status' => $status]);

            $list = $this->dao->getCartList($where, 0, 0, ['productInfo', 'attrInfo']);
        }

        $count = $promotionsPrice = $coupon_price = $firstOrderPrice = 0;
        $cartList = $valid = $promotions = $coupon = $invalid = $type = $activity_id = [];
        if ($list) {
            [$list, $valid, $invalid] = $this->handleCartList($uid, $list, [], $shipping_type);
            $type = array_unique(array_column($list, 'type'));
            $activity_id = array_unique(array_column($list, 'activity_id'));
            if (!($activity_id[0] ?? 0) && ($type[0] ?? 0) != 6) {
                $data = $this->computedProductPromotion($uid, $valid, 0, true);
                extract($data);
                $cartList = array_merge($valid, $giveCartList);
                foreach ($cartList as $key => $cart) {
                    if (isset($cart['promotions_true_price']) && isset($cart['price_type']) && $cart['price_type'] == 'promotions') {
                        $promotionsPrice = bcadd((string)$promotionsPrice, (string)bcmul((string)$cart['promotions_true_price'], (string)$cart['cart_num'], 2), 2);
                    }
                }
            }
            if ($numType) {
                $count = count($valid);
            } else {
                $count = array_sum(array_column($valid, 'cart_num'));
            }
        }
        $deduction = ['type' => $type[0] ?? 0, 'activity_id' => $activity_id[0] ?? 0];
        $deduction['promotions_price'] = $promotionsPrice;
        $deduction['coupon_price'] = $coupon_price;
        $deduction['first_order_price'] = $firstOrderPrice;

        $user_store_id = $this->getItem('store_id', 0);
        $invalid_key = 'invalid_' . $user_store_id . '_' . $uid;
        //写入缓存
        if ($status == 1 && $invalid) {
            CacheService::redisHandler()->set($invalid_key, $invalid, 60);
        }
        //读取缓存
        if ($status == 0) {
            $other_invalid = CacheService::redisHandler()->get($invalid_key);
            if ($other_invalid) $invalid = array_merge($invalid, $other_invalid);
        }

        return ['promotions' => $promotions, 'coupon' => $coupon, 'valid' => $valid, 'invalid' => $invalid, 'deduction' => $deduction, 'count' => $count];
    }

    /**
     * 购物车重选
     * @param int $cart_id
     * @param int $product_id
     * @param string $unique
     */
    public function modifyCart(int $cart_id, int $product_id, string $unique)
    {
        /** @var StoreProductAttrValueServices $attrService */
        $attrService = app()->make(StoreProductAttrValueServices::class);
        $stock = $attrService->value(['product_id' => $product_id, 'unique' => $unique, 'type' => 0], 'stock');
        if ($stock > 0) {
            $this->dao->update($cart_id, ['product_attr_unique' => $unique, 'cart_num' => 1]);
        } else {
            throw new ValidateException('选择的规格库存不足');
        }
    }

    /**
     * 重选购物车
     * @param int $id
     * @param int $uid
     * @param int $productId
     * @param string $unique
     * @param int $num
     * @param int $store_id
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetCart(int $id, int $uid, int $productId, string $unique, int $num, int $store_id = 0)
    {
        $res = $this->dao->getOne(['uid' => $uid, 'product_id' => $productId, 'product_attr_unique' => $unique, 'store_id' => $store_id]);
        if ($res) {
            /** @var StoreProductServices $StoreProduct */
            $StoreProduct = app()->make(StoreProductServices::class);
            $stock = $StoreProduct->getProductStock((int)$productId, $unique);
            $cart_num = $res->cart_num + $num;
            if ($cart_num > $stock) {
                $cart_num = $stock;
            }
            $res->cart_num = $cart_num;
            $res->save();
            if ($res['id'] != $id) $this->dao->delete($id);
        } else {
            $this->dao->update($id, ['product_attr_unique' => $unique, 'cart_num' => $num]);
        }
    }

    /**
     * 首页加入购物车
     * @param int $uid
     * @param int $productId
     * @param int $num
     * @param string $unique
     * @param int $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCartNum(int $uid, int $productId, int $num, string $unique, int $type)
    {
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);

        if ($unique == '') {
            $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
        }
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $productInfo = $productServices->isValidProduct((int)$productId);
        if (!$productInfo) {
            throw new ValidateException('该商品已下架或删除');
        }
        if (!($unique && $attrValueServices->getAttrvalueCount($productId, $unique, 0))) {
            throw new ValidateException('请选择有效的商品属性');
        }
        $stock = $productServices->getProductStock((int)$productId, $unique);
        if ($stock < $num) {
            throw new ValidateException('该商品库存不足' . $num);
        }
        //预售商品
        if ($productInfo['is_presale_product']) {
            if ($productInfo['presale_start_time'] > time()) throw new ValidateException('预售活动未开始');
            if ($productInfo['presale_end_time'] < time()) throw new ValidateException('预售活动已结束');
        }
        //检查限购
        if ($type != 0) $this->checkLimit($uid, $productId, $num);

        $cart = $this->dao->getOne(['uid' => $uid, 'product_id' => $productId, 'product_attr_unique' => $unique, 'store_id' => 0]);
        if ($cart) {
            if ($type == -1) {
                $cart->cart_num = $num;
            } elseif ($type == 0) {
                $cart->cart_num = $cart->cart_num - $num;
            } elseif ($type == 1) {
                if ($cart->cart_num >= $stock) {
                    throw new ValidateException('该商品库存只有' . $stock);
                }
                $new_cart_num = $cart->cart_num + $num;
                if ($new_cart_num > $stock) {
                    $new_cart_num = $stock;
                }
                $cart->cart_num = $new_cart_num;
            }
            if ($cart->cart_num === 0) {
                return $this->dao->delete($cart->id);
            } else {
                $cart->add_time = time();
                $cart->save();
                return $cart->id;
            }
        } else {
            $data = [
                'uid' => $uid,
                'product_id' => $productId,
                'product_type' => $productInfo['product_type'],
                'cart_num' => $num,
                'product_attr_unique' => $unique,
                'type' => 0,
                'add_time' => time()
            ];
            $id = $this->dao->save($data)->id;
            event('cart.add', [$uid, 0, 0, 0]);
            return $id;
        }
    }

    /**
     *    购物车数量统计
     * @param int $uid
     * @param string $numType
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartCount(int $uid, string $numType = '0', int $store_id = 0)
    {
        $count = 0;
        $ids = [];
        $sum_price = 0;
        $cartList = $this->dao->getUserCartList(['uid' => $uid, 'status' => 1], 'id,cart_num');
        if ($cartList) {
            $ids = array_column($cartList, 'id');
            if ($numType) {
                $count = count($cartList);
            } else {
                $count = array_sum(array_column($cartList, 'cart_num'));
            }
        }
        return compact('count', 'ids', 'sum_price');
    }

    /**
     * 处理购物车数据
     * @param int $uid
     * @param array $cartList
     * @param array $addr
     * @param int $shipping_type
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handleCartList(int $uid, array $cartList, array $addr = [], int $shipping_type = 1, int $store_id = 0)
    {
        if (!$cartList) {
            return [$cartList, [], [], [], 0, [], []];
        }
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var MemberCardServices $memberCardService */
        $memberCardService = app()->make(MemberCardServices::class);
        $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false);
        $tempIds = [];
        $userInfo = [];
        $discount = 100;
        if ($uid) {
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            $userInfo = $user->getUserCacheInfo($uid);
            //用户等级是否开启
            if (sys_config('member_func_status', 1) && $userInfo) {
                /** @var SystemUserLevelServices $systemLevel */
                $systemLevel = app()->make(SystemUserLevelServices::class);
                $discount = $systemLevel->getDiscount($uid, (int)$userInfo['level']);
            }
        }
        //不送达运费模板
        if ($shipping_type == 1 && $addr) {
            $cityId = (int)($addr['city_id'] ?? 0);
            if ($cityId) {
                /** @var CityAreaServices $cityAreaServices */
                $cityAreaServices = app()->make(CityAreaServices::class);
                $cityIds = $cityAreaServices->getRelationCityIds($cityId);
                foreach ($cartList as $item) {
                    if (isset($item['productInfo']['temp_id']) && $item['productInfo']['temp_id']) {
                        $tempIds[] = $item['productInfo']['temp_id'];
                    }
                }
                $tempIds = array_unique($tempIds);
                if ($tempIds) {
                    /** @var ShippingTemplatesServices $shippingService */
                    $shippingService = app()->make(ShippingTemplatesServices::class);
                    $tempIds = $shippingService->getColumn([['id', 'in', $tempIds], ['no_delivery', '=', 1]], 'id');
                    if ($tempIds) {
                        /** @var ShippingTemplatesNoDeliveryServices $noDeliveryServices */
                        $noDeliveryServices = app()->make(ShippingTemplatesNoDeliveryServices::class);
                        $tempIds = $noDeliveryServices->isNoDelivery($tempIds, $cityIds);
                    }
                }
            }
        }

        $valid = $invalid = [];
        /** @var StoreProductLabelServices $storeProductLabelServices */
        $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
        $productServices->setItem('isVip', $this->getItem('isVip', 0));
        foreach ($cartList as &$item) {
            $item['is_gift'] = 0;
            if (isset($item['productInfo']['delivery_type'])) {
                $item['productInfo']['delivery_type'] = is_string($item['productInfo']['delivery_type']) ? explode(',', $item['productInfo']['delivery_type']) : $item['productInfo']['delivery_type'];
            } else {
                $item['productInfo']['delivery_type'] = [];
            }
            if (isset($item['attrInfo']) && $item['attrInfo'] && (!isset($item['productInfo']['attrInfo']) || !$item['productInfo']['attrInfo'])) {
                $item['productInfo']['attrInfo'] = $item['attrInfo'] ?? [];
            }
            $item['attrStatus'] = isset($item['productInfo']['attrInfo']['stock']) && $item['productInfo']['attrInfo']['stock'];
            $item['productInfo']['attrInfo']['image'] = $item['productInfo']['attrInfo']['image'] ?? $item['productInfo']['image'] ?? '';
            $item['productInfo']['attrInfo']['suk'] = $item['productInfo']['attrInfo']['suk'] ?? '已失效';
            if (isset($item['productInfo']['attrInfo'])) {
                $item['productInfo']['attrInfo'] = get_thumb_water($item['productInfo']['attrInfo']);
            }
            $item['productInfo'] = get_thumb_water($item['productInfo']);
            $productInfo = $item['productInfo'];
            $item['vip_truePrice'] = 0;
            $item['price_type'] = '';
            if (isset($productInfo['attrInfo']['product_id']) && $item['product_attr_unique']) {
                $item['costPrice'] = $productInfo['attrInfo']['cost'] ?? 0;
                $item['trueStock'] = $item['branch_stock'] = $productInfo['attrInfo']['stock'] ?? 0;
                $item['branch_sales'] = $productInfo['attrInfo']['sales'] ?? 0;
                $item['truePrice'] = $productInfo['attrInfo']['price'] ?? 0;
                $item['sum_price'] = $productInfo['attrInfo']['price'] ?? 0;
                $item['vip_price'] = $productInfo['attrInfo']['vip_price'] ?? 0;
                $item['truePrice'] = $item['sum_price'] =  (float)($productInfo['attrInfo']['price'] ?? 0);
                if (!$item['type'] || !$item['activity_id']) {
                    [$truePrice, $vip_truePrice, $type] = $productServices->setLevelPrice($productInfo['attrInfo']['price'] ?? 0, $uid, $userInfo, $vipStatus, $discount, $productInfo['attrInfo']['vip_price'] ?? 0, $productInfo['is_vip'] ?? 0, true,['level_type'=>$productInfo['level_type'] ?? 1,'level_price'=>$productInfo['attrInfo']['level_price'] ?? '']);
                    $item['truePrice'] = $truePrice;
                    $item['vip_truePrice'] = $vip_truePrice;
                    $item['price_type'] = $type;
                }
                if($item['type'] == 8){
                    $item['truePrice'] = 0;
                }
            } else {
                $item['costPrice'] = $productInfo['cost'] ?? 0;
                $item['trueStock'] = $item['branch_sales'] = $productInfo['stock'] ?? 0;
                $item['branch_sales'] = $productInfo['sales'] ?? 0;
                $item['vip_price'] = $productInfo['vip_price'] ?? 0;
                $item['truePrice'] = $item['sum_price'] = (float)($productInfo['price'] ?? 0);
                if (!$item['type'] || !$item['activity_id']) {
                    [$truePrice, $vip_truePrice, $type] = $productServices->setLevelPrice($item['productInfo']['price'] ?? 0, $uid, $userInfo, $vipStatus, $discount, $item['productInfo']['vip_price'] ?? 0, $item['productInfo']['is_vip'] ?? 0, true);
                    $item['truePrice'] = $truePrice;
                    $item['vip_truePrice'] = $vip_truePrice;
                    $item['price_type'] = $type;
                }
            }
            $item['pay_price'] = bcmul((string)$item['truePrice'], (string)$item['cart_num'], 2);
            $item['productInfo']['store_label'] = [];
            if (isset($item['productInfo']['store_label_id']) && $item['productInfo']['store_label_id']) {
                $item['productInfo']['store_label'] = $storeProductLabelServices->getLabelCache($item['productInfo']['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
            }
            unset($item['attrInfo']);
            if (isset($item['status']) && $item['status'] == 0) {
                $item['is_valid'] = 0;
                $item['invalid_desc'] = '此商品已失效';
                $invalid[] = $item;
            } elseif ((isset($item['productInfo']['delivery_type']) && !$item['productInfo']['delivery_type']) || in_array($item['productInfo']['product_type'], [1, 2, 3])) {
                $item['is_valid'] = 1;
                $valid[] = $item;
            } else {
                switch ($shipping_type) {
                    case -1://购物车展示
                        $item['is_valid'] = 1;
                        $valid[] = $item;
                        break;
                    case 1:
                        //不送达
                        if (in_array($item['productInfo']['temp_id'], $tempIds) || (isset($item['productInfo']['delivery_type']) && !in_array(1, $item['productInfo']['delivery_type']) && !in_array(3, $item['productInfo']['delivery_type']))) {
                            $item['is_valid'] = 0;
                            $item['invalid_desc'] = '此商品超出配送/自提范围';
                            $invalid[] = $item;
                        } else {
                            $item['is_valid'] = 1;
                            $valid[] = $item;
                        }
                        break;
                    case 2:
                        //不支持到店自提
                        if (isset($item['productInfo']['delivery_type']) && $item['productInfo']['delivery_type'] && !in_array(2, $item['productInfo']['delivery_type'])) {
                            $item['is_valid'] = 0;
                            $item['invalid_desc'] = '此商品超出配送/自提范围';
                            $invalid[] = $item;
                        } elseif ($item['productInfo']['product_type'] == 1) {
                            $item['is_valid'] = 0;
                            $item['invalid_desc'] = '此商品超出配送/自提范围';
                            $invalid[] = $item;
                        } else {
                            $item['is_valid'] = 1;
                            $valid[] = $item;
                        }
                        break;
                    default:
                        $item['is_valid'] = 1;
                        $valid[] = $item;
                        break;
                }
            }
        }
        $productServices->reset();
        return [$cartList, $valid, $invalid];
    }


    /**
     * 组合前端购物车需要的数据结构
     * @param array $cartList
     * @param array $protmoions
     * @return array
     */
    public function getReturnCartList(array $cartList, array $promotions)
    {
        $result = [];
        if ($cartList) {
            if ($promotions) $promotions = array_combine(array_column($promotions, 'id'), $promotions);
            $i = 0;
            foreach ($cartList as $key => $cart) {
                $data = ['promotions' => [], 'pids' => [], 'cart' => []];
                if ($result && isset($cart['promotions_id']) && $cart['promotions_id']) {
                    $isTure = false;
                    foreach ($result as $key => &$res) {
                        if (array_intersect($res['pids'], $cart['promotions_id'])) {
                            $res['pids'] = array_unique(array_merge($res['pids'], $cart['promotions_id'] ?? []));
                            $res['cart'][] = $cart;
                            $isTure = true;
                            break;
                        }
                    }
                    if (!$isTure) {
                        $data['cart'][] = $cart;
                        $data['pids'] = array_unique($cart['promotions_id'] ?? []);
                        $result[$i] = $data;
                        $i++;
                    }
                } else {
                    $data['cart'][] = $cart;
                    $data['pids'] = array_unique($cart['promotions_id'] ?? []);
                    $result[$i] = $data;
                    $i++;
                }
            }

            foreach ($result as $key => &$item) {
                if ($item['pids']) {
                    foreach ($item['pids'] as $key => $id) {
                        $pInfo = $promotions[$id] ?? [];
                        $data = [];
                        if ($pInfo) {
                            $data = [
                                'id' => $pInfo['id'],
                                'promotions_type' => $pInfo['promotions_type'],
                                'threshold_type' => $pInfo['threshold_type'],
                                'type' => $pInfo['type'],
                                'discount_type' => $pInfo['discount_type'],
                                'differ_threshold' => $pInfo['differ_threshold'],
                                'title' => $pInfo['title'],
                                'desc' => $pInfo['desc'],
                                'is_valid' => $pInfo['is_valid'],
                            ];
                        }
                        $item['promotions'][] = $data;
                    }
                }
            }
        }
        return $result;
    }


    /**
     *
     * @param int $uid
     * @param int $tourist_uid
     * @param int $store_id
     * @param int $staff_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function controlCartNum(int $uid, int $tourist_uid = 0, int $store_id = 0, int $staff_id = 0)
    {
        $maxCartNum = $this->maxCartNum;
        $where = [
            'is_del' => 0,
            'is_new' => 0,
            'is_pay' => 0,
            'status' => 1
        ];
        if ($uid) $where['uid'] = $uid;
        if ($tourist_uid) $where['tourist_uid'] = $tourist_uid;
        if ($store_id) $where['store_id'] = $store_id;
        if ($staff_id) $where['staff_id'] = $staff_id;
        try {
            $count = $this->dao->count($where);
            if ($count >= $maxCartNum) {//删除一个最早加入购物车商品
                $one = $this->dao->search($where)->order('id asc')->find();
                if ($one) {
                    $this->dao->delete($one['id']);
                }
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error('自动控制购物车数量，删除最早加入商品失败：' . $e->getMessage());
        }
        return true;
    }

    /**
     * 检测限购
     * @param int $uid
     * @param int $product_id
     * @param int $num
     * @param bool $new
     * @return bool
     */
    public function checkLimit(int $uid, int $product_id, int $num, bool $new = false)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $limitInfo = $productServices->getCacheProductInfo($product_id);
        if (!$limitInfo) {
            throw new ValidateException('商品不存在');
        }
        if (!$limitInfo['is_limit']) {//不限购
            return true;
        }
        $cartNum = 0;
        if (!$new) {
            $cartNum = $this->dao->sum(['uid' => $uid, 'product_id' => $product_id, 'status' => 1, 'is_del' => 0], 'cart_num', true);
        }
        if ($limitInfo['limit_type'] == 1) {
            if (($num + $cartNum) > $limitInfo['limit_num']) {
                throw new ValidateException('单次购买不能超过 ' . $limitInfo['limit_num'] . ' 件');
            }
        } else if ($limitInfo['limit_type'] == 2) {
            /** @var StoreOrderCartInfoServices $orderCartServices */
            $orderCartServices = app()->make(StoreOrderCartInfoServices::class);
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            //取消购买限购数量
            $orderDelNum = $storeOrderServices->search(['paid' => 0, 'is_del' => 1])->column('id');
			//购买数量
            $orderPayNum = $orderCartServices->search(['uid' => $uid, 'product_id' => $product_id],false)
                ->when($orderDelNum, function ($query) use ($orderDelNum) {
                    $query->whereNotIn('oid', $orderDelNum);
                })->sum('cart_num');
			//退款数量
            $orderRefundNum = $orderCartServices->sum(['uid' => $uid, 'product_id' => $product_id], 'refund_num');
            $orderNum = $cartNum + $orderPayNum - $orderRefundNum;
            if (($num + $orderNum) > $limitInfo['limit_num']) {
                throw new ValidateException('该商品限购 ' . $limitInfo['limit_num'] . ' 件，您已经购买 ' . $orderNum . ' 件');
            }
        }
        return true;
    }

    /**
     * 计算用户购物车商品（优惠活动、最优优惠券）
     * @param int $uid
     * @param $cartId
     * @param int $shipping_type
     * @param bool $new
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computeUserCart(int $uid, $cartId, int $shipping_type = 1, bool $new = false)
    {
        $data = [];
        //获取购物车信息
        $cartGroup = $this->getUserProductCartListV1($uid, $cartId, $new, [], $shipping_type, 0, true);
        $valid = $cartGroup['valid'] ?? [];
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $sumPrice = $computedServices->getOrderSumPrice($valid, 'sum_price');//获取订单原总金额
        $totalPrice = $computedServices->getOrderSumPrice($valid, 'pay_price', false);//获取订单svip、用户等级优惠之后总金额
        $vipPrice = $computedServices->getOrderSumPrice($valid, 'vip_truePrice');//获取订单会员优惠金额

        $deduction = $cartGroup['deduction'] ?? [];
        $coupon = $cartGroup['useCoupon'] ?? [];
        $promotions = [];
        $giveCartList = $cartGroup['giveCartList'] ?? [];
        $couponPrice = $cartGroup['couponPrice'] ?? 0;
        $firstOrderPrice = $cartGroup['firstOrderPrice'];

        $cartList = array_merge($valid, $giveCartList);
        $promotionsPrice = 0;
        if ($cartList) {
            foreach ($cartList as $key => $cart) {
                if (isset($cart['promotions_true_price']) && isset($cart['price_type']) && $cart['price_type'] == 'promotions') {
                    $promotionsPrice = bcadd((string)$promotionsPrice, (string)bcmul((string)$cart['promotions_true_price'], (string)$cart['cart_num'], 2), 2);
                }
            }
        }
        $deduction['promotions_price'] = (float)$promotionsPrice;
        $deduction['coupon_price'] = (float)$couponPrice;
        $deduction['first_order_price'] = (float)$firstOrderPrice;
        $deduction['sum_price'] = (float)$sumPrice;
        $deduction['vip_price'] = (float)$vipPrice;

        $payPrice = $this->getPayPrice((string)$totalPrice, (string)$couponPrice, (string)$firstOrderPrice);
        $deduction['pay_price'] = (float)$payPrice;

        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $svip_status = $userServices->checkUserIsSvip($uid);
        $svip_price = 0.00;
        //开启付费会员 且用户不是付费会员 //计算 开通付费会员节省金额
        if (sys_config('member_card_status', 1) && !$svip_status) {
            [$vipPayPrice, $payPostage, $storePostageDiscount] = $this->computeUserVipCart($uid, $cartId, $shipping_type, $new);
            $svip_price = (float)max(bcsub((string)$payPrice, (string)$vipPayPrice, 2), 0);
        }
        return compact('promotions', 'coupon', 'deduction', 'svip_status', 'svip_price');
    }

    /**
     * 计算用户是付费会员节省多少钱
     * @param int $uid
     * @param $cartId
     * @param int $shipping_type
     * @param bool $new
     * @param bool $isCart
     * @param int $couponId
     * @param $addr
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computeUserVipCart(int $uid, $cartId, int $shipping_type = 1, bool $new = false, bool $isCart = true, int $couponId = 0, $addr = [])
    {
        //获取购物车信息
        $this->setItem('isVip', 1);
        $cartGroup = $this->getUserProductCartListV1($uid, $cartId, $new, [], $shipping_type, $couponId, $isCart);
        $this->reset();
        $valid = $cartGroup['valid'] ?? [];
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $totalPrice = $computedServices->getOrderSumPrice($valid, 'truePrice');//获取订单svip、用户等级优惠之后总金额

        $payPrice = $this->getPayPrice((string)$totalPrice, (string)($cartGroup['couponPrice'] ?? 0), (string)($cartGroup['firstOrderPrice'] ?? 0));
        //不是购物车 计算运费
        if (!$isCart) {
            //没传地址id或地址已删除未找到 ||获取默认地址
            if (!$addr) {
                /** @var UserAddressServices $addressServices */
                $addressServices = app()->make(UserAddressServices::class);
                $addr = $addressServices->getUserDefaultAddressCache($uid);
            }
            $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;//满额包邮金额
            $priceGroup = $computedServices->getOrderPriceGroup($uid, $valid, $addr, $storeFreePostage);
        }
        return [$payPrice, $priceGroup['payPostage'] ?? 0, $priceGroup['storePostageDiscount'] ?? 0];
    }

    /**
     * 计算实际支付金额
     * @param string $payPrice
     * @param string $couponPrice
     * @param string $firstOrderPrice
     * @return float
     */
    public function getPayPrice(string $payPrice, string $couponPrice, string $firstOrderPrice)
    {
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
        return (float)$payPrice;
    }


}
