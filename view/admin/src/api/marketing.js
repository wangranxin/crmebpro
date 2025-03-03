// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import request from '@/plugins/request';

/**
 * @description 优惠券制作--列表
 * @param {Object} param params {Object} 传值参数
 */
export function couponListApi(params) {
  return request({
    url: 'marketing/coupon/list',
    method: 'get',
    params
  });
}

/**
 * @description 优惠券制作--新增表单
 * type:添加优惠券类型0：通用，1：品类，2：商品
 */
export function couponCreateApi(type) {
  return request({
    url: `marketing/coupon/create/${type}`,
    method: 'get'
  });
}

/**
 * @description 优惠券制作--品类
 * @param {*} type 默认 1
 */
export function couponCategoryApi(type) {
  return request({
    url: `product/category/cascader_list/${type}`,
    method: 'get'
  });
}

/**
 * @description 优惠券制作--保存
 */
export function couponSaveApi(data) {
  return request({
    url: `marketing/coupon/save_coupon`,
    method: 'post',
    data
  });
}

/**
 * @description 优惠券制作--编辑表单
 */
export function couponEditeApi(id) {
  return request({
    url: `marketing/coupon/${id}/edit`,
    method: 'get'
  });
}

/**
 * @description 优惠券制作--发布优惠券表单
 * @param {Number} param id {Number} 优惠券id
 */
export function couponSendApi(id) {
  return request({
    url: `marketing/coupon/issue/${id}`,
    method: 'get'
  });
}

/**
 * @description 已发布管理--列表
 * @param {Object} param params {Object} 传值参数
 */
export function releasedListApi(params) {
  return request({
    url: 'marketing/coupon/released',
    method: 'get',
    params
  });
}

/**
 * @description 已发布管理--领取记录
 * @param {Number} param id {Number} 已发布优惠券id
 */
export function releasedissueLogApi(id, params) {
  return request({
    url: `marketing/coupon/released/issue_log/${id}`,
    method: 'get',
    params
  });
}

/**
 * @description 已发布管理--修改状态表单
 * @param {Number} param id {Number} 已发布优惠券id
 */
export function releaseStatusApi(id) {
  return request({
    url: `marketing/coupon/released/${id}/status`,
    method: 'get'
  });
}

/**
 * @description 优惠券列表--是否开启
 * @param {*} data
 */
export function couponStatusApi(data) {
  return request({
    url: `marketing/coupon/status/${data.id}/${data.status}`,
    method: 'get'
  });
}

/**
 * @description 优惠券
 * @param {*} id
 */
export function couponDetailApi(id) {
  return request({
    url: `marketing/coupon/copy/${id}`,
    method: 'get'
  });
}

/**
 * @description 会员领取记录 -- 列表
 * @param {Object} param params {Object} 传值参数
 */
export function userListApi(params) {
  return request({
    url: `/marketing/coupon/user`,
    method: 'get',
    params
  });
}

/**
 * @description 砍价商品 -- 列表
 * @param {Object} param params {Object} 传值参数
 */
export function bargainListApi(params) {
  return request({
    url: `marketing/bargain`,
    method: 'get',
    params
  });
}

/**
 * @description 砍价商品 -- 列表
 * @param {Object} param params {Object} 传值参数
 */
export function bargainUserListApi(params) {
  return request({
    url: `marketing/bargain_list`,
    method: 'get',
    params
  });
}

/**
 * @description 砍价商品 -- 列表
 * @param {Object} param params {Object} 传值参数
 */
export function bargainUserInfoApi(id) {
  return request({
    url: `marketing/bargain_list_info/${id}`,
    method: 'get'
  });
}

/**
 * @description 砍价商品 -- 详情
 * @param {Number} param id {Number} 砍价商品id
 */
export function bargainInfoApi(id) {
  return request({
    url: `marketing/bargain/${id}`,
    method: 'get'
  });
}

/**
 * @description 砍价商品 -- 保存编辑
 * @param {Object} param data {Object} 传值参数
 */
export function bargainCreatApi(data) {
  return request({
    url: `marketing/bargain/${data.id}`,
    method: 'POST',
    data
  });
}

