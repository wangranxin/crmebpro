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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\Request;
use app\services\work\WorkChannelCodeServices;
use FormBuilder\Exception\FormBuilderException;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use app\services\work\WorkChannelCategoryServices;

/**
 * 渠道二维码分类
 * Class ClientCate
 * @package app\controller\admin\v1\work
 */
class ChannelCate extends AuthController
{

    /**
     * @var WorkChannelCategoryServices
     */
    #[Inject]
    protected WorkChannelCategoryServices $services;

    /**
     * 获取列表
     * @return mixed
     */
    public function index()
    {
        return $this->success($this->services->getCateAll());
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * @param $id
     * @return mixed
     * @throws FormBuilderException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function edit($id)
    {
        return $this->success($this->services->updateForm((int)$id));
    }

    /**
     * 保存数据
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $data = $request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);

        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }
        if ($this->services->count(['nowName' => $data['name'], 'group' => WorkChannelCategoryServices::TYPE])) {
            return $this->fail('分类名称已存在');
        }

        $data['group'] = WorkChannelCategoryServices::TYPE;

        if ($this->services->save($data)) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    /**
     * 修改分类
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);


        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }

        if ($this->services->count(['notId' => $id, 'nowName' => $data['name'], 'group' => WorkChannelCategoryServices::TYPE])) {
            return $this->fail('分类名称已存在');
        }

        if ($this->services->update($id, $data)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * @param WorkChannelCodeServices $channelCodeServices
     * @param $id
     * @return mixed
     */
    public function delete(WorkChannelCodeServices $channelCodeServices, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        if ($channelCodeServices->count(['cate_id' => $id])) {
            return $this->success('分类下有渠道码不删除');
        }
        if ($this->services->delete($id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }
}

