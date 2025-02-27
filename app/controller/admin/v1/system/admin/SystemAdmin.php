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
namespace app\controller\admin\v1\system\admin;

use app\controller\admin\AuthController;
use app\services\system\admin\LoginAuthServices;
use app\services\system\admin\SystemAdminServices;
use crmeb\services\CacheService;
use think\facade\Config;
use think\annotation\Inject;

/**
 * Class SystemAdmin
 * @package app\controller\admin\v1\setting
 */
class SystemAdmin extends AuthController
{

    /**
     * @var SystemAdminServices
     */
    #[Inject]
    protected SystemAdminServices $services;

    /**
     * 显示管理员资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', '', '', 'account_like'],
            ['roles', ''],
            ['is_del', 1],
            ['status', '']
        ]);
        $where['level'] = $this->adminInfo['level'] + 1;
        $where['admin_type'] = 1;
        return $this->success($this->services->getAdminList($where));
    }

    /**
     * 创建表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm($this->adminInfo['level'] + 1));
    }

    /**
     * 添加管理员信息
     * @param LoginAuthServices $loginAuthServices
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/7/31 10:06
     */
    public function save(LoginAuthServices $loginAuthServices)
    {
        $data = $this->request->postMore([
            ['account', ''],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['real_name', ''],
            ['phone', ''],
            ['roles', []],
            ['status', 0],
        ]);

        $this->validate($data, \app\validate\admin\setting\SystemAdminValidate::class);
        //验证密码
        $loginAuthServices->validatePassword((string)$data['pwd']);

        $data['level'] = $this->adminInfo['level'] + 1;
        $this->services->create($data);
        return $this->success('添加成功');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!$id) {
            return $this->fail('管理员信息读取失败');
        }

        return $this->success($this->services->updateForm($this->adminInfo['level'] + 1, (int)$id));
    }

    /**
     * 修改管理员信息
     * @param LoginAuthServices $loginAuthServices
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/7/31 10:08
     */
    public function update(LoginAuthServices $loginAuthServices, $id)
    {
        $data = $this->request->postMore([
            ['account', ''],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['real_name', ''],
            ['phone', ''],
            ['roles', []],
            ['status', 0],
        ]);
        if ($data['pwd']) {
            $loginAuthServices->validatePassword((string)$data['pwd']);
        }
        $this->validate($data, \app\validate\admin\setting\SystemAdminValidate::class, 'update');

        if ($this->services->save((int)$id, $data)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 删除管理员
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('删除失败，缺少参数');
        if ($this->services->update((int)$id, ['is_del' => 1, 'status' => 0]))
            return $this->success('删除成功！');
        else
            return $this->fail('删除失败');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        $this->services->update((int)$id, ['status' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 获取当前登陆管理员的信息
     * @return mixed
     */
    public function info()
    {
        return $this->success($this->adminInfo);
    }

    /**
     * 修改当前登陆admin信息
     * @return mixed
     */
    public function update_admin()
    {
        $data = $this->request->postMore([
            ['real_name', ''],
            ['head_pic', ''],
            ['pwd', ''],
            ['new_pwd', ''],
            ['conf_pwd', ''],
            ['phone', ''],
            ['code', '']
        ]);
        if ($this->services->updateAdmin($this->adminId, $data))
            return $this->success('修改成功');
        else
            return $this->fail('修改失败');
    }

    /**
     * 退出登陆
     * @return mixed
     */
    public function logout()
    {
        $key = trim(ltrim($this->request->header(Config::get('cookie.token_name')), 'Bearer'));
        CacheService::redisHandler()->delete(md5($key));
        return $this->success();
    }
}
