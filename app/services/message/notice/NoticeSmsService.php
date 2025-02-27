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
namespace app\services\message\notice;

use app\jobs\notice\SmsJob;
use app\services\message\service\StoreServiceServices;
use app\services\system\admin\SystemAdminServices;

/**
 * 短信发送消息列表
 * Created by PhpStorm.
 * User: xurongyao <763569752@qq.com>
 * Date: 2021/9/22 1:23 PM
 */
class NoticeSmsService extends BaseNoticeService
{

    /**
     * 发送短信消息
     * @param $phone
     * @param array $data 模板内容
     * @param string $template
     * @return void
     */
    public function sendSms($phone, array $data, string $template)
    {
        if ($phone) {
            $this->handle(fn() => SmsJob::dispatch('doJob', [$phone, $data, $template]));
        }
    }

    /**
     * 退款发送管理员消息任务
     * @param $order
     * @return bool
     */
    public function sendAdminRefund($order, $store_id = 0)
    {
        if (isset($order['supplier_id']) && $order['supplier_id']) {
            /** @var SystemAdminServices $systemAdminServices */
            $systemAdminServices = app()->make(SystemAdminServices::class);
            $adminList = $systemAdminServices->getNotifySupplierList((int)$order['supplier_id'], 'phone,real_name as nickname');
        } else {
            /** @var StoreServiceServices $StoreServiceServices */
            $StoreServiceServices = app()->make(StoreServiceServices::class);
            $adminList = $StoreServiceServices->getStoreServiceOrderNotice();
        }
        if ($adminList) {
            foreach ($adminList as $item) {
                $data = ['order_id' => $order['order_id'], 'admin_name' => $item['nickname'] ?? ''];
                $this->sendSms($item['phone'], $data, 'ADMIN_RETURN_GOODS_CODE');
            }
        }
        return true;
    }


    /**
     * 用户确认收货管理员短信提醒
     * @param $switch
     * @param $adminList
     * @param $order
     * @return bool
     */
    public function sendAdminConfirmTakeOver($order)
    {
        /** @var StoreServiceServices $StoreServiceServices */
        $StoreServiceServices = app()->make(StoreServiceServices::class);
        $adminList = $StoreServiceServices->getStoreServiceOrderNotice();
        foreach ($adminList as $item) {
            $data = ['order_id' => $order['order_id'], 'admin_name' => $item['nickname']];
            $this->sendSms($item['phone'], $data, 'ADMIN_TAKE_DELIVERY_CODE');
        }
        return true;
    }

    /**
     * 下单成功给客服管理员发送短信
     * @param $switch
     * @param $adminList
     * @param $order
     * @return bool
     */
    public function sendAdminPaySuccess($order, $store_id = 0)
    {
        if (isset($order['supplier_id']) && $order['supplier_id']) {
            /** @var SystemAdminServices $systemAdminServices */
            $systemAdminServices = app()->make(SystemAdminServices::class);
            $adminList = $systemAdminServices->getNotifySupplierList((int)$order['supplier_id'], 'phone,real_name as nickname');
        } else {
            /** @var StoreServiceServices $StoreServiceServices */
            $StoreServiceServices = app()->make(StoreServiceServices::class);
            $adminList = $StoreServiceServices->getStoreServiceOrderNotice();
        }
        if ($adminList) {
            foreach ($adminList as $item) {
                $data = ['order_id' => $order['order_id'], 'admin_name' => $item['nickname'] ?? ''];
                $this->sendSms($item['phone'], $data, 'ADMIN_PAY_SUCCESS_CODE');
            }
        }
        return true;
    }
}
