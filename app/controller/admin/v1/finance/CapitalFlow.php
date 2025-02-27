<?php

namespace app\controller\admin\v1\finance;

use app\controller\admin\AuthController;
use app\services\system\CapitalFlowServices;
use think\annotation\Inject;

class CapitalFlow extends AuthController
{

    /**
     * @var CapitalFlowServices
     */
    #[Inject]
    protected CapitalFlowServices $services;

    /**
     * 资金流水
     * @return mixed
     */
    public function getFlowList()
    {
        $where = $this->request->getMore([
            ['time', ''],
            ['trading_type', 0],
            ['keywords', ''],
            ['ids', ''],
            ['export', 0]
        ]);
        $date = $this->services->getFlowList($where);
        return app('json')->success($date);
    }

    /**
     * 资金流水备注
     * @param $id
     * @return mixed
     */
    public function setMark($id)
    {
        $data = $this->request->postMore([
            ['mark', '']
        ]);
        $this->services->setMark($id, $data);
        return app('json')->success('备注成功！');
    }

    /**
     * 账单记录
     * @return mixed
     */
    public function getFlowRecord()
    {
        $where = $this->request->getMore([
            ['type', 'day'],
            ['time', '']
        ]);
        $data = $this->services->getFlowRecord($where);
        return app('json')->success($data);
    }
}
