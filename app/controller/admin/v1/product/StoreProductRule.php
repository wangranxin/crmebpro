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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\jobs\BatchHandleJob;
use app\services\other\queue\QueueServices;
use app\services\product\sku\StoreProductRuleServices;
use think\annotation\Inject;

/**
 * 规则管理
 * Class StoreProductRule
 * @package app\controller\admin\v1\product
 */
class StoreProductRule extends AuthController
{

    /**
     * @var StoreProductRuleServices
     */
    #[Inject]
    protected StoreProductRuleServices $services;

    /**
     * 规格列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['rule_name', '']
        ]);
		$where['type'] = 0;
		$where['relation_id'] = 0;
        $list = $this->services->getList($where);
        return $this->success($list);
    }

    /**
     * 保存规格
     * @param $id
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['rule_name', ''],
            ['spec', []]
        ]);
        if (!$data['rule_name']) {
            return $this->fail('请输入分类名称');
        }
        $this->services->save($id, $data);
        return $this->success('保存成功!');
    }

    /**
     * 获取规格信息
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $this->services->getInfo((int)$id);
        return $this->success($info);
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) {
			return $this->fail('缺少ID');
		}
		$info = $this->services->getInfo((int)$id);
		if (!$info) {
			return $this->fail('删除的数据不存在');
		}
		$this->services->delete($id);
        return $this->success('删除成功!');
    }
}
