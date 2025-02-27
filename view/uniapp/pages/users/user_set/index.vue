<script>
import { getUserInfo, getLogout } from '@/api/user.js';
import { toLogin } from '@/libs/login.js';
import { mapGetters, mapMutations } from 'vuex';
import colors from '@/mixins/color.js';
import tuiModal from '@/components/tui-modal/index.vue';
import NavBar from '@/components/NavBar.vue';
export default {
	computed: mapGetters(['isLogin']),
	data() {
		return {
			userInfo: {},
			isShowAuth: false,
			autoplay: this.$store.state.app.autoplay,
			loginType: 'h5',
			showModal: false
		};
	},
	mixins: [colors],
	components: { tuiModal, NavBar },
	onShow() {
		if (this.isLogin) {
			this.getUserInfo();
		} else {
			toLogin();
		}
	},
	methods: {
		...mapMutations(['SET_AUTOPLAY']),
		/**
		 * 小程序设置
		 */
		Setting() {
			uni.openSetting({
				success: function (res) {}
			});
		},
		getUserInfo() {
			let that = this;
			getUserInfo().then((res) => {
				that.userInfo = res.data;
			});
		},
		autoplayChange(event) {
			this.SET_AUTOPLAY(event.detail.value);
		},
		handleClick(e) {
			let index = e.index;
			if (index == 1) {
				getLogout()
					.then((res) => {
						this.showModal = false;
						this.$store.commit('LOGOUT');
						uni.reLaunch({
							url: '/pages/index/index'
						});
					})
					.catch((err) => {});
			} else {
				this.showModal = false;
			}
		},
		outLogin() {
			this.showModal = true;
		}
	}
};
</script>

<template>
	<!-- 设置 -->
	<view :style="colorStyle">
		<!-- #ifdef MP -->
		<NavBar showBack bagColor="#f5f5f5" titleText="设置"></NavBar>
		<!-- #endif -->
		<view class="userSet">
			<navigator url="/pages/users/user_info/index" hover-class="none" class="userInfo acea-row row-between-wrapper rd-24rpx mx-20">
				<view class="picTxt acea-row row-middle">
					<view class="pictrue">
						<image :src="userInfo.avatar"></image>
					</view>
					<view class="text">
						<view class="name line1">{{ userInfo.nickname }}</view>
						<view class="info">ID：{{ userInfo.uid }}</view>
					</view>
				</view>
				<view class="iconfont icon-ic_rightarrow"></view>
			</navigator>
			<view class="list rd-24rpx mx-20">
				<navigator url="/pages/users/user_address_list/index" hover-class="none" class="item acea-row row-between-wrapper">
					<view>地址管理</view>
					<view class="grab">
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</navigator>
				<navigator url="/pages/users/user_invoice_list/index" hover-class="none" class="item acea-row row-between-wrapper">
					<view>发票管理</view>
					<view class="grab">
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</navigator>
			</view>
			<view class="list rd-24rpx mx-20">
				<view class="item acea-row row-between-wrapper">
					<view>移动网络下视频自动播放</view>
					<switch :checked="autoplay" @change="autoplayChange" />
				</view>
				<!-- #ifdef MP -->
				<view class="item acea-row row-between-wrapper">
					<view>权限设置</view>
					<view class="input grab" @click="Setting">
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</view>
				<!-- #endif -->
				<navigator url="/pages/users/user_agreement_list/index" hover-class="none" class="item acea-row row-between-wrapper">
					<view>政策协议</view>
					<view class="input grab">
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</navigator>
			</view>
			<!-- #ifdef H5 -->
			<view class="log-out list rd-24rpx mx-20 acea-row row-center-wrapper" @click="outLogin" v-if="!this.$wechat.isWeixin()">退出登录</view>
			<!-- #endif -->
			<!-- #ifdef APP-PLUS -->
			<view class="log-out list rd-24rpx mx-20 acea-row row-center-wrapper" @click="outLogin">退出登录</view>
			<!-- #endif -->
			<!-- 确认框 -->
			<tuiModal :show="showModal" title="提示" content="确认退出登录?" :maskClosable="false" @click="handleClick"></tuiModal>
		</view>
		<home></home>
	</view>
</template>

<style lang="scss">
.userSet {
	.userInfo {
		margin-top: 20rpx;
		background-color: #fff;
		padding: 32rpx 16rpx 32rpx 32rpx;

		.iconfont {
			font-size: 30rpx;
			color: #999;
		}

		.picTxt {
			.text {
				margin-left: 30rpx;
				font-weight: 400;

				.name {
					font-size: 32rpx;
					color: #333;
				}

				.info {
					font-size: 24rpx;
					color: #999;
					margin-top: 5rpx;
				}
			}

			.pictrue {
				width: 120rpx;
				height: 120rpx;

				image {
					width: 100%;
					height: 100%;
					border: 1px solid #eee;
					border-radius: 50%;
				}
			}
		}
	}
	.list {
		background-color: #fff;
		margin-top: 20rpx;
		.item {
			padding: 32rpx 20rpx 24rpx 24rpx;
			font-size: 30rpx;
			color: #333;
			.grab {
				color: #ccc;
				.iconfont {
					font-size: 30rpx;
					color: #999;
					margin-left: 6rpx;
				}
			}

			/deep/.uni-switch-input {
				width: 84rpx;
				height: 48rpx;
				margin: -8rpx 0;

				&::before {
					width: 80rpx;
					height: 44rpx;
				}

				&::after {
					width: 44rpx;
					height: 44rpx;
				}
			}
		}
	}
	.log-out {
		font-size: 30rpx;
		text-align: center;
		color: #333333;
		width: 710rpx;
		height: 98rpx;
		border-radius: 24rpx;
		margin: 30rpx auto 0 auto;
	}
}
</style>
