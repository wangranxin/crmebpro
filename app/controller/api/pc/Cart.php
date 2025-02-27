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

namespace app\controller\api\pc;

use app\Request;
use app\services\pc\CartServices;
use think\annotation\Inject;
use think\Response;

/**
 * Class CartController
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2023/9/19
 * @package app\controller\api\pc
 */
class Cart
{
    /**
     * @var CartServices
     */
    #[Inject]
    protected CartServices $services;

    /**
     * 获取用户购物车列表
     * @param Request $request
     * @return Response
     */
    public function getCartList(Request $request): Response
    {
        $uid = $request->uid();
        $data = $this->services->getCartList((int)$uid);
        return app('json')->successful($data);
    }
}
