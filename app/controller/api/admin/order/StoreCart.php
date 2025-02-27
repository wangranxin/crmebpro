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
namespace app\controller\api\admin\order;

use app\Request;
use app\services\order\StoreCartServices;
use think\annotation\Inject;

/**
 * 购物车类
 * Class StoreCart
 * @package app\api\controller\store
 */
class StoreCart
{

    /**
     * @var StoreCartServices
     */
    #[Inject]
    protected StoreCartServices $services;

	/**
 	* 获取购物车数据
	* @param Request $request
	* @param StoreCartServices $services
	* @param $uid
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getCartList(Request $request, StoreCartServices $services, $uid)
    {
        $cartIds = $request->get('cart_ids', '');
        $touristUid = $request->get('tourist_uid', '');
        $new = $request->get('new', false);
        $cartIds = $cartIds ? explode(',', $cartIds) : [];
        if (!$touristUid && !$uid) {
            return app('json')->fail('缺少用户信息');
        }
		$services->setItem('tourist_uid', $touristUid)
            ->setItem('staff_id', $request->uid());

        $result = $services->getUserCartList((int)$uid, -1, $cartIds, -1, 0, !!$new);
		$services->reset();

        return app('json')->success($result['valid'] ?? []);
    }


    /**
 	* 加入购物车
	* @param Request $request
	* @param StoreCartServices $services
	* @param $uid
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function addCart(Request $request, StoreCartServices $services, $uid)
    {
        $where = $request->postMore([
            ['productId', 0],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            ['new', 1],//1直接购买,0=加入购物车
            ['tourist_uid', ''],//虚拟用户uid
        ]);

        $new = !!$where['new'];

        if (!$where['productId']) {
            return app('json')->fail('参数错误');
        }
        //真实用户存在，虚拟用户uid为空
        if ($uid) {
            $where['tourist_uid'] = '';
        }
        if (!$uid && !$where['tourist_uid']) {
            return app('json')->fail('缺少用户UID');
        }
        $services->setItem('tourist_uid', $where['tourist_uid'])
            ->setItem('staff_id', $request->uid());

        $activityId = $type = 0;

        [$cartId, $cartNum] = $services->setCart((int)$uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], $type, $new, (int)$activityId);

        $services->reset();
        return app('json')->success(['cartId' => $cartId]);
    }

    /**
 	* 收银台更改购物车数量
	* @param Request $request
	* @param StoreCartServices $services
	* @param $uid
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function numCart(Request $request, StoreCartServices $services, $uid)
    {
        $where = $request->postMore([
            ['id', 0],//购物车编号
            ['number', 0],//购物数量
        ]);
        if (!$where['id'] || !$where['number'] || !is_numeric($where['id']) || !is_numeric($where['number'])) {
            return app('json')->fail('参数错误!');
        }
        if ($services->changeCashierCartNum((int)$where['id'], (int)$where['number'], $uid )) {

            return app('json')->success('修改成功');
        } else {
            return app('json')->fail('修改失败');
        }
    }

    /**
 	* 删除购物车
	* @param Request $request
	* @param StoreCartServices $services
	* @param $uid
	* @return \think\Response
	*/
    public function delCart(Request $request, StoreCartServices $services, $uid)
    {
        [$ids] = $request->postMore([
            ['ids', ''],//购物车编号
        ], true);
        if (!$ids) {
            return app('json')->fail('参数错误!');
        }
		$ids = is_array($ids) ? $ids : stringToIntArray($ids);
        if ($services->removeUserCart((int)$uid, $ids)) {
            return app('json')->success('删除成功');
        } else {
            return app('json')->fail('清除失败！');
        }
    }

}
