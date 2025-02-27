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
namespace app\controller\admin\v1\user;

use app\services\other\Import\ImportRecordServices;
use app\controller\admin\AuthController;
use crmeb\services\FileService;
use think\annotation\Inject;

class UserImport extends AuthController
{

    /**
     * @var ImportRecordServices
     */
    #[Inject]
    protected ImportRecordServices $services;

    /**
     * 导入用户
     * @return \think\Response
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * User: liusl
     * DateTime: 2024/12/19 下午4:05
     */
    public function import()
    {
        $data = $this->request->getMore([
            ['file', ""],
            ['real_name', '']
        ]);

        if (!$data['file']) return app('json')->fail('请上传文件');
        $file = public_path() . substr($data['file'], 1);

        $cardData = app()->make(FileService::class)->readImportExcel($file, 2);

        $cardCount = count($cardData);

        if ($cardCount == 0) {
            return app('json')->fail('导入数据不能为空');
        }

        if ($cardCount > ImportRecordServices::MAX_IMPORT_NUM) {
            return app('json')->fail('导入数据不能超过 ' . ImportRecordServices::MAX_IMPORT_NUM);
        }

        $res = $this->services->batchUserImport($cardData, $data['real_name']);

        $failCount = $res['errorCount'] ?? 0;
        $allCount = $cardCount;
        $id = $res['id'] ?? 0;

        if ($cardCount > ImportRecordServices::MAX_SINGLE_NUM) {
            $msg = '执行队列成功,稍后导入记录查看';
            $type = 2;
        } else {
            $msg = '导入成功';
            $type = 1;
        }

        return app('json')->success(compact('id', 'msg', 'failCount', 'allCount', 'type'));
    }

    /**
     * 列表
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/19 下午4:59
     */
    public function importList()
    {
        $where = $this->request->getMore([
            ['time', ''],
            ['name', '']
        ]);
        $where['type'] = 'user';
        $where['is_del'] = 0;
        return app('json')->success($this->services->getImportList($where));
    }

    /**
     * 删除
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/19 下午4:59
     */
    public function importDelete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        return app('json')->success($this->services->deleteImport($id));
    }


}
