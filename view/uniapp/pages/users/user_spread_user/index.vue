<template>
	<view :style="[colorStyle]">
		<!-- #ifdef MP || APP-PLUS -->
		<NavBar titleText="分销中心" :iconColor="iconColor" :textColor="iconColor" :isScrolling="isScrolling" showBack></NavBar>
		<!-- #endif -->
		<view class="headerBg"></view>
		<view class="my-promotion">
			<view class="price-wrapper">
				<view class="navigator acea-row row-middle row-right" v-if="userInfo.division_type == 0">
					<view class="inner acea-row row-middle" @click="jumbPath(10)">
						<text class="iconfont icon-huiyuandengji"></text>
						{{ levelInfo.name || '等级未解锁' }}
						<text class="iconfont icon-ic_rightarrow"></text>
					</view>
				</view>
				<view class="navigator acea-row row-middle row-right" 
					v-if="userInfo.division_type == 1">
					<view class="inner acea-row row-middle" v-if="userInfo.division_apply_open == 1"
						@tap="copyInvite">
						邀请码:
						{{ userInfo.division_invite }}
					</view>
				</view>
				<view class="price-box" :class="!headerStatus ? 'header-height' : ''" :style="[headerBg]">
					<view class="box-top acea-row row-center">
						<view class="" @click="jumbPath(0)">可提现</view>
					</view>
					<view class="com-count acea-row row-center" @click="jumbPath(6)">
						{{ userInfo.commissionCount || '0.00' }}
					</view>
					<view class="box-btn">
						<view class="item acea-row row-column row-middle">
							<view class="text">累计佣金(元)</view>
							<view class="num">
								{{ userInfo.accumulate || '0.00' }}
							</view>
						</view>
						<view class="item acea-row row-column row-middle">
							<view class="text">冻结佣金(元)</view>
							<view class="num">
								{{ userInfo.broken_commission || '0.00' }}
							</view>
						</view>
					</view>
					<view class="btn-wrap">
						<view class="btn acea-row row-center-wrapper" @click="jumbPath(0)">立即提现</view>
					</view>
				</view>
			</view>
			<view class="statistics acea-row">
				<view v-for="(item, index) in menus" :key="index" class="item mb acea-row row-column row-center-wrapper"
					@click="jumbPath(item.jump_type)">
					<view class="img">
						<image :src="imgHost + '/statics/images/' + item.img" class="image"></image>
					</view>
					<view class="item-r">
						<view class="text">{{ item.name }}</view>
					</view>
				</view>
			</view>
		</view>
		<home></home>
		<BaseCode v-if="isCode" :code="code" :codeImg="codeImg" :isShowCode.sync="isCode"></BaseCode>
		<view class="img-modal" v-if="agentTgcg" :class="[agentTgcg ? 'tui-modal-show' : '']">
			<div class="img-box">
				<img :src="imgHost + '/statics/images/agent_tgcg.png'" alt="" />
				<view class="close" @click="agentTgcg = false"></view>
			</div>
		</view>
		<task :inv-show="taskShow" :task="task" @inv-close="
				() => {
					taskShow = false;
				}
			"></task>
	</view>
</template>

