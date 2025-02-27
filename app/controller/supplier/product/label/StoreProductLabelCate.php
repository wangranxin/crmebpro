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
namespace app\controller\supplier\product\label;

use app\controller\supplier\AuthController;
use app\Request;
use app\services\product\label\StoreProductLabelCateServices;
use app\services\product\label\StoreProductLabelServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 商品标签组
 * Class StoreProductLabelCate
 * @package app\controller\supplier\product\label
 */
class StoreProductLabelCate extends AuthController
{
    /**
     * @var StoreProductLabelCateServices
     */
	#[Inject]
    protected StoreProductLabelCateServices $services;

    /**
     * 获取标签组列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $where = $request->postMore([
            ['name', '']
        ]);
		$where['type'] = 2;
        $where['relation_id'] = $this->supplierId;
        $where['group'] = 2;
        $where['product_label'] = 1;
        return $this->success($this->services->getProductLabelCateList($where));
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
     * 保存新增标签组
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0],
        ]);
        if (!trim($data['name'])) {
            return $this->fail('请输入标签组名称');
        }
        if ($this->services->getOne(['name' => $data['name'], 'group' => 2, 'type' => 2, 'relation_id' => $this->supplierId])) {
            return $this->fail('标签组已存在');
        }
        $data['type'] = 2;
		$data['relation_id'] = $this->supplierId;
        $data['group'] = 2;
        $data['add_time'] = time();
        $this->services->save($data);
        return $this->success('添加标签组成功!');
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
     * 更新标签组
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入标签组名称');
        }
        $cate = $this->services->getOne(['name' => $data['name'], 'group' => 2, 'type' => 2, 'relation_id' => $this->supplierId]);
        if ($cate && $cate['id'] != $id) {
            return $this->fail('标签组已存在');
        }
        $this->services->update($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除标签组
     * @param StoreProductLabelServices $labelServices
     * @param $id
     * @return mixed
     */
    public function delete(StoreProductLabelServices $labelServices, $id)
    {
        if (!$id || !$this->services->count(['id' => $id])) {
            return $this->fail('删除的数据不存在');
        }
        if ($labelServices->count(['label_cate' => $id])) {
            return $this->fail('标签组下有标签不能删除');
        }
        $this->services->delete((int)$id);
        return $this->success('删除成功!');
    }
}
