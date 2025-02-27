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

namespace app\dao\system;

use app\dao\BaseDao;
use app\model\system\SystemUserApply;

/**
 * Class SystemRoleDao
 * @package app\dao\system\admin
 */
class SystemUserApplyDao extends BaseDao
{
	/**
	 * 设置模型名
	 * @return string
	 */
    protected function setModel(): string
    {
        return SystemUserApply::class;
    }


	/**
	 * 获取列表
	 * @param array $where
	 * @param string $field
	 * @param int $page
	 * @param int $limit
	 * @param array $typeWhere
	 * @return array
	 */
	public function getList(array $where, string $field = '*', array $with = [], int $page = 0, int $limit = 0)
	{
		return $this->search($where)->field($field)->when($with, function ($query) use ($with) {
			$query->with($with);
		})->when($page && $limit, function ($query) use ($page, $limit) {
			$query->page($page, $limit);
		})->order('id desc')->select()->toArray();
	}

}
