<?php


namespace app\model\work;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model\relation\HasOne;

/**
 * Class WorkGroupMsgTask
 * @package app\model\work
 */
class WorkGroupMsgTask extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_group_msg_task';

    /**
     * @var string
     */
    protected $pk = 'id';

    /**
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * @return HasOne
     */
    public function member(): HasOne
    {
        return $this->hasOne(WorkMember::class, 'userid', 'userid')
            ->field(['userid', 'name'])
            ->bind(['name' => 'name']);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getSendTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * @return HasOne
     */
    public function sendResult()
    {
        return $this->hasOne(WorkGroupMsgSendResult::class, 'userid', 'userid');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchMsgIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('msg_id', $value);
        } else {
            $query->where('msg_id', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where('status', $value);
        }
    }
}
