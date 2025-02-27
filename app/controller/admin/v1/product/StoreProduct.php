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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\controller\admin\v1\system\SystemClearData;
use app\jobs\BatchHandleJob;
use app\services\other\CacheServices;
use app\services\other\queue\QueueServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\product\StoreProductBatchProcessServices;
use app\services\product\product\StoreProductServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\sku\StoreProductRuleServices;
use crmeb\services\FileService;
use crmeb\services\SpreadsheetExcelService;
use crmeb\services\UploadService;
use think\annotation\Inject;

/**
 * Class StoreProduct
 * @package app\controller\admin\v1\product
 */
class StoreProduct extends AuthController
{
    /**
     * @var StoreProductServices
     */
    #[Inject]
    protected StoreProductServices $service;

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header()
    {
        $where = $this->request->getMore([
            ['field_key', ''],//商品搜索类型,商品id:product_id;名称:store_name;关键字:keyword
            ['store_name', ''],//关键字
            ['product_type', ''],//商品类型0:普通商品，1：卡密，2：优惠券，3：虚拟商品,4：次卡商品
            ['cate_id', ''],//分类
            ['brand_id', ''],//品牌
            ['delivery_type', ''],//商品配送方式1=快递，2=门店自提，3=门店配送
            ['spec_type', ''],//商品规格 0单 1多
            ['store_label_id', ''],//标签
            ['supplier_id', 0],//供应商
            ['sales_range', ''],//销量区间
            ['price_range', ''],//售价区间
            ['create_range', ''],//创建时间区间
            ['type', 1, '', 'status'],//类型
            ['is_vip',''],//付费会员价
            ['level_type',''],//等级会员价格,1:系统默认,2:自定义
            ['is_brokerage',''],//参与返佣
            ['activity_type'],//参与活动
            ['stock_range', ''],//库存区间
            ['collect_range', ''],//收藏区间
        ]);
        if ($where['supplier_id']) {
            $where['relation_id'] = $where['supplier_id'];
            $where['type'] = 2;
        } else {
            $where['pid'] = 0;
        }
        $cateId = $where['cate_id'];
        if ($cateId) {
            $cateId = is_string($cateId) ? [$cateId] : $cateId;
            $cateId = array_unique(array_diff($cateId, [0]));
        }
        $where['cate_id'] = $cateId;
        $list = $this->service->getHeader(0, $where);
        return $this->success(compact('list'));
    }

    /**
     * 获取退出未保存的数据
     * @param CacheServices $services
     * @return mixed
     */
    public function getCacheData(CacheServices $services)
    {
        return $this->success(['info' => $services->getDbCache($this->adminId . '_product_data', [])]);
    }

