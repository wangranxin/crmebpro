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

namespace app\controller\admin\v1\supplier;

use app\controller\admin\AuthController;
use app\services\supplier\LoginServices;
use app\services\supplier\SystemSupplierServices;
use app\services\system\SystemUserApplyServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 供应商申请
 * Class SupplierApply
 * @package app\controller\admin\v1\supplier
 */
class SupplierApply extends AuthController
{

	/**
	 * 供应商申请类型
	 * @var int
	 */
	protected int $type = 2;

	/**
	* @var SystemUserApplyServices
	*/
	#[Inject]
	protected SystemUserApplyServices $services;

    /**
     * 获取供应商列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
			['data', '', '', 'time'],
			['status', ],
            [['keyword', 's'], ''],
        ]);
        $where['is_del'] = 0;
		$where['type'] = $this->type;
        return $this->success($this->services->getApplyList($where));
    }


    /**
     * 获取供应商信息
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->get((int)$id));
    }

	/**
	 *  审核商品表单
	 * @param $id
	 * @return mixed
	 */
	public function verifyForm($id)
	{
		if (!$id) {
			return $this->fail('缺少参数');
		}
		return $this->success($this->services->verifyForm((int)$id));
	}

	/**
	 * 申请审核
	 * @param $id
	 * @return mixed
	 */
	public function verifyApply($id)
	{
		if (!$id) return $this->fail('缺少参数');
		$data = $this->request->postMore([
			['status', 1],
			['fail_msg', '']
		]);
		return $this->success('操作成功', $this->services->verifyApply((int)$id, $data));
	}

	/**
	 * 备注表单
	 * @param $id
	 * @return mixed
	 */
	public function markForm($id)
	{
		if (!$id) {
			return $this->fail('缺少参数');
		}
		return $this->success($this->services->markForm((int)$id));
	}

	/**
	 * 备注
	 * @param $id
	 * @return mixed
	 */
	public function mark($id)
	{
		if (!$id) return $this->fail('缺少参数');
		[$mark] = $this->request->postMore([
			['mark', ''],
		], true);
		if (!$mark) {
			return $this->fail('请输入备注');
		}
		$this->services->update($id, ['mark' => $mark]);
		return $this->success('备注成功！');
	}

	/**
	 * 删除申请
	 * @param $id
	 * @return mixed
	 */
	public function delete($id)
	{
		if (!$id) return $this->fail('删除失败，缺少参数');
		$this->services->update((int)$id, ['is_del' => 1]);
		return $this->success('删除成功！');
	}


}