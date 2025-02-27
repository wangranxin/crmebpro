<script>
import { HTTP_REQUEST_URL } from '@/config/app';
// #ifdef MP || APP-PLUS
import topBar from '../topBar.vue';
// #endif
export default {
	components: {
		// #ifdef MP  || APP-PLUS
		topBar
		// #endif
	},
	props: {
		userInfo: {
			type: Object,
			default: () => {}
		},
		commission: {
			type: Object,
			default: () => {}
		},
		// perShowType 0 手机号 1 ID
		perShowType: {
			type: Number,
			default: 0
		},
		isScrolling: {
			type: Boolean,
			default: false
		},
		property: {
			type: Array,
			default: () => []
		}
	},
	inject: ['intoPage', 'tapQrCode', 'goMenuPage', 'goEdit', 'bindPhone'],
	computed: {
		headerBg() {
			return `url(${HTTP_REQUEST_URL}/statics/images/users/template4_bg.png)`;
		}
	}
};
</script>

<template>
	<view class="warp" :style="{ backgroundImage: headerBg }">
		<!-- #ifdef MP || APP-PLUS -->
		<topBar :styleType="5" :isScrolling="isScrolling"></topBar>
		<!-- #endif -->
		<view class="acea-row row-middle user">
			<view class="avatar relative" :class="{'svip-border': userInfo.pay_vip_status}" @click="goEdit">
				<image class="avatar-img" :src="userInfo.avatar" mode="aspectFill" v-if="userInfo.avatar"></image>
				<image class="avatar-img" src="@/static/images/f.png" mode="aspectFill" v-else></image>
				<image class="vip-badge" src="@/static/img/svip_badge.png" v-show="userInfo.pay_vip_status"></image>
			</view>
			<view class="name-wrap">
				<view class="name" v-if="userInfo.nickname">{{ userInfo.nickname }}</view>
				<view class="name display-add" v-else @tap="intoPage">请点击授权</view>
				<view class="phone" v-if="userInfo.phone && userInfo.uid">{{ perShowType ? 'ID：' + userInfo.uid : userInfo.phone }}</view>
				<view class="bind-phone" v-else @tap="bindPhone">绑定手机号</view>
			</view>
			<view>
				<text class="iconfont icon-a-ic_QRcode fs-40" @click="tapQrCode"><text class="tips">会员码</text></text>
				<text class="iconfont icon-a-ic_setup1 fs-40 mx-34" @click="intoPage('/pages/users/user_set/index')"></text>
				<text class="iconfont icon-ic_message3 fs-40" @click="intoPage('/pages/users/message_center/index')">
					<text v-if="userInfo.service_num" class="number">{{ userInfo.service_num >= 100 ? '99+' : userInfo.service_num }}</text>
				</text>
			</view>
		</view>

		<view class="acea-row row-middle order justify-between">
			<view class="item" v-for="(item, index) in property" @click="goMenuPage(item.url)" :key="index">
				{{ item.label }}
				<text class="value num-fa-semi">{{ item.value || 0 }}</text>
			</view>
		</view>
	</view>
</template>

<style scoped lang="scss">
.warp {
	background-position: bottom;
	background-size: 100% 100%;
}
.user {
	padding: 60rpx 42rpx 56rpx 30rpx;
	background-position: bottom;
	background-size: 100%;

	.avatar {
		width: 112rpx;
		height: 112rpx;
		border-radius: 50%;
		.avatar-img{
			width: 100%;
			height:100%;
			border-radius: 50%;
			display: block;
		}
	}
	.vip-badge{
		position: absolute;
		top: -16rpx;
		right: -4rpx;
		width: 36rpx;
		height: 36rpx;
	}
	.svip-border{
		border: 2rpx solid #F1BB0D;
	}

	.name-wrap {
		flex: 1;
		padding: 0 32rpx;
		color: #333333;
	}

	.name {
		font-weight: 500;
		font-size: 32rpx;
		line-height: 44rpx;
	}

	.phone {
		margin-top: 10rpx;
		font-size: 24rpx;
		line-height: 34rpx;
	}
	.bind-phone {
		margin-top: 12rpx;
		background: #fff;
		border-radius: 30px;
		width: max-content;
		text-align: center;
		font-size: 20rpx;
		font-weight: 400;
		color: #333333;
		line-height: 28rpx;
		padding: 6rpx 16rpx;
	}
	.iconfont {
		position: relative;
	}

	.tips {
		position: absolute;
		bottom: 100%;
		left: 50%;
		height: 28rpx;
		padding: 0 14rpx;
		border-radius: 14rpx;
		margin-bottom: 4rpx;
		background-color: #ffd89c;
		transform: translateX(-50%);
		white-space: nowrap;
		font-size: 16rpx;
		line-height: 28rpx;
		color: #9e5e1a;
	}
	.tips::before {
		content: '';
		position: absolute;
		bottom: -6rpx;
		left: calc(50% - 6rpx);
		width: 0;
		height: 0;
		border-left: 6rpx solid transparent;
		border-right: 6rpx solid transparent;
		border-top: 6rpx solid #ffd89c; /* 修改颜色以改变三角形颜色 */
	}
	.number {
		position: absolute;
		top: -8rpx;
		right: 0;
		min-width: 10rpx;
		height: 24rpx;
		padding: 0 6rpx;
		border: 2rpx solid var(--view-theme);
		border-radius: 12rpx;
		background-color: #ffffff;
		transform: translateX(50%);
		font-weight: 500;
		font-size: 18rpx;
		line-height: 24rpx;
		color: var(--view-theme);
	}
}
.order {
	padding: 0 32rpx 32rpx 32rpx;
	font-size: 26rpx;
	line-height: 36rpx;
	color: #999999;

	.item + .item {
		margin-left: 40rpx;
	}

	.value {
		margin-left: 8rpx;
		font-size: 28rpx;
		line-height: 32rpx;
		color: #333333;
	}
}
</style>
