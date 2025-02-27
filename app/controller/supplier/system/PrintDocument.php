<?php

namespace app\controller\supplier\system;

use app\controller\supplier\AuthController;
use app\services\system\PrintDocumentServices;
use think\annotation\Inject;

class PrintDocument extends AuthController
{
    /**
     * @var PrintDocumentServices
     */
    #[Inject]
    protected PrintDocumentServices $services;

    /**
     * 打印机列表
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printList()
    {
        $where = $this->request->getMore([
            ['type', 0],
            ['keyword', ''],
        ]);
        $where['supplier_id'] = $this->supplierId;
        return $this->success($this->services->printList($where));
    }

    /**
     * 打印机添加表单
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printForm($id)
    {
        return $this->success($this->services->printForm($id));
    }

    /**
     * 保存打印机
     * @param $id
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printSave($id)
    {
        $data = $this->request->postMore([
            ['print_name', ''],
            ['type', 0],
            ['yly_user_id', ''],
            ['yly_app_id', ''],
            ['yly_app_secret', ''],
            ['yly_sn', ''],
            ['fey_user', ''],
            ['fey_ukey', ''],
            ['fey_sn', ''],
            ['times', 1],
            ['print_type', 1],
            ['status', 1],
        ]);
        $data['supplier_id'] = $this->supplierId;
        $this->services->printSave($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 打印机状态修改
     * @param $id
     * @param $status
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printSetStatus($id, $status)
    {
        $this->services->printSetStatus($id, $status);
        return $this->success('修改成功');
    }

    /**
     * 删除打印机
     * @param $id
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printDel($id)
    {
        $this->services->printDel($id);
        return $this->success('删除成功');
    }

    public function getPrintContent($id)
    {
        return $this->success($this->services->getPrintContent($id));
    }

    public function savePrintContent($id)
    {
        $data = $this->request->postMore([
            ['header', 0],
            ['delivery', 0],
            ['buyer_remarks', 0],
            ['goods', []],
            ['freight', 0],
            ['preferential', []],
            ['pay', []],
            ['custom', 0],
            ['order', []],
            ['code', 0],
            ['code_url', ''],
            ['show_notice', 0],
            ['notice_content', '']
        ]);
        $this->services->savePrintContent($id, $data);
        return $this->success('保存成功');
    }
}
