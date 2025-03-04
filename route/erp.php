<?php

use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Route;

/**
 * erp 接口
 */
Route::group('erpapi', function () {

    // 授权相关
    Route::get('auth', 'Auth/auth')->name('auth'); //获取授权地址
    Route::get('auth/call_back', 'Auth/authCallBack')->name('authCallBack'); //授权回调
    Route::get('auth/access_token', 'Auth/accessToken')->name('accessToken'); //获取token

    // 商品相关
    Route::get('product/syncProduct', 'Product/syncProduct')->name('syncProduct'); //获取商品

    // 库存相关
    Route::post('stock/callback','Stock/stockCallback')->name('stockCallback'); //库存回调

    // 订单相关
    Route::post('order/deliver_callback', 'Order/deliverCallback')->name('deliverCallback'); //订单发货回调
    Route::post('order/cancel_callback', 'Order/cancelCallback')->name('cancelCallback'); //订单取消回调
    Route::post('refund/receive_callback', 'Order/receiveCallback')->name('receiveCallback'); //售后收货回调

})->prefix('erp.')->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);
