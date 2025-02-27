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

namespace app\controller\kefu;


use think\annotation\Inject;
use app\services\kefu\ProductServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class Product
 * @package app\kefuapi\controller
 */
class Product extends AuthController
{

    /**
     * @var ProductServices
     */
    #[Inject]
    protected ProductServices $services;

    /**
     * 获取用户购买记录
     * @param $uid
     * @param string $store_name
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getCartProductList($uid, string $store_name = '')
    {
        return $this->success(get_thumb_water($this->services->getProductCartList((int)$uid, $store_name)));
    }

    /**
     * 用户浏览记录
     * @param $uid
     * @param string $store_name
     * @return mixed
     */
    public function getVisitProductList($uid, string $store_name = '')
    {
        return $this->success(get_thumb_water($this->services->getVisitProductList((int)$uid, $store_name)));
    }

    /**
     * 获取用户购买的热销商品
     * @param $uid
     * @param string $store_name
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductHotSale($uid, string $store_name = '')
    {
        return $this->success(get_thumb_water($this->services->getProductHotSale((int)$uid, $store_name)));
    }

    /**
     * 商品详情
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductInfo($id)
    {
        return $this->success($this->services->getProductInfo((int)$id));
    }
}
