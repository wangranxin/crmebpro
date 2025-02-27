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

namespace app\dao\system\admin;

use app\dao\BaseDao;
use app\model\system\admin\SystemAdmin;

/**
 * Class SystemAdminDao
 * @package app\dao\system\admin
 */
class SystemAdminDao extends BaseDao
{
    protected function setModel(): string
    {
        return SystemAdmin::class;
    }

    /**
	* 获取列表
	* @param array $where
	* @param int $page
	* @param int $limit
	* @param string $field
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function getList(array $where, int $page = 0, int $limit = 0, string $field = '*')
    {
        return $this->search($where)->field($field)->when($page && $limit, function ($query) use($page, $limit) {
			$query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 用管理员名查找管理员信息
     * @param string $account
     * @param int $adminType
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function accountByAdmin(string $account, int $adminType)
    {
        return $this->search(['account' => $account, 'is_del' => 0, 'status' => 1, 'admin_type' => $adminType])->find();
    }

    /**
     * 用电话查找管理员信息
     * @param string $phone
     * @param int $adminType
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function phoneByAdmin(string $phone, int $adminType = 1)
    {
        return $this->search(['phone' => $phone, 'is_del' => 0, 'status' => 1, 'admin_type' => $adminType])->find();
    }

    /**
	 * 当前账号是否可用
	 * @param string $account
	 * @param int $id
	 * @param int $admin_type
	 * @return int
	 * @throws \think\db\exception\DbException
	 */
    public function isAccountUsable(string $account, int $id, int $admin_type = 1)
    {
        return $this->search(['account' => $account, 'is_del' => 0])->where('admin_type', $admin_type)->where('id', '<>', $id)->count();
    }

    /**
     * 获取adminid
     * @param int $level
     * @return array
     */
    public function getAdminIds(int $level)
    {
        return $this->getModel()->where('level', '>=', $level)->column('id', 'id');
    }

    /**
     * 获取低于等级的管理员名称和id
     * @param string $field
     * @param int $level
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrdAdmin(string $field = 'real_name,id', int $level = 0)
    {
        return $this->getModel()->where('level', '>=', $level)->field($field)->select()->toArray();
    }
}
