<?php


namespace app\dao\work;


use app\dao\BaseDao;
use app\model\work\WorkMomentSendResult;
use crmeb\traits\SearchDaoTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class WorkMomentSendResultDao
 * @package app\dao\work
 */
class WorkMomentSendResultDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkMomentSendResult::class;
    }

    /**
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMomentList(array $where, array $field = ['*'])
    {
        return $this->search($where)->field($field)->select()->toArray();
    }
}
