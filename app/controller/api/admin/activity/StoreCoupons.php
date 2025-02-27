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
namespace app\controller\api\admin\activity;

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
 	* 下单获取用户可使用优惠券
	* @param Request $request
	* @param StoreCouponIssueServices $service
	* @param $uid
	* @return \think\Response
	* @throws \Psr\SimpleCache\InvalidArgumentException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function order(Request $request, StoreCouponIssueServices $service, $uid)
    {
		[$cartId, $new, $shipping_type] = $request->getMore([
			'cartId',
            'new',
            ['shipping_type', 1]
        ], true);
		$uid = (int)$uid;
		$coupons = [];
		if ($uid) {
			$coupons = $service->beUseableCouponList((int)$uid, $cartId, !!$new, (int)$shipping_type);
		}
        return app('json')->successful($coupons);
    }
}
