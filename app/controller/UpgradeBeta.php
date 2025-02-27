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

namespace app\controller;

use app\jobs\user\UserBrokerageJob;
use app\Request;
use app\services\community\CommunityCommentServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserBrokerageServices;
use think\facade\Db;


class UpgradeBeta
{
    /**
     * @param string $field
     * @param int $n
     * @return bool
     */
    public function setIsUpgrade(string $field, int $n = 0)
    {
        try {
            $upgrade = parse_ini_file(app()->getRootPath() . '.upgrade');
        } catch (\Throwable $e) {
            $upgrade = [];
        }
        if ($n) {
            if (!is_array($upgrade)) {
                $upgrade = [];
            }
            $string = '';
            foreach ($upgrade as $key => $item) {
                $string .= $key . '=' . $item . "\r\n";
            }
            $string .= $field . '=' . $n . "\r\n";
            try {
                file_put_contents(app()->getRootPath() . '.upgrade', $string);
            } catch (\Throwable $e) {

            }
            return true;
        } else {
            if (!is_array($upgrade)) {
                return false;
            }
            return isset($upgrade[$field]);
        }
    }


    /**
     * 获取当前版本号
     * @return array
     */
    public function getversion($str)
    {
        $version_arr = [];
        $curent_version = @file(app()->getRootPath() . $str);

        foreach ($curent_version as $val) {
            [$k, $v] = explode('=', $val);
            $version_arr[$k] = $v;
        }
        return $version_arr;
    }


    public function index(Request $request)
    {
        $data = $this->upData();
        $Title = "CRMEB升级程序";
        $Powered = "Powered by CRMEB";

        //获取当前版本号
        $version_now = $this->getversion('.version')['version'];
        $version_new = $data['new_version'];
        $isUpgrade = true;
        $executeIng = false;

        return view('/upgrade/step1', [
            'title' => $Title,
            'powered' => $Powered,
            'version_now' => $version_now,
            'version_new' => $version_new,
            'isUpgrade' => json_encode($isUpgrade),
            'executeIng' => json_encode($executeIng),
            'next' => 1,
            'action' => 'upgrade'
        ]);

    }

//    public function syncOldBrokeragePrice()
//    {
//        UserBrokerageJob::dispatchDo('syncOldBrokeragePrice');
//    }

