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

namespace app\services\product\sku;


use app\dao\product\sku\StoreProductRuleDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use think\annotation\Inject;

/**
 * Class StoreProductRuleService
 * @package app\services\product\sku
 * @mixin StoreProductRuleDao
 */
class StoreProductRuleServices extends BaseServices
{
    /**
     * @var StoreProductRuleDao
     */
    #[Inject]
    protected StoreProductRuleDao $dao;

	/**
 	* 获取规格模板
	* @param int $type
	* @param int $relation_id
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
	public function getRule(int $type = 0, int $relation_id = 0 )
	{
		$list = $this->dao->getList(['type' => $type, 'relation_id' => $relation_id]);

		foreach ($list as &$item) {
            $attr_name = $attr_value = [];
            if ($item['rule_value']) {
				$item['rule_value'] = $specs = json_decode($item['rule_value'], true);
                foreach ($item['rule_value'] as $ruleKey => $ruleItem) {
                    foreach ($ruleItem['detail'] as $valueKey => $valueItem) {
                        if (is_string($valueItem)) {
                            $item['rule_value'][$ruleKey]['detail'][$valueKey] = ['value' => $valueItem, 'pic' => ''];
                            $item['rule_value'][$ruleKey]['add_pic'] = 0;
                        }
                    }
                }
                if ($specs) {
                    foreach ($specs as $key => $value) {
                        $attr_name[] = $value['value'];
                        $attr_value[] = implode(',', $value['detail']);
                    }
                } else {
                    $attr_name[] = '';
                    $attr_value[] = '';
                }
                $item['attr_name'] = implode(',', $attr_name);
                $item['attr_value'] = $attr_value;
            }
        }
        return $list;
	}

    /**
     * 获取商品规格列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        foreach ($list as &$item) {
            $attr_name = $attr_value = [];
            if ($item['rule_value']) {
                $specs = json_decode($item['rule_value'], true);
                if ($specs) {
                    foreach ($specs as $key => $value) {
                        $attr_name[] = $value['value'];
                        $attr_value[] = implode(',', $value['detail']);
                    }
                } else {
                    $attr_name[] = '';
                    $attr_value[] = '';
                }
                $item['attr_name'] = implode(',', $attr_name);
                $item['attr_value'] = $attr_value;
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
	 * 保存数据
	 * @param int $id
	 * @param array $data
	 * @param int $type
	 * @param int $relation_id
	 * @return void
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
    public function save(int $id, array $data, int $type = 0, int $relation_id = 0)
    {
		$data['type'] = $type;
		$data['relation_id'] = $relation_id;
        $data['rule_value'] = json_encode($data['spec']);
        unset($data['spec']);
		$rule = $this->dao->getOne(['rule_name' => $data['rule_name'], 'type' => $type, 'relation_id' => $relation_id]);
        if ($id) {
			if ($rule && $rule['id'] != $id) {
				throw new AdminException('分类名称已存在');
			}
            $res = $this->dao->update($id, $data);
        } else {
			if ($rule) {
				throw new AdminException('分类名称已存在');
			}
            $res = $this->dao->save($data);
        }
        if (!$res) throw new AdminException('保存失败');
    }

    /**
     * 获取一条数据
     * @param int $id
     * @return array
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id);
        $info['spec'] = json_decode($info['rule_value'], true);
        return compact('info');
    }

    /**
     * 删除数据
     * @param string $ids
     */
    public function del(string $ids)
    {
        if ($ids == '') throw new AdminException('请至少选择一条数据');
        $this->dao->del($ids);
    }

    /**
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductRuleList(array $where, $field = "*")
    {
        return $this->dao->getProductRuleList($where, $field);
    }

}
