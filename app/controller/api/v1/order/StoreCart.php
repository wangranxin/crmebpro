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
namespace app\controller\api\v1\order;

use app\Request;
use app\services\order\StoreCartServices;
use app\services\activity\discounts\StoreDiscountsServices;
use crmeb\services\CacheService;
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
     * 购物车 列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        [$status, $latitude, $longitude, $store_id] = $request->postMore([
            ['status', 1],//购物车商品状态
            ['latitude', ''],
            ['longitude', ''],
            ['store_id', 0]
        ], true);
        if (!checkCoordinates($longitude, $latitude)) {
            return app('json')->fail('参数错误');
        }
        $this->services->setItem('latitude', $latitude)->setItem('longitude', $longitude)->setItem('store_id', (int)$store_id)->setItem('status', $status);
        $result = $this->services->getUserCartList((int)$request->uid(), (int)$status);
        $this->services->reset();
        $result['valid'] = $this->services->getReturnCartList($result['valid'], $result['promotions']);
        unset($result['promotions']);
        return app('json')->successful($result);
    }

    /**
     * 购物车 添加
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        $where = $request->postMore([
            ['productId', 0],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            [['new', 'd'], 0],// 1 加入购物车直接购买  0 加入购物车
            [['is_new', 'd'], 0],// 1 加入购物车直接购买  0 加入购物车
            [['secKillId', 'd'], 0],//秒杀商品ID
            [['bargainId', 'd'], 0],//砍价商品ID
            [['combinationId', 'd'], 0],//拼团商品ID
            [['storeIntegralId', 'd'], 0],//积分商品ID
            [['discountId', 'd'], 0],//优惠套餐ID
            ['discountInfos', []],//优惠套餐商品ID
            [['newcomerId', 'd'], 0],//新人专享商品ID
            [['luckRecordId', 'd'], 0],//抽奖记录ID
            [['key', 's'], ''],//直接购买购物车ID 再次累加1
            [['is_set', 'd'], 0],//1：直接设置购物车数量 0：累加
        ]);
        if ($where['is_new'] || $where['new']) $new = true;
        else $new = false;
        if (!$where['productId'] && !$where['discountId']) {
            return app('json')->fail('参数错误');
        }
        $type = 0;
        $uid = (int)$request->uid();
        $activityId = 0;
        $this->services->setItem('key', $where['key'] ?? '');
        $this->services->setItem('is_set', $where['is_set'] ?? 0);
        if ($where['discountId']) {//套餐商品
            $type = 5;
            /** @var StoreDiscountsServices $discountService */
            $discountService = app()->make(StoreDiscountsServices::class);
            $discounts = $discountService->get((int)$where['discountId'], ['id', 'is_limit', 'limit_num']);
            if (!$discounts) {
                return app('json')->fail('套餐商品未找到！');
            }
            //套餐限量
            if ($discounts['is_limit']) {
                if ($discounts['limit_num'] <= 0) {
                    return app('json')->fail('套餐限量不足');
                }
                if (!CacheService::checkStock(md5($discounts['id']), 1, $type)) {
                    return app('json')->fail('套餐限量不足');
                }
            }
            $cartIds = [];
            $cartNum = 0;
            $activityId = (int)$where['discountId'];
            foreach ($where['discountInfos'] as $info) {
                [$cartId, $cartNum] = $this->services->setCart($uid, (int)$info['product_id'], 1, $info['unique'], $type, $new, $activityId, (int)$info['id']);
                $cartIds[] = $cartId;
            }
        } else {
            if ($where['secKillId']) {
                $type = 1;
                $activityId = $where['secKillId'];
            } elseif ($where['bargainId']) {
                $type = 2;
                $activityId = $where['bargainId'];
            } elseif ($where['combinationId']) {
                $type = 3;
                $activityId = $where['combinationId'];
            } elseif ($where['storeIntegralId']) {
                $type = 4;
                $activityId = $where['storeIntegralId'];
            } elseif ($where['newcomerId']) {
                $type = 7;
                $activityId = $where['newcomerId'];
            } elseif ($where['luckRecordId']) {
                $type = 8;
                $activityId = $where['luckRecordId'];
            }
            [$cartIds, $cartNum] = $this->services->setCart($uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], $type, $new, (int)$activityId);
        }
        $this->services->reset();
        if (!$cartIds) {
            return app('json')->fail('添加失败');
        } else {
            //更新秒杀详情缓存
            $this->services->cacheTag('Cart_Nums_' . $uid)->clear();
            return app('json')->successful('ok', ['cartId' => $cartIds, 'cartNum' => $cartNum]);
        }
    }

    /**
     * 购物车 删除商品
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request)
    {
        $where = $request->postMore([
            ['ids', ''],//购物车编号
        ]);
        $where['ids'] = is_array($where['ids']) ? $where['ids'] : stringToIntArray($where['ids']);
        if (!count($where['ids']))
            return app('json')->fail('参数错误!');
        if ($this->services->removeUserCart((int)$request->uid(), $where['ids'])) {
            $this->services->cacheTag('Cart_Nums_' . $request->uid())->clear();
            return app('json')->successful();
        }
        return app('json')->fail('清除失败！');
    }

    /**
     * 购物车 修改商品数量
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function num(Request $request)
    {
        $where = $request->postMore([
            ['id', 0],//购物车编号
            ['type', 1],//1购物车 id,2商品 id
            ['number', 0],//购物车编号
        ]);
        if ($where['type'] == 2) {
            $where['id'] = $this->services->value(['product_id' => $where['id'], 'is_pay' => 0, 'uid' => $request->uid()], 'id');
        }
        unset($where['type']);

        if (!$where['id'] || !$where['number'] || !is_numeric($where['id']) || !is_numeric($where['number'])) return app('json')->fail('参数错误!');
        $res = $this->services->changeUserCartNum($where['id'], $where['number'], $request->uid());
        if ($res) {
            $this->services->cacheTag('Cart_Nums_' . $request->uid())->clear();
            return app('json')->successful();
        } else {
            return app('json')->fail('修改失败');
        }
    }

    /**
     * 购物车 统计 数量 价格
     * @param Request $request
     * @return mixed
     */
    public function count(Request $request)
    {
        [$numType, $store_id] = $request->postMore([
            ['numType', true],//购物车编号
            ['store_id', 0]
        ], true);
        $uid = (int)$request->uid();
        return app('json')->success('ok', $this->services->getUserCartCount($uid, $numType, (int)$store_id));
    }

    /**
     * 购物车重选
     * @param Request $request
     * @return mixed
     */
    public function reChange(Request $request)
    {
        [$cart_id, $product_id, $unique] = $request->postMore([
            ['cart_id', 0],
            ['product_id', 0],
            ['unique', '']
        ], true);
        $this->services->modifyCart($cart_id, $product_id, $unique);

        $this->services->cacheTag('Cart_Nums_' . $request->uid())->clear();

        return app('json')->success('重选成功');
    }

    /**
     * 计算用户购物车商品（优惠活动、最优优惠券）
     * @param Request $request
     * @return mixed
     */
    public function computeCart(Request $request)
    {
        [$cartId, $shipping_type] = $request->postMore([
            'cartId',
            ['shipping_type', -1]
        ], true);
        $uid = (int)$request->uid();
        $result = $this->services->computeUserCart($uid, $cartId, (int)$shipping_type);
        return app('json')->success($result);
    }
}
