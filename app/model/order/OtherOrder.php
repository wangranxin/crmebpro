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

namespace app\model\order;


use app\model\store\SystemStoreStaff;
use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

class OtherOrder extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'other_order';

    protected $insert = ['add_time'];

    // protected $hidden = ['add_time', 'is_del', 'uid'];

	/**
     * 一对一关联用户表
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'nickname', 'avatar', 'phone', 'spread_uid', 'overdue_time', 'now_money', 'integral']);
    }

	/**
     * 一对一关联用户表
     * @return \think\model\relation\HasOne
     */
    public function userInfo()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'avatar', 'nickname', 'phone'])->bind([
                'avatar' => 'avatar',
                'nickname' => 'nickname',
                'phone' => 'phone'
            ]);
    }

    /**
     * @return model\relation\HasOne
     */
    public function staff()
    {
        return $this->hasOne(SystemStoreStaff::class, 'id', 'staff_id')
            ->field(['id', 'uid', 'store_id', 'staff_name'])
            ->bind([
                'staff_uid' => 'uid',
                'staff_store_id' => 'store_id',
                'staff_name' => 'staff_name'
            ]);
    }


    /**
     * 门店ID
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value !== '') {
            if ($value == -1) {//所有门店
                $query->where('store_id', '>', 0);
            } else {
                $query->where('store_id', $value);
            }
        }
    }

    /**
     * 门店店员ID
     * @param $query
     * @param $value
     */
    public function searchStaffIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('staff_id', $value);
            } else {
                $query->where('staff_id', $value);
            }
        }
    }

    /**
     * 订单类型
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->where('type', 'in', $value);
            } else {
                $query->where('type', $value);
            }
        }
    }

    public function searchPaidAttr($query, $value)
    {
        $query->where('paid', $value);
    }

    /**
     * 支付方式不属于
     * @param $query
     * @param $value
     */
    public function searchPayTypeNoAttr($query, $value)
    {
        $query->where('pay_type', '<>', $value);
    }

    /**
     * 用户来源
     * @param Model $query
     * @param $value
     */
    public function searchChannelTypeAttr($query, $value)
    {
        if ($value != '') $query->where('channel_type', $value);
    }

    /**
     * 订单id搜索器
     * @param $query
     * @param $value
     */
    public function searchOrderIdAttr($query, $value)
    {
        if ($value != "") {
            $query->where('order_id', $value);
        }

    }

    /**
     * 会员类型
     * @param $query
     * @param $value
     */
    public function searchMemberTypeAttr($query, $value)
    {
        if ($value && $value != 'card' && $value != 'free') {
           if ($value == -1){
               $query->where('member_type', "<>" , 0);
           }else{
               $query->where('member_type', $value);
           }
        } elseif ($value == 'card') {
            $query->where('member_type', 'free')->where('code', '<>', '');
        } elseif ($value == 'free') {
            $query->where('member_type', 'free')->where('code', '');
        }
    }

    /**
     * 支付方式
     * @param $query
     * @param $value
     */
    public function searchPayTypeAttr($query, $value)
    {
        if ($value) {
            if ($value == "free") {
                $query->where(function ($query) {
                    $query->where('type', 'in', [0, 2, 4])->whereOr(function ($query) {
                        $query->where(['type' => 1, 'is_free' => 1]);
                    });
                });
            } else {
                $query->where('pay_type', $value);
            }

        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchAddTimeAttr($query, $value)
    {
        if ($value) {
            $query->whereTime('add_time', 'between', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $query->where('uid', 'in', $value);
        }
    }


}
