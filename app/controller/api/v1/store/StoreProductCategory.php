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
namespace app\controller\api\v1\store;

use app\services\product\category\StoreProductCategoryServices;
use think\annotation\Inject;
use think\Request;

/**
 * Class StoreProductCategory
 * @package app\api\controller\v1\store
 */
class StoreProductCategory
{

    /**
     * @var StoreProductCategoryServices
     */
    #[Inject]
    protected StoreProductCategoryServices $services;

    /**
     * 获取分类列表
     * @return mixed
     */
    public function category(Request $request)
    {
        $where = $request->getMore([
            ['pid', 0],
        ]);
        $category = $this->services->getCategory($where);
        return app('json')->success($category);
    }
}