<script>
	import {
		getUserInfo,
		agentLevelList,
		agentLevelTaskList,
		moneyList,
		spreadOrder,
		spreadPeople,
		getAgentCode,
		agentSpread
	} from '@/api/user.js';
	import {
		openExtrctSubscribe
	} from '@/utils/SubscribeMessage.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import task from './components/task.vue';
	import {
		mapGetters
	} from 'vuex';
	import BaseMoney from '@/components/BaseMoney.vue';
	import NavBar from '@/components/NavBar.vue';
	import colors from '@/mixins/color.js';
	import {
		HTTP_REQUEST_URL
	} from '@/config/app';
	import BaseCode from '@/components/BaseCode.vue';
	export default {
		components: {
			task,
			BaseMoney,
			NavBar,
			BaseCode
		},
		mixins: [colors],
		data() {
			return {
				// #ifdef MP || APP-PLUS
				getHeight: this.$util.getWXStatusHeight(),
				iconColor: '#000000',
				isScrolling: false,
				// #endif
				imgHost: HTTP_REQUEST_URL,
				userInfo: {},
				taskShow: false,
				yesterdayPrice: 0.0,
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				distributionLevel: [],
				levelList: [],
				task: [],
				levelInfo: {},
				config: {
					bar: {
						code: '',
						color: ['#000'],
						bgColor: '#FFFFFF', // 背景色
						width: 480, // 宽度
						height: 110 // 高度
					},
					qrc: {
						code: '123123',
						size: 380, // 二维码大小
						level: 3, //等级 0～4
						bgColor: '#FFFFFF', //二维码背景色 默认白色
						border: {
							color: ['#eee', '#eee'], //边框颜色支持渐变色
							lineWidth: 3 //边框宽度
						},
						// img: '/static/logo.png', //图片
						// iconSize: 40, //二维码图标的大小
						color: ['#333', '#333'] //边框颜色支持渐变色
					}
				},
				// 员工/正常推广
				speadMenuList: [{
						img: 'spread_dd.png',
						name: '分销订单',
						jump_type: 8
					},
					{
						img: 'spread_td.png',
						name: '我的团队',
						jump_type: 9
					},
					{
						img: 'spread_ph.png',
						name: '佣金排行',
						jump_type: 2
					},
					{
						img: 'spread_yj.png',
						name: '佣金记录',
						jump_type: 6
					},
					{
						img: 'spread_tgr.png',
						name: '推广人排行',
						jump_type: 1
					},
					{
						img: 'spread_yq.png',
						name: '邀请好友',
						jump_type: 5
					},
					{
						img: 'spread_dj.png',
						name: '分销等级',
						jump_type: 10
					},
					{
						img: 'spread_sm.png',
						name: '分销说明',
						jump_type: 11
					}
				],
				// 代理商列表
				agentMenuList: [{
						img: 'agent_yqyg.png',
						name: '邀请员工',
						jump_type: 'code'
					},
					{
						img: 'spread_tgr.png',
						name: '推广人排行',
						jump_type: 1
					},
					{
						img: 'agent_yjmx.png',
						name: '佣金明细',
						jump_type: 6
					},
					{
						img: 'agent_yjph.png',
						name: '佣金排行',
						jump_type: 2
					},
					{
						img: 'agent_tgdd.png',
						name: '代理商推广订单',
						jump_type: 8
					},
					{
						img: 'agent_yglb.png',
						name: '员工列表',
						jump_type: 12
					},
					{
						img: 'spread_yq.png',
						name: '邀请好友',
						jump_type: 5
					},
				],
				// 事业部列表
				divisionMenuList: [{
						img: 'spread_td.png',
						name: '我的团队',
						jump_type: 9
					},
					{
						img: 'agent_sybtgdd.png',
						name: '推广订单',
						jump_type: 8
					},
					{
						img: 'spread_tgr.png',
						name: '推广人排行',
						jump_type: 1
					},
					{
						img: 'agent_yjph.png',
						name: '佣金排行',
						jump_type: 2
					},
					{
						img: 'agent_yjmx.png',
						name: '佣金明细',
						jump_type: 6
					},
					{
						img: 'spread_yq.png',
						name: '邀请好友',
						jump_type: 5
					},
				],
				listData: [],
				sel: 0,
				speedAll: 0,
				headerStatus: false,
				isSpead: 0,
				agentTgcg: false,
				isCode: false,
				code: '',
				codeImg: ''
			};
		},
		computed: {
			...mapGetters(['isLogin']),
			headerBg() {
				let img;
				if (this.userInfo.division_type == 0) {
					img = '/statics/images/spread_bg.png';
				} else if (this.userInfo.division_type == 1) {
					if (this.userInfo.division_apply_open == 1) {
						img = '/statics/images/spread_bg.png';
					} else {
						img = '/statics/images/spread_bg_fill.png';
					}
				} else {
					img = '/statics/images/spread_bg_fill.png';
				}
				return {
					backgroundImage: 'url(' + HTTP_REQUEST_URL + img + ')'
				};
			},
			menus() {
				let arr;
				if (this.userInfo.division_type == 1) {
					arr = this.divisionMenuList;
				} else if (this.userInfo.division_type == 2) {
					arr = this.agentMenuList;
				} else {
					arr = this.speadMenuList;
				}
				return arr;
			}
		},
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						//this.getUserInfo();
					}
				},
				deep: true
			}
		},
		onLoad(options) {
			// type 存在为事业部 否则为普通推广
			// this.isSpead = options.type == 2 ? 2 : 1;
			if (this.isLogin) {
				this.agentLevelList();
				// this.getUserInfo()
				this.clickTab(0);
				// #ifdef MP
				const queryData = uni.getEnterOptionsSync(); // uni-app版本 3.5.1+ 支持
				if (queryData.query.scene) {
					this.$Cache.set('agent_id', queryData.query.scene);
				}
				// #endif
				// #ifndef MP
				if (options.agent_id) {
					this.$Cache.set('agent_id', options.agent_id);
				}
				// #endif
			} else {
				toLogin();
			}
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
			if (this.$Cache.get('agent_id')) this.bindAgent();
		},
		methods: {
			// 绑定员工关系
			bindAgent(agent_id) {
				agentSpread({
					// #ifdef MP
					agent_code: this.$Cache.get('agent_id')
					// #endif
					// #ifndef MP
					agent_id: this.$Cache.get('agent_id')
					// #endif
				}).then((res) => {
					this.$Cache.clear('agent_id');
					this.agentTgcg = true
				}).catch((err) => {
					this.$util.Tips({
						title: err
					});
				});
			},
			moveHandle() {},
			agentLevelList() {
				agentLevelList().then((res) => {
					const {
						level_info,
						level_list,
						task,
						user
					} = res.data;
					this.levelInfo = level_info;
					this.userInfo = user;
					if (user.agent_id == 0 && this.isSpead == 2) {
						// this.agentMenuList.shift();
					}
					// this.taskInfo = task;
					this.levelList = level_list;
					this.headerStatus = level_list.length ? true : false;
					this.level_id = level_info.id || 0;
					const index = level_list.findIndex((grade, v) => grade.id === user.agent_level);
					if (index !== -1) {
						this.swiperIndex = index === -1 ? 0 : index + 1;
					}
					let info = this.levelList[index === -1 ? 0 : index + 1];
					this.levelInfo.icon = info.icon;
					this.level_id = (info !== undefined ? info.id : 0) || 0;
					this.getTask();
				});
			},
			/**
			 * 获取任务要求
			 */
			getTask() {
				let that = this;
				that.taskNum = 0;
				agentLevelTaskList(that.level_id).then((res) => {
					that.task = res.data.list;
					that.speedAll = res.data.speedAll;
				});
			},
			onLoadFun() {
				this.agentLevelList();
				this.clickTab(0);
				this.isShowAuth = false;
			},
			//跳转
			jumbPath(type) {
				if (type == 'code') {
					// #ifdef MP
					getAgentCode().then((res) => {
						this.codeImg = res.data.url;
						this.isCode = true;
					});
					// #endif
					// #ifndef MP
					this.code = HTTP_REQUEST_URL + '/pages/users/user_spread_user/index?agent_id=' + this.userInfo.agent_id;
					this.$nextTick((e) => {
						this.isCode = true;
					});
					// #endif
				}
				let path = [
					'/pages/users/user_cash/index',
					'/pages/users/promoter_rank/index',
					'/pages/users/commission_rank/index',
					'/pages/users/user_spread_code/index',
					'/pages/users/user_vip/index',
					'/pages/users/user_spread_code/index',
					'/pages/users/user_spread_money/index?type=1',
					'/pages/users/user_spread_money/index?type=4',
					'/pages/users/promoter-order/index',
					`/pages/users/promoter-list/index${this.userInfo.division_type == 1?'?type=1': ''}`,
					'/pages/users/user_distribution_level/index',
					'/pages/users/user_distribution_info/index',
					'/pages/users/agent/staff_list'
				];

				uni.navigateTo({
					url: path[type]
				});
			},
			// 授权关闭
			authColse(e) {
				this.isShowAuth = e;
			},
			openSubscribe(page) {
				uni.showLoading({
					title: '正在加载'
				});
				openExtrctSubscribe()
					.then((res) => {
						uni.hideLoading();
						uni.navigateTo({
							url: page
						});
					})
					.catch(() => {
						uni.hideLoading();
					});
			},
			/**
			 * 获取个人用户信息
			 */
			getUserInfo() {
				let that = this;
				getUserInfo().then((res) => {
					that.$set(that, 'userInfo', res.data);
					if (!res.data.spread_status) {
						that.$util.Tips({
							title: '您目前暂无推广权限'
						}, {
							tab: 4,
							url: '/pages/index/index'
						});
					}
				});
			},
			clickTab(index) {
				this.sel = index;
				let mets = [moneyList, spreadOrder, spreadPeople];
				let data = {
					keyword: '',
					start: 0,
					stop: 0,
					page: 1,
					limit: 10
				};
				if (index == 2) {
					data = {
						...data,
						grade: 0,
						sort: ''
					};
				}
				mets[index](data, 3).then((res) => {
					this.listData = res.data.list;
				});
				// if (index == 0) {} else if (index == 1) {
				// 	this.getRecordOrderList()
				// } else {
				// 	this.userSpreadNewList()
				// }
			},
			copyInvite(){
				let data = this.userInfo.division_invite
				uni.setClipboardData({
					data: data.toString(),
					success: () =>{
						uni.showToast({
							title: '已复制'
						})
					},
					fail:function(err){
						uni.showToast({
							title: err.errMsg,
							icon:'none'
						})
					}
				})
			}
		},
		// #ifdef MP
		onPageScroll(e) {
			uni.$emit('scroll');
			if (e.scrollTop > 50) {
				this.isScrolling = true;
				this.iconColor = '#000000';
			} else if (e.scrollTop < 50) {
				this.isScrolling = false;
				this.iconColor = '#FFFFFF';
			}
		}
		// #endif
	};
