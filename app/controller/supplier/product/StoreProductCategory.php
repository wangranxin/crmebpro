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
namespace app\controller\supplier\product;

use app\controller\supplier\AuthController;
use app\services\product\category\StoreProductCategoryServices;
use think\annotation\Inject;use think\facade\App;

/**
 * 商品分类控制器
 * Class StoreProductCategory
 */
class StoreProductCategory extends AuthController
{
    /**
     * @var StoreProductCategoryServices
     */
	#[Inject]
    protected StoreProductCategoryServices $services;

    /**
     * 分类列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_show', ''],
            ['pid', 0],
            ['cate_name', ''],
        ]);
//		$where['type'] = 2;
//		$where['relation_id'] = $this->supplierId;
		$where['pid'] = (int)$where['pid'];
        $data = $this->services->getList($where);
        return $this->success($data);
    }

    /**
     * 商品分类搜索
     * @return mixed
     */
    public function tree_list($type)
    {
        $list = $this->services->getTierList(1, $type);
        return $this->success($list);
    }

    /**
     * 获取分类cascader格式数据
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascader_list($type = 1)
    {
        return $this->success($this->services->cascaderList());
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') return $this->fail('缺少参数');
        $this->services->setShow($id, $is_show);
        return $this->success($is_show == 1 ? '显示成功' : '隐藏成功');
    }

    /**
     * 生成新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm(1));
    }

    /**
     * 保存新增分类
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['big_pic', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['cate_name']) {
            return $this->fail('请输入分类名称');
        }
		$data['type'] = 2;
		$data['relation_id'] = $this->supplierId;
        $this->services->createData($data);
        return $this->success('添加分类成功!');
    }

    /**
     * 生成更新表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit($id)
    {
        return $this->success($this->services->editForm((int)$id, 1));
    }

    /**
     * 更新分类
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['big_pic', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['cate_name']) {
            return $this->fail('请输入分类名称');
        }
        $this->services->editData($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除分类
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->services->del((int)$id);
        return $this->success('删除成功!');
    }
}
