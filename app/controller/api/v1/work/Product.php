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

namespace app\controller\api\v1\work;

use app\services\kefu\ProductServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class ProductController
 * @package app\controller\api\v1\work
 */
class Product extends BaseWork
{

    /**
     * @var ProductServices
     */
    #[Inject]
    protected ProductServices $service;

    /**
     * 获取用户购买记录
     * @param string $store_name
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getCartProductList(string $store_name = '')
    {
        $uid = $this->clientInfo['uid'] ?? 0;
        if (!$uid) {
            return $this->success([]);
        }
        return $this->success(get_thumb_water($this->service->getProductCartList((int)$uid, $store_name)));
    }

    /**
     * 用户浏览记录
     * @param string $store_name
     * @return mixed
     */
    public function getVisitProductList(string $store_name = '')
    {
        $uid = $this->clientInfo['uid'] ?? 0;
        if (!$uid) {
            return $this->success([]);
        }
        return $this->success(get_thumb_water($this->service->getVisitProductList((int)$uid, $store_name)));
    }

    /**
     * 获取用户购买的热销商品
     * @param string $store_name
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductHotSale(string $store_name = '')
    {
        $uid = $this->clientInfo['uid'] ?? 0;
        if (!$uid) {
            return $this->success([]);
        }
        return $this->success(get_thumb_water($this->service->getProductHotSale((int)$uid, $store_name)));
    }
}
