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
namespace app\controller\admin\v1\order;

use app\common\controller\Order;
use app\controller\admin\AuthController;
use app\services\order\{
    StoreOrderServices
};
use think\annotation\Inject;

/**
 * 订单管理
 * Class StoreOrder
 * @package app\controller\admin\v1\order
 */
class StoreOrder extends AuthController
{

    use Order;

    /**
     * @var StoreOrderServices
     */
    #[Inject]
    protected StoreOrderServices $services;

    /**
     * 易联云打印机打印
     * @param $id
     * @return mixed
     */
    public function order_print($id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $order = $this->services->get($id);
        if (!$order) {
            return app('json')->fail('订单没有查到,无法打印!');
        }
        $res = $this->services->orderPrint((int)$id, 0, 0);
        if ($res) {
            return app('json')->success('打印成功');
        } else {
            return app('json')->fail('打印失败');
        }
    }
}
