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

namespace crmeb\services\express\storage;

use app\services\other\ExpressServices;
use crmeb\basic\BaseExpress;
use crmeb\exceptions\ApiException;
use crmeb\services\AccessTokenServeService;

/**
 * Class Express
 * @package crmeb\services\express\storage
 */
class Express extends BaseExpress
{
    //注册服务
    const EXPRESS_OPEN = 'v2/expr/open';
    //电子面单模版
    const EXPRESS_TEMP = 'v2/expr_dump/temp';
    //快递公司
    const EXPRESS_LIST = 'v2/expr/express';
    //快递查询
    const EXPRESS_QUERY = 'v2/expr/query';
    //面单打印
    const EXPRESS_DUMP = 'v2/expr/dump';
    //获取物流公司信息
    const SHIPMENT_KUAIDI_NUMS = 'v2/shipment/get_kuaidi_coms';
    //创建商家寄件订单
    const SHIPMENT_CREATE_ORDER = 'v2/shipment/create_order';
    //取消商家寄件
    const SHIPMENT_CANCEL_ORDER = 'v2/shipment/cancel_order';
    //获取商家寄件订单列表
    const SHIPMENT_INDEX = 'v2/shipment/index';
    //获取商家寄件订单预扣金额
    const SHIPMENT_PRICE = 'v2/shipment/price';

    /** 初始化
     * @param array $config
     * @return mixed|void
     */

    protected function initialize(array $config = [])
    {
        parent::initialize($config);
    }

    /**
     * 商家寄件获取快递公司
     * @return array|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/5/15
     */
    public function getKuaidiComs()
    {
        $list = $this->accessToken->httpRequest(self::SHIPMENT_KUAIDI_NUMS, [], 'GET');
        foreach ($list as &$item) {
            $item['code'] = $item['value'];
            $item['value'] = $item['label'];
            $num = 1;
            foreach ($item['list'] as &$value) {
                $value['title'] = $item['label'] . '模版' . $num;
                $num++;
            }
        }
        return $list;
    }

