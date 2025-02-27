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
namespace app\services\product\product;

use app\dao\product\product\StoreProductDao;
use app\jobs\product\ProductStockTips;
use app\jobs\product\ProductStockJob;
use app\services\activity\discounts\StoreDiscountsProductsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\activity\seckill\StoreSeckillTimeServices;
use app\services\agent\AgentLevelServices;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\community\CommunityServices;
use app\services\diy\DiyServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderComputedServices;
use app\services\order\StoreOrderCreateServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\brand\StoreBrandServices;
use app\services\product\ensure\StoreProductEnsureServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\product\sku\StoreProductVirtualServices;
use app\services\product\specs\StoreProductSpecsServices;
use app\services\store\SystemStoreServices;
use app\services\supplier\SystemSupplierServices;
use app\services\system\form\SystemFormServices;
use app\services\user\label\UserLabelServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserRelationServices;
use app\services\user\UserSearchServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\exceptions\AdminException;
use crmeb\services\FileService;
use crmeb\services\FormBuilder as Form;
use crmeb\services\SystemConfigService;
use crmeb\traits\OptionTrait;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Route as Url;

/**
 * Class StoreProductService
 * @package app\services\product\product
 * @mixin StoreProductDao
 */
class StoreProductServices extends BaseServices
{
    use OptionTrait;

    /**
     * @var StoreProductDao
     */
    #[Inject]
    protected StoreProductDao $dao;

    /**
     * 获取顶部标签
     * @param int $store_id
     * @param array $where
     * @return array[]
     */
    public function getHeader(int $store_id = 0, array $where = [])
    {
        if ($store_id || (isset($where['store_id']) && $where['store_id'])) {
            $where['type'] = 1;
            $where['relation_id'] = $store_id ?: $where['store_id'];
            unset($where['store_id']);
        } elseif (isset($where['supplier_id']) && $where['supplier_id']) {
            $where['type'] = 2;
            $where['relation_id'] = $where['supplier_id'];
            unset($where['supplier_id']);
        } else {
            $where['pid'] = 0;
        }
        //出售中的商品
        $onsale = $this->dao->getCount(['status' => 1] + $where);
        //已经售馨商品
        $outofstock = $this->dao->getCount(['status' => 4] + $where);
        //警戒库存商品
        $store_stock = sys_config('store_stock', 0);
        $policeforce = $this->dao->getCount(['status' => 5, 'store_stock' => $store_stock > 0 ? $store_stock : 2] + $where);
        //仓库中的商品
        $forsale = $this->dao->getCount(['status' => 2] + $where);
        //回收站商品
        $delete = $this->dao->getCount(['status' => 6] + $where);
        //待审核商品
        $unVerify = $this->dao->getCount(['status' => 0] + $where);
        //审核未通过商品
        $refuseVerify = $this->dao->getCount(['status' => -1] + $where);
        //强制下架商品
        $removeVerify = $this->dao->getCount(['status' => -2] + $where);

        return [
            ['type' => 1, 'name' => '销售中', 'count' => $onsale],
            ['type' => 2, 'name' => '仓库中', 'count' => $forsale],
            ['type' => 4, 'name' => '已售罄', 'count' => $outofstock],
            ['type' => 5, 'name' => '库存预警', 'count' => $policeforce],
            ['type' => 6, 'name' => '回收站', 'count' => $delete],
            ['type' => 0, 'name' => '待审核', 'count' => $unVerify],
            ['type' => -1, 'name' => '审核未通过', 'count' => $refuseVerify],
            ['type' => -2, 'name' => '强制下架', 'count' => $removeVerify]
        ];
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, $is_move = false)
    {
        $store_stock = sys_config('store_stock', 0);
        $where['store_stock'] = $store_stock > 0 ? $store_stock : 2;
        [$page, $limit] = $this->getPageValue();
        $order_string = '';
        $order_arr = ['asc', 'desc'];
        if (isset($where['sales']) && in_array($where['sales'], $order_arr)) {
            $order_string = 'sales ' . $where['sales'];
        }
        //门店不展示卡密商品
        $count = $this->dao->getCount($where);
        //页面搜索，第二页之后没有结果，强制返回第一页数据
        if ($count <= $limit && $page !== 1) {
            $page = 1;
        }
        $list = $this->dao->getList($where, $page, $limit, $order_string);
        if ($list) {
            $cateIds = implode(',', array_column($list, 'cate_id'));
            /** @var StoreProductCategoryServices $categoryService */
            $categoryService = app()->make(StoreProductCategoryServices::class);
            $cateList = $categoryService->getCateParentAndChildName($cateIds);
            $supplierIds = $storeIds = [];
            foreach ($list as $value) {
                switch ($value['type']) {
                    case 0:
                        break;
                    case 1://门店
                        $storeIds[] = $value['relation_id'];
                        break;
                    case 2://供应商
                        $supplierIds[] = $value['relation_id'];
                        break;
                }
            }
            $supplierIds = array_unique($supplierIds);
            $storeIds = array_unique($storeIds);
            $supplierList = $storeList = [];
            if ($supplierIds) {
                /** @var SystemSupplierServices $supplierServices */
                $supplierServices = app()->make(SystemSupplierServices::class);
                $supplierList = $supplierServices->getColumn([['id', 'in', $supplierIds], ['is_del', '=', 0]], 'id,supplier_name', 'id');
            }
            if ($storeIds) {
                /** @var SystemStoreServices $storeServices */
                $storeServices = app()->make(SystemStoreServices::class);
                $storeList = $storeServices->getColumn([['id', 'in', $storeIds], ['is_del', '=', 0]], 'id,name', 'id');
            }

            if ($is_move) {
                $proIds = array_column(array_filter($list, function ($item) {
                    return $item['spec_type'] == 0;
                }), 'id', 'id');
                /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
                $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
                $attrValueList = $storeProductAttrValueServices->getSkuArray(['product_id' => $proIds, 'type' => 0], 'unique,cost,price,ot_price,stock,product_id', 'product_id');
            }

            foreach ($list as &$item) {
                if ($item['spec_type'] == 0 && $is_move) {
                    $item['attr_value'] = $attrValueList[$item['id']] ?? [];
                }

                $item['branch_sales'] = $item['sales'] ?? 0;
                $item['branch_stock'] = $item['stock'] ?? 0;
                $item['is_show'] = $item['branch_is_show'] ?? $item['is_show'];
                $item['cate_name'] = '';
                if (isset($item['cate_id']) && $item['cate_id']) {
                    $cate_name = $categoryService->getCateName(explode(',', $item['cate_id']), $cateList);
                    if ($cate_name) {
                        $item['cate_name'] = is_array($cate_name) ? implode(',', $cate_name) : '';
                    }
                }
                $item['stock_attr'] = $item['stock'] > 0;//库存
                $item['plate_name'] = '平台';
                switch ($item['type']) {
                    case 0:
                        $item['plate_name'] = '平台';
                        break;
                    case 1://门店
                        $item['plate_name'] = '门店：' . ($storeList[$item['relation_id']]['name'] ?? '');
                        break;
                    case 2://供应商
                        $item['plate_name'] = '供应商：' . ($supplierList[$item['relation_id']]['supplier_name'] ?? '');
                        if ($item['settle_price'] <= 0 && isset($where['type']) && $where['type'] == 2) {
                            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
                            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
                            $attrValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $item['id'], 'type' => 0], 'product_id,unique,settle_price', 'product_id');
                            $item['settle_price'] = min(array_column($attrValue, 'settle_price'));
                        }
                        break;
                }
            }
        }

        return compact('list', 'count');
    }

    /**
     * 设置商品上下架
     * @param $ids
     * @param $is_show
     */
    public function setShow(array $ids, int $is_show)
    {
        if ($is_show == 0) {
            //下架检测是否有参与活动商品
//            $this->checkActivity($ids);
        } else {
            $count = $this->dao->getCount(['ids' => $ids, 'is_del' => 1]);
            if ($count) throw new AdminException('回收站商品无法直接上架，请先恢复商品');
        }
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cartService->batchUpdate($ids, ['status' => $is_show], 'product_id');
        $update = ['is_show' => $is_show];
        if ($is_show) {//手动上架 清空定时下架状态
            $update['auto_off_time'] = 0;
        }
        $this->dao->batchUpdate($ids, $update);

        /** @var StoreProductRelationServices $storeProductRelationServices */
        $storeProductRelationServices = app()->make(StoreProductRelationServices::class);
        $storeProductRelationServices->setShow($ids, (int)$is_show);

        event('product.status', [$ids, $is_show]);

        $this->dao->cacheTag()->clear();

        return true;
    }

    /**
     * 商品审核表单
     * @param int $id
     * @param int $is_verify
     * @return mixed
     */
    public function verifyForm(int $id, int $is_verify = 1)
    {
        $f = [];
        if ($is_verify == 1) {
            $f[] = Form::radio('is_verify', '审核状态', 1)->options([['value' => 1, 'label' => '通过'], ['value' => -1, 'label' => '拒绝']])->appendControl(-1, [
                Form::textarea('refusal', '拒绝原因')->required('请输入拒绝原因')]);
        } else {
            $f[] = Form::hidden('is_verify', '-2');
            $f[] = Form::textarea('refusal', '下架原因')->required('请输入下架原因');
        }
        return create_form($is_verify == 1 ? '商品审核' : '强制下架', $f, Url::buildUrl('/product/product/set_verify/' . $id), 'post');
    }

    /**
     * 审核前验证商品售价和结算价
     * @param int $id
     * @return bool
     * User: liusl
     * DateTime: 2024/9/27 15:52
     */
    public function getVerify(int $id)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $attr_value = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0], 'price,settle_price');
        foreach ($attr_value as &$item) {
            if ($item['settle_price'] > $item['price']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 商品审核
     * @param int $id
     * @param int $is_verify
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function verify(int $id, array $data)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('商品不存在');
        }
        $is_verify = $data['is_verify'] ?? 1;
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);

        $data['auto_on_time'] = $data['auto_on_time'] ? strtotime($data['auto_on_time']) : 0;
        if ($data['auto_on_time']) {
            $data['is_show'] = 0;
        }

        $cartService->update(['product_id' => $id, 'status' => 1], ['status' => 0]);
//        if (in_array($is_verify, [-1, -2])) {
//            if ($is_verify == -1) {
//                $data['is_show'] = 0;
//            }
//            $cartService->update(['product_id' => $id, 'status' => 1], ['status' => 0]);
//        } elseif ($is_verify == 1) {
//            if ($info['is_show']) $cartService->update(['product_id' => $id, 'status' => 0], ['status' => 1]);
//        }
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        /** @var StoreProductCategoryServices $storeCatecoryService */
        $storeCatecoryService = app()->make(StoreProductCategoryServices::class);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreCouponIssueServices $storeCouponIssueServices */
        $storeCouponIssueServices = app()->make(StoreCouponIssueServices::class);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        /** @var ShippingTemplatesServices $shippingTemplates */
        $shippingTemplates = app()->make(ShippingTemplatesServices::class);
        $storeProductCategoryServices = app()->make(StoreProductCategoryServices::class);
        $storeBrandServices = app()->make(StoreBrandServices::class);
        //单独调用
