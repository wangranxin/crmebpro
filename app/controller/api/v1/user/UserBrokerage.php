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
namespace app\controller\api\v1\user;


use app\Request;
use app\services\user\UserBrokerageServices;
use think\annotation\Inject;

/**
 * 佣金
 * Class UserBrokerage
 * @package app\controller\api\v1\user
 */
class UserBrokerage
{

    /**
     * @var UserBrokerageServices
     */
    #[Inject]
    protected UserBrokerageServices $services;

    /**
     * 推广数据    昨天的佣金   累计提现金额  当前佣金
     * @param Request $request
     * @return mixed
     */
    public function commission(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->commission($uid));
    }

    /**
     * 推广订单
     * @param Request $request
     * @return mixed
     */
    public function spread_order(Request $request)
    {
        $orderInfo = $request->postMore([
            ['page', 1],
            ['limit', 20],
            ['category', 'now_money'],
            ['type', 'brokerage'],
            ['start', 0],
            ['stop', 0],
            ['keyword', '']
        ]);
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->spread_order($uid, $orderInfo));
    }

    /**
     * 订单收益明细
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/11/22 上午10:45
     */
    public function order_income(Request $request)
    {
        $where = $request->getMore([
            ['start', 0],
            ['stop', 0],
        ]);
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->orderIncome($uid, $where));

    }

    /**
     * 数据总览
     * @param Request $request
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/11/23 下午12:11
     */
    public function overview(Request $request)
    {
        $where = $request->getMore([
            ['start', 0],
            ['stop', 0],
        ]);
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->overview($uid, $where));
    }

    /**
     * 用户贡献
     * @param $type
     * @param Request $request
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/11/23 下午2:18
     */
    public function contribute($type, Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->contribute($uid, $type));
    }

    /**
     * 推广 佣金/提现 总和
     * @param Request $request
     * @param $type 3 佣金  4 提现
     * @return mixed
     */
    public function spread_count(Request $request, $type)
    {
        $uid = (int)$request->uid();
        return app('json')->successful(['count' => $this->services->spread_count($uid, $type)]);
    }


    /**
     * 佣金排行
     * @param Request $request
     * @return mixed
     */
    public function brokerage_rank(Request $request)
    {
        $data = $request->getMore([
            ['page', ''],
            ['limit'],
            ['type']
        ]);
        $uid = (int)$request->uid();
        $userInfo = $request->user();
        $data = $this->services->brokerage_rank($uid, $data['type']);
        $data['nickname'] = $userInfo['nickname'];
        $data['avatar'] = $userInfo['avatar'];
        return app('json')->success($data);
    }
}
