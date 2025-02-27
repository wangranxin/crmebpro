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
 *供应商流水-获取列表
 */
 export function supplierFinanceInfo(params) {
  return request({
    url: 'finance/supplier_flowing_water/list',
    method: 'get',
    params
  });
}

/**
 *供应商流水--备注
 */
 export function supplierFinanceMarkApi(id, data) {
  return request({
    url: `/finance/supplier_flowing_water/mark/${id}`,
    method: 'post',
    data
  });
}

/**
 *供应商流水--获取账单记录列表
 */
 export function supplierFfundRecordApi(params) {
  return request({
    url: `/finance/supplier_flowing_water/fund_record`,
    method: 'get',
    params
  });
}

/**
 *供应商流水--账单记录列表-账单下载
 */
 export function exportfundRecordApi(params) {
  return request({
    url: `/export/financeRecord`,
    method: 'get',
    params
  });
}

/**
 * 获取交易类型
 * @param {*} params
 * @returns
 */
export function supplierFinanceType(params) {
  return request({
    url: `/finance/supplier_flowing_water/type`,
    method: 'get',
    params
  });
}

/**
 *转账申请-申请列表
 */
 export function supplierExtractInfo(params) {
  return request({
    url: '/finance/supplier_extract/list',
    method: 'get',
    params
  });
}

/**
 *转账申请-申请提现
 */
 export function supplierExtractApi(data) {
  return request({
    url: '/finance/supplier_extract/cash',
    method: 'post',
    data
  });
}

/**
 *转账申请-备注
 */
 export function supplierExtractMarkApi(id, data) {
  return request({
    url: `/finance/supplier_extract/mark/${id}`,
    method: 'post',
    data
  });
}

/**
 *财务设置-获取|保存
 */
 export function settingApi(data = undefined, method = 'get') {
  return request({
    url: '/finance/info',
    method,
    data
  });
}

/**
 *供应商流水--账单记录列表-账单详情
 */
export function supplierFfundRecordInfoApi(params) {
    return request({
        url: `/finance/supplier_flowing_water/fund_record_info`,
        method: 'get',
        params
    });
}
