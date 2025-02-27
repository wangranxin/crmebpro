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
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\activity\newcomer\StoreNewcomerServices;
use app\services\other\CacheServices;
use app\services\user\UserServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;

/**
 * 新人商品类
 * Class StoreNewcomer
 * @package app\api\controller\activity
 */
class StoreNewcomer
{

    /**
     * @var StoreNewcomerServices
     */
    #[Inject]
    protected StoreNewcomerServices $services;

    /**
     * 新人大礼包弹窗
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGift(Request $request)
    {
        $data = [];
        $uid = (int)$request->uid();
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserCacheInfo($uid);
        //新用户
        if ($userInfo && $userInfo['add_time'] == $userInfo['last_time'] && $this->services->checkUserNewcomer($uid, $userInfo)) {
            $data = SystemConfigService::more([
                'newcomer_limit_status',
                'newcomer_limit_time',
                'register_integral_status',
                'register_give_integral',
                'register_money_status',
                'register_give_money',
                'register_coupon_status',
                'register_give_coupon',
                'first_order_status',
                'first_order_discount',
                'first_order_discount_limit',
                'register_price_status'
            ]);
            $data['product_count'] = 0;
			if ($data['register_price_status']) {
				/** @var StoreNewcomerServices $newcomerServices */
				$newcomerServices = app()->make(StoreNewcomerServices::class);
				$data['product_count'] = $newcomerServices->count(['is_del' => 0]);
			}
            $ids = $data['register_give_coupon'] ?? [];
            $data['register_give_coupon'] = [];
            if ($data['register_coupon_status'] && $ids) {
                /** @var StoreCouponUserServices $couponServices */
                $couponServices = app()->make(StoreCouponUserServices::class);
                $coupon = $couponServices->getList(['cid' => $ids, 'uid' => $uid]);
                if ($coupon) $coupon = $couponServices->tidyCouponList($coupon);
                $data['register_give_coupon'] = $coupon;
            }
			if (!$data['first_order_status']) {
				$data['first_order_discount'] = 0;
			}
			if (!$data['register_integral_status']) {
				$data['register_give_integral'] = 0;
			}
			if (!$data['register_money_status']) {
				$data['register_give_money'] = 0;
			}
            $data['coupon_count'] = count($data['register_give_coupon']);
        }
        return app('json')->success($data);
    }

    /**
     * 新人礼信息
     * @param Request $request
     * @return mixed
     */
    public function getInfo(Request $request)
    {
        $data = [];
        $uid = (int)$request->uid();
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo($uid);
        $status = sys_config('newcomer_status');
        if ($userInfo && $status) {
            $data = SystemConfigService::more([
                'newcomer_limit_status',
                'newcomer_limit_time',
                'register_integral_status',
                'register_give_integral',
                'register_money_status',
                'register_give_money',
                'register_coupon_status',
                'register_give_coupon',
                'first_order_status',
                'first_order_discount',
                'first_order_discount_limit',
                'register_price_status'
            ]);
            $data['product_count'] = 0;
			if ($data['register_price_status']) {
				/** @var StoreNewcomerServices $newcomerServices */
				$newcomerServices = app()->make(StoreNewcomerServices::class);
				$data['product_count'] = $newcomerServices->count(['is_del' => 0]);
			}
            /** @var CacheServices $cache */
            $cache = app()->make(CacheServices::class);
            $data['newcomer_agreement'] = $cache->getDbCache('newcomer_agreement', '');
            $ids = $data['register_give_coupon'] ?? [];
            $data['register_give_coupon'] = [];
            if ($data['register_coupon_status'] && $ids) {
                /** @var StoreCouponUserServices $couponServices */
                $couponServices = app()->make(StoreCouponUserServices::class);
                $coupon = $couponServices->getList(['cid' => $ids, 'uid' => $uid]);
                if ($coupon) $coupon = $couponServices->tidyCouponList($coupon);
                $data['register_give_coupon'] = $coupon;
            }
            $data['coupon_count'] = count($data['register_give_coupon']);

            $data['last_time'] = 0;
            if ($data['newcomer_limit_status'] && $data['newcomer_limit_time']) {
                $data['last_time'] = bcadd((string)$userInfo['add_time'], bcmul((string)$data['newcomer_limit_time'], '86400'));
            }

			if (!$data['first_order_status']) {
				$data['first_order_discount'] = 0;
			}
			if (!$data['register_integral_status']) {
				$data['register_give_integral'] = 0;
			}
			if (!$data['register_money_status']) {
				$data['register_give_money'] = 0;
			}
        }
        return app('json')->success($data);
    }

    /**
     * 新人商品列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lst(Request $request)
    {
        $uid = (int)$request->uid();
        $data = [];
        //新用户
        if ($this->services->checkUserNewcomer($uid)) {
            $data = $this->services->getCustomerProduct([], 'id,type,product_id,relation_id,product_type,price', ['product' => function ($query) {
				$query->field('id,image,store_name,stock,sales,ot_price');
            }]);
        }
        return app('json')->successful(get_thumb_water($data, 'mid'));
    }

    /**
     * 新人商品详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, $id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $uid = (int)$request->uid();
        $data = $this->services->newcomerDetail($uid, (int)$id);
        return app('json')->success($data);
    }
}
