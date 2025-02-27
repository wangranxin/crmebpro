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
namespace app\controller\supplier\system;

use app\controller\supplier\AuthController;
use app\services\supplier\SupplierTicketPrintServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class SupplierTicketPrint
 * @package app\controller\supplier
 */

class SupplierTicketPrint extends AuthController
{

    /**
     * @var SupplierTicketPrintServices
     */
    #[Inject]
    protected SupplierTicketPrintServices $services;

    /**
 	* 获取打印信息
	* @return \think\Response
	* @throws DataNotFoundException
	* @throws DbException
	* @throws ModelNotFoundException
	*/
    public function read()
    {
        return $this->success($this->services->getTicketInfo((int)$this->supplierId));
    }

    /**
     * 更新打印信息
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function update()
    {
        $data = $this->request->postMore([
            ['develop_id', 0],
            ['api_key', ''],
            ['client_id', ''],
            ['terminal_number', ''],
            ['status', 0],
        ]);

        $this->validate($data, \app\validate\supplier\SupplierTicketPrintValidate::class, 'update');
        $this->services->savePrintData((int)$this->supplierId, $data);
        return $this->success('保存成功');
    }
}
