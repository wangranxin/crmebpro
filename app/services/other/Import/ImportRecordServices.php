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

namespace app\services\other\Import;


use app\dao\other\Import\ImportRecordDao;
use app\jobs\user\UserImportJob;
use app\jobs\user\UserSpreadJob;
use app\services\BaseServices;
use app\services\kefu\UserServices;
use app\services\other\CityAreaServices;
use app\services\user\group\UserGroupServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\wechat\WechatUserServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * 导入
 * Class ImportRecordServices
 * @package app\services\other\import
 * @mixin ImportRecordDao
 */
class ImportRecordServices extends BaseServices
{

    const MAX_IMPORT_NUM = 10000;
    const MAX_SINGLE_NUM = 50;

    /**
     * @var ImportRecordDao
     */
    #[Inject]
    protected ImportRecordDao $dao;

    /**
     * 列表
     * @param $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/19 下午4:54
     */
    public function getImportList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('add_time DESC')->select()->toArray();
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('count', 'list');
    }

    /**
     * 删除
     * @param int $id
     * @return true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/19 下午4:57
     */
    public function deleteImport(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('删除失败');
        }
        $info->is_del = 1;
        $info->save();

        app()->make(ImportRecordErrorServices::class)->delete(['record_id' => $id]);
        return true;
    }

    /**
     * 批量
     * @param $data
     * User: liusl
     * DateTime: 2024/12/9 下午4:09
     */
    public function batchUserImport($data, $real_name = '', $type = 'user')
    {
        $res = $this->dao->save([
            'name' => $real_name ? substr($real_name, 0, strpos($real_name, '.')) : '导入用户数据',
            'type' => $type,
            'total_count' => count($data),
            'add_time' => time()]);
        $id = $res->id;
        $errorCount = 0;
        if (count($data) > self::MAX_SINGLE_NUM) {

            $chunkedArray = array_chunk($data, self::MAX_SINGLE_NUM);
            foreach ($chunkedArray as $key => $chunk) {
                $end = !array_key_last($chunkedArray) == $key;
                UserImportJob::dispatch([$id, $chunk, $end]);
            }
        } else {
            $errorCount = $this->userSingleImport($data, $id, true);

        }
        return compact('errorCount', 'id');
    }

    /**
     * 0:openid    1:unioId 2:uid 3:手机号 4:用户昵称 5:客户姓名 6:性别 7:生日 8:用户等级 9:经验值 10:付费会员有效期
     * 11:客户积分 12:客户余额 13:客户标签 14:用户分组 15:用户来源 16:省 17:市 18:区 19:地址
     * 单次
     * User: liusl
     * DateTime: 2024/12/9 下午4:10
     */
    public function userSingleImport($data, $id, $end = false)
    {
        $userLabel = app()->make(UserLabelServices::class);

        $labelCateId = $userLabel->search(['type' => 0, 'label_cate' => 0])->where('label_name', '外部导入')->value('id');
        if (!$labelCateId) {
            $labelCateId = $userLabel->search([])->save(['label_name' => '外部导入', 'type' => 0, 'label_cate' => 0])->id;
        }

        // 数据处理
        list($errorData, $handleData, $labelData, $groupData) = $this->checkUser($data);

        // 唯一化并过滤标签和分组数据
        $_labelValues = array_unique(array_filter($labelData));
        $_groupValues = array_unique(array_filter($groupData));

        $res = $this->transaction(function () use ($labelCateId, $_labelValues, $_groupValues, $errorData, $id, $handleData, $end) {
            // 获取标签和分组数据
            $labelValues = $this->getOrCreateLabels($_labelValues, $labelCateId);
            $groupValues = $this->getOrCreateGroups($_groupValues);

            // 错误数据
            $saveErrorData = $this->prepareErrorData($errorData, $id);

            // 处理用户数据
            list($labelRelationData, $wechatUserData) = $this->processHandleData($handleData, $labelValues, $groupValues);
            // 批量插入其他数据
            $this->bulkSave($labelRelationData, $wechatUserData, $saveErrorData, $id);

            //是否最后一批数据
            if ($end) {
                $this->dao->update($id, ['status' => 1]);
            }
            return true;
        });
        if (!$res) {
            throw new ValidateException('导入失败');
        }
        return count($errorData);
    }


    /**
     * 获取或创建标签
     * @param $_labelValues
     * @param $labelCateId
     * @return array
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/18 下午5:51
     */

    private function getOrCreateLabels($_labelValues, $labelCateId)
    {
        $userLabel = app()->make(UserLabelServices::class);

        $labelValues = $userLabel->search([])->whereIn('label_name', $_labelValues)->column('label_name', 'id');

        $diffLabels = array_diff($_labelValues, $labelValues);

        if ($diffLabels) {
            foreach ($diffLabels as $diffLabel) {
                $_id = $userLabel->search([])->insert([
                    'label_cate' => $labelCateId,
                    'label_name' => $diffLabel,
                ], true);
                $labelValues[$_id] = $diffLabel;
            }
        }

        return array_combine(
            array_map('md5', $labelValues),
            array_keys($labelValues)
        );
    }

    /**
     * 获取或创建分组
     * @param $_groupValues
     * @return array
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/18 下午5:51
     */
    private function getOrCreateGroups($_groupValues)
    {
        $userGroupServices = app()->make(UserGroupServices::class);
        $groupValues = $userGroupServices->search([])->whereIn('group_name', $_groupValues)->column('group_name', 'id');
        $diffGroups = array_diff($_groupValues, $groupValues);

        if ($diffGroups) {
            foreach ($diffGroups as $diffGroup) {
                $_id = $userGroupServices->search([])->insert(['group_name' => $diffGroup], true);
                $groupValues[$_id] = $diffGroup;
            }
        }
        return array_combine(
            array_map('md5', $groupValues),
            array_keys($groupValues)
        );
    }

    /**
     * 错误记录
     * @param $errorData
     * @param $id
     * @return array
     * User: liusl
     * DateTime: 2024/12/18 下午5:51
     */
    private function prepareErrorData($errorData, $id)
    {
        $saveErrorData = [];
        foreach ($errorData as $error) {
            $fail_msg = $error['fail_msg'] ?? '';
            unset($error['fail_msg']);
            $saveErrorData[] = [
                'record_id' => $id,
                'original_data' => json_encode($error),
                'fail_msg' => $fail_msg,
            ];
        }
        return $saveErrorData;
    }

    /**
     * 处理用户数据
     * @param $handleData
     * @param $labelValues
     * @param $groupValues
     * @return array[]
     * User: liusl
     * DateTime: 2024/12/18 下午5:50
     */
    private function processHandleData($handleData, $labelValues, $groupValues)
    {
        $userServices = app()->make(UserServices::class);

        $labelRelationData = [];
        $wechatUserData = [];

        foreach ($handleData as $handle) {
            $uid = $handle['uid'];
            $openid = $handle['openid'];
            $unionid = $handle['unionid'];
            $label = $handle['label'];

            unset($handle['label'], $handle['uid'], $handle['openid'], $handle['unionid']);

            if ($uid) {
                $handle['uid'] = $uid;
            }
            $handle['group'] = $handle['group'] ? $groupValues[md5($handle['group'])] ?? 0 : 0;
            $handle['account'] = $handle['phone'] ?: 'wx' . rand(1, 9999) . time();
            $handle['pwd'] = md5('123456');
            $handle['avatar'] = sys_config('h5_avatar');
            $handle['user_type'] = $handle['login_type'];
            $handle['add_time'] = time();
            $uid = $userServices->save($handle)->uid;

            // 处理标签关系
            if ($label) {
                foreach ($label as $l) {
                    $labelRelationData[] = [
                        'uid' => $uid,
                        'label_id' => $labelValues[md5($l)] ?? 0,
                    ];
                }
            }

            // 处理微信用户
            if (($openid || $unionid) && in_array($handle['login_type'], ['routine', 'app', 'wechat'])) {
                $wechatUserData[] = [
                    'uid' => $uid,
                    'openid' => $openid,
                    'unionid' => $unionid,
                    'nickname' => $handle['nickname'],
                    'sex' => $handle['sex'],
                    'user_type' => $handle['login_type']
                ];
            }
            event('user.register', [$userServices->get($uid), true, 0]);
        }

        return [$labelRelationData, $wechatUserData];
    }

    /**
     * 批量保存数据
     * @param $labelRelationData
     * @param $wechatUserData
     * @param $saveErrorData
     * @param $id
     * @return void
     * @throws DbException
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/18 下午5:50
     */
    private function bulkSave($labelRelationData, $wechatUserData, $saveErrorData, $id)
    {
        if (count($labelRelationData) > 0) {
            app()->make(UserLabelRelationServices::class)->saveAll($labelRelationData);
        }

        if (count($wechatUserData) > 0) {
            app()->make(WechatUserServices::class)->saveAll($wechatUserData);
        }

        if (count($saveErrorData) > 0) {
            app()->make(ImportRecordErrorServices::class)->saveAll($saveErrorData);
            $this->dao->search([])->where('id', $id)->setInc('fail_count', count($saveErrorData));
        }
    }


    /**
     * 检查用户数据并处理
     * @param $data
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/18 下午4:10
     */
    public function checkUser($data)
    {
        $userServices = app()->make(UserServices::class);
        $wechatUserServices = app()->make(WechatUserServices::class);
        $cityAreaServices = app()->make(CityAreaServices::class);

        // 初始化错误数据、处理后的数据、标签数据和分组数据数组
        $errorData = [];
        $handleData = [];
        $labelData = [];
        $groupData = [];

        // 获取微信用户的openid和unionid信息
        $openIds = $wechatUserServices->search([])->whereIn('openid', array_filter(array_column($data, 'openid')))->column('uid', 'openid');
        $unionIds = $wechatUserServices->search([])->whereIn('unionid', array_filter(array_column($data, 'unionid')))->column('uid', 'unionid');
        // 获取用户的uid和手机号信息
        $userIds = $userServices->search([])->withTrashed()->whereIn('uid', array_filter(array_column($data, 'uid')))->column('uid', 'uid');
        $phoneIds = $userServices->search([])->whereIn('phone', array_filter(array_column($data, 'phone')))->column('uid', 'phone');
        //等级
        $levels = app()->make(SystemUserLevelServices::class)->getColumn(['is_del' => 0, 'is_show' => 1], 'exp_num,grade', 'id');
        // 遍历输入的数据数组
        foreach ($data as $handle) {
            // 检查是否所有关键用户信息都未提供如果未提供，则标记为错误
            if (!$handle['unionid'] && !$handle['uid'] && !$handle['phone'] && !$handle['openid']) {
                $handle['fail_msg'] = 'openid, unionid, uid, 手机号请填写至少一个用户信息';
                $errorData[] = $handle;
                continue;
            }

            if ($handle['openid'] && isset($openIds[$handle['openid']])) {
                $handle['fail_msg'] = 'openid: ' . $handle['openid'] . ' 已存在';
                $errorData[] = $handle;
                continue;
            }

            if ($handle['unionid'] && isset($unionIds[$handle['unionid']])) {
                $handle['fail_msg'] = 'unionid: ' . $handle['unionid'] . ' 已存在';
                $errorData[] = $handle;
                continue;
            }

            if ($handle['uid'] && isset($userIds[$handle['uid']])) {
                $handle['fail_msg'] = 'uid: ' . $handle['uid'] . ' 已存在';
                $errorData[] = $handle;
                continue;
            }

            if ($handle['phone']) {
                if (!preg_match('/^1[3-9]\d{9}$/', $handle['phone'])) {
                    $handle['fail_msg'] = '手机号格式错误';
                    $errorData[] = $handle;
                    continue;
                }
                if (isset($phoneIds[$handle['phone']])) {
                    $handle['fail_msg'] = '手机号: ' . $handle['phone'] . ' 已存在';
                    $errorData[] = $handle;
                    continue;
                }
            }

            //真实姓名
            if ($handle['real_name'] && mb_strlen($handle['real_name'], 'UTF-8') > 25) {
                $handle['fail_msg'] = '真实姓名不能超过25个字符';
                $errorData[] = $handle;
                continue;
            }
            // 根据性别名称转换为对应的数字代码
            $handle['sex'] = match ($handle['sex']) {
                '男' => 1,
                '女' => 2,
                default => 0,
            };

            if ($handle['birthday']) {
                $handle['birthday'] = $this->excelDate($handle['birthday']);
                $handle['birthday'] = strtotime($handle['birthday']);
            }
            if ($handle['overdue_time']) {
                $handle['overdue_time'] = $this->excelDate($handle['overdue_time']);
                $handle['overdue_time'] = strtotime($handle['overdue_time']);
                $handle['is_money_level'] = 1;
            }

            if ($handle['label']) {
                $handle['label'] = array_unique(array_filter(explode(',', $handle['label'])));
                $labelData = array_merge($labelData, $handle['label']);
            }

            if ($handle['group']) {
                $groupData[] = $handle['group'];
            }

            //经验
            if ($handle['level'] || $handle['exp']) {
                $level = in_array($handle['level'], array_column($levels, 'grade')) ? $handle['level'] : 0;
                $level_num = 0;
                foreach ($levels as $var) {
                    if ($handle['exp'] >= $var['exp_num']) {
                        $level_num = $var['grade'];
                    }
                }

                foreach ($levels as $key => $var) {
                    if ($level > $level_num && $var['grade'] == $level) {
                        $handle['exp'] = $var['exp_num'];
                        $handle['level'] = $key;
                        break;
                    }
                    if ($level <= $level_num && $var['grade'] == $level_num) {
                        $handle['level'] = $key;
                        break;
                    }else{
                        $handle['level'] = $handle['exp'] = 0;
                    }
                }
            }

            if ($handle['province'] && $handle['city'] && $handle['area']) {
                $handle['provincials'] ="{$handle['province']}/{$handle['city']}/{$handle['area']}";
                $province = $cityAreaServices->getCityId($handle['province'], $handle['city'], $handle['area']);
                $handle['province'] = $province['province'];
                $handle['city'] = $province['city'];
                $handle['area'] = $province['area'];
            } else {
                $handle['province'] = 0;
                $handle['city'] = 0;
                $handle['area'] = 0;
            }
            $handle['login_type'] = match ($handle['login_type']) {
                '微信小程序' => 'routine',
                '公众号' => 'wechat',
                'H5' => 'h5',
                'PC' => 'pc',
                'APP' => 'app',
                default => 'import',
            };
            $handleData[] = $handle;
        }
        return [$errorData, $handleData, $labelData, $groupData];
    }

    public function excelDate($date)
    {
        //Excel 1900日期系统起始日期
        $excelStartDate = strtotime('1900-01-01');
        // 因为 Excel 错误地包含了 1900-02-29，日期序列号应该减去 1
        $date = $date - 2;

        $unixTimestamp = strtotime("+$date days", $excelStartDate);
        return date('Y-m-d', $unixTimestamp);
    }
}
