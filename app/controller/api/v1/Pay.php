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

namespace app\controller\api\v1;


use crmeb\services\AliPayService;
use crmeb\services\wechat\Payment;
use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use think\Response;

/**
 * 支付相关回调
 * Class Pay
 * @package app\api\controller\v1
 */
class Pay
{

    /**
     * 支付回调
     * @param string $type
     * @return string|Response
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function notify(string $type)
    {
        switch (urldecode($type)) {
            case 'alipay':
                return AliPayService::handleNotify();
                break;
            case 'routine':
                return Payment::instance()->setAccessEnd(Payment::MINI)->handleNotify();
                break;
            case 'wechat':
                return Payment::instance()->setAccessEnd(Payment::WEB)->handleNotify();
                break;
            case 'app':
                return Payment::instance()->setAccessEnd(Payment::APP)->handleNotify();
                break;
        }
    }

    /**
     * 退款回调
     * @param string $type
     * @return Response
     * @throws Exception
     */
    public function refund(string $type)
    {
        switch (urldecode($type)) {
            case 'alipay':

                break;
            case 'routine':
                return Payment::instance()->setAccessEnd(Payment::MINI)->handleRefundedNotify();
                break;
            case 'wechat':
                return Payment::instance()->setAccessEnd(Payment::WEB)->handleRefundedNotify();
                break;
            case 'app':
                return Payment::instance()->setAccessEnd(Payment::APP)->handleRefundedNotify();
                break;
        }
    }

    /**
     * 商户转账回调
     * @param string $type
     * @return Response|void
     * User: liusl
     * DateTime: 2025/2/14 下午3:06
     */
    public function mchNotify(string $type)
    {
        switch (urldecode($type)) {
            case 'mini':
                return Payment::instance()->setAccessEnd(Payment::MINI)->handleMchNotify();
                break;
            case 'web':
                return Payment::instance()->setAccessEnd(Payment::WEB)->handleMchNotify();
                break;
            case 'app':
                return Payment::instance()->setAccessEnd(Payment::APP)->handleMchNotify();
                break;
        }
    }
}