/**
 * @description 砍价商品 -- 修改状态
 * @param {Object} param data {Object} 传值参数
 */
export function bargainSetStatusApi(data) {
  return request({
    url: `marketing/bargain/set_status/${data.id}/${data.status}`,
    method: 'PUT'
  });
}

/**
 * 砍价统计
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getbargainStatistics(id, params) {
    return request({
        url: `marketing/bargain/statistics/head/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 砍价列表
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getbargainStatisticsPeople(id, params) {
    return request({
        url: `marketing/bargain/statistics/list/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 砍价订单
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getbargainStatisticsOrder(id, params) {
    return request({
        url: `marketing/bargain/statistics/order/${id}`,
        method: 'get',
        params,
    });
}

/**
 * @description 拼团商品 -- 列表
 * @param {Object} param data {Object} 传值参数
 */
export function combinationListApi(params) {
  return request({
    url: `marketing/combination`,
    method: 'get',
    params
  });
}

/**
 * @description 拼团商品 -- 修改状态
 * @param {Object} param data {Object} 传值参数
 */
export function combinationSetStatusApi(data) {
  return request({
    url: `marketing/combination/set_status/${data.id}/${data.status}`,
    method: 'PUT'
  });
}

/**
 * @description 拼团商品 -- 拼团统计
 * @param {Object} param data {Object} 传值参数
 */
export function statisticsApi() {
  return request({
    url: `marketing/combination/statistics`,
    method: 'GET'
  });
}

/**
 * @description 拼团商品 -- 详情
 * @param {Number} param id {Number} 拼团商品id
 */
export function combinationInfoApi(id) {
  return request({
    url: `marketing/combination/${id}`,
    method: 'get'
  });
}

/**
 * @description 拼团商品 -- 保存编辑
 * @param {Object} param data {Object} 传值参数
 */
export function combinationCreatApi(data) {
  return request({
    url: `marketing/combination/${data.id}`,
    method: 'POST',
    data
  });
}

/**
 * @description 拼团商品 -- 拼团列表
 */
export function combineListApi(params) {
  return request({
    url: `marketing/combination/combine/list`,
    method: 'GET',
    params
  });
}

/**
 * @description 拼团商品 -- 拼团人列表
 * @param {Number} param id {Number} 拼团商品id
 */
export function orderPinkListApi(id) {
  return request({
    url: `marketing/combination/order_pink/${id}`,
    method: 'GET'
  });
}

/**
 * 拼团统计
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getcombinationStatistics(id, params) {
    return request({
        url: `marketing/combination/statistics/head/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 拼团列表
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getcombinationStatisticsPeople(id, params) {
    return request({
        url: `marketing/combination/statistics/list/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 拼团订单
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getcombinationStatisticsOrder(id, params) {
    return request({
        url: `marketing/combination/statistics/order/${id}`,
        method: 'get',
        params,
    });
}

/**
 * @description 秒杀商品 -- 列表
 */
export function seckillProductList (params) {
    return request({
        url: `marketing/seckill/product`,
        method: 'GET',
        params
    });
}

/**
 * @description 秒杀商品 -- 修改状态
 */
export function seckillProductStatus (data) {
    return request({
        url: `marketing/seckill/product/set_status/${data.id}/${data.status}`,
        method: 'put'
    });
}

/**
 * @description 秒杀活动 -- 列表
 */
export function seckillListApi(params) {
  return request({
    url: `marketing/seckill`,
    method: 'GET',
    params
  });
}

/**
 * @description 秒杀活动 -- 详情
 */
export function seckillInfoApi(id) {
  return request({
    url: `marketing/seckill/${id}`,
    method: 'GET'
  });
}

/**
 * @description 秒杀商品 -- 保存编辑
 */
export function seckillAddApi(data,id) {
  return request({
    url: `marketing/seckill/${id}`,
    method: 'post',
    data
  });
}

/**
 * @description 秒杀商品 -- 修改状态
 */
export function seckillStatusApi(data) {
  return request({
    url: `marketing/seckill/set_status/${data.id}/${data.status}`,
    method: 'put'
  });
}

