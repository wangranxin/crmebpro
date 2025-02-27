<template>
  <!-- 积分详情 -->
	<view :style="colorStyle">
		<!-- #ifdef MP || APP-PLUS -->
		<view class="accountTitle">
			<view :style="{height:getHeight.barTop+'px'}"></view>
			<view class="sysTitle acea-row row-center-wrapper" :style="{height:getHeight.barHeight+'px'}">
				<view>积分详情</view>
				<text class="iconfont icon-ic_leftarrow" @click="goarrow"></text>
			</view>
		</view>
		<view :style="{height:(getHeight.barTop+getHeight.barHeight)+'px'}"></view>
		<!-- #endif -->
		<view class='integral-details'>
			<view class='header'>
				<view class='currentScore acea-row row-middle'>
					<image src="../static/integral.png"></image>
					<view>我的积分</view>
					<view class="notice acea-row row-center-wrapper">
					  <view class="iconfont icon-ic_horn1"></view>
					  <view v-if="userInfo.clear_integral>0 && userInfo.clear_time>0">您的积分将于{{ userInfo.clear_time | dateFormat }}清零</view>
					  <view v-else>积分数值的高低会直接影响您的会员等级</view>
					</view>
				</view>
				<view class="scoreNum">{{userInfo.integral}}</view>
				<view class='nav'>
					<view class="title acea-row row-middle">
						<text class="name">积分详情</text>
						<navigator url="/pages/users/user_integral_detail/index" hover-class="none">
							积分明细<text class="iconfont icon-ic_rightarrow"></text>
						</navigator>
					</view>
					<view class="list acea-row row-middle row-around">
						<view class='item'>
							<view class='num'>{{userInfo.sum_integral}}</view>
							<view>累计积分</view>
						</view>
						<view class="line"></view>
						<view class='item'>
							<view class='num'>{{userInfo.deduction_integral}}</view>
							<view>累计消费</view>
						</view>
						<view class="line"></view>
						<view class='item'>
							<view class='num'>{{userInfo.frozen_integral}}</view>
							<view>冻结积分</view>
						</view>
					</view>
				</view>
				<view class="pictrue">
					<image :src="imgHost+'/statics/images/balance_species.png'"></image>
				</view>
			</view>
			<view class='wrapper'>
				<view class="title">互动玩积分</view>
				<view class="list acea-row row-bottom">
					<view class="item">
						<view class="name">下单抵扣</view>
						<view>购买商品可获得</view>
						<navigator hover-class='none' open-type="switchTab" url="/pages/index/index" class="bnt acea-row row-center-wrapper">去下单</navigator>
					</view>
					<view class="item on">
						<view class="name">积分兑换</view>
						<view>购买商品可获得</view>
						<navigator hover-class='none' url='/pages/activity/points_mall/index' class="bnt acea-row row-center-wrapper">去兑换</navigator>
					</view>
					<view class="item on1">
						<view class="name">抽奖赢积分</view>
						<view>购买商品可获得</view>
						<navigator hover-class='none' url='/pages/goods/lottery/grids/index?type=1' class="bnt acea-row row-center-wrapper">去抽奖</navigator>
					</view>
				</view>
			</view>
			<view class='wrapper on'>
				<view class="title">做任务领积分</view>
				<view class="task">
					<view class="item acea-row row-between-wrapper">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue">
								<image src="../static/goumai.png"></image>
							</view>
							<view class="text">
								<view class="name">购买商品</view>
								<view class="info">购买商品可获得</view>
							</view>
						</view>
						<navigator hover-class='none' open-type="switchTab" url="/pages/index/index" class="bnt acea-row row-center-wrapper">去完成</navigator>
					</view>
					<view class="item acea-row row-between-wrapper" v-if="signInStatus">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue">
								<image src="../static/qiandao.png"></image>
							</view>
							<view class="text">
								<view class="name">每日签到</view>
								<view class="info">每日签到可获得</view>
							</view>
						</view>
						<navigator hover-class='none' url='/pages/users/user_sgin/index' class="bnt acea-row row-center-wrapper">去完成</navigator>
					</view>
					<view class="item acea-row row-between-wrapper">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue">
								<image src="../static/choujiang.png"></image>
							</view>
							<view class="text">
								<view class="name">九宫格抽奖</view>
								<view class="info">评价完成可获得</view>
							</view>
						</view>
						<navigator hover-class='none' url='/pages/goods/lottery/grids/index?type=1' class="bnt acea-row row-center-wrapper">去完成</navigator>
					</view>
				</view>
			</view>
			<view class="footer"></view>
			<home></home>
		</view>
	</view>
