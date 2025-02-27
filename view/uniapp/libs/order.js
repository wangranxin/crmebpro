// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

let app = getApp()

export function goShopDetail(item, uid) {
	return new Promise((resolve,reject) => {
		if(item.is_presale_product){
			uni.navigateTo({
				url: `/pages/activity/goods_details/index?id=${item.id}&type=6`
			})
		}else{
			if (item.activity && item.activity.type == 1) {
				uni.navigateTo({
					url: `/pages/activity/goods_details/index?id=${item.activity.id}&type=1&time=${item.activity.time}&status=1`
				})
			} else if (item.activity && item.activity.type == 2) {
				uni.navigateTo({
					url: `/pages/activity/goods_bargain_details/index?id=${item.activity.id}&spid=${uid}`
				})
			} else if (item.activity && item.activity.type == 3) {
				uni.navigateTo({
					url: `/pages/activity/goods_details/index?id=${item.activity.id}&type=3`
				})
			} else {
				reject(item);
			}
		}
	});
}


export function goPage() {
	return new Promise(resolve => {
		resolve(true);
	});
}
