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

namespace app\controller\admin\v1\statistic;


use app\controller\admin\AuthController;
use app\services\product\category\StoreProductCategoryServices;
use app\services\statistic\ProductStatisticServices;
use think\annotation\Inject;

/**
 * Class ProductStatistic
 * @package app\controller\admin\v1\statistic
 */
class ProductStatistic extends AuthController
{

    /**
     * @var ProductStatisticServices
     */
    #[Inject]
    protected ProductStatisticServices $services;

    /**
     * 商品基础
     * @return mixed
     */
    public function getBasic()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time']
        ]);
        $where['time'] = $this->getDay($where['time']);
        return $this->success($this->services->getBasic($where));
    }

    /**
     * 商品趋势
     * @return mixed
     */
    public function getTrend()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time']
        ]);
        $where['time'] = $this->getDay($where['time']);
        return $this->success($this->services->getTrend($where));
    }

    /**
     * 商品排行
     * @return mixed
     */
    public function getProductRanking(StoreProductCategoryServices $storeCategoryServices)
    {
        $where = $this->request->getMore([
			['cate_id', ''],
            ['data', '', '', 'time'],
            ['sort', '']
        ]);
		$cateId = $where['cate_id'];
		if ($cateId) {
			$cateId = is_string($cateId) ? [$cateId] : $cateId;
			$cateId = array_merge($cateId, $storeCategoryServices->getColumn(['pid' => $cateId], 'id'));
			$cateId = array_unique(array_diff($cateId, [0]));
		}
		$where['cate_id'] = $cateId;
        $where['time'] = $this->getDay($where['time']);
        return $this->success($this->services->getProductRanking($where));
    }

    /**
     * 导出
     * @return mixed
     */
    public function getExcel()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time']
        ]);
        $where['time'] = $this->getDay($where['time']);
        return $this->success($this->services->getTrend($where, true));
    }

    /**
     * 格式化时间
     * @param $time
     * @return string
     */
    public function getDay($time)
    {
        if (strstr($time, '-') !== false) {
            [$startTime, $endTime] = explode('-', $time);
            if (!$startTime && !$endTime) {
                return date("Y/m/d", strtotime("-30 days", time())) . '-' . date("Y/m/d 23:59:59", time());
            } else {
				if (strtotime($endTime) == strtotime(date('Y-m-d', strtotime($endTime)))) {
					$endTime = $endTime . ' 23:59:59';
				}
                return $startTime . '-' . $endTime;
            }
        } else {
            return date("Y/m/d", strtotime("-30 days", time())) . '-' . date("Y/m/d 23:59:59", time());
        }
    }
}
