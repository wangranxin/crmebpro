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
namespace app\controller\admin\v1\community;

use app\controller\admin\AuthController;
use app\Request;
use app\services\community\CommunityTopicServices;
use crmeb\exceptions\AdminException;use think\annotation\Inject;

/**
 * 社区话题
 * Class CommunityTopic
 * @package app\controller\admin\v1\community
 */
class CommunityTopic extends AuthController
{
    /**
     * @var CommunityTopicServices
     */
    #[Inject]
    protected CommunityTopicServices $services;

    /**
     * 获取社区话题列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $where = $request->postMore([
            ['name', ''],//标题
            ['is_recommend', ''],//推荐
            ['status', ''],//状态
        ]);
		$data['type'] = 0;
        $data['relation_id'] = 0;
        return $this->success($this->services->sysPage($where));
    }

    /**
	 * 获取所有话题列表
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
    public function allTopic()
    {
        return $this->success($this->services->getAllTopic([],true));
    }


    /**
     * 生成新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create($id = '')
    {
        return $this->success($this->services->createForm($id));
    }

    /**
     * 保存新增社区话题
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0],
            ['is_recommend', 0],
            ['status', 0],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入话题名称');
        }
		$topic = $this->services->getOne(['name' => $data['name'], 'is_del' => 0]);
        if ($topic && (!$id || ($id && $topic['id'] != $id)) ) {
            return $this->fail('话题已存在');
        }
        $data['type'] = 0;
        $data['relation_id'] = 0;
		if ($id) {
			$res = $this->services->update($id, $data);
		} else {
			$data['add_time'] = time();
			$res = $this->services->save($data);
		}
		if (!$res)  return $this->fail('保存失败');
        return $this->success('保存成功!');
    }

    /**
     * 设置社区话题是否显示
     * @param $id
     * @param $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        ($status == '' || $id == '') && $this->fail('缺少参数');
        $res = $this->services->update((int)$id, ['status' => (int)$status]);
        if ($res) {
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }


	/**
     * 设置社区话题是否显示
     * @param $id
     * @param $hot
     * @return mixed
     */
    public function setHot($id, $hot)
    {
        ($hot == '' || $id == '') && $this->fail('缺少参数');
        $res = $this->services->update((int)$id, ['is_recommend' => (int)$hot]);
        if ($res) {
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }

    /**
     * 删除社区话题
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id || !$this->services->count(['id' => $id, 'is_del' => 0])) {
            return $this->fail('删除的数据不存在');
        }
        $this->services->update((int)$id, ['is_del' => 1]);
        return $this->success('删除成功!');
    }


}
