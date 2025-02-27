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

use app\jobs\system\CapitalFlowJob;
use app\services\activity\lottery\LuckLotteryRecordServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\user\UserExtractServices;
use app\services\user\UserRechargeServices;
use app\services\user\UserServices;

/**
 * 提现回调
 * Class PayNotifyServices
 * @package app\services\pay
 */
class PayMchNotifyServices
{

    /**
     * 提现
     * @param string|null $order_id 订单id
     * @return bool
     */
    public function wechatTx(string $order_id = null, string $trade_no = null, string $state = null, string $fail_reason = null)
    {
        try {
            $services = app()->make(UserExtractServices::class);
            $userExtract = $services->getOne(['transfer_bill_no' => $trade_no]);
            if (!$userExtract) {
                return true;
            }
            $userExtract->wechat_state = $state;
            if ($fail_reason) {
                $userExtract->fail_reason = $fail_reason;
            }
            $userExtract->save();
            if ($state == 'SUCCESS') {
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $userType = $userServices->value(['uid' => $userExtract['uid']], 'user_type');
                $nickname = $userServices->value(['uid' => $userExtract['uid']], 'nickname');
                $phone = $userServices->value(['uid' => $userExtract['uid']], 'phone');

                //记录资金流水队列
                CapitalFlowJob::dispatch([['order_id' => $userExtract['id'], 'store_id' => 0, 'uid' => $userExtract['uid'], 'nickname' => $nickname, 'phone' => $phone, 'price' => $userExtract['extract_price'], 'pay_type' => $userExtract['extract_type']], 'extract']);

                //消息推送
                event('notice.notice', [['uid' => $userExtract['uid'], 'userType' => strtolower($userType), 'extractNumber' => $userExtract['extract_price'], 'nickname' => $nickname], 'user_extract']);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * 红包
     * @param string|null $order_id 订单id
     * @return bool
     */
    public function wechatHb(string $order_id = null, string $trade_no = null, string $state = null, string $fail_reason = null)
    {
        try {
            $info = app()->make(LuckLotteryRecordServices::class)->get(['transfer_bill_no' => $trade_no]);
            if (!$info) {
                return true;
            }
            $info->wechat_state = $state;
            if ($fail_reason) {
                $info->fail_reason = $fail_reason;
            }
            $info->save();
        } catch (\Exception $e) {
            return false;
        }
    }

}
