<script>
	import { toLogin } from '@/libs/login.js';
export default {
	props: {
		userInfo: {
			type: Object,
			default: () => {}
		},
		property: {
			type: Array,
			default: () => []
		},
		// perShowType 0 手机号 1 ID
		perShowType: {
			type: Number,
			default: 0
		}
	},
	inject: ['intoPage', 'tapQrCode', 'goMenuPage', 'goEdit', 'bindPhone'],
	methods: {
		openAuto(){
			toLogin();
		}
	}
};
</script>
<template>
	<view class="header">
		<!-- 用户信息、设置 -->
		<view class="acea-row row-middle user">
			<view class="avatar relative" :class="userInfo.pay_vip_status ? 'svip-border' : 'white-border'"
				@tap="goEdit">
				<image class="avatar-img" :src="userInfo.avatar" mode="aspectFill" v-if="userInfo.avatar"></image>
				<image class="avatar-img" src="@/static/images/f.png" mode="aspectFill" v-else></image>
				<image class="vip-badge" src="@/static/img/svip_badge.png" v-show="userInfo.pay_vip_status"></image>
			</view>
			<view class="name-wrap">
				<view class="name display-add" v-if="!userInfo.uid" @tap="openAuto">请点击授权</view>
				<view class="acea-row row-middle" v-if="userInfo.uid">
					<view class="name">{{ userInfo.nickname }}</view><strong></strong>
					<view class="vip flex-center" v-if="userInfo.level">
						<text class="iconfont icon-huiyuandengji"></text>
						V{{userInfo.level}}
					</view>
				</view>
				<view class="bind-phone" v-if="!userInfo.phone && userInfo.uid" @tap="bindPhone">绑定手机号</view>
				<view class="phone" v-else>{{ perShowType ? 'ID：' + userInfo.uid : userInfo.phone }}</view>
			</view>
			<view class="">
				<text class="iconfont icon-a-ic_QRcode fs-40" @tap="tapQrCode"><text class="tips">会员码</text></text>
				<text class="iconfont icon-a-ic_setup1 fs-40 mx-34" @tap="intoPage('/pages/users/user_set/index')"></text>
				<text class="iconfont icon-ic_message3 fs-40" @tap="intoPage('/pages/users/message_center/index')">
					<text v-if="userInfo.service_num" class="number">{{ userInfo.service_num >= 100 ? '99+' : userInfo.service_num }}</text>
				</text>
			</view>
		</view>
		<!-- 余额、优惠券 -->
		<view class="acea-row balance-coupon">
			<view class="item" v-for="(item, index) in property" @tap="goMenuPage(item.url)" :key="index">
				<view class="value num-fa-semi">{{ item.value || 0 }}</view>
				<view>{{ item.label }}</view>
			</view>
		</view>
		<!-- 会员中心、积分商城 -->
		<view class="acea-row member-points">
			<view class="acea-row row-middle row-center item" @tap="intoPage(userInfo.level_status == 1 ? '/pages/users/user_vip/index' : '/pages/annex/vip_grade_active/index')">
				<view>
					<view>会员中心</view>
					<view class="arrow">
						查看新权益
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</view>
				<image src="@/static/images/user-member.png" class="image"></image>
			</view>
			<view class="acea-row row-middle row-center item" @tap="intoPage('/pages/activity/points_mall/index')">
				<view>
					<view>积分商城</view>
					<view class="arrow">
						限量兑神券
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</view>
				<image src="@/static/images/user-points.png" class="image"></image>
			</view>
		</view>
	</view>
</template>

<style lang="scss" scoped>
.header {
	padding: 18rpx 0 0rpx;
	border-bottom-right-radius: 50% 40rpx;
	border-bottom-left-radius: 50% 40rpx;
	margin-bottom: 18rpx;
	background-color: var(--view-theme);

	.user {
		padding: 0 40rpx 0 30rpx;

		.iconfont {
			position: relative;
			color: #ffffff;
			font-size: 40rpx;
		}
	}

	.bind-phone {
		margin-top: 12rpx;
		background: rgba(255, 255, 255, 0.3);
		border-radius: 30px;
		width: max-content;
		text-align: center;
		font-size: 20rpx;
		font-weight: 400;
		color: #ffffff;
		line-height: 28rpx;
		padding: 6rpx 16rpx;
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
		border-top: 6rpx solid #ffd89c;
		/* 修改颜色以改变三角形颜色 */
	}

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
		top: -20rpx;
		right: -10rpx;
		width: 48rpx;
		height: 46rpx;
	}
	.svip-border{
		border: 2rpx solid #F1BB0D;
	}
	.white-border{
		border: 2rpx solid #FFFFFF;
	}

	.name-wrap {
		flex: 1;
		padding: 0 32rpx;
		color: #ffffff;
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
.vip{
	// width: 64rpx;
	height: 26rpx;
	background: #FEF0D9;
	border: 1px solid #FACC7D;
	border-radius: 50rpx;
	font-size: 18rpx;
	font-weight: 500;
	color: #DFA541;
	margin-left: 10rpx;
	padding: 0 6rpx;
	.iconfont {
		font-size: 20rpx !important;
		margin-right: 4rpx;
		color: #DFA541 !important;
	}
}
.balance-coupon {
	margin-top: 44rpx;

	.item {
		flex: 1;
		text-align: center;
		font-weight: 500;
		font-size: 22rpx;
		line-height: 22rpx;
		color: rgba(255, 255, 255, 0.6);
	}

	.value {
		margin-bottom: 12rpx;
		font-weight: 400;
		font-size: 32rpx;
		line-height: 32rpx;
		color: rgba(255, 255, 255, 0.85);
	}
}

.member-points {
	border-radius: 20rpx;
	margin: 20rpx;
	background-color: #ffffff;

	.item {
		position: relative;
		flex: 1;
		height: 134rpx;
		padding-left: 40rpx;
		font-weight: 500;
		font-size: 28rpx;
		line-height: 34rpx;
		color: #333333;

		&::before {
			content: '';
			position: absolute;
			top: 50%;
			left: 0;
			height: 48rpx;
			border-left: 1rpx solid #eeeeee;
			transform: translateY(-50%);
		}

		&:first-child::before {
			display: none;
		}
		.iconfont {
			position: relative;
			font-size: 20rpx;
		}
	}

	.arrow {
		margin-top: 12rpx;
		font-weight: 400;
		font-size: 22rpx;
		line-height: 24rpx;
		color: #ff7d00;
	}

	.image {
		width: 88rpx;
		height: 88rpx;
		margin-left: 40rpx;
	}

	.iconfont {
		margin-left: 2rpx;
		font-size: 24rpx;
	}
}
</style>
