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
namespace app\controller\api\v2\activity;

use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\StoreOrderServices;use app\services\product\product\StoreProductCouponServices;
use think\annotation\Inject;
use think\exception\ValidateException;use think\Request;

/**
 * 优惠券
 * Class StoreCoupons
 * @package app\controller\api\v2\store
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
     * @param \app\Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            [['type', 'd'], ''],
            [['product_id', 'd'], 0],
			[['brand_id', 'd'], 0],
			['defaultOrder', ''],
			['timeOrder', ''],
			['priceOrder', '']
        ]);
        return app('json')->successful($this->services->getIssueCouponList((int)$request->uid(), $where, '*', true));
    }

    /**
     * 获取新人券
     * @return mixed
     */
    public function getNewCoupon(Request $request)
    {
        $userInfo = $request->user();
        $data = [];
        /** @var StoreCouponIssueServices $couponService */
        $couponService = app()->make(StoreCouponIssueServices::class);
        $data['list'] = $couponService->getNewCoupon();
        $data['image'] = '';
        if ($userInfo->add_time === $userInfo->last_time) {
            $data['show'] = 1;
        } else {
            $data['show'] = 0;
        }
        //会员领取优惠券
        //$couponService->sendMemberCoupon($userInfo->uid);
        return app('json')->success($data);
    }

    /**
 	* 获取每日新增的优惠券
	* @param Request $request
	* @param StoreCouponIssueServices $couponIssueServices
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getTodayCoupon(Request $request, StoreCouponIssueServices $couponIssueServices)
    {
        $uid = 0;
        if ($request->hasMacro('uid')) $uid = (int)$request->uid();
        $data['list'] = $couponIssueServices->getTodayCoupon($uid);
        $data['image'] = '';
        return app('json')->success($data);
    }
}
