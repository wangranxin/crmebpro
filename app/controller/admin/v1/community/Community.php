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
use app\services\community\CommunityCacheServices;
use app\services\community\CommunityServices;
use think\annotation\Inject;

/**
 * 社区
 * Class StoreCategory
 * @package app\admin\controller\v1\marketing\video
 */
class Community extends AuthController
{

    /**
     * @var CommunityServices
     */
    #[Inject]
    protected CommunityServices $services;

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],//时间
            ['topic_id', ''],//话题
            ['star', ''],//推荐指数
            ['content_type', ''],//内容类型1:图文2视频
            ['keyword', ''],//关键字搜索
            ['is_verify', ''],//是否审核
            ['type', ''],//内容来源
        ]);
        $where['is_del'] = 0;
        return $this->success($this->services->getHeader($where));
    }

    /**
     * 社区列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],//时间
            ['topic_id', ''],//话题
            ['star', ''],//推荐指数
            ['content_type', ''],//内容类型1:图文2社区内容
            ['keyword', ''],//关键字搜索
            ['is_verify', ''],//关键字搜索
            ['type', ''],//内容来源
        ]);
        $where['is_del'] = 0;
        return $this->success($this->services->getList($where, ['topic'], 'id desc'));
    }

    /**
     * 获取社区内容信息
     * @param $id
     * @return mixed
     */
    public function info($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->getDetail((int)$id, 0, true));
    }


    /**
     * 保存新增分类
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['content_type', 1],//内容类型1：图文2视频
            ['title', ''],//标题
            ['content', ''],//内容
            ['image', ''],//封面
            ['video_url', ''],//视频地址
            ['slider_image', []],//图集
            ['topic_id', []],//关联话题
            ['product_id', []],//关联商品
            ['status', 1],//状态
            ['is_recommend', 1],//推荐
            ['star', 1],//推荐指数
            ['sort', 0],//排序
        ]);
        if (!$data['title']) {
            $this->fail('请输入社区内容标题');
        }
        if (!$data['topic_id']) {
            $this->fail('请至少选择一个话题');
        }
        $data['image'] = !$data['image'] && $data['slider_image'] ? ($data['slider_image'][0] ?? '') : $data['image'];//封面图
        $data['slider_image'] = json_encode($data['slider_image']);

        $data['relation_id'] = 0;
        $data['is_verify'] = 1;
        $data['verify_time'] = time();
        $id = $id ?: 0;
        $this->services->saveData($data, $id, true);
        return $this->success('添加社区内容成功!');
    }

    /**
     * 设置推荐星级表单
     * @param $id
     * @return \think\Response
     */
    public function setStarForm($id)
    {
        if ($id == '') return $this->fail('缺少参数');
        return $this->success($this->services->setStarForm($id));
    }

    /**
     * 设置推荐星级
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStar($id)
    {
        [$star] = $this->request->postMore([
            ['star', 1],
        ], true);
        if ($id == '') return $this->fail('缺少参数');
        $this->services->getCommunityInfo((int)$id);
        $this->services->update($id, ['star' => $star]);
        return $this->success('设置成功');
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     */
    public function setStatus($id = '', $status = '')
    {
        if ($status == '' || $id == '') return $this->fail('缺少参数');
        $this->services->update($id, ['status' => $status]);
        event('community.operate', [$id]);
        return $this->success($status == 1 ? '显示成功' : '隐藏成功');
    }

    /**
     *  审核表单
     * @param $id
     * @return mixed
     */
    public function verifyForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->services->verifyForm($id));
    }

    /**
     * 强制下架
     * @param $id
     * @param $recommend
     * @return mixed
     */
    public function takeDownForm($id)
    {
        if ($id == '') return $this->fail('缺少参数');
        $info = $this->services->get($id);
        if (!$info) {
            $this->fail('社区内容不存在');
        }
        return $this->success($this->services->verifyForm((int)$id, 2));
    }

    /**
     * 审核
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function setVerify($id = '')
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $data = $this->request->getMore([
            ['is_verify', 1],
            ['refusal', '']
        ]);
        if (in_array($data['is_verify'], [-1, -2]) && !$data['refusal']) {
            return $this->fail('请输入原因');
        }
        $data['verify_time'] = time();
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('社区内容不存在');
        }
        $this->services->update((int)$id, $data);
        if ($data['is_verify'] == 1) {
            /** @var CommunityCacheServices $communityCacheServices */
            $communityCacheServices = app()->make(CommunityCacheServices::class);
            $communityCacheServices->setCommunityNewest($info['relation_id'], $id);
        }
        event('community.operate', [$id, 1]);
        return $this->success('操作成功');
    }


    /**
     * 删除社区内容
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if ($id == '') return $this->fail('缺少参数');
        $this->services->communityDelete($id, 0, true);
        return $this->success('删除成功!');
    }
}