/**
 * 秒杀时间段列表
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function seckillTimeList(params) {
    return request({
        url: `/marketing/seckill/time`,
        method: 'get',
        params
    });
}

/**
 * 添加、编辑表单
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function seckillTimeCreate(id) {
    return request({
        url: `/marketing/seckill/time/create/${id}`,
        method: 'get'
    });
}

/**
 * 秒杀配置修改状态
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function seckillSetStatus(row) {
    return request({
        url: `/marketing/seckill/time/set_status/${row.id}/${row.status}`,
        method: 'put'
    });
}

/**
 * 秒杀统计
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getseckillStatistics(id, params) {
    return request({
        url: `marketing/seckill/statistics/head/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 秒杀参与人
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getseckillStatisticsPeople(id, params) {
    return request({
        url: `marketing/seckill/statistics/people/${id}`,
        method: 'get',
        params,
    });
}

/**
 * 秒杀订单
 * @param {*} id
 * @param {*} params
 * @returns
 */
export function getseckillStatisticsOrder(id, params) {
    return request({
        url: `marketing/seckill/statistics/order/${id}`,
        method: 'get',
        params,
    });
}

/**
 * @description 积分日志 -- 列表
 */
export function integralListApi(params) {
  return request({
    url: `marketing/integral`,
    method: 'GET',
    params
  });
}

/**
 * @description 积分日志 -- 头部
 */
export function integralStatisticsApi(params) {
  return request({
    url: `marketing/integral/statistics`,
    method: 'GET',
    params
  });
}

/**
 * @description 积分日志 -- 头部
 */
export function seckillTimeListApi() {
  return request({
    url: `marketing/seckill/time_list`,
    method: 'GET'
  });
}

/**
 * 积分统计顶部
 * @param {com} data
 */
export function getPointBasic(data) {
    return request({
        url: 'marketing/point/get_basic',
        method: 'get',
        params: data,
    });
}

/**
 * 积分统计 折线图
 * @param {com} data
 */
export function getPointTrend(data) {
    return request({
        url: 'marketing/point/get_trend',
        method: 'get',
        params: data,
    });
}

/**
 * @description 积分来源分析
 * @param {Object} param data {Object} 传值参数
 */
export function getChannel(params) {
    return request({
        url: '/marketing/point/get_channel',
        method: 'get',
        params,
    });
}

/**
 * @description 积分消耗分析
 * @param {Object} param data {Object} 传值参数
 */
export function getType(params) {
    return request({
        url: '/marketing/point/get_type',
        method: 'get',
        params,
    });
}

/**
 * @description 积分分类
 * @param {Object} param data {Object} 传值参数
 */
export function integralCategory(params) {
    return request({
        url: '/marketing/integral/category',
        method: 'get',
        params,
    });
}

/**
 * @description 添加积分分类
 * @param {Object} param data {Object} 传值参数
 */
export function integralCreate() {
    return request({
        url: '/marketing/integral/category/create',
        method: 'get',
    });
}

/**
 * @description 编辑积分分类
 * @param {Object} param data {Object} 传值参数
 */
export function integralCategoryEdit(id) {
    return request({
        url: '/marketing/integral/category/'+id,
        method: 'get',
    });
}

/**
 * @description 积分分类修改状态
 * @param {Object} param data {Object} 传值参数
 */
export function categorySetShow(data) {
    return request({
        url: `/marketing/integral/category/set_show/${data.id}/${data.is_show}`,
        method: 'PUT',
    });
}

/**
 * @description 获取商品规格
 */
export function productAttrsApi(id, type) {
  return request({
    url: `product/product/attrs/${id}/${type}`,
    method: 'GET'
  });
}

/**
 * @description 已发布管理 -- 删除
 */
export function delCouponReleased(id) {
  return request({
    url: `marketing/coupon/released/${id}`,
    method: 'DELETE'
  });
}

/**
 * @description 积分日志 -- 导出
 */
export function userPointApi(data) {
  return request({
    url: `export/userPoint`,
    method: 'get',
    params: data
  });
}

/**
 * @description 商铺砍价活动 -- 导出
 */
