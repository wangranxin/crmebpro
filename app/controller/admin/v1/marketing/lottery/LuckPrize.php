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

namespace app\controller\admin\v1\marketing\lottery;

use think\annotation\Inject;
use app\controller\admin\AuthController;
use app\services\activity\lottery\LuckPrizeServices;

/**
 * 抽奖奖品
 * Class LuckPrize
 * @package app\controller\admin\v1\marketing\lottery
 */
class LuckPrize extends AuthController
{

    /**
     * @var LuckPrizeServices
     */
    #[Inject]
    protected LuckPrizeServices $services;

    /**
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/19
     */
    public function edit($id)
    {
        $data = $this->request->postMore([
            ['type', 1],
            ['lottery_id', 0],
            ['name', ''],
            ['prompt', ''],
            ['image', ''],
            ['chance', 1],
            ['total', 1],
            ['couon_id', 0],
            ['product_id', 0],
            ['unique', ''],
            ['num', 1]
        ]);
        if (!$id) {
            return $this->fail('缺少参数id');
        }
        if (!$data['lottery_id']) {
            return $this->fail('缺少抽奖活动id');
        }
        return $this->success($this->services->edit((int)$id, $data) ? '编辑成功' : '编辑失败');
    }

}
