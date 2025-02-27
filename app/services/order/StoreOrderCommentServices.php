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

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\jobs\order\AutoCommentOrderJob;
use app\services\BaseServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 订单评价
 * Class StoreOrderCommentServices
 * @package app\services\order
 * @mixin StoreOrderDao
 */
class StoreOrderCommentServices extends BaseServices
{
    /**
     * 评论默认数据
     * @var string[]
     */
    protected array $commentArr = [
        'reply_score' => 3,
        'product_score' => 5,
        'service_score' => 5,
        'delivery_score' => 5,
        'comment' => '',
        'pics' => [],
    ];

    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * 订单评价
     * @param int $oid
     * @param int $uid
     * @param array $group
     * @param string $unique
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function comment(int $oid, int $uid = 0, array $group = [], string $unique = '')
    {
        if (!$oid) throw new ValidateException('参数错误!');
        $order = $this->dao->get($oid);
        if (!$order) {
            throw new ValidateException('订单不存在!');
        }
        $where = ['oid' => $oid];
        if ($unique) {
            $where['unique'] = $unique;
        }
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfos = $cartInfoServices->getColumn($where, 'oid,product_id,cart_info,unique');
        if (!$cartInfos) {
            throw new ValidateException('评价商品不存在');
        }
        $is_sync = $group['is_sync'] ?? 0;
        unset($group['is_sync']);
        $group = array_merge($this->commentArr, array_intersect_key($group, $this->commentArr));
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if ($group['product_score'] < 1) throw new ValidateException('请为商品评分');
        if ($group['service_score'] < 1) throw new ValidateException('请为商家服务评分');
        if ($group['delivery_score'] < 1) throw new ValidateException('请为物流服务评分');
        if ($group['pics']){
            $pics = is_array($group['pics']) ? $group['pics'] : explode(',', $group['pics']);
            $group['pics'] = json_encode($pics);
        }
        $user_info = [];
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user_info = $userServices->getUserInfo($uid);
        }
        if ($order['store_id']) {
            $group['type'] = 1;
            $group['relation_id'] = $order['store_id'];
        } elseif ($order['supplier_id']) {
            $group['type'] = 2;
            $group['relation_id'] = $order['supplier_id'];
        } else {
            $group['type'] = $group['relation_id'] = 0;
        }
        $group = array_merge($group, [
            'uid' => $uid,
            'nickname' => $user_info['nickname'] ?? substr(md5($oid . time()), 0, 12),
            'avatar' => $user_info['avatar'] ?? sys_config('h5_avatar'),
            'oid' => $oid,
            'add_time' => time(),
            'reply_type' => 'product'
        ]);
        /** @var StoreProductReplyServices $replyServices */
        $replyServices = app()->make(StoreProductReplyServices::class);
        $data_all = [];
        $ids = [];
        $sync = [];
        foreach ($cartInfos as $cartInfo) {
            if ($replyServices->be(['oid' => $cartInfo['oid'], 'unique' => $cartInfo['unique']])) continue;
            $_info = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
            if (isset($_info['activity_id']) && $_info['activity_id']) $productId = $_info['product_id'];
            else $productId = $cartInfo['product_id'];
            $ids[] = $productId;
            $group['unique'] = $cartInfo['unique'];
            $group['product_id'] = $productId;
            $group['sku_unique'] = $_info['productInfo']['attrInfo']['unique'] ?? '';
            $group['sku'] = $_info['productInfo']['attrInfo']['suk'] ?? '';
            $data_all[] = $group;
        }
        if ($is_sync) {
            $sync = [
                'content_type' => 1,
                'content' => $group['comment'] ?? '',
                'slider_image' => $pics ?? [],
                'relation_id' => $uid,
                'product_id' => $ids
            ];
        }
        if ($data_all) {
            $res = $replyServices->saveAll($data_all);
            if (!$res) {
                throw new ValidateException('评价失败!');
            }
        }
        try {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $orderServices->checkOrderOver($replyServices, $cartInfoServices->getCartColunm(['oid' => $oid, 'is_gift' => 0], 'unique', ''), $oid);
        } catch (\Exception $e) {
            throw new ValidateException($e->getMessage());
        }
        //清除评价缓存
        $replyServices->cacheTag()->clear();
        //订单评价成功事件
        event('order.comment', [$order]);

        if ($ids) {
            //商品评价
            event('product.reply', [$ids, $is_sync, $sync]);
        }
        return true;
    }

    /**
     * 执行自动默认好评
     * @param array $where
     * @param int $page
     * @param int $maxLimit
     * @return bool
     */
    public function runAutoCommentOrder(array $where, int $page = 0, int $maxLimit = 0)
    {
        /** @var StoreOrderStoreOrderStatusServices $service */
        $service = app()->make(StoreOrderStoreOrderStatusServices::class);
        $orderList = $service->getOrderIds($where, $page, $maxLimit);
        foreach ($orderList as $order) {
            if ($order['status'] == 3) {
                continue;
            }
            $group = ['comment' => '此用户未作评价'];
            $this->comment((int)$order['id'], (int)$order['uid'], $group);
        }
        return true;
    }

    /**
     * 自动默认好评
     * @return bool
     */
    public function autoCommentOrder()
    {
        //7天前时间戳
        $systemCommentTime = (int)sys_config('system_comment_time', 0);
        //0为取消自动默认好评功能
        if ($systemCommentTime == 0) {
            return true;
        }
        $sevenDay = strtotime(date('Y-m-d H:i:s', strtotime('-' . $systemCommentTime . ' day')));
        /** @var StoreOrderStoreOrderStatusServices $service */
        $service = app()->make(StoreOrderStoreOrderStatusServices::class);
        $where = [
            'change_time' => $sevenDay,
            'is_del' => 0,
            'paid' => 1,
            'status' => 2,
            'change_type' => ['user_take_delivery', 'take_delivery', 'writeoff']
        ];
        $maxLimit = 20;
        $count = $service->getOrderCount($where);
        if ($count > $maxLimit) {
            $pages = ceil($count / $maxLimit);
            for ($i = 1; $i <= $pages; $i++) {
                AutoCommentOrderJob::dispatch([$where, $i, $maxLimit]);
            }
        } else {
            $this->runAutoCommentOrder($where);
        }
        return true;
    }
}
