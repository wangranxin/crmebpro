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


use app\services\order\StoreOrderStatusServices;
use app\services\store\SystemStoreServices;
use app\services\store\SystemStoreStaffServices;
use app\Request;
use think\annotation\Inject;
use think\facade\Log;
use think\Response;

/**
 * Class StoreController
 * @package app\api\controller\v1\store
 */
class Store
{

    /**
     * @var SystemStoreServices
     */
    #[Inject]
    protected SystemStoreServices $services;


    /**
     * 获取门店列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreList(Request $request)
    {
        [$store_type, $keywords, $latitude, $longitude] = $request->getMore([
            ['store_type', 1],
            ['keyword', ''],
            ['latitude', ''],
            ['longitude', '']
        ], true);
        if (!checkCoordinates($longitude, $latitude)) {
            return app('json')->fail('参数错误');
        }
        $where = ['uid' => (int)$request->uid(), 'store_type' => $store_type, 'keywords' => $keywords];
        $storeList = [];
        //开启门店
        if (sys_config('store_func_status', 1)) {
            $storeList = $this->services->getNearbyStore($where, $latitude, $longitude, $request->ip());
        }
        return app('json')->success($storeList);
    }

    /**
     * 获取门店客服列表
     * @param SystemStoreStaffServices $staffServices
     * @param $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCustomerList(SystemStoreStaffServices $staffServices, $store_id)
    {
        $customer = [];
        if ($store_id) {
            $customer = $staffServices->getCustomerList((int)$store_id, 'id,store_id,staff_name,avatar,customer_phone,customer_url');
        }
        return app('json')->success($customer);
    }

    /**
     * 获取客服详情
     * @param SystemStoreStaffServices $staffServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCustomerInfo(SystemStoreStaffServices $staffServices, $id)
    {
        $info = [];
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $info = $staffServices->getStaffInfo((int)$id, 'id,store_id,staff_name,avatar,customer_phone,customer_url');
        if (!$info) {
            return app('json')->fail('客服不存在');
        }
        $info = $info->toArray();
        $storeInfo = $this->services->getStoreInfo((int)$info['store_id']);
        $info['store_name'] = $storeInfo['name'] ?? '';
        $info['address'] = $storeInfo['address'] ?? '';
        $info['detailed_address'] = $storeInfo['detailed_address'] ?? '';
        $info['latitude'] = $storeInfo['latitude'] ?? '';
        $info['longitude'] = $storeInfo['longitude'] ?? '';
        $info['day_time'] = $storeInfo['day_time'] ?? '';
        $info['day_start'] = $storeInfo['day_start'] ?? '';
        $info['day_end'] = $storeInfo['day_end'] ?? '';
        return app('json')->success($info);
    }



}
