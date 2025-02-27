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
namespace app\controller\supplier\product\ensure;

use app\controller\supplier\AuthController;
use app\Request;
use app\services\product\ensure\StoreProductEnsureServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 商品保障服务
 * Class StoreProductEnsure
 * @package app\controller\supplier\product\ensure
 */
class StoreProductEnsure extends AuthController
{
    /**
     * @var StoreProductEnsureServices
     */
	#[Inject]
    protected StoreProductEnsureServices $services;

    /**
     * 获取保障服务列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $where = $request->postMore([
            ['name', '']
        ]);
        $where['relation_id'] = $this->supplierId;
        $where['type'] = 2;
        return $this->success($this->services->getEnsureList($where));
    }

	/**
	 * 获取保障服务列表
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
    public function allEnsure()
    {
		return $this->success($this->services->getAllEnsure(2, (int)$this->supplierId));
    }


    /**
     * 生成新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存新增保障服务
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['image', ''],
            ['desc', ''],
            ['sort', 0],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入保障服务条款');
        }
        if (!$data['image']) return $this->fail('请上传图标');
        if ($this->services->getOne(['name' => $data['name']])) {
            return $this->fail('保障服务条款已存在');
        }
        $data['type'] = 2;
        $data['relation_id'] = $this->supplierId;
        $data['add_time'] = time();
        $this->services->save($data);
        return $this->success('添加保障服务成功!');
    }

    /**
     * 生成更新表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit($id)
    {
        return $this->success($this->services->editForm((int)$id));
    }

    /**
     * 更新保障服务
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['image', ''],
            ['desc', ''],
            ['sort', 0],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入保障服务条款');
        }
        if (!$data['image']) return $this->fail('请上传图标');
        $cate = $this->services->getOne(['name' => $data['name']]);
        if ($cate && $cate['id'] != $id) {
            return $this->fail('保障服务条款已存在');
        }
        $this->services->update($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 设置保障服务是否显示
     * @param $id
     * @param $is_show
     * @return mixed
     */
    public function set_show($id, $is_show)
    {
        ($is_show == '' || $id == '') && $this->fail('缺少参数');
        $res = $this->services->update((int)$id, ['status' => (int)$is_show]);
        if ($res) {
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }

    /**
     * 删除保障服务
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id || !$this->services->count(['id' => $id])) {
            return $this->fail('删除的数据不存在');
        }
        $this->services->delete((int)$id);
        return $this->success('删除成功!');
    }
}