</template>

<script>
	import { HTTP_REQUEST_URL } from '@/config/app';
	import {postSignUser, getSignConfig} from '@/api/user.js';
	import dayjs from '@/plugin/dayjs/dayjs.min.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import emptyPage from '@/components/emptyPage.vue';
	import colors from '@/mixins/color'
	export default {
		components: {
			emptyPage,
		},
		filters: {
			dateFormat: function(value) {
				return dayjs(value * 1000).format('YYYY-MM-DD'); 
			}
		},
		mixins:[colors],
		data() {
			return {
				getHeight: this.$util.getWXStatusHeight(),
				imgHost:HTTP_REQUEST_URL,
				userInfo: {},
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				isTime: 0,
				signInStatus:1,
			};
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						// #ifdef H5 || APP-PLUS
						this.getUserInfo();
						// #endif
					}
				},
				deep: true
			}
		},
		onPageScroll(object) {
			uni.$emit('scroll');
		},
		onLoad() {
			if (this.isLogin) {
				this.getUserInfo();
				this.signStatus();
			} else {
				toLogin()
			}
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
		},
		methods: {
			signStatus(){
				getSignConfig().then(res=>{
					this.signInStatus = res.data.signStatus;
				}).catch(err=>{
					this.$util.Tips({
						title: err
					});
				})
			},
			goarrow(){
				uni.navigateBack()
			},
			getUserInfo: function() {
				let that = this;
				postSignUser({
					sign: 1,
					integral: 1,
					all: 1
				}).then(function(res) {
					that.$set(that, 'userInfo', res.data);
					let clearTime = res.data.clear_time;
					let showTime = clearTime-(86400*14);
					let timestamp = Date.parse(new Date())/1000;
					if(showTime < timestamp){
						that.isTime = 1
					}else{
						that.isTime = 0
					}
				});
			}
		}
	}
</script>

