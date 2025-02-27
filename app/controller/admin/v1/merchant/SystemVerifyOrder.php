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
namespace app\controller\admin\v1\merchant;

use app\controller\admin\AuthController;
use app\services\order\StoreOrderServices;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 核销订单
 * Class SystemVerifyOrder
 * @package app\controller\admin\v1\merchant
 */
class SystemVerifyOrder extends AuthController
{

    /**
     * @var StoreOrderServices
     */
    #[Inject]
    protected StoreOrderServices $services;

    /**
     * 获取核销订单列表
     * return json
     */
    public function list()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            ['real_name', ''],
            ['store_id', ''],
            ['type', ''],
            ['field_key', ''],
        ]);
        $data = $this->services->getOrderList($where + ['status' => 6], ['*'], ['store', 'staff']);
        return $this->success(['count' => $data['count'], 'data' => $data['data'], 'badge' => $data['stat']]);
    }

    /**
     * 未使用,获取核销订单头部
     * @return mixed
     */
    public function getVerifyBadge()
    {
        return $this->success([]);
    }

    /**
     * 订单列表推荐人详细
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function order_spread_user($uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo((int)$uid);
        $spread = [];
        if ($userInfo['spread_uid']) {
            $spread = $userServices->getUserInfo((int)$userInfo['spread_uid']);
            if ($spread) {
                $spread = $spread->toArray();
                $spread['brokerage_pric'] = $spread['brokerage_price'];
                $spread['birthday'] = $spread['birthday'] ? date('Y-m-d', $spread['birthday']) : '';
                $spread['last_time'] = $spread['last_time'] ? date('Y-m-d H:i:s', $spread['last_time']) : '';
            }
        }
        return $this->success(['spread' => $spread]);
    }
}
