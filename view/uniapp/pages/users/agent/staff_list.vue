<template>
	<view :style="colorStyle">
		<view class="promoter-list">
			<view class="header">
				<view class='search acea-row row-middle'>
					<text class="iconfont icon-ic_search"></text>
					<input placeholder='搜索用户名称' placeholder-class='placeholder' v-model="keyword" @confirm="submitForm" confirm-type='search' name="search"></input>
				</view>
			<!-- 	<view class='nav acea-row row-around' v-if="brokerageLevel == 2">
					<view :class="grade == 0 ? 'item on' : 'item'" @click='setType(0)'>一级({{total}})</view>
					<view :class="grade == 1 ? 'item on' : 'item'" @click='setType(1)'>二级({{totalLevel}})</view>
				</view> -->
				<timeSlot @changeTime="changeTime"></timeSlot>
			</view>
			<view class='list' v-if="recordList.length">
				<view class="top_num">
					共 <text class="main_color">{{count}}</text> 位员工，获得佣金<text class="main_color">¥{{price}}</text>
				</view>
				<view class="itemCon">
					<view class='item acea-row row-between-wrapper' v-for="(item,index) in recordList" :key="index">
						<view class="picTxt acea-row row-between-wrapper">
							<view class='pictrue'>
								<image :src='item.avatar'></image>
							</view>
							<view class='text'>
								<view class='name line1'>{{item.nickname}}</view>
								<view>加入时间：{{item.spread_time}}</view>
							</view>
						</view>
						<view class="right">
							<view><text class='num font-nums'>{{item.childCount ? item.childCount : 0}}</text>人</view>
							<view><text class="num">{{item.orderCount ? item.orderCount : 0}}</text>单</view>
							<view><text class="num">{{item.numberCount ? item.numberCount : 0}}</text>元</view>
						</view>
						<view class="item-btn">
							<view class="division-percent">
								分佣比例：{{ item.division_percent }}%
							</view>
							<view class="action">
								<view class="clear" @click="del(item, index)">删除</view>
								<view class="change" @click="changeData(item)">修改分佣比例</view>
							</view>
							
						</view>
					</view>
				</view>
			</view>
			<view class="empty" v-if="recordList.length == 0">
				<emptyPage title="暂无数据～"></emptyPage>
			</view>
		</view>
		<view class="refund-input" :class="refund_close ? 'on' : ''">
			<view class="input-msg">
				<view class="close">
				
				<text class="iconfont icon-ic_close" @tap="refund_close = false"></text>
				</view>
				<view class="refund-input-title">修改分佣比例(%)</view>
				<view class="refund-input-sty">
					<input type="number" v-model="agent_percent" placeholder="请输入百分比" />
				</view>
				<view class="refund-bth">
					<!-- <view class="close-refund" @click="refund_close = false">取消</view> -->
					<view class="submit-refund" @click="refundSubmit()">提交</view>
				</view>
			</view>
		</view>
		<view class="mask invoice-mask" v-if="refund_close" @click="refund_close = false"></view>
		<view class="mark" v-show="isCancellation"></view>
		<tui-modal :show="isCancellation" maskClosable custom @cancel="isCancellation = false">
			<view class="tui-modal-custom">
				<view class="fs-32 fw-500 lh-44rpx text-center">删除员工</view>
				<view class="fs-30 text--w111-666 lh-42rpx text-center mt-22">确定删除该员工？</view>
				<view class="flex-y-center">
					<view class="w-full h-72 rd-36rpx flex-center border b-solid b--w111-ccc text-primary-con fs-26  mt-32 mr-16 clear-btn" @tap="isCancellation = false">取消</view>
					<view class="w-full h-72 rd-36rpx flex-center bg-red fs-26 text--w111-fff mt-32 ml-16" @tap="clear">确定</view>
				</view>
			</view>
		</tui-modal>
	</view>
</template>

