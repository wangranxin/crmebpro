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

namespace app\services\order;


use app\dao\order\StoreOrderEconomizeDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 计算节省金额
 * Class StoreOrderEconomizeServices
 * @package app\services\order
 * @mixin StoreOrderEconomizeDao
 */
class StoreOrderEconomizeServices extends BaseServices
{

    /**
     * @var StoreOrderEconomizeDao
     */
    #[Inject]
    protected StoreOrderEconomizeDao $dao;

    /**
     * 添加节省金额数据
     * @param array $add
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function addEconomize(array $add)
    {
        if (!$add) throw new ValidateException('数据不存在');
        return $this->dao->save($add);
    }

    /**
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne(array $where)
    {
        if (!$where) throw new ValidateException('条件缺失');
        return $this->dao->getOne($where);
    }

    /**
 	* 汇总付费会员节省金额
	* @param $uid
	* @return float
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function sumEconomizeMoney($uid)
    {
		$economizeMoney = 0.00;
        if (!$uid) return $economizeMoney;
        $list = $this->dao->getList(['uid' => $uid]);
        if ($list) {
            foreach ($list as $k => $v) {
                $economizeMoney += $v['postage_price'];
                $economizeMoney += $v['member_price'];
                $economizeMoney += $v['offline_price'];
                $economizeMoney += $v['coupon_price'];
            }
        }
        return (float)sprintf("%.2f", $economizeMoney);
    }
}
