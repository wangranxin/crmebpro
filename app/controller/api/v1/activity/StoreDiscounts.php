<?php


namespace app\controller\api\v1\activity;


use app\Request;
use app\services\activity\discounts\StoreDiscountsServices;
use think\annotation\Inject;

/**
 * 优惠套餐控制器
 * Class StoreDiscounts
 * @package app\controller\api\v1\activity
 */
class StoreDiscounts
{
    /**
     * @var StoreDiscountsServices
     */
    #[Inject]
    protected StoreDiscountsServices $services;

    /**
     * 获取优惠商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        [$product_id] = $request->postMore([
            ['product_id', 0]
        ], true);
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getDiscounts((int)$product_id, $uid));
    }
}
