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
namespace app\controller\api\v1\product;

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

	/**
 	* 获取同级的所有分类
	* @param Request $request
	* @return \think\Response
	*/
	public function levelCategory(Request $request)
    {
        [$id] = $request->getMore([
            ['id', 0],
        ], true);
		if (!$id) {
			return app('json')->fail('参数错误');
		}
        return app('json')->success($this->services->getLevelCategory((int)$id));
    }

    /**
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/11
     * @return mixed
     */
    public function getCategoryVersion()
    {
        return app('json')->success(['version' => $this->services->getCategoryVersion()]);
    }
}
