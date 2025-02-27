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
namespace app\controller\supplier\product;


use app\controller\admin\v1\system\SystemClearData;
use app\jobs\BatchHandleJob;
use app\services\other\queue\QueueServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\product\StoreProductBatchProcessServices;
use app\services\product\product\StoreProductServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\sku\StoreProductRuleServices;
use app\services\user\label\UserLabelCateServices;
use app\services\user\label\UserLabelServices;
use crmeb\services\UploadService;
use think\annotation\Inject;
use app\controller\supplier\AuthController;

/**
 * Class StoreProduct
 * @package app\controller\supplier\product
 */
class StoreProduct extends AuthController
{

    /**
     * @var StoreProductServices
     */
    #[Inject]
    protected StoreProductServices $services;

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header(StoreProductCategoryServices $storeProductCategoryServices)
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', 1, '', 'status'],
            ['show_type', ''],
            ['sales', 'normal'],
            ['pid', ''],
            ['data', '', '', 'time'],
            ['store_label_id', ''],
            ['brand_id', '']
        ]);
        $cateId = $where['cate_id'];
        if ($cateId) {
            $cateId = is_string($cateId) ? [$cateId] : $cateId;
            $cateId = array_merge($cateId, $storeProductCategoryServices->getColumn(['pid' => $cateId], 'id'));
            $cateId = array_unique(array_diff($cateId, [0]));
        }
        $where['cate_id'] = $cateId;
        $where['supplier_id'] = $this->supplierId;
        $list = $this->services->getHeader(0, $where);
        return app('json')->success(compact('list'));
    }

    /**
     * 显示资源列表
     * @return mixed
     */
    public function index(StoreProductCategoryServices $storeProductCategoryServices)
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', 1, '', 'status'],
            ['show_type', ''],
            ['sales', 'normal'],
            ['pid', ''],
            ['data', '', '', 'time'],
            ['store_label_id', ''],
            ['brand_id', '']
        ]);
        $cateId = $where['cate_id'];
        if ($cateId) {
            $cateId = is_string($cateId) ? [$cateId] : $cateId;
            $cateId = array_merge($cateId, $storeProductCategoryServices->getColumn(['pid' => $cateId], 'id'));
            $cateId = array_unique(array_diff($cateId, [0]));
        }
        $where['cate_id'] = $cateId;
        $where['relation_id'] = $this->supplierId;
        $where['type'] = 2;
        $data = $this->services->getList($where);
        return app('json')->success($data);
    }

    /**
     * 获取选择的商品列表
     * @return mixed
     */
    public function search_list()
    {
        $where = $this->request->getMore([
            ['cate_id', ''],
            ['store_name', ''],
            ['type', 1, '', 'status'],
            ['is_live', 0],
            ['is_new', ''],
            ['is_vip_product', ''],
            ['is_presale_product', ''],
            ['data', '', '', 'time'],
            ['store_label_id', ''],
            ['brand_id', '']
        ]);
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $where['type'] = 2;
        $where['relation_id'] = $this->supplierId;
        /** @var StoreProductCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreProductCategoryServices::class);
        if ($where['cate_id'] !== '') {
            if ($storeCategoryServices->value(['id' => $where['cate_id']], 'pid')) {
                $where['sid'] = $where['cate_id'];
            } else {
                $where['cid'] = $where['cate_id'];
            }
        }
        unset($where['cate_id']);
        $list = $this->services->searchList($where);
        return $this->success($list);
    }

    /**
     * 获取分类cascader格式数据
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascader_list(StoreProductCategoryServices $services)
    {
        return app('json')->success($services->cascaderList(2, (int)$this->supplierId));
    }

    /**
     * 获取商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id = 0)
    {
        return app('json')->success($this->services->getInfo((int)$id));
    }

    /**
     * 保存新建或编辑
     * @param StoreProductAttrServices $attrServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save(StoreProductAttrServices $attrServices, $id)
    {
        $data = $this->request->postMore([
            ['product_type', 0],//商品类型
            ['supplier_id', 0],//供应商ID
            ['cate_id', []],
            ['store_name', ''],
            ['store_info', ''],
            ['keyword', ''],
            ['unit_name', '件'],
            ['recommend_image', ''],
            ['slider_image', []],
            ['is_sub', []],//佣金是单独还是默认
            ['sort', 0],
//            ['sales', 0],
            ['ficti', 100],
            ['give_integral', 0],
            ['is_show', 0],
            ['is_hot', 0],
            ['is_benefit', 0],
            ['is_best', 0],
            ['is_new', 0],
            ['mer_use', 0],
            ['is_postage', 0],
            ['is_good', 0],
            ['description', ''],
            ['spec_type', 0],
            ['video_open', 0],
            ['video_link', ''],
            ['items', []],
            ['attrs', []],
            ['attr', []],
            ['recommend', []],//商品推荐
            ['activity', []],
            ['coupon_ids', []],
            ['label_id', []],
            ['command_word', ''],
            ['tao_words', ''],
            ['type', 0, '', 'is_copy'],
            ['delivery_type', []],//物流设置
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['temp_id', 0],//运费模版
            ['recommend_list', []],
            ['brand_id', []],
            ['soure_link', ''],
            ['bar_code', ''],
            ['code', ''],
            ['is_support_refund', 1],//是否支持退款
            ['is_presale_product', 0],//预售商品开关
            ['presale_time', []],//预售时间
            ['presale_day', 0],//预售发货日
            ['is_vip_product', 0],//是否付费会员商品
            ['auto_on_time', 0],//自动上架时间
            ['auto_off_time', 0],//自动下架时间
            ['custom_form', []],//自定义表单
            ['system_form_id', 0],//系统表单ID
            ['store_label_id', []],//商品标签
            ['ensure_id', []],//商品保障服务区
            ['specs', []],//商品参数
            ['specs_id', 0],//商品参数ID
            ['is_limit', 0],//是否限购
            ['limit_type', 0],//限购类型
            ['limit_num', 0]//限购数量
        ]);
        $data['is_verify'] = 0;
        //供应商商品不支持门店
        $data['delivery_type'] = [1];
        $this->services->saveData((int)$id, $data, 2, (int)$this->supplierId);

        $this->services->cacheTag()->clear();
        $attrServices->cacheTag()->clear();
        return $this->success($id ? '保存商品信息成功' : '添加商品成功!');
    }

    /**
     * 保存编辑
     * @param StoreBranchProductAttrValueServices $services
     * @param $id
     * @return \think\Response
     */
    public function update(StoreBranchProductAttrValueServices $services, $id = 0)
    {
        $data = $this->request->postMore([
            ['attrs', []],
            ['label_id', []],
            ['is_show', 1]
        ]);
        $storeId = $this->storeId;
        $services->updataAll((int)$id, (array)$data, (int)$storeId);
        return app('json')->success('保存商品信息成功');
    }


    /**
     * 获取关联用户标签列表
     * @param UserLabelServices $service
     * @return mixed
     */
    public function getUserLabel(UserLabelCateServices $userLabelCateServices, UserLabelServices $service)
    {
        $cate = $userLabelCateServices->getLabelCateAll(2, (int)$this->supplierId);
        $data = [];
        $label = [];
        if ($cate) {
            foreach ($cate as $value) {
                $data[] = [
                    'id' => $value['id'] ?? 0,
                    'value' => $value['id'] ?? 0,
                    'label_cate' => 0,
                    'label_name' => $value['name'] ?? '',
                    'label' => $value['name'] ?? '',
                    'store_id' => $value['store_id'] ?? 0,
                    'type' => $value['type'] ?? 1,
                ];
            }
            $label = $service->getColumn(['type' => 2, 'relation_id' => $this->supplierId], '*');
            if ($label) {
                foreach ($label as &$item) {
                    $item['label'] = $item['label_name'];
                    $item['value'] = $item['id'];
                }
            }
        }
        return app('json')->success($service->get_tree_children($data, $label));
    }

    /**
     * 修改状态
     * @param StoreBranchProductServices $services
     * @param $is_show
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_show(StoreBranchProductServices $services, $is_show = '', $id = '')
    {
        if (!$id) return $this->fail('缺少商品ID');
        $services->setShow($this->supplierId, $id, $is_show);
        return $this->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 获取规格模板
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_rule(StoreProductRuleServices $services)
    {
        return $this->success($services->getRule(2, (int)$this->supplierId));
    }

    /**
     * 获取商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_product_info($id = 0)
    {
        return $this->success($this->services->getInfo((int)$id));
    }


    /**
     * 获取运费模板列表
     * @param ShippingTemplatesServices $services
     * @return \think\Response
     */
    public function get_template(ShippingTemplatesServices $services)
    {
        return $this->success($services->getTemp(2, (int)$this->supplierId));
    }

    /**
     * 获取视频上传token
     * @return mixed
     * @throws \Exception
     */
    public function getTempKeys()
    {
        $upload = UploadService::init();
        $type = (int)sys_config('upload_type', 1);
        $key = $this->request->get('key', '');
        $path = $this->request->get('path', '');
        $contentType = $this->request->get('contentType', '');
        if ($type === 5) {
            if (!$key || !$contentType) {
                return app('json')->fail('缺少参数');
            }
            $re = $upload->getTempKeys($key, $path, $contentType);
        } else {
            $re = $upload->getTempKeys();
        }
        return $re ? $this->success($re) : $this->fail($upload->getError());
    }

    /**
     * 获取商品所有规格数据
     * @param StoreBranchProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function getAttrs(StoreBranchProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        return $this->success($services->getStoreProductAttr((int)$id));
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //删除商品检测是否有参与活动
//        $this->services->checkActivity($id);
        $res = $this->services->del($id);
        event('product.delete', [$id]);
        return $this->success($res);
    }

    /**
     * 生成规格列表
     * @param int $id
     * @param int $type
     * @return mixed
     */
    public function is_format_attr($id = 0, $type = 0)
    {
        $data = $this->request->postMore([
            ['attrs', []],
            ['items', []],
            ['product_type', 0]

        ]);
//        if ($id > 0 && $type == 1) $this->services->checkActivity($id);
        $info = $this->services->getAttr($data, $id, $type, 2, 0, true);
        return $this->success(compact('info'));
    }

    /**
     * 快速修改商品规格库存
     * @param StoreProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function saveProductAttrsStock(StoreProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        [$attrs] = $this->request->getMore([
            ['attrs', []]
        ], true);
        if (!$attrs) {
            return $this->fail('请重新修改规格库存');
        }
        foreach ($attrs as $attr) {
            if (!isset($attr['unique']) || !isset($attr['pm']) || !isset($attr['stock'])) {
                return $this->fail('请重新修改规格库存');
            }
        }
        return $this->success(['stock' => $services->saveProductAttrsStock((int)$id, $attrs)]);
    }

    /**
     * 设置批量商品上架
     * @return mixed
     */
    public function product_show()
    {
        [$ids, $all, $where] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['where', []],
        ], true);
        if ($all == 0) {//单页不走队列
            if (empty($ids)) return $this->fail('请选择需要上架的商品');
            $this->services->setShow($ids, 1);
            return $this->success('上架成功');
        }
        if ($all == 1) {
            $ids = [];
            if (isset($where['type'])) {
                $where['status'] = $where['type'];
                unset($where['type']);
            }
            $where['type'] = 2;
            $where['relation_id'] = $this->supplierId;
        }
        $type = 4;//商品上架
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch(['up', $type]);
        return $this->success('后台程序已执商品上架任务!');
    }

    /**
     * 设置批量商品下架
     * @return mixed
     */
    public function product_unshow()
    {
        [$ids, $all, $where] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['where', []],
        ], true);
        if ($all == 0) {//单页不走队列
            if (empty($ids)) return $this->fail('请选择需要下架的商品');
            $this->services->setShow($ids, 0);
            return $this->success('下架成功');
        }
        if ($all == 1) {
            $all_ids = $this->services->getColumn(['is_show' => 1, 'is_del' => 0, 'type' => 2, 'relation_id' => $this->supplierId], 'id');
//			$this->services->checkActivity($all_ids);
            $ids = [];
            if (isset($where['type'])) {
                $where['status'] = $where['type'];
                unset($where['type']);
            }
            $where['type'] = 2;
            $where['relation_id'] = $this->supplierId;
        }
        $type = 4;//商品下架
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch(['down', $type]);
        return $this->success('后台程序已执商品下架任务!');
    }

    /**
     * 商品批量操作
     * @param StoreProductBatchProcessServices $batchProcessServices
     * @return mixed
     */
    public function batchProcess(StoreProductBatchProcessServices $batchProcessServices)
    {
        [$type, $ids, $all, $where, $data] = $this->request->postMore([
            ['type', 1],
            ['ids', ''],
            ['all', 0],
            ['where', ""],
            ['data', []]
        ], true);
        if (!$ids && $all == 0) return $this->fail('请选择批处理商品');
        if (!$data) {
            return $this->fail('请选择批处理数据');
        }
        if (isset($where['type'])) {
            $where['status'] = $where['type'];
            unset($where['type']);
        }
        $where['type'] = 2;
        $where['relation_id'] = $this->supplierId;
        //批量操作
        $batchProcessServices->batchProcess((int)$type, $ids, $data, !!$all, $where);
        return app('json')->success('已加入消息队列,请稍后查看');
    }

    /**
     * 删除回收站商品
     * @param $id
     * @param SystemClearData $clearData
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/9/30 15:39
     */
    public function thoroughDelete($id, SystemClearData $clearData)
    {
        $info = $this->services->getOne(['id' => $id]);
        if (!$info) return $this->fail('商品不存在');
        if ($info->relation_id != $this->supplierId) return $this->fail('只能删除供应商自己商品');
        if ($info->is_del == 0) return $this->fail('只能删除回收站商品');

        $clearData->recycleProduct([$id]);

        return $this->success('删除成功');
    }

}
