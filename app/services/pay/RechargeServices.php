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

namespace app\services\pay;


use app\services\user\UserRechargeServices;
use app\services\wechat\WechatUserServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 *
 * Class RechargeServices
 * @package app\services\pay
 */
class RechargeServices
{
    /**
     * @var PayServices
     */
    #[Inject]
    protected PayServices $pay;

    /**
     * 充值订单支付
     * @param string $order_id
     * @param string $payType
     * @return \Alipay\EasySDK\Payment\Wap\Models\AlipayTradeWapPayResponse|array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function recharge(string $order_id, string $payType = 'weixin',string $from = 'weixin')
    {
        /** @var UserRechargeServices $rechargeServices */
        $rechargeServices = app()->make(UserRechargeServices::class);
        $recharge = $rechargeServices->getOne(['order_id' => $order_id]);
        if (!$recharge) {
            throw new ValidateException('订单失效或者不存在');
        }
        if ($recharge['paid'] == 1) {
            throw new ValidateException('订单已支付');
        }
        $payType = $from == 'routine' ? 'routine' : $payType;
        if ($payType != $recharge['recharge_type']) {
            $res = $rechargeServices->update(['order_id' => $order_id], ['recharge_type' => $payType]);
            if (!$res) {
                throw new ValidateException('订单支付方式修改失败');
            }
        }
        $openid = '';
        //没有付款码，不是微信H5支付，门店支付，PC支付，不再APP端，需要判断用户openid
        if (!in_array($recharge['recharge_type'], ['weixinh5', 'store', 'pc']) && !request()->isApp()) {
            $userType = '';
            switch ($recharge['recharge_type']) {
                case 'weixin':
                case 'weixinh5':
                    $userType = 'wechat';
                    break;
                case 'routine':
                    $userType = 'routine';
                    break;
            }
            if (!$userType) {
                throw new ValidateException('不支持该类型方式');
            }
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            $openid = $wechatUser->uidToOpenid((int)$recharge['uid'], $userType);
            if (!$openid) {
                throw new ValidateException('获取用户openid失败,无法支付');
            }
        }
        return $this->pay->pay($recharge['recharge_type'], $openid, $recharge['order_id'], $recharge['price'], 'user_recharge', '用户充值');
    }

}
