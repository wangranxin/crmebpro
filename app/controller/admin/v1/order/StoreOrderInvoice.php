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

use app\controller\admin\AuthController;
use app\services\order\StoreOrderInvoiceServices;
use app\services\order\StoreOrderServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserServices;
use think\annotation\Inject;

/**
 * 发票管理
 * Class StoreOrderInvoice
 * @package app\controller\admin\v1\order
 */
class StoreOrderInvoice extends AuthController
{

    /**
     * @var StoreOrderInvoiceServices
     */
    #[Inject]
    protected StoreOrderInvoiceServices $services;

    /**
     * 获取订单类型数量
     * @return mixed
     */
    public function chart()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            [['type', 'd'], 0],
        ]);
        $data = $this->services->chart($where);
        return $this->success($data);
    }

    /**
     * 查询发票列表
     * @return mixed
     */
    public function list()
    {
        $where = $this->request->getMore([
            ['status', 0],
            ['real_name', ''],
            ['header_type', ''],
            ['type', ''],
            ['data', '', '', 'time'],
            ['field_key', ''],
        ]);
        return $this->success($this->services->getList($where));
    }


    /**
     * 设置发票状态
     * @param string $id
     * @return mixed
     */
    public function set_invoice($id = '')
    {
        if ($id == '') return $this->fail('缺少参数');
        $data = $this->request->postMore([
            ['is_invoice', 0],
            ['invoice_number', 0],
            ['invoice_amount', 0],
            ['remark', '']
        ]);
        if ($data['is_invoice'] == 1 && !$data['invoice_number']) {
            return $this->fail('请填写开票号');
        }
        if ($data['invoice_number'] && !preg_match('/^\d{8,20}$/', $data['invoice_number'])) {
            return $this->fail('请填写正确的开票号');
        }
        $this->services->setInvoice((int)$id, $data);
        return $this->success('设置成功');
    }

    /**
     * 订单详情
     * @param $id 订单id
     * @return mixed
     */
    public function orderInfo(StoreProductServices $productServices, StoreOrderServices $orderServices, $id)
    {
        if (!$id || !($orderInfo = $orderServices->get($id))) {
            return $this->fail('订单不存在');
        }
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->get($orderInfo['uid']);
        if (!$userInfo) {
            return app('json')->fail('用户信息不存在');
        }
        $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
        $userInfo['spread_name'] = '';
        if ($userInfo['spread_uid'])
            $userInfo['spread_name'] = $services->value(['uid' => $userInfo['spread_uid']], 'nickname');
        $orderInfo = $orderServices->tidyOrder($orderInfo->toArray(), true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;

        $orderInfo['add_time'] = $orderInfo['_add_time'] ?? '';
		$cateIds = [];
		foreach ($orderInfo['cartInfo'] as &$item) {
			$cateIds = array_merge($cateIds, explode(',', $item['productInfo']['cate_id'] ?? ''));
		}
		$cateIds = implode(',', array_unique($cateIds));
		/** @var StoreProductCategoryServices $categoryService */
		$categoryService = app()->make(StoreProductCategoryServices::class);
		$cateList = $categoryService->getCateParentAndChildName($cateIds);
        foreach ($orderInfo['cartInfo'] as &$item) {
			$item['class_name'] = '';
			if (isset($item['productInfo']['cate_id']) && $item['productInfo']['cate_id']) {
				$cate_name = $categoryService->getCateName(explode(',', $item['productInfo']['cate_id']), $cateList);
				if ($cate_name) {
					$item['class_name'] = is_array($cate_name) ? implode(',', $cate_name) : '';
				}
			}
        }
        if ($orderInfo['store_id'] && $orderInfo['shipping_type'] == 2) {
            /** @var  $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['_store_name'] = $storeServices->value(['id' => $orderInfo['store_id']], 'name');
        } else {
            $orderInfo['_store_name'] = '';
        }
        $userInfo = $userInfo->toArray();
        $invoice = $this->services->getOne(['order_id' => $id]);
        return app('json')->success(compact('orderInfo', 'userInfo', 'invoice'));
    }
}
