<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\services\product\specs;

use app\dao\other\CategoryDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品参数模版
 * Class StoreProductSpecsTemplateServices
 * @package app\services\product\specs
 * @mixin CategoryDao
 */
class StoreProductSpecsTemplateServices extends BaseServices
{

    /**
     * 在分类库中3
     */
    const GROUP = 3;

    /**
     * @var CategoryDao
     */
    #[Inject]
    protected CategoryDao $dao;

    /**
 	* 获取所有参数模版
	* @param array $where
	* @param int $type
	* @param int $relation_id
	* @return array
	* @throws DataNotFoundException
	* @throws DbException
	* @throws ModelNotFoundException
	*/
    public function getProductSpecsTemplateList(array $where, int $type = 0, int $relation_id = 0) : array
    {
        [$page, $limit] = $this->getPageValue();
        $where = array_merge($where, ['type' => $type, 'relation_id' => $relation_id, 'group' => 3]);
        $count = $this->dao->count($where);
        $list = $this->dao->getCateList($where, $page, $limit, ['*']);
        if ($list) {
            foreach ($list as &$item) {
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
        }
        return compact('list', 'count');
    }

	/**
	 * 获取所有商品参数
	 * @param int $type
	 * @param int $relation_id
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getAllSpecs(int $type = 0, int $relation_id = 0)
	{
		$where = [];
//		$where['relation_id'] = $relation_id;
//		$where['type'] = $type;
		$where['group'] = 3;
		return $this->dao->getCateList($where, 0, 0, ['id', 'name'], ['specs' => function($query) {
			$query->where('status', 1);
		}]);
	}


}
