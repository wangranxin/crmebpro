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

namespace app\controller\api\v2\order;


use app\services\order\StoreOrderInvoiceServices;
use app\services\order\StoreOrderServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\store\SystemStoreServices;
use crmeb\services\UtilService;
use think\annotation\Inject;
use think\Request;

/**
 * Class StoreOrderInvoice
 * @package app\api\controller\v2\order
 */
class StoreOrderInvoice
{
    /**
     * @var StoreOrderInvoiceServices
     */
    #[Inject]
    protected StoreOrderInvoiceServices $services;

    /**
     * 订单开票
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function makeUp(Request $request)
    {
        [$order_id, $invoice_id] = $request->postMore([
            ['order_id', 0],
            ['invoice_id', 0]
        ], true);
        if (!$order_id) return app('json')->fail('请选择要开票订单');
        if (!$invoice_id) return app('json')->fail('请选择发票');
        $uid = (int)$request->uid;
        return app('json')->successful($this->services->makeUp($uid, $order_id, (int)$invoice_id));
    }

    /**
     * 开票记录
     * @param Request $request
     * @return mixed
     */
    public function list(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getOrderInvoiceList(['uid' => $uid]));
    }

    /**
     * 订单详情
     * @param \app\Request $request
     * @param $uni
     * @return mixed
     */
    public function detail(StoreOrderServices $services, Request $request, $uni, $id)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $order = $services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        $orderInvoice = $this->services->get($id);
        $orderInvoice['invoice_time'] = $orderInvoice['invoice_time'] ? date('Y-m-d H:i:s', $orderInvoice['invoice_time']) : '';
        $order['invoice'] = $orderInvoice;
        //门店是否开启 ｜｜ 门店自提是否开启
        if (!sys_config('store_func_status', 1) || !sys_config('store_self_mention')) {
            //关闭门店自提后 订单隐藏门店信息
            $order['shipping_type'] = 1;
        }
        if ($order['verify_code']) {
            $verify_code = $order['verify_code'];
            $verify[] = substr($verify_code, 0, 4);
            $verify[] = substr($verify_code, 4, 4);
            $verify[] = substr($verify_code, 8);
            $order['_verify_code'] = implode(' ', $verify);
        }
        $order['add_time_y'] = date('Y-m-d', $order['add_time']);
        $order['add_time_h'] = date('H:i:s', $order['add_time']);
        /** @var SystemStoreServices $storeServices */
        $storeServices = app()->make(SystemStoreServices::class);
        $order['system_store'] = $storeServices->getStoreDispose($order['store_id']);
        if (($order['shipping_type'] === 2 || $order['delivery_uid'] != 0) && $order['verify_code']) {
            $name = $order['verify_code'] . '.jpg';
            /** @var SystemAttachmentServices $attachmentServices */
            $attachmentServices = app()->make(SystemAttachmentServices::class);
            $imageInfo = $attachmentServices->getInfo(['name' => $name]);
            $siteUrl = sys_config('site_url');
            if (!$imageInfo) {
                $imageInfo = UtilService::getQRCodePath($order['verify_code'], $name);
                if (is_array($imageInfo)) {
                    $attachmentServices->attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
                    $url = $imageInfo['dir'];
                } else
                    $url = '';
            } else $url = $imageInfo['att_dir'];
            if (isset($imageInfo['image_type']) && $imageInfo['image_type'] == 1) $url = $siteUrl . $url;
            $order['code'] = $url;
        }
        $order['mapKey'] = sys_config('tengxun_map_key');
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = (bool)sys_config('ali_pay_status');//支付包支付 1 开启 0 关闭
        return app('json')->successful('ok', $services->tidyOrder($order, true, true));
    }
}
