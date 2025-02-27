<?php


use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\api\AuthTokenMiddleware;
use app\http\middleware\api\BlockerMiddleware;
use app\http\middleware\api\ClientMiddleware;
use app\http\middleware\InstallMiddleware;
use app\http\middleware\StationOpenMiddleware;
use app\http\middleware\api\CommunityOpenMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

/**
 * 用户端路由配置
 */
Route::group('api', function () {

    Route::any('order_call_back', 'v1.order.StoreOrder/callBack');//商家寄件回调
    Route::any('wechat/serve', 'v1.wechat.Wechat/serve');//公众号服务
    Route::any('wechat/miniServe', 'v1.wechat.Wechat/miniServe');//小程序服务
    Route::any('work/serve', 'v1.wechat.Wechat/work');//企业微信服务
    Route::any('pay/notify/:type', 'v1.Pay/notify');//支付回调
    Route::any('pay/mchNotify/:type', 'v1.Pay/mchNotify');//商户转账回调
    Route::any('city_delivery/notify', 'v1.CityDelivery/notify');//UU、达达回调
    Route::get('get_script', 'v1.PublicController/getScript');//统计代码
    Route::get('get_copyright', 'v1.Common/getCopyright');//获取版权
    Route::get('ali_pay', 'v1.order.StoreOrder/aliPay')->name('aliPay');// 支付宝复制链接支付
    Route::get('community/config', 'v1.community.Community/getConfig')->name('getConfig');//获取社区配置
    //登录类
    Route::group(function () {
        //apple快捷登陆
        Route::post('apple_login', 'v1.Login/appleLogin')->name('appleLogin');
        //账号密码登录
        Route::post('login', 'v1.Login/login')->name('login');
        // 获取发短信的key
        Route::get('verify_code', 'v1.Login/verifyCode')->name('verifyCode');
        //手机号登录
        Route::post('login/mobile', 'v1.Login/mobile')->name('loginMobile');
        //图片验证码
        Route::get('sms_captcha', 'v1.Login/captcha')->name('captcha');
        //验证码发送
        Route::post('register/verify', 'v1.Login/verify')->name('registerVerify');
        //手机号注册
        Route::post('register', 'v1.Login/register')->name('register');
        //手机号修改密码
        Route::post('register/reset', 'v1.Login/reset')->name('registerReset');
        // 绑定手机号(静默授权 还未有用户信息)
        Route::post('binding', 'v1.Login/binding_phone')->name('bindingPhone');
        //获取图形验证码
        Route::get('ajcaptcha', 'v1.Login/ajcaptcha')->name('ajcaptcha');
        //图形验证
        Route::post('ajcheck', 'v1.Login/ajcheck')->name('ajcheck');
        //远程登录
        Route::get('remote_register', 'v1.Login/remoteRegister')->name('remoteRegister');

    })->middleware(StationOpenMiddleware::class);

    //无需授权接口
    Route::group(function () {

        Route::get('geoLbscoder', 'v1.PublicController/geoLbscoder')->name('geoLbscoder');//经纬度转位置信息

        Route::get('city', 'v2.PublicController/city')->name('city');//增加省市区

        Route::get('site_config', 'v1.PublicController/getSiteConfig')->name('getSiteConfig');//获取网站配置

        Route::get('navigation/[:template_name]', 'v1.diy.Diy/getNavigation')->name('getNavigation');//获取底部导航

        Route::get('search/hot_keyword', 'v1.PublicController/hotKeywords')->name('hotKeyword');//热门搜索关键字获取
        Route::get('search/keyword', 'v1.PublicController/searchWords')->name('searchKeyword');//搜索关键字关联

        Route::get('category', 'v1.product.StoreProductCategory/category')->name('category');//商品分类类
        Route::get('level_category', 'v1.product.StoreProductCategory/levelCategory')->name('levelCategory');//商品同级所以分类
        Route::get('category_version', 'v1.product.StoreProductCategory/getCategoryVersion')->name('getCategoryVersion');//商品分类类版本
        Route::get('reply/list/:id', 'v1.product.StoreProductReply/reply_list')->name('replyList');//商品评价列表
        Route::get('reply/config/:id', 'v1.product.StoreProductReply/reply_config')->name('replyConfig');//商品评价数量和好评度

        Route::get('user_agreement/[:type]', 'v1.PublicController/getUserAgreement')->name('getUserAgreement')->middleware(AuthTokenMiddleware::class, false);//获取用户协议
        Route::get('agreement/[:type]', 'v1.PublicController/getAgreement')->name('getUserAgreement')->middleware(AuthTokenMiddleware::class, false);//获取用户协议

        Route::get('get_open_adv', 'v1.PublicController/getOpenAdv')->name('getOpenAdv');//首页开屏广告

    })->middleware(StationOpenMiddleware::class);

    //授权不通过,不会抛出异常继续执行
    Route::group(function () {
        //公共类
        Route::get('index', 'v1.PublicController/index')->name('index');//首页
        Route::get('menu/user', 'v1.PublicController/menu_user')->name('menuUser');//个人中心菜单
        Route::get('menu/date', 'v1.PublicController/menu_user_data')->name('menuUserData');//个人中心数据
        Route::get('get_qrcode/:type/:id', 'v1.other.Qrcode/getQrcode')->name('getQrcode');//获取分销二维码

        //商品类
        Route::get('presale/list', 'v1.product.StoreProduct/presaleList')->name('presaleList');//预售商品列表
        Route::get('search/recommend/:type', 'v1.product.StoreProduct/searchRecommendList')->name('searchRecommendList');//搜索页推荐商品列表
        Route::get('search/filter', 'v1.product.StoreProduct/searchFilter')->name('searchFilter');//商品分类活动、标签、品牌筛选参数
        Route::get('brand', 'v1.product.StoreProduct/brand')->name('brand');//品牌列表

        //商品榜单
        Route::get('product/rank/category', 'v1.product.StoreProductRank/rankCategory')->name('RankCategory');//绑定分类列表
        Route::get('product/rank/:type', 'v1.product.StoreProductRank/rankList')->name('RankList');//榜单商品列表

        Route::post('image_base64', 'v1.PublicController/get_image_base64')->name('getImageBase64');// 获取图片base64
        Route::get('product/detail/recommend/:id', 'v1.product.StoreProduct/recommend')->name('productRecommend');//商品详情推荐商品
        Route::get('product/detail/activity/:id', 'v1.product.StoreProduct/activity')->name('productActivity');//商品详情关联活动
        Route::get('product/detail/:id/[:type]', 'v1.product.StoreProduct/detail')->name('detail');//商品详情
        Route::get('product/detail_content/:id/', 'v1.product.StoreProduct/detailContent')->name('detailContent');//商品详情内容
        Route::get('groom/list/:type', 'v1.product.StoreProduct/groom_list')->name('groomList');//获取首页推荐不同类型商品的轮播图和商品
        Route::get('products', 'v1.product.StoreProduct/lst')->name('products');//商品列表
        Route::get('product/hot', 'v1.product.StoreProduct/product_hot')->name('productHot');//为你推荐
        Route::get('reply/comment/:id', 'v1.product.StoreProductReply/commentList')->name('commentList');//评价回复列表

        //文章分类类
        Route::get('article/category/list', 'v1.publics.ArticleCategory/lst')->name('articleCategoryList');//文章分类列表
        //文章类
        Route::get('article/list/:cid', 'v1.publics.Article/lst')->name('articleList');//文章列表
        Route::get('article/like/:id', 'v1.publics.Article/userArticleLikes')->name('userArticleLikes');//文章点赞
        Route::get('article/details/:id', 'v1.publics.Article/details')->name('articleDetails');//文章详情
        Route::get('article/hot/list', 'v1.publics.Article/hot')->name('articleHotList');//文章 热门
        Route::get('article/new/list', 'v1.publics.Article/new')->name('articleNewList');//文章 最新
        Route::get('article/banner/list', 'v1.publics.Article/banner')->name('articleBannerList');//文章 banner
        //活动---秒杀
        Route::get('seckill/index', 'v1.activity.StoreSeckill/index')->name('seckillIndex');//秒杀商品时间区间
        Route::get('seckill/list/:time', 'v1.activity.StoreSeckill/lst')->name('seckillList');//秒杀商品列表
        Route::get('seckill/detail/:id/[:time]', 'v1.activity.StoreSeckill/detail')->name('seckillDetail');//秒杀商品详情
        Route::get('seckill/detail_code/:id', 'v1.activity.StoreSeckill/detailCode')->name('seckilldetailCode');//秒杀商品二维码
        //活动---砍价
        Route::get('bargain/config', 'v1.activity.StoreBargain/config')->name('bargainConfig');//砍价商品列表配置
        Route::get('bargain/list', 'v1.activity.StoreBargain/lst')->name('bargainList');//砍价商品列表
        Route::get('bargain/detail/:id', 'v1.activity.StoreBargain/detail')->name('bargainDetail');//砍价商品详情
        //活动---拼团
        Route::get('combination/list', 'v1.activity.StoreCombination/lst')->name('combinationList');//拼团商品列表
        Route::get('combination/detail/:id', 'v1.activity.StoreCombination/detail')->name('combinationDetail');//拼团商品详情
        Route::get('combination/detail_code/:id', 'v1.activity.StoreCombination/detailCode')->name('detailCode');//拼团商品详情二维码
        //用户类
        Route::get('user/activity', 'v1.user.User/activity')->name('userActivity');//活动状态

        //微信
        Route::get('wechat/config', 'v1.wechat.Wechat/config')->name('wechatConfig');//微信 sdk 配置
        Route::get('wechat/auth', 'v1.wechat.Wechat/auth')->name('wechatAuth');//微信授权
        Route::post('wechat/app_auth', 'v1.wechat.Wechat/appAuth')->name('appAuth');//微信APP授权

        //小程序登陆
        Route::post('wechat/mp_auth', 'v1.wechat.Routine/mp_auth')->name('mpAuth');//小程序登陆
        Route::get('wechat/get_logo', 'v1.Common/getLogo')->name('getLogo');//登陆页面logo
        Route::get('wechat/teml_ids', 'v1.wechat.Routine/teml_ids')->name('wechatTemlIds');//小程序订阅消息
        Route::get('wechat/live', 'v1.wechat.Routine/live')->name('wechatLive');//小程序直播列表
        Route::get('wechat/livePlaybacks/:id', 'v1.wechat.Routine/livePlaybacks')->name('livePlaybacks');//小程序直播回放

        //物流公司
        Route::get('logistics', 'v1.PublicController/logistics')->name('logistics');//物流公司列表

        //分享配置
        Route::get('share', 'v1.PublicController/share')->name('share');//分享配置

        //优惠券
        Route::get('coupons', 'v1.activity.StoreCoupons/lst')->name('couponsList'); //可领取优惠券列表

        //获取关注微信公众号海报
        Route::get('wechat/follow', 'v1.wechat.Wechat/follow')->name('Follow');
        //用户是否关注
        Route::get('subscribe', 'v1.user.User/subscribe')->name('Subscribe');
        //门店列表
        Route::get('store_list', 'v1.PublicController/store_list')->name('storeList');
        //获取城市列表
        Route::get('city_list', 'v1.PublicController/city_list')->name('cityList');
        //获取附近最近门店
        Route::get('nearby_store', 'v1.store.Store/nearbyStore')->name('nearbyStore');

        Route::get('pink', 'v1.activity.StoreCombination/pink')->name('pinkData');
        Route::get('combination/banner_list', 'v1.activity.StoreCombination/banner_list')->name('combinationBannerList');//拼团列表轮播图

        Route::post('user/set_visit', 'v1.user.User/set_visit')->name('setVisit');// 添加用户访问记录
        Route::get('copy_words', 'v1.PublicController/copy_words')->name('copyWords');// 复制口令接口

        //活动---积分商城
        Route::get('store_integral/index', 'v1.activity.StoreIntegral/index')->name('storeIntegralIndex');//积分商城首页数据
        Route::get('store_integral/category', 'v1.activity.StoreIntegral/category')->name('storeIntegralCategory');//积分商城分类列表
        Route::get('store_integral/list', 'v1.activity.StoreIntegral/lst')->name('storeIntegralList');//积分商品列表
        Route::get('store_integral/detail/:id', 'v1.activity.StoreIntegral/detail')->name('storeIntegralDetail');//积分商品详情

        //优惠套餐列表
        Route::get('store_discounts/list/:product_id', 'v1.activity.StoreDiscounts/index');

        //获取客服类型
        Route::get('get_customer_type', 'v2.PublicController/getCustomerType')->name('getCustomerType');//获取客服类型
        Route::get('user/service/get_adv', 'v1.user.StoreService/getKfAdv')->name('userServiceGetKfAdv');//获取客服页面广告

        //商品类
        Route::get('product/code/:id', 'v1.product.StoreProduct/code')->name('productCode');//商品分享二维码 推广员
        Route::get('product/short_link/:id', 'v1.product.StoreProduct/shortLink')->name('shortLink');//小程序商品短链


    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, false);

    //会员授权接口
    Route::group(function () {
        //用户修改手机号
        Route::post('user/updatePhone', 'v1.Login/update_binding_phone')->name('updateBindingPhone');
        //设置登录code
        Route::post('user/code', 'v1.user.StoreService/setLoginCode')->name('setLoginCode');
        //查看code是否可用
        Route::get('user/code', 'v1.Login/setLoginKey')->name('getLoginKey');
        //用户绑定手机号
        Route::post('user/binding', 'v1.Login/user_binding_phone')->name('userBindingPhone');
        Route::get('logout', 'v1.Login/logout')->name('logout');// 退出登录
        Route::post('switch_h5', 'v1.Login/switch_h5')->name('switch_h5');// 切换账号


        //保存商品评价回复
        Route::post('reply/comment/:id', 'v1.product.StoreProductReply/replyComment')->name('replyComment');
        //获取评论详情
        Route::get('reply/info/:id', 'v1.product.StoreProductReply/replyInfo')->name('replyInfo');
        //评论回复点赞
        Route::post('reply/praise/:id', 'v1.product.StoreProductReply/commentPraise')->name('commentPraise');
        //取消评论回复点赞
        Route::post('reply/un_praise/:id', 'v1.product.StoreProductReply/unCommentPraise')->name('unCommentPraise');
        //评论点赞
        Route::post('reply/reply_praise/:id', 'v1.product.StoreProductReply/replyPraise')->name('replyPraise');
        //取消评论点赞
        Route::post('reply/un_reply_praise/:id', 'v1.product.StoreProductReply/unReplyPraise')->name('unReplyPraise');


        //公共类
        Route::post('upload/image', 'v1.PublicController/uploadImage')->name('uploadImage');//文件上传
        Route::post('upload/video', 'v1.PublicController/uploadVideo')->name('uploadVideo');//视频上传
        //用户类 客服聊天记录
        Route::get('user/service/list', 'v1.user.StoreService/lst')->name('userServiceList');//客服列表
        Route::get('user/service/record', 'v1.user.StoreService/record')->name('userServiceRecord');//客服聊天记录
        Route::post('user/service/feedback', 'v1.user.StoreService/saveFeedback')->name('saveFeedback');//保存客服反馈信息
        Route::get('user/service/feedback', 'v1.user.StoreService/getFeedbackInfo')->name('getFeedbackInfo');//获得客服反馈头部信息
        Route::get('user/record', 'v1.user.StoreService/recordList')->name('recordList');//获取用户和客服的消息列表

        //用户类  用户
        Route::get('user', 'v1.user.User/user')->name('user');//个人中心
        Route::post('user/spread', 'v1.user.User/spread')->name('userSpread');//静默绑定授权
        Route::post('user/edit', 'v1.user.User/edit')->name('userEdit');//用户修改信息
        Route::get('user/balance', 'v1.user.User/balance')->name('userBalance');//用户资金统计
        Route::get('userinfo', 'v1.user.User/userinfo')->name('userinfo');// 用户信息
        Route::get('user/rand_code', 'v1.user.User/randCode')->name('randCode');//查看用户code
        Route::get('user/visit_list', 'v1.user.User/visitList')->name('visitList');//商品浏览列表
        Route::delete('user/visit', 'v1.user.User/visitDelete')->name('visitDelete');//商品浏览记录删除
        Route::get('cancel/user', 'v1.user.User/cancelUser')->name('cancelUser');// 用户注销

        //用户类  地址
        Route::get('address/detail/:id', 'v1.user.UserAddress/address')->name('address');//获取单个地址
        Route::get('address/list', 'v1.user.UserAddress/address_list')->name('addressList');//地址列表
        Route::post('address/default/set', 'v1.user.UserAddress/address_default_set')->name('addressDefaultSet');//设置默认地址
        Route::get('address/default', 'v1.user.UserAddress/address_default')->name('addressDefault');//获取默认地址
        Route::post('address/edit', 'v1.user.UserAddress/address_edit')->name('addressEdit');//修改 添加 地址
        Route::post('address/del', 'v1.user.UserAddress/address_del')->name('addressDel');//删除地址
        //用户类 收藏
        Route::get('collect/user', 'v1.user.UserCollect/collect_user')->name('collectUser');//收藏商品列表
        Route::post('collect/add', 'v1.user.UserCollect/collect_add')->name('collectAdd');//添加收藏
        Route::post('collect/del', 'v1.user.UserCollect/collect_del')->name('collectDel');//取消收藏
        Route::post('collect/all', 'v1.user.UserCollect/collect_all')->name('collectAll');//批量添加收藏

        Route::get('brokerage_rank', 'v1.user.UserBrokerage/brokerage_rank')->name('brokerageRank');//佣金排行
        Route::get('rank', 'v1.user.User/rank')->name('rank');//推广人排行
        //用戶类 分享
        Route::post('user/share', 'v1.PublicController/user_share')->name('user_share');//记录用户分享
        Route::get('user/share/words', 'v1.PublicController/copy_share_words')->name('user_share_words');//关键字分享
        //用户类 点赞
//    Route::post('like/add', 'user.User/like_add')->name('likeAdd');//添加点赞
//    Route::post('like/del', 'user.User/like_del')->name('likeDel');//取消点赞
        //用户类 签到
        Route::get('sign/config', 'v1.user.UserSign/sign_config')->name('signConfig');//签到配置
        Route::get('sign/list', 'v1.user.UserSign/sign_list')->name('signList');//签到列表
        Route::get('sign/month', 'v1.user.UserSign/sign_month')->name('signIntegral');//签到列表（年月）
        Route::post('sign/user', 'v1.user.UserSign/sign_user')->name('signUser');//签到用户信息
        Route::post('sign/integral', 'v1.user.UserSign/sign_integral')->middleware(BlockerMiddleware::class)->name('signIntegral');//签到
        Route::get('sign/remind/:status', 'v1.user.UserSign/sign_remind')->name('signRemind');//用户设置签到提醒
        Route::get('sign/calendar', 'v1.user.UserSign/sign_calendar')->name('signCalendar');//日历数据
        //优惠券类
        Route::post('coupon/receive', 'v1.activity.StoreCoupons/receive')->middleware(BlockerMiddleware::class)->name('couponReceive'); //领取优惠券
        Route::post('coupon/receive/batch', 'v1.activity.StoreCoupons/receive_batch')->name('couponReceiveBatch'); //批量领取优惠券
        Route::get('coupons/user/num', 'v1.activity.StoreCoupons/userCount')->name('userCount');//我的优惠券数量
        Route::get('coupons/user/:types', 'v1.activity.StoreCoupons/user')->name('couponsUser');//用户已领取优惠券
        Route::get('coupons/order/:price', 'v1.activity.StoreCoupons/order')->name('couponsOrder');//优惠券 订单列表
        //购物车类
        Route::get('cart/list', 'v1.order.StoreCart/lst')->name('cartList'); //购物车列表
        Route::post('cart/compute', 'v1.order.StoreCart/computeCart')->name('computeCart'); //购物车列表重新计算
        Route::post('cart/add', 'v1.order.StoreCart/add')->middleware(BlockerMiddleware::class)->name('cartAdd'); //购物车添加
        Route::post('cart/del', 'v1.order.StoreCart/del')->name('cartDel'); //购物车删除
        Route::post('cart/num', 'v1.order.StoreCart/num')->name('cartNum'); //购物车 修改商品数量
        Route::get('cart/count', 'v1.order.StoreCart/count')->name('cartCount'); //购物车 获取数量
        //订单类
        Route::post('order/check_shipping', 'v1.order.StoreOrder/checkShipping')->name('checkShipping'); //检测是否显示快递和自提标签
        Route::post('order/confirm', 'v1.order.StoreOrder/confirm')->name('orderConfirm'); //订单确认
        Route::post('order/computed/:key', 'v1.order.StoreOrder/computedOrder')->name('computedOrder'); //计算订单金额
        Route::post('order/create/:key', 'v1.order.StoreOrder/create')->name('orderCreate')->middleware(BlockerMiddleware::class); //订单创建
        Route::get('order/cashier/:orderId/[:type]', 'v1.order.StoreOrder/cashier')->name('orderCashier'); //订单收银台
        Route::get('order/data', 'v1.order.StoreOrder/data')->name('orderData'); //订单统计数据
        Route::get('order/list', 'v1.order.StoreOrder/lst')->name('orderList'); //订单列表
        Route::get('order/detail/:uni', 'v1.order.StoreOrder/detail')->name('orderDetail'); //订单详情
        Route::post('order/prize/:orderId', 'v1.order.StoreOrder/getOrderPrize');//获取订单下单奖励
        Route::get('order/write/records/:id', 'v1.order.StoreOrder/writeOffRecords')->name('writeOffRecords'); //订单核销记录
        Route::post('order/cancel', 'v1.order.StoreOrder/cancel')->name('orderCancel'); //订单取消
        Route::get('delivery_order/detail/:id', 'v1.order.StoreOrder/deliveryOrderDetail')->name('deliveryOrderDetail'); //配送订单详情

        //订单售后
        Route::get('order/refund/reason', 'v1.order.StoreOrder/refund_reason')->name('orderRefundReason'); //订单退款理由
        Route::get('order/refund/cart_info/:id', 'v1.order.StoreOrder/refundCartInfo')->name('StoreOrderRefundCartInfo');//获取退款商品列表
        Route::post('order/refund/cart_info', 'v1.order.StoreOrder/refundCartInfoList')->name('StoreOrderRefundCartInfoList');//获取退款商品列表
        Route::post('order/refund/apply/:id', 'v1.order.StoreOrder/applyRefund')->name('StoreOrderApplRefund');//订单申请退款V2
        Route::post('order/refund/verify', 'v1.order.StoreOrder/refund_verify')->middleware(BlockerMiddleware::class)->name('orderRefundVerify'); //订单申请退款
        Route::post('order/refund/express', 'v1.order.StoreOrder/refund_express')->name('orderRefundExpress'); //退货退款填写订单号
        Route::get('order/refund/list', 'v1.order.StoreOrderRefund/lst')->name('orderRefundList'); //售后订单列表
        Route::get('order/refund/detail/:uni', 'v1.order.StoreOrderRefund/detail')->name('orderRefundDetail'); //售后订单详情
        Route::post('order/refund/cancel/:uni', 'v1.order.StoreOrderRefund/cancelApply')->name('orderRefundCancel'); //取消售后申请
        Route::post('order/refund/again/:id', 'v1.order.StoreOrderRefund/againRefundOrder')->middleware(BlockerMiddleware::class)->name('againRefundOrder'); //再次提交售后申请
        Route::get('order/refund/del/:uni', 'v1.order.StoreOrderRefund/delRefundOrder')->name('delRefundOrder'); //删除已退款和拒绝退款的订单


        Route::post('order/take', 'v1.order.StoreOrder/take')->middleware(BlockerMiddleware::class)->name('orderTake'); //订单收货
        Route::get('order/express/:uni/[:type]', 'v1.order.StoreOrder/express')->name('orderExpress'); //订单查看物流
        Route::post('order/del', 'v1.order.StoreOrder/del')->name('orderDel'); //订单删除
        Route::post('order/again', 'v1.order.StoreOrder/again')->name('orderAgain'); //订单 再次下单
        Route::post('order/pay', 'v1.order.StoreOrder/pay')->name('orderPay'); //订单支付
        Route::post('order/product', 'v1.order.StoreOrder/product')->name('orderProduct'); //订单商品信息
        Route::post('order/comment', 'v1.order.StoreOrder/comment')->middleware(BlockerMiddleware::class)->name('orderComment'); //订单评价
        Route::get('order/pay_cashier', 'v1.order.StoreOrder/payCashierOrder')->name('payCashierOrder'); //用户门店下单付款
        //活动---砍价
        Route::post('bargain/start', 'v1.activity.StoreBargain/start')->middleware(BlockerMiddleware::class)->name('bargainStart');//砍价开启
        Route::post('bargain/start/user', 'v1.activity.StoreBargain/start_user')->name('bargainStartUser');//砍价 开启砍价用户信息
        Route::post('bargain/share', 'v1.activity.StoreBargain/share')->name('bargainShare');//砍价 观看/分享/参与次数
        Route::post('bargain/help', 'v1.activity.StoreBargain/help')->middleware(BlockerMiddleware::class)->name('bargainHelp');//砍价 帮助好友砍价
        Route::post('bargain/help/price', 'v1.activity.StoreBargain/help_price')->name('bargainHelpPrice');//砍价 砍掉金额
        Route::post('bargain/help/count', 'v1.activity.StoreBargain/help_count')->name('bargainHelpCount');//砍价 砍价帮总人数、剩余金额、进度条、已经砍掉的价格
        Route::post('bargain/help/list', 'v1.activity.StoreBargain/help_list')->name('bargainHelpList');//砍价 砍价帮
        Route::get('bargain/user/list', 'v1.activity.StoreBargain/user_list')->name('bargainUserList');//砍价列表(已参与)
        Route::post('bargain/user/cancel', 'v1.activity.StoreBargain/user_cancel')->name('bargainUserCancel');//砍价取消
        Route::get('bargain/poster_info/:bargainId', 'v1.activity.StoreBargain/posterInfo')->name('posterInfo');//砍价海报详细信息
        //活动---拼团
        Route::get('combination/pink/:id', 'v1.activity.StoreCombination/pinkInfo')->name('combinationPink');//拼团商品详情
        Route::post('combination/remove', 'v1.activity.StoreCombination/remove')->name('combinationRemove');//拼团 取消开团
        Route::get('combination/poster_info/:id', 'v1.activity.StoreCombination/posterInfo')->name('pinkPosterInfo');//拼团海报详细获取
        //账单类

        Route::get('commission', 'v1.user.UserBrokerage/commission')->name('commission');//推广数据 昨天的佣金 累计提现金额 当前佣金
        Route::get('spread/overview', 'v1.user.UserBrokerage/overview')->name('overview');//推广数据总览
        Route::get('spread/contribute/:type', 'v1.user.UserBrokerage/contribute')->name('contribute');//贡献
        Route::get('spread/order_income', 'v1.user.UserBrokerage/order_income')->name('order_income');//订单收益明细
        Route::post('spread/people', 'v1.user.User/spread_people')->name('spreadPeople');//推荐用户
        Route::post('spread/head', 'v1.user.User/peopleHead')->name('spreadPeople');//推广用户统计
        Route::post('spread/order', 'v1.user.UserBrokerage/spread_order')->name('spreadOrder');//推广订单
        Route::get('spread/commission/:type', 'v1.user.UserBill/spread_commission')->name('spreadCommission');//推广佣金明细
        Route::get('spread/count/:type', 'v1.user.UserBrokerage/spread_count')->name('spreadCount');//推广 佣金 3/提现 4 总和
        Route::get('integral/list', 'v1.user.UserBill/integral_list')->name('integralList');//积分记录
        Route::get('user/routine_code', 'v1.user.UserBill/getRoutineCode')->name('getRoutineCode');//小程序二维码
        Route::get('user/spread_info', 'v1.user.UserBill/getSpreadInfo')->name('getSpreadInfo');//获取分销背景等信息
        //提现类
        Route::get('extract/bank', 'v1.user.UserExtract/bank')->name('extractBank');//提现银行/提现最低金额
        Route::get('extract/detail', 'v1.user.UserExtract/detail')->name('extractBank');//提现银行/提现最低金额
        Route::post('extract/cash', 'v1.user.UserExtract/cash')->middleware(BlockerMiddleware::class)->name('extractCash');//提现申请
        //充值类
        Route::post('recharge/recharge', 'v1.user.UserRecharge/recharge')->name('rechargeRecharge');//统一充值 订单生成
        Route::post('recharge/pay', 'v1.user.UserRecharge/recharge_pay')->name('rechargePay');//统一充值 支付
        Route::get('recharge/index', 'v1.user.UserRecharge/index')->name('rechargeQuota');//充值余额选择
        //会员等级类
        Route::get('user/level/detection', 'v1.user.UserLevel/detection')->name('userLevelDetection');//检测用户是否可以成为会员
        Route::get('user/level/grade', 'v1.user.UserLevel/grade')->name('userLevelGrade');//会员等级列表
//        Route::get('user/level/task/:id', 'v1.user.UserLevel/task')->name('userLevelTask');//获取等级任务
        Route::get('user/level/info', 'v1.user.UserLevel/userLevelInfo')->name('levelInfo');//获取等级任务
        Route::get('user/level/expList', 'v1.user.UserLevel/expList')->name('expList');//获取等级任务
        Route::get('user/level/activate_info', 'v1.user.UserLevel/activateInfo')->name('userActivateInfo');//用户激活会员卡需要的信息
        Route::post('user/level/activate', 'v1.user.UserLevel/activateLevel')->name('userActivateLevel');//用户激活会员卡

        //首页获取未支付订单
        Route::get('order/nopay', 'v1.order.StoreOrder/get_noPay')->name('getNoPay');//获取未支付订单

        Route::get('seckill/code/:id', 'v1.activity.StoreSeckill/code')->name('seckillCode');//秒杀商品海报
        Route::get('combination/code/:id', 'v1.activity.StoreCombination/code')->name('combinationCode');//拼团商品海报

        //会员卡
        Route::get('user/member/card/index', 'v1.user.MemberCard/index')->name('userMemberCardIndex');// 主页会员权益介绍页
        Route::post('user/member/card/draw', 'v1.user.MemberCard/draw_member_card')->name('userMemberCardDraw');//卡密领取会员卡
        Route::post('user/member/card/create', 'v1.order.OtherOrder/create')->name('userMemberCardCreate');//购买卡创建订单
        Route::post('user/member/card/pay', 'v1.order.OtherOrder/create_pay')->name('userMemberCardCreatePay');//购卡订单支付
        Route::get('user/member/coupons/list', 'v1.user.MemberCard/memberCouponList')->name('userMemberCouponsList');//会员券列表
        Route::get('user/member/overdue/time', 'v1.user.MemberCard/getOverdueTime')->name('userMemberOverdueTime');//会员时间
        //线下付款
        Route::post('order/offline/check/price', 'v1.order.OtherOrder/computed_offline_pay_price')->name('orderOfflineCheckPrice'); //检测线下付款金额
        Route::post('order/offline/create', 'v1.order.OtherOrder/create')->name('orderOfflineCreate'); //检测线下付款金额
        Route::get('order/offline/pay/type', 'v1.order.OtherOrder/pay_type')->name('orderOfflineCreate'); //线下付款支付方式
        //积分商城订单
        Route::get('store_integral/order/detail/:uni', 'v1.activity.StoreIntegralOrder/detail')->name('storeIntegralOrderDetail'); //订单详情
        Route::get('store_integral/order/list', 'v1.activity.StoreIntegralOrder/lst')->name('storeIntegralOrderList'); //订单列表
        Route::post('store_integral/order/del', 'v1.activity.StoreIntegralOrder/del')->name('storeIntegralOrderDel'); //订单删除
        //消息站内信
        Route::get('user/message', 'v1.user.SystemMessage/message')->name('MessageSystemList'); //用户信息
        Route::get('user/message_system/list', 'v1.user.SystemMessage/message_list')->name('MessageSystemList'); //站内信列表
        Route::get('user/message_system/detail/:id', 'v1.user.SystemMessage/detail')->name('MessageSystemDetail'); //详情

        //供应商申请
        Route::get('user/apply/record', 'v1.system.UserApply/userApplyRecord')->name('userApplyRecord'); //供应商申请记录
        Route::post('user/apply/supplier/:id', 'v1.system.UserApply/userApply')->name('userApplySupplier'); //供应商申请
        Route::get('user/apply/:id', 'v1.system.UserApply/getInfo')->name('userApplyInfo'); //单个申请记录数据

        /** 事业部 */
        Route::get('division/agent/apply/info', 'v1.user.Division/applyInfo')->name('申请详情');//申请详情
        Route::post('division/agent/apply/:id', 'v1.user.Division/applyAgent')->name('申请代理商');//申请代理商
        Route::get('division/agent/staff_list', 'v1.user.Division/staffList')->name('员工列表');//员工列表
        Route::post('division/agent/staff_percent', 'v1.user.Division/staffPercent')->name('设置员工分佣比例');//设置员工分佣比例
        Route::get('division/agent/del_staff/:uid', 'v1.user.Division/delStaff')->name('删除员工');//删除员工
        Route::get('division/agent/spread/code', 'v1.user.Division/agentSpreadCode')->name('agentSpreadCode');//小程序员工邀请码
        Route::post('division/agent/spread', 'v1.user.Division/agentSpread')->name('agentSpread');//代理商绑定员工

        /** 分销员申请 */
        Route::get('user/promoter/apply/info', 'v1.user.PromoterApply/applyInfo')->name('申请信息');//申请信息
        Route::post('user/promoter/apply/:id', 'v1.user.PromoterApply/applyPromoter')->name('申请分销员');//申请分销员

    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, true);

    /**
     * diy相关
     */
    Route::group('diy', function () {

        //无需登录接口
        Route::group(function () {
            Route::get('get_diy/[:id]', 'v1.diy.Diy/getDiy');//DIY接口
            Route::get('diy_version/[:id]', 'v1.diy.Diy/getDiyVersion');//DIY版本接口
        });

        //未授权接口---不会抛异常
        Route::group(function () {

            Route::get('user_info', 'v1.diy.Diy/userInfo')->name('diyUserInfo');//diy用户信息
            Route::get('video_list', 'v1.diy.Diy/videoList')->name('diyVideoList');//diy短视频列表
            Route::get('newcomer_list', 'v1.diy.Diy/newcomerList')->name('diyNewcomerList');//diy新人专享商品列表
            Route::get('product_rank', 'v1.diy.Diy/productRank')->name('diyNewcomerList');//diy新人专享商品列表
            Route::get('sign', 'v1.diy.Diy/diySign')->name('diySign');//diy签到数据
            Route::get('get_suspended', 'v1.diy.Diy/getSuspendedDiy')->name('getSuspendedDiy');//diy悬浮窗数据

        })->middleware(AuthTokenMiddleware::class, false);

    })->middleware(StationOpenMiddleware::class);

    /**
     * v1.1 版本路由
     */
    Route::group('v2', function () {
        //无需授权接口
        Route::group(function () {
            //小程序登录页面自动加载，返回用户信息的缓存key，返回是否强制绑定手机号
            Route::get('routine/auth_type', 'v2.wechat.Routine/authType')->option(['real_name' => '小程序页面登录类型']);
            //小程序授权登录，返回token
            Route::get('routine/auth_login', 'v2.wechat.Routine/authLogin')->option(['real_name' => '小程序授权登录']);
            //小程序授权绑定手机号
            Route::post('routine/auth_binding_phone', 'v2.wechat.Routine/authBindingPhone')->option(['real_name' => '小程序授权绑定手机号']);
            //小程序手机号直接登录
            Route::post('routine/phone_login', 'v2.wechat.Routine/phoneLogin')->option(['real_name' => '手机号直接登录']);
            //小程序授权后绑定手机号
            Route::post('routine/binding_phone', 'v2.wechat.Routine/BindingPhone')->option(['real_name' => '小程序授权后绑定手机号']);
            //公众号授权登录，返回token
            Route::get('wechat/auth_login', 'v2.wechat.Wechat/authLogin')->option(['real_name' => '公众号授权登录']);
            //公众号授权绑定手机号
            Route::post('wechat/auth_binding_phone', 'v2.wechat.Wechat/silenceAuthBindingPhone')->option(['real_name' => '公众号授权绑定手机号']);


            //小程序授权
            Route::get('wechat/routine_auth', 'v2.wechat.Routine/auth');
            //小程序静默授权
            Route::get('wechat/silence_auth', 'v2.wechat.Routine/silenceAuthNoLogin');
            //小程序静默授权登陆
            Route::get('wechat/silence_auth_login', 'v2.wechat.Routine/silenceAuth');
            //小程序授权绑定手机号
            Route::post('auth_bindind_phone', 'v2.wechat.Routine/authBindingPhone');
            //小程序手机号登录直接绑定
            Route::post('phone_silence_auth', 'v2.wechat.Routine/silenceAuthBindingPhone');
            //公众号授权登录
            Route::get('wechat/auth', 'v2.wechat.Wechat/auth');
            //公众号静默授权
            Route::get('wechat/wx_silence_auth', 'v2.wechat.Wechat/silenceAuthNoLogin');
            //公众号静默授权登陆
            Route::get('wechat/wx_silence_auth_login', 'v2.wechat.Wechat/silenceAuth');
            //微信手机号登录直接绑定
            Route::post('phone_wx_silence_auth', 'v2.wechat.Wechat/silenceAuthBindingPhone');


            //DIY接口
            Route::get('diy/get_diy/[:name]', 'v2.PublicController/getDiy');
            //是否强制绑定手机号
            Route::get('bind_status', 'v2.PublicController/bindPhoneStatus');
            //小程序授权绑定手机号
            Route::post('auth_bindind_phone', 'v2.wechat.Routine/authBindingPhone');
            //小程序手机号登录直接绑定
            Route::post('phone_silence_auth', 'v2.wechat.Routine/silenceAuthBindingPhone');
            //微信手机号登录直接绑定
            Route::post('phone_wx_silence_auth', 'v2.wechat.Wechat/silenceAuthBindingPhone');
            //获取门店自提开启状态
            Route::get('diy/get_store_status', 'v2.PublicController/getStoreStatus');
            //一键换色
            Route::get('diy/color_change/:name', 'v2.PublicController/colorChange');
            //商品详情diy
            Route::get('diy/product_detail', 'v2.PublicController/productDetailDiy');
            //获取地址列表
            Route::get('cityList', 'v2.PublicController/cityList');
            //活动优惠活动商品列表
            Route::get('promotions/productList/:type', 'v2.activity.StorePromotions/productList');
            //优惠活动赠品信息
            Route::get('promotions/give_info/:id', 'v2.activity.StorePromotions/getPromotionsGive');
        });
        //需要授权
        Route::group(function () {
            Route::post('reset_cart', 'v2.order.StoreCart/resetCart')->name('resetCart');
            Route::get('new_coupon', 'v2.activity.StoreCoupons/getNewCoupon')->name('getNewCoupon');//获取新人券
            Route::post('user/user_update', 'v2.wechat.Routine/updateInfo');
            Route::get('user/service/record', 'v2.user.StoreService/record')->name('userServiceRecord');//客服聊天记录
            Route::get('cart_list', 'v2.order.StoreCart/getCartList');
            Route::get('get_attr/:id/:type', 'v2.product.StoreProduct/getProductAttr');
            Route::post('set_cart_num', 'v2.order.StoreCart/setCartNum');
            //订单申请发票
            Route::post('order/make_up_invoice', 'v2.order.StoreOrderInvoice/makeUp')->name('orderMakeUpInvoice');
            //用户发票列表
            Route::get('invoice', 'v2.user.UserInvoice/invoiceList')->name('userInvoiceLIst');
            //单个发票详情
            Route::get('invoice/detail/:id', 'v2.user.UserInvoice/invoice')->name('userInvoiceDetail');
            //修改|添加发票
            Route::post('invoice/save', 'v2.user.UserInvoice/saveInvoice')->name('userInvoiceSave');
            //设置默认发票
            Route::post('invoice/set_default/:id', 'v2.user.UserInvoice/setDefaultInvoice')->name('userInvoiceSetDefault');
            //获取默认发票
            Route::get('invoice/get_default/:type', 'v2.user.UserInvoice/getDefaultInvoice')->name('userInvoiceGetDefault');
            //删除发票
            Route::get('invoice/del/:id', 'v2.user.UserInvoice/delInvoice')->name('userInvoiceDel');
            //订单申请开票记录
            Route::get('order/invoice_list', 'v2.order.StoreOrderInvoice/list')->name('orderInvoiceList');
            //订单开票详情
            Route::get('order/invoice_detail/:uni/:id', 'v2.order.StoreOrderInvoice/detail')->name('orderInvoiceList');

            //清除搜索记录
            Route::get('user/clean_search', 'v2.user.UserSearch/cleanUserSearch')->name('cleanUserSearch');
            //更新公众号用户信息
            Route::get('user/wechat', 'v2.user.User/updateUserInfo')->name('updateUserInfo');

            //抽奖活动详情
            Route::get('lottery/info/[:factor]', 'v2.activity.LuckLottery/lotteryInfo')->name('lotteryInfo');
            //参与抽奖
            Route::post('lottery', 'v2.activity.LuckLottery/luckLottery')->name('luckLottery')->middleware(BlockerMiddleware::class);
            //领取奖品
            Route::post('lottery/receive', 'v2.activity.LuckLottery/lotteryReceive')->middleware(BlockerMiddleware::class)->name('lotteryReceive');
            //抽奖记录
            Route::get('lottery/record', 'v2.activity.LuckLottery/lotteryRecord')->name('lotteryRecord');
            //获取分销等级列表
            Route::get('agent/level_list', 'v2.agent.AgentLevel/levelList')->name('agentLevelList');
            //获取分销等级任务列表
            Route::get('agent/level_task_list', 'v2.agent.AgentLevel/levelTaskList')->name('agentLevelTaskList');

            //获取用户余额、佣金、提现明细列表
            Route::get('user/money_list/:type', 'v2.user.User/userMoneyList')->name('userMoneyList');
            //获取用户推广用户列表
            Route::get('agent/agent_user_list/:type', 'v2.agent.Agent/agentUserList')->name('agentUserList');
            //获取用户推广获得收益，佣金轮播，分销规则
            Route::get('agent/agent_info', 'v2.agent.Agent/agentInfo')->name('agentInfo');
            //优惠活动凑单商品列表
            Route::get('promotions/collect_order/product', 'v2.activity.StorePromotions/collectOrderProduct');

        })->middleware(AuthTokenMiddleware::class, true);

        //授权不通过,不会抛出异常继续执行
        Route::group(function () {
            //用户搜索记录
            Route::get('user/search_list', 'v2.user.UserSearch/getUserSearchList')->name('userSearchList');
            Route::get('get_today_coupon', 'v2.activity.StoreCoupons/getTodayCoupon');//新优惠券弹窗接口
            Route::get('subscribe', 'v2.PublicController/subscribe')->name('WechatSubscribe');// 微信公众号用户是否关注
            //公共类
            Route::get('index', 'v2.PublicController/index')->name('index');//首页
            //优惠券
            Route::get('coupons', 'v2.activity.StoreCoupons/lst')->name('couponsList'); //可领取优惠券列表
            //商品评价列表
            Route::get('reply/list/:id', 'v2.product.StoreProduct/reply_list')->name('v2replyList');//商品评价列表
        })->middleware(AuthTokenMiddleware::class, false);

    })->middleware(StationOpenMiddleware::class);

    /**
     * pc 路由
     */
    Route::group('pc', function () {
        //登陆接口
        Route::group(function () {
            Route::get('key', 'pc.Login/getLoginKey')->name('getLoginKey');//获取扫码登录key
            Route::get('scan/:key', 'pc.Login/scanLogin')->name('scanLogin');//检测扫码情况
            Route::get('get_appid', 'pc.Login/getAppid')->name('getAppid');//检测扫码情况
            Route::get('wechat_auth', 'pc.Login/wechatAuth')->name('wechatAuth');//检测扫码情况
        });

        //未授权接口
        Route::group(function () {
            Route::get('get_pay_vip_code', 'pc.Home/getPayVipCode')->name('getPayVipCode');//获取付费会员购买页面二维码
            Route::get('get_product_phone_buy', 'pc.Home/getProductPhoneBuy')->name('getProductPhoneBuy');//手机购买跳转url配置
            Route::get('get_banner', 'pc.Home/getBanner')->name('getBanner');//PC首页轮播图
            Route::get('get_category_product', 'pc.Home/getCategoryProduct')->name('getCategoryProduct');//首页分类尚品
            Route::get('get_products', 'pc.Product/getProductList')->name('getProductList');//商品列表
            Route::get('get_product_code/:product_id', 'pc.Product/getProductRoutineCode')->name('getProductRoutineCode');//商品详情小程序二维码
            Route::get('get_city/:pid', 'pc.PublicController/getCity')->name('getCity');//获取城市数据
            Route::get('check_order_status/:order_id/:end_time', 'pc.Order/checkOrderStatus')->name('checkOrderStatus');//轮询订单状态接口
            Route::get('get_company_info', 'pc.PublicController/getCompanyInfo')->name('getCompanyInfo');//获取公司信息
            Route::get('get_recommend/:type', 'pc.Product/getRecommendList')->name('getRecommendList');//获取推荐商品
            Route::get('get_wechat_qrcode', 'pc.PublicController/getWechatQrcode')->name('getWechatQrcode');//获取关注二维码
            Route::get('get_good_product', 'pc.Product/getGoodProduct')->name('getGoodProduct');//获取优品推荐
        })->middleware(AuthTokenMiddleware::class, false);

        //会员授权接口
        Route::group(function () {
            Route::get('get_cart_list', 'pc.Cart/getCartList')->name('getCartList');//购物车列表
            Route::get('get_balance_record/:type', 'pc.User/getBalanceRecord')->name('getBalanceRecord');//余额记录
            Route::get('get_order_list', 'pc.Order/getOrderList')->name('getOrderList');//订单列表
            Route::get('get_collect_list', 'pc.User/getCollectList')->name('getCollectList');//收藏列表
            Route::post('order/refund/cart_info', 'pc.Order/refundCartInfoList')->name('StoreOrderRefundCartInfoList');//获取退款商品列表
            Route::get('order/refund/list', 'pc.Order/refundList')->name('orderRefundList'); //售后订单列表
        })->middleware(AuthTokenMiddleware::class, true);

    })->middleware(StationOpenMiddleware::class);

    /**
     * 社区
     */
    Route::group('community', function () {
        //无需登录
        Route::group(function () {
            Route::get('topic', 'v1.community.Community/getTopic')->name('getTopic');//获取话题
        });
        //不登录不会报错
        Route::group(function () {
            Route::get('list', 'v1.community.Community/list')->name('list');//列表
            Route::get('detail/:id', 'v1.community.Community/detail')->name('detail');//详情
            Route::put('browse/:id', 'v1.community.Community/setBrowse')->name('setBrowse');//浏览
            Route::get('product_list', 'v1.community.Community/getProductList')->name('getProductList');//获取商品
            //评论
            Route::get('comment/list', 'v1.community.CommunityComment/list')->name('getCommunityCommentList');//获取评论
            //个人中心
            Route::get('user_info/:authorUid', 'v1.community.CommunityUser/getInfo')->name('getInfo');//获取个人中心信息
            Route::get('topic_count/:id', 'v1.community.Community/topicCount')->name('topicCount');//话题发帖数量

        })->middleware(AuthTokenMiddleware::class, false);
        //需要授权接口
        Route::group(function () {
            Route::post('community_save', 'v1.community.Community/communitySave')->name('communitySave');//新增
            Route::post('community_update/:id', 'v1.community.Community/update')->name('update');//编辑
            Route::post('community_like/:id', 'v1.community.Community/setCommunityLike')->name('setCommunityLike');//点赞
            Route::get('like_list', 'v1.community.Community/communityLikeList')->name('communityLikeList');//点赞列表
            Route::get('elegant_list', 'v1.community.Community/communityElegantList')->name('communityElegantList');//种草秀
            Route::get('share/:id', 'v1.community.Community/communityShare')->name('communityShare');//分享
            Route::delete('community_delete/:id', 'v1.community.Community/communityDelete')->name('communityDelete');//删除

            //评论
            Route::post('comment/save', 'v1.community.CommunityComment/save')->name('saveCommunityComment');//新增评论
            Route::post('comment_like/:id', 'v1.community.CommunityComment/setCommentLike')->name('setCommentLike');//评论点赞
            Route::delete('comment_delete/:id', 'v1.community.CommunityComment/commentDelete')->name('commentDelete');//删除

            //主页
            Route::post('update_desc', 'v1.community.CommunityUser/updateDesc')->name('updateDesc');//修改个人简介
            Route::post('set_interest/:authorUid', 'v1.community.CommunityUser/setInterest')->name('setInterest');//关注/取消
            Route::get('follow_list/:type', 'v1.community.CommunityUser/followList')->name('followList');//关注/粉丝列表
            Route::get('user_friend', 'v1.community.CommunityUser/userFriend')->name('userFriend');//好友列表
            Route::get('recommend_list', 'v1.community.CommunityUser/recommendList')->name('recommendList');//推荐用户
            Route::get('follow', 'v1.community.CommunityUser/follow')->name('follow');//关注发新作品

            //消息列表
            Route::get('message', 'v1.community.Community/message')->name('MessageCommunityList'); //消息列表

        })->middleware(AuthTokenMiddleware::class, true);
    })->middleware(StationOpenMiddleware::class)->middleware(CommunityOpenMiddleware::class);

    /**
     * 营销路由
     */
    Route::group('marketing', function () {

        //无需登录接口
        Route::group(function () {

        });

        //未授权接口---不会抛异常
        Route::group(function () {
            Route::get('short_video', 'v1.activity.Video/list')->name('shortVideoList');//短视频列表
            Route::get('short_video/info/:id', 'v1.activity.Video/info')->name('shortVideoProductInfo');//短视频详情
            Route::get('short_video/comment/:id', 'v1.activity.Video/commentList')->name('shortVideoCommentList');//短视频评论列表
            Route::get('short_video/product/:id', 'v1.activity.Video/productList')->name('shortVideoProductList');//短视频关联商品列表

            //新人礼
            Route::get('newcomer/product_list', 'v1.activity.StoreNewcomer/lst')->name('newcomerProductList');//新人专享商品
            Route::get('newcomer/product_detail/:id', 'v1.activity.StoreNewcomer/detail')->name('newcomerProductInfo');//新人商品详情

        })->middleware(AuthTokenMiddleware::class, false);

        //需要授权接口
        Route::group(function () {
            Route::post('short_video/comment/:id/:pid', 'v1.activity.Video/saveComment')->name('shortVideoComment');//短视频评论
            Route::get('short_video/comment_reply/:pid', 'v1.activity.Video/commentReplyList')->name('shortVideoCommentReplyList');//短视频评论回复列表
            Route::delete('short_video/comment/:id', 'v1.activity.Video/commentDelete')->name('shortVideoCommentDelete');//删除短视频评论

            Route::get('short_video/comment/:type/:id', 'v1.activity.Video/commentRelation')->name('shortVideoCommentRelation');//短视频评论点赞
            Route::get('short_video/:type/:id', 'v1.activity.Video/relation')->name('shortVideoRelation');//短视频点赞、收藏、分享

            //新人礼
            Route::get('newcomer/info', 'v1.activity.StoreNewcomer/getInfo')->name('newcomerInfo');//新人礼信息
            Route::get('newcomer/gift', 'v1.activity.StoreNewcomer/getGift')->name('newcomerInfo');//新人大礼包弹窗信息

        })->middleware(AuthTokenMiddleware::class, true);

    })->middleware(StationOpenMiddleware::class);


    /**
     * 移动端商家管理
     */
    Route::group('admin', function () {
        //控制台
        Route::get('erp/config', 'admin.order.StoreOrder/getErpConfig')->name('getErpConfig');//获取erp配置
        Route::get('refund_order/list', 'admin.order.StoreOrder/refundOrderList')->name('RefundOrderList');//退款订单列表
        Route::get('refund_order/detail/:uni', 'admin.order.StoreOrder/refundOrderDetail')->name('RefundOrderDetail');//退款订单详情
        Route::post('refund_order/remark', 'admin.order.StoreOrder/refundRemark')->name('refundRemark');//退款订单备注

        //商品
        Route::group('product', function () {
            //代客下单商品
            Route::get('category', 'admin.product.StoreProductCategory/category')->name('category');//商品分类
            Route::get('list', 'admin.product.StoreProduct/lst')->name('products');//商品列表

            //商品管理
            Route::get('admin_list', 'admin.product.StoreProduct/adminList')->name('products');//管理商品列表
            Route::post('set_show', 'admin.product.StoreProduct/setShow')->name('setShow');//修改商品状态
            Route::get('product_label', 'admin.product.StoreProduct/labelTreeList')->name('labelTreeList');//商品标签树形列表
            Route::get('get_attr/:id', 'admin.product.StoreProduct/getAttr')->name('updateAttrs');//获取商品规格
            Route::post('update_attrs/:id', 'admin.product.StoreProduct/updateAttrs')->name('updateAttrs');//修改库存价格
            Route::post('batch_process', 'admin.product.StoreProduct/batchProcess')->name('batchProcess');//修改分类标签
        });


        //用户
        Route::group('user', function () {
            Route::get('list', 'admin.user.User/list')->name('list');//用户列表
            Route::get('label/:uid', 'admin.user.User/userLabel')->name('userLabel');//用户标签
            Route::get('coupon/grant', 'admin.user.User/couponGrant')->name('couponGrant');//优惠券列表
            Route::get('group/list', 'admin.user.User/userGroup')->name('userGroup');//用户分组
            Route::get('level/list', 'admin.user.User/userLevel')->name('userLevel');//用户等级
            Route::get('info/:id', 'admin.user.User/info')->name('info');//用户详情
            Route::post('update_other/:uid', 'admin.user.User/updateOther')->name('updateOther');//修改余额/积分
            Route::post('update', 'admin.user.User/update')->name('update');//用户编辑

            Route::get('address/list/:uid', 'admin.user.UserAddress/address_list')->name('UserAddressList');//用户地址列表
            Route::get('address/default/:uid', 'admin.user.UserAddress/address_default')->name('addressDefault');//获取用户默认地址
        });

        //订单
        Route::group('order', function () {
            Route::get('statistics', 'admin.order.StoreOrder/statistics')->name('adminOrderStatistics');//订单数据统计
            Route::get('staging', 'admin.order.StoreOrder/stagingData')->name('adminOrderstagingData');//工作台数据统计
            Route::get('data', 'admin.order.StoreOrder/data')->name('adminOrderData');//订单每月统计数据
            Route::get('list', 'admin.order.StoreOrder/lst')->name('adminOrderList');//订单列表
            Route::get('detail/:orderId', 'admin.order.StoreOrder/detail')->name('adminOrderDetail');//订单详情
            Route::get('delivery/gain/:orderId', 'admin.order.StoreOrder/delivery_gain')->name('adminOrderDeliveryGain');//订单发货获取订单信息
            Route::post('delivery/keep/:id', 'admin.order.StoreOrder/delivery_keep')->name('adminOrderDeliveryKeep');//订单发货
            Route::post('price', 'admin.order.StoreOrder/price')->name('adminOrderPrice');//订单改价
            Route::post('remark', 'admin.order.StoreOrder/remark')->name('adminOrderRemark');//订单备注
            Route::get('time', 'admin.order.StoreOrder/time')->name('adminOrderTime');//订单交易额
            Route::get('time/chart', 'admin.order.StoreOrder/timeChart')->name('timeChart');//订单交易额时间统计
            Route::post('offline', 'admin.order.StoreOrder/offline')->name('adminOrderOffline');//订单支付
            Route::post('refund', 'admin.order.StoreOrder/refund')->middleware(BlockerMiddleware::class)->name('adminOrderRefund');//订单退款
            Route::post('refund_agree/:id', 'admin.order.StoreOrder/agreeRefund')->name('adminOrderAgreeRefund');//商家同意退货退款

            Route::get('delivery', 'admin.order.StoreOrder/getDeliveryAll')->name('getDeliveryAll');//获取配送员
            Route::get('delivery_info', 'admin.order.StoreOrder/getDeliveryInfo')->name('getDeliveryInfo');//获取电子面单默认信息
            Route::get('export_temp', 'admin.order.StoreOrder/getExportTemp')->name('getExportTemp');//获取电子面单模板获取
            Route::get('export_all', 'admin.order.StoreOrder/getExportAll')->name('getExportAll');//获取物流公司
            Route::get('split_cart_info/:id', 'admin.order.StoreOrder/split_cart_info')->name('StoreOrderSplitCartInfo')->option(['real_name' => '获取订单可拆分商品列表']);//获取订单可拆分商品列表
            Route::put('split_delivery/:id', 'admin.order.StoreOrder/split_delivery')->middleware(BlockerMiddleware::class)->name('StoreOrderSplitDelivery')->option(['real_name' => '拆单发送货']);//拆单发送货
            Route::post('open/refund/:id', 'admin.order.StoreOrder/open_order_refund')->middleware(BlockerMiddleware::class)->name('openOrderRefund')->option(['real_name' => '拆单退款']);//拆单退款
            //订单核销
            Route::post('order_verific', 'admin.order.StoreOrder/order_verific')->middleware(BlockerMiddleware::class)->name('order');//订单核销
            Route::post('wirteoff/records/:id', 'admin.order.StoreOrder/writeOffRecords')->middleware(BlockerMiddleware::class)->name('writeOffRecords')->option(['real_name' => '订单核销记录']);//订单核销记录

            //代客下单
            Route::get('cart/:uid', 'admin.order.StoreCart/getCartList')->name('orderCartList'); //购物车列表
            Route::post('cart/add/:uid', 'admin.order.StoreCart/addCart')->middleware(BlockerMiddleware::class)->name('cartAdd'); //购物车添加
            Route::delete('cart/del/:uid', 'admin.order.StoreCart/delCart')->name('cartDel'); //购物车删除
            Route::post('cart/num/:uid', 'admin.order.StoreCart/numCart')->name('cartNum'); //购物车 修改商品数量

            Route::get('place/list', 'admin.order.CreateOrder/lst')->name('orderPlaceList'); //代客下单记录
            Route::post('confirm/:uid', 'admin.order.CreateOrder/confirm')->name('orderConfirm'); //订单确认
            Route::post('computed/:key/:uid', 'admin.order.CreateOrder/computedOrder')->name('computedOrder'); //计算订单金额
            Route::get('coupons/:uid', 'admin.activity.StoreCoupons/order')->name('couponsOrder');//下单可使用优惠券
            Route::post('create/:key/:uid', 'admin.order.CreateOrder/createOrder')->middleware(BlockerMiddleware::class)->name('createOrder'); //代客下单：创建订单
            Route::post('pay/:uid', 'admin.order.CreateOrder/pay')->name('payOrder'); //代客下单支付信息
            Route::get('pay/status', 'admin.order.CreateOrder/checkOrderStatus')->option(['real_name' => '获取订单状态']);

        });

    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, true)->middleware(\app\http\middleware\api\CustomerMiddleware::class);


    /**
     * 移动端门店中心 路由
     */
    Route::group('store', function () {

        //无需登录接口
        Route::group(function () {
            Route::get('category', 'v1.store.StoreProductCategory/category')->name('category');//商品分类
        });

        //未授权接口---不会抛异常
        Route::group(function () {
            Route::get('list', 'v1.store.Store/getStoreList')->name('storeList');//门店列表
        })->middleware(AuthTokenMiddleware::class, false);

        //需要授权接口
        Route::group(function () {
            Route::get('delivery/info', 'store.StoreDelivery/info')->name('storeStaffInfo');//配送员信息
            Route::get('delivery/statistics', 'store.StoreDelivery/statistics')->name('deliveryStatistics');//门店配送员统计信息
            Route::get('delivery/data', 'store.StoreDelivery/data')->name('deliveryData');//每月配送统计列表数据
            Route::get('delivery/order', 'store.StoreDelivery/orderList')->name('deliveryOrder');//配送员订单列表
            Route::get('delivery/list', 'store.StoreDelivery/getDeliveryAll')->name('getDeliveryAll');//获取配送员列表

            Route::get('refund/detail/:id', 'store.StoreOrder/refundDetail')->name('refundDetail');//订单列表
            Route::get('order/detail/:id', 'store.StoreOrder/detail')->name('storeOrderDetail');//订单详情
            Route::get('order/writeoff_info/:type', 'store.StoreOrder/writeoffOrderinfo')->name('writeoffOrderinfo');//扫码核销获取订单信息
            Route::post('order/cart_info', 'store.StoreOrder/orderCartInfo')->name('writeoffOrderCartInfo');//核销获取商品信息
            Route::post('order/writeoff', 'store.StoreOrder/wirteoff')->middleware(BlockerMiddleware::class)->name('storeOrderWriteoff');//订单核销

            Route::get('order/delivery_info/:orderId', 'store.StoreOrder/deliveryInfo')->name('storeOrderDeliveryInfo');//订单发货获取订单信息
            Route::put('order/split_delivery/:id', 'store.StoreOrder/split_delivery')->middleware(BlockerMiddleware::class)->name('StoreOrderSplitDelivery');//拆单发送货
        })->middleware(AuthTokenMiddleware::class, true);

    })->middleware(StationOpenMiddleware::class);

    //企业微信
    Route::group('work', function () {
        //获取企业微信jsSDK配置
        Route::get('config', 'v1.work.WorkController/config')->name('WorkConfig');
        //获取企业微信应用jsSDK配置
        Route::get('agentConfig', 'v1.work.WorkController/agentConfig')->name('agentConfig');
        //获取客户群详情
        Route::get('groupInfo', 'v1.work.GroupChatController/getGroupInfo')->name('getGroupInfo');
        //获取群成员列表
        Route::get('groupMember/:id', 'v1.work.GroupChatController/getChatMemberList')->name('getChatMemberList');

        Route::group(function () {
            //获取客户信息详情
            Route::get('client/info', 'v1.work.Client/getClientInfo')->name('getClientInfo');
            //获取客户订单列表
            Route::get('order/list', 'v1.work.Order/getUserOrderList')->name('getWorkOrderList');
            //获取客户订单详情
            Route::get('order/info/:id', 'v1.work.Order/orderInfo')->name('getWorkOrderInfo');
            //购买商品记录
            Route::get('product/cart_list', 'v1.work.Product/getCartProductList')->name('getCartProductList');
            //浏览记录商品记录
            Route::get('product/visit_list', 'v1.work.Product/getVisitProductList')->name('getVisitProductList');

        })->middleware(ClientMiddleware::class);
    });


    /**
     * miss 路由
     */
    Route::miss(function () {
        if (app()->request->isOptions()) {
            $header = Config::get('cookie.header');
            $header['Access-Control-Allow-Origin'] = app()->request->header('origin');
            return Response::create('ok')->code(200)->header($header);
        } else
            return Response::create()->code(404);
    });

})->prefix('api.')->middleware(InstallMiddleware::class)->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);

