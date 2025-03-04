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
namespace app\controller\admin\v1\system;

use app\controller\admin\AuthController;
use app\services\system\SystemRoleServices;
use app\services\system\SystemMenusServices;
use crmeb\services\CacheService;
use think\annotation\Inject;

/**
 * Class SystemRole
 * @package app\controller\admin\v1\setting
 */
class SystemRole extends AuthController
{

    /**
     * @var SystemRoleServices
     */
    #[Inject]
    protected SystemRoleServices $services;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['role_name', ''],
        ]);
        $where['type'] = 0;
        $where['level'] = $this->adminInfo['level'] + 1;
        return $this->success($this->services->getRoleList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create(SystemMenusServices $services)
    {
        $menus = $services->getmenus($this->adminInfo['level'] == 0 ? [] : $this->adminInfo['roles'], 1, 0);
        return $this->success(compact('menus'));
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            'role_name',
            ['status', 0],
            ['checked_menus', [], '', 'rules']
        ]);
        if (!$data['role_name']) return $this->fail('请输入身份名称');
        if (!is_array($data['rules']) || !count($data['rules']))
            return $this->fail('请选择最少一个权限');
        $data['rules'] = implode(',', $data['rules']);
        if ($id) {
            if (!$this->services->update($id, $data)) return $this->fail('修改失败!');
            CacheService::redisHandler('system_menus')->clear();
            return $this->success('修改成功!');
        } else {
            $data['level'] = $this->adminInfo['level'] + 1;
            if (!$this->services->save($data)) return $this->fail('添加身份失败!');
            CacheService::redisHandler('system_menus')->clear();
            return $this->success('添加身份成功!');
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit(SystemMenusServices $services, $id)
    {
        $role = $this->services->get($id);
        if (!$role) {
            return $this->fail('修改的角色不存在');
        }
        $menus = $services->getMenus($this->adminInfo['level'] == 0 ? [] : $this->adminInfo['roles'], 1, 0);
        return $this->success(['role' => $role->toArray(), 'menus' => $menus]);
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else {
            CacheService::redisHandler('system_menus')->clear();
            return $this->success('删除成功!');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $role = $this->services->get($id);
        if (!$role) {
            return $this->fail('没有查到此身份');
        }
        $role->status = $status;
        if ($role->save()) {
            CacheService::redisHandler('system_menus')->clear();
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }
}
