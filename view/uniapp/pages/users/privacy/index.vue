<template>
	<view>
		<view class="content">
			<!-- #ifdef MP-WEIXIN -->
			<rich-text :nodes="content"></rich-text>
			<!-- #endif -->
			<!-- #ifdef H5 || APP-PLUS -->
			<view v-html="content"></view>
			<!-- #endif -->
		</view>
	</view>
</template>

<script>
let sysHeight = uni.getWindowInfo().statusBarHeight;
import { getUserAgreement, getPayVipInfoApi } from '@/api/user.js';
export default {
	data() {
		return {
			content: ``,
			sysHeight: sysHeight || 0,
			type: ''
		};
	},
	computed: {
		pageName() {
			let list = {
				privacy: '隐私协议',
				cancel: '注销协议',
				user: '用户协议',
				supplier: '供应商入驻协议'
			};
			uni.setNavigationBarTitle({
				title: list[this.type]
			});
			return list[this.type];
		}
	},
	onLoad(e) {
		this.type = e.type;
		if (e) {
			if(e.type == 'payVip'){
				getPayVipInfoApi().then(res=>{
					this.content = res.data.member_explain.content;
				}).catch((err) => {
					that.$util.Tips({
						title: err.msg
					});
				});
			}else{
				getUserAgreement(e.type).then((res) => {
					this.content = res.data.content;
				})
				.catch((err) => {
					that.$util.Tips({
						title: err.msg
					});
				});
			}
			
		} else {
			getUserAgreement('privacy').then((res) => {
				this.content = res.data.content;
			})
			.catch((err) => {
				that.$util.Tips({
					title: err.msg
				});
			});
		}
	},
	mounted() {},
	methods: {
		goBack() {
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style scoped lang="scss">
page {
	background-color: #fff;
}
.content {
	padding: 0 30rpx 40rpx 30rpx;
}
.sys-head {
	position: fixed;
	width: 100%;
	background-color: #fff;
	top: 0;
	left: 0;
	z-index: 9;
	.sys-title {
		height: 43px;
		line-height: 43px;
		text-align: center;
		position: relative;
		.iconfont {
			position: absolute;
			left: 20rpx;
		}
	}
}
</style>
