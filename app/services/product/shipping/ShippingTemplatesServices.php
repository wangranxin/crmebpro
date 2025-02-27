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
declare (strict_types=1);

namespace app\services\product\shipping;

use app\services\BaseServices;
use app\dao\product\shipping\ShippingTemplatesDao;
use app\services\product\product\StoreProductServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use think\annotation\Inject;

/**
 * 运费模板
 * Class ShippingTemplatesServices
 * @package app\services\product\shipping
 * @mixin ShippingTemplatesDao
 */
class ShippingTemplatesServices extends BaseServices
{

	/**
	 * 计费类型
	 * @var string[]
	 */
	protected array $group = [
		1 => '按件数',
		2 => '按重量',
		3 => '按体积'
	];

    /**
     * @var ShippingTemplatesDao
     */
    #[Inject]
    protected ShippingTemplatesDao $dao;

    /**
     * 获取运费模板列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShippingList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getShippingList($where, $page, $limit);
		if ($data) {
			$groupName = $this->group;
			foreach ($data as &$item) {
				$item['type'] = $groupName[$item['group']] ?? '';
			}
		}
        $count = $this->dao->count($where);
        return compact('data', 'count');
    }

    /**
     * @param array $where
     * @param $field
     * @param string|null $key
     * @param int $exprie
     * @return bool|mixed|null
     */
    public function getShippingColumnCache(array $where, $field, string $key = null, int $exprie = 60)
    {
        return CacheService::redisHandler('apiShipping')->remember(md5('Shipping_column' . json_encode($where)), function () use ($where, $field, $key) {
            return $this->dao->getShippingColumn($where, $field, $key);
        }, $exprie);
    }

    /**
     * 获取需要修改的运费模板
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShipping(int $id)
    {
        $templates = $this->dao->get($id);
        if (!$templates) {
            throw new AdminException('修改的模板不存在');
        }
        /** @var ShippingTemplatesFreeServices $freeServices */
        $freeServices = app()->make(ShippingTemplatesFreeServices::class);
        /** @var ShippingTemplatesRegionServices $regionServices */
        $regionServices = app()->make(ShippingTemplatesRegionServices::class);
        /** @var ShippingTemplatesNoDeliveryServices $noDeliveryServices */
        $noDeliveryServices = app()->make(ShippingTemplatesNoDeliveryServices::class);
        $data['appointList'] = $freeServices->getFreeListV1($id);
        $data['templateList'] = $regionServices->getRegionListV1($id);
        $data['noDeliveryList'] = $noDeliveryServices->getNoDeliveryListV1($id);
        if (!isset($data['templateList'][0]['city_ids'])) {
            $data['templateList'][0] = ['city_ids' => [[0]], 'regionName' => '默认全国'];
        }
        $data['formData'] = [
            'name' => $templates->name,
            'type' => $templates->getData('group'),
            'appoint_check' => intval($templates->getData('appoint')),
            'no_delivery_check' => intval($templates->getData('no_delivery')),
            'sort' => intval($templates->getData('sort')),
        ];
        return $data;
    }

    /**
     * 保存或者修改运费模板
     * @param int $id
     * @param array $temp
     * @param array $data
     * @return mixed
     */
    public function save(int $id, array $temp, array $data)
    {

        /** @var ShippingTemplatesRegionServices $regionServices */
        $regionServices = app()->make(ShippingTemplatesRegionServices::class);
        $this->transaction(function () use ($regionServices, $data, $id, $temp) {

            if ($id) {
                $res = $this->dao->update($id, $temp);
            } else {
                $res = $this->dao->save($temp);
            }
            if (!$res) {
                throw new AdminException('保存失败');
            }
            if (!$id) $id = $res->id;

            //设置区域配送
            if (!$regionServices->saveRegionV1($data['region_info'], (int)$data['group'], (int)$id)) {
                throw new AdminException('指定区域邮费添加失败!');
            }
            //设置指定包邮
            if ($data['appoint']) {
                /** @var ShippingTemplatesFreeServices $freeServices */
                $freeServices = app()->make(ShippingTemplatesFreeServices::class);
                if (!$freeServices->saveFreeV1($data['appoint_info'], (int)$data['group'], (int)$id)) {
                    throw new AdminException('指定包邮添加失败!');
                }
            }
            //设置不送达
            if ($data['no_delivery']) {
                /** @var ShippingTemplatesNoDeliveryServices $noDeliveryServices */
                $noDeliveryServices = app()->make(ShippingTemplatesNoDeliveryServices::class);
                if (!$noDeliveryServices->saveNoDeliveryV1($data['no_delivery_info'], (int)$id)) {
                    throw new AdminException('指定不送达添加失败!');
                }
            }
        });
        return true;
    }

    /**
     * 删除运费模板
     * @param int $id
     */
    public function detete(int $id)
    {
        $this->dao->delete($id);
        /** @var ShippingTemplatesFreeServices $freeServices */
        $freeServices = app()->make(ShippingTemplatesFreeServices::class);
        /** @var ShippingTemplatesRegionServices $regionServices */
        $regionServices = app()->make(ShippingTemplatesRegionServices::class);
        /** @var ShippingTemplatesNoDeliveryServices $noDeliveryServices */
        $noDeliveryServices = app()->make(ShippingTemplatesNoDeliveryServices::class);
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $this->transaction(function () use ($id, $freeServices, $regionServices, $noDeliveryServices, $storeProductServices) {
            $freeServices->delete($id, 'temp_id');
            $regionServices->delete($id, 'temp_id');
            $noDeliveryServices->delete($id, 'temp_id');
            $storeProductServices->update(['temp_id' => $id], ['temp_id' => 1]);
        });
    }
}
