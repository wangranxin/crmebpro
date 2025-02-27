// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import request from "@/plugins/request";

/**
 * @description 商品属性 -- 列表
 * @param {Object} param params {Object} 传值参数
 */
export function ruleListApi(params) {
  return request({
    url: `product/product/rule`,
    method: "GET",
    params,
  });
}

/**
 * @description 商品属性 -- 添加
 * @param {Number} param id {Number} 属性id
 * @param {Object} param data {Object} 传值参数
 */
export function ruleAddApi(data, id) {
  return request({
    url: `product/product/rule/${id}`,
    method: "POST",
    data,
  });
}

/**
 * @description 选择商品 -- 列表
 */
export function changeListApi(params) {
  return request({
    url: `product/product/list`,
    method: "GET",
    params,
  });
}

/**
 * @description 商品管理-- 分类
 */
export function treeListApi(type) {
  return request({
    url: `product/category/tree/${type}`,
    method: "get",
  });
}

/**
 * @description 商品属性 -- 详情
 * @param {Number} param id {Number} 属性id
 */
export function ruleInfoApi(id) {
  return request({
    url: `product/product/rule/${id}`,
    method: "get",
  });
}

/**
 *商品列表-商品评价
 */
export function productReplyApi(params) {
  return request({
    url: `product/reply`,
    method: "get",
    params,
  });
}

/**
 * 商品评论 -- 回复
 */
export function setReplyApi(data, id) {
  return request({
    url: `product/reply/set_reply/${id}`,
    method: "PUT",
    data,
  });
}

/**
 *商品列表-获取列表
 */
export function productListInfo(data) {
  return request({
    url: "product/product",
    method: "get",
    params: data,
  });
}

/**
 *商品列表
 */
export function productList(data) {
  return request({
    url: `/product/product/list`,
    method: "get",
    params: data,
  });
}

/**
 *商品列表-获取列表头
 */
export function productHeaderInfo(data) {
  return request({
    url: "product/type_header",
    method: "get",
    params: data,
  });
}

/**
 * @description 商品详情里面分类-- cascader
 */
export function cascaderList(type) {
  return request({
    url: `product/category/cascader_list/${type}`,
    method: "get",
  });
}

/**
 * @description 添加商品-- 商品标签
 */
export function productStoreLabel() {
  return request({
    url: "product/product_label",
    method: "get",
  });
}

/**
 * 商品 -- 上下架
 */
export function setShowApi(id, is_show) {
  return request({
    url: `product/product/set_show/${id}/${is_show}`,
    method: "PUT",
  });
}

/**
 * @description 商品属性 -- 批量上下架
 * @param {Object} param data {Object} 传值对象
 */
export function productShowApi(data) {
  return request({
    url: `product/product/product_show`,
    method: "put",
    data,
  });
}

/**
 * @description 商品属性 -- 批量下架
 * @param {Object} param data {Object} 传值对象
 */
export function productUnshowApi(data) {
  return request({
    url: `product/product/product_unshow`,
    method: "put",
    data,
  });
}

/**
 * @description 商品 -- 获取运费模板
 */
export function productGetTemplateApi() {
  return request({
    url: `product/product/get_template`,
    method: "get",
  });
}

/**
 * @description 保存云端视频附件记录
 * @param {String} param ids {String}
 */
export function videoAttachment(data) {
  return request({
    url: "file/video_attachment",
    method: "post",
    data,
  });
}

/**
 * @description 自定义表单组件列表
 * @param {String} param ids {String}
 */
export function allSystemForm() {
  return request({
    url: "system/form/all_system_form",
    method: "get",
  });
}

/**
 * 商品批量操作
 * @param {*} data
 * @returns
 */
export function batchProcess(data) {
  return request({
    url: "product/batch_process",
    method: "post",
    data,
  });
}

/**
 * @description 商品列表-- 详情
 */
export function productInfoApi(id) {
  return request({
    url: `product/product/${id}`,
    method: "get",
  });
}

/**
 * @description 商品管理-- 添加品牌-获取上级分类
 */
export function brandCascader() {
  return request({
    url: "product/brand/cascader_list",
    method: "get",
  });
}

/**
 * @description 商品管理-- 提交添加品牌
 */
export function productBrand(data) {
  return request({
    url: "product/brand",
    method: "POST",
    data,
  });
}

/**
 * @description 商品管理-- 提交编辑品牌
 */
export function productBrandrev(id, data) {
  return request({
    url: `product/brand/${id}`,
    method: "put",
    data,
  });
}

/**
 * @description 商品管理-- 提交
 */
export function productAddApi(data) {
  return request({
    url: `product/product/${data.id}`,
    method: "POST",
    data,
  });
}

/**
 * @description 商品管理 -- 生成属性
 * @param {Object} param data {Object} 传值参数
 */
export function generateAttrApi(data, id, type) {
  return request({
    url: `product/generate_attr/${id}/${type}`,
    method: "POST",
    data,
  });
}

/**
 * @description 商品属性 -- 获取规则属性模板
 */
export function productGetRuleApi() {
  return request({
    url: `product/product/get_rule`,
    method: "get",
  });
}

/**
 * @description 获取上传参数
 */
export function productGetTempKeysApi(data) {
  return request({
    url: `product/product/get_temp_keys`,
    method: "get",
    params: data,
  });
}

/**
 * @description 商品管理-- 临时保存
 */
export function productCache() {
  return request({
    url: "product/cache",
    method: "get",
  });
}

/**
 * @description 商品管理-- 取消临时保存
 */
export function cacheDelete() {
  return request({
    url: "product/cache",
    method: "delete",
  });
}

/**
 * @description 商品管理-- 添加商品品牌列表
 */
export function brandList() {
  return request({
    url: `product/brand/cascader_list/2`,
    method: "get",
  });
}

/**
 * @description 商品分类 -- 添加表单
 * @param {Object} param params {Object} 传值参数
 */
export function productCreateApi() {
  return request({
    url: "product/category/create",
    method: "get",
  });
}

/**
 *添加商品-获取所有商品单位列表
 */
export function productAllUnit(id) {
  return request({
    url: `product/get_all_unit`,
    method: "get",
  });
}

/**
 *添加商品-商品单位添加表单
 */
export function productUnitCreate(id) {
  return request({
    url: `product/unit/create`,
    method: "get",
  });
}

/**
 * @description 商品添加编辑-- 获取上传视频类型
 */
export function uploadType() {
  return request({
    url: "file/upload_type",
    method: "get",
  });
}

/**
 * @description 添加商品-- 商品标签
 */
export function productAllEnsure() {
  return request({
    url: "product/all_ensure",
    method: "get",
  });
}

/**
 * @description 添加商品-- 添加商品标签
 */
export function productLabelAdd() {
  return request({
    url: "product/label/form",
    method: "get",
  });
}

/**
 * @description 添加商品-- 添加商品参数
 */
export function productAllSpecs() {
  return request({
    url: "product/all_specs",
    method: "get",
  });
}

/**
 * diy系统表单信息（详情）
 * @param {*} type
 * @returns
 */
export function systemFormInfo(id, data) {
  return request({
    url: `/system/form/info/${id}`,
    method: "get",
    params: data,
  });
}

/**
 *商品列表-获取商品规格
 */
export function productAttrsApi(id) {
  return request({
    url: `product/product/attrs/${id}`,
    method: "get",
  });
}

/**
 *商品列表-提交商品规格库存
 */
export function productSaveStocksApi(data, id) {
  return request({
    url: `product/product/saveStocks/${id}`,
    method: "PUT",
    data,
  });
}