    /**
     * 商家寄件创建订单
     * @param array $data
     * @return array|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/5/15
     */
    public function shippmentCreateOrder(array $data)
    {
        $siid = sys_config('config_export_siid');
        $param = [
            'kuaidicom' => $data['kuaidicom'],
            'man_name' => $data['man_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'send_real_name' => $data['send_real_name'],
            'send_phone' => $data['send_phone'],
            'send_address' => $data['send_address'],
            'call_back_url' => sys_config('site_url') . '/api/order_call_back',
            'return_type' => $siid ? '10' : '20',
            'siid' => $siid,
            'tempid' => $data['temp_id'],
            'cargo' => $data['cargo'],
            'weight' => $data['weight'],
            'day_type' => $data['day_type'],
            'pickup_start_time' => $data['pickup_start_time'],
            'pickup_end_time' => $data['pickup_end_time'],
        ];
        return $this->accessToken->httpRequest(self::SHIPMENT_CREATE_ORDER, $param, 'post');
    }

    /**
     * 取消商家寄件订单
     * @param array $data
     * @return array|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/5/15
     */
    public function shipmentCancelOrder(array $data)
    {
        $param = [
            'task_id' => $data['task_id'],//快递100商家寄件任务id
            'order_id' => $data['order_id'],//快递100商家寄件发起的订单号。并不是系统中的订单号
            'cancel_msg' => $data['cancel_msg'],//取消原因
        ];
        return $this->accessToken->httpRequest(self::SHIPMENT_CANCEL_ORDER, $param);
    }

    /**
     * 获取商家寄件订单列表
     * @param array $data
     * @return array|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/5/15
     */
    public function getShipmentOrderList(array $data)
    {
        $param = [
            'kuaidi_num' => $data['kuaidi_num'] ?? '',
            'courier_name' => $data['courier_name'] ?? '',
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10,
        ];

        return $this->accessToken->httpRequest(self::SHIPMENT_INDEX, $param, 'GET');
    }

    /**
     * @param array $data
     * @return array|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/6/16
     */
    public function getPrice(array $data)
    {
        if (empty($data['kuaidicom'])) {
            throw new ApiException('快递编码必须填写');
        }
        if (empty($data['send_address'])) {
            throw new ApiException('寄件地址必须填写');
        }
        if (empty($data['address'])) {
            throw new ApiException('收件地址必须填写');
        }
        $param = [
            'kuaidicom' => $data['kuaidicom'],
            'send_address' => $data['send_address'],
            'address' => $data['address'] ?? '',
            'weight' => $data['weight'] ?? '',
            'service_type' => $data['service_type'] ?? '',
        ];

        return $this->accessToken->httpRequest(self::SHIPMENT_PRICE, $param);
    }

    /**
     * 开通物流服务
     * @return bool|mixed
     */
    public function open()
    {
        return $this->accessToken->httpRequest(self::EXPRESS_OPEN, []);
    }

    /**
     * 获取电子面单模版
     * @param $com 快递公司编号
     * @param int $page
     * @param int $limit
     * @return bool|mixed
     */
    public function temp(string $com)
    {
        $param = [
            'com' => $com
        ];
        $header = [];
        if (!sys_config('config_export_siid')) {
            $header = ['version:v1.1'];
        }
        return $this->accessToken->httpRequest(self::EXPRESS_TEMP, $param, 'GET', true, $header);
    }

    /**
     * 获取物流公司列表
     * @param int $type 快递类型：1，国内运输商；2，国际运输商；3，国际邮政
     * @return bool|mixed
     */
    public function express(int $type = 0, int $page = 0, int $limit = 20)
    {
        if ($type) {
            $param = [
                'type' => $type,
                'page' => $page,
                'limit' => $limit
            ];
        } else {
            $param = [];
        }

        return $this->accessToken->httpRequest(self::EXPRESS_LIST, $param,'GET');
    }

    /**
     * 查询物流信息
     * @param $com
     * @param $num
     * @return bool|mixed
     * @return 是否签收 ischeck
     * @return 物流状态：status 0在途，1揽收，2疑难，3签收，4退签，5派件，6退回，7转单，10待清关，11清关中，12已清关，13清关异常，14收件人拒签
     * @return 物流详情 content
     */
    public function query(string $num, string $com = '')
    {
        $param = [
            'com' => $com,
            'num' => $num
        ];
        if ($com === null) {
            unset($param['com']);
        }
        return $this->accessToken->httpRequest(self::EXPRESS_QUERY, $param);
    }

    /**
     * 电子面单打印
     * @param array $data 必需参数: com(快递公司编码)、to_name(寄件人)、to_tel（寄件人电话）、to_addr（寄件人详细地址）、from_name（收件人）、from_tel（收件人电话)、from_addr（收件人地址）、temp_id（电子面单模板ID）、siid（云打印机编号）、count（商品数量）
     * @return bool|mixed
     */
    public function dump($data)
    {
        $param = $data;
        $param['com'] = $data['com'] ?? '';
        if (!$param['com']) throw new ApiException('快递公司编码缺失');
        $param['to_name'] = $data['to_name'] ?? '';
        $param['to_tel'] = $data['to_tel'] ?? '';
//        $param['order_id'] = $data['order_id'] ?? '';
        $param['to_addr'] = $data['to_addr'] ?? '';
        if (!$param['to_addr'] || !$param['to_tel'] || !$param['to_name']) throw new ApiException('寄件人信息缺失');
        $param['from_name'] = $data['from_name'] ?? '';
        $param['from_tel'] = $data['from_tel'] ?? '';
        $param['from_addr'] = $data['from_addr'] ?? '';
        if (!$param['from_name'] || !$param['from_tel'] || !$param['from_addr']) throw new ApiException('收件人信息缺失');
        $param['temp_id'] = $data['temp_id'] ?? '';
        if (!$param['temp_id']) {
            throw new ApiException('电子面单模板ID缺失');
        }
        $param['siid'] = sys_config('config_export_siid');
//        if (!$param['siid']) {
//            throw new ApiException('云打印机编号缺失');
//        }
        $param['count'] = $data['count'] ?? '';
        $param['cargo'] = $data['cargo'] ?? '';

        if (!$param['count']) {
            throw new ApiException('商品数量缺失');
        }

        /** @var ExpressServices $expressServices */
        $expressServices = app()->make(ExpressServices::class);
        $expressData = $expressServices->getOneByWhere(['code' => $param['com']])->toArray();
        if (isset($data['cargo'])) $param['cargo'] = $data['cargo'];
        if ($expressData['partner_id'] == 1) $param['partner_id'] = $expressData['account'];
        if ($expressData['partner_key'] == 1) $param['partner_key'] = $expressData['key'];
        if ($expressData['net'] == 1) $param['net'] = $expressData['net_name'];
        if ($expressData['check_man'] == 1) $param['checkMan'] = $expressData['courier_name'];
        if ($expressData['partner_name'] == 1) $param['partnerName'] = $expressData['customer_name'];
        if ($expressData['is_code'] == 1) $param['code'] = $expressData['code_name'];
        //修改没有打印机的时候print_type=IMAGE，就会返回面单图片
        $header = [];
        if (!$data['siid']) {
            $param['print_type'] = 'IMAGE';
            $header = ['version:v1.1'];
        }
        return $this->accessToken->httpRequest(self::EXPRESS_DUMP, $param, 'POST', true, $header);
    }

}
