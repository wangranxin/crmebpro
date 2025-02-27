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
declare (strict_types=1);

namespace app\services\message;

use app\dao\message\SystemMessageDao;
use app\services\BaseServices;
use app\services\message\service\StoreServiceRecordServices;use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 站内信
 * Class SystemMessageServices
 * @package app\services\message
 * @mixin SystemMessageDao
 */
class SystemMessageServices extends BaseServices
{
    /**
     * @var SystemMessageDao
     */
    #[Inject]
    protected SystemMessageDao $dao;

	/**
 	* 获取用户信息数量
	* @param int $uid
	* @param array $type
	* @return int
	* @throws \think\db\exception\DbException
	*/
	public function getUserMessageNum(int $uid, array $type = [0, 1])
	{
		$num = 0;
		if (in_array(0, $type)) {//客服消息
			/** @var StoreServiceRecordServices $servicesRecord */
			$servicesRecord = app()->make(StoreServiceRecordServices::class);
			$service_num = $servicesRecord->sum(['user_id' => $uid], 'mssage_num');
			$num = bcadd((string)$num, (string)$service_num, 0);
		}
		if (in_array(1, $type)) {//站内信
			$system = $this->dao->count(['uid' => $uid, 'look' => 0, 'is_del' => 0]);
			$num = bcadd((string)$num, (string)$system, 0);
		}
		return (int)$num;
	}

	/**
 	* 站内信列表
	* @param $uid
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getMessageSystemList($uid)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $where['uid'] = $uid;
        $list = $this->dao->getMessageList($where, '*', $page, $limit);
        $count = $this->dao->getCount($where);
        if (!$list) {
			return ['list' => [], 'count' => 0];
        }
		$ids = [];
        foreach ($list as &$item) {
			if (!$item['look']) {//没查看
				$ids[] = $item['id'];
			}
            $item['add_time'] = time_tran($item['add_time']);
        }
        $message = $this->dao->count(['uid' => $uid, 'look' => 0, 'is_del' => 0]);
		if ($ids) {
			$this->dao->batchUpdate($ids, ['look' => 1], 'id');
		}
        return ['list' => $list, 'count' => $count, 'service_num' => $message];
    }

    public function getInfo($where)
    {
        $info = $this->dao->getOne($where);
        if (!$info || $info['is_del'] == 1) {
            throw new ValidateException('数据不存在');
        }
        $info = $info->toArray();
        if ($info['look'] == 0) {
            $this->update($info['id'], ['look' => 1]);
        }
        $info['add_time'] = time_tran($info['add_time']);
        return $info;
    }

    /**
     * 站内信发放
     * @param int $uid
     * @param array $noticeInfo
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function systemSend(int $uid, array $noticeInfo)
    {
        $data = [];
        $data['mark'] = $noticeInfo['mark'];
        $data['uid'] = $uid;
        $data['title'] = $noticeInfo['title'];
        $data['content'] = $noticeInfo['content'];
        $data['type'] = 1;
        $data['add_time'] = time();
        return $this->dao->save($data);
    }
}
