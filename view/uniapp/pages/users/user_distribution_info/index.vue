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
import { getDistributionInfo } from '@/api/user.js';
export default {
	data() {
		return {
			content: ``
		};
	},
	computed: {
	},
	onLoad(e) {
		this.distributionInfo();
	},
	mounted() {},
	methods: {
		distributionInfo(){
			getDistributionInfo().then(res=>{
				this.content = res.data.member_explain.content;
			}).catch((err) => {
				this.$util.Tips({
					title: err.msg
				});
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
	padding: 15px 30rpx 40rpx 30rpx;
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