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

namespace app\services\other;


use app\dao\other\AgreementDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * Class AgreementServices
 * @package app\services\other
 * @mixin AgreementDao
 */
class AgreementServices extends BaseServices
{

    /**
     * @var AgreementDao
     */
    #[Inject]
    protected AgreementDao $dao;

	/**
     * 获取会员协议
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgreementBytype($type)
    {
        if (!$type) return [];
		return $this->dao->cacheTag()->remember(md5('agreement_' . $type), function () use ($type) {
            $data = $this->dao->getOne(['type' => $type]);
			if ($data) {
				return $data->toArray();
			} else {
				return [];
			}
        });
    }

    /**
     * 修改协议内容
     * @param array $data
     * @param $id
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveAgreement(array $data, $id = 0)
    {
        if (!$data) return false;
        if (!isset($data['type']) || !$data['type'] || $data['type'] == 0) throw new ValidateException('协议类型缺失');
        if (!isset($data['title']) || !$data['title']) throw new ValidateException('请填写协议名称');
        if (!isset($data['content']) || !$data['content']) throw new ValidateException('请填写协议内容');
        if (!$id) {
            $getOne = $this->getAgreementBytype($data['type']);
            if ($getOne) throw new ValidateException('该类型协议已经存在');
        }
		$data['add_time'] = time();
        if ($id) {
            $res = $this->dao->update($id,$data);
        } else {
			$res = $this->dao->save($data);
        }
		$this->dao->cacheTag()->clear();
        return !!$res;
    }


}
