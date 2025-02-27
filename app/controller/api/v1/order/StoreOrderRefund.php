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
namespace app\controller\api\v1\order;

use app\Request;
use app\services\order\StoreOrderRefundServices;
use think\annotation\Inject;

/**
 * 售后订单
 * Class StoreOrderRefundController
 * @package app\controller\api\v1\order
 */
class StoreOrderRefund
{

    /**
     * @var StoreOrderRefundServices
     */
    #[Inject]
    protected StoreOrderRefundServices $services;

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
			[['search', 's'], '', '', 'real_name'],
            ['refund_type', '', '', 'refundTypes']
        ]);
        $where['uid'] = $request->uid();
        $list = $this->services->getRefundOrderList($where);
        return app('json')->successful($list);
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function detail(StoreOrderRefundServices $services, Request $request, $uni)
    {
        $orderData = $services->refundDetail($uni);
        return app('json')->successful('ok', $orderData);
    }


    /**
     * 取消申请
     * @param $id
     * @return mixed
     */
    public function cancelApply(Request $request, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
		$uid = (int)$request->uid();
		$this->services->cancelApplyRefund($uid, $uni);
        return app('json')->success('取消成功');
    }

    /**
     * 删除已退款和拒绝退款的订单
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function delRefundOrder(Request $request, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $uid = (int)$request->uid();
        $this->services->delRefundOrder($uid, $uni);
        return app('json')->success('删除成功');
    }

	/**
     * 再次申请
     * @param $id
     * @return mixed
     */
    public function againRefundOrder(Request $request, $id)
    {
        if (!strlen(trim($id))) return app('json')->fail('参数错误');
		$uid = (int)$request->uid();
		$this->services->againRefundOrder($uid, (int)$id);
        return app('json')->success('申请成功');
    }
}
