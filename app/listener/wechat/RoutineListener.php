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

namespace app\listener\wechat;

use app\services\order\StoreOrderTakeServices;
use crmeb\services\wechat\MiniProgram;
use think\facade\Event;
use think\facade\Log;

/**
 * 小程序消息处理事件
 * Class RoutineListener
 * @package app\listener\wechat
 */
class RoutineListener
{

    /**
     * 事件回调
     * @param null $payload
     * @return bool|string
     */
    public function __invoke($payload, \Closure $next)
    {
        try {
            switch ($payload['MsgType']) {
                case 'event':
                    switch (strtolower($payload['Event'])) {
                        case 'funds_order_pay':
                            $prefix = substr($payload['order_info']['trade_no'], 0, 2);
                            //处理一下参数
                            switch ($prefix) {
                                case 'wx':
                                case 'cp':
                                    $data['attach'] = 'Product';
                                    break;
                                case 'hy':
                                    $data['attach'] = 'Member';
                                    break;
                                case 'cz':
                                    $data['attach'] = 'UserRecharge';
                                    break;
                            }
                            $data['out_trade_no'] = $payload['order_info']['trade_no'];
                            $data['transaction_id'] = $payload['order_info']['transaction_id'];
                            $data['opneid'] = $payload['FromUserName'];
                            if (Event::until('pay.notify', [$data])) {
                                $response = 'success';
                            } else {
                                $response = 'faild';
                            }
                            Log::error(['data' => $data, 'res' => $response, 'message' => $payload]);
                            break;
                        case 'trade_manage_remind_access_api':  // 小程序完成账期授权时  小程序产生第一笔交易时 已产生交易但从未发货的小程序，每天一次
                            break;
                        case 'trade_manage_remind_shipping':   // 曾经发过货的小程序，订单超过48小时未发货时
                            break;
                        case 'trade_manage_order_settlement':     // 订单完成发货时  订单结算时
//                            if (isset($payload['estimated_settlement_time'])) { //订单完成发货时
//                                MiniProgram::notifyConfirmByTradeNo($payload['merchant_trade_no'], time());
//                            }
                            if (isset($payload['confirm_receive_method'])) {  // 订单结算时
                                /** @var StoreOrderTakeServices $StoreOrderTakeServices */
                                $storeOrderTakeServices = app()->make(StoreOrderTakeServices::class);
                                $storeOrderTakeServices->miniOrderTakeOrder($payload['merchant_trade_no']);
                            }
                            break;
                    }
                    break;
                // ... 其它消息
                default:

                    break;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error(['title' => '微信消息服务端消息执行错误', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        return $response ?? $next($payload);
    }
}
