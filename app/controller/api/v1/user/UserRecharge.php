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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\pay\OrderPayServices;
use app\services\pay\PayServices;
use app\services\pay\RechargeServices;
use app\services\pay\YuePayServices;
use app\services\user\UserRechargeServices;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 充值类
 * Class UserRecharge
 * @package app\api\controller\user
 */
class UserRecharge
{
    /**
     * @var UserRechargeServices
     */
    #[Inject]
    protected UserRechargeServices $services;

    /**
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/19
     */
    public function recharge(Request $request)
    {
        [$price, $recharId, $type, $from] = $request->postMore([
            ['price', 0],
            ['rechar_id', 0],
            ['type', 0],
            ['from', 'weixin']
        ], true);
        if (!$price || $price <= 0) return app('json')->fail('充值金额不能为0元!');
        if (!in_array($type, [0, 1])) return app('json')->fail('充值方式不支持!');
        if (!in_array($from, ['weixin', 'weixinh5', 'routine'])) return app('json')->fail('充值方式不支持');
        $storeMinRecharge = sys_config('store_user_min_recharge');
        if ($price < $storeMinRecharge) return app('json')->fail('充值金额不能低于' . $storeMinRecharge);
        $uid = (int)$request->uid();
        $re = $this->services->recharge($uid, $price, $recharId, $type, $from);
        if ($re) {
            return app('json')->successful($type == 1 ? '操作成功' : '订单生成成功', ['order_id' => $re['data']['order_id'] ?? '']);
        }
        return app('json')->fail('充值订单生产失败');
    }

    /**
     * 充值订单支付
     * @param Request $request
     * @return \think\Response
     */
    public function recharge_pay(Request $request)
    {
        [$uni, $payType, $from, $quitUrl] = $request->postMore([
            ['uni', ''],
            ['paytype', 'weixin'],
            ['from', 'weixin'],
            ['quitUrl', '']
        ], true);
        $info['order_id'] = $uni;
        $recharge = $this->services->getOne(['order_id' => $uni]);
        if (!$recharge) {
            throw new ValidateException('订单失效或者不存在');
        }
        if ($recharge['paid'] == 1) {
            throw new ValidateException('订单已支付');
        }
        if (($from == 'routine' ? 'routine' : $payType) != $recharge['recharge_type']) {
            $res = $this->services->update(['order_id' => $uni], ['recharge_type' => $payType]);
            if (!$res) {
                throw new ValidateException('订单支付方式修改失败');
            }
        }
        $orderInfo = $recharge->toArray();
        $order_id = $orderInfo['order_id'];
        $info = compact('order_id');
        $orderInfo['pay_price'] = $recharge['price'];
        switch ($payType) {
            case PayServices::WEIXIN_PAY:
                if ($orderInfo['paid']) return app('json')->fail('已支付!');
                /** @var OrderPayServices $payServices */
                $payServices = app()->make(OrderPayServices::class);
                $info['jsConfig'] = $payServices->orderPay($orderInfo, $from);
                if ($from == 'weixinh5') {
                    return app('json')->status('wechat_h5_pay', '前往支付', $info);
                } else {
                    return app('json')->status('wechat_pay', '前往支付', $info);
                }
                break;
            case PayServices::ALIAPY_PAY:
                if (!$quitUrl && ($request->isH5() || $request->isWechat())) {
                    return app('json')->status('pay_error', '请传入支付宝支付回调URL', $info);
                }
                /** @var OrderPayServices $payServices */
                $payServices = app()->make(OrderPayServices::class);
                $info['jsConfig'] = $payServices->alipayOrder($orderInfo, $quitUrl, $request->isRoutine());
                $payKey = md5($orderInfo['order_id']);
                CacheService::set($payKey, ['order_id' => $orderInfo['order_id'], 'other_pay_type' => true], 300);
                $info['pay_key'] = $payKey;
                return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '前往支付', $info);
                break;
            default:
                return app('json')->fail('未知支付方式');
        }


    }

    /**
     * 充值额度选择
     * @return mixed
     */
    public function index()
    {
        $rechargeQuota = sys_data('user_recharge_quota') ?? [];
        $data['recharge_quota'] = $rechargeQuota;
        $recharge_attention = sys_config('recharge_attention');
        $recharge_attention = explode("\n", $recharge_attention);
        $data['recharge_attention'] = $recharge_attention;
        $data['user_extract_balance_status'] = (int)sys_config('user_extract_balance_status', 1);
        return app('json')->successful($data);
    }
}
