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
namespace app\controller\api\admin\product;

use app\Request;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\product\StoreProductBatchProcessServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use think\annotation\Inject;

/**
 * 商品类
 * Class StoreProduct
 * @package app\api\controller\store
 */
class StoreProduct
{
    /**
     * 商品services
     * @var StoreProductServices
     */
    #[Inject]
    protected StoreProductServices $services;

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request, StoreProductCategoryServices $services)
    {
        $where = $request->getMore([
            [['sid', 'd'], 0],
            [['cid', 'd'], 0],
            [['tid', 'd'], 0],
            ['keyword', '', '', 'store_name'],
            ['priceOrder', ''],
            ['salesOrder', ''],
            ['defaultOrder', ''],
            [['news', 'd'], 0, '', 'is_new'],
            [['type', ''], '', '', 'status'],
            ['ids', ''],
            [['selectId', 'd'], ''],
            ['cate_id', ''],
            ['productId', ''],
            ['brand_id', ''],
            ['promotions_id', 0],
            ['promotions_type', 0],
            ['store_label_id', 0]
        ]);
        if ($where['selectId'] && (!$where['sid'] || !$where['cid'])) {
            $level = $services->value(['id' => (int)$where['selectId']], 'level') ?? 0;
            $levelArr = $services->cateField;
            $where[$levelArr[$level] ?? 'cid'] = $where['selectId'];
        }
        $where['ids'] = stringToIntArray($where['ids']);
        if (!$where['ids']) {
            unset($where['ids']);
        }
        $where['brand_id'] = stringToIntArray($where['brand_id']);
        $where['store_label_id'] = stringToIntArray($where['store_label_id']);

        $cateId = $where['cate_id'];
        if ($cateId) {
            $cateId = is_string($cateId) ? stringToIntArray($where['cate_id']) : $cateId;
            $cateId = array_merge($cateId, $services->getColumn(['pid' => $cateId], 'id'));
            $cateId = array_unique(array_diff($cateId, [0]));
        }
        $where['cate_id'] = $cateId;

        $type = 'mid';
        $field = ['image', 'recommend_image'];
        if ($where['store_name']) {//搜索
            $field = ['image'];
            $where['pid'] = 0;
        }
        $list = $this->services->getGoodsList($where, (int)$request->uid(), (int)$where['promotions_type']);
        return app('json')->successful(get_thumb_water($list, $type, $field));
    }


    public function adminList(Request $request)
    {
        $where = $request->getMore([
            ['store_name', ''],
            ['type', 1, '', 'status'],
        ]);
        $data = $this->services->getList($where,true);
        return app('json')->successful($data);
    }

    /**
     * 上下架
     * @param $is_show
     * @param $id
     * @return mixed
     * User: liusl
     * DateTime: 2024/1/23 17:43
     */
    public function setShow(Request $request)
    {
        [$id, $is_show] = $request->getMore([
            ['id', 0],
            ['is_show', 0],
        ], true);
        if (!$id) return app('json')->fail('请选择商品');
        $ids = is_array($id) ? $id : [$id];
        $this->services->setShow($ids, $is_show);
        return app('json')->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 商品标签树
     * @return mixed
     * User: liusl
     * DateTime: 2024/1/23 19:21
     */
    public function labelTreeList(StoreProductLabelServices $services)
    {
        return app('json')->success($services->getProductLabelTreeList());
    }

    /**
     * 修改规格属性
     * @param Request $request
     * @param $id
     * @param StoreProductAttrValueServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/1/24 16:35
     */
    public function updateAttrs(Request $request, $id, StoreProductAttrValueServices $services)
    {
        [$attr_value] = $request->postMore([
            ['attr_value', []],
        ], true);

        if (!$id) {
            return app('json')->fail('请选择商品');
        }
        if (!$attr_value) {
            return app('json')->fail('请填写属性值');
        }

        //判断规格的属性值是否存在
        $requiredKeys = ['unique', 'price', 'stock', 'cost', 'ot_price'];
        foreach ($attr_value as $attr) {
            $missingKeys = array_diff($requiredKeys, array_keys($attr));
            if (!empty($missingKeys)) {
                return app('json')->fail('请重新修改规格库存');
            }
        }

        $services->updateAttrs($id, $attr_value);
        return app('json')->success('修改成功');
    }

    /**
     * 批量/修改标签/分类
     * @param StoreProductBatchProcessServices $batchProcessServices
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/1/24 16:36
     */
    public function batchProcess(Request $request, StoreProductBatchProcessServices $batchProcessServices)
    {
        [$type, $ids, $data] = $request->postMore([
            ['type', 1],
            ['ids', ''],
            ['data', []]
        ], true);
        if (!$ids) return app('json')->fail('请选择处理商品');
        if (!$data) {
            return app('json')->fail('请选择处理数据');
        }
        if(is_array($ids)){
            //批量操作
            $batchProcessServices->batchProcess((int)$type, $ids, $data);
        }else{
            switch ($type){
                case 1://修改分类
                    $batchProcessServices->setPrdouctCate((int)$ids, $data);
                    break;
                case 2://修改标签
                    $batchProcessServices->runBatch([$ids], $data);
                    break;
               default:
                    return app('json')->fail('请选择处理类型');
            }
        }
        return app('json')->success('修改成功');
    }

    /**
     * 根据 id 获取规格
     * @param $id
     * @param StoreProductAttrValueServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/1/25 11:18
     */
    public function getAttr($id,StoreProductAttrValueServices $services)
    {
        if(!$id) return app('json')->fail('请选择商品');
        $list = $services->getProductAttrValue(['product_id' => $id, 'type' => 0]);
        return app('json')->success($list ? : []);
    }
}