export function stroeBargainApi(data) {
  return request({
    url: `export/storeBargain`,
    method: 'get',
    params: data
  });
}

/**
 * @description 商铺拼团 -- 导出
 */
export function storeCombinationApi(data) {
  return request({
    url: `export/storeCombination`,
    method: 'get',
    params: data
  });
}

/**
 * @description 商铺秒杀 -- 导出
 */
export function storeSeckillApi(data) {
  return request({
    url: `export/storeSeckill`,
    method: 'get',
    params: data
  });
}
/**
 * @description 积分商品 -- 列表
 */
export function integralProductListApi(params) {
  return request({
    url: `marketing/integral_product`,
    method: 'GET',
    params
  });
}
/**
 * @description 积分商品 -- 保存编辑
 */
export function integralAddApi(data) {
  return request({
    url: `marketing/integral/${data.id}`,
    method: 'post',
    data
  });
}

/**
 * @description 积分商品 -- (多个) 保存
 */
export function integralAddBatch(data) {
  return request({
    url: `marketing/integral/batch`,
    method: 'post',
    data
  });
}

/**
 * @description 积分商品 -- 详情
 */
export function integralInfoApi(id) {
  return request({
    url: `marketing/integral/${id}`,
    method: 'GET'
  });
}
/**
 * @description 积分商品 -- 修改状态
 */
export function integralIsShowApi(data) {
  return request({
    url: `marketing/integral/set_show/${data.id}/${data.is_show}`,
    method: 'put'
  });
}
/**
 * @description 积分订单管理--列表
 * @param {Object} param data {Object} 传值参数
 */
export function integralOrderList(data) {
  return request({
    url: 'marketing/integral/order/list',
    method: 'get',
    params: data
  });
}

/**
 * @description 积分订单数据--列表
 * @param {Object} param data {Object} 传值参数
 */
export function integralGetOrdes(data) {
  return request({
    url: 'marketing/integral/order/chart',
    method: 'get',
    params: data
  });
}

/**
 * @description 订单表单详情数据
 * @param {Number} param id {Number} 订单id
 */
export function getIntegralOrderDataInfo(id) {
  return request({
    url: `marketing/integral/order/info/${id}`,
    method: 'get'
  });
}

/**
 * @description 修改备注信息
 * @param {Number} param data.id {Number} 订单id
 * @param {String} param data.remark {String} 备注信息
 */
export function integralOrderPutRemarkData(data) {
  return request({
    url: `marketing/integral/order/remark/${data.id}`,
    method: 'put',
    data: data.remark
  });
}

/**
 * @description 获取订单记录
 * @param {Number} param data.id {Number} 订单id
 * @param {String} param data.datas {String} 分页参数
 */
export function getIntegralOrderRecord(data) {
  return request({
    url: `marketing/integral/order/status/${data.id}`,
    method: 'get',
    params: data.datas
  });
}

/**
 * @description 发送货提交表单
 * @param {Number} param data.id {Number} 订单id
 * @param {Object} param data.datas {Object} 表单信息
 */
export function integralOrderPutDelivery(data) {
  return request({
    url: `marketing/integral/order/delivery/${data.id}`,
    method: 'put',
    data: data.datas
  });
}

/**
 * @description 配送信息表单
 * @param {Number} param id {Number} 订单id
 */
export function getIntegralOrderDistribution(id) {
  return request({
    url: `marketing/integral/order/distribution/${id}`,
    method: 'get'
  });
}
/**
 * @description 订单物流信息
 * @param {Number} param id {Number} 订单id
 */
export function getExpress(id) {
  return request({
    url: `marketing/integral/order/express/${id}`,
    method: 'get'
  });
}
/**
 * @description 获取快递公司
 */
export function getExpressData(status) {
  return request({
    url: `marketing/integral/order/express_list?status=` + status,
    method: 'get'
  });
}
/**
 * 订单时获取所有配送员列表
 */
export function orderDeliveryList() {
  return request({
    url: 'marketing/integral/order/delivery/list',
    method: 'get'
  });
}

/**
 * 电子面单模板
 * @param {com} data 快递公司编号
 */
