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


class Upgrade
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

    public function syncOldBrokeragePrice()
    {
        UserBrokerageJob::dispatchDo('syncOldBrokeragePrice');
    }

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
            $this->syncOldBrokeragePrice();
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


            //新增表
            [
                'code' => 320,
                'type' => -1,
                'table' => "community_record",
                'sql' => "CREATE TABLE IF NOT EXISTS  `@table` (
       `id` int NOT NULL AUTO_INCREMENT COMMENT '记录ID',
       `uid` int NOT NULL DEFAULT '0' COMMENT '用户ID',
       `relation_id` int NOT NULL DEFAULT '0' COMMENT '关联用户ID',
       `type` tinyint NOT NULL DEFAULT '1' COMMENT '记录类型：1-点赞, 2-评论, 3-关注',
       `link_id` int NOT NULL DEFAULT '0' COMMENT '关联帖子ID',
       `comment_id` int NOT NULL DEFAULT '0' COMMENT '关联评论ID',
       `comment_type` tinyint NOT NULL DEFAULT '0' COMMENT '评论类型：1-评论, 2-回复',
       `content` varchar(1000)  NOT NULL DEFAULT '' COMMENT '内容',
       `is_viewed` tinyint NOT NULL DEFAULT '0' COMMENT '是否已查看：0-未查看, 1-已查看',
       `add_time` int NOT NULL DEFAULT '0' COMMENT '添加时间(时间戳)',
       `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '删除',
       PRIMARY KEY (`id`)
    )ENGINE = InnoDB  DEFAULT CHARSET = utf8mb4 COMMENT = '社区记录表'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "import_record",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(128) NOT NULL COMMENT '文件名称',
    `type` varchar(32) NOT NULL COMMENT '类型;user: 用户',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态,0:正在导入 1 已完成',
    `total_count` int NOT NULL DEFAULT '0' COMMENT '总条数',
    `fail_count` int NOT NULL DEFAULT '0' COMMENT '失败条数',
    `down_count` int NOT NULL DEFAULT '0' COMMENT '下载次数',
    `add_time` int NOT NULL COMMENT '添加时间',
    `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '删除',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT = '用户导入'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "import_record_error",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL DEFAULT '0' COMMENT '记录 id',
  `original_data` text  COMMENT '原数据 json',
  `fail_msg` varchar(2000)  NOT NULL COMMENT '错误信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT = '用户导入错误信息表'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "system_menus_relevance",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
     `id` int NOT NULL AUTO_INCREMENT,
     `menu_id` int NOT NULL DEFAULT '0'  COMMENT '菜单id',
     `keyword` varchar(255)  NOT NULL COMMENT '关键词',
     PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT = '菜单关键词搜索'"
            ],
            //新增字段
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "is_brokerage",
                'findSql' => "show columns from `@table` like 'is_brokerage'",
                'sql' => "ALTER TABLE `@table` ADD `is_brokerage` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否参与分佣1 参与 0 不参与' AFTER `is_sold`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "brokerage_type",
                'findSql' => "show columns from `@table` like 'brokerage_type'",
                'sql' => "ALTER TABLE `@table` ADD `brokerage_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '返佣类型 1 固定优惠类型,2 返佣比例' AFTER `is_brokerage`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "level_type",
                'findSql' => "show columns from `@table` like 'level_type'",
                'sql' => "ALTER TABLE `@table` ADD `level_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '等级会员价格,1:系统默认,2:自定义' AFTER `brokerage_type`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "share_content",
                'findSql' => "show columns from `@table` like 'share_content'",
                'sql' => "ALTER TABLE `@table` ADD `share_content` varchar(2000)  NOT NULL DEFAULT '' COMMENT '分享文案'  AFTER `brokerage_type`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "default_sku",
                'findSql' => "show columns from `@table` like 'default_sku'",
                'sql' => "ALTER TABLE `@table` ADD `default_sku` varchar(255)  NOT NULL DEFAULT '' COMMENT '默认规格'  AFTER `share_content`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "min_qty",
                'findSql' => "show columns from `@table` like 'min_qty'",
                'sql' => "ALTER TABLE `@table`  ADD `min_qty` int(11) NOT NULL DEFAULT '0' COMMENT '起购数量'  AFTER `default_sku`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product",
                'field' => "presale_status",
                'findSql' => "show columns from `@table` like 'presale_status'",
                'sql' => "ALTER TABLE `@table`  ADD `presale_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '预售结束后状态 1:上架,0 下架'  AFTER `min_qty`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product_attr_value",
                'field' => "level_price",
                'findSql' => "show columns from `@table` like 'level_price'",
                'sql' => "ALTER TABLE `@table`  ADD `level_price` varchar(512)  NOT NULL DEFAULT '' COMMENT '等级价格' AFTER `write_end`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product_attr_value",
                'field' => "is_default_select",
                'findSql' => "show columns from `@table` like 'is_default_select'",
                'sql' => "ALTER TABLE `@table`  ADD `is_default_select` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认规格' AFTER `level_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_product_attr_value",
                'field' => "is_show",
                'findSql' => "show columns from `@table` like 'is_show'",
                'sql' => "ALTER TABLE `@table`  ADD  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示' AFTER `is_default_select`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_brokerage",
                'field' => "order_uid",
                'findSql' => "show columns from `@table` like 'order_uid'",
                'sql' => "ALTER TABLE `@table`  ADD `order_uid` int NOT NULL DEFAULT '0' COMMENT '订单用户' AFTER `link_id`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_brokerage",
                'field' => "order_price",
                'findSql' => "show columns from `@table` like 'order_price'",
                'sql' => "ALTER TABLE `@table`  ADD `order_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额' AFTER `order_uid`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "community_topic",
                'field' => "type",
                'findSql' => "show columns from `@table` like 'type'",
                'sql' => "ALTER TABLE `@table`  ADD  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型,1 后台,2 用户' AFTER `is_recommend`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "user_bill",
                'field' => "obtain_time",
                'findSql' => "show columns from `@table` like 'obtain_time'",
                'sql' => "ALTER TABLE `@table`  ADD `obtain_time` int NOT NULL DEFAULT '0' COMMENT '获得时间(用于发布帖子时间)'  AFTER `frozen_time`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "total_price",
                'findSql' => "show columns from `@table` like 'total_price'",
                'sql' => "ALTER TABLE `@table`  ADD `total_price` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品总价' AFTER `cart_num`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "pay_price",
                'findSql' => "show columns from `@table` like 'pay_price'",
                'sql' => "ALTER TABLE `@table`  ADD `pay_price` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际支付金额' AFTER `total_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "pay_postage",
                'findSql' => "show columns from `@table` like 'pay_postage'",
                'sql' => "ALTER TABLE `@table`  ADD `pay_postage` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付邮费' AFTER `pay_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "deduction_price",
                'findSql' => "show columns from `@table` like 'deduction_price'",
                'sql' => "ALTER TABLE `@table`  ADD `deduction_price` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '抵扣金额' AFTER `pay_postage`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "coupon_price",
                'findSql' => "show columns from `@table` like 'coupon_price'",
                'sql' => "ALTER TABLE `@table`  ADD `coupon_price` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠券金额' AFTER `deduction_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "promotions_price",
                'findSql' => "show columns from `@table` like 'promotions_price'",
                'sql' => "ALTER TABLE `@table` ADD `promotions_price` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '优惠活动优惠金额' AFTER `coupon_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "first_order_price",
                'findSql' => "show columns from `@table` like 'first_order_price'",
                'sql' => "ALTER TABLE `@table` ADD `first_order_price` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '首单优惠金额' AFTER `promotions_price`"
            ],
            [
                'code' => 320,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "change_price",
                'findSql' => "show columns from `@table` like 'change_price'",
                'sql' => "ALTER TABLE `@table` ADD `change_price` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '改价优惠金额' AFTER `first_order_price`"
            ],
            //数据
            [
                'code' => 320,
                'type' => -1,
                'table' => "system_config_tab",
                'sql' =>"INSERT INTO `@table` VALUES  (null, 0, 95, '运营配置', 'community_operate', 1, 0, '', 0, 0)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'get_remote_login_url'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'web_site'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'get_remote_login_url', 'text', 'input', 27, '', 1, '', 100, 0, '\"\"', '远程登录地址', '内嵌商城跳转h5页面链接携带（remote_token=远程用户生成的token）参数时，可自动登录商城，若remote_token为空的时候，本系统认定在外部系统中未登录，会跳转此地址进行登录', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_integral'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_integral', 'radio', '', 97, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '赠送积分', '', 100, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_integral_num'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_integral_num', 'text', 'number', 97, '', 1, '', 0, 0, '0', '单次赠送积分', '用户成功发布一条种草内容后赠送的积分数量', 90, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_integral_restrict'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_integral_restrict', 'text', 'number', 97, '', 1, '', 0, 0, '0', '每人每天积分上限', '用户每日通过发布种草内容可获得积分上限，为0时不限制', 80, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_exp'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_exp', 'radio', '', 97, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '赠送经验', '赠送经验', 70, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_exp_num'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_exp_num', 'text', 'number', 97, '', 1, '', 0, 0, '0', '单次赠送经验', '用户成功发布一条种草内容后赠送的经验数量', 60, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'community_exp_restrict'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'community_operate'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'community_exp_restrict', 'text', 'number', 97, '', 1, '', 100, 0, '0', '每人每天经验上限', '用户每日通过发布种草内容可获得的经验上限，为空时不限制', 50, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'alipay_verify_type'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'extract_set'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'alipay_verify_type', 'radio', '', 78, '1=>手动审核\n2=>无需审核', 1, '', 0, 0, '1', '审核方式', '用户提交支付宝提现申请后,是否需要商家操作审核', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_verify_type'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'extract_set'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_verify_type', 'radio', '', 78, '1=>手动审核\n2=>无需审核', 1, '', 0, 0, '1', '审核方式', '用户提交微信提现申请后,是否需要商家操作审核', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'brokerage_page_type'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'extract_set'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'brokerage_page_type', 'radio', '', 76, '1=>普通版\n2=>专业版', 1, '', 0, 0, '1', '分销中心', '普通版：展示详细分销数据。专业版：增加分销统计页面，帮助分销员快速了解分销情况。需于个人中心 > 我的服务模块增加(我的推广)页面链接', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'v3_pay_public_key'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'pay'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'v3_pay_public_key', 'text', 'input', 4, '', 1, '', 100, 0, '\"\"', 'v3支付公钥', 'v3支付公钥，新版本使用公钥请填写', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'v3_pay_public_pem'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'pay'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'v3_pay_public_pem', 'upload', '', 4, '', 3, '', 0, 0, '\"\"', 'v3支付公钥证书', 'v3支付公钥证书，使用新版本支付公钥上传此证书', 0, 1)"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "system_config",
                'sql' => "UPDATE  `@table` set `parameter` = '0=>线下手动转账\n1=>自动到微信零钱',  `info` = '微信到账方式', `desc` = '微信提现方式：手动线下转账，自动转账到零钱(需开通商家转账到零钱)' WHERE `menu_name` = 'brokerage_type'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "page_link",
                'sql' => "INSERT INTO `@table` VALUES (null, 5, 3, '代理商申请', '/pages/users/agent/apply', ' ', '/pages/users/agent/apply', 1, 0, 1626837579)"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "page_link",
                'sql' => "INSERT INTO `@table` VALUES (null, 5, 3, '分销申请', '/pages/users/distributor/apply', ' ', '/pages/users/distributor/apply', 1, 0, 1626837579)"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "page_link",
                'sql' => "INSERT INTO `@table` VALUES (null, 5, 3, '专业分销', '/pages/users/spreadData/index', ' ', '/pages/users/spreadData/index', 1, 0, 1626837579)"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "system_timer",
                'sql' => "INSERT INTO `@table` VALUES (null, '预售商品到期处理数据', 'auto_presale_product', 1, '预售商品到期处理数据上架还是下架状态', 1, '10', 0, 1735975461, 0, 1735975461)"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '10' WHERE `type` = 0 AND `tempkey` = '14403'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '15,16' WHERE `type` = 0 AND `tempkey` = '1470'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '5' WHERE `type` = 0 AND `tempkey` = '1481'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '3' WHERE `type` = 0 AND `tempkey` = '3801'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '22' WHERE `type` = 0 AND `tempkey` = '1458'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '12,24' WHERE `type` = 0 AND `tempkey` = '3098'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '11' WHERE `type` = 0 AND `tempkey` = '2727'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '21' WHERE `type` = 0 AND `tempkey` = '1128'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '7,18' WHERE `type` = 0 AND `tempkey` = '1451'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '8,17' WHERE `type` = 0 AND `tempkey` = '755'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '4' WHERE `type` = 0 AND `tempkey` = '1927'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '9' WHERE `type` = 0 AND `tempkey` = '335'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '13,14,23' WHERE `type` = 0 AND `tempkey` = '3353'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '22' WHERE `type` = 1 AND `tempkey` = '42984'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '5' WHERE `type` = 1 AND `tempkey` = '50439'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '7' WHERE `type` = 1 AND `tempkey` = '48058'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '22' WHERE `type` = 1 AND `tempkey` = 'OPENTM416122303'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '12' WHERE `type` = 1 AND `tempkey` = 'OPENTM409367318'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '13' WHERE `type` = 1 AND `tempkey` = 'OPENTM418350969'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '4' WHERE `type` = 1 AND `tempkey` = '43216'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '14' WHERE `type` = 1 AND `tempkey` = 'OPENTM410867947'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '10' WHERE `type` = 1 AND `tempkey` = 'OPENTM400590844'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '8,17' WHERE `type` = 1 AND `tempkey` = '42934'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '15' WHERE `type` = 1 AND `tempkey` = '51729'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '16' WHERE `type` = 1 AND `tempkey` = 'OPENTM403167119'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '9' WHERE `type` = 1 AND `tempkey` = 'OPENTM201661503'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '18' WHERE `type` = 1 AND `tempkey` = '46232'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "template_message",
                'sql' => "UPDATE `@table` set `notification_id` = '36' WHERE `type` = 1 AND `tempkey` = '25599'"
            ],
            [
                'code' => 320,
                'type' => -1,
                'table' => "system_menus",
                'sql' =>"INSERT INTO `@table` VALUES  (null, 9, 1, '', '用户导入记录', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/user/importRecord', '9', 1, '', 0, 'admin-user-import-record', 0)"
            ],

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
                'code' => 320,
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

            [
                'code' => 320,
                'type' => -1,
                'table' => "system_menus_relevance",
                'sql' => <<<SQL
INSERT INTO `@table`  (`id`, `menu_id`, `keyword`) VALUES 
(6, 1091, '首页'),
(7, 1091, '首页数据'),
(8, 1351, '网站地址'),
(9, 1351, '网站名称'),
(10, 1351, '网站缓存时间'),
(11, 1351, '备案号'),
(12, 1351, '后台设置'),
(15, 1351, '系统ICO图标'),
(16, 1351, '悬浮菜单'),
(17, 1351, '移动端登录LOGO'),
(18, 1351, '分享设置'),
(19, 1351, '密码设置'),
(20, 1351, '防火墙设置'),
(21, 1351, '远程登录设置'),
(22, 1351, '系统参数过滤'),
(23, 1598, '一号通登录'),
(24, 1598, '一号通账户'),
(25, 1599, '一号通配置'),
(26, 1599, '一号通APPID'),
(27, 1599, '配置一号通'),
(28, 1355, '采集商品配置'),
(29, 1355, '商品采集配置'),
(30, 1355, '一号通'),
(31, 1355, '99api'),
(32, 1355, '物流查询配置'),
(33, 1355, '物流配置'),
(34, 1355, '阿里云物流查询'),
(35, 1355, '电子面单'),
(36, 1355, '电子面单打印机设置'),
(37, 1355, '地图配置'),
(38, 1355, '地图key'),
(39, 1355, '腾讯地图key'),
(40, 1355, '短信配置'),
(41, 1355, '短信类型'),
(42, 1355, '短信签名'),
(43, 1355, '代码统计'),
(45, 1356, '存储设置'),
(46, 1356, '基础设置'),
(47, 1356, '基础配置'),
(48, 1356, '缩略图'),
(49, 1356, '本地存储'),
(50, 1356, '阿里云存储'),
(51, 1356, '腾讯云存储'),
(52, 1356, '七牛云存储'),
(53, 1356, '京东云存储'),
(54, 1356, '华为云存储'),
(55, 1356, '天翼云存储'),
(56, 1356, '水印'),
(57, 1356, '图片水印'),
(58, 1356, '存储空间'),
(59, 1356, '空间域名'),
(60, 1490, '同城配送'),
(61, 1490, '配送设置'),
(62, 1490, '第三方配送'),
(63, 1490, '自主配送'),
(64, 1490, '达达配送'),
(65, 1490, 'UU配送'),
(66, 1491, '配送记录'),
(67, 1491, '配送订单'),
(68, 1491, '配送平台'),
(69, 1491, '配送状态'),
(70, 1359, '发货设置'),
(71, 1359, '全场包邮'),
(72, 1359, '线下支付'),
(73, 1359, '到店自提'),
(74, 1359, '包邮设置'),
(75, 1359, '提货点设置'),
(76, 1359, '自提点设置'),
(77, 1359, '满额包邮'),
(78, 230, '运费模版'),
(79, 230, '指定包邮'),
(80, 230, '邮费设置'),
(81, 230, '配送区域'),
(82, 230, '指定不送达'),
(83, 145, '物流公司'),
(84, 145, '物流编码'),
(85, 145, '月结账号'),
(86, 145, '物流设置'),
(87, 229, '城市配置'),
(88, 229, '添加城市'),
(89, 229, '地区'),
(90, 229, '省市区'),
(91, 720, '配送员管理'),
(92, 720, '添加配送员'),
(93, 1586, '商品设置'),
(94, 1586, '警戒库存'),
(95, 1586, '预警库存'),
(96, 1586, '库存警戒'),
(97, 1353, '订单设置'),
(98, 1353, '订单取消设置'),
(99, 1353, '未支付时间设置'),
(100, 1353, '商品未支付设置'),
(101, 1353, '取消订单时间设置'),
(102, 1353, '次卡提醒'),
(103, 1353, '临期提醒'),
(104, 1353, '次卡临期提醒'),
(105, 1353, '自动收货时间'),
(106, 1353, '自动收货设置'),
(107, 1353, '自动默认好评时间设置'),
(108, 1353, '自动好评'),
(109, 1353, '售后设置'),
(110, 1353, '售后退款设置'),
(111, 1353, '退货地址设置'),
(112, 1353, '售后期限设置'),
(113, 1353, '售后退货设置'),
(114, 1354, '支付设置'),
(115, 1354, '微信支付'),
(116, 1354, '支付宝支付'),
(117, 1354, '线下支付'),
(118, 1354, '余额支付'),
(119, 1354, '微信支付证书'),
(120, 1354, '微信支付证书密钥'),
(121, 1354, '支付接口类型'),
(122, 1354, '支付公钥'),
(123, 1354, '支付私钥'),
(124, 1354, '支付应用Appid'),
(125, 1614, '打印机设置'),
(126, 1614, '小票打印'),
(127, 1614, '易联云'),
(128, 1614, '飞鹅云'),
(129, 1386, '隐私协议'),
(130, 1386, '用户协议'),
(131, 1386, '注销协议'),
(132, 1386, '供应商入住协议'),
(133, 1386, '代理商入住协议'),
(134, 19, '角色管理'),
(135, 19, '角色设置'),
(136, 19, '身份管理'),
(137, 19, '添加身份'),
(138, 19, '角色权限'),
(139, 20, '管理员列表'),
(140, 20, '添加管理员'),
(141, 21, '权限规则'),
(142, 21, '设置权限'),
(143, 21, '添加规则'),
(144, 21, '接口路径'),
(145, 21, '菜单'),
(146, 21, '关键词'),
(147, 21, '路由地址'),
(148, 1295, '消息设置'),
(149, 1295, '商城消息'),
(150, 1295, '通知会员'),
(151, 1295, '通知平台'),
(152, 1295, '消息通知'),
(153, 1295, '订阅消息'),
(154, 1295, '公众号模版消息'),
(155, 1295, '通知场景'),
(156, 1295, '小程序订阅'),
(157, 1362, '公众号配置'),
(158, 1362, '基础配置'),
(159, 1362, '公众号基础配置'),
(160, 1362, '公众号开发者信息'),
(161, 1362, '服务器配置'),
(162, 1362, '微信公众号'),
(163, 1362, '微信验证TOKEN'),
(164, 1362, '消息加解密'),
(165, 1362, '公众号分享二维码'),
(166, 1362, '推广海报'),
(167, 1362, '公众号关注二维码'),
(168, 1362, '关注公众号'),
(169, 109, '微信图文'),
(170, 109, '图文消息'),
(171, 114, '自动回复'),
(172, 114, '微信关注回复'),
(173, 114, '关键字回复'),
(174, 114, '无效关键字回复'),
(175, 114, '文字消息'),
(176, 114, '图片消息'),
(177, 114, '图文消息'),
(178, 114, '声音消息'),
(179, 1090, '微信会员卡券'),
(180, 1345, '小程序配置'),
(181, 1345, '小程序客服类型'),
(182, 1345, '内置客服系统'),
(183, 1345, '发货信息管理'),
(184, 1345, '手机号获取方式'),
(185, 1345, '小程序码'),
(186, 1345, '小程序源码包'),
(187, 1361, 'PC设置'),
(188, 1361, '友情链接'),
(189, 1361, '公安备案'),
(190, 1361, '微信开放平台'),
(191, 1379, '微信开放平台'),
(192, 1379, '移动应用APPID'),
(193, 1379, '移动应用AppSecret'),
(194, 111, '配置分类'),
(195, 111, '配置列表'),
(196, 111, '分类字段'),
(197, 112, '组合数据'),
(198, 112, '数据组'),
(199, 112, '数据列表'),
(200, 1287, '对外接口'),
(201, 1287, '接口账号'),
(202, 1287, 'ToKen'),
(203, 1287, '推送接口'),
(204, 1287, 'ToKen接口'),
(205, 1287, '推送'),
(206, 1287, '接口权限'),
(207, 1287, 'POST'),
(208, 1539, '接口信息'),
(209, 1539, '调用方式'),
(210, 1539, '调用示例'),
(211, 1539, '调用内容'),
(212, 1539, '接口名称'),
(213, 1539, '请求参数'),
(214, 1539, '返回参数'),
(215, 1539, '错误码'),
(216, 1539, 'GET'),
(217, 1539, 'POST'),
(218, 1539, 'PUT'),
(219, 1539, 'DEL'),
(220, 57, '清除缓存'),
(221, 57, '清除日志'),
(222, 57, '刷新缓存'),
(223, 57, '缓存清除'),
(224, 57, '缓存处理'),
(225, 47, '系统日志'),
(226, 47, '日志'),
(227, 47, '操作日志'),
(228, 47, '行为日志'),
(229, 64, '文件校验'),
(230, 66, '清除数据'),
(231, 66, '更换域名'),
(232, 66, '预热营销商品库存'),
(233, 66, '清除临时附件'),
(234, 66, '清除回收站商品'),
(235, 66, '清除商城数据'),
(236, 66, '清除订单数据'),
(237, 66, '清除客服数据'),
(238, 66, '清除微信数据'),
(239, 66, '清除内容分类'),
(240, 66, '清除用户数据'),
(241, 66, '清除所有附件'),
(242, 66, '清除系统记录'),
(243, 67, '数据库列表'),
(244, 67, '数据备份'),
(245, 67, '备份列表'),
(246, 67, '备份'),
(247, 67, '优化表'),
(248, 67, '修复表'),
(249, 67, '表名称'),
(250, 605, '商业授权'),
(251, 605, '授权码'),
(252, 605, '授权信息'),
(253, 605, '授权图片'),
(254, 605, '修改授权'),
(255, 1544, '定时任务'),
(256, 1544, '自动执行'),
(257, 1436, '用户设置'),
(258, 1436, '用户基础设置'),
(259, 1436, '默认头像'),
(260, 1436, '用户信息设置'),
(261, 1436, '登录注册'),
(262, 1436, '新人礼'),
(263, 1436, '注册有礼'),
(264, 1436, '手机号绑定'),
(265, 1436, '新人专享'),
(266, 1436, '新人礼规则'),
(267, 1436, '会员等级'),
(268, 1436, '激活有礼'),
(269, 1436, '会员等级有礼'),
(270, 1436, '会员卡激活'),
(271, 1436, '用户等级'),
(272, 1436, '付费会员启用'),
(273, 1436, '付费会员价'),
(274, 1368, '客服类型'),
(275, 1368, '企业微信客服'),
(276, 1368, '系统客服'),
(277, 1368, '客服设置'),
(278, 1368, '客服电话'),
(279, 1605, '社区功能'),
(280, 1605, '内容审核'),
(281, 1605, '图文审核'),
(282, 1605, '短视频审核'),
(283, 1605, '社区评论审核'),
(284, 687, '直播间管理'),
(285, 687, '添加直播间'),
(286, 687, '同步直播间'),
(287, 688, '直播商品'),
(288, 689, '主播管理'),
(289, 689, '添加主播'),
(290, 1389, '企微码'),
(291, 1389, '企微活码'),
(292, 1389, '企微二维码'),
(293, 1392, '欢迎语'),
(294, 1406, '同步企微员工'),
(295, 1419, '同步企微客户'),
(296, 1419, '非企微客户'),
(297, 1419, '企业微信客户'),
(298, 1422, '企微群发'),
(299, 1422, '群发消息'),
(300, 1422, '群发详情'),
(301, 1422, '发送统计'),
(302, 1431, '发布朋友圈'),
(303, 1431, '朋友圈列表'),
(304, 1431, '立即发送'),
(305, 1390, '企微群'),
(306, 1390, '企业微信群聊'),
(307, 1390, '企微群统计'),
(308, 1390, '企微群成员'),
(309, 1390, '企微群公告'),
(310, 1390, '企微群人数'),
(311, 1391, '自动拉群'),
(312, 1424, '客户群群发'),
(313, 1424, '群发消息'),
(314, 1424, '客户群接收群消息'),
(315, 1424, '群主发送群消息'),
(316, 1416, '企业微信基础配置'),
(317, 1416, '企业微信通讯录配置'),
(318, 1416, '企业微信客户设置'),
(319, 1416, '企业微信自建应用设置'),
(320, 39, '佣金提现'),
(321, 39, '提现申请'),
(322, 39, '佣金提现审核'),
(323, 1364, '查看发票'),
(324, 1364, '发票申请'),
(325, 1364, '发票记录'),
(326, 1364, '订单发票'),
(327, 1365, '发票功能'),
(328, 1365, '专用发票功能'),
(329, 1365, '发票设置'),
(330, 40, '余额充值记录'),
(331, 40, '充值退款'),
(332, 40, '充值金额'),
(333, 40, '用户充值记录'),
(335, 1384, '交易流水'),
(336, 1384, '交易金额'),
(337, 1384, '流水记录'),
(338, 38, '佣金记录'),
(339, 38, '用户佣金'),
(340, 38, '账户佣金'),
(341, 29, '分销员信息'),
(342, 29, '分销订单'),
(344, 29, '推广人列表'),
(347, 972, '分销等级'),
(348, 972, '返佣上浮比例设置'),
(349, 972, '等级任务'),
(350, 972, '返佣比例'),
(351, 28, '分销模式'),
(357, 28, '绑定用户'),
(358, 28, '绑定模式'),
(359, 28, '绑定时效'),
(360, 28, '分销海报'),
(361, 28, '分销中心'),
(362, 28, '佣金计算方案'),
(363, 28, '返佣比例设置'),
(364, 28, '佣金冻结'),
(365, 28, '冻结时间'),
(366, 28, '自购返佣'),
(367, 28, '推广用户返佣'),
(368, 28, '佣金提现设置'),
(369, 28, '提现手续费'),
(374, 1385, '分销说明'),
(375, 1385, '分销规则'),
(376, 1620, '申请列表'),
(377, 1620, '分销员申请'),
(378, 1620, '分销员申请审核'),
(379, 1609, '区域代理'),
(380, 1609, '邀请码'),
(382, 1610, '代理商'),
(384, 1611, '代理商申请'),
(385, 1611, '申请记录'),
(386, 1611, '申请审核'),
(387, 1613, '团队开关'),
(388, 1613, '代理商申请开关'),
(389, 1613, '团队配置'),
(390, 28, '分销层级'),
(391, 1453, '供应商订单');
SQL
            ],
        ];
        return $data;
    }
}