    public function upgrade(Request $request)
    {
        [$sleep, $page, $prefix] = $request->getMore([
            ['sleep', 0],
            ['page', 1],
            ['prefix', 'eb_'],
        ], true);
        $data = $this->upData();
        $code_now = $this->getversion('.version')['version_code'];
        $sql_arr = [];
        foreach ($data['update_sql'] as $items) {
            if ($items['code'] > $code_now) {
                $sql_arr[] = $items;
            }
        }
        if (!isset($sql_arr[$sleep])) {
            file_put_contents(app()->getRootPath() . '.version', "version=" . $data['new_version'] . "\nversion_code=" . $data['new_code']);
//            $this->syncOldBrokeragePrice();
            return app('json')->successful(['sleep' => -1]);
        }
        $sql = $sql_arr[$sleep];
        Db::startTrans();
        try {
            if ($sql['type'] == 1) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表添加成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 2) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表删除成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 3) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段添加成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 4) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段修改成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 5) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段删除成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 6) {
                $table = $prefix . $sql['table'] ?? '';
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $prefix . $sql['table'];
                        $item['status'] = 1;
                        $item['error'] = $table . '表中此数据已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['whereSql']) && $sql['whereSql']) {
                        $whereTable = $prefix . $sql['whereTable'] ?? '';
                        $whereSql = str_replace('@whereTable', $whereTable, $sql['whereSql']);
                        $tabId = Db::query($whereSql)[0]['tabId'] ?? 0;
                        if (!$tabId) {
                            $item['table'] = $whereTable;
                            $item['status'] = 1;
                            $item['error'] = '查询父类ID不存在';
                            $item['sleep'] = $sleep + 1;
                            $item['add_time'] = date('Y-m-d H:i:s', time());
                            Db::commit();
                            return app('json')->successful($item);
                        }
                        $upSql = str_replace('@tabId', $tabId, $upSql);
                    }
                    if (Db::execute($upSql)) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = '数据添加成功';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
            } elseif ($sql['type'] == 7) {
                $table = $prefix . $sql['table'] ?? '';
                $whereTable = $prefix . $sql['whereTable'] ?? '';
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $prefix . $sql['table'];
                        $item['status'] = 1;
                        $item['error'] = $table . '表中此数据不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['whereSql']) && $sql['whereSql']) {
                        $whereSql = str_replace('@whereTable', $whereTable, $sql['whereSql']);
                        $tabId = Db::query($whereSql)[0]['tabId'] ?? 0;
                        if (!$tabId) {
                            $item['table'] = $whereTable;
                            $item['status'] = 1;
                            $item['error'] = '查询父类ID不存在';
                            $item['sleep'] = $sleep + 1;
                            $item['add_time'] = date('Y-m-d H:i:s', time());
                            Db::commit();
                            return app('json')->successful($item);
                        }
                        $upSql = str_replace('@tabId', $tabId, $upSql);
                    }
                    if (Db::execute($upSql)) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = '数据修改成功';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
            } elseif ($sql['type'] == 8) {

            } elseif ($sql['type'] == -1) {
                $table = $prefix . $sql['table'];
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['new_table']) && $sql['new_table']) {
                        $new_table = $prefix . $sql['new_table'];
                        $upSql = str_replace('@new_table', $new_table, $upSql);
                    }
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '更新sql执行成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            }
        } catch (\Throwable $e) {
            $item['table'] = $prefix . $sql['table'];
            $item['status'] = 0;
            $item['sleep'] = $sleep + 1;
            $item['add_time'] = date('Y-m-d H:i:s', time());
            $item['error'] = $e->getMessage();
            Db::rollBack();
            return app('json')->successful($item);
        }
    }

    public function upData()
    {
        $data['new_version'] = 'CRMEB-PRO v3.2.0';
        $data['new_code'] = 320;
        $data['update_sql'] = [
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "order_id",
                'findSql' => "show columns from `@table` like 'order_id'",
                'sql' => "ALTER TABLE `@table` ADD `order_id` varchar(32)  NOT NULL DEFAULT '' COMMENT '订单号'  AFTER `id`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "user_name",
                'findSql' => "show columns from `@table` like 'user_name'",
                'sql' => "ALTER TABLE `@table` ADD `user_name` varchar(64)  NOT NULL DEFAULT '' COMMENT '真实姓名'  AFTER `qrcode_url`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "wechat_state",
                'findSql' => "show columns from `@table` like 'wechat_state'",
                'sql' => "ALTER TABLE `@table` ADD `wechat_state` varchar(32)  NOT NULL DEFAULT '' COMMENT 'v3转账状态码'  AFTER `user_name`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "package_info",
                'findSql' => "show columns from `@table` like 'package_info'",
                'sql' => "ALTER TABLE `@table` ADD `package_info` varchar(1000)  NOT NULL DEFAULT '' COMMENT 'v3转账支付收款页的package'  AFTER `wechat_state`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "fail_reason",
                'findSql' => "show columns from `@table` like 'fail_reason'",
                'sql' => "ALTER TABLE `@table` ADD `fail_reason` varchar(32) NOT NULL DEFAULT '' COMMENT 'v3转账失败原因'  AFTER `package_info`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "transfer_bill_no",
                'findSql' => "show columns from `@table` like 'transfer_bill_no'",
                'sql' => "ALTER TABLE `@table` ADD `transfer_bill_no` varchar(256)  NOT NULL DEFAULT '' COMMENT 'v3微信转账单号' AFTER `fail_reason`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_extract",
                'field' => "channel_type",
                'findSql' => "show columns from `@table` like 'channel_type'",
                'sql' => "ALTER TABLE `@table` ADD `channel_type` varchar(10)  NOT NULL DEFAULT '' COMMENT '提现渠道(小程序:routine;公众号:h5;app)' AFTER `transfer_bill_no`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "order_id",
                'findSql' => "show columns from `@table` like 'order_id'",
                'sql' => "ALTER TABLE `@table` ADD `order_id` varchar(32)  NOT NULL DEFAULT '' COMMENT '订单号'  AFTER `add_time`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "wechat_state",
                'findSql' => "show columns from `@table` like 'wechat_state'",
                'sql' => "ALTER TABLE `@table` ADD `wechat_state` varchar(32)  NOT NULL DEFAULT '' COMMENT 'v3转账状态码'  AFTER `order_id`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "package_info",
                'findSql' => "show columns from `@table` like 'package_info'",
                'sql' => "ALTER TABLE `@table` ADD `package_info` varchar(1000)  NOT NULL DEFAULT '' COMMENT 'v3转账支付收款页的package'  AFTER `wechat_state`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "fail_reason",
                'findSql' => "show columns from `@table` like 'fail_reason'",
                'sql' => "ALTER TABLE `@table` ADD `fail_reason` varchar(32) NOT NULL DEFAULT '' COMMENT 'v3转账失败原因'  AFTER `package_info`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "transfer_bill_no",
                'findSql' => "show columns from `@table` like 'transfer_bill_no'",
                'sql' => "ALTER TABLE `@table` ADD `transfer_bill_no` varchar(256)  NOT NULL DEFAULT '' COMMENT 'v3微信转账单号' AFTER `fail_reason`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "luck_lottery_record",
                'field' => "channel_type",
                'findSql' => "show columns from `@table` like 'channel_type'",
                'sql' => "ALTER TABLE `@table` ADD `channel_type` varchar(10)  NOT NULL DEFAULT '' COMMENT '提现渠道(小程序:routine;公众号:h5;app)' AFTER `transfer_bill_no`"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'pay_weixin_scene_id'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'pay'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'pay_weixin_scene_id', 'text', 'number', 4, '', 1, '', 100, 0, '', '转账场景ID', '微信v3支付商家转账的转账场景ID,用于抽奖红包和佣金提现', 0, 1)"
            ],
            [
                'code' => 319,
                'type' => -1,
                'table' => "system_notification",
                'sql' => "INSERT INTO `@table` VALUES (null, 'revenue_received', '收益到账通知', '收益到账给用户通知', 0, 0, 1, 1, 0, 0, '收益到账通知', '', 0, '0', '0', '', '', '', '', '', 1, 0)"
            ],

            [
                'code' => 320,
                'type' => 6,
                'table' => "template_message",
                'whereTable' => "system_notification",
                'findSql' => "select id from @table where tempkey = '1493'",
                'whereSql' => "select id as tabId from @whereTable where mark = 'revenue_received'",
                'sql' => "INSERT INTO `@table` VALUES (null, `@tabId`, 0, '1493', '收益到账通知', '', '收益金额{{amount3.DATA}}\n温馨提醒{{thing4.DATA}}\n时间{{time9.DATA}}', '', '', '1739763519', 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "template_message",
                'whereTable' => "system_notification",
                'findSql' => "select id from @table where tempkey = '1493'",
                'whereSql' => "select id as tabId from @whereTable where mark = 'revenue_received'",
                'sql' => "INSERT INTO `@table` VALUES (null, `@tabId`, 1, '54531', '提现结果通知', '', '金额{{amount2.DATA}}\n提现结果{{const5.DATA}}\n提现时间{{time3.DATA}}', '', '', '1739763519', 1)"
            ],
        ];
        return $data;
    }
}
