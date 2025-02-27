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
namespace app\controller\admin\v1\other\export;

use app\controller\admin\AuthController;
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\agent\AgentManageServices;
use app\services\agent\DivisionServices;
use app\services\order\StoreOrderInvoiceServices;
use app\services\other\export\ExportServices;
use app\services\order\StoreOrderServices;
use app\services\other\Import\ImportRecordErrorServices;
use app\services\other\queue\QueueAuxiliaryServices;
use app\services\other\queue\QueueServices;
use app\services\product\product\StoreProductServices;
use app\services\other\ExpressServices;
use app\services\store\SystemStoreServices;
use app\services\supplier\finance\SupplierFlowingWaterServices;
use app\services\system\form\SystemFormDataServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserBillServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserRechargeServices;
use app\services\wechat\WechatUserServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 导出excel类
 * Class ExportExcel
 * @package app\controller\admin\v1\export
 */
class ExportExcel extends AuthController
{
    /**
     * @var ExportServices
     */
    #[Inject]
    protected ExportServices $service;

    /**
     * 保存用户资金监控的excel表格
     * @param UserMoneyServices $services
     * @return mixed
     */
    public function userFinance(UserMoneyServices $services)
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['type', ''],
        ]);
        $data = $services->getMoneyList($where, '*', $this->service->limit);
        return $this->success($this->service->userFinance($data['data'] ?? []));
    }

    /**
     * 用户佣金
     * @param UserBrokerageServices $services
     * @return mixed
     */
    public function userCommission(UserBrokerageServices $services)
    {
        $where = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['price_max', ''],
            ['price_min', ''],
            ['excel', '1'],
            ['date', '', '', 'time']
        ]);
        $data = $services->getCommissionList($where, $this->service->limit);
        return $this->success($this->service->userCommission($data['list'] ?? []));
    }

    /**
     * 用户积分
     * @param UserBillServices $services
     * @return mixed
     */
    public function userPoint(UserBillServices $services)
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['excel', '1'],
        ]);
        $data = $services->getPointList($where, '*', $this->service->limit);
        return $this->success($this->service->userPoint($data['list'] ?? []));
    }

    /**
     * 用户充值
     * @param UserRechargeServices $services
     * @return mixed
     */
    public function userRecharge(UserRechargeServices $services)
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', ''],
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['excel', '1'],
        ]);
        $data = $services->getRechargeList($where, '*', $this->service->limit);
        return $this->success($this->service->userRecharge($data['list'] ?? []));
    }

    /**
     * 分销管理 用户推广
     * @param AgentManageServices $services
     * @return mixed
     */
    public function userAgent(AgentManageServices $services)
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['data', ''],
            ['excel', '1'],
        ]);
        $data = $services->agentSystemPage($where, $this->service->limit);
        return $this->success($this->service->userAgent($data['list']));
    }

    /**
     * 微信用户导出
     * @param WechatUserServices $services
     * @return mixed
     */
    public function wechatUser(WechatUserServices $services)
    {
        $where = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['data', ''],
            ['tagid_list', ''],
            ['groupid', '-1'],
            ['sex', ''],
            ['export', '1'],
            ['subscribe', '']
        ]);
        $tagidList = explode(',', $where['tagid_list']);
        foreach ($tagidList as $k => $v) {
            if (!$v) {
                unset($tagidList[$k]);
            }
        }
        $tagidList = array_unique($tagidList);
        $where['tagid_list'] = implode(',', $tagidList);
        $data = $services->exportData($where);
        return $this->success($this->service->wechatUser($data));
    }

    /**
     * 商铺砍价活动导出
     * @param StoreBargainServices $services
     * @return mixed
     */
    public function storeBargain(StoreBargainServices $services)
    {
        $where = $this->request->getMore([
            ['start_status', ''],
            ['status', ''],
            ['store_name', ''],
            ['page', 0]
        ]);
        $where['is_del'] = 0;
        $page = $where['page'];
        unset($where['page']);
        $data = $services->getList($where, $page, $this->service->limit);
        return $this->success($this->service->storeBargain($data));
    }

    /**
     * 商铺拼团导出
     * @param StoreCombinationServices $services
     * @return mixed
     */
    public function storeCombination(StoreCombinationServices $services)
    {
        $where = $this->request->getMore([
            ['start_status', ''],
            ['is_show', ''],
            ['store_name', ''],
            ['page', 0]
        ]);
        $where['is_del'] = 0;
        $page = $where['page'];
        unset($where['page']);
        $data = $services->getList($where, $page, $this->service->limit);
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $countAll = $storePinkServices->getPinkCount([]);
        $countTeam = $storePinkServices->getPinkCount(['k_id' => 0, 'status' => 2]);
        $countPeople = $storePinkServices->getPinkCount(['k_id' => 0]);
        foreach ($data as &$item) {
            $item['count_people'] = $countPeople[$item['id']] ?? 0;//拼团数量
            $item['count_people_all'] = $countAll[$item['id']] ?? 0;//参与人数
            $item['count_people_pink'] = $countTeam[$item['id']] ?? 0;//成团数量
        }
        return $this->success($this->service->storeCombination($data));
    }

    /**
     * 商铺秒杀导出
     * @param StoreSeckillServices $services
     * @return mixed
     */
    public function storeSeckill(StoreSeckillServices $services)
    {
        $where = $this->request->getMore([
            ['start_status', ''],
            ['status', ''],
            ['store_name', ''],
            ['page', 0]
        ]);
        $where['is_del'] = 0;
        $page = $where['page'];
        unset($where['page']);
        $data = $services->getList($where, $page, $this->service->limit);
        return $this->success($this->service->storeSeckill($data));
    }

    /**
     * 导出商品卡号、卡密模版
     * @return mixed
     */
    public function storeProductCardTemplate()
    {
        return $this->success($this->service->storeProductCardTemplate());
    }

    /**
     * 商铺产品导出
     * @param StoreProductServices $services
     * @return mixed
     */
    public function storeProduct(StoreProductServices $services)
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', '', '', 'status'],
            ['store_id', ''],
            ['supplier_id', ''],
            ['sales', 'normal'],
            ['store_label_id', ''],
            ['brand_id', ''],
            ['ids', ''],
            ['all', 0]
        ]);
        if (!$where['ids'] && $where['all'] == 0) return $this->fail('请选择批处理商品');
        if ($where['supplier_id']) {
            $where['relation_id'] = $where['supplier_id'];
            $where['type'] = 2;
        } else {
            $where['pid'] = 0;
        }
        $where['ids'] = stringToIntArray($where['ids']);

        unset($where['supplier_id'], $where['store_id'], $where['all']);

        $data = $services->searchList($where, true, $this->service->limit);
        return $this->success($this->service->storeProduct($data['list'] ?? []));
    }

    /**
     * 订单列表导出
     * @param StoreOrderServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeOrder(StoreOrderServices $services)
    {
        $where_tmp = $this->request->getMore([
            ['status', ''],
            ['real_name', ''],
            ['is_del', ''],
            ['data', '', '', 'time'],
            ['type', ''],
            ['export_type', ''],
            ['pay_type', ''],
            ['plat_type', -1],
            ['order', ''],
            ['field_key', ''],
            ['store_id', ''],
            ['supplier_id', ''],
            ['ids', '']
        ]);
        $type = $where_tmp['export_type'];
        unset($where_tmp['export_type']);
        $with = [];
        if ($where_tmp['ids']) {
            $where['id'] = stringToIntArray($where_tmp['ids']);
        } else {
            $where['pid'] = 0;
        }
        if ($type) {
            $where['status'] = 1;
            $where['paid'] = 1;
            $where['is_del'] = 0;
            $where['shipping_type'] = 1;
            $where['is_system_del'] = 0;
            $with = ['pink', 'refund' => function ($query) {
                $query->whereIn('refund_type', [0, 1, 2, 4, 5])->where('is_cancel', 0)->where('is_del', 0)->field('id,store_order_id');
            }];
        }
        if (!$where_tmp['ids'] && !$type) {
            unset($where_tmp['ids']);
            $where = $where_tmp;
        }
        $where['is_system_del'] = 0;
        $where['plat_type'] = $where_tmp['plat_type'];
        $where['store_id'] = $where_tmp['store_id'];
        $where['supplier_id'] = $where_tmp['supplier_id'];
        $data = $services->getExportList($where, $with, $this->service->limit);
        return $this->success($this->service->storeOrder($data, $type));
    }

    /**
     * 获取提货点
     * @return mixed
     */
    public function storeMerchant(SystemStoreServices $services)
    {
        $where = $this->request->getMore([
            [['keywords', 's'], ''],
            [['type', 'd'], 0],
        ]);
        $data = $services->getExportData($where);
        return $this->success($this->service->storeMerchant($data));
    }

    /**
     * 会员卡导出
     * @param int $id
     * @param MemberCardServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberCard(int $id, MemberCardServices $services)
    {
        $data = $services->getExportData(['batch_card_id' => $id], $this->service->limit);
        return $this->success($this->service->memberCard($data));
    }

    /**
     * 导出批量任务发货的记录
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchOrderDelivery($id, $queueType, $cacheType)
    {
        /** @var QueueAuxiliaryServices $auxiliaryService */
        $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueInfo = $queueService->getQueueOne(['id' => $id]);
        if (!$queueInfo) $this->fail("数据不存在");
        $queueValue = json_decode($queueInfo['queue_in_value'], true);
        if (!$queueValue || !isset($queueValue['cacheType'])) $this->fail("数据参数缺失");
        $data = $auxiliaryService->getExportData(['binding_id' => $id, 'type' => $cacheType], $this->service->limit);
        return $this->success($this->service->batchOrderDelivery($data, $queueType));
    }

    /**
     * 物流公司表导出
     * @return mixed
     */
    public function expressList()
    {
        /** @var ExpressServices $expressService */
        $expressService = app()->make(ExpressServices::class);
        $data = $expressService->apiExpressList();
        return $this->success($this->service->expressList($data));
    }

    /**
     * 导出积分兑换订单
     * @param StoreIntegralOrderServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeIntegralOrder(StoreIntegralOrderServices $services)
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['real_name', ''],
            ['data', '', '', 'time'],
            ['order', ''],
            ['field_key', ''],
            ['product_id', '']
        ]);
        $where['is_system_del'] = 0;
        $data = $services->getExportList($where, $this->service->limit);
        return $this->success($this->service->storeIntegralOrder($data));
    }


    /**
     * 供应商资金流水导出
     * @param SupplierFlowingWaterServices $services
     * @return mixed
     */
    public function waterExport(SupplierFlowingWaterServices $services)
    {
        $where = $this->request->getMore([
            ['supplier_id', 0],
            ['keyword', ''],
            ['data', '', '', 'time'],
        ]);
        $where['is_del'] = 0;
        $data = $services->getList($where);
        return $this->success($this->service->SupplierFinanceRecord($data['list'] ?? [], '供应商资金流水导出'));
    }

    /**
     * 供应商账单下载
     * @param SupplierFlowingWaterServices $services
     * @return mixed
     */
    public function waterRecord(SupplierFlowingWaterServices $services)
    {
        [$ids] = $this->request->getMore([
            ['ids', ''],
            ['supplier_id', 0]
        ], true);
        $where['id'] = stringToIntArray($ids);
        $where['is_del'] = 0;
        $data = $services->getList($where);
        return $this->success($this->service->SupplierFinanceRecord($data['list'] ?? []));
    }

    /**
     * 发票导出
     * @param StoreOrderInvoiceServices $services
     * @return mixed
     */
    public function invoiceExport(StoreOrderInvoiceServices $services)
    {
        $where = $this->request->getMore([
            ['status', 0],
            ['real_name', ''],
            ['header_type', ''],
            ['type', ''],
            ['data', '', '', 'time'],
            ['field_key', ''],
        ]);
        $data = $services->getList($where);
        return $this->success($this->service->invoiceRecord($data['list'] ?? []));
    }

    /**
     * 系统表单收集数据导出
     * @param SystemFormDataServices $systemFormDataServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException'
     */
    public function systemFormDataExport(SystemFormDataServices $systemFormDataServices, $id)
    {
        $where = $this->request->postMore([
            ['data', '', '', 'time']
        ]);
        $data = $systemFormDataServices->getFormDataList((int)$id, $where);
        return $this->success($this->service->systemFormData($data['list'] ?? []));
    }

    public function divisionOrderExport()
    {
        $where = $this->request->getMore([
            ['time', '',],
            ['field_key', ''],
            ['real_name', ''],
            ['division_id', ''],
            ['division_agent_id', ''],
        ]);
        $adminInfo = $this->request->adminInfo();
        $data = app()->make(DivisionServices::class)->divisionOrder($adminInfo, $where);
        return $this->success($this->service->divisionOrder($data['data'], 1));
    }

    /**
     * 导入用户错误记录导出
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/19 下午5:34
     */
    public function importUserExport()
    {
        $where = $this->request->getMore([
            ['record_id', '',],
        ]);
        if (!$where['record_id']) {
            return $this->fail('参数错误');
        }
        $data = app()->make(ImportRecordErrorServices::class)->getErrorList($where);
        return $this->success($this->service->importUser($data['list'] ?? []));
    }


    /**
     * 商品迁移数据导出
     * @param StoreProductServices $services
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/1/6 下午5:10
     */
    public function storeProductImport(StoreProductServices $services)
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', '', '', 'status'],
            ['store_id', ''],
            ['supplier_id', ''],
            ['sales', 'normal'],
            ['store_label_id', ''],
            ['brand_id', ''],
            ['ids', ''],
            ['all', 0]
        ]);
        if (!$where['ids'] && $where['all'] == 0) return $this->fail('请选择批处理商品');
        if ($where['supplier_id']) {
            $where['relation_id'] = $where['supplier_id'];
            $where['type'] = 2;
        } else {
            $where['pid'] = 0;
        }
        $where['ids'] = stringToIntArray($where['ids']);

        unset($where['supplier_id'], $where['store_id'], $where['all']);

        $data = $services->searchList($where, true, $this->service->limit);
        return $this->success($this->service->storeProductImport($data['list'] ?? []));
    }
}