</script>

<style scoped lang="scss">
	.headerBg {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 428rpx;
		background: linear-gradient(360deg, rgba(233, 51, 35, 0) 0%, rgba(233, 51, 35, 0.2) 100%);
	}

	.my-promotion {
		padding: 24rpx 20rpx;

		.header {
			background: #212230 url('../static/fxbg.png') no-repeat;
			background-size: 100% 100%;
			padding: 48rpx 30rpx;
			color: #fff;
			position: relative;
			height: 328rpx;

			.user-msg {
				display: flex;
				align-items: flex-start;
				width: 100%;

				.acator {
					width: 90rpx;
					height: 90rpx;
					margin-right: 20rpx;

					image {
						width: 100%;
						height: 100%;
						border-radius: 50%;
					}
				}

				.msg {
					display: flex;
					flex-direction: column;

					.name {
						font-size: 30rpx;
						font-weight: bold;
					}

					.process {
						width: 380rpx;
						height: 6rpx;
						border-radius: 6rpx;
						background: #4d4e59;
						margin: 20rpx 0;

						.fill {
							height: 100%;
							border-radius: 6rpx;
							background-color: #fff;
						}
					}

					.level-info {
						font-size: 20rpx;

						.mr20 {
							margin-right: 40rpx;
						}
					}
				}

				.invite {
					display: flex;
					align-items: center;
					position: absolute;
					right: 0rpx;
					background: rgba(255, 255, 255, 0.14);
					border-radius: 32px 0px 0px 32px;
					color: #ffffff;
					padding: 10rpx 16rpx 10rpx 8rpx;

					.poster-in {
						width: 20rpx;
						height: 20rpx;
						display: flex;
						align-items: center;
						margin-right: 8rpx;

						image {
							width: 100%;
							height: 100%;
						}
					}

					.text {
						font-size: 20rpx;
					}
				}
			}

			.tesk {
				position: absolute;
				bottom: 0;
				width: 690rpx;
				height: 128rpx;
				background: linear-gradient(135deg, #fee8c7 0%, #ffbd6b 100%);
				border-radius: 6px 6px 0px 0px;
				padding: 24rpx 30rpx;
				display: flex;
				justify-content: space-between;
				align-items: center;

				.line {
					width: 1px;
					height: 74rpx;
					background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #9b5900 52%, rgba(255, 255, 255, 0) 100%);
					opacity: 0.2;
					margin: 0 26rpx;
				}

				.tesk-l {
					display: flex;
					align-items: center;

					.fx-leavel {
						display: flex;
						align-items: center;
						color: #9b5900;
						font-size: 30rpx;
						font-weight: bold;

						.point {
							margin-left: 20rpx;
							width: 0;
							height: 0;
							border-top: 8rpx solid transparent;
							border-left: 8rpx solid #9b5900;
							border-bottom: 8rpx solid transparent;
						}
					}

					.fx-trip {
						color: #9b5900;
						font-size: 24rpx;
						margin-top: 14rpx;
					}

					.upgrade {
						width: 68rpx;
						height: 68rpx;

						image {
							width: 100%;
							height: 100%;
						}
					}
				}

				.tesk-r {
					padding: 10rpx 20rpx;
					color: #9b5900;
					font-size: 24rpx;
					font-weight: 400;
					text-align: center;
					background: #ffffff;
					border-radius: 40rpx;
				}
			}
		}

		.price-wrapper {
			position: relative;

			.navigator {
				position: absolute;
				top: 8rpx;
				right: 6rpx;
				width: 246rpx;
				height: 68rpx;
				padding: 0 16rpx 18rpx 0;
				border-top-right-radius: 32rpx;
				background: linear-gradient(270deg, #f6c363 0%, #fef8e2 100%, rgba(216, 216, 216, 0) 100%);
				font-size: 20rpx;
				color: #b33f1b;

				.inner {
					position: relative;
					z-index: 1;
				}

				.image {
					width: 32rpx;
					height: 32rpx;
					margin-right: 8rpx;
				}

				.icon-huiyuandengji {
					font-size: 32rpx;
					margin-right: 8rpx;
				}

				.icon-ic_rightarrow {
					font-size: 20rpx;
				}
			}
		}

		.price-box.header-height {
			position: relative;
			// margin-top: -150rpx;
		}

		.price-box {
			position: relative;
			height: 370rpx;
			padding: 70rpx 0 0;
			background-repeat: no-repeat;
			background-size: 100% 100%;

			.box-top {
				font-size: 30rpx;
				line-height: 42rpx;
				color: rgba(255, 255, 255, 0.6);
			}

			.com-count {
				margin-top: 16rpx;
				color: #fff;
				font-size: 76rpx;
				font-family: 'Regular';
				font-weight: 600;
			}

			.box-btn {
				display: flex;
				justify-content: space-between;
				margin-top: 52rpx;

				.in {
					display: flex;
					justify-content: center;
					align-items: center;
					flex-direction: column;
				}

				.item {
					width: 210rpx;

					.text {
						margin-bottom: 8rpx;
						font-size: 24rpx;
						line-height: 34rpx;
						color: rgba(255, 255, 255, 0.6);
					}

					.num {
						font-family: SemiBold;
						font-size: 34rpx;
						line-height: 34rpx;
						color: #ffffff;
					}
				}
			}

			.btn-wrap {
				position: absolute;
				top: 100%;
				left: 50%;
				padding: 16rpx;
				border-radius: 52rpx;
				background: #f5f5f5;
				transform: translate(-50%, -50%);
			}

			.btn {
				width: 256rpx;
				height: 72rpx;
				border-radius: 44rpx;
				background-color: #e93323;
				font-size: 30rpx;
				color: #ffffff;
			}
		}

		.statistics {
			margin: 66rpx -20rpx 0 0;
			border-radius: 12px;

			.mb {
				margin-bottom: 20rpx;
			}

			.item {
				width: 345rpx;
				height: 224rpx;
				border-radius: 24rpx;
				margin-right: 20rpx;
				background-color: #ffffff;

				.img {
					width: 80rpx;
					height: 80rpx;

					.image {
						width: 100%;
						height: 100%;
					}
				}

				.item-r {
					.text {
						margin-top: 20rpx;
						font-size: 26rpx;
						line-height: 36rpx;
						color: #333333;
					}

					.trip {
						color: #999999;
						font-size: 22rpx;
					}
				}
			}
		}

		// ---------------------------------------
		.data {
			margin: 28rpx 30rpx;
			background-color: #fff;
			width: 690rpx;
			border-radius: 12rpx;

			.data-num {
				height: 168rpx;
				// background: url('../static/data-num.png') no-repeat;
				background-size: 100% 100%;
				display: flex;
				align-items: center;
				justify-content: space-around;
				color: #fff;
				font-size: 24rpx;

				.num {}

				.num-color {
					margin-top: 20rpx;
					font-weight: bold;
					font-size: 36rpx;
				}
			}

			.data-money {
				display: flex;
				justify-content: space-between;
				color: #333;
				padding: 16rpx 30rpx;
				font-size: 24rpx;

				.money {
					display: flex;
					align-items: center;
					color: #333333;
				}

				.money-num {
					color: #e93323;
					font-size: 28rpx;
					font-weight: bold;
					padding-left: 20rpx;
				}

				.btn {
					width: 160rpx;
					background: linear-gradient(135deg, #fea21f 0%, #fe7a18 100%);
					border-radius: 38rpx;
					color: #fff;
					text-align: center;
					padding: 16rpx 0;
					font-size: 26rpx;
				}
			}
		}

		.invites {
			width: 690rpx;
			margin: 28rpx 30rpx;
			background-color: #fff;
			border-radius: 12rpx;
			font-size: 26rpx;
			color: #333;
			padding: 40rpx 46rpx;

			.invite-list {
				display: flex;

				.item {
					margin-right: 48rpx;
					display: flex;
					flex-direction: column;
					align-items: center;

					.img {
						width: 60rpx;
						height: 60rpx;
						margin-bottom: 24rpx;

						image {
							width: 100%;
							height: 100%;
							border-radius: 50%;
						}
					}
				}
			}
		}

		.list {
			width: 690rpx;
			margin: 28rpx 30rpx;
			background-color: #fff;
			border-radius: 12rpx;
			font-size: 28rpx;

			.tab-list {
				display: flex;
				justify-content: space-between;
				padding: 32rpx 30rpx 0 30rpx;
				color: #999999;

				.tab {
					display: flex;

					.item {
						margin-right: 48rpx;
						transition: all 0.3s;

						.item-text {}

						.line {
							width: 54rpx;
							height: 4rpx;
							margin: 12rpx auto 0 auto;
							border-radius: 4px;
						}

						.line.on {
							background-color: #e93323;
						}
					}

					.item .on {
						font-size: 32rpx;
						font-weight: bold;
						color: #e93323;
					}
				}
			}

			.more {
				display: flex;
				align-items: center;
				font-size: 26rpx;

				.icon-xiangyou {
					font-size: 24rpx;
				}
			}
		}
	}

	.img-modal {
		width: 100%;
		height: 100%;
		position: fixed;
		left: 0;
		top: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 51;
		background: rgba(0, 0, 0, 0.5);

		.img-box {
			position: relative;
			opacity: 1;
			transition: all 0.3s ease-in-out;
			width: 480rpx;
			height: 588rpx;
			z-index: 9999;

			img {
				width: 100%;
				height: 100%;
			}

			.close {
				position: absolute;
				bottom: 46rpx;
				width: 280rpx;
				height: 80rpx;
				// background-color: red;
				left: 100rpx;
			}
		}
	}

	.tui-modal-scale {
		transform: scale(0);
	}

	.tui-modal-normal {
		transform: scale(1);
	}
</style>