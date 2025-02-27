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
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\coupon\StoreCouponUserServices;
use think\annotation\Inject;

/**
 * 优惠券类
 * Class StoreCoupons
 * @package app\api\controller\store
 */
class StoreCoupons
{
    /**
     * @var StoreCouponIssueServices
     */
    #[Inject]
    protected StoreCouponIssueServices $services;

    /**
     * 可领取优惠券列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
			[['type', 'd'], ''],
            [['product_id', 'd'], 0],
			[['brand_id', 'd'], 0],
        ]);
        if ($request->getFromType() == 'pc') $where['type'] = '';
        return app('json')->successful($this->services->getIssueCouponList((int)$request->uid(), $where)['list']);
    }

    /**
     * 领取优惠券
     *
     * @param Request $request
     * @return mixed
     */
    public function receive(Request $request)
    {
        [$couponId] = $request->getMore([
            ['couponId', 0]
        ], true);
		$couponId = (int)$couponId;
        if (!$couponId) {
			return app('json')->fail('参数错误!');
        }
		$uid = (int)$request->uid();
        $coupon = $this->services->issueUserCoupon($uid, $couponId,false);
        if ($coupon) {
            $coupon = $coupon->toArray();
            return app('json')->success('领取成功', $coupon);
        }
        return app('json')->fail('领取失败');
    }

    /**
     * 我的优惠券数量
     * @param Request $request
     * @param StoreCouponUserServices $storeCouponUserService
     * @return \think\Response
     * @throws \think\db\exception\DbException
     */
    public function userCount(Request $request, StoreCouponUserServices $storeCouponUserService)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($storeCouponUserService->getUserCounponNum($uid));
    }

    /**
     * 用户已领取优惠券
     * @param Request $request
     * @param $types
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user(Request $request, StoreCouponUserServices $storeCouponUserService, $types)
    {
        $uid = (int)$request->uid();
        [$type] = $request->getMore([
            ['type', 0],
        ], true);
        return app('json')->successful($storeCouponUserService->getUserCounpon($uid, $types, $type));
    }

    /**
     * 优惠券 订单获取
     * @param Request $request
     * @param $price
     * @return mixed
     */
    public function order(Request $request, StoreCouponIssueServices $service, $cartId, $new)
    {
		[$shipping_type, $storeId] = $request->getMore([
            ['shipping_type', 1],
            ['store_id', 0],
        ], true);
        return app('json')->successful($service->beUseableCouponList((int)$request->uid(), $cartId, !!$new, (int)$shipping_type));
    }
}