<script>
	import {
		agentStaffList,
		agentDelStaff,
		agentStaffPercent
	} from '@/api/user.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import emptyPage from '@/components/emptyPage.vue'
	import timeSlot from '@/components/timeSlot/index.vue'
	import colors from '@/mixins/color.js';
	import tuiModal from '@/components/tui-modal/index.vue';
	export default {
		components: {
			timeSlot,
			emptyPage,
			tuiModal
		},
		mixins: [colors],
		data() {
			return {
				total: 0,
				totalLevel: 0,
				teamCount: 0,
				page: 1,
				limit: 20,
				keyword: '',
				sort: '',
				grade: 0,
				status: false,
				recordList: [],
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				start: 0,
				stop: 0,
				count: 0,
				price: 0,
				brokerageLevel: 0,
				refund_close:false,
				isCancellation:false,
				agent_percent: null,
				delIndex: 0,
				uid: null
			};
		},
		computed: mapGetters(['isLogin']),
		onLoad() {
			if (this.isLogin) {
				this.userSpreadNewList();
			} else {
				toLogin()
			}
		},
		onShow: function() {
			uni.removeStorageSync('form_type_cart');
			if (this.is_show) this.userSpreadNewList();
		},
		onHide: function() {
			this.is_show = true;
		},
		methods: {
			changeData(data) {
				this.refund_close = true;
				this.uid = data.uid;
			},
			del(data,index){
				this.uid = data.uid
				this.delIndex = index
				this.isCancellation = true
			},
			clear() {
				let that = this;
				agentDelStaff(this.uid)
					.then((res) => {
						that.recordList.splice(this.delIndex, 1);
						that.$set(that, 'recordList', that.recordList);
						// that.userSpreadNewList();
						that.isCancellation = false;
						that.teamCount -= 1;
						return that.$util.Tips({
							title: '删除成功',
							icon: 'success'
						});
					})
					.catch((err) => {
						return that.$util.Tips({
							title: err
						});
					});
			},
			refundSubmit() {
				if (this.agent_percent < 0) {
					return this.$util.Tips({
						title: '请输入比例'
					});
				}
				agentStaffPercent({
					division_percent: this.agent_percent,
					uid: this.uid
				})
					.then((res) => {
						this.$util.Tips(
							{
								title: res.msg,
								icon: 'success'
							},
							() => {
								this.refund_close = false;
								this.page = 1;
								this.limit = 20;
								this.keyword = '';
								// this.sort = '';
								this.status = false;
								this.agent_percent = null;
								this.$set(this, 'recordList', []);
								this.userSpreadNewList();
							}
						);
					})
					.catch((err) => {
						this.$util.Tips({
							title: err
						});
					});
			},
			changeTime(time) {
				console.log(time)
				this.start = time.start
				this.stop = time.stop
				this.submitForm()
			},
			onLoadFun(e) {
				this.userSpreadNewList();
				this.isShowAuth = false;
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			setSort(sort) {
				let that = this;
				that.sort = sort;
				that.page = 1;
				that.limit = 20;
				that.status = false;
				that.$set(that, 'recordList', []);
				that.userSpreadNewList();
			},
			// setKeyword: function(e) {
			// 	this.keyword = e.detail.value;
			// },
			submitForm: function() {
				this.page = 1;
				this.limit = 20;
				this.status = false;
				this.$set(this, 'recordList', []);
				this.userSpreadNewList();
			},

			setType: function(grade) {
				if (this.grade != grade) {
					this.grade = grade;
					this.page = 1;
					this.limit = 20;
					this.keyword = '';
					this.sort = '';
					this.status = false;
					this.$set(this, 'recordList', []);
					this.userSpreadNewList();
				}
			},
			userSpreadNewList: function() {
				let that = this;
				let page = that.page;
				let limit = that.limit;
				let status = that.status;
				let keyword = that.keyword;
				let sort = that.sort;
				let grade = that.grade;
				let recordList = that.recordList;
				let recordListNew = [];
				if (status == true) return;
				agentStaffList({
					start: this.start,
					stop: this.stop,
					page: page,
					limit: limit,
					keyword: keyword,
				}).then(res => {
					let len = res.data.list.length;
					let recordListData = res.data.list;
					recordListNew = recordList.concat(recordListData);
					that.total = res.data.count;
					that.teamCount = res.data.count;
					that.status = limit > len;
					that.page = page + 1;
					that.$set(that, 'recordList', recordListNew);
					that.count = res.data.count;
					that.price = res.data.brokerage;
				});
			}
		},
		onReachBottom: function() {
			this.userSpreadNewList();
		}
	}
</script>

<style scoped lang="scss">
	.empty{
		margin: 20rpx;
	}
	.font-nums{
		color: #E93323;
	}
	.promoter-list .nav {
		background-color: #fff;
		height: 92rpx;
		line-height: 86rpx;
		font-size: 28rpx;
		color: #666;
		font-family: PingFang SC;
	}

	.promoter-list .nav .item.on {
		color: #E93323;
		position: relative;
		font-size: 32rpx;

		&::after {
			position: absolute;
			content: '';
			width: 38rpx;
			height: 30rpx;
			border: 2px solid #E93323;
			border-left: 2px solid transparent !important;
			border-top: 2px solid transparent !important;
			border-right: 2px solid transparent !important;
			border-radius: 50%;
			left: 50%;
			margin-left: -24rpx;
			bottom: 16rpx;
		}
	}

	.promoter-list .header {
		background-color: #fff;
		padding-top: 24rpx;
	}

	.promoter-list .search {
		width: 710rpx;
		height: 72rpx;
		padding: 0 32rpx;
		box-sizing: border-box;
		background-color: #F5F5F5;
		border-radius: 50rpx;
		margin: 0 auto 10rpx auto;

		.placeholder {
			color: #ccc;
			font-size: 28rpx;
		}
	}

	.promoter-list .search .iconfont {
		font-size: 32rpx;
		color: #999;
		margin-right: 18rpx;
	}

	.promoter-list .list {
		margin-top: 12rpx;
	}

	.promoter-list .list .sortNav {
		background-color: #fff;
		height: 76rpx;
		border-bottom: 1rpx solid #eee;
		color: #333;
		font-size: 28rpx;
	}

	.promoter-list .list .sortNav .sortItem {
		text-align: center;
		flex: 1;
	}

	.promoter-list .list .sortNav .sortItem image {
		width: 24rpx;
		height: 24rpx;
		margin-left: 6rpx;
		vertical-align: -3rpx;
	}

	.promoter-list .list .itemCon {
		background-color: #fff;
		margin: 0 20rpx;
		border-radius: 24rpx;
		padding-top: 10rpx;
	}

	.promoter-list .list .item {
		height: 290rpx;
		font-size: 24rpx;
		color: #666;
		margin: 0 24rpx;
	}

	.promoter-list .list .item .picTxt {
		width: 440rpx;
	}

	.promoter-list .list .item .picTxt .pictrue {
		width: 112rpx;
		height: 112rpx;
		border-radius: 50%;
	}

	.promoter-list .list .item .picTxt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 50%;
	}

	.promoter-list .list .item .picTxt .text {
		width: 304rpx;
		font-size: 24rpx;
		color: #999;
	}

	.promoter-list .list .item .picTxt .text .name {
		font-size: 28rpx;
		color: #333;
		margin-bottom: 13rpx;
	}

	.promoter-list .list .item .right {
		width: 190rpx;
		text-align: right;
		font-size: 24rpx;
		color: #333;
	}

	.promoter-list .list .item .right .num {
		margin-right: 7rpx;
	}

	.top_num {
		padding: 14rpx 20rpx 28rpx 20rpx;
		font-size: 24rpx;
		color: #666;
	}

	.main_color {
		color: #E93323;
		margin: 0 6rpx;
	}
	
	.item-btn {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: space-between;
		background-color: #fff;
		border-top: 1rpx solid #EEEEEE;
		padding: 26rpx 0 16rpx 0;
		font-size: 24rpx;
		.division-percent{
			color: #999999;
		}
		.action{
			display: flex;
			align-items: center;
		}
		.clear,
		.change {
			padding: 10rpx 24rpx;
			border-radius: 30rpx;
		}
		
		.clear {
			border: 1rpx solid #CCCCCC;
			color: #333333;
		}
		
		.change {
			background-color: #E93323;
			color: #fff;
			margin-left: 16rpx;
		}
	}
	.refund-input {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
		border-radius: 40rpx 40rpx 0 0;
		background-color: #fff;
		z-index: 99;
		padding: 40rpx 0 70rpx 0;
		transition: all 0.3s cubic-bezier(0.25, 0.5, 0.5, 0.9);
		transform: translate3d(0, 100%, 0);
	
		.refund-input-title {
			font-weight: 500;
			font-size: 16px;
			color: #333333;
			font-size: 32rpx;
			margin-bottom: 60rpx;
		}
	
		.refund-input-sty {
			background-color: #F5F5F5;
			padding: 20rpx 20rpx;
			border-radius: 50rpx;
			color: #BBBBBB;
			width: 100%;
			margin: 0rpx 20rpx 0rpx 20rpx;
			text-align: center;
		}
		/deep/ .uni-input-placeholder,
		/deep/ .uni-input-input{
			color: #bbb;
			text-align: center;
		}
		.input-msg {
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			position: relative;
			margin: 0 65rpx;
			.close{
				position: absolute;
				top: 8rpx;
				color: #aaa;
				font-size: 32rpx;
				right: -30rpx;
				width: 36rpx;
				height: 36rpx;
				border-radius: 50%;
				margin-top: -18rpx;
				background: #EEEEEE;
				text-align: center;
				line-height: 36rpx;
			}
		}
	
		.refund-bth {
			display: flex;
			margin: 0 65rpx;
			margin-top: 20rpx;
			justify-content: space-around;
			width: 100%;
	
			.close-refund {
				padding: 24rpx 80rpx;
				border-radius: 80rpx;
				color: #fff;
				background-color: #ccc;
			}
	
			.submit-refund {
				width: 100%;
				padding: 24rpx 0rpx;
				text-align: center;
				border-radius: 80rpx;
				color: #fff;
				background-color: #E93323;
			}
		}
	}
	
	.refund-input.on {
		transform: translate3d(0, 0, 0);
	}
</style>