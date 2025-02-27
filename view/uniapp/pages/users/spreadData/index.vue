<template>
	<view>
		<view class="top-card relative">
			<NavBar titleText="分销数据"
				textSize="34rpx" 
				:isScrolling="isScrolling"
				:iconColor="isScrolling ? '#333333' : '#ffffff'" 
				:textColor="isScrolling ? '#333333' : '#ffffff'" 
				showBack></NavBar>
			<view class="pt-24">
				<view class="flex items-start pl-66">
					<image :src="userInfo.avatar" class="w-104 h-104 rd-50-p111-"></image>
					<view class="flex-1 flex-between-center pl-16 pt-8">
						<view class="flex-y-center">
							<text class="fs-32 lh-44rpx text-color1 max-w-200 line1">{{userInfo.nickname}}</text>
							<view class="flex-y-center h-40 rd-20rpx fs-20 text--w111-f7f0df opacity-tag px-12"
								@tap="jumbPath(10)">
								<text class="iconfont icon-huiyuandengji fs-24"></text>
								<text class="pl-6">{{ levelInfo.name || '等级未解锁' }}</text>
							</view>
						</view>
						<view class="flex-center qrcode-btn" @tap="createCode(5)" v-if="userInfo.division_type == 2">
							<text class="iconfont icon-a-ic_QRcode fs-28"></text>
							<text class="fs-20 pl-4">推广码</text>
						</view>
						<view class="flex-center qrcode-btn" @tap="jumbPath(5)" v-else>
							<text class="iconfont icon-a-ic_QRcode fs-28"></text>
							<text class="fs-20 pl-4">推广码</text>
						</view>
					</view>
				</view>
				<view class="px-20">
					<view class="w-full relative vip-card px-32" :style="[vipCardStyle]">
						<view class="flex-between-center pt-60">
							<view class="text-color1">
								<view class="fs-28 lh-40rpx flex-y-center">可提现金额（元）
									<text class="iconfont icon-icon_question_2 fs-28" @tap="jumbPath(11)"></text> 
								</view>
								<view class="flex-y-center pt-18" @tap="jumbPath(6)">
									<view class="fs-60 SemiBold">{{ userInfo.commissionCount || '0.00' }}</view>
									<text class="iconfont icon-ic_rightarrow fs-28 pl-10"></text>
								</view>
							</view>
							<view class="gradient-gold w-128 h-58 flex-center rd-30rpx fs-24 fw-500" @tap="jumbPath(0)">去提现</view>
						</view>
						<view class="pt-40 flex-between-center">
							<view class="flex-col">
								<text class="fs-24 text--w111-fff lh-34rpx op-60">累计佣金(元)</text>
								<text class="SemiBold fs-34 pt-8 text-color1">{{ userInfo.accumulate || '0.00' }}</text>
							</view>
							<view class="flex-col">
								<text class="fs-24 text--w111-fff lh-34rpx op-60">已提现(元)</text>
								<text class="SemiBold fs-34 pt-8 text-color1">{{withdraw}}</text>
							</view>
							<view class="flex-col">
								<text class="fs-24 text--w111-fff lh-34rpx op-60">冻结佣金(元)</text>
								<text class="SemiBold fs-34 pt-8 text-color1">{{ userInfo.broken_commission || '0.00' }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="px-20 relative content-box mt-20">
			<view class="w-full bg--w111-fff rd-24rpx px-24">
				<view class="pt-32 fs-28 fw-500 lh-40rpx">订单收益明细</view>
				<view class="mt-28 w-full h-60 rd-30rpx flex-between-center bg-gold-1">
					<view class="tab-item tab-item1 flex-center" :class="orderActive == 0 ? 'tab-active' : 'have-line'" @tap="setTime('all',1)">全部</view>
					<view class="tab-item tab-item1 flex-center" :class="orderActive == 1 ? 'tab-active' : 'have-line'" @tap="setTime('today',1)">今日</view>
					<view class="tab-item tab-item1 flex-center" :class="orderActive == 2 ? 'tab-active' : 'have-line'" @tap="setTime('yesterday',1)">昨日</view>
					<view class="tab-item tab-item1 flex-center" :class="orderActive == 3 ? 'tab-active' : 'have-line'" @tap="setTime('seven',1)">近七天</view>
				</view>
				<view class="mt-8 pl-12">
					<view class="data-cell w-full flex-between-center py-32"  @tap="jumbPath(8)">
						<view>已返佣</view>
						<view class="flex-col">
							<text class="fs-36 SemiBold">{{orderData.order_count}}</text>
							<text class="fs-22 lh-30rpx pt-12 text--w111-666">订单数</text>
						</view>
						<view class="flex-col w-120">
							<text class="fs-36 SemiBold">{{orderData.brokerage}}</text>
							<text class="fs-22 lh-30rpx pt-12 text--w111-666">获得佣金</text>
						</view>
						<text class="iconfont icon-ic_rightarrow fs-22 text--w111-666"></text>
					</view>
					<view class="data-cell w-full flex-between-center py-32"  @tap="jumbPath(8)">
						<view>未返佣</view>
						<view class="flex-col">
							<text class="fs-36 SemiBold">{{orderData.no_order_count}}</text>
							<text class="fs-22 lh-30rpx pt-12 text--w111-666">订单数</text>
						</view>
						<view class="flex-col w-120">
							<text class="fs-36 SemiBold">{{orderData.no_brokerage}}</text>
							<text class="fs-22 lh-30rpx pt-12 text--w111-666">获得佣金</text>
						</view>
						<text class="iconfont icon-ic_rightarrow fs-22 text--w111-666"></text>
					</view>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-24rpx px-24 mt-20">
				<view class="pt-32 fs-28 fw-500 lh-40rpx">数据统计</view>
				<view class="mt-28 w-full h-60 rd-30rpx flex-between-center bg-gold-1">
					<view class="tab-item tab-item1 flex-center" :class="viewActive == 0 ? 'tab-active' : 'have-line'" @tap="setTime('all',2)">全部</view>
					<view class="tab-item tab-item1 flex-center" :class="viewActive == 1 ? 'tab-active' : 'have-line'" @tap="setTime('today',2)">今日</view>
					<view class="tab-item tab-item1 flex-center" :class="viewActive == 2 ? 'tab-active' : 'have-line'" @tap="setTime('yesterday',2)">昨日</view>
					<view class="tab-item tab-item1 flex-center" :class="viewActive == 3 ? 'tab-active' : 'have-line'" @tap="setTime('seven',2)">近七天</view>
				</view>
				<view class="mt-40 grid-column-3 grid-gap-y-48rpx pb-32">
					<view class="w-full flex-col pl-12" v-for="(item, index) in statistics" :key="index">
						<text class="fs-36 SemiBold" >{{item.value}}</text>
						<!-- <baseMoney :money="item.value" symbolSize="24" integerSize="36" decimalSize="36" color="#333333" weight v-else></baseMoney> -->
						<view class="flex-y-center text--w111-666 pt-12">
							<text class="fs-22 lh-30rpx">{{item.name}}</text>
							<!-- <text class="iconfont icon-ic_rightarrow fs-22"></text> -->
						</view>
					</view>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-24rpx px-24 mt-20">
				<view class="pt-32 fs-28 fw-500 lh-40rpx">用户贡献榜单</view>
				<view class="mt-28 w-full h-60 rd-30rpx flex-between-center bg-gold-1">
					<!--  -->
					<view class="tab-item tab-item2 flex-center" 
						:class="contributeType == 'order_count' ? 'tab-active' : 'have-line'" @tap="toggleContribute('order_count')">订单数</view>
					<view class="tab-item tab-item2 flex-center" 
						:class="contributeType == 'order_price' ? 'tab-active' : 'have-line'" @tap="toggleContribute('order_price')">订单金额</view>
					<view class="tab-item tab-item2 flex-center" 
						:class="contributeType == 'brokerage' ? 'tab-active' : 'have-line'" @tap="toggleContribute('brokerage')">收益</view>
				</view>
				<view class="py-32" v-show="contributeData.length">
					<view class="flex-between-center fs-22 lh-30rpx text--w111-666 pl-12 mb-28">
						<text class="w-44">排名</text>
						<text class="w-128">用户</text>
						<text class="w-66">订单数</text>
						<text class="w-108">订单金额</text>
						<text class="w-108">收益</text>
					</view>
					<view class="flex-between-center rank-cell fs-22 lh-30rpx text--w111-333 pl-12" 
						v-for="(item,index) in contributeData" :key="index">
						<view class="fs-26 w-44">
							<text class="w-44 SemiBold rank-1" v-show="index == 0">1</text>
							<text class="w-44 SemiBold rank-2" v-show="index == 1">2</text>
							<text class="w-44 SemiBold rank-3" v-show="index == 2">3</text>
							<text class="w-44 SemiBold text--w111-ccc" v-show="index > 2">{{ index + 1 }}</text>
						</view>
						<text class="w-128 line1">{{ item.nickname || '微信用户' }}</text>
						<text class="w-66">{{ item.total_order_count }}</text>
						<text class="w-108">¥{{ item.total_order_price }}</text>
						<text class="w-108">¥{{ item.total_brokerage }}</text>
					</view>
					<view class="flex-center pt-40 text--w111-333" @tap="jumbPath(13)">
						<text class="fs-24">查看更多</text>
						<text class="iconfont icon-ic_rightarrow fs-24"></text>
					</view>
				</view>
				<view class="py-32" v-show="!contributeData.length">
					<emptyPage title="暂无贡献榜单数据～" src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-24rpx px-24 mt-20">
				<view class="pt-32 fs-28 fw-500 lh-40rpx">我的团队</view>
				<!-- 团队 代理商 员工 我的推广 -->
				<!-- 代理商 员工 我的团队 -->
				<!-- 普通用户 员工 一级分销 二级分销 -->
				<view class="mt-28 w-full h-60 rd-30rpx flex-between-center bg-gold-1" v-if="brokerageLevel == 2 || promoterType == 1">
					<view class="tab-item tab-item3 flex-center" 
						:class="{'tab-active': teamParams.grade == 0}" 
						@tap="toggleTeam(0)">{{ promoterType ? '代理商' : '一级分销' }}</view>
					<view class="tab-item tab-item3 flex-center" 
						:class="{'tab-active': teamParams.grade == 1}" 
						@tap="toggleTeam(1)">{{ promoterType ? '员工' : '二级分销' }}</view>
				</view>
				<view class="mt-28 w-full h-70 rd-12rpx team-grident pl-20 flex-y-center">
					<text class="title-line"></text>
					<text class="fs-24 fw-500 lh-34rpx pl-16">团队数据</text>
				</view>
				<view class="mt-32 grid-column-4">
					<view class="w-full flex-col pl-16" v-for="(item, index) in teamStatistics" :key="index">
						<text class="fs-36 SemiBold" v-if="item.type == 1">{{item.num}}</text>
						<baseMoney :money="item.num" symbolSize="24" integerSize="36" decimalSize="36" color="#333333" weight v-else></baseMoney>
						<view class="text--w111-666 pt-12 fs-22 lh-30rpx">{{item.label}}</view>
					</view>
				</view>
				<view class="mt-32 w-full h-70 rd-12rpx team-grident pl-20 flex-y-center">
					<text class="title-line"></text>
					<text class="fs-24 fw-500 lh-34rpx pl-16">团队成员</text>
				</view>
				<view class="py-32" v-show="teamList.length">
					<view class="flex-between-center fs-22 lh-30rpx text--w111-666 pl-12 mb-28">
						<text class="w-176">成员</text>
						<text class="w-88">推广人数</text>
						<text class="w-108">订单数</text>
						<text class="w-108">消费金额</text>
					</view>
					<view class="flex-between-center rank-cell fs-22 lh-30rpx text--w111-666 pl-12" 
						v-for="(item,index) in teamList" :key="index">
						<view class="w-176 flex-y-center">
							<image class="w-40 h-40 rd-50-p111- block mr-8" :src="item.avatar"></image>
							<text class="w-128 line1">{{item.nickname}}</text>
						</view>
						<text class="w-88">{{item.childCount ? item.childCount : 0}}</text>
						<text class="w-108">{{item.orderCount ? item.orderCount : 0}}</text>
						<text class="w-108">{{item.numberCount ? item.numberCount : 0}}</text>
					</view>
					<view class="flex-center pt-40" @tap="jumbPath(9)">
						<text class="fs-24">查看更多</text>
						<text class="iconfont icon-ic_rightarrow fs-24"></text>
					</view>
				</view>
				<view v-show="!teamList.length">
					<emptyPage title="暂无团队据～" src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-24rpx px-24 mt-20">
				<view class="pt-32 fs-28 fw-500 lh-40rpx">分销榜单</view>
				<view class="mt-28 w-full h-60 rd-30rpx flex-between-center bg-gold-1">
					<view class="tab-item tab-item3 flex-center" :class="{'tab-active': rankTab == 0}" @tap="toggleRank(0)">佣金榜单</view>
					<view class="tab-item tab-item3 flex-center"  :class="{'tab-active': rankTab == 1}"  @tap="toggleRank(1)">推广人榜单</view>
				</view>
				<view v-if="rankList.length">
					<view class="mt-32">
						<view class="flex-between-center rank2-cell" v-for="(item,index) in rankList" :key="index">
							<view class="flex-y-center">
								<image :src="'../static/no'+(index + 1)+'.png'" class="w-64 h-64 block mr-28"></image>
								<image :src="item.avatar" class="w-80 h-80 rd-50-p111-"></image>
								<view class="w-164 line1 lh-40rpx fs-28 lh-40rpx ml-24">{{item.nickname}}</view>
							</view>
							<view class="fs-28 lh-40rpx" v-show="rankTab == 0">¥{{item.brokerage_price || ''}}</view>
							<view class="fs-28 lh-40rpx" v-show="rankTab == 1">{{item.count || ''}}人</view>
						</view>
					</view>
					<view class="flex-center py-32" @tap="jumbPath(rankTab ? 1 : 2)">
						<text class="fs-24">查看更多</text>
						<text class="iconfont icon-ic_rightarrow fs-24"></text>
					</view>
				</view>
				<view v-else>
					<emptyPage title="暂无排行榜数据哦～" src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
		</view>
		<BaseCode v-if="isCode" :code="code" :codeImg="codeImg" :isShowCode.sync="isCode"></BaseCode>
	</view>
</template>
<script>
import colors from '@/mixins/color.js';
import { 
	agentLevelList,
	spreadPeople, 
	getBrokerageRank, 
	getRankList, 
	spreadOrderIncomeApi,
	spreadOverviewApi,
	spreadContributeApi,
	getAgentCode
  } from "@/api/user.js"
import { toLogin } from '@/libs/login.js';
import { mapGetters } from 'vuex';
import {HTTP_REQUEST_URL} from '@/config/app';
import home from '@/components/home/index.vue';
import NavBar from "@/components/NavBar.vue";
import emptyPage from '@/components/emptyPage.vue';
import BaseCode from '@/components/BaseCode.vue';
let sysHeight = uni.getWindowInfo().statusBarHeight;
export default {
	data() {
		return {
			imgHost: HTTP_REQUEST_URL,
			sysHeight,
			isScrolling: false,
			userInfo:{
				avatar:'',
				nickname:'',
				uid: 0,
			},
			viewActive: 0,
			statistics:[],
			teamStatistics:[
				{ label: '团队人数',num:0,type:1},
				{ label: '推广人数',num:0,type:1},
				{ label: '推广订单数',num:0,type:1},
				{ label: '返佣金额',num:'0.00',type:2},
			],
			levelInfo:{},
			levelList:[],
			teamList: [], //我的团队
			teamParams:{
				page:1,
				limit:10,
				grade: 0
			},
			rankTab: 0,
			rankList: [],
			brokerageLevel: 1, //后台开启的分销等级
			promoterType: 0, //普通分销用户或者事业部成员
			orderActive: 0,
			orderData:{},
			contributeType: 'order_count',
			contributeData:[],
			withdraw: "",
			isCode: false,
			code: "",
			codeImg: ""
		}
	},
	mixins: [colors],
	components: { home, NavBar, emptyPage, BaseCode },
	computed: {
		...mapGetters(['isLogin']),
		vipCardStyle(){
			return {
				backgroundImage: 'url('+this.imgHost+'/statics/images/promoter/spread_data_bg.png'+')'
			}
		},
		cardBgStyle(){
			return {
				backgroundImage: 'url('+this.imgHost+'/statics/images/promoter/spread_black_card.png'+')'
			}
		},
	},
	onPageScroll(option) {
		uni.$emit('scroll');
		if (option.scrollTop > 50) {
			this.isScrolling = true;
		} else if (option.scrollTop < 50) {
			this.isScrolling = false;
		}
	},
	onLoad() {
		if(this.isLogin){
			this.getUserData();
			this.getTeamList();
			this.getRankList(0);
			this.getOrderIncome(0,0);
			this.getOverviewData(0,0);
			this.getContribute();
		}else{
			toLogin();
		}
	},
	methods: {
		getUserData() {
			agentLevelList().then((res) => {
				this.userInfo = res.data.user;
				this.promoterType = res.data.user.division_type;
				this.levelInfo = res.data.level_info;
				this.levelList = res.data.level_list;
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		toggleTeam(val){
			this.teamParams.grade = val;
			this.teamList = [];
			this.getTeamList();
		},
		getTeamList(){
			spreadPeople(this.teamParams).then(res => {
				this.brokerageLevel = res.data.brokerage_level;
				this.teamList = res.data.list;
				this.teamStatistics[0].num = res.data.count;
				this.teamStatistics[1].num = this.teamParams.grade ? res.data.totalLevel : res.data.total;
				this.teamStatistics[2].num = res.data.order_count;
				this.teamStatistics[3].num = res.data.price.toString();
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		getRankList(val){
			let funApi = val == 1 ? getRankList : getBrokerageRank;
			funApi({page:1,limit:3,type:'week'}).then(res=>{
				this.rankList = val ? res.data.list : res.data.rank;
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		toggleRank(val){
			this.rankTab = val;
			this.getRankList(val);
		},
		getOrderIncome(start, stop){
			spreadOrderIncomeApi({
				page:1,
				limit:10,
				start,stop
			}).then(res=>{
				this.orderData = res.data;
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		setTime(time,type) {
			let self = this
			this.time = time;
			let start = '';
			let stop = '';
			let year = new Date().getFullYear(), month = new Date().getMonth() + 1, day = new Date().getDate();
			switch (time) {
				case "all":
					if(type == 1){
						this.orderActive = 0;
						this.getOrderIncome(0,0);
					}else{
						this.viewActive = 0;
						this.getOverviewData(0,0)
					}
				
					break;
				case "today":
					start = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /1000;
					stop = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /1000 + 24 * 60 * 60 - 1;
					if(type == 1){
						this.orderActive = 1;
						this.getOrderIncome(start, stop);
					}else{
						this.viewActive = 1;
						this.getOverviewData(start, stop)
					}
					break;
				case "yesterday":
					start = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() / 1000 - 24 * 60 * 60;
					stop = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() / 1000 - 1;
					if(type == 1){
						this.orderActive = 2;
						this.getOrderIncome(start, stop);
					}else{
						this.viewActive = 2;
						this.getOverviewData(start, stop)
					}
					break;
				case "seven":
					start = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
						1000 +
						24 * 60 * 60 -
						7 * 3600 * 24;
					stop = new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
						1000 +
						24 * 60 * 60 -
						1;
						if(type == 1){
							this.orderActive = 3;
							this.getOrderIncome(start, stop);
						}else{
							this.viewActive = 3;
							this.getOverviewData(start, stop)
						}
					break;
			}
		},
		getOverviewData(start,stop){
			spreadOverviewApi({
				page:1,
				limit:10,
				start,stop
			}).then(res=>{
				this.withdraw = res.data[2].value;
				this.statistics = res.data.map(item=>({
					...item,
					type: typeof item.value === 'number' ? 1 : 2
				}));
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		getContribute(){
			let params = {
				page:1, 
				limit: 5,
				type: this.contributeType
			};
			spreadContributeApi(this.contributeType, params).then(res=>{
				this.contributeData = res.data;
			}).catch((err) => {
				this.$util.Tips({
					title: err
				});
			});
		},
		toggleContribute(val){
			this.contributeType = val;
			this.getContribute();
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
				'/pages/users/agent/staff_list',
				'/pages/users/spreadData/contributionRank'
			];
		
			uni.navigateTo({
				url: path[type]
			});
		},
		createCode(){
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
	}
}
</script>
<style scoped lang="scss">
.top-card{
	background: linear-gradient(360deg, #F5F5F5 0%, rgba(28, 26, 27, 0.72) 16%, #353334 28%, #1C1A1B 53%);
}
.text-color1{
	color: #FCF0C6;
}
.text--w111-f7f0df{
	color: #F7F0DF;
}
.max-w-200{
	max-width: 200rpx;
}
.op-60{
	opacity: 0.6;
}
.opacity-tag{
	background: rgba(255,255,255,0.1);
	margin-left: 8rpx;
}
.qrcode-btn{
	width: 126rpx;
	height: 48rpx;
	background: rgba(252,240,198,0.2);
	border-radius: 92rpx 0rpx 0rpx 92rpx;
	color: #FCDD92;
}
.vip-card{
	margin-top: -32rpx;
	width: 100%;
	height: 326rpx;
	background-size: contain;
	background-repeat: no-repeat;
}
.gradient-gold{
	background: linear-gradient( 270deg, #F7DDA2 0%, #FFF2C3 100%);
}
.bg-gold-1{
	background-color: rgba(247, 221, 162, 0.2);
}

.tab-item{
	height: 60rpx;
	font-size: 22rpx;
	color: #666666;
}
.tab-item1{
	width: 164rpx;
}
.tab-item2{
	width: 220rpx;
}
.tab-item3{
	width: 331rpx;
}
.tab-active{
	border-radius: 30rpx;
	background: #F9D47C;
	color: #333333;
	font-weight: 500;
}
.grid-gap-y-48rpx{
	row-gap: 48rpx;
}
.data-cell ~ .data-cell{
	border-top: 2rpx solid #F5F5F5;
}
.rank-1{
	color: #E73E05;
}
.rank-2{
	color: #E26F04;
}
.rank-3{
	color: #F4BB00;
}
.rank-cell ~ .rank-cell{
	margin-top: 40rpx;
}
.rank2-cell ~ .rank2-cell{
	margin-top: 48rpx;
}
.team-grident{
	background: linear-gradient( to right, #F9F9F9 0%, rgba(249,249,249,0) 100%);
}
.title-line{
	width: 6rpx;
	height: 22rpx;
	background: #F9D47C;
	border-radius: 3rpx;
}
.have-line{
	position: relative;
}
.have-line + .have-line::before {
  content: '';
  width: 1px; 
  height: 28rpx; 
  background-color: rgba(196, 186, 157, 1);
  position: absolute;
  left: -1px;
  top: 16rpx;
}
</style>