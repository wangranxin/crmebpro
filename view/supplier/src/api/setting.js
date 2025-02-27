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
 * @description 获取小票
 * @param {Object} param data {Object} 传值参数
 */
export function printing() {
  return request({
    url: "/printing",
    method: "get",
  });
}

/**
 * @description 更新小票
 * @param {Object} param data {Object} 传值参数
 */
export function putPrinting(data) {
  return request({
    url: "/printing",
    method: "put",
    data,
  });
}

/**
 * @description 供应商
 * @param {Object} param data {Object} 传值参数
 */
export function supplier() {
  return request({
    url: "/supplier",
    method: "get",
  });
}

/**
 * @description 更新供应商
 * @param {Object} param data {Object} 传值参数
 */
export function putSupplier(data) {
  return request({
    url: "/supplier",
    method: "put",
    data,
  });
}

/**
 * @description 获取省市区街道
 */
export function cityApi(data) {
  return request({
    url: "city",
    method: "get",
    params: data,
  });
}

/**
 * @description 管理员列表
 */
export function adminListApi(data) {
  return request({
    url: "admin",
    method: "get",
    params: data,
  });
}

/**
 * @description 添加管理员
 */
export function adminFromApi() {
  return request({
    url: "admin/create",
    method: "get",
  });
}

/**
 * @description 编辑管理员
 */
export function adminEditFromApi(id) {
  return request({
    url: `admin/${id}/edit`,
    method: "get",
  });
}

/**
 * @description 管理员修改状态
 */
export function setShowApi(data) {
  return request({
    url: `admin/set_status/${data.id}/${data.status}`,
    method: "put",
  });
}

/**
 * @description 设置 运费模板 -- 列表
 *  @param {Object} param data {Object} 传值参数
 */
export function templatesApi(data) {
  return request({
    url: `setting/shipping_templates/list`,
    method: "get",
    params: data,
  });
}

/**
 * @description 设置 运费模板 -- 提交修改表单；
 */
export function templatesSaveApi(id, data) {
  return request({
    url: `setting/shipping_templates/save/${id}`,
    method: "post",
    data,
  });
}

/**
 * @description 设置 运费模板 -- 提交修改表单；
 */
export function shipTemplatesApi(id) {
  return request({
    url: `setting/shipping_templates/${id}/edit`,
    method: "get",
  });
}

/**
 * @description 运费模板 获取省市区街道
 */
export function cityData(data) {
  return request({
    url: "city",
    method: "get",
    params: data,
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
 * 获取表单配置
 * @param type
 */
export function getNewFormBuildRuleApi(type) {
  return request({
    url: "system/config/edit_new_build/" + type,
    method: "get",
  });
}

/**
 * 打印机列表
 * @param {*} type
 * @returns
 */
export function printList(data) {
  return request({
    url: `/print/list`,
    method: "get",
    params: data,
  });
}

/**
 * 打印机创建
 * @param {*} type
 * @returns
 */
export function printForm(id) {
  return request({
    url: `/print/form/${id}`,
    method: "get",
  });
}
/**
 * 打印机状态切换
 * @param {*} type
 * @returns
 */
export function printSetStatus(data) {
  return request({
    url: `/print/set_status/${data.id}/${data.status}`,
    method: "post",
  });
}

/**
 * 发票配置保存
 * @returns
 */
export function printSaveContent(id, data) {
  return request({
    url: `/print/save_content/${id}`,
    method: "post",
    data,
  });
}
/**
 * 获取发票配置
 */
export function printContent(id) {
  return request({
    url: `/print/content/${id}`,
    method: "get",
  });
}