//        $data['tempList'] = $shippingTemplates->getTemp();
//        $data['cateList'] = $storeCatecoryService->cascaderList();
        $productInfo = $this->dao->getInfo($id);
        if ($productInfo) $productInfo = $productInfo->toArray();
        else throw new ValidateException('商品不存在');
        $couponIds = $productInfo['coupons'] ? array_column($productInfo['coupons'], 'issue_coupon_id') : [];
        $is_sub = $recommend = [];
        if ($productInfo['is_sub'] == 1) $is_sub[] = 1;
        if ($productInfo['is_vip'] == 1) $is_sub[] = 0;
        $productInfo['is_sub'] = $is_sub;
        $productInfo['recommend'] = $recommend;
        $productInfo['price'] = floatval($productInfo['price']);
        $productInfo['postage'] = floatval($productInfo['postage']);
        $productInfo['ot_price'] = floatval($productInfo['ot_price']);
        $productInfo['vip_price'] = floatval($productInfo['vip_price']);
        $productInfo['is_limit'] = boolval($productInfo['is_limit'] ?? 0);
        $productInfo['cost'] = floatval($productInfo['cost']);
        $productInfo['brand_id'] = $productInfo['brand_com'] ? array_map('intval', explode(',', $productInfo['brand_com'])) : [];
        if ($productInfo['brand_id']) {
            $productInfo['brand_name'] = $storeBrandServices->getColumn(['id' => $productInfo['brand_id']], 'id,brand_name');
        }
        $productInfo['video_open'] = (bool)$productInfo['video_open'];
        if ($productInfo['video_link'] && strpos($productInfo['video_link'], 'http') === false) {
            $productInfo['video_link'] = sys_config('site_url') . $productInfo['video_link'];
        }
        $productInfo['coupons'] = $storeCouponIssueServices->productCouponList([['id', 'in', $couponIds]], 'title,id');
        $productInfo['cate_id'] = is_array($productInfo['cate_id']) ? $productInfo['cate_id'] : explode(',', $productInfo['cate_id']);
        if ($productInfo['cate_id']) {
            $productInfo['cate_name'] = $storeProductCategoryServices->getColumn(['id' => $productInfo['cate_id']], 'id,cate_name');
        }
        if ($productInfo['label_id']) {
            $label_id = is_array($productInfo['label_id']) ? $productInfo['label_id'] : explode(',', $productInfo['label_id']);
            $productInfo['label_id'] = $userLabelServices->getLabelList(['ids' => $label_id], ['id', 'label_name']);
        } else {
            $productInfo['label_id'] = [];
        }
        if ($productInfo['type'] == 2) {
            $productInfo['supplier_name'] = app()->make(SystemSupplierServices::class)->value(['id' => $productInfo['relation_id']], 'supplier_name');
        }
        if ($productInfo['store_label_id']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $productInfo['store_label_id'] = $storeProductLabelServices->getColumn([['id', 'in', $productInfo['store_label_id']]], 'id,label_name');
        } else {
            $productInfo['store_label_id'] = [];
        }
        $productInfo['give_integral'] = floatval($productInfo['give_integral']);
        $productInfo['presale_time'] = $productInfo['presale_start_time'] == 0 ? [] : [date('Y-m-d H:i:s', $productInfo['presale_start_time']), date('Y-m-d H:i:s', $productInfo['presale_end_time'])];
        $productInfo['auto_on_time'] = $productInfo['is_show'] ? '' : ($productInfo['auto_on_time'] ? date('Y-m-d H:i:s', $productInfo['auto_on_time']) : '');
        $productInfo['auto_off_time'] = !$productInfo['is_show'] ? '' : ($productInfo['auto_off_time'] ? date('Y-m-d H:i:s', $productInfo['auto_off_time']) : '');
        $productInfo['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 0]);
        //系统表单
        $productInfo['custom_form'] = $productInfo['custom_form_info'] = [];
        if ($productInfo['system_form_id']) {
            /** @var SystemFormServices $systemFormServices */
            $systemFormServices = app()->make(SystemFormServices::class);
            $systemForm = $systemFormServices->value(['id' => $productInfo['system_form_id']], 'value');
            if ($systemForm) {
                $productInfo['custom_form'] = is_string($systemForm) ? json_decode($systemForm, true) : $systemForm;
            }
            $productInfo['custom_form_info'] = $systemFormServices->handleForm($productInfo['custom_form']);
        }
        //无属性添加默认属性
        $storeProductAttrResultServices->checkProductAttr($id, 0, $productInfo);
        if ($productInfo['spec_type'] == 1) {
            $result = $storeProductAttrResultServices->getResult(['product_id' => $id, 'type' => 0]);
            $attrInfo = $storeProductAttrValueServices->getColumn(['product_id' => $id, 'type' => 0]);

//            foreach ($result['value'] as $k => $v) {
//                $num = 1;
//                foreach ($v['detail'] as $dk=>$dv) {
//                    $result['value'][$k]['value' . $num] = $dv;
////                    $result['value'][$k][$dk] = ['value' => $dv, 'pic' => ''];
//                    $num++;
//                }
//            }
//            foreach ($result['attr'] as $k => $v){
//                foreach ($v['detail'] as $dk=>$dv) {
//                    $result['attr'][$k]['detail'][$dk] = ['value' => $dv, 'pic' => ''];
//                }
//            }
            foreach ($result['value'] as $k => $v) {
                if (!isset($v['is_show'])) {
                    $result['value'][$k]['is_show'] = 1;
                }
                $num = 1;
                foreach ($v['detail'] as $dv) {
                    $result['value'][$k]['value' . $num] = $dv;
                    $num++;
                }
                if (!isset($v['attr_arr'])) {
                    $result['value'][$k]['attr_arr'] = array_values($v['detail']);
                    foreach ($v['detail'] as $detailKey => $detailValue) {
                        $result['value'][$k][$detailKey] = $detailValue;
                    }
                }
                foreach ($attrInfo as $attrKey => $attrItem) {
                    if (implode(',', $result['value'][$k]['attr_arr']) == $attrKey) {
                        $result['value'][$k]['unique'] = $attrItem['unique'];
                    }
                }
            }
            foreach ($result['attr'] as $attrKey => $attrItem) {
                foreach ($attrItem['detail'] as $valueKey => $valueItem) {
                    if (is_string($valueItem)) {
                        $result['attr'][$attrKey]['detail'][$valueKey] = ['value' => $valueItem, 'pic' => ''];
                        $result['attr'][$attrKey]['add_pic'] = 0;
                    }
                }
            }
            $productInfo['items'] = $result['attr'];
            $productInfo['attrs'] = $result['value'];
            $productInfo['attr'] = ['pic' => '', 'vip_price' => 0, 'price' => 0, 'settle_price' => 0, 'cost' => 0, 'ot_price' => 0, 'stock' => 0, 'bar_code' => '', 'weight' => 0, 'volume' => 0, 'brokerage' => 0, 'brokerage_two' => 0, 'code' => '', 'write_times' => 1, 'write_valid' => 1, 'days' => 0, 'section_time' => []];
        } else {
            /** @var StoreProductVirtualServices $virtualService */
            $virtualService = app()->make(StoreProductVirtualServices::class);
            $result = $storeProductAttrValueServices->getOne(['product_id' => $id, 'type' => 0]);
            $productInfo['items'] = [];
            $productInfo['attrs'] = [];
            $productInfo['attr'] = [
                'pic' => $result['image'] ?? '',
                'vip_price' => isset($result['vip_price']) ? floatval($result['vip_price']) : 0,
                'price' => isset($result['price']) ? floatval($result['price']) : 0,
                'settle_price' => isset($result['settle_price']) ? floatval($result['settle_price']) : 0,
                'cost' => isset($result['cost']) ? floatval($result['cost']) : 0,
                'ot_price' => isset($result['ot_price']) ? floatval($result['ot_price']) : 0,
                'stock' => isset($result['stock']) ? floatval($result['stock']) : 0,
                'bar_code' => isset($result['bar_code']) ? $result['bar_code'] : '',
                'code' => isset($result['code']) ? $result['code'] : '',
                'virtual_list' => $virtualService->getArr(isset($result['unique']), $id),
                'weight' => isset($result['weight']) ? floatval($result['weight']) : 0,
                'volume' => isset($result['volume']) ? floatval($result['volume']) : 0,
                'brokerage' => isset($result['brokerage']) ? floatval($result['brokerage']) : 0,
                'brokerage_two' => isset($result['brokerage_two']) ? floatval($result['brokerage_two']) : 0,
                'disk_info' => $result['disk_info'] ?? [],
                'write_times' => intval($result['write_times'] ?? 1),//核销次数
                'write_valid' => intval($result['write_valid'] ?? 1),//核销时效类型
                'days' => intval($result['write_days'] ?? $result['days'] ?? 0),//购买后：N天有效
                'section_time' => [($result['write_start'] ?? '') ? date('Y-m-d H:i:s', $result['write_start'] ?? '') : '', ($result['write_end'] ?? '') ? date('Y-m-d H:i:s', $result['write_end'] ?? '') : ''],//[核销开始时间,核销结束时间]
            ];
        }
        if ($productInfo['activity']) {
            $activity = explode(',', $productInfo['activity']);
            foreach ($activity as $k => $v) {
                if ($v == 1) {
                    $activity[$k] = '秒杀';
                } elseif ($v == 2) {
                    $activity[$k] = '砍价';
                } elseif ($v == 3) {
                    $activity[$k] = '拼团';
                } elseif ($v == 0) {
                    $activity[$k] = '默认';
                }
            }
            $productInfo['activity'] = $activity;
        } else {
            $productInfo['activity'] = ['默认', '秒杀', '砍价', '拼团'];
        }
        //推荐产品
        $recommend_list = [];
        if ($productInfo['recommend_list'] != '') {
            $productInfo['recommend_list'] = explode(',', $productInfo['recommend_list']);
            if (count($productInfo['recommend_list'])) {
                $images = $this->getColumn([['id', 'in', $productInfo['recommend_list']]], 'image', 'id');
                foreach ($productInfo['recommend_list'] as $item) {
                    $recommend_list[] = [
                        'product_id' => $item,
                        'image' => $images[$item] ?? ''
                    ];
                }
            }
        }
        $productInfo['recommend_list'] = $recommend_list;
        $data['productInfo'] = $productInfo;
        return $data;
    }

    /**
     * 获取商品规格
     * @param array $data
     * @param int $id
     * @param int $type
     * @param int $plat_type
     * @param int $relation_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAttr(array $data, int $id, int $type, int $plat_type = 0, int $relation_id = 0, bool $is_supplier = false)
    {

        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $productInfo = [];
        if ($id) {
            $productInfo = $this->dao->get($id);
            if (!$productInfo) {
                throw new ValidateException('商品不存在');
            }
        }
        /** @var StoreProductVirtualServices $virtualService */
        $virtualService = app()->make(StoreProductVirtualServices::class);
        $attr = $data['attrs'];
        $product_type = $productInfo['product_type'] ?? $data['product_type'] ?? 0; //商品类型
//        var_dump(attr_format($attr));
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
            foreach ($detail as $v => $d) {
                $detail[$v] = trim($d);
            }
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $detail);
            $types = 1;
            if ($id) {
                $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0, 'suk' => $suk], 'unique,bar_code,code,cost,price,settle_price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,vip_price,disk_info', 'suk');
                if (!$sukValue) {
                    if ($type == 0) $types = 0; //编辑商品时，将没有规格的数据不生成默认值
                    $sukValue[$suk]['pic'] = '';
                    $sukValue[$suk]['price'] = 0;
                    $sukValue[$suk]['settle_price'] = 0;
                    $sukValue[$suk]['cost'] = 0;
                    $sukValue[$suk]['ot_price'] = 0;
                    $sukValue[$suk]['stock'] = 0;
                    $sukValue[$suk]['bar_code'] = '';
                    $sukValue[$suk]['code'] = '';
                    if (in_array($product_type, [1, 2])) {
                        if ($product_type == 1) {
                            $sukValue[$suk]['virtual_list'] = [];
                        } elseif ($product_type == 2) {
                            $sukValue[$suk]['coupon_id'] = 0;
                        }
                    }
                    $sukValue[$suk]['weight'] = 0;
                    $sukValue[$suk]['volume'] = 0;
                    $sukValue[$suk]['brokerage'] = 0;
                    $sukValue[$suk]['brokerage_two'] = 0;
                    switch ($product_type) {
                        case 1://卡密
                            $sukValue[$suk]['virtual_list'] = [];
                            break;
                        case 2://优惠券
                            $sukValue[$suk]['coupon_id'] = 0;
                            break;
                        case 3://虚拟商品
                            break;
                        case 4://次卡商品
                            $sukValue[$suk]['write_times'] = 1;//核销次数
                            $sukValue[$suk]['write_valid'] = 1;//核销时效类型
                            $sukValue[$suk]['days'] = 0;//购买后：N天有效
                            $sukValue[$suk]['section_time'] = [];//[核销开始时间,核销结束时间]
                            break;
                    }
                }
//                if($store_id !== 0){
//                    $branchValue = $branchProductAttrValueServices->get(['product_id' => $id, 'type' => 0, 'unique' => $sukValue['unique'],'store_id'=>$store_id]);
//                }
            } else {
                $sukValue[$suk]['pic'] = '';
                $sukValue[$suk]['price'] = 0;
                $sukValue[$suk]['settle_price'] = 0;
                $sukValue[$suk]['cost'] = 0;
                $sukValue[$suk]['ot_price'] = 0;
                $sukValue[$suk]['stock'] = 0;
                $sukValue[$suk]['bar_code'] = '';
                $sukValue[$suk]['code'] = '';
                if (in_array($product_type, [1, 2])) {
                    if ($product_type == 1) {
                        $sukValue[$suk]['virtual_list'] = [];
                    } elseif ($product_type == 2) {
                        $sukValue[$suk]['coupon_id'] = 0;
                    }
                }
                $sukValue[$suk]['weight'] = 0;
                $sukValue[$suk]['volume'] = 0;
                $sukValue[$suk]['brokerage'] = 0;
                $sukValue[$suk]['brokerage_two'] = 0;
                switch ($product_type) {
                    case 1://卡密
                        $sukValue[$suk]['virtual_list'] = [];
                        break;
                    case 2://优惠券
                        $sukValue[$suk]['coupon_id'] = 0;
                        break;
                    case 3://虚拟商品
                        break;
                    case 4://次卡商品
                        $sukValue[$suk]['write_times'] = 1;//核销次数
                        $sukValue[$suk]['write_valid'] = 1;//核销时效类型
                        $sukValue[$suk]['days'] = 0;//购买后：N天有效
                        $sukValue[$suk]['section_time'] = [];//[核销开始时间,核销结束时间]
                        break;
                }
            }
            if ($types) { //编辑商品时，将没有规格的数据不生成默认值
                foreach (array_keys($detail) as $k => $title) {
                    $header[$k]['title'] = $title;
                    $header[$k]['align'] = 'center';
                    $header[$k]['minWidth'] = 130;
                }
                $values = '';
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                    $header[$k]['slot'] = 'value' . ($k + 1);
                    $values .= $v . ',';
                }
                $valueNew[$count]['values'] = substr($values, 0, strlen($values) - 1);
                $valueNew[$count]['detail'] = $detail;
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['settle_price'] = $sukValue[$suk]['settle_price'] ? floatval($sukValue[$suk]['settle_price']) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['vip_price'] = isset($sukValue[$suk]['vip_price']) ? floatval($sukValue[$suk]['vip_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
                if ($product_type == 1 && !$type) {
                    $valueNew[$count]['virtual_list'] = isset($sukValue[$suk]['unique']) && $sukValue[$suk]['unique'] ? $virtualService->getArr($sukValue[$suk]['unique'], $id) : [];
                    $valueNew[$count]['disk_info'] = $sukValue[$suk]['disk_info'] ?? '';
                }
                $valueNew[$count]['weight'] = floatval($sukValue[$suk]['weight']) ?? 0;
                $valueNew[$count]['volume'] = floatval($sukValue[$suk]['volume']) ?? 0;
                $valueNew[$count]['brokerage'] = floatval($sukValue[$suk]['brokerage']) ?? 0;
                $valueNew[$count]['brokerage_two'] = floatval($sukValue[$suk]['brokerage_two']) ?? 0;
                switch ($product_type) {
                    case 1://卡密
                        if (!$type) {
                            $valueNew[$count]['virtual_list'] = isset($sukValue[$suk]['unique']) && $sukValue[$suk]['unique'] ? $virtualService->getArr($sukValue[$suk]['unique'], $id) : [];
                            $valueNew[$count]['disk_info'] = $sukValue[$suk]['disk_info'] ?? '';
                        }
                        break;
                    case 2://优惠券
                        break;
                    case 3://虚拟商品
                        break;
                    case 4://次卡商品
                        $valueNew[$count]['write_times'] = intval($sukValue[$suk]['write_times'] ?? 1);//核销次数
                        $valueNew[$count]['write_valid'] = intval($sukValue[$suk]['write_valid'] ?? 1);//核销时效类型
                        $valueNew[$count]['days'] = intval($sukValue[$suk]['write_days'] ?? $sukValue[$suk]['days'] ?? 0);//购买后：N天有效
                        $start = $sukValue[$suk]['write_start'] ?? '';
                        $end = $sukValue[$suk]['write_end'] ?? '';
                        $valueNew[$count]['section_time'] = [$start ? date('Y-m-d', $start) : '', $end ? date('Y-m-d', $end) : ''];//[核销开始时间,核销结束时间]
                        break;
                }
                $count++;
            }
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 80];
        if ($is_supplier) {//供应商
            $header[] = ['title' => '结算价', 'slot' => 'settle_price', 'align' => 'center', 'minWidth' => 120];
        } else {//供应商不展示成本价
            if ($plat_type == 2) {
                $header[] = ['title' => '结算价', 'slot' => 'settle_price', 'align' => 'center', 'minWidth' => 120];
            }
            $header[] = ['title' => '售价', 'slot' => 'price', 'align' => 'center', 'minWidth' => 120];
            $header[] = ['title' => '成本价', 'slot' => 'cost', 'align' => 'center', 'minWidth' => 140];
            $header[] = ['title' => '划线价', 'slot' => 'ot_price', 'align' => 'center', 'minWidth' => 140];
        }
