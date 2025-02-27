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

namespace app\services\system;


use app\dao\system\SystemUserApplyDao;
use app\services\BaseServices;
use app\dao\system\SystemRoleDao;
use app\services\supplier\SystemSupplierServices;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;


/**
 * Class SystemUserApplyServices
 * @package app\services\system
 * @mixin SystemRoleDao
 */
class SystemUserApplyServices extends BaseServices
{

	/**
	 * @var string[]
	 */
	protected array $status_name = [
		0 => '未处理',
		1 => '已审核',
		2 => '未通过',
	];

	/**
	* @var SystemUserApplyDao
	*/
	#[Inject]
	protected SystemUserApplyDao $dao;



	/**
	 * 所有申请记录列表
	 * @param array $where
	 * @param string $field
	 * @return array
	 */
	public function getApplyList(array $where, string $field = '*')
	{
		[$page, $limit] = $this->getPageValue();
		$list = $this->dao->getList($where, $field, ['user'], $page, $limit);
		foreach ($list as &$item) {
			$item['nickname'] = $item['user']['nickname'] ?? '';
			$item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', (int)$item['add_time']) : '';
			$item['status_name'] = $this->status_name[$item['status']] ?? '未处理';
		}
		$count = $this->dao->count($where);
		return compact('list', 'count');
	}

	/**
	 * 用户申请记录
	 * @param int $uid
	 * @param int $type
	 * @return array
	 */
	public function getUserApply(int $uid, int $type = 2)
	{
		[$page, $limit] = $this->getPageValue();
		$where = ['uid' => $uid, 'type' => $type, 'is_del' => 0];
		$list = $this->dao->getList($where, '*', ['user'], $page, $limit);
		foreach ($list as &$item) {
			$item['nickname'] = $item['user']['nickname'] ?? '';
			$item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', (int)$item['add_time']) : '';
		}
		return $list;
	}

	/**
	 * 添加申请
	 * @param int $id
	 * @param int $uid
	 * @param $data
	 * @param int $type
	 * @return int|mixed
	 */
	public function saveApply(int $id, int $uid, $data, int $type = 2)
	{
		$data['uid'] = $uid;
		$data['add_time'] = time();
		$data['type'] = $type;
		if ($id) {
			$data['status'] = 0;
			$data['status_time'] = 0;
			$data['fail_msg'] = '';
			$this->dao->update($id, $data);
		} else {
			$res = $this->dao->save($data);
			if (!$res) {
				throw new ValidateException('保存失败，请稍后再试');
			}
			$id = $res->id;
		}
		return $id;

	}

	/**
	 * 审核表单
	 * @param int $id
	 * @return mixed
	 */
	public function verifyForm(int $id)
	{
		$info = $this->dao->get($id);
		if (!$info) {
			throw new ValidateException('申请记录不存在');
		}
		$f = [];
		$f[] = Form::radio('status', '审核状态', 1)->options([['value' => 1, 'label' => '通过'], ['value' => 2, 'label' => '拒绝']])->appendControl(2, [
					Form::textarea('fail_msg', '拒绝原因')->required('请输入拒绝原因')
				]);
		return create_form('供应商申请审核', $f, Url::buildUrl('/supplier/apply/verify/' . $id), 'post');
	}

	/**
	 * 备注表单
	 * @param int $id
	 * @return mixed
	 */
	public function markForm(int $id)
	{
		$info = $this->dao->get($id);
		if (!$info) {
			throw new ValidateException('申请记录不存在');
		}
		$info = $info->toArray();
		$f = [];
		$f[] = Form::textarea('mark', '备注', $info['mark'])->required('请输入拒绝原因');
		return create_form('供应商申请备注', $f, Url::buildUrl('/supplier/apply/mark/' . $id), 'post');
	}

	/**
	 * 审核申请记录
	 * @param int $id
	 * @param array $data
	 * @param int $type
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function verifyApply(int $id, array $data, int $type = 2)
	{
		$info = $this->dao->get($id);
		if (!$info) {
			throw new ValidateException('申请记录不存在');
		}
		$info = $info->toArray();
		if ($info['status'] != 0) {
			throw new ValidateException('请不要重复审核');
		}
		if (!isset($data['status']) || !in_array($data['status'], [1, 2])) {
			throw new ValidateException('审核状态异常，请稍后重试');
		}
		$res = $this->transaction(function () use ($id, $info, $data, $type) {
			$result = [];
			if ($data['status'] == 1) {//通过
				switch ($type) {
					case 2://供应商
						/** @var SystemSupplierServices $systemSupplierServices */
						$systemSupplierServices = app()->make(SystemSupplierServices::class);
						$result = $systemSupplierServices->verifyAgreeCreate($id, $info);
						break;
				}
			}
			$data['relation_id'] = $result['id'] ?? 0;
			$data['status_time'] = time();
			$res = $this->dao->update($id, $data);
			if (!$res) {
				throw new ValidateException('审核保存失败');
			}
			return $info;
		});
		event('supplier.verify', [$res, $data['status']]);
		return $res;
	}




}
