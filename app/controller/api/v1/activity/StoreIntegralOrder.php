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
namespace app\controller\api\v1\activity;


use app\Request;
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\order\StoreOrderServices;
use think\annotation\Inject;

/**
 * 积分订单
 * Class StoreIntegralController
 * @package app\api\controller\activity
 */
class StoreIntegralOrder
{

    /**
     * @var StoreIntegralOrderServices
     */
    #[Inject]
    protected StoreIntegralOrderServices $services;



    /**
     * 订单详情
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function detail(Request $request, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $order = $this->services->getOne(['order_id' => $uni, 'is_del' => 0]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        if (!$order['paid']) return app('json')->fail('订单未支付，无法查看');
        $orderData = $this->services->tidyOrder($order);
        return app('json')->successful('ok', $orderData);
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where['uid'] = $request->uid();
        $where['paid'] = 1;
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
		$where['type'] = 4;
        $list = $this->services->getOrderApiList($where);
        return app('json')->successful($list);
    }

    /**
     * 订单删除
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request, StoreOrderServices $storeOrderServices)
    {
        [$order_id] = $request->postMore([
            ['order_id', ''],
        ], true);
        if (!$order_id) return app('json')->fail('参数错误!');
        $res = $storeOrderServices->removeOrder($order_id, (int)$request->uid());
        if ($res) {
            return app('json')->successful();
        } else {
            return app('json')->fail('删除失败');
        }
    }
}