//        $header[] = ['title' => '会员价', 'slot' => 'vip_price', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '库存', 'slot' => 'stock', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '商品条形码', 'slot' => 'bar_code', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '商品编码', 'slot' => 'code', 'align' => 'center', 'minWidth' => 140];
        switch ($product_type) {
            case 0:
                $header[] = ['title' => '重量(KG)', 'slot' => 'weight', 'align' => 'center', 'minWidth' => 140];
                $header[] = ['title' => '体积(m³)', 'slot' => 'volume', 'align' => 'center', 'minWidth' => 140];
                break;
            case 1://卡密
                $header[] = ['title' => '卡密商品', 'slot' => 'fictitious', 'align' => 'center', 'minWidth' => 140];
                break;
            case 2://优惠券
                break;
            case 3://虚拟商品
                break;
            default:
                break;
        }
        $header[] = ['title' => '操作', 'slot' => 'action', 'align' => 'center', 'minWidth' => 70];
        return ['attr' => $attr, 'value' => $valueNew, 'header' => $header, 'product_type' => $product_type];
    }

    /**
     * SPU
     * @return string
     */
    public function createSpu()
    {
        mt_srand();
        return substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8) . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 新增编辑商品
     * @param int $id
     * @param array $data
     * @param int $type
     * @param int $relation_id
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveData(int $id, array $data, int $type = 0, int $relation_id = 0)
    {
        if (count($data['cate_id']) < 1) throw new AdminException('请选择商品分类');
        if (!$data['store_name']) throw new AdminException('请输入商品名称');
        if (count($data['slider_image']) < 1) throw new AdminException('请上传商品轮播图');
        if ($data['product_type'] == 0 && isset($data['delivery_type']) && count($data['delivery_type']) < 1) throw new AdminException('请选择商品配送方式');
        if (in_array($data['product_type'], [1, 2, 3])) {
            $data['freight'] = 2;
            $data['temp_id'] = 0;
            $data['postage'] = 0;
        } else {
            if ($data['freight'] == 1) {
                $data['temp_id'] = 0;
                $data['postage'] = 0;
            } elseif ($data['freight'] == 2) {
                $data['temp_id'] = 0;
            } elseif ($data['freight'] == 3) {
                $data['postage'] = 0;
            }
            if ($data['freight'] == 2 && !$data['postage']) {
                throw new AdminException('请设置运费金额');
            }
            if ($data['freight'] == 3 && !$data['temp_id']) {
                throw new AdminException('请选择运费模版');
            }
        }
        if ($data['product_type'] == 4) {
            $data['delivery_type'] = 2;
        }
        // 开启ERP后商品编码验证
        $isOpen = sys_config('erp_open');
        if ($isOpen && $data['product_type'] == 0 && empty($data['code'])) {
            throw new AdminException('请输入商品编码');
        }
        $detail = $data['spec_type'] == 0 ? [$data['attr']] : $data['attrs'];
        $attr = $data['items'];
        //关联补充信息
        $relationData = [];
        $relationData['cate_id'] = $data['cate_id'] ?? [];
        $relationData['brand_id'] = $data['brand_id'] ?? [];
        $relationData['store_label_id'] = $data['store_label_id'] ?? [];
        $relationData['label_id'] = $data['label_id'] ?? [];
        $relationData['ensure_id'] = $data['ensure_id'] ?? [];
        $relationData['specs_id'] = $data['specs_id'] ?? [];
        $relationData['coupon_ids'] = $data['coupon_ids'] ?? [];

        $description = $data['description'];
        $data['type'] = $type;
        $data['relation_id'] = $relation_id;
        $supplier_id = $data['supplier_id'] ?? 0;
        if ($supplier_id) {
            $data['type'] = 2;
            $data['relation_id'] = $supplier_id;
        }
        if ($data['type'] == 0) {
            $data['is_verify'] = 1;
        }
        $is_copy = $data['is_copy'];
        unset($data['supplier_id'], $data['is_copy']);
        //视频
        if ($data['video_link'] && strpos($data['video_link'], 'http') === false) {
            $data['video_link'] = sys_config('site_url') . $data['video_link'];
        }
        //品牌
        $data['brand_com'] = $data['brand_id'] ? implode(',', $data['brand_id']) : '';
        $data['brand_id'] = $data['brand_id'] ? end($data['brand_id']) : 0;
//        $data['is_vip'] = in_array(0, $data['is_sub']) ? 1 : 0;
//        $data['is_sub'] = in_array(1, $data['is_sub']) ? 1 : 0;
        $data['product_type'] = intval($data['product_type']);
        $data['is_vip_product'] = intval($data['is_vip_product']);
        $data['is_presale_product'] = intval($data['is_presale_product']);
        $data['presale_status'] = intval($data['presale_status'] ?? 0);
        $data['presale_start_time'] = $data['is_presale_product'] ? strtotime($data['presale_time'][0]) : 0;
        $data['presale_end_time'] = $data['is_presale_product'] ? strtotime($data['presale_time'][1]) : 0;
        if ($data['presale_start_time'] && $data['presale_start_time'] < time()) {
            throw new AdminException('预售开始时间不能小于当前时间');
        }
        if ($data['presale_end_time'] && $data['presale_end_time'] < time()) {
            throw new AdminException('预售结束时间不能小于当前时间');
        }
        $data['auto_on_time'] = $data['auto_on_time'] ? strtotime($data['auto_on_time']) : 0;
        $data['auto_off_time'] = $data['auto_off_time'] ? strtotime($data['auto_off_time']) : 0;
        if ($data['auto_on_time']) {
            $data['is_show'] = 0;
        }
        $data['is_limit'] = intval($data['is_limit']);
        if (!$data['is_limit']) {
            $data['limit_type'] = 0;
            $data['limit_num'] = 0;
        } else {
            if (!in_array($data['limit_type'], [1, 2])) throw new AdminException('请选择限购类型');
            if ($data['limit_num'] <= 0) throw new AdminException('限购数量不能小于1');
        }
        $data['custom_form'] = $data['custom_form'] ? json_encode($data['custom_form']) : '';
        if ($data['store_label_id']) {
            $data['store_label_id'] = is_array($data['store_label_id']) ? implode(',', $data['store_label_id']) : $data['store_label_id'];
        } else {
            $data['store_label_id'] = '';
        }
        if ($data['ensure_id']) {
            $data['ensure_id'] = is_array($data['ensure_id']) ? implode(',', $data['ensure_id']) : $data['ensure_id'];
        } else {
            $data['ensure_id'] = '';
        }
//        if (!$data['specs_id']) {
//            $data['specs'] = '';
//        }
        if ($data['specs']) {
            $specs = [];
            if (is_array($data['specs'])) {
                /** @var StoreProductSpecsServices $storeProductSpecsServices */
                $storeProductSpecsServices = app()->make(StoreProductSpecsServices::class);
                foreach ($data['specs'] as $item) {
                    $specs[] = $storeProductSpecsServices->checkSpecsData($item);
                }
                $data['specs'] = json_encode($specs);
            }
        } else {
            $data['specs'] = '';
        }
        if ($data['spec_type'] == 0) {
            $attr = [
                [
                    'value' => '规格',
                    'detailValue' => '',
                    'attrHidden' => '',
                    'detail' => ['默认']
                ]
            ];
            $detail[0]['suk'] = '默认';
            $detail[0]['detail'] = ['规格' => '默认'];
            $data['default_sku'] = '';
        }
        foreach ($detail as &$item) {
            //默认规格
            if ($data['spec_type'] == 1) {
                if (isset($item['is_default_select']) && $item['is_default_select'] == 1) {
                    $data['default_sku'] = implode(",", $item['attr_arr']);
                }
            } else {
                $item['is_show'] = 1;
            }
            $item['stock'] = isset($item['is_show']) && $item['is_show'] == 1 ? $item['stock'] : 0;
            if ($isOpen && $data['product_type'] == 0 && (!isset($item['code']) || !$item['code'])) {
                throw new AdminException('请输入【' . ($item['values'] ?? '默认') . '】商品编码');
            }
            if ($type == 2 && !$item['settle_price']) {
                throw new AdminException('请输入结算价');
            }

            $item['product_type'] = $data['product_type'];
            $item['brokerage'] = 0;
            $item['brokerage_two'] = 0;
            if (!isset($item['price'])) $item['price'] = 0;
            if (!isset($item['ot_price'])) $item['ot_price'] = 0;
            if (($item['brokerage'] + $item['brokerage_two']) > $item['price']) {
                throw new AdminException('一二级返佣相加不能大于商品售价');
            }
            //验证次卡商品数据
            if ($data['product_type'] == 4) {
                if (!isset($item['write_times']) || !$item['write_times']) {
                    throw new AdminException('请输入核销次数');
                }
                if (!isset($item['write_valid'])) {
                    throw new AdminException('请选择核销时效类型');
                }
                switch ($item['write_valid']) {
                    case 1://永久
                        $item['days'] = 0;
                        $item['section_time'] = [0, 0];
                        break;
                    case 2://购买后n天
                        $item['section_time'] = [0, 0];
                        if (!isset($item['days']) || !$item['days']) {
                            throw new AdminException('填写时效天数');
                        }
                        break;
                    case 3://固定时间
                        $item['days'] = 0;
                        if (!isset($item['section_time']) || !$item['section_time'] || !is_array($item['section_time']) || count($item['section_time']) != 2) {
                            throw new AdminException('请选择固定有效时间段或时间格式错误');
                        }
                        [$start, $end] = $item['section_time'];
                        $data['start_time'] = $start ? strtotime($start) : 0;
                        $data['end_time'] = $end ? strtotime($end) : 0;
                        if ($data['start_time'] && $data['end_time'] && $data['end_time'] <= $data['start_time']) {
                            throw new AdminException('请重新选择：结束时间必须大于开始时间');
                        }
                        break;
                    default:
                        throw new AdminException('请选择核销时效类型');
                        break;
                }
            }
        }
        foreach ($data['activity'] as $k => $v) {
            if ($v == '秒杀') {
                $data['activity'][$k] = 1;
            } elseif ($v == '砍价') {
                $data['activity'][$k] = 2;
            } elseif ($v == '拼团') {
                $data['activity'][$k] = 3;
            } else {
                $data['activity'][$k] = 0;
            }
        }
        $data['activity'] = implode(',', $data['activity']);
        $data['recommend_list'] = count($data['recommend_list']) ? implode(',', $data['recommend_list']) : '';
        $data['price'] = min(array_column($detail, 'price'));
        $settle_price_arr = array_column($detail, 'settle_price') ?? [];
        $data['settle_price'] = $settle_price_arr ? min($settle_price_arr) : 0;
        $data['ot_price'] = min(array_column($detail, 'ot_price'));
        $data['cost'] = isset($data['cost']) ? min(array_column($detail, 'cost')) : 0;
        if (!$data['cost']) {
            $data['cost'] = 0;
        }
        $data['cate_id'] = implode(',', $data['cate_id']);
        $data['label_id'] = implode(',', $data['label_id']);
        $data['image'] = $data['slider_image'][0];//封面图
        $slider_image = $data['slider_image'];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['stock'] = array_sum(array_column($detail, 'stock'));
        //是否售罄
        $data['is_sold'] = $data['stock'] ? 0 : 1;

        unset($data['description'], $data['coupon_ids'], $data['items'], $data['attrs'], $data['recommend']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        /** @var StoreDiscountsProductsServices $storeDiscountProduct */
        $storeDiscountProduct = app()->make(StoreDiscountsProductsServices::class);
        /** @var StoreProductVirtualServices $productVirtual */
        $productVirtual = app()->make(StoreProductVirtualServices::class);
        //同一链接不多次保存
        if (!$id && $data['soure_link']) {
            $productInfo = $this->dao->getOne(['soure_link' => $data['soure_link'], 'is_del' => 0], 'id');
            if ($productInfo) $id = (int)$productInfo['id'];
        }
        [$skuList, $id, $is_new, $data] = $this->transaction(function () use ($id, $relationData, $data, $description, $storeDescriptionServices, $storeProductAttrServices, $detail, $attr, $storeDiscountProduct, $productVirtual, $slider_image) {

            if ($id) {
                //上下架处理
                $this->setShow([$id], $data['is_show']);
                $oldInfo = $this->get($id)->toArray();
                if ($oldInfo['product_type'] != $data['product_type']) {
                    throw new AdminException('商品类型不能切换！');
                }
                //修改不改变商品来源
                if ($oldInfo['type']) {
                    $data['type'] = $oldInfo['type'];
                    $data['relation_id'] = $oldInfo['relation_id'];
                }
                unset($data['sales']);
                $res = $this->dao->update($id, $data);
                if (!$res) throw new AdminException('修改失败');
                // 修改优惠套餐商品的运费模版id
                $storeDiscountProduct->update(['product_id' => $id], ['temp_id' => $data['temp_id']]);
                if ($oldInfo['type'] == 1 && !$oldInfo['pid'] && !$data['is_verify']) {
                    /** @var StoreCartServices $cartService */
                    $cartService = app()->make(StoreCartServices::class);
                    $cartService->batchUpdate([$id], ['status' => 0], 'product_id');
                }
                $is_new = 1;
            } else {

                //默认评分
                $data['star'] = config('admin.product_default_star');
                $data['add_time'] = time();
                $data['code_path'] = '';
//                $data['settle_price'] = $data['settle_price'] ?? '0.00';
                $data['spu'] = $this->createSpu();
                $res = $this->dao->save($data);

                if (!$res) throw new AdminException('添加失败');
                $id = (int)$res->id;
                $is_new = 0;
            }

            //商品详情
            $storeDescriptionServices->saveDescription($id, $description);
            $skuList = $storeProductAttrServices->validateProductAttr($attr, $detail, $id);
            foreach ($skuList['valueGroup'] as &$item) {
                if (!isset($item['sum_stock']) || !$item['sum_stock']) $item['sum_stock'] = $item['stock'] ?? 0;
            }
            $proudctVipPrice = 0;
            $detailTemp = array_column($skuList['valueGroup'], 'vip_price');
            if ($detailTemp) {
                $proudctVipPrice = min($detailTemp);
            }
            $this->dao->update($id, ['vip_price' => $proudctVipPrice]);
            $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, $id);
            if (!$valueGroup) throw new AdminException('添加失败！');
            if ($data['product_type'] == 1) {
                $productVirtual->saveProductVirtual($id, $valueGroup);
            }
            return [$skuList, $id, $is_new, $data];
        });
        event('product.create', [$id, $data, $skuList, $is_new, $slider_image, $description, $is_copy, $relationData]);
    }

    /**
     * 放入回收站
     * @param int $id
     * @return string
     */
    public function del(int $id)
    {
        if (!$id) throw new AdminException('参数不正确');
        $productInfo = $this->dao->get($id);
        if (!$productInfo) throw new AdminException('商品数据不存在');
        $msg = '';
        $data = $update = [];
        if ($productInfo['is_del'] == 1) {
            $data['is_del'] = 0;
            $update = ['is_del' => 0];
            $msg = '成功恢复商品';
        } else {
            $data['is_del'] = 1;
            $data['is_show'] = 0;
            $update = ['is_del' => 1];
            $msg = '成功移到回收站';
        }
        $this->transaction(function () use ($id, $data, $productInfo, $update) {
            //门店商品处理
            switch ($productInfo['type']) {
                case 0://平台商品
                    $this->dao->update(['pid' => $id], $update);
                    break;
                case 1://门店商品
                    /** @var SystemStoreServices $storeServices */
                    $storeServices = app()->make(SystemStoreServices::class);
                    $storeInfo = $storeServices->getStoreInfo((int)$productInfo['relation_id']);
                    $data['is_verify'] = 0;
                    //门店开启免审
                    if (isset($storeInfo['product_verify_status']) && $storeInfo['product_verify_status']) {
                        $data['is_verify'] = 1;
                    }
                    break;
            }
            $res = $this->dao->update($id, $data);
            if (!$res) throw new AdminException($productInfo['is_del'] == 1 ? '恢复失败,请稍候再试!' : '删除失败,请稍候再试!');

        });
        return $msg;
    }

    /**
     * 获取选择的商品列表
     * @param array $where
     * @param bool $isStock
     * @param int $limit
     * @return array
     */
    public function searchList(array $where, bool $isStock = false, int $limit = 0)
    {
        $store_stock = sys_config('store_stock');
        $where['store_stock'] = $store_stock > 0 ? $store_stock : 2;
        $data = $this->getProductList($where, $isStock, $limit, ['attrValue', 'descriptions']);
        if ($data['list']) {
            $cateIds = implode(',', array_column($data['list'], 'cate_id'));
            /** @var StoreProductCategoryServices $categoryService */
            $categoryService = app()->make(StoreProductCategoryServices::class);
            $cateList = $categoryService->getCateParentAndChildName($cateIds);
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            foreach ($data['list'] as &$item) {
                $item['cate_name'] = '';
                if (isset($item['cate_id']) && $item['cate_id']) {
                    $cate_ids = explode(',', $item['cate_id']);
                    $cate_name = $categoryService->getCateName($cate_ids, $cateList);
                    if ($cate_name) {
                        $item['cate_name'] = is_array($cate_name) ? implode(',', $cate_name) : '';
                    }
                }
                $item['give_integral'] = floatval($item['give_integral']);
                $item['price'] = floatval($item['price']);
                $item['vip_price'] = floatval($item['vip_price']);
                $item['ot_price'] = floatval($item['ot_price']);
                $item['postage'] = floatval($item['postage']);
                $item['cost'] = floatval($item['cost']);
                $item['delivery_type'] = is_string($item['delivery_type']) ? explode(',', $item['delivery_type']) : $item['delivery_type'];
                $item['store_label'] = '';
                if ($item['store_label_id']) {
                    $storeLabelList = $storeProductLabelServices->getColumn([['relation_id', '=', 0], ['type', '=', 0], ['id', 'IN', $item['store_label_id']]], 'id,label_name');
                    $item['store_label'] = $storeLabelList ? implode(',', array_column($storeLabelList, 'label_name')) : '';
                }
            }
        }
        return $data;
    }

    /**
     * 后台获取商品列表展示
     * @param array $where
     * @param bool $isStock
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductList(array $where, bool $isStock = true, int $limit = 0, array $with = ['attrValue'])
    {
        $field = ['*'];
        if ($isStock) {
            $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
            $field = [
                '*',
                '(SELECT count(*) FROM `' . $prefix . 'user_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'collect\') as collect',
                '(SELECT count(*) FROM `' . $prefix . 'user_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'like\') as likes',
                '(SELECT SUM(stock) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as stock',
//                '(SELECT SUM(sales) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as sales',
                '(SELECT count(*) FROM `' . $prefix . 'store_visit` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `product_type` = \'product\') as visitor',
            ];
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getSearchList($where, $page, $limit, $field, '', $with);
        $count = $this->dao->getCount($where);
        return compact('count', 'list');
    }

    /**
     * 获取商品规格
     * @param int $id
     * @param int $type
     * @return array
     */
    public function getProductRules(int $id, int $type = 0)
    {
        $productInfo = $this->dao->get($id);
        if (!$productInfo) {
            throw new ValidateException('商品不存在');
        }
        $product_type = $productInfo['product_type'] ?? $data['product_type'] ?? 0; //商品类型
        /** @var StoreProductAttrServices $storeProductAttrService */
        $storeProductAttrService = app()->make(StoreProductAttrServices::class);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $productAttr = $storeProductAttrService->getProductAttr(['product_id' => $id, 'type' => 0]);
        if (!$productAttr) return [];
        $attr = [];
        foreach ($productAttr as $key => $value) {
            $attr[$key]['value'] = $value['attr_name'];
            $attr[$key]['detailValue'] = '';
            $attr[$key]['attrHidden'] = true;
            $attr[$key]['detail'] = $value['attr_values'];
        }
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
//        $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => $type]);
        $sukValue = $sukDefaultValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0]);
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            if (!isset($sukDefaultValue[$suk])) continue;
//            if (!isset($sukValue[$suk])) {
//                $sukValue[$suk] = $sukDefaultValue[$suk];
//            }
            foreach (array_keys($detail) as $k => $title) {
                $header[$k]['title'] = $title;
                $header[$k]['align'] = 'center';
                $header[$k]['minWidth'] = 80;
            }
            $valueNew[$count]['value'] = '';
            foreach (array_values($detail) as $k => $v) {
                $valueNew[$count]['value' . ($k + 1)] = $v;
                $header[$k]['key'] = 'value' . ($k + 1);
                $valueNew[$count]['value'] .= $valueNew[$count]['value'] == '' ? $v : '，' . $v;
            }
            if ($type == 4) {
                $valueNew[$count]['product_id'] = $sukValue[$suk]['product_id'];
                $valueNew[$count]['integral'] = floatval($sukValue[$suk]['integral']);
            }
            $valueNew[$count]['detail'] = $detail;
            $valueNew[$count]['pic'] = $sukValue[$suk]['pic'];
            $valueNew[$count]['price'] = floatval($sukValue[$suk]['price']);
            if ($type == 2) $valueNew[$count]['min_price'] = 0;
            if ($type == 0) $valueNew[$count]['p_price'] = floatval($sukValue[$suk]['price']);
            $valueNew[$count]['settle_price'] = floatval($sukValue[$suk]['settle_price']);
            $valueNew[$count]['cost'] = floatval($sukValue[$suk]['cost']);
            $valueNew[$count]['ot_price'] = $type == 3 ? floatval($sukValue[$suk]['price']) : floatval($sukValue[$suk]['ot_price']);
            $valueNew[$count]['stock'] = intval($sukValue[$suk]['stock']);
            $valueNew[$count]['quota'] = intval($sukValue[$suk]['quota']);
            $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
            $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
            $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
            $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
            $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
            $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
            $count++;
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        if ($type == 1) {
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '划线价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '秒杀价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 2) {
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '划线价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '砍价起始金额', 'slot' => 'price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '砍价最低价', 'slot' => 'min_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 3) {
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '日常售价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '拼团价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 4) {
            $header[] = ['title' => '兑换积分', 'key' => 'integral', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '金额', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '划线价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '售价', 'key' => 'p_price', 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        if ($type == 2) {
            $header[] = ['title' => '限量', 'slot' => 'quota', 'align' => 'center', 'minWidth' => 80];
        } else if ($type == 4) {
            $header[] = ['title' => '兑换次数', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '限量', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品条形码', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编码', 'key' => 'code', 'align' => 'center', 'minWidth' => 80];
        return ['items' => $attr, 'attrs' => $valueNew, 'header' => $header, 'product_type' => $product_type];
    }

    /**
     * 检查商品是否有活动
     * @param  $id
     * @return bool
     */
    public function checkActivity($id = 0)
    {
        if ($id) {
            /** @var StoreSeckillServices $storeSeckillService */
            $storeSeckillService = app()->make(StoreSeckillServices::class);
            /** @var StoreBargainServices $storeBargainService */
            $storeBargainService = app()->make(StoreBargainServices::class);
            /** @var StoreCombinationServices $storeCombinationService */
            $storeCombinationService = app()->make(StoreCombinationServices::class);
            $res1 = $storeSeckillService->count(['product_id' => $id, 'is_del' => 0, 'status' => 1, 'seckill_time' => 1]);
            $res2 = $storeBargainService->count(['product_id' => $id, 'is_del' => 0, 'status' => 1, 'bargain_time' => 1]);
            $res3 = $storeCombinationService->count(['product_id' => $id, 'is_del' => 0, 'is_show' => 1, 'pinkIngTime' => 1]);
            if ($res1 || $res2 || $res3) throw new AdminException('商品有活动开启，无法进行此操作');
        }
        return true;
    }

    /**
     * 获取临时缓存商品数据
     * @param int $id
     * @return false|mixed|string|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/3
     */
    public function getCacheProductInfo(int $id)
    {
        if (!$id) {
            return [];
        }
        $storeInfo = $this->dao->cacheTag()->remember((string)$id, function () use ($id) {
            $storeInfo = $this->dao->getOne(['id' => $id], '*', ['descriptions']);
            if (!$storeInfo) {
                throw new ValidateException('商品不存在');
            } else {
                $storeInfo = $storeInfo->toArray();
            }
            return $storeInfo;
        }, 600);

        return $storeInfo;
    }

    /**
     * 前台获取商品列表
     * @param array $where
     * @param int $uid
     * @param int $promotions_type
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsList(array $where, int $uid, int $promotions_type = 0)
    {
        $where['is_verify'] = 1;
        $where['is_show'] = 1;
        $where['is_del'] = 0;
//        if (isset($where['store_name']) && $where['store_name']) {
//            /** @var UserSearchServices $userSearchServices */
//            $userSearchServices = app()->make(UserSearchServices::class);
//            $searchIds = $userSearchServices->vicSearch($uid, $where['store_name'], $where);
//            if ($searchIds) {//之前查询结果记录
//                $where['ids'] = $searchIds;
//                unset($where['store_name']);
//            }
//        }
        $promotionsWhere = [];
        $list = [];
        //优惠活动凑单
        if (isset($where['promotions_id']) && $where['promotions_id']) {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            $promotionsWhere = $storePromotionsServices->collectProductById((int)$where['promotions_id']);
            unset($where['promotions_id']);
        } else if (isset($where['promotions_type']) && $where['promotions_type']) {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            $promotionsWhere = $storePromotionsServices->collectProductByType([(int)$where['promotions_type']]);
            unset($where['promotions_type']);
        }
        if (!$promotionsWhere || (isset($promotionsWhere['ids']) && $promotionsWhere['ids'])) {
            $where = array_merge($where, $promotionsWhere);
            if (isset($where['productId']) && $where['productId'] != '') {
                $where['ids'] = is_string($where['productId']) ? stringToIntArray($where['productId']) : $where['productId'];
                $where['ids'] = array_unique(array_map('intval', $where['ids']));
                unset($where['productId']);
            }
            $where['is_vip_product'] = 0;
            $discount = 100;
            $level_name = '';
            if (!$promotions_type && $uid) {
                /** @var UserServices $user */
                $user = app()->make(UserServices::class);
                $userInfo = $user->getUserCacheInfo($uid);
                $is_vip = $userInfo['is_money_level'] ?? 0;
                $where['is_vip_product'] = $is_vip ? -1 : 0;
                //用户等级是否开启
                /** @var SystemUserLevelServices $systemLevel */
                $systemLevel = app()->make(SystemUserLevelServices::class);
                $levelInfo = $systemLevel->getLevelCache((int)($userInfo['level'] ?? 0));
                if (sys_config('member_func_status', 1) && $levelInfo) {
                    $discount = $levelInfo['discount'] ?? 100;
                }
                $level_name = $levelInfo['name'] ?? '';
            }

            [$page, $limit] = $this->getPageValue();
            $field = ['id,relation_id,type,pid,delivery_type,product_type,store_name,cate_id,image,IFNULL(sales, 0) + IFNULL(ficti, 0) as sales,price,stock,activity,ot_price,spec_type,recommend_image,unit_name,is_vip,vip_price,is_presale_product,is_vip_product,system_form_id,is_presale_product,presale_start_time,presale_end_time,is_limit,limit_num,video_open,video_link,freight,star,store_label_id,brand_id,min_qty'];
            $list = $this->dao->getSearchList($where, $page, $limit, $field, '', ['couponId']);
            if ($list) {
                /** @var MemberCardServices $memberCardService */
                $memberCardService = app()->make(MemberCardServices::class);
                $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price') && sys_config('svip_price_status', 1);
                /** @var StoreProductLabelServices $storeProductLabelServices */
                $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
                foreach ($list as &$item) {
                    $minData = $this->getMinPrice($uid, $item, $discount);
                    $item['price_type'] = $minData['price_type'] ?? '';
                    $item['level_name'] = $level_name;
                    if ($item['price_type'] == 'member') {
                        $item['vip_price'] = $minData['vip_price'] ?? 0;
                        if (!$item['is_vip'] || !$vipStatus) {
                            $item['vip_price'] = 0;
                        }
                    } else {
                        $item['level_price'] = $minData['vip_price'] ?? 0;
                    }
                    $item['cart_button'] = $item['product_type'] > 0 || $item['is_presale_product'] || $item['system_form_id'] ? 0 : 1;

                    $item['presale_pay_status'] = $this->checkPresaleProductPay((int)$item['id'], $item);
                    if (!$item['video_open']) {
                        $item['video_link'] = '';
                    }
                    $item['store_label'] = [];
                    if ($item['store_label_id']) {
                        $item['store_label'] = $storeProductLabelServices->getLabelCache($item['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
                    }
                    if (isset($item['brand_id']) && $item['brand_id']) {
                        $item['brand_name'] = $this->productIdByBrandName((int)$item['id'], $item);
                    }
                }
                $list = $this->getActivityList($list);
                $list = $this->getProduceOtherList($list, $uid, isset($where['status']) && !!$where['status']);
                $list = $this->getProductPromotions($list, $promotions_type ? [$promotions_type] : []);
            }
        }
        return $list;
    }

    /**
     * 搜索获取商品品牌列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBrandList(array $where, int $uid = 0)
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if (isset($where['store_name']) && $where['store_name']) {
            $keyword = $where['store_name'];
            /** @var UserSearchServices $userSearchServices */
            $userSearchServices = app()->make(UserSearchServices::class);
            $searchIds = $userSearchServices->vicSearch($uid, $keyword, $where);
            if ($searchIds) {//之前查询结果记录
                $where['ids'] = $searchIds;
                unset($where['store_name']);
            } else {//分词查询
            }
        }
        if ($where['productId'] !== '') {
            $where['ids'] = explode(',', $where['productId']);
            $where['ids'] = array_unique(array_map('intval', $where['ids']));
            unset($where['productId']);
        }
        $brandIds = $this->dao->getColumnList($where);
        $brandIds = array_unique(array_filter($brandIds));
        /** @var StoreBrandServices $storeBrandServices */
        $storeBrandServices = app()->make(StoreBrandServices::class);
        $brandColumn = $storeBrandServices->searchList(['id' => $brandIds, 'is_del' => 0, 'is_show' => 1], [], ['id', 'brand_name']);
        return $brandColumn ?? [];
    }

    /**
     * 搜索获取商品品牌列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchFilter(int $uid, array $where, string $type = 'brand')
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if (isset($where['store_name']) && $where['store_name']) {
            $keyword = $where['store_name'];
            /** @var UserSearchServices $userSearchServices */
            $userSearchServices = app()->make(UserSearchServices::class);
            $searchIds = $userSearchServices->vicSearch($uid, $keyword, $where, false);
            if ($searchIds) {//之前查询结果记录
                $where['ids'] = $searchIds;
                unset($where['store_name']);
            } else {//分词查询
            }
        }
        if ($where['productId'] !== '') {
            $where['ids'] = explode(',', $where['productId']);
            $where['ids'] = array_unique(array_map('intval', $where['ids']));
            unset($where['productId']);
        }
        $promotions = $pIds = $brand = $store_label = [];
        //优惠活动凑单
        $promotionsWhere = [];
        $promotionsType = [];
        if (isset($where['promotions_id']) && $where['promotions_id']) {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            $promotionsWhere = $storePromotionsServices->collectProductById((int)$where['promotions_id']);
            unset($where['promotions_id']);
        } else if (isset($where['promotions_type']) && $where['promotions_type']) {
            $promotionsType = [(int)$where['promotions_type']];
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            $promotionsWhere = $storePromotionsServices->collectProductByType($promotionsType);
            unset($where['promotions_type']);
        }
        if (!$promotionsWhere || (isset($promotionsWhere['ids']) && $promotionsWhere['ids'])) {
            $where = array_merge($where, $promotionsWhere);
            $products = $this->dao->getColumnList($where, 'id,brand_id,store_label_id');
            if ($products) {
                $products = $this->getProductPromotions($products, $promotionsType);
                $promotionsArr = array_column($products, 'promotions');
                if ($promotionsArr) {
                    foreach ($promotionsArr as $item) {
                        if ($item && !in_array($item['id'], $pIds)) {
                            $pIds[] = $item['id'];
                            unset($item['product_id'], $item['products'], $item['promotions']);
                            $promotions[] = $item;
                        }
                    }
                }
                $brandIds = array_unique(array_filter(array_column($products, 'brand_id')));
                if ($brandIds) {
                    /** @var StoreBrandServices $storeBrandServices */
                    $storeBrandServices = app()->make(StoreBrandServices::class);
                    $brand = $storeBrandServices->getColumn(['id' => $brandIds, 'is_del' => 0, 'is_show' => 1], 'id,brand_name');
                }
                $store_label_ids = array_unique(explode(',', implode(',', array_filter(array_column($products, 'store_label_id')))));
                if ($store_label_ids) {
                    /** @var StoreProductLabelServices $storeProductLabelServices */
                    $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
                    $store_label = $storeProductLabelServices->getLabelCache($store_label_ids, ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
                }
            }
        }
        return compact('promotions', 'brand', 'store_label');
    }

    /**
     * 获取商品所在优惠活动
     * @param array $list
     * @param array $promotions_type
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductPromotions(array $list, array $promotions_type = [])
    {
        if (!$list) {
            return $list;
        }
        $productIds = array_column($list, 'id');
        /** @var StorePromotionsServices $storePromotionsServices */
        $storePromotionsServices = app()->make(StorePromotionsServices::class);
        $with = ['products' => function ($query) {
            $query->field('promotions_id,product_id,is_all,unique');
        }];
        $field = 'id,promotions_type,name,desc,image,promotions_type,title,product_id,product_partake_type,discount,discount_type,start_time,stop_time';
        [$promotionsArr, $productDetails, $promotionsDetail] = $storePromotionsServices->getProductsPromotionsDetail($productIds, $field, $with, $promotions_type);
        $promotionsArr = array_combine(array_column($promotionsArr, 'id'), $promotionsArr);
        foreach ($list as &$item) {
            $item['product_id'] = $item['id'];
            $promotionsIds = $productDetails[$item['id']] ?? [];
            $item['promotions'] = $item['activity_frame'] = $item['activity_background'] = [];
            if ($promotionsIds) {
                foreach ($promotionsIds as $id) {
                    $promotions = $promotionsArr[$id] ?? [];
                    if (!$promotions) {
                        continue;
                    }
                    unset($promotions['product_id'], $promotions['products'], $promotions['promotions']);
                    switch ($promotions['promotions_type']) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                            if (!$promotions_type) {//无指定优惠类型
                                if ($item['promotions']) {
                                    if (($promotions['promotions_type'] ?? 0) <= ($item['promotions']['promotions_type'] ?? 0)) {
                                        if (($promotions['promotions_type']['discount'] ?? 0) > ($item['promotions']['discount'] ?? 0)) {
                                            $item['promotions'] = $promotions;
                                        }
                                    } else {
                                        break;
                                    }
                                } else {
                                    $item['promotions'] = $promotions;
                                }
                            } else {
                                if (!$item['promotions']) {
                                    $item['promotions'] = $promotions;
                                } else {//同类活动展示最新的一个
                                    break;
                                }
                                break;
                            }
                            break;
                        case 5://边框
                            if (!$item['activity_frame']) {
                                $item['activity_frame'] = [
                                    'id' => $promotions['id'],
                                    'name' => $promotions['name'],
                                    'image' => $promotions['image'],
                                ];
                            } else {//同类活动展示最新的一个
                                break;
                            }
                            break;
                        case 6://背景
                            if (!$item['activity_background']) {
                                $item['activity_background'] = [
                                    'id' => $promotions['id'],
                                    'name' => $promotions['name'],
                                    'image' => $promotions['image'],
                                ];
                            } else {//同类活动展示最新的一个
                                break;
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取某些模板所需得购物车数量
     * @param array $list
     * @param int $uid
     * @param bool $type
     * @return array
     */
    public function getProduceOtherList(array $list, int $uid, bool $type = true)
    {
        if (!$type || !$list) {
            return $list;
        }
        $productIds = array_column($list, 'id');
        if ($productIds) {
            if ($uid) {
                /** @var StoreCartServices $cartServices */
                $cartServices = app()->make(StoreCartServices::class);
                $cartNumList = $cartServices->productIdByCartNum($productIds, $uid);
                $data = [];
                foreach ($cartNumList as $item) {
                    $data[$item['product_id']][] = $item['cart_num'];
                }
                $newNumList = [];
                foreach ($data as $key => $item) {
                    $newNumList[$key] = array_sum($item);
                }
                $cartNumList = $newNumList;
            } else {
                $cartNumList = [];
            }
            foreach ($list as &$item) {
                $item['is_att'] = false;
                $item['cart_num'] = $cartNumList[$item['id']] ?? 0;
            }
        }
        return $list;
    }

    /**
     * 获取商品活动标签
     * @param array $list
     * @param bool $status
     * @return array|array[]|mixed
     */
    public function getActivityList(array $list, bool $status = true)
    {
        if (!$list) return [];
        if ($status) {
            $productIds = array_column($list, 'id');
        } else {
            $productIds = [$list['id']];
            $list = [$list];
        }
        if ($productIds) {
            /** @var StoreSeckillServices $storeSeckillService */
            $storeSeckillService = app()->make(StoreSeckillServices::class);
            /** @var StoreBargainServices $storeBargainServices */
            $storeBargainServices = app()->make(StoreBargainServices::class);
            /** @var StoreCombinationServices $storeCombinationServices */
            $storeCombinationServices = app()->make(StoreCombinationServices::class);
            $seckillIdsList = $storeSeckillService->getSeckillIdsArrayCache($productIds);
            $pinkIdsList = $storeCombinationServices->getPinkIdsArrayCache($productIds);
            $bargrainIdsList = $storeBargainServices->getBargainIdsArrayCache($productIds);
            foreach ($list as &$item) {
                $id = $item['id'];
                $seckillId = $seckillIdsList && is_array($seckillIdsList) ? array_filter($seckillIdsList, function ($val) use ($item, $id) {
                    if ($val['product_id'] === $id) {
                        return $val;
                    }
                }) : [];
                $item['activity'] = $this->activity($item['activity'],
                    $item['id'],
                    $pinkIdsList[$id] ?? 0,
                    $seckillId,
                    $bargrainIdsList[$id] ?? 0,
                    $status);
                if (isset($item['couponId'])) {
                    $item['checkCoupon'] = (bool)count($item['couponId']);
                    unset($item['couponId']);
                } else {
                    $item['checkCoupon'] = false;
                }
            }
        }
        if ($status) {
            return $list;
        } else {
            return $list[0]['activity'];
        }
    }

    /**
     * 获取商品在此时段活动优先类型
     * @param string $activity
     * @param int $id
     * @param int $combinationId
     * @param array $seckillId
     * @param int $bargainId
     * @param bool $status
     * @return array
     */
    public function activity(string $activity, int $id, int $combinationId, array $seckillId, int $bargainId, bool $status = true)
    {
        if (!$activity) {
            $activity = '0,1,2,3';//如果老商品没有活动顺序，默认活动顺序，秒杀-砍价-拼团
        }
        $activity = explode(',', $activity);
        if ($activity[0] == 0 && $status) return [];
        $activityId = [];
        $time = 0;
        if ($seckillId) {
            /** @var StoreSeckillTimeServices $storeSeckillTimeServices */
            $storeSeckillTimeServices = app()->make(StoreSeckillTimeServices::class);
            $timeList = $storeSeckillTimeServices->time_list();
            if ($timeList) {
                $timeList = array_combine(array_column($timeList, 'id'), $timeList);
                $today = date('Y-m-d');
                $currentHour = date('Hi');
                foreach ($seckillId as $v) {
                    $time_ids = is_string($v['time_id']) ? explode(',', $v['time_id']) : $v['time_id'];
                    if ($time_ids) {
                        foreach ($time_ids as $time_id) {
                            $timeInfo = $timeList[$time_id] ?? [];
                            if ($timeInfo) {
                                $start = str_replace(':', '', $timeInfo['start_time']);
                                $end = str_replace(':', '', $timeInfo['end_time']);
                                if ($currentHour >= $start && $currentHour < $end) {
                                    $activityId[1] = $v['id'];
                                    $time = strtotime($today . ' ' . $timeInfo['end_time']);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($bargainId) $activityId[2] = $bargainId;
        if ($combinationId) $activityId[3] = $combinationId;
        $data = [];
        foreach ($activity as $k => $v) {
            if (array_key_exists($v, $activityId)) {
                if ($status) {
                    $data['type'] = $v;
                    $data['id'] = $activityId[$v];
                    if ($v == 1) $data['time'] = $time;
                    break;
                } else {
                    if ($v != 0) {
                        $arr['type'] = $v;
                        $arr['id'] = $activityId[$v];
                        if ($v == 1) $arr['time'] = $time;
                        $data[] = $arr;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取热门商品
     * @param array $where
     * @param string $order
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProducts(array $where, string $order = '', int $num = 0, array $with = ['couponId', 'descriptions'])
    {
        [$page, $limit] = $this->getPageValue();
        if ($num) {
            $page = 1;
            $limit = $num;
        }
        $list = $this->dao->getSearchList($where, $page, $limit, ['id,pid,type,store_name,cate_id,image,IFNULL(sales, 0) + IFNULL(ficti, 0) as sales,price,is_vip,vip_price,stock,activity,unit_name,freight,star,is_presale_product,presale_start_time,presale_end_time,store_label_id,brand_id'], $order, $with);
        if ($list) {
            $discount = 100;
            $level_name = '';
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price') && sys_config('svip_price_status', 1);
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            foreach ($list as $k => &$item) {
                $minData = $this->getMinPrice(0, $item, $discount);
                $item['price_type'] = $minData['price_type'] ?? '';
                $item['level_name'] = $level_name;
                if ($item['price_type'] == 'member') {
                    $item['vip_price'] = $minData['vip_price'] ?? 0;
                    if (!$item['is_vip'] || !$vipStatus) {
                        $item['vip_price'] = 0;
                    }
                } else {
                    $item['level_price'] = $minData['vip_price'] ?? 0;
                }
                $item['store_label'] = [];
                if ($item['store_label_id']) {
                    $item['store_label'] = $storeProductLabelServices->getLabelCache($item['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
                }
                if (isset($item['brand_id']) && $item['brand_id']) {
                    $item['brand_name'] = $this->productIdByBrandName((int)$item['id'], $item);
                }
                $item['presale_pay_status'] = $this->checkPresaleProductPay((int)$item['id'], $item);
            }
            $list = $this->getActivityList($list);
            $list = $this->getProductPromotions($list);
        }
        return $list;
    }

    /**
     * 检测预售商品是否可以购买
     * @param int $id
     * @param array $productInfo
     * @return int
     */
    public function checkPresaleProductPay(int $id, array $productInfo = [])
    {
        if (!$id) return 0;
        if (!$productInfo) {
            $productInfo = $this->getCacheProductInfo($id);
            if (!$productInfo) {
                return 0;
            }
        }
        if (!isset($productInfo['is_presale_product']) || !isset($productInfo['presale_start_time']) || !isset($productInfo['presale_end_time'])) {
            return 0;
        }
        if ($productInfo['is_presale_product']) {
            if ($productInfo['presale_start_time'] > time()) {
                return 1;
            } elseif ($productInfo['presale_start_time'] <= time() && $productInfo['presale_end_time'] >= time()) {
                return 2;
            } elseif ($productInfo['presale_end_time'] < time()) {
                return 3;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * 商品详情显示计算最高佣金
     * @param $storeInfo
     * @param $productValue
     * @param $uid
     * @return int|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/3 下午12:12
     */
    public function productBrokerage($storeInfo, $productValue, $uid): mixed
    {
        //商城分销是否开启
        if (!sys_config('brokerage_func_status') || $storeInfo['is_brokerage'] == 0) {
            return 0;
        }

        //获取后台一级返佣比例
        $storeBrokerageRatio = sys_config('store_brokerage_ratio');

        if ($uid) {
            $user_info = app()->make(UserServices::class)->getUserCacheInfo($uid);
            $agent_level = $user_info['agent_level'] ?? 0;

            if ($agent_level) {
                $one_brokerage_level = app()->make(AgentLevelServices::class)->getLevelInfo($agent_level);
                $storeBrokerageRatio = bcadd($storeBrokerageRatio, bcmul($storeBrokerageRatio, bcdiv(($one_brokerage_level['one_brokerage'] ?? 0), 100, 2), 2), 2);
            }
        }

        if ($storeInfo['is_sub'] == 0) {
            $price = max(array_column($productValue, 'price'));
            $brokerageRatio = bcdiv($storeBrokerageRatio, 100, 4);
            $brokerage = bcmul((string)$price, (string)$brokerageRatio, 2);
        } else {
            $brokerage = max(array_column($productValue, 'brokerage'));
        }

        return $brokerage;
    }

    /**
     * 获取商品详情
     * @param int $id
     * @param int $type
     * @param int $promotions_type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productDetail(int $uid, int $id, int $type, int $promotions_type = 0)
    {
        $data['uid'] = $uid;
        $storeInfo = $this->getCacheProductInfo($id);
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        }
        if (!$storeInfo['is_show']) {
            throw new ValidateException('商品不存在!');
        }
        if ($storeInfo['is_del']) {
            throw new ValidateException('商品不存在!!');
        }
        //新出接口，删除详情内容返回
//        unset($storeInfo['description']);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);
        $infoDiy = $diyServices->getProductDetailDiy();
        //diy控制参数
        if (!isset($infoDiy['showService']) || !in_array(3, $infoDiy['showService'])) {
            $storeInfo['specs'] = [];
        }
        $storeInfo['brand_name'] = $this->productIdByBrandName((int)$storeInfo['id'], $storeInfo);
        $storeInfo['store_label'] = $storeInfo['ensure'] = [];
        if ($storeInfo['store_label_id']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $storeInfo['store_label'] = $storeProductLabelServices->getLabelCache($storeInfo['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
        }
        if ($storeInfo['ensure_id'] && isset($infoDiy['showService']) && in_array(2, $infoDiy['showService'])) {
            /** @var StoreProductEnsureServices $storeProductEnsureServices */
            $storeProductEnsureServices = app()->make(StoreProductEnsureServices::class);
            $storeInfo['ensure'] = $storeProductEnsureServices->getEnsurCache($storeInfo['ensure_id'], ['id', 'name', 'image', 'desc']);
        }

        $discount = isset($storeInfo['promotions'][0]['promotions_type']) && $storeInfo['promotions'][0]['promotions_type'] == 1 ? $storeInfo['promotions'][0]['discount'] : -1;

        $configData = SystemConfigService::more(['site_url', 'tengxun_map_key', 'store_self_mention', 'routine_contact_type', 'site_name', 'share_qrcode', 'store_func_status', 'product_poster_title']);
        $siteUrl = $configData['site_url'] ?? '';
        if ($storeInfo['video_open']) {
            if ($storeInfo['video_link'] && strpos($storeInfo['video_link'], 'http') === false) {
                $storeInfo['video_link'] = $siteUrl . $storeInfo['video_link'];
            }
        } else {
            $storeInfo['video_link'] = '';
        }
        $storeInfo['image'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['image_base'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['fsales'] = $storeInfo['ficti'] + $storeInfo['sales'];

        /** @var UserRelationServices $userRelationServices */
        $userRelationServices = app()->make(UserRelationServices::class);
        $storeInfo['userCollect'] = $userRelationServices->isProductRelationCache(['uid' => $uid, 'relation_id' => $id, 'type' => 'collect', 'category' => UserRelationServices::CATEGORY_PRODUCT]);
        $storeInfo['userLike'] = 0;

        //预售相关
        $storeInfo['presale_pay_status'] = $this->checkPresaleProductPay($id, $storeInfo);

        $storeInfo['presale_start_time'] = $storeInfo['presale_start_time'] ? date('Y-m-d H:i', $storeInfo['presale_start_time']) : '';
        $storeInfo['presale_end_time'] = $storeInfo['presale_end_time'] ? date('Y-m-d H:i', $storeInfo['presale_end_time']) : '';
        //系统表单
        $storeInfo['custom_form'] = [];
        if ($storeInfo['system_form_id']) {
            /** @var SystemFormServices $systemFormServices */
            $systemFormServices = app()->make(SystemFormServices::class);
            $systemForm = $systemFormServices->value(['id' => $storeInfo['system_form_id']], 'value');
            if ($systemForm) {
                $storeInfo['custom_form'] = is_string($systemForm) ? json_decode($systemForm, true) : $systemForm;
            }
        }
        //有自定义表单或预售或虚拟不展示加入购物车按钮
        $storeInfo['cart_button'] = $storeInfo['custom_form'] || $storeInfo['is_presale_product'] || $storeInfo['product_type'] > 0 ? 0 : 1;

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetailCache($id, $uid, $type, 0, 0, $storeInfo, $discount);
        //最高佣金
        $data['brokerage'] = $this->productBrokerage($storeInfo, $productValue, $uid);
        $attrValue = $productValue;
        if (!$storeInfo['spec_type']) {
            $productAttr = [];
            $productValue = [];
        }
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $productValue;
        $storeInfo['small_image'] = get_thumb_water($storeInfo['image']);

        /**
         * 判断配送方式
         */
        $storeInfo['delivery_type'] = $this->getDeliveryType($storeInfo['type'], $storeInfo['relation_id'], $storeInfo['delivery_type']);

        $data['storeInfo'] = $storeInfo;

        /** @var MemberCardServices $memberCardService */
        $memberCardService = app()->make(MemberCardServices::class);
        $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price') && sys_config('svip_price_status', 1);
        $price_count = count($infoDiy['showPrice']);
        if ($price_count >= 1) {
            //两个都选 取最低的
            $minPrice = $this->getMinPrice($uid, $data['storeInfo'], null, $price_count == 2);
            if ($price_count == 1) {//
                if (in_array(1, $infoDiy['showPrice'])) {//svip
                    $minPrice['price_type'] = 'member';
                } else {//用户等级
                    $minPrice['price_type'] = 'level';
                    $minPrice['vip_price'] = $minPrice['level_price'];
                }
            }
        } else {//一个都不展示
            $minPrice = ['vip_price' => 0, 'price_type' => '', 'level_name' => ''];
        }

        $data['storeInfo'] = array_merge($data['storeInfo'], $minPrice);
        if ($data['storeInfo']['price_type'] == 'member' && (!$data['storeInfo']['is_vip'] || !$vipStatus)) {
            $data['storeInfo']['vip_price'] = 0;
        }
        $data['priceName'] = 0;
        if ($uid) {
            $data['priceName'] = $this->getPacketPrice($storeInfo, $attrValue, $uid);
        }
        $data['reply'] = [];
        $data['replyChance'] = $data['replyCount'] = 0;
        if (isset($infoDiy['showReply']) && $infoDiy['showReply']) {
            /** @var StoreProductReplyServices $storeProductReplyService */
            $storeProductReplyService = app()->make(StoreProductReplyServices::class);
            $reply = $storeProductReplyService->getRecProductReplyCache($id, (int)($infoDiy['replyNum'] ?? 1));
            $data['reply'] = $reply ? get_thumb_water($reply, 'small', ['pics']) : [];
            [$replyCount, $goodReply, $replyChance] = $storeProductReplyService->getProductReplyData($id);
            $data['replyChance'] = $replyChance;
            $data['replyCount'] = $replyCount;
        }
        //种草秀
        $data['elegant_list'] = [];
        $data['elegant_count'] = 0;
        if (isset($infoDiy['showCommunity']) && $infoDiy['showCommunity']) {
            /** @var CommunityServices $communityServices */
            $communityServices = app()->make(CommunityServices::class);
            $elegant = $communityServices->getJoinCommunityList(['product_id' => $id], 0, 'left_id', (int)($infoDiy['communityNum'] ?? 1));
            $data['elegant_list'] = $elegant['list'] ?? [];
            $data['elegant_count'] = $elegant['count'] ?? [];
        }
        $data['mapKey'] = $configData['tengxun_map_key'] ?? '';
        $data['store_func_status'] = (int)($configData['store_func_status'] ?? 1);//门店是否开启
        $data['store_self_mention'] = $data['store_func_status'] ? (int)($configData['store_self_mention'] ?? 1) : 0;//门店自提是否开启
        $data['routine_contact_type'] = $configData['routine_contact_type'] ?? 0;
        $data['site_name'] = $configData['site_name'] ?? '';
        $data['share_qrcode'] = $configData['share_qrcode'] ?? 0;
        $data['product_poster_title'] = $configData['product_poster_title'] ?? '';
        /** @var StoreProductRankServices $productRankServices */
        $productRankServices = app()->make(StoreProductRankServices::class);
        $keyArr = [1, 2, 3];
        $rank = 0;
        $rank_type = 1;
        foreach ($keyArr as $item) {
            $rankOne = $productRankServices->getProductRank($uid, $id, (int)$item);
            if (!$rankOne) continue;
            if ($rank) {
                if ($rank > $rankOne) {
                    $rank = $rankOne;
                    $rank_type = $item;
                }
            } else {
                $rank = $rankOne;
                $rank_type = $item;
            }
        }
        $data['storeInfo']['rank'] = $rank;
        $data['storeInfo']['rank_type'] = $rank_type;
        //浏览记录
        ProductLogJob::dispatch(['visit', ['uid' => $uid, 'id' => $id, 'product_id' => $id], 'product']);
        return $data;
    }

    /**
     * 是否开启vip
     * @param bool $vip
     * @return bool
     */
    public function vipIsOpen(bool $vip = false, $vipStatus = -1)
    {
        if (!$vip) {
            return false;
        }
        $member_status = sys_config('member_card_status', 1);
        if (!$member_status) {
            return false;
        }
        if ($vipStatus == -1) {
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false, $member_status);
        }
        return $vipStatus && $member_status && $vip && sys_config('svip_price_status', 1);
    }

    /**
     * 获取商品分销佣金最低和最高
     * @param $storeInfo
     * @param $productValue
     * @param int $uid
     * @return int|string
     */
    public function getPacketPrice($storeInfo, $productValue, int $uid)
    {
        if (!count($productValue)) {
            return 0;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->checkUserPromoter($uid)) {
            return 0;
        }
        //独立反拥
        if (isset($storeInfo['is_sub']) && $storeInfo['is_sub'] == 1) {
            $maxPrice = (float)max(array_column($productValue, 'brokerage'));
            $minPrice = (float)min(array_column($productValue, 'brokerage'));
        } else {
            //一级返佣金额
            $store_brokerage_ratio = sys_config('store_brokerage_ratio');
            $store_brokerage_ratio = (string)bcdiv((string)$store_brokerage_ratio, '100', 2);
            //佣金计算方式
            $brokerageComputeType = sys_config('brokerage_compute_type', 1);

            $brokeragePrice = [];
            foreach ($productValue as $attr) {
                switch ($brokerageComputeType) {
                    case 1://售价
                    case 2://实付金额
                        //都用售价计算
                        $brokeragePrice[] = (float)bcmul($store_brokerage_ratio, (string)($attr['price'] ?? 0), 2);
                        break;
                    case 3://商品利润
                        $brokeragePrice[] = bcmul($store_brokerage_ratio, bcsub((string)($attr['price'] ?? 0), (string)($attr['cost'] ?? 0), 2), 2);
                        break;
                }
            }
            $maxPrice = max($brokeragePrice);
            $minPrice = min($brokeragePrice);
            //大于1 取整（两位小数前端展示超出）
            $maxPrice = $maxPrice > 1 ? floor($maxPrice) : $maxPrice;
            $minPrice = $minPrice > 1 ? floor($minPrice) : $minPrice;
        }
        if ($minPrice == 0 && $maxPrice == 0) {
            $priceName = 0;
        } else if ($minPrice == 0 && $maxPrice)
            $priceName = $maxPrice;
        else if ($maxPrice == 0 && $minPrice)
            $priceName = $minPrice;
        else if ($maxPrice == $minPrice && $minPrice)
            $priceName = $maxPrice;
        else
            $priceName = $minPrice . '~' . $maxPrice;
        return strlen(trim($priceName)) ? $priceName : 0;
    }

    /**
     * 获取商品用户等级、svip最低价格，优惠类型
     * @param int $uid
     * @param $productInfo
     * @param $discount
     * @param bool $is_min
     * @return array
     */
    public function getMinPrice(int $uid, $productInfo, $discount = null, $is_min = true)
    {
        $level_name = '';
        $vip_price = 0;
        $price_type = '';
        $level_price = 0;
        if ($productInfo) {
            if (is_null($discount)) {
                $discount = 100;
                if ($uid) {
                    /** @var UserServices $user */
                    $user = app()->make(UserServices::class);
                    $userInfo = $user->getUserCacheInfo($uid);
                    //用户等级是否开启
                    /** @var SystemUserLevelServices $systemLevel */
                    $systemLevel = app()->make(SystemUserLevelServices::class);
                    $levelInfo = $systemLevel->getLevelCache((int)($userInfo['level'] ?? 0));
                    if (sys_config('member_func_status', 1) && $levelInfo) {
                        $discount = $levelInfo['discount'] ?? 100;
                    }
                    $level_name = $levelInfo['name'] ?? '';
                }
            }
            if ($discount >= 0 && $discount < 100) {//等级价格
                $level_price = (float)bcmul((string)bcdiv((string)$discount, '100', 2), (string)$productInfo['price'], 2);
            } else {
                $level_price = $productInfo['price'];
            }
            if ($productInfo['is_vip']) {//svip价格
                $vip_price = $productInfo['vip_price'];
            }
            if (($discount != 100 || $productInfo['is_vip']) && $is_min) {//需要对比价格
                if ($discount != 100 && $productInfo['is_vip']) {
                    if ($level_price < $productInfo['vip_price']) {
                        $price_type = 'level';
                        $vip_price = $level_price;
                    } else {
                        $price_type = 'member';
                        $vip_price = $productInfo['vip_price'];
                    }
                } else if ($discount != 100 && !$productInfo['is_vip']) {
                    $price_type = 'level';
                    $vip_price = $level_price;
                } else if ($discount == 100 && $productInfo['is_vip']) {
                    $price_type = 'member';
                    $vip_price = $productInfo['vip_price'];
                }
            }
        }
        return compact('level_name', 'vip_price', 'price_type', 'level_price');
    }

    /**
     * 计算商品优惠后金额、优惠价格
     * @param $price
     * @param int $uid
     * @param $userInfo
     * @param $vipStatus
     * @param int $discount
     * @param float $vipPrice
     * @param int $is_vip
     * @param false $is_show
     * @param false $level_info
     * @return array  [优惠后的总金额,优惠金额]
     */
    public function setLevelPrice($price, int $uid, $userInfo, $vipStatus, $discount = 0, $vipPrice = 0.00, $is_vip = 0, $is_show = false, $level_info = [])
    {
        if (!(float)$price) return [(float)$price, (float)$price, ''];
        if (!$vipStatus) $is_vip = 0;
        //已登录
        if ($uid) {
            if (!$userInfo) {
                /** @var UserServices $user */
                $user = app()->make(UserServices::class);
                $userInfo = $user->getUserCacheInfo($uid);
            }
            if ($discount === 0) {
                /** @var SystemUserLevelServices $systemLevel */
                $systemLevel = app()->make(SystemUserLevelServices::class);
                $discount = $systemLevel->getDiscount($uid, (int)$userInfo['level']);
            }
        } else {
            //没登录
            $discount = 100;
        }

        $discount = bcdiv((string)$discount, '100', 2);
        $level_price = -1;
        if (count($level_info) > 0 && $level_info['level_type'] == 2 && $uid && $userInfo['level'] != 0) {
            $level_prices = json_decode($level_info['level_price'], true);
            foreach ($level_prices as $val) {
                if ($userInfo['level'] == $val['id']) {
                    $level_price = $val['price'];
                }
            }
        }
        //执行减去会员优惠金额
        [$truePrice, $vip_truePrice, $type] = $this->isPayLevelPrice($uid, $userInfo, $vipStatus, $price, $discount, $vipPrice, $is_vip, $is_show, $level_price);
        //返回优惠后的总金额
        $truePrice = $truePrice < 0.01 ? 0.01 : $truePrice;
        //优惠的金额
        $vip_truePrice = $vip_truePrice == $price ? bcsub((string)$vip_truePrice, '0.01', 2) : $vip_truePrice;
        return [(float)$truePrice, (float)$vip_truePrice, $type];
    }

    /**
     * 获取会员价格（付费会员价格和购买商品会员价格）
     * @param int $uid
     * @param $userInfo
     * @param $vipStatus
     * @param $price
     * @param string $discount
     * @param float $payVipPrice
     * @param int $is_vip
     * @param false $is_show
     * @param false $level_price //单独设置等级价格,-1不设置
     * @return array
     */
    public function isPayLevelPrice(int $uid, $userInfo, $vipStatus, $price, string $discount, $payVipPrice = 0.00, $is_vip = 0, $is_show = false, $level_price = -1)
    {
        //is_vip == 0表示会员价格不启用，展示为零
        if ($is_vip == 0) $payVipPrice = 0;
        if (!$userInfo && $uid) {
            //检测用户是否是付费会员
            /** @var  UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserCacheInfo($uid);
        }
        //新增等级价格单独设置
        $noPayVipPrice = ($level_price == -1) ? (($discount && $discount != 0.00) ? bcmul((string)$discount, (string)$price, 2) : $price) : $level_price;
        if ($payVipPrice < $noPayVipPrice && $payVipPrice > 0) {
            $vipPrice = $payVipPrice;
            $type = 'member';
        } else {
            $vipPrice = $noPayVipPrice;
            $type = 'level';
        }
        //如果$isSingle==true 返回优惠后的总金额，否则返回优惠的金额
        if ($vipStatus && $is_vip == 1) {
            //$is_show == false 是计算支付价格，true是展示
            if (!$is_show) {
                return [$vipPrice, bcsub((string)$price, (string)$vipPrice, 2), $type];
            } else {
                $isVip = $this->getItem('isVip', 0);
                //强制计算用户是vip
                if ($isVip || ($userInfo && isset($userInfo['is_money_level']) && $userInfo['is_money_level'] > 0)) {
                    return [$vipPrice, bcsub((string)$price, (string)$vipPrice, 2), $type];
                } else {
                    $type = 'level';
                    return [$noPayVipPrice, bcsub((string)$price, (string)$noPayVipPrice, 2), $type];
                }
            }
        } else {
            $type = 'level';
            return [(float)$noPayVipPrice, (float)bcsub((string)$price, (string)$noPayVipPrice, 2), $type];
        }
    }

    /**
     * 商品是否存在
     * @param int $productId
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isValidProduct(int $productId, bool $is_show = false, string $field = '*')
    {
        $where = ['id' => $productId, 'is_del' => 0, 'is_show' => 1, 'is_verify' => 1];
        if ($is_show) unset($where['is_show']);
        return $this->dao->getOne($where, $field);
    }

    /**
     * 获取商品库存
     * @param int $productId
     * @param string $uniqueId
     * @return int|mixed
     */
    public function getProductStock(int $productId, string $uniqueId = '')
    {
        /** @var  StoreProductAttrValueServices $StoreProductAttrValue */
        $StoreProductAttrValue = app()->make(StoreProductAttrValueServices::class);
        return $uniqueId == '' ?
            $this->dao->value(['id' => $productId], 'stock') ?: 0
            : $StoreProductAttrValue->uniqueByStock($uniqueId);
    }

    /**
     * 下单、退款商品、规格库存变化清空缓存
     * @return void
     */
    public function clearProductCache()
    {
        $this->dao->cacheTag()->clear();
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        $storeProductAttrServices->cacheTag()->clear();
    }

    /**
     * 减库存,加销量
     * @param int $num
     * @param int $productId
     * @param string $unique
     * @return bool
     */
    public function decProductStock(int $num, int $productId, string $unique = '')
    {
        $res = true;
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            $res = $res && $skuValueServices->decProductAttrStock($productId, $unique, $num);
        }
        $res = $res && $this->dao->decStockIncSales(['id' => $productId], $num);
        if ($res) {
            $this->workSendStock($productId);
        }
        $this->clearProductCache();
        return $res;
    }

    /**
     * 减销量，加库存
     * @param int $num
     * @param int $productId
     * @param string $unique
     * @return bool
     */
    public function incProductStock(int $num, int $productId, string $unique = '')
    {
        $res = true;
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            $res = $res && $skuValueServices->incProductAttrStock($productId, $unique, $num);
        }
        $res = $res && $this->dao->incStockDecSales(['id' => $productId], $num);
        $this->clearProductCache();
        return $res;
    }

    /**
     * 库存预警发送消息
     * @param int $productId
     */
    public function workSendStock(int $productId)
    {
        ProductStockTips::dispatch([$productId]);
    }

    /**
     * 获取推荐商品
     * @param int $uid
     * @param array $where
     * @param int $num
     * @param string $type
     * @param string $order
     * @return array|null
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \throwable
     */
    public function getRecommendProduct(int $uid, array $where = [], int $num = 0, string $type = 'mid', string $order = 'sort DESC, id DESC')
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_vip_product'] = 0;
        $where['is_verify'] = 1;
        $where['pid'] = 0;
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $is_vip = $userServices->value(['uid' => $uid], 'is_money_level');
            $where['is_vip_product'] = $is_vip ? -1 : 0;
        }
        $field = ['id', 'type', 'pid', 'image', 'store_name', 'store_info', 'cate_id', 'price', 'ot_price', 'IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'unit_name', 'sort', 'activity', 'stock', 'vip_price', 'is_vip', 'video_link', 'freight', 'star', 'is_presale_product', 'presale_start_time', 'presale_end_time', 'store_label_id', 'brand_id'];
        $list = $this->dao->getRecommendProduct($where, $field, $num, $page, $limit, ['couponId'], $order);
        if ($list) {
            $list = get_thumb_water($list, $type);
            $list = $this->getActivityList($list);
            $list = $this->getProductPromotions($list);
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            if (isset($where['is_vip']) && $where['is_vip'] == 1) {
                $vipStatus = 1;
            } else {
                $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false) && sys_config('svip_price_status', 1);
            }
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            foreach ($list as &$item) {
                if (!($vipStatus && $item['is_vip'])) {
                    $item['vip_price'] = 0;
                }
                $item['store_label'] = [];
                if ($item['store_label_id']) {
                    $item['store_label'] = $storeProductLabelServices->getLabelCache($item['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
                }
                if (isset($item['brand_id']) && $item['brand_id']) {
                    $item['brand_name'] = $this->productIdByBrandName((int)$item['id'], $item);
                }
                $item['presale_pay_status'] = $this->checkPresaleProductPay((int)$item['id'], $item);
            }
        }
        return $list;
    }

    /**
     * 生成商品复制口令关键字
     * @param int $productId
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductWords(int $productId)
    {
        $productInfo = $this->getCacheProductInfo($productId);
        $keyWords = "";
        if ($productInfo) {
            $oneKey = "crmeb-fu致文本 Http:/ZБ";
            $twoKey = "Б轉移至☞" . sys_config('site_name') . "☜";
            $threeKey = "【" . $productInfo['store_name'] . "】";
            $mainKey = base64_encode($productId);
            $keyWords = $oneKey . $mainKey . $twoKey . $threeKey;
        }
        return $keyWords;
    }

    /**
     * 通过商品id获取商品分类
     * @param array $productId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productIdByProductCateName(array $productId)
    {
        $data = $this->dao->productIdByCateId($productId);
        $cateData = [];
        foreach ($data as $item) {
            $cateData[$item['id']] = implode(',', array_map(function ($i) {
                return $i['cate_name'];
            }, $item['cateName']));
        }
        return $cateData;
    }

    /**
     * 根据商品id获取品牌名称
     * @param int $productId
     * @return mixed
     */
    public function productIdByBrandName(int $productId, $productInfo = [])
    {
        if ($productInfo) {
            $brand_id = $productInfo['brand_id'] ?? 0;
        } else {
            $storeInfo = $this->getCacheProductInfo($productId);
            $brand_id = $storeInfo['brand_id'] ?? 0;
        }

        /** @var StoreBrandServices $storeBrandServices */
        $storeBrandServices = app()->make(StoreBrandServices::class);
        $storeBrandInfo = $storeBrandServices->getCacheBrandInfo((int)$brand_id);

        return $storeBrandInfo['brand_name'] ?? '';
    }

    /**
     * 自动上下架
     * @return bool
     */
    public function autoUpperShelves()
    {
        $this->dao->overUpperShelves(1);
        $this->dao->overUpperShelves(0);
        return true;
    }

    /**
     * 获取预售列表
     * @param int $uid
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPresaleList(int $uid, array $where, int $limit = 0)
    {
        if ($limit) {//diy获取条数
            $page = 0;
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $where['is_show'] = 1;
        $data = $this->dao->getPresaleList($where, $page, $limit);
        if ($data['list']) {
            /** @var StoreProductCategoryServices $storeCategoryService */
            $storeCategoryService = app()->make(StoreProductCategoryServices::class);
            /** @var StoreCouponIssueServices $couponServices */
            $couponServices = app()->make(StoreCouponIssueServices::class);
            /** @var StoreBrandServices $storeBrandServices */
            $storeBrandServices = app()->make(StoreBrandServices::class);
            $brands = $storeBrandServices->getColumn([], 'id,pid', 'id');
            /** @var SystemFormServices $systemFormServices */
            $systemFormServices = app()->make(SystemFormServices::class);
            $systemForms = $systemFormServices->getColumn([['id', 'in', array_unique(array_column($data['list'], 'system_form_id'))], ['is_del', '=', 0]], 'id,value', 'id');
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            foreach ($data['list'] as &$item) {
                $custom_form = $systemForms[$item['system_form_id']]['value'] ?? [];
                $item['custom_form'] = is_string($custom_form) ? json_decode($custom_form, true) : $custom_form;

                $cateId = $item['cate_id'];
                $cateId = explode(',', $cateId);
                $cateId = array_merge($cateId, $storeCategoryService->cateIdByPid($cateId));
                $cateId = array_diff($cateId, [0]);
                $brandId = [];
                if ($item['brand_id']) {
                    $brandId = $brands[$item['brand_id']] ?? [];
                }
                $item['store_label'] = [];
                if ($item['store_label_id']) {
                    $item['store_label'] = $storeProductLabelServices->getLabelCache($item['store_label_id'], ['id', 'label_name', 'style_type', 'color', 'bg_color', 'border_color', 'icon']);
                }
                $item['presale_pay_status'] = $this->checkPresaleProductPay((int)$item['id'], $item);
                $counpons = $couponServices->getIssueCouponListNew($uid, ['product_id' => $item['id'], 'cate_id' => $cateId, 'brand_id' => $brandId], 'id,type,coupon_type,coupon_title,coupon_price,use_min_price', 0, 1, 'coupon_price desc,sort desc,id desc');
                $item['coupon'] = $counpons[0] ?? [];
                if (isset($item['brand_id']) && $item['brand_id']) {
                    $item['brand_name'] = $this->productIdByBrandName((int)$item['id'], $item);
                }
            }
        }
        return $data;
    }

    /**
     * 判断配送方式
     * @param int $type 商品类型 0平台 1门店 2供应商
     * @param int $relation_id 门店id
     * @param array $delivery_type 配送方式
     * @return array
     *
     * @date 2022/09/09
     * @author yyw
     */
    public function getDeliveryType(int $type, int $relation_id, array $delivery_type)
    {
        //门店总开关
        if (!sys_config('store_func_status', 1)) {
            if (in_array('2', $delivery_type)) unset($delivery_type[array_search('2', $delivery_type)]);
        } else {
            //获取总平台自提配置设置
            $store_self_mention = (bool)sys_config('store_self_mention');
            $store_mention = true;
            //获取门店自提配置
            if ($type === 1 && $relation_id) {
                /** @var SystemStoreServices $storeServices */
                $storeServices = app()->make(SystemStoreServices::class);
                $storeInfo = $storeServices->cacheRemember($relation_id, function () use ($storeServices, $relation_id) {
                    $storeInfo = $storeServices->get(['id' => $relation_id, 'is_show' => 1, 'is_del' => 0]);
                    return $storeInfo ? $storeInfo->toArray() : null;
                });
                $store_mention = ($storeInfo['is_store'] ?? 0) === 1;
            }
            //判断当前商品配送方式
            if (!$store_self_mention || !$store_mention || !(in_array('2', $delivery_type))) {
                if (in_array('2', $delivery_type)) unset($delivery_type[array_search('2', $delivery_type)]);
            }
        }
        return array_merge($delivery_type);
    }

    /**
     * 计算商品实际到手价
     * @param int $uid
     * @param int $id
     * @param string $unique
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \throwable
     */
    public function computedProductPayPrice(int $uid, int $id, string $unique = '')
    {
        $productInfo = $this->getCacheProductInfo($id);
        if (!$productInfo) {
            throw new ValidateException('商品不存在');
        }
        /** @var StoreProductAttrValueServices $attrServices */
        $attrServices = app()->make(StoreProductAttrValueServices::class);
        $where = ['product_id' => $id, 'type' => 0];
        if ($unique) {
            $attrInfo = $attrServices->get($where + ['unique' => $unique]);
        } else {
            $attrInfo = $attrServices->getList($where, '*', 0, 1, 'price asc');
            $attrInfo = $attrInfo[0] ?? [];
        }
        if (!$attrInfo) {
            throw new ValidateException('商品不存在');
        }
        $cartInfo = [];
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $key = $storeOrderCreateService->getNewOrderId((string)$uid);
        $info['id'] = $key;
        $info['type'] = 0;
        $info['store_id'] = 0;
        $info['tourist_uid'] = 0;
        $info['product_type'] = $productInfo['product_type'];
        $info['activity_id'] = 0;
        $info['discount_product_id'] = 0;
        $info['product_id'] = $id;
        $info['product_attr_unique'] = $attrInfo['unique'];
        $info['cart_num'] = 1;
        $info['productInfo'] = [];
        $info['productInfo'] = is_object($productInfo) ? $productInfo->toArray() : $productInfo;
        $info['attrInfo'] = is_object($productInfo) ? $attrInfo->toArray() : $attrInfo;
        $info['productInfo']['attrInfo'] = $info['attrInfo'];
        $info['sum_price'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
        $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
        $info['integral'] = 0;
        $info['trueStock'] = $info['productInfo']['attrInfo']['stock'] ?? 0;
        $info['costPrice'] = $info['productInfo']['attrInfo']['cost'] ?? 0;
        $cartInfo[] = $info;

        /** @var StoreCartServices $storeCartServices */
        $storeCartServices = app()->make(StoreCartServices::class);
        //整合购物车商品数据
        [$cartInfo, $valid, $invalid] = $storeCartServices->handleCartList($uid, $cartInfo, [], -1);

        $type = array_unique(array_column($cartInfo, 'type'));
        $price_type = array_unique(array_column($cartInfo, 'price_type'));
        $product_type = array_unique(array_column($cartInfo, 'product_type'));
        $activity_id = array_unique(array_column($cartInfo, 'activity_id'));
        $deduction = ['price_type' => $price_type[0] ?? 0, 'product_type' => $product_type[0] ?? 0, 'type' => $type[0] ?? 0, 'activity_id' => $activity_id[0] ?? 0];
        $promotions = $giveCoupon = $giveCartList = $useCoupon = $giveProduct = [];
        $giveIntegral = $couponPrice = $firstOrderPrice = 0;
        //计算优惠
        $data = $storeCartServices->computedProductPromotion($uid, $valid, 0, true);
        extract($data);

        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $sumPrice = $computedServices->getOrderSumPrice($valid, 'sum_price');//获取订单原总金额
        $totalPrice = $computedServices->getOrderSumPrice($valid, 'truePrice');//获取订单svip、用户等级优惠之后总金额
        $vipPrice = $computedServices->getOrderSumPrice($valid, 'vip_truePrice');//获取订单会员优惠金额

        $coupon = $useCoupon;

        $cartList = array_merge($valid, $giveCartList);
        $promotionsPrice = 0;
        if ($cartList) {
            foreach ($cartList as $key => $cart) {
                if (isset($cart['promotions_true_price']) && isset($cart['price_type']) && $cart['price_type'] == 'promotions') {
                    $promotionsPrice = bcadd((string)$promotionsPrice, (string)bcmul((string)$cart['promotions_true_price'], (string)$cart['cart_num'], 2), 2);
                }
            }
        }
        $deduction['promotions_price'] = (float)$promotionsPrice;
        $deduction['coupon_price'] = (float)$couponPrice;
        $deduction['first_order_price'] = (float)$firstOrderPrice;
        $deduction['sum_price'] = (float)$sumPrice;
        $deduction['vip_price'] = (float)$vipPrice;

        $payPrice = (float)$totalPrice;
        if ($couponPrice < $payPrice) {//优惠券金额
            $payPrice = bcsub((string)$payPrice, (string)$couponPrice, 2);
        } else {
            $payPrice = 0;
        }
        if ($firstOrderPrice < $payPrice) {//首单优惠金额
            $payPrice = bcsub((string)$payPrice, (string)$firstOrderPrice, 2);
        } else {
            $payPrice = 0;
        }
        $deduction['pay_price'] = (float)$payPrice;
        if ($promotions) {//优惠活动
            $promotionsList = $promotions;
            unset($promotions);
            foreach ($promotionsList as $key => $item) {
                $promotions[] = [
                    'id' => $item['id'],
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'name' => $item['name'],
                    'promotions_type' => $item['promotions_type'],
                    'threshold_type' => $item['threshold_type'],
                    'threshold' => $item['threshold'],
                    'discount_type' => $item['discount_type'],
                    'discount' => $item['discount'],
                    'desc' => $item['desc'],
                    'start_time' => $item['start_time'] ? date('Y-m-d', $item['start_time']) : '',
                    'stop_time' => $item['stop_time'] ? date('Y-m-d', $item['stop_time']) : '',
                    'giveProducts' => $item['giveProducts'] ?? [],
                    'giveCoupon' => $item['giveCoupon'] ?? []
                ];
            }
        }
        return compact('promotions', 'coupon', 'deduction');
    }

    /**
     * 同步库存
     * @param array $items
     * @return void
     */
    public function syncStock(array $items)
    {
        return $this->transaction(function () use ($items) {
            $goods = $saveData = [];
            // 同步规格value库存
            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            $list = $storeProductAttrValueServices->getColumn(['bar_code' => array_column($items, 'bar_code')], 'id, product_id, bar_code, stock', 'bar_code');

            foreach ($items as $item) {
                $value = $list[$item['bar_code']] ?? [];
                if (!$value) continue;
                if (!isset($goods[$value['product_id']])) $goods[$value['product_id']] = 1;
                $saveData[] = ['id' => $value['id'], 'stock' => $item['qty']];
            }

            if ($saveData) {
                $storeProductAttrValueServices->saveAll($saveData);
            }

            if ($goods) {
                ProductStockJob::dispatch('distribute', [$goods]);
            }
            return true;
        });
    }

    /**
     * 计算商品库存
     * @param int $id
     * @return void
     */
    public function calcStockByAttrValue(int $id)
    {
        return $this->transaction(function () use ($id) {
            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

            /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
            $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);

            $stock = $storeProductAttrValueServices->sum(['product_id' => $id], 'stock');
            $res = $this->dao->update($id, ['stock' => $stock]);
            if (!$res) throw new AdminException('修改失败');

            $attrValue = $storeProductAttrValueServices->getColumn(['product_id' => $id], 'stock', 'bar_code');
            $result = $storeProductAttrResultServices->getResult(['product_id' => $id, 'type' => 0]);
            if (!$attrValue || !$result) return;

            foreach ($result['value'] as $k => $value) {
                if (isset($attrValue[$value['bar_code']])) {
                    $result['value'][$k]['stock'] = $attrValue[$value['bar_code']];
                }
            }
            $storeProductAttrResultServices->del($id, 0);
            $storeProductAttrResultServices->setResult($result, $id, 0);
            return true;
        });
    }

    /**
     * 修改警戒库存配置,批量修改商品库存警戒
     * @return true
     * @throws \think\db\exception\DbException
     * User: liusl
     * DateTime: 2024/9/13 17:42
     */
    public function productStockTips()
    {
        $count = $this->dao->count(['is_del' => 0]);
        $maxLimit = 50;
        if ($count > $maxLimit) {
            $pages = ceil($count / $maxLimit);
            for ($i = 1; $i <= $pages; $i++) {
                ProductLogJob::dispatch('productStockTips', [$i, $maxLimit]);
            }
        } else {
            $this->runProductStockTips();
        }
        return true;
    }

    /**
     * 修改警戒库存配置,批量修改商品库存警戒
     * @param int $page
     * @param int $limit
     * @return true
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/9/13 17:43
     */
    public function runProductStockTips(int $page = 0, int $limit = 0)
    {
        $list = $this->dao->getSearchList(['is_del' => 0], $page, $limit, ['stock', 'id', 'is_police', 'is_sold']);
        if (!$list) {
            return true;
        }
        $store_stock = sys_config('store_stock') ?? 0;//库存预警界限
        foreach ($list as $item) {
            /** @var StoreProductAttrValueServices $storeValueService */
            $storeValueService = app()->make(StoreProductAttrValueServices::class);
            $count = $storeValueService->getPolice([
                ['type', '=', 0],
                ['stock', '<=', $store_stock],
                ['product_id', '=', $item['id']]
            ]);
            $is_sold = $storeValueService->get(['type' => 0, 'product_id' => $item['id'], 'stock' => 0]) ? 1 : 0;
            if ($store_stock >= $item['stock'] || $count) {
                $is_police = 1;
            } else {
                $is_police = 0;
            }
            $this->dao->update($item['id'], ['is_police' => $is_police, 'is_sold' => $is_sold]);
        }
        return true;
    }

    /**
     * 获取分佣/会员价
     * @param $id
     * @param $type 1分佣,2会员价
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/10/28 15:04
     */
    public function otherInfo(int $id, int $type)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

        $info = $this->dao->get($id, ['id', 'is_brokerage', 'brokerage_type', 'is_sub', 'is_vip', 'level_type']);
        if (!$info) throw new AdminException('商品不存在');
        $data = [];
        $info['level_type'] = $info['level_type'] ?: 1;
        $data['storeInfo'] = $info->toArray();
        $data['level_list'] = [];
        if ($type == 1) {
            $data['store_brokerage_ratio'] = bcmul(sys_config('store_brokerage_ratio', 0), 0.01, 2);
            $data['store_brokerage_two'] = bcmul(sys_config('store_brokerage_two', 0), 0.01, 2);
        } else {
            /** @var SystemUserLevelServices $systemUserLevelServices */
            $systemUserLevelServices = app()->make(SystemUserLevelServices::class);
            $level_list = $systemUserLevelServices->getWhereLevelList([], 'id,name,grade,discount');
            foreach ($level_list as &$item) {
                $item['discount'] = bcmul($item['discount'], '0.01', 2);
            }
            $data['level_list'] = $level_list;
        }

        $attrValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0]);
        foreach ($attrValue as &$val) {
            $val['level_price'] = $val['level_price'] ? json_decode($val['level_price'], true) : [];
            if ($data['storeInfo']['level_type'] == 1) {
                continue;
            }
            // 获取已存在的等级 id 数组
            $existingIds = array_column($val['level_price'], 'id');

            // 补全缺失的等级值
            foreach ($data['level_list'] as $level) {
                if (!in_array($level['id'], $existingIds)) {
                    // 计算缺失的 price 值
                    $price = bcmul($val['price'], $level['discount'], 2);
                    $val['level_price'][] = ['id' => $level['id'], 'price' => $price];
                }
            }
        }
        $data['attrValue'] = $attrValue;


        return $data;
    }

    /**
     * 保存分佣/会员价
     * @param int $id
     * @param int $type
     * @param array $data
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/10/28 15:36
     */
    public function otherUpdate(int $id, int $type, array $data)
    {
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('商品不存在');
        }
        $proData = [];
        if ($type == 1) {
            $proData['is_sub'] = $data['is_sub'];
            $proData['is_brokerage'] = $data['is_brokerage'];
        } else {
            $proData['is_vip'] = $data['is_vip'];
            $proData['level_type'] = $data['level_type'];
            $proData['vip_price'] = min(array_column($data['attr_value'], 'vip_price')) ?: 0;
        }
        foreach ($data['attr_value'] as $item) {
            $attrData = [];
            if ($type == 1) {
                $attrData['brokerage'] = $data['is_sub'] ? $item['brokerage'] : 0;
                $attrData['brokerage_two'] = $data['is_sub'] ? $item['brokerage_two'] : 0;
            } else {
                $attrData['vip_price'] = $data['is_vip'] ? $item['vip_price'] : 0;
                $attrData['level_price'] = isset($item['level_price']) && $item['level_price'] ? json_encode($item['level_price']) : '';
            }
            $storeProductAttrValueServices->update(['id' => $item['id']], $attrData);
        }
        $this->dao->update($id, $proData);
        return true;
    }

    /**
     * 商品导入
     * @param $file
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/21 上午10:28
     */
    public function productImport($file)
    {
        $all = $success = $fail = 0;
        $file = public_path() . substr($file, 1);
        $importData = app()->make(FileService::class)->readImportExcel($file, 1, 'product');
        if (!$importData) {
            throw new AdminException('导入数据为空');
        }
        $cateOne = array_column($importData, 'cate_name_one');
        $cateTwo = array_column($importData, 'cate_name_two');
        $productCateServices = app()->make(StoreProductCategoryServices::class);
        $cateOneArr = $productCateServices->getColumn([['cate_name', 'in', $cateOne]], 'id', 'cate_name');
        $cateTwoArr = $productCateServices->getColumn([['cate_name', 'in', $cateTwo]], 'id', 'cate_name');
        $productData = $issetProductArr = [];
        $virtualType = ['普通商品' => 0, '卡密/网盘' => 1, '优惠券' => 2, '虚拟商品' => 3, '次卡商品' => 4];
        $productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $barCodeArr = array_unique($productAttrValueServices->getColumn(['type' => 0], 'code', 'id'));
        $barCodeNumberArr = array_unique($productAttrValueServices->getColumn(['type' => 0], 'bar_code', 'id'));
        foreach ($importData as $sku) {
            if ($sku['id'] == null) continue;
            if (!isset($productData[$sku['id']])) {
                $productData[$sku['id']]['product_type'] = $virtualType[$sku['product_type']];
                $productData[$sku['id']]['supplier_id'] = 0;
                $productData[$sku['id']]['cate_id'] = [$cateOneArr[$sku['cate_name_one']] ?? '', $cateTwoArr[$sku['cate_name_two']] ?? ''];
                $productData[$sku['id']]['store_name'] = $sku['store_name'];
                $productData[$sku['id']]['store_info'] = $sku['store_info'];
                $productData[$sku['id']]['keyword'] = $sku['keyword'];
                $productData[$sku['id']]['unit_name'] = $sku['unit_name'];
                $productData[$sku['id']]['recommend_image'] = '';
                $productData[$sku['id']]['slider_image'] = explode(';', $sku['slider_image']);
                $productData[$sku['id']]['is_sub'] = [];
                $productData[$sku['id']]['sort'] = 0;
                $productData[$sku['id']]['ficti'] = $sku['ficti'];
                $productData[$sku['id']]['give_integral'] = $sku['give_integral'];
                $productData[$sku['id']]['is_show'] = 0;
                $productData[$sku['id']]['is_hot'] = 0;
                $productData[$sku['id']]['is_benefit'] = 0;
                $productData[$sku['id']]['is_best'] = 0;
                $productData[$sku['id']]['is_new'] = 0;
                $productData[$sku['id']]['mer_use'] = 0;
                $productData[$sku['id']]['is_postage'] = 0;
                $productData[$sku['id']]['is_good'] = 0;
                $productData[$sku['id']]['description'] = $sku['description'];
                $productData[$sku['id']]['spec_type'] = $sku['spec_type'] == '多规格' ? 1 : 0;
                $productData[$sku['id']]['video_open'] = $sku['video_link'] != '' ? 1 : 0;
                $productData[$sku['id']]['video_link'] = $sku['video_link'];
                //items
                //attrs
                //attr
                $productData[$sku['id']]['recommend'] = [];
                $productData[$sku['id']]['activity'] = ['默认', '秒杀', '砍价', '拼团'];
                $productData[$sku['id']]['coupon_ids'] = [];
                $productData[$sku['id']]['label_id'] = [];
                $productData[$sku['id']]['command_word'] = $sku['command_word'];
                $productData[$sku['id']]['tao_words'] = '';
                $productData[$sku['id']]['is_copy'] = 0;
                $productData[$sku['id']]['delivery_type'] = [1];
                $productData[$sku['id']]['freight'] = 1;
                $productData[$sku['id']]['postage'] = 0;
                $productData[$sku['id']]['temp_id'] = '';
                $productData[$sku['id']]['recommend_list'] = [];
                $productData[$sku['id']]['brand_id'] = [];
                $productData[$sku['id']]['soure_link'] = '';
                $productData[$sku['id']]['bar_code'] = '';
                $productData[$sku['id']]['code'] = '';
                $productData[$sku['id']]['is_support_refund'] = 1;
                $productData[$sku['id']]['is_presale_product'] = 0;
                $productData[$sku['id']]['presale_time'] = [];
                $productData[$sku['id']]['presale_day'] = 0;
                $productData[$sku['id']]['is_vip_product'] = 0;
                $productData[$sku['id']]['auto_on_time'] = 0;
                $productData[$sku['id']]['auto_off_time'] = 0;
                $productData[$sku['id']]['custom_form'] = [];
                $productData[$sku['id']]['system_form_id'] = 0;
                $productData[$sku['id']]['store_label_id'] = [];
                $productData[$sku['id']]['ensure_id'] = 0;
                $productData[$sku['id']]['specs'] = [];
                $productData[$sku['id']]['specs_id'] = 0;
                $productData[$sku['id']]['is_limit'] = 0;
                $productData[$sku['id']]['limit_type'] = 0;
                $productData[$sku['id']]['limit_num'] = 0;
                $productData[$sku['id']]['share_content'] = '';
                $productData[$sku['id']]['min_qty'] = $sku['min_qty'];
                $productData[$sku['id']]['presale_status'] = 0;
            }
            $detail = [];
            foreach (explode(';', $sku['sku_value']) as $pair) {
                list($key, $value) = explode('=', $pair);
                $detail[$key] = $value;
            }
            if ($sku['code'] != '' && in_array($sku['code'], $barCodeArr)) {
                $issetProductArr[] = $sku['id'];
            }
            if ($sku['bar_code'] != '' && in_array($sku['bar_code'], $barCodeNumberArr)) {
                $issetProductArr[] = $sku['id'];
            }
            if ($sku['spec_type'] == '多规格') {
                $productData[$sku['id']]['attrs'][] = [
                    'attr_arr' => explode(',', $sku['sku_name']),
                    'detail' => $detail,
                    'price' => $sku['price'],
                    'pic' => $sku['pic'],
                    'ot_price' => $sku['ot_price'],
                    'cost' => $sku['cost'],
                    'stock' => $sku['stock'],
                    'is_show' => 1,
                    'is_default_select' => 0,
                    'is_virtual' => 0,
                    'brokerage' => 0,
                    'brokerage_two' => 0,
                    'vip_price' => 0,
                    'vip_proportion' => 0,
                    'unique' => '',
                    'weight' => $sku['weight'],
                    'volume' => $sku['volume'],
                    'code' => $sku['code'],
                    'bar_code' => $sku['bar_code'],
                ];
            } else {
                $productData[$sku['id']]['attrs'][] = [];
                $productData[$sku['id']]['attr'] = [
                    'attr_arr' => explode(',', $sku['sku_name']),
                    'detail' => $detail,
                    'price' => $sku['price'],
                    'pic' => $sku['pic'],
                    'ot_price' => $sku['ot_price'],
                    'cost' => $sku['cost'],
                    'stock' => $sku['stock'],
                    'is_show' => 1,
                    'is_default_select' => 0,
                    'is_virtual' => 0,
                    'brokerage' => 0,
                    'brokerage_two' => 0,
                    'vip_price' => 0,
                    'vip_proportion' => 0,
                    'unique' => '',
                    'weight' => $sku['weight'],
                    'volume' => $sku['volume'],
                    'code' => $sku['code'],
                    'bar_code' => $sku['bar_code'],
                ];
            }
            $items = [];
            $pairs = explode(';', $sku['sku_type_value']);
            foreach ($pairs as $pair) {
                // 将每个部分按等号分割成 key 和 value
                list($key, $values) = explode('=', $pair);
                // 将 value 部分按逗号分割为数组
                $detailArray = explode(',', $values);
                $detail = [];
                foreach ($detailArray as &$det) {
                    $detail[] = [
                        'value' => $det,
                        'pic' => '',
                    ];
                }
                // 重新构建原始数组的结构
                $items[] = [
                    'value' => $key,
                    'detail' => $detail
                ];
            }
            $productData[$sku['id']]['items'] = $items;
        }
        $all = count($productData);
        foreach (array_unique($issetProductArr) as $issetProduct) {
            if (isset($productData[$issetProduct])) {
                unset($productData[$issetProduct]);
            }
        }
        $success = count($productData);
        $jump = $all - $success;
        foreach ($productData as $info) {
            $this->saveData(0, $info);
        }
        $fail = 0;
        return compact('all', 'success', 'jump', 'fail');
    }

    /**
     * 预售结束后操作
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/1/4 下午3:41
     */
    public function autoPresaleProduct()
    {
        $where = [
            'time_type' => 3,
            'presale_status' => 1
        ];
        $data = $this->dao->getPresaleList($where, 0, 0);

        $list = $data['list'];
        if (!$list) return true;
        $ids = array_column($list, 'id');
        $this->dao->update(['id' => $ids], ['is_show' => 1, 'is_presale_product' => 0]);
        return true;
    }
}