<style scoped lang="scss">
	@keyframes kf {
		0% {
			transform: translateY(8rpx);
		}
	
		100% {
			transform: translateY(0);
		}
	}
	.footer{
		height: 30rpx;
		height: calc(30rpx + constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		height: calc(30rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
	}
	.accountTitle{
		background: linear-gradient(90deg, var(--view-theme) 0%, var(--view-gradient) 100%);
		position: fixed;
		left:0;
		top:0;
		width: 100%;
		z-index: 99;
		.sysTitle{
			width: 100%;
			position: relative;
			font-weight: 500;
			color: #fff;
			font-size: 30rpx;
			.iconfont{
				position: absolute;
				font-size: 36rpx;
				left:11rpx;
				width: 60rpx;
			}
		}
	}
	
	.integral-details{
		.wrapper{
			width: 710rpx;
			background: #FFFFFF;
			border-radius: 24rpx;
			margin: 86rpx auto 0 auto;
			padding-bottom: 22rpx;
			&.on{
				margin-top: 20rpx;
				padding-bottom: 32rpx;
			}
			.title{
				font-size: 32rpx;
				font-weight: 600;
				color: #333333;
				padding: 32rpx 32rpx 42rpx 32rpx;
			}
			.list{
				padding: 0 23rpx;
				.item{
					background-image: url('../static/integralBg03.png');
					background-repeat: no-repeat;
					background-size: 100% 100%;
					width: 212rpx;
					height: 294rpx;
					text-align: center;
					font-weight: 400;
					color: #999999;
					font-size: 24rpx;
					padding-top: 110rpx;
					
					&.on{
						background-image: url('../static/integralBg02.png');
					}
					
					&.on1{
						background-image: url('../static/integralBg01.png');
					}
					
					&~.item{
						margin-left: 14rpx;
					}
					
					.name{
						color: #333333;
						font-size: 28rpx;
						margin-bottom: 8rpx;
					}
					
					.bnt{
						width: 136rpx;
						height: 56rpx;
						background: linear-gradient(90deg, var(--view-theme) 0%, var(--view-gradient) 100%);
						border-radius: 50rpx;
						font-weight: 500;
						color: #FFFFFF;
						font-size: 24rpx;
						margin: 20rpx auto 0 auto;
					}
				}
			}
			.task{
				padding: 0 24rpx;
				.item{
					&~.item{
						margin-top: 52rpx;
					}
					.bnt{
						width: 136rpx;
						height: 56rpx;
						background: linear-gradient(90deg, var(--view-theme) 0%, var(--view-gradient) 100%);
						border-radius: 50rpx;
						color: #fff;
						font-weight: 500;
						font-size: 24rpx;
					}
					.picTxt{
						.pictrue{
							width: 88rpx;
							height: 88rpx;
							image{
								width: 100%;
								height: 100%;
							}
						}
						.text{
							margin-left: 16rpx;
							font-weight: 400;
							.name{
								font-size: 28rpx;
								color: #333333;
							}
							.info{
								color: #999999;
								font-size: 24rpx;
								margin-top: 8rpx;
								.num{
									color: var(--view-theme);
									margin-left: 4rpx;
								}
							}
						}
					}
				}
			}
		}
	}
	
	.integral-details .header {
		width: 100%;
		height: 380rpx;
		font-size: 72rpx;
		color: #fff;
		padding: 36rpx 0 45rpx 0;
		box-sizing: border-box;
		background: linear-gradient(90deg, var(--view-theme) 0%, var(--view-gradient) 100%);
		border-radius: 0 0 640rpx 640rpx/0 0 80rpx 80rpx;
		position: relative;
		.pictrue{
			position: absolute;
			width: 412rpx;
			height: 378rpx;
			right: 0;
			top: 22rpx;
			animation: kf 1.2s linear .8s infinite alternate;
			image{
				width: 100%;
				height: 100%;
			}
		}
	}

	.integral-details .header .currentScore {
		font-size: 24rpx;
		color: #fff;
		margin-left: 32rpx;
		
		image{
			width: 38rpx;
			height: 37rpx;
			display: block;
			margin-right: 4rpx;
		}
		
		.notice{
			background: rgba(0,0,0,0.1);
			padding: 13rpx 16rpx 15rpx 16rpx;
			border-radius: 50rpx;
			font-size: 22rpx;
			color: #FFFFFF;
			margin-left: 16rpx;
			
			.iconfont{
				font-size: 26rpx;
				margin-right: 8rpx;
				margin-top: 5rpx;
			}
		}
	}

	.integral-details .header .scoreNum {
		font-family: "SemiBold";
		margin: 8rpx 0 0 40rpx;
	}

	.integral-details .header .nav {
		background-image: url('../static/integralBg.png');
		background-repeat: no-repeat;
		background-size: 100% 100%;
		width: 710rpx;
		height: 240rpx;
		margin-top: 28rpx;
		margin: 28rpx auto 0 auto;
		position: relative;
		z-index: 9;
		
		.title{
			font-size: 22rpx;
			color: var(--view-theme);
			padding-top: 18rpx;
			margin-left: 32rpx;
			
			.name{
				font-size: 32rpx;
				font-weight: 600;
				color: #333333;
				margin-right: 16rpx;
			}
			
			.iconfont{
				font-size: 24rpx;
			}
		}
		
		.list{
			margin-top: 48rpx;
			.item{
				color: #999999;
				font-size: 24rpx;
				text-align: center;
				width: 33%;
				.num{
					color: #333333;
					font-size: 40rpx;
					margin-top: 14rpx;
					font-family: "SemiBold";
					margin-bottom: 5px;
				}
			}
			.line{
				width: 1px;
				height: 42rpx;
				background-color: #DDDDDD;
			}
		}
	}
</style>
