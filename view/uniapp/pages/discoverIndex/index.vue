<template>
	<view :style="colorStyle">
		<view class="plant-grass guodu" :class="pageTheme">
			<view class="header_box guodu fixed-lt w-full z-999" :style="{'padding-top': sysHeight + 'px'}">
				<!-- #ifdef MP-WEIXIN -->
				<view class="h-80 pl-30 pr-20 flex-between-center" :style="[menuBoxStyle]">
				<!-- #endif -->
				<!-- #ifdef APP-PLUS || H5 -->
				<view class="h-80 pl-30 pr-20 flex-between-center">
				<!-- #endif -->
					<text class="iconfont icon-ic_menu3 fs-40" @tap="toggleBubble"></text>
					<view class="flex-y-center fs-32">
						<text class="tab-item" :class="{'active-tab': tabActive == 0}" @tap="changeTab(0)">关注</text>
						<text class="tab-item pl-52"  :class="{'active-tab': tabActive == 1}" @tap="changeTab(1)">发现</text>
						<text class="tab-item pl-52"  :class="{'active-tab': tabActive == 2}" @tap="changeTab(2)">精选</text>
						<!-- #ifdef MP-WEIXIN -->
						<text class="iconfont icon-ic_search block pl-32 fs-36" @tap="authTo('/pages/discover/discoverSearch/index')"></text>
						<!-- #endif -->
					</view>
					<!-- #ifdef APP-PLUS || H5 -->
					<text class="iconfont icon-ic_search fs-40" @tap="authTo('/pages/discover/discoverSearch/index')"></text>
					<!-- #endif -->
				</view>
			</view>
			<view :style="{'padding-top': marTop}" v-if="tabActive == 0">
				<!-- 我的关注 -->
				<view class="guanzhu bg--w111-fff w-full" v-if="followList.length">
					<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false"
						>
						<view class="inline-block mr-40" v-for="(user,i) in followList" :key="i">
							<view class="w-110 flex-col flex-center relative">
								<image :src="user.author_image" class="w-100 h-100 rd-50-p111-"
								@tap="authTo('/pages/discover/discoverUser/index?id=' + user.relation_id)"></image>
								<view class="w-110 fs-22 pt-16 text-center line1">{{user.author}}</view>
								<view class="new-dot" v-if="user.is_new"></view>
							</view>
						</view>
						<view class="inline-block mr-40" v-if="followList.length && followList.length > 9">
							<view class="flex-y-center relative bottom-40" @tap="authTo('/pages/discover/discoverFollow/index?type=0')">
								<view class="flex-col flex-center fs-22 text--w111-666 lh-30rpx pr-20">
									<text>全部</text>
									<text>关注</text>
								</view>
								<text class="iconfont icon-ic_right21 fs-24 text--w111-ccc"></text>
							</view>
						</view>
					</scroll-view>
				</view>
				<!-- 列表卡片 -->
				<view class="bg--w111-f5f5f5 pl-20 pr-20 pt-20" v-if="contentList.length">
					<view class="bg--w111-fff rd-24rpx p-30rpx pro-card"
					v-for="(item,index) in contentList" :key="index"
					@tap="authTo(`/pages/discover/${item.content_type == 1 ? 'discoverDetails' : 'discoverVideo'}/index?id=${item.id}`)">
						<view class="flex-between-center">
							<view class="flex-y-center" @tap.stop="authTo('/pages/discover/discoverUser/index?id=' + item.relation_id)">
								<image :src="item.author_image" class="w-52 h-52 rd-50-p111-"></image>
								<view class="fs-26 fw-500 pl-16">{{item.author}}</view>
							</view>
							<text class="fs-24 text--w111-999">{{item.time_text}}</text>
						</view>
						<view class="mt-20">
							<view v-if="item.content_type == 1">
								<swiper
									class="guanzhu-swiper"
									circular
									:indicator-dots="true"
									indicator-color="rgba(255, 255, 255, 0.4)"
									indicator-active-color="#f5f5f5"
									autoplay
									:interval="4000"
									:duration="500">
									<swiper-item v-for="(pic,i) in item.slider_image" :key="i">
										<view class="swiper-item flex-center">
											<easy-loadimage
											mode="aspectFill"
											:image-src="pic"
											width="650rpx"
											height="650rpx"
											borderRadius="24rpx"></easy-loadimage>
										</view>
									</swiper-item>
								</swiper>
							</view>
							<view class="relative" v-else>
								<image :src="item.image" class="w-full h-650 rd-24rpx" mode="aspectFill"></image>
								<view class="player flex-center">
									<text class="iconfont icon-ic_right2 text--w111-fff fs-32"></text>
								</view>
							</view>
							<view class="w-full mt-32" v-if="item.product && item.product.length > 1">
								<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
									<view class="inline-block scroll-pro" v-for="(pro,k) in item.product" :key="k">
										<view class="w-414 rd-16rpx p-16 b-e flex-between-center"
										@tap.stop="authTo('/pages/goods_details/index?id=' + pro.id)">
											<easy-loadimage
											:image-src="pro.image"
											width="104rpx"
											height="104rpx"
											borderRadius="8rpx"></easy-loadimage>
											<view class="flex-1 pl-14">
												<view class="w-256 line1 fs-26 pb-20">{{pro.store_name}}</view>
												<baseMoney :money="pro.price" symbolSize="22" integerSize="32" decimalSize="24" weight></baseMoney>
											</view>
										</view>
									</view>
								</scroll-view>
							</view>
							<view class="w-full mt-24 pb-40 bb-f5" v-else-if="item.product && item.product.length == 1">
								<view class="w-full rd-16rpx p-16 b-e flex-between-center" @tap.stop="authTo('/pages/goods_details/index?id=' + item.product[0].id)">
									<image :src="item.product[0].image" class="w-104 h-104 rd-8rpx"></image>
									<view class="flex-1 pl-14">
										<view class="w-464 line1 fs-26 pb-20">{{item.product[0].store_name}}</view>
										<view class="flex-between-center">
											<baseMoney :money="item.product[0].price" symbolSize="22" integerSize="32" decimalSize="24" color="#333" weight></baseMoney>
											<view class="w-96 h-40 rd-20rpx flex-center fs-22 font-num theme-border">购买</view>
										</view>
									</view>
								</view>
							</view>
							<view class="fs-30 fw-500 lh-44rpx mt-32">{{item.title}}</view>
							<view class="pt-12 fs-28 lh-42rpx">
								<BaseTextMore
								:content="item.content" 
								fontColor="#333"
								actionFontColor="#333"
								:font-size="28"
								:rows="2" 
								expand-text="展开" 
								collapse-text="收起"></BaseTextMore>
								<text class="pr-20 font-num" 
								v-for="topic in item.topic" :key="topic.id + 'p'"
								@tap.stop="authTo('/pages/discover/discoverTopic/index?id=' + topic.id)"
								>#{{topic.name}}</text>
							</view>
							<view class="flex-between-center mt-44">
								<!-- #ifdef H5 || APP-PLUS -->
								<view class="flex-y-center" @tap.stop="openShare(item)">
									<text class="iconfont icon-ic_share fs-44"></text>
									<text class="fs-26 pl-12">{{item.share_num}}</text>
								</view>
								<!-- #endif -->
								<!-- #ifdef MP-WEIXIN -->
								<button class="flex-y-center" open-type="share" hover-class="none" @tap.stop="openShare(item)">
									<text class="iconfont icon-ic_share fs-44"></text>
									<text class="fs-26 pl-12">{{item.share_num}}</text>
								</button>
								<!-- #endif -->
								<view class="flex-y-center">
									<view class="flex-y-center" @tap.stop="contentLike(item)">
										<text class="iconfont fs-44" :class="item.is_like ? 'icon-ic_love_2' : 'icon-ic_love'"></text>
										<text class="fs-26 pl-12">{{item.like_num}}</text>
									</view>
									<view class="flex-y-center pl-24">
										<text class="iconfont icon-ic_daipingjia1 fs-44"></text>
										<text class="fs-26 pl-12">{{item.comment_num}}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="px-20 mt-20" v-else>
					<emptyPage
					:title="isLogin ? '暂无关注内容～' : '登录后可查看关注用户的发布哦 '"
					:isLogin="isLogin"
					:src="isLogin ? '/statics/images/video/no_friend.png' : '/statics/images/noSearch.gif'"></emptyPage>
				</view>
				<view class="px-20 mt-40" v-if="recommendList.length">
					<view class="recommend">
						<view class="flex-center">
							<image class="w-42 h-36" src="@/static/img/recommend_zs.png"></image>
							<text class="fs-32 fw-500 text--w111-333 lh-44rpx px-6">为你推荐</text>
							<image class="w-42 h-36" src="@/static/img/recommend_zs.png"></image>
						</view>
						<view class="mt-24 w-full">
							<view class="card w-full mb-20" v-for="(item,index) in recommendList" :key="index">
								<view class="flex-between-center">
									<view class="flex-y-center" 
									@tap="authTo('/pages/discover/discoverUser/index?id=' + item.relation_id)">
										<easy-loadimage
										:image-src="item.author_image"
										width="80rpx"
										height="80rpx"
										borderRadius="40rpx"></easy-loadimage>
										<view class="pl-20">
											<view class="fs-28 lh-44rpx">{{item.author}}</view>
											<view class="flex fs-24 lh-36rpx">
												<view>
													<text class="text--w111-999 pr-10">内容</text>
													<text>{{item.community_num}}</text>
												</view>
												<text class="fs-20 pl-14 text--w111-eee">|</text>
												<view class="pl-14">
													<text class="text--w111-999 pr-10">粉丝</text>
													<text>{{item.fans_num}}</text>
												</view>
											</view>
										</view>
									</view>
									<view class="w-124 h-50 rd-30rpx flex-center bg-gradient text--w111-fff fs-24"
										v-show="!item.is_follow" @tap.stop="followRecommendUser(item,index)">
										<text class="iconfont icon-ic_increase fs-24"></text>关注
									</view>
									<view v-show="item.is_follow" class="w-124 h-50 rd-30rpx b-e flex-center fs-24 text--w111-999">已关注</view>
								</view>
								<view class="w-full mt-38" v-if="item.community.length">
									<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
										<view class="inline-block mr-20" v-for="(pic,i) in item.community" :key="i"
											@tap="authTo(`/pages/discover/${pic.content_type == 1 ? 'discoverDetails' : 'discoverVideo'}/index?id=${pic.id}`)">
											<view class="relative">
												<easy-loadimage
												:image-src="pic.image"
												width="184rpx"
												height="254rpx"
												borderRadius="16rpx"></easy-loadimage>
												<view class="recommend_player flex-center" v-if="pic.content_type == 2">
													<text class="iconfont icon-ic_right2 fs-20 text--w111-fff"></text>
												</view>  
											</view>
										</view>
									</scroll-view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="pb-safe">
					<view class="h-140"></view>
				</view>
			</view>
			<view :style="{'padding-top': marTop}" v-if="tabActive == 1">
				<!-- 话题分类 -->
				<view class="topic-box w-full flex-between-center text--w111-999 relative" >
					<view class="flex-1">
						<scroll-view scroll-x="true"
						scroll-with-animation
						:scroll-into-view="intoindex"
						class="white-nowrap vertical-middle w-676 pl-30"
						show-scrollbar="false">
							<view class="inline-block mr-52">
								<view class="lh-100 h-100 fs-30 line1"
									:class="{'cate-active': cateActive == ''}"
									@tap="changeCate(-1,'')">全部</view>
							</view>
							<view class="inline-block mr-52" 
							v-for="(item,index) in topicList" :key="index"
							:id='"sort"+index'>
								<view class="lh-100 h-100 fs-30 line1"
								style="max-width: 204rpx;"
									:class="{'cate-active': cateActive == item.id}"
									@tap="changeCate(index,item.id)">{{item.name}}</view>
							</view>
						</scroll-view>
					</view>
					<view class="w-76 h-100 flex-center abs-rt topic-box" @tap="toggleCateDrawer">
						<text class="iconfont icon-ic_downarrow fs-36"></text>
					</view>
				</view>
				<!-- 内容瀑布流 -->
				<view class="px-20" v-if="contentList.length">
					<waterfallsFlow :wfList="contentList" @onFlowLike="flowLike"></waterfallsFlow>
				</view>
				<view class="px-20 bg--w111-fff" v-else>
					<emptyPage title="暂无内容～"></emptyPage>
				</view>
				<view class="pb-safe">
					<view class="h-140"></view>
				</view>
			</view>
			<view v-if="tabActive == 2" class="text--w111-fff">
				<discoverVideo
				:swiperData="contentList"
				:showFooter="showFooter"
				:replyStatus="configData.community_comment_status"
				:addReply="configData.community_comment_add"
				@onLike="likeFun"
				@followChange="followUser"
				@onSwiper="beforeRequest"
				@onShare="resShare"></discoverVideo>
			</view>
		</view>
		<view class="fixed-lt w-full bg--w111-fff rd-b-24rpx z-100" :style="{top: sysHeight + 38 + 'px'}"
			v-if="showCateDrawer">
			<view class="w-full pt-30 pr-20 pb-30 pl-30 more-topic-box">
				<view class="flex-between-center">
					<text class="fs-30 fw-500">推荐话题</text>
					<text class="iconfont icon-ic_uparrow fs-36 text--w111-999" @tap="toggleCateDrawer"></text>
				</view>
				<view class="mt-32 flex flex-wrap">
					<view class="h-58 bg--w111-f5f5f5 rd-8rpx px-24 flex-center fs-24 mr-28 mb-28"
						v-for="(item,index) in recommendTopic" :key="index"
						:class="{'active-topic': cateActive == item.id}"
						@tap="changeCate(index,item.id)">{{item.name}}</view>
				</view>
				<view class="flex-between-center mt-24">
					<text class="fs-30 fw-500">其他话题</text>
				</view>
				<view class="mt-32 flex flex-wrap">
					<view class="h-58 rd-8rpx px-24 flex-center fs-24 mr-28 mb-28 b-e"
						v-for="(item,index) in topicNoRecmmend" :key="index"
						:class="{'active-topic': cateActive == item.id}"
						@tap="changeCate(index,item.id)">{{item.name}}</view>
				</view>
			</view>
		</view>
		<view class="mask z-80" v-if="showCateDrawer" @tap="()=>{showCateDrawer = false}"></view>
		<view class="bubble_box" :style="{'top': 40 + sysHeight + 'px','--bgColor': bubbleBg,'--textColor':bubbleText }"
		v-if="showBubble">
			<view class="bubble flex-col justify-between" :style="[bubbleShadow]">
				<view class="flex-y-center pl-16" @tap="bubbleTo('/pages/discover/discoverCreate/index')">
					<text class="iconfont icon-ic_edit"></text>
					<text class="fs-26 pl-18">发布作品</text>
				</view>
				<view :style="[lineColor]"></view>
				<view class="flex-y-center pl-16" @tap="bubbleTo('/pages/discover/discoverUser/index?id=' + uid)">
					<text class="iconfont icon-a-ic_user1"></text>
					<text class="fs-26 pl-18">我的主页</text>
				</view>
			</view>
		</view>
		<pageFooter @newDataStatus="newDataStatus"></pageFooter>
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import { HTTP_REQUEST_URL } from '@/config/app';
	import colors from "@/mixins/color";
	import { mapGetters } from 'vuex';
	import { toLogin } from '@/libs/login.js';
	import {LOGIN_STATUS} from '@/config/cache';
	import {
		getCommunityConfig,
		communityListApi,
		getTopicApi,
		communityLikeApi,
		communityRecommendListApi,
		communitySetInsterestApi,
		communityFlowIndexApi,
		communityShareApi
	} from "@/api/community.js";
	import emptyPage from '@/components/emptyPage.vue';
	import waterfallsFlow from "@/components/discoverWaterfall/WaterfallsFlow.vue";
	import baseDrawer from '@/components/tui-drawer/tui-drawer.vue';
	import pageFooter from '@/components/pageFooter/index.vue';
	import discoverVideo from '@/components/discoverVideo/index.vue';
	import BaseTextMore from "@/components/BaseTextMore.vue"
	export default {
		data() {
			return {
				sysHeight,
				tabActive:1,
				params:{
					page:1,
					limit:10,
					topic_id:'', //话题
					keyword:'',
					is_follow:0, //是否关注
					content_type:''
				},
				topicList:[],
				topicNoRecmmend:[],
				contentList:[],
				recommendList:[],
				recommendParams:{
					page:1,
					limit:20,
				},
				cateActive:'',
				loading:false,
				recLoading:false,
				showCateDrawer:false,
				showBubble:false,
				showFooter:false,
				bubbleBg:'#ffffff',
				bubbleText:'#333333',
				infoData:{},
				followList:[],
				recommendTopic:[],
				intoindex:'',
				marTop:'40px',
				configData:{
					community_status:1,
					community_comment_add:1,
					community_comment_status:1
				}
			}
		},
		mixins: [colors],
		components:{
			emptyPage,
			waterfallsFlow,
			baseDrawer,
			discoverVideo,
			pageFooter,
			BaseTextMore
		},
		provide() {
			return {
				flowLike: this.flowLike
			}
		},
		onPageScroll(object) {
			this.showBubble = false;
			uni.$emit('scroll');
		},
		onReachBottom() {
			this.getList();
		},
		onPullDownRefresh() {
			if(this.tabActive == 0){
				this.params.is_follow = 1;
				this.getFollow();
				this.getRecommendList();
			}else{
				this.params.is_follow = 0;
			}
			this.params.page = 1;
			this.params.topic_id = '';
			this.contentList = [];
			this.loading = false;
			this.getConfig();
			this.getList();
			setTimeout(function () {
				uni.stopPullDownRefresh();
			}, 1000);
		},
		computed:{
			...mapGetters(['isLogin','uid']),
			pageTheme(){
				return this.tabActive == 2 ? 'dark' : 'light'
			},
			bubbleShadow(){
				if([0,1].includes(this.tabActive)){
					return {
						'box-shadow': '0rpx 2rpx 15rpx 0rpx rgba(0,0,0,0.102)'
					}
				}
			},
			menuBoxStyle(){
				// #ifdef MP-WEIXIN
				let res = wx.getMenuButtonBoundingClientRect();
				return {
					width: res.left + 'px'
				}
				// #endif
			},
			lineColor(){
				if(this.tabActive == 2){
					return {
						'border-bottom':  '1rpx solid rgba(238, 238, 238, 0.1)'
					}
				}else{
					return {
						'border-bottom':  '1rpx solid #eeeeee'
					}
				}
			}
		},
		onLoad() {
			// #ifdef MP-WEIXIN || APP-PLUS
			this.getHeight();
			// #endif
			this.getConfig();
			this.getTopic();
			this.getList();
		},
		methods: {
			getConfig(){
				getCommunityConfig().then(res=>{
					this.configData = res.data;
				})
			},
			getHeight(){
				let that = this;
				// #ifdef MP-WEIXIN
				const query = uni.createSelectorQuery().in(this);
				query.select('.header_box').boundingClientRect(data => {
				    if (data) {
						that.marTop = data.height + 'px';
				    }
				}).exec();
				// #endif
				// #ifdef APP-PLUS || H5
				that.marTop = 40 + sysHeight + 'px';
				// #endif
			},
			changeTab(val){
				if(this.tabActive == val) return
				uni.pageScrollTo({
					duration: 0,
					scrollTop: 0
				})
				if(val == 0){
					this.params.is_follow = 1;
					this.params.content_type = '';
					this.bubbleBg = '#ffffff';
					this.bubbleText = '#333333';
					this.getFollow();
					this.getRecommendList();
				}else if(val == 1){
					this.params.is_follow = 0;
					this.params.content_type = '';
					this.bubbleBg = '#ffffff';
					this.bubbleText = '#333333';
				}else{
					// #ifdef APP-PLUS
					let that = this;
					let href = encodeURIComponent(`${HTTP_REQUEST_URL}/pages/discover/discoverVideo/index?content_type=0&token=${that.$Cache.get(LOGIN_STATUS)}`)
					uni.navigateTo({
						url: `/pages/discover/discoverVideo/app?url=${href}`
					})
					return
					// #endif
					// #ifdef H5 || MP-WEIXIN
					this.params.is_follow = 0;
					this.bubbleBg = 'rgba(51, 51, 51, 0.7)';
					this.bubbleText = '#ffffff';
					this.infoData = null;
					// #endif
					
				}
				this.showBubble = false;
				this.showCateDrawer = false;
				this.tabActive = val;
				this.params.page = 1;
				this.params.topic_id = '';
				this.contentList = [];
				this.loading = false;
				this.getList();
			},
			changeCate(index,id){
				this.cateActive = id;
				this.$nextTick(()=>{
					this.intoindex = 'sort' + index;
				})
				this.params.page = 1;
				this.params.topic_id = id;
				this.contentList = [];
				this.loading = false;
				this.showCateDrawer = false;
				this.getList();
			},
			getFollow(){
				communityFlowIndexApi().then(res=>{
					this.followList = res.data;
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				});
			},
			beforeRequest(current){
				if(this.showBubble){
					this.showBubble = false;
				}
				if(this.contentList.length - 1 == current){
					this.getList();
				}
			},
			resShare(data){
				this.infoData = data;
			},
			authTo(url){
				this.$util.JumpPath(url);
			},
			bubbleTo(url){
				this.showBubble = false;
				if(this.isLogin){
					this.$util.JumpPath(url);
				}else{
					toLogin();
				}
				
			},
			toggleBubble(){
				this.showBubble = !this.showBubble;
			},
			newDataStatus(val){
				this.showFooter = val ? true : false;
			},
			getTopic(){
				getTopicApi({page:1,limit:10}).then(res=>{
					this.topicList = res.data;
					this.topicNoRecmmend = this.topicList;
				})
				getTopicApi({page:1,limit:10, is_recommend: 1 }).then(res=>{
					this.recommendTopic = res.data;
				})
			},
			getList(){
				if (this.loading) return;
				this.loading = true;
				communityListApi(this.params).then(res=>{
					// let arr = [];
					// res.data.forEach(item=>{
					// 	arr.push(item.author_image);
					// })
					// console.table(arr);
					let list = res.data;
					let loading = list.length < this.params.limit;
					this.contentList = this.contentList.concat(list);
					this.params.page++;
					this.loading = loading;
				})
			},
			toggleCateDrawer(){
				this.showCateDrawer = !this.showCateDrawer;
			},
			likeFun(data){
				this.contentList[data.index].is_like = data.status;
				if(data.status == 1){
					this.contentList[data.index].like_num++;
				}else{
					this.contentList[data.index].like_num--;
				}
			},
			flowLike(data){
				let index = this.contentList.findIndex(item=> data.id == item.id);
				this.contentList[index].is_like = data.status;
				if(data.status == 1){
					this.contentList[index].like_num++;
				}else{
					this.contentList[index].like_num--;
				}
			},
			contentLike(item){
				let id = item.id;
				let status = item.is_like == 1 ? 0 : 1;
				let that = this;
				communityLikeApi(id,{status}).then(res=>{
					item.is_like = status;
					if(status == 1){
						item.like_num++
					}else{
						item.like_num--
					}
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			followUser(data){
				this.contentList[data.index].is_follow = 1;
				return this.$util.Tips({
					title: '关注成功'
				});
			},
			followRecommendUser(item,index){
				if(!this.isLogin) return this.$util.Tips({
					title: '请登录'
				});
				communitySetInsterestApi(item.relation_id,{status:1}).then(res=>{
					item.is_follow = 1;
					// this.getFollow();
					return this.$util.Tips({
						title: res.msg
					});
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				});
			},
			getRecommendList(){
				if (this.recLoading) return;
				this.recLoading = true;
				communityRecommendListApi(this.recommendParams).then(res=>{
					this.recommendList = res.data;
					this.recLoading = false;
				})
			},
			toRight(){
				setTimeout(()=>{
					uni.navigateTo({
						url: '/pages/discover/discoverFollow/index?type=1'
					})
				},500)
			},
			openShare(item){ 
				let that = this;
				// #ifdef H5
				uni.setClipboardData({
					data: `${HTTP_REQUEST_URL}/pages/discover/${item.content_type == 1 ? 'discoverDetails' : 'discoverVideo'}/index?id=${item.id}&spid=${this.uid}`,
					success: () =>
						uni.showToast({
							title: '链接已复制'
						})
				})
				// #endif
				// #ifdef MP-WEIXIN
				this.infoData = item;
				// #endif
				communityShareApi(item.id).catch(err=>{
					console.error(err);
				})
			},
		},
		/**
		 * 用户点击右上角分享
		 */
		// #ifdef MP
		onShareAppMessage() {
			let that = this;
			return {
				title: that.infoData.title || that.infoData.content,
				imageUrl: that.infoData.image,
				path: `/pages/discover/${that.infoData.content_type == 1 ? 'discoverDetails' : 'discoverVideo'}/index?id=${that.infoData.id}&spid=${that.uid}`
			};
		}
		// #endif
	}
</script>

<style scoped lang="scss">
.plant-grass{
	height: 100vh;
}
.guodu{
	transition: background-color 0.5s ease; /* 添加过渡效果 */
}
.dark{
	background-color: #000000;
	.header_box{
		background-color: transparent;
	}
	.tab-item{
		color: rgba(245,245,245,0.6);
	}
	.icon-ic_menu3,.icon-ic_search{
		color: #fff;
	}
	.active-tab{
		color: #fff;
		font-weight: 500;
	}
}
.light{
	.header_box{
		background-color: #ffffff;
	}
	.tab-item{
		color: rgba(51,51,51,0.6);
	}
	.icon-ic_menu3,.icon-ic_search{
		color: #333;
	}
	.active-tab{
		color: #333;
		font-weight: 500;
	}
}
.new-dot{
	position: absolute;
	right: 10rpx;
	top: 0;
	width: 16rpx;
	height: 16rpx;
	border-radius: 50%;
	background-color: var(--view-theme);
}
.bottom-40{
	bottom: 40rpx;
}
.guanzhu{
	padding: 40rpx 0 40rpx 30rpx;
}
.guanzhu-swiper{
	width: 100%;
	height: 650rpx;
	.swiper-item{
		width: 100%;
		height: 100%;
	}
}
.scroll-pro ~ .scroll-pro{
	margin-left: 30rpx;
}
.pro-card ~ .pro-card{
	margin-top: 20rpx;
}
.player{
	position: absolute;
	top: 50%;
	left:50%;
	transform: translate(-50%,-50%);
	width: 72rpx;
	height: 72rpx;
	background-color: rgba(51, 51, 51, .5);
	border-radius: 50%;
}
.recommend_player{
	position: absolute;
	top: 50%;
	left:50%;
	transform: translate(-50%,-50%);
	width: 40rpx;
	height: 40rpx;
	background-color: rgba(51, 51, 51, .5);
	border-radius: 50%;
}
.b-e{
	border: 1rpx solid #eee;
}
.topic-box{
	background: linear-gradient( 180deg, #FFFFFF 0%, #F5F5F5 100%);
}
.lh-100{
	line-height: 100rpx;
}
.recommend{
	.card{
		background: #FFFFFF;
		border-radius: 24rpx;
		padding: 32rpx 30rpx 30rpx;
	}
}
.text--w111-eee{
	color: #eee;
}
.theme-border{
	border: 1px solid var(--view-theme);
}
.cate-active{
	font-weight: 500;
	color: var(--view-theme);
	position: relative;
	&:after{
		content: '';
		position: absolute;
		left: 50%;
		transform: translateX(-50%);
		bottom: 14rpx;
		width: 64rpx;
		height: 5rpx;
		background: var(--view-theme);
		border-radius: 4rpx;
	}
}
.active-topic{
	color: var(--view-theme);
	background-color: var(--view-minorColorT);
}
.more-topic-box{
	max-height: 900rpx;
	overflow-y: scroll;
}
.bubble_box{
	position: fixed;
	left: 12rpx;
	width: 214rpx;
	z-index: 5000;
	.bubble{
		width: 214rpx;
		height: 198rpx;
		/* #ifdef MP-WEIXIN */
		background: var(--bg-color);
		color: var(--text-color);
		/* #endif */
		/* #ifdef H5 || APP-PLUS */
		background: var(--bgColor);
		color: var(--textColor);
		/* #endif */
		border-radius: 16rpx;
		padding: 30rpx 16rpx 30rpx 16rpx;
		position: relative;
		&:before{
			content: '';
			position: absolute;
			left: 22rpx;
			top: -20rpx;
			width: 0;
			height: 0;
			border-style: solid;
			border-width: 0 10px 10px 10px;
			/* #ifdef MP-WEIXIN */
			border-color: transparent transparent  var(--bg-color) transparent;
			/* #endif */
			/* #ifdef H5 || APP-PLUS */
			border-color: transparent transparent  var(--bgColor) transparent;
			/* #endif */
		}
	}
}
.icon-ic_love_2{
	color: #e93323;
}
// #ifdef APP-PLUS || H5
.pro-card /deep/uni-swiper .uni-swiper-dots-horizontal .uni-swiper-dot{
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
}
// #endif
// #ifdef MP
.pro-card swiper /deep/.wx-swiper-dot{
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
}
 /* #endif */

</style>