export function orderExpressTemp(data) {
  return request({
    url: 'marketing/integral/order/express/temp',
    method: 'get',
    params: data
  });
}

export function orderSheetInfo() {
  return request({
    url: 'marketing/integral/order/sheet_info',
    method: 'get'
  });
}

/**
 * 改折扣活动
 * @param {com} data 添加或修改折扣活动
 */
export function saveDiscount(type, id, data) {
  return request({
    url: `marketing/promotions/save_discount/${type}/${id}`,
    method: 'post',
    data
  });
}

/**
 * 满减活动
 * @param {com} data 添加或修改满减活动
 */
export function savePromotions(type, id, data) {
  return request({
    url: `marketing/promotions/save/${type}/${id}`,
    method: 'post',
    data
  });
}

/**
 * 改折扣活动
 * @param {com} data 活动列表活动
 */
export function discountList(type, data) {
  return request({
    url: `marketing/promotions/list/${type}`,
    method: 'get',
    params: data
  });
}

/**
 * 改折扣活动
 * @param {com} data 开启关闭
 */
export function discountsetStatus(id, status) {
  return request({
    url: `marketing/promotions/set_status/${id}/${status}`,
    method: 'get'
  });
}

/**
 * 改折扣活动
 * @param {com} data 详情
 */
export function discountInfo(id) {
  return request({
    url: `marketing/promotions/info/${id}`,
    method: 'get'
  });
}

/**
 * 获取短视频列表
 * @param {com} data 列表
 */
export function videoList(data) {
  return request({
    url: `/marketing/video/index`,
    method: 'get',
    params: data
  });
}

/**
 * 获取短视频信息
 * @param {com} data 详情
 */
export function videoInfo(id) {
  return request({
    url: `/marketing/video/info/${id}`,
    method: 'get'
  });
}

/**
 * 保存短视频
 * @param {com} data 提交
 */
export function videoSave(data, id) {
  return request({
    url: `/marketing/video/save/${id}`,
    method: 'post',
    data
  });
}

/**
 * 短视频上下架
 * @param {com} data 上下架
 */
export function videoSetStatus(data) {
  return request({
    url: `/marketing/video/set_status/${data.id}/${data.status}`,
    method: 'get'
  });
}

/**
 * 短视频推荐
 * @param {com} data 推荐
 */
export function videoSetRecommend(data) {
  return request({
    url: `/marketing/video/set_recommend/${data.id}/${data.recommend}`,
    method: 'get'
  });
}

/**
 * 短视频审核
 * @param {com} data 审核
 */
export function videoVerify(data) {
  return request({
    url: `/marketing/video/verify/${data.id}/${data.verify}`,
    method: 'get'
  });
}

/**
 * 短视频强制下架
 * @param {com} data 强制
 */
export function videoTakeDown(id) {
  return request({
    url: `/marketing/video/take_down/${id}`,
    method: 'get'
  });
}

/**
 * 获取短视频评论列表
 * @param {com} data 列表
 */
export function videoComment(data) {
  return request({
    url: `/marketing/video/comment`,
    method: 'get',
    params: data
  });
}

/**
 * 评论回复提交
 * @param {com} data 提交
 */
export function videoReply(data, id) {
  return request({
    url: `/marketing/video/comment/reply/${id}`,
    method: 'post',
    data
  });
}

/**
 * 获取评论回复列表
 * @param {com} data 回复列表
 */
export function videoCommentReply(data, id) {
  return request({
    url: `/marketing/video/comment/reply/${id}`,
    method: 'get',
    params: data
  });
}

/**
 * 获取虚拟评论表单
 * @param {com} data 回复列表
 */
export function fictitiousReply(video_id) {
  return request({
    url: `/marketing/video/comment/fictitious/${video_id}`,
    method: 'get'
  });
}

/**
 * 活动边框列表
 * @param {*} data
 * @returns
 */
export function activityFrameList(data) {
  return request({
    url: '/marketing/activity_frame/list',
    method: 'get',
    params: data
  });
}

/**
 * 添加或修改活动边框
 * @param {*} data
 * @returns
 */
