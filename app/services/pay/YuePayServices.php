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

namespace app\services\pay;

use app\services\BaseServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 余额支付
 * Class YuePayServices
 * @package app\services\pay
 */
class YuePayServices extends BaseServices
{

    /**
     * 订单余额支付
     * @param array $orderInfo
     * @param $uid
     * @return bool[]|string[]
     */
    public function yueOrderPay(array $orderInfo, $uid)
    {
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }

        if ($orderInfo['paid']) {
            throw new ValidateException('该订单已支付!');
        }
        $type = 'pay_product';
        if (isset($orderInfo['member_type'])) {
            $type = 'pay_member';
        }
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->getUserInfo($uid);
        if ($userInfo['now_money'] < $orderInfo['pay_price']) {
            return ['status' => 'pay_deficiency', 'msg' => '余额不足' . floatval($orderInfo['pay_price'])];
        }

        $this->transaction(function () use ($services, $orderInfo, $userInfo, $type) {
            $res = false !== $services->bcDec($userInfo['uid'], 'now_money', $orderInfo['pay_price'], 'uid');
            switch ($type) {
                case 'pay_product'://商品余额
                    $id = $orderInfo['id'] ?? 0;
                    /** @var StoreOrderServices $orderSerives */
                    $orderSerives = app()->make(StoreOrderServices::class);
                    $orderInfo = $orderSerives->get($id);
                    if (!$orderInfo) {
                        throw new ValidateException('订单不存在');
                    }
                    $orderInfo = $orderInfo->toArray();
                    /** @var StoreOrderCartInfoServices $cartInfoServices */
                    $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
                    try {
                        $storeName = $cartInfoServices->getCarIdByProductTitle($id);
                        $arr = explode('|', $storeName);
                        $num = count($arr);
                        if ($num > 1) {
                            $title = '购买' . substrUTf8($arr[0], 9, 'UTF-8', '') . '等';
                        } else {
                            $title = '购买' . substrUTf8($storeName, 10, 'UTF-8', '');
                        }
                    } catch (\Exception $e) {
                        $title = '';
                    }

                    //写入余额记录
                    $now_money = bcsub((string)$userInfo['now_money'], (string)$orderInfo['pay_price'], 2);
                    $number = $orderInfo['pay_price'];
                    /** @var UserMoneyServices $userMoneyServices */
                    $userMoneyServices = app()->make(UserMoneyServices::class);
                    $res = $res && $userMoneyServices->income('pay_product', $userInfo['uid'], $number, $now_money, $orderInfo['id'], $title);
                    /** @var StoreOrderSuccessServices $orderServices */
                    $orderServices = app()->make(StoreOrderSuccessServices::class);
                    $res = $res && $orderServices->paySuccess($orderInfo, PayServices::YUE_PAY, ['userInfo' => $userInfo]);//余额支付成功
                    break;
                case 'pay_member'://会员卡支付
                    /** @var OtherOrderServices $OtherOrderServices */
                    $OtherOrderServices = app()->make(OtherOrderServices::class);
                    $res = $res && $OtherOrderServices->paySuccess($orderInfo, PayServices::YUE_PAY, ['userInfo' => $userInfo]);//余额支付成功
                    break;
            }
            if (!$res) {
                throw new ValidateException('余额支付失败!');
            }
        });
        return ['status' => true];
    }

}
