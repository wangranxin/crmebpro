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

namespace app\controller\admin\v1\message\service;

use app\Request;
use think\annotation\Inject;
use app\controller\admin\AuthController;
use app\services\message\service\StoreServiceSpeechcraftServices;
use app\validate\admin\service\StoreServiceSpeechcraftValidate;

/**
 * 话术空控制器
 * Class StoreServiceSpeechcraft
 * @package app\controller\admin\v1\application\wechat
 */
class StoreServiceSpeechcraft extends AuthController
{

    /**
     * @var StoreServiceSpeechcraftServices
     */
    #[Inject]
    protected StoreServiceSpeechcraftServices $services;

    /**
     * 显示资源列表
     *
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(Request $request)
    {
        $where = $request->getMore([
            ['title', ''],
            ['message', ''],
            [['cate_id', 'd'], ''],
        ]);
        $where['kefu_id'] = 0;
        return $this->success($this->services->getSpeechcraftList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        [$cate_id] = $this->request->getMore([
            ['cate_id', 0]
        ], true);
        return $this->success($this->services->createForm((int)$cate_id));
    }

    /**
     * 保存新建的资源
     *
     * @param \app\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = $request->postMore([
            ['title', ''],
            ['message', ''],
            [['cate_id', 'd'], 0],
            ['sort', 0],
        ]);

        $this->validate($data, StoreServiceSpeechcraftValidate::class);
        $data['add_time'] = time();
        $data['kefu_id'] = 0;
        if ($this->services->count(['message' => $data['message']])) {
            return $this->fail('话术不能重复添加');
        }
        if ($this->services->save($data)) {
            return $this->success('创建话术成功');
        } else {
            return $this->fail('创建话术失败');
        }
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('获取失败');
        }
        return $this->success($info);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        return $this->success($this->services->updateForm((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param \app\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->postMore([
            ['title', ''],
            ['message', ''],
            ['sort', 0],
            [['cate_id', 'd'], 0],
        ]);

        $this->validate($data, StoreServiceSpeechcraftValidate::class);
        $message = $this->services->get(['message' => $data['message']]);
        if ($message && $message['id'] != $id) {
            return $this->fail('话术不能重复添加');
        }
        if ($this->services->update($id, $data)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }

    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id || !($info = $this->services->get($id))) {
            return $this->fail('删除的话术不存在！');
        }
        if ($info->delete()) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }
}