export function activityFrameSave(id, data) {
  return request({
    url: `/marketing/activity_frame/save/${id}`,
    method: 'post',
    data
  });
}

/**
 * 活动边框详情
 * @param {*} data
 * @returns
 */
export function activityFrameInfo(id) {
  return request({
    url: `/marketing/activity_frame/info/${id}`,
    method: 'get'
  });
}

/**
 * 活动边框上下架
 * @param {*} data
 * @returns
 */
export function activityFrameStatus(id, status) {
  return request({
    url: `/marketing/activity_frame/set_status/${id}/${status}`,
    method: 'get'
  });
}

/**
 * 活动背景列表
 * @param {*} data
 * @returns
 */
export function activityBackgroundList(data) {
  return request({
    url: '/marketing/activity_background/list',
    method: 'get',
    params: data
  });
}

/**
 * 活动背景上下架
 * @param {*} data
 * @returns
 */
export function activityBackgroundStatus(id, status) {
  return request({
    url: `/marketing/activity_background/set_status/${id}/${status}`,
    method: 'get'
  });
}

/**
 * 添加或修改活动背景
 * @param {*} data
 * @returns
 */
export function activityBackgroundSave(id, data) {
  return request({
    url: `/marketing/activity_background/save/${id}`,
    method: 'post',
    data
  });
}

/**
 * 活动背景详情
 * @param {*} data
 * @returns
 */
export function activityBackgroundInfo(id) {
  return request({
    url: `/marketing/activity_background/info/${id}`,
    method: 'get'
  });
}

/**
 * 渠道码分类列表
 * @returns 
 */
export function wechatQrcodeCateList() {
  return request({
      url: '/app/wechat_qrcode/cate/list',
      method: 'get'
  });
}

/**
 * 渠道码列表
 * @param {*} params 
 * @returns 
 */
export function wechatQrcodeList(params) {
  return request({
      url: '/app/wechat_qrcode/list',
      method: 'get',
      params
  });
}

/**
 * 渠道码分类添加编辑表单
 * @param {*} id 
 * @returns 
 */
export function wechatQrcodeCateCreate(id) {
  return request({
      url: `/app/wechat_qrcode/cate/create/${id}`,
      method: 'get'
  });
}

/**
 * 切换渠道码状态
 * @param {*} data 
 * @returns 
 */
export function wechatQrcodeStatus(data) {
  return request({
      url: `/app/wechat_qrcode/set_status/${data.id}/${data.status}`,
      method: 'put'
  });
}

/**
 * 渠道码用户列表
 * @param {*} qid 
 * @returns 
 */
export function wechatQrcodeUserList(params) {
  return request({
      url: `/app/wechat_qrcode/user_list/${params.id}`,
      method: 'get',
      params
  });
}

/**
 * 渠道码详情
 * @param {*} id 
 * @returns 
 */
export function wechatQrcodeInfo(id) {
  return request({
      url: `/app/wechat_qrcode/info/${id}`,
      method: 'get'
  });
}

/**
 * 保存渠道码
 * @param {*} data 
 * @returns 
 */
export function wechatQrcodeSave(id, data) {
  return request({
      url: `/app/wechat_qrcode/save/${id}`,
      method: 'post',
      data
  });
}

/**
 * 渠道码统计
 * @param {*} id 
 * @param {*} params 
 * @returns 
 */
export function wechatQrcodeStatistic(id, params) {
  return request({
      url: `/app/wechat_qrcode/statistic/${id}`,
      method: 'get',
      params
  });
}

/**
 * 签到奖励列表
 * @param {*} type 
 * @returns 
 */
export function signRewards(params) {
  return request({
      url: '/setting/sign/rewards',
      params
  });
}

/**
 * 添加签到奖励
 * @param {*} params 
 * @returns 
 */
export function addRewards(params) {
  return request({
      url: '/setting/sign/add_rewards',
      params
  });
}

/**
 * 编辑签到奖励
 * @param {*} id 
 * @returns 
 */
export function editRewards(id) {
  return request({
      url: `/setting/sign/edit_rewards/${id}`,
  });
}