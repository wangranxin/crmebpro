<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/9/12
 */

namespace app\controller\admin;

use app\jobs\user\UserBrokerageJob;
use app\services\agent\AgentLevelServices;
use app\services\community\CommunityCommentServices;
use app\services\community\CommunityServices;
use app\services\message\sms\SmsSendServices;
use app\services\order\StoreOrderTakeServices;
use app\services\other\CityAreaServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserServices;
use crmeb\services\wechat\Work;
use think\annotation\Inject;
use think\helper\Str;


class Test
{

    /**
     * @var StoreProductServices
     */
    #[Inject]
    protected StoreProductServices $services;

    public function index()
    {
//        app()->make(StoreOrderTakeServices::class)->autoTakeOrder();
//        app()->make(StoreProductServices::class)->autoPresaleProduct();
//        app()->make(CityAreaServices::class)->getCityId('陕西省','西安市','长安区');

//        app()->make(UserBrokerageServices::class)->contribute(1);

//        UserBrokerageJob::dispatchDo('syncOldBrokeragePrice');
//        $phone = '18161705878';
//        $template = 'ORDER_DELIVER_SUCCESS';
//        $data = ['code' => 123456];
//        /** @var SmsSendServices $smsServices */
//        $smsServices = app()->make(SmsSendServices::class);
//        $smsServices->send(true, $phone, $data, $template);
    }

    public function rule()
    {
        $this->app = app();
        $rule = request()->get('rule', 'storeapi');
        $this->app->route->setTestMode(true);
        $this->app->route->clear();


        $path = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;


        $files = is_dir($path) ? scandir($path) : [];

        foreach ($files as $file) {
            if (strpos($file, '.php')) {
                include $path . $file;
            }
        }

        $ruleList = $this->app->route->getRuleList();

        $ruleNewList = [];
        foreach ($ruleList as $item) {
            if (Str::contains($item['rule'], $rule)) {
                $ruleNewList[] = $item;
            }
        }
        foreach ($ruleNewList as $key => &$value) {
            $only = $value['option']['only'] ?? [];
            $route = is_string($value['route']) ? explode('/', $value['route']) : [];
            $value['route'] = is_string($value['route']) ? $value['route'] : '';
            $action = $route[count($route) - 1] ?? null;
            if ($only && $action && !in_array($action, $only)) {
                unset($ruleNewList[$key]);
            }
            $except = $value['option']['except'] ?? [];
            if ($except && $action && in_array($action, $except)) {
                unset($ruleNewList[$key]);
            }
        }
        echo "<html lang=\"zh-CN\">
<head>
    <title>路由地址列表</title>
</head>
<link rel='stylesheet' type='text/css' href='https://www.layuicdn.com/layui/css/layui.css' />
<body>
<div style='margin: 20px'>
<fieldset class=\"layui-elem-field layui-field-title\" style=\"margin-top: 20px;\">
  <legend>路由地址列表</legend>
</fieldset>
<div class=\"layui-form\">
  <table class=\"layui-table\">
    <thead>
      <tr>
        <th>请求方式</th>
        <th>接口地址</th>
        <th>接口名称</th>
        <th>接口方法</th>
      </tr>
    </thead>
    <tbody>
  ";
        $allAction = ['delete', 'index', 'update', 'edit', 'save', 'create', 'read'];
        foreach ($ruleNewList as $route) {
            $option = $route['option']['real_name'] ?? null;
            if (is_array($option)) {
                foreach ($allAction as $action) {
                    if (Str::contains($route['route'], $action)) {
                        $real_name = $option[$action] ?? '';
                    }
                }
            } else {
                $real_name = $option;
            }
            $rule = $route['rule'];
            echo "<tr>
<td>$route[method]</td>
<td>" . htmlspecialchars($rule) . "</td>
<td>$real_name</td>
<td>$route[route]</td>
</tr>";
        }
        echo "</table></div></div>";
    }
}