    /**
     * 1分钟保存一次产品数据
     * @param CacheServices $services
     * @return mixed
     */
    public function saveCacheData(CacheServices $services)
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
            ['sales', 0],
            ['ficti', 0],
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
            ['video_open', 0],//是否开启视频
            ['video_link', ''],//视频链接
            ['items', []],
            ['attrs', []],
            ['activity', []],
            ['coupon_ids', []],
            ['label_id', []],
            ['command_word', ''],
            ['tao_words', ''],
            ['type', 0],
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
        ]);
        $services->setDbCache($this->adminId . '_product_data', $data, 68400);
        return $this->success();
    }

    /**
     * 删除数据缓存
     * @param CacheServices $services
     * @return mixed
     */
    public function deleteCacheData(CacheServices $services)
    {
        $services->delectDbCache($this->adminId . '_product_data');
        return $this->success();
    }

    /**
     * 显示资源列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['field_key', ''],//商品搜索类型
            ['store_name', ''],//关键字
            ['product_type', ''],//商品类型0:普通商品，1：卡密，2：优惠券，3：虚拟商品,4：次卡商品
            ['cate_id', ''],//分类
            ['brand_id', ''],//品牌
            ['delivery_type', ''],//商品配送方式1=快递，2=门店自提，3=门店配送
            ['spec_type', ''],//商品规格 0单 1多
            ['store_label_id', ''],//标签
            ['supplier_id', 0],//供应商
            ['sales_range', ''],//销量区间
            ['price_range', ''],//售价区间
            ['create_range', ''],//创建时间区间
            ['type', 1, '', 'status'],//类型
            ['sales', 'normal'],
            ['is_vip',''],//付费会员价
            ['level_type',''],//等级会员
            ['is_brokerage',''],//参与返佣
            ['activity_type'],//参与活动
            ['stock_range', ''],//库存区间
            ['collect_range', ''],//收藏区间
        ]);
        if ($where['supplier_id']) {
            $where['relation_id'] = $where['supplier_id'];
            $where['type'] = 2;
        } else {
            $where['pid'] = 0;
        }
        $cateId = $where['cate_id'];
        if ($cateId) {
            $cateId = is_string($cateId) ? [$cateId] : $cateId;
            $cateId = array_unique(array_diff($cateId, [0]));
        }
        $where['cate_id'] = $cateId;
        unset($where['supplier_id']);
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     *  审核商品表单
     * @param $id
     * @return mixed
     */
    public function verifyForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->verifyForm($id));
    }

    /**
     * 强制下架表单
     * @param $id
     * @return mixed
     */
    public function removeForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->verifyForm($id, 2));
    }

    /**
     * 审核商品
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function setVerify($id = '')
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $data = $this->request->getMore([
            ['is_verify', 1],
            ['refusal', ''],
            ['is_show', 0],
            ['auto_on_time', 0],//自动上架时间
        ]);
        if (in_array($data['is_verify'], [-1, -2]) && !$data['refusal']) {
            return $this->fail('请输入原因');
        }
        $this->service->verify((int)$id, $data);
        return $this->success('审核成功');
    }

    /**
     * 审核前验证
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/9/27 15:47
     */
    public function getVerify($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success(['verify' => $this->service->getVerify($id) ? 1 : 0]);
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '')
    {
        $this->service->setShow([$id], $is_show);

        return $this->success($is_show == 1 ? '上架成功' : '下架成功');
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
            $this->service->setShow($ids, 1);
            return $this->success('上架成功');
        }
        if ($all == 1) {
            $ids = [];
            if (isset($where['type'])) {
                $where['status'] = $where['type'];
                unset($where['type']);
            }
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
            $this->service->setShow($ids, 0);
            return $this->success('下架成功');
        }
        if ($all == 1) {
//            $all_ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
//            $this->service->checkActivity($all_ids);
            $ids = [];
            if (isset($where['type'])) {
                $where['status'] = $where['type'];
                unset($where['type']);
            }
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
     * 批量设置商品配送方式
     * @return mixed
     */
    public function setProductDeliveryType()
    {
        [$ids, $all, $deliveryType] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['delivery_type', []],
        ], true);
        if (!$deliveryType) {
            return $this->fail('请选择配送方式');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if (count($ids) == 1) {
            $product = $this->service->get($ids[0]);
            if ($product && in_array($product['product_type'], [1, 2, 3])) {
                return $this->fail('虚拟、卡密商品不需要设置配送方式');
            }
        }
        if ($all == 1) {
            $update_where = ['is_show' => 1, 'is_del' => 0];
        } else {
            $update_where = [['id', 'in', $ids]];
        }
        if (!$this->service->update($update_where, ['delivery_type' => $deliveryType])) {
            return $this->fail('设置失败');
        }

        $this->service->cacheTag()->clear();

        return $this->success('设置成功');
    }

    /**
     * 批量设置商品标签
     * @return mixed
     */
    public function setProductlabel()
    {
        [$ids, $all, $store_label_id] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['store_label_id', []],
        ], true);
        if (!$store_label_id) {
            return $this->fail('请选择商品标签');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $update_where = ['is_show' => 1, 'is_del' => 0];
        } else {
            $update_where = [['id', 'in', $ids]];
        }
        if (!$this->service->update($update_where, ['store_label_id' => implode(',', $store_label_id)])) {
            return $this->fail('设置失败');
        }

        $this->service->cacheTag()->clear();

        return $this->success('设置成功');
    }

    /**
     * 批量设置商品保障服务
     * @return mixed
     */
    public function setProductEnsure()
    {
        [$ids, $all, $ensure_id] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['store_label_id', []],
        ], true);
        if (!$ensure_id) {
            return $this->fail('请选择商品保障服务');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $update_where = ['is_show' => 1, 'is_del' => 0];
        } else {
            $update_where = [['id', 'in', $ids]];
        }
        if (!$this->service->update($update_where, ['ensure_id' => implode(',', $ensure_id)])) {
            return $this->fail('设置失败');
        }

        $this->service->cacheTag()->clear();

        return $this->success('设置成功');
    }


    /**
     * 批量设置商品参数
     * @return mixed
     */
    public function setProductSpecs()
    {
        [$ids, $all, $specs_id, $specs] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['specs_id', 0],
            ['specs', []],
        ], true);
        if (!$specs_id) {
            return $this->fail('请选择商品参数模版');
        }
        if (!$specs) {
            return $this->fail('请添加商品参数');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $update_where = ['is_show' => 1, 'is_del' => 0];
        } else {
            $update_where = [['id', 'in', $ids]];
        }
        if (!$this->service->update($update_where, ['specs_id' => $specs_id, 'specs' => json_encode($specs)])) {
            return $this->fail('设置失败');
        }

        $this->service->cacheTag()->clear();

        return $this->success('设置成功');
    }

    /**
     * 获取规格模板
     * @param StoreProductRuleServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_rule(StoreProductRuleServices $services)
    {
        return $this->success($services->getRule());
    }

    /**
     * 获取编辑商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_product_info($id = 0)
    {
        return $this->success($this->service->getInfo((int)$id));
    }

    /**
     * 保存新建或编辑
     * @param $id
     * @return mixed
     * @throws \Exception
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
            //['is_sub', []],//佣金是单独还是默认
            ['sort', 0],
//            ['sales', 0],
            ['ficti', 0],
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
            ['limit_num', 0],//限购数量
            ['share_content', ''],//分享文案
            ['min_qty', 0],//起购数量
            ['presale_status', 0],//预售结束后状态
        ]);
        $this->service->saveData((int)$id, $data);

        $this->service->cacheTag()->clear();
        $attrServices->cacheTag()->clear();

        return $this->success($id ? '保存商品信息成功' : '添加商品成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete(StoreProductAttrServices $attrServices, $id)
    {
        //删除商品检测是否有参与活动
//        $this->service->checkActivity($id);
        $res = $this->service->del($id);
        event('product.delete', [$id]);

        $this->service->cacheTag()->clear();
        $attrServices->cacheTag()->clear();

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
        //if ($id > 0 && $type == 1) $this->service->checkActivity($id);
        $plat_type = 0;
        $relation_id = 0;
        if ((int)$id) {//编辑 需要查看商品来源
            $productInfo = $this->service->get((int)$id, ['id', 'type', 'relation_id']);
            if (!$productInfo) {
                return $this->fail('商品不存在');
            }
            $plat_type = (int)$productInfo['type'];
            $relation_id = (int)$productInfo['relation_id'];
        }
        $info = $this->service->getAttr($data, $id, $type, $plat_type, $relation_id);
        return $this->success(compact('info'));
    }

    /**
     * 获取选择的商品列表
     * @return mixed
     */
    public function search_list()
    {
        $where = $this->request->getMore([
            ['ids', ''],
            ['cate_id', ''],
            ['store_name', ''],
            ['is_integral', 0],
            ['type', '', '', 'status'],
            ['is_live', 0],
            ['is_new', ''],
            ['is_vip_product', ''],
            ['is_presale_product', ''],
            ['store_label_id', ''],
            ['is_supplier', -1],//供应商商品0:不是1:是
            ['product_type', ''],//供应商商品0:不是1:是
        ]);
//        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $where['is_verify'] = 1;
        $where['product_type'] =  $where['product_type'] ? explode(',', $where['product_type']) : '';
        /** @var StoreProductCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreProductCategoryServices::class);
        if ($where['cate_id'] !== '') {
            if ($storeCategoryServices->value(['id' => $where['cate_id']], 'pid')) {
                $where['sid'] = $where['cate_id'];
            } else {
                $where['cid'] = $where['cate_id'];
            }
        }
        if ($where['is_supplier'] == 1) {
            $where['type'] = [2];
        }
        unset($where['cate_id'], $where['is_supplier']);
        $list = $this->service->searchList($where);
        return $this->success($list);
    }

    /**
     * 获取某个商品规格
     * @return mixed
     */
    public function get_attrs()
    {
        [$id, $type] = $this->request->getMore([
            [['id', 'd'], 0],
            [['type', 'd'], 0],
        ], true);
        $info = $this->service->getProductRules($id, $type);
        return $this->success(compact('info'));
    }

    /**
     * 获取运费模板列表
     * @param ShippingTemplatesServices $services
     * @return \think\Response
     */
    public function get_template(ShippingTemplatesServices $services)
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        $type = $relation_id = 0;
        if ((int)$id) {
            $info = $this->service->get((int)$id, ['id', 'type', 'relation_id']);
            if ($info) {
                $type = (int)$info['type'];
                $relation_id = (int)$info['relation_id'];
            }
        }
        return $this->success($services->getTemp($type, $relation_id));
    }

    /**
     * 获取视频上传token
     * @return mixed
     * @throws \Exception
     */
    public function getTempKeys()
    {
        $upload = UploadService::init();
        $re = $upload->getTempKeys();
        return $re ? $this->success($re) : $this->fail($upload->getError());
    }

    /**
     * 检测商品是否开活动
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function check_activity($id)
    {
        $this->service->checkActivity($id);
        return $this->success('成功');
    }

    /**
     * 获取商品所有规格数据
     * @param StoreProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function getAttrs(StoreProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        return $this->success($services->getProductAttrValue(['product_id' => $id, 'type' => 0]));
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
     * 导入卡密
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import_card()
    {
        $data = $this->request->getMore([
            ['file', ""]
        ]);
        if (!$data['file']) return app('json')->fail('请上传文件');
        $file = public_path() . substr($data['file'], 1);
        /** @var FileService $readExcelService */
        $readExcelService = app()->make(FileService::class);
        $cardData = $readExcelService->readProductCardExcel($file, 2);
        return app('json')->success($cardData);
    }

    /**
     * 导入erp商品
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import_erp_product()
    {
        [$path] = $this->request->postMore([
            ['path', ""]
        ], true);
        if (!$path) {
            return $this->fail('请上传表格');
        }
        $path = public_path() . substr($path, 1);

        $data = SpreadsheetExcelService::getExcelData($path, ['spu' => 'A']);
        $spus = [];
        foreach ($data as $item) {
            $spus[] = $item['spu'];
        }
        $spus = array_unique(array_filter($spus));
        foreach ($spus as $spu) {
            //ProductSyncErp::dispatchDo('productFromErp', [$spu]);
        }
        unlink($path);
        return $this->success('已加入消息队列进行同步');
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
     * DateTime: 2024/9/30 15:38
     */
    public function thoroughDelete($id, SystemClearData $clearData)
    {
        $info = $this->service->getOne(['id' => $id]);
        if (!$info) return $this->fail('商品不存在');
        if ($info->is_del == 0) return $this->fail('只能删除回收站商品');

        $clearData->recycleProduct([$id]);

        return $this->success('删除成功');
    }

    /**
     * 获取分佣/会员价
     * @param $id
     * @param $type
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/10/28 15:05
     */
    public function otherInfo($id, $type)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        return $this->success($this->service->otherInfo($id, $type));
    }

    /**
     * 保存分佣/会员价
     * @param $id
     * @param $type
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/10/28 15:49
     */
    public function otherUpdate($id, $type)
    {
        $data = $this->request->postMore([
            ['is_brokerage', 0],//是否参与返佣
            ['is_sub', 0],//是否单独返佣
            ['is_vip', 0],//是否开启会员价
            ['level_type', 0],//等级会员价格,1:系统默认,2:自定义
            ['attr_value', []]//sku
        ]);
        if(!$id) return app('json')->fail('参数错误');
        $this->service->otherUpdate($id, $type, $data);
        return $this->success('保存成功');
    }

    /**
     * 导入商品
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/12/21 上午10:28
     */
    public function productImport()
    {
        [$file] = $this->request->getMore([
            ['file', ""]
        ], true);
        if (!$file) return app('json')->fail('导入数据为空');
        $res = $this->service->productImport($file);
        return app('json')->success('导入成功', $res);
    }
}
