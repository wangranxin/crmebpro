<script>
import { HTTP_REQUEST_URL } from '@/config/app';
export default {
	inject: ['intoPage', 'tapQrCode', 'goMenuPage', 'goEdit', 'bindPhone'],
	props: {
		userInfo: {
			type: Object,
			default: () => {}
		},
		// perShowType 0 手机号 1 ID
		perShowType: {
			type: Number,
			default: 0
		}
	},
	computed: {
		headerBg() {
			return `url(${HTTP_REQUEST_URL}/statics/images/users/user_header_bg.png)`;
		}
	},
	methods: {
		getTimePeriod() {
			const currentTime = new Date();
			const currentHour = currentTime.getHours();

			if (currentHour >= 6 && currentHour < 12) {
				return '，早上好';
			} else if (currentHour >= 12 && currentHour < 14) {
				return '，中午好';
			} else if (currentHour >= 14 && currentHour < 18) {
				return '，下午好';
			} else {
				return '，晚上好';
			}
		}
	}
};
</script>

<template>
	<view class="user_info_card relative" :style="{ backgroundImage: headerBg }">
		<view class="flex-between-center top">
			<view class="flex-y-center">
				<view class="avatar relative" :class="userInfo.pay_vip_status ? 'svip-border' : 'white-border'"
					@click="goEdit">
					<image class="avatar-img" :src="userInfo.avatar" mode="aspectFill"  v-if="userInfo.avatar"></image>
					<image class="avatar-img" src="@/static/images/f.png" mode="aspectFill" v-else></image>
					<image class="vip-badge" src="@/static/img/svip_badge.png" v-show="userInfo.pay_vip_status"></image>
				</view>
				<view class="ml-24">
					<view class="fs-36 text--w111-333 lh-50rpx fw-500" v-if="userInfo.nickname">{{ userInfo.nickname }}</view>
					<view class="fs-36 text--w111-333 lh-50rpx fw-500" v-else @tap="intoPage">请点击授权</view>
					<view class="bind-phone" v-if="!userInfo.phone && userInfo.uid" @tap="bindPhone">绑定手机号</view>
					<view class="fs-24 text--w111-333 lh-34rpx mt-8" v-else>{{ perShowType ? 'ID：' + userInfo.uid : userInfo.phone }}</view>
				</view>
			</view>
			<view>
				<text class="iconfont icon-a-ic_QRcode fs-40" @click="tapQrCode"><text class="tips">会员码</text></text>
				<text class="iconfont icon-a-ic_setup1 fs-40 mx-34" @click="intoPage('/pages/users/user_set/index')"></text>
				<text class="iconfont icon-ic_message3 fs-40" @click="intoPage('/pages/users/message_center/index')">
					<text v-if="userInfo.service_num" class="number">{{ userInfo.service_num >= 100 ? '99+' : userInfo.service_num }}</text>
				</text>
			</view>
		</view>
		<view class="w-full bg_zs">
			<view class="svip_card" @tap="intoPage('/pages/annex/vip_paid/index')">
				<view class="h-full flex-between-center pl-32 pr-28 text--w111-FFD89C fs-24">
					<view class="flex-y-center">
						<text class="iconfont icon-ic_crown fs-32"></text>
						<text class="pl-12" v-show="userInfo.pay_vip_status">尊贵的SVIP会员{{ getTimePeriod() }}</text>
						<text class="pl-12" v-show="!userInfo.pay_vip_status">开通svip会员，专享多项特权</text>
					</view>
					<view class="flex-y-center">
						<text>{{userInfo.pay_vip_status ? '去查看' : '去开通'}} </text>
						<text class="iconfont icon-ic_rightarrow fs-24"></text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<style scoped lang="scss">
.navbar {
	position: relative;
	.content {
		position: fixed;
		top: 0;
		right: 0;
		left: 0;
		z-index: 998;
		font-weight: 500;
		font-size: 34rpx;
		color: #ffffff;
	}
}
.avatar {
	width: 80rpx;
	height: 80rpx;
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
	top: -8rpx;
	right: -4rpx;
	width: 24rpx;
	height: 24rpx;
}
.svip-border{
	border: 2rpx solid #F1BB0D;
}
.white-border{
	border: 2rpx solid #FFFFFF;
}
.user_info_card {
	// min-height: 304rpx;
	background-size: 100% 100%;
	.top {
		padding: 44rpx 48rpx 50rpx 48rpx;
	}
	.iconfont {
		position: relative;
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
}

.bg_zs {
	background-image: url('@/static/img/bg_zs.png');
	background-size: 100%;
	background-repeat: no-repeat;
	background-position-y: 56rpx;
	padding-bottom: 130rpx;
}

.svip_card {
	width: 662rpx;
	height: 92rpx;
	background-image: url('@/static/img/user_svip_bg.png');
	background-size: 100%;
	background-repeat: no-repeat;
	position: absolute;
	left: 50%;
	transform: translateX(-50%);
}
.tips {
	position: absolute;
	top: -34rpx;
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
</style>
