<template>
	<!-- 商品列表 -->
	<view :style="colorStyle">
		<view class="header_box fixed-lt w-full z-20 bg--w111-fff" :style="{'padding-top': sysHeight + 'px'}">
			<view class="h-80 px-20 flex-y-center">
				<text class="iconfont icon-ic_leftarrow fs-40 mr-16" @tap="goBack"></text>
				<!--  #ifdef  MP-WEIXIN -->
				<view class="w-438 h-58 rd-30 bg--w111-f5f5f5 flex-y-center px-32 relative">
				<!--  #endif -->
				<!--  #ifndef  MP-WEIXIN -->
				<view class="flex-1 h-58 rd-30 bg--w111-f5f5f5 flex-y-center px-32 relative">
				<!--  #endif -->
					<text class="iconfont icon-ic_search fs-24"></text>
					<input :value='where.keyword' class="pl-18 w-460 line1 fs-24" placeholder="请输入商品名称" @confirm="searchSubmit" />
					<text class="iconfont icon-ic_close1 fs-28 text--w111-999 z-10" v-if="where.keyword" @tap="clearWord"></text>
				</view>
			</view>
			<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full pl-32"
			show-scrollbar="false" v-if="cateSearch">
			  <view class="inline-block fs-30 h-80 lh-80rpx mr-40"
				v-for="item in filterCateList" :key="item.id"
				:class="item.id == where.sid ? 'text-primary-con fw-500 fs-32' : ''"
				@tap="cateCheck(item)">{{item.cate_name}}</view>
			</scroll-view>
			<view class="h-88 px-32 flex-between-center relative">
				<view class="text--w111-333 fw-500 select_cate relative" v-if="!cateSearch && title">{{title}}</view>
				<view class="text--w111-333 fs-26 fw-500 flex-y-center" v-else @tap="toggleSortable">
					<text>综合排序</text>
					<text class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26" @tap='set_where(3)'>
					<text :class="[1,2].includes(stock) ? 'text-primary-con' : ''">销量</text>
					<text v-show="stock==2" class="iconfont icon-ic_down2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="stock==1" class="iconfont icon-ic_up2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="stock==0" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26" @tap='set_where(2)'>
					<text :class="[1,2].includes(price) ? 'text-primary-con' : ''">价格</text>
					<text v-show="price==2" class="iconfont icon-ic_down2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="price==1" class="iconfont icon-ic_up2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="price==0" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26">
					<text class="iconfont" :class="is_switch ? 'icon-a-ic_Imageandtextsorting' : 'icon-a-ic_QRcode'" @tap='Changswitch'></text>
					<text class="menu_line"></text>
					<view @tap="showFilterDrawer = true">筛选</view>
					<text class="iconfont icon-ic_sort pl-8" @tap="showFilterDrawer = true"></text>
				</view>
				<view class="sortable-box w-full bg--w111-fff rd-b-24rpx z-999" v-if="showSortAbleBox">
					<view class="flex-between-center pb-40"
						v-for="(item, index) in sortableShowTab" :key="index" @tap="checkSortable(item.value)">
						<text class="fs-26" :class="item.value == where.defaultOrder ? 'text-primary-con' : 'text--w111-333'">{{item.title}}</text>
						<image class="w-28 h-28" src="../static/dui-icon.png" v-show="item.value == where.defaultOrder"></image>
					</view>
				</view>
			</view>
			<view class="py-16 flex-y-center bg--w111-fff" v-if="(promotionList.length && !cateSearch) || where.promotions_id">
				<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
					<view
						class="inline-block op-border ml-24 h-48 lh-48rpx bg--w111-f5f5f5 px-24 rd-24 text-center fs-22 text--w111-333"
						v-for="item in promotionList" :key="item.id"
						:class="where.promotions_id == item.id ? 'active' : ''"
						@tap="promotionTap(item)">
							<text class="w-full line1 text-center">{{item.desc}}</text>
						 </view>
				</scroll-view>
			</view>
		</view>
		<view class="pt-16 pl-20 pr-20 bg--w111-fff" :style="{'margin-top':marTop + 'rpx'}" v-if="is_switch==false">
			<view class="flex mb-32 pro_item" v-for="(item,index) in tempArr" :key="index" @tap="goDetail(item)">
				<view class="list-pic w-240 h-240 relative">
					<easy-loadimage
					:image-src="item.image"
					:borderSrc="item.activity_frame.image"
					width="240rpx"
					height="240rpx"
					borderRadius="20rpx"></easy-loadimage>
				</view>
				<view class="flex-1 pl-20 flex-col justify-between">
					<view class="w-full">
						<view class="w-full line2 fs-28 text--w111-333 lh-40rpx">
							<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
						</view>
						<view class="w-full flex items-end flex-wrap mt-16">
							<BaseTag
								:text="label.label_name"
								:color="label.color"
								:background="label.bg_color"
								:borderColor="label.border_color"
								:circle="label.border_color ? true : false"
								:imgSrc="label.icon"
								v-for="(label, idx) in item.store_label" :key="idx"></BaseTag>
						</view>
					</view>
					<view class="flex-y-center">
						<baseMoney :money="item.price" symbolSize="24" integerSize="40" decimalSize="24" weight></baseMoney>
						<view class="inline-block h-26 lh-28rpx rd-14rpx bg--w111-F7E9CD fs-22 ml-8" v-if="Number(item.vip_price) > 0">
							<text class="inline-block h-26 lh-28rpx svip_rd fs-18 bg--w111-484643 text--w111-FDDAA4 px-8">SVIP</text>
							<text class="px-8 fs-22">¥{{item.vip_price}}</text>
						</view>
					</view>
					<view class="flex-between-center">
						<view class="text--w111-999 fs-22">
							<text>已售{{item.sales}}件</text>
							<text class="pl-16">评分 {{item.star}}</text>
						</view>
						<view class="w-44 h-44 rd-24 bg-gradient flex-center" @tap.stop="addCartTap(item)">
							<text class="iconfont icon-ic_ShoppingCart1 text--w111-fff fs-26"></text>
						</view>
					</view>
				</view>
			</view>
			<view class='loadingicon acea-row row-center-wrapper' v-if='tempArr.length > 0'>
				<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
			</view>
		</view>
		<view class="p-20 relative" :style="{'margin-top':marTop + 'rpx'}" v-else>
			<view class="jinabian"></view>
			<waterfallsFlow ref="waterfallsFlow" :wfList="tempArr" @itemTap="goDetail"></waterfallsFlow>
		</view>
		<view class='px-20' v-if="tempArr.length==0 && where.page > 1">
			<emptyPage :title="where.keyword ? '无搜索结果,换个词试试吧' : '暂无商品，去看点别的吧～'" 
				:src="where.keyword ? '/statics/images/noSearch.gif' : '/statics/images/empty-box.gif'"></emptyPage>
			<recommend :hostProduct="hostProduct"></recommend>
		</view>
		<view class="fixed-lb w-full pb-safe bg--w111-fff z-101" 
			v-if="promotionList.length && where.promotions_id">
			<view class="w-full h-108 px-20 flex-between-center">
				<view class="flex-y-center" @tap="getCartList(0)">
					<view class="w-80 h-80 rd-50-p111- flex-center cart-icon relative">
						<text class="iconfont icon-ic_ShoppingCart1 fs-48"></text>
						<uni-badge class="badge-style" v-if="cart_num > 0" :text="cart_num"></uni-badge>
					</view>
					<view class="ml-24">
						<view class="flex-y-center">
							<text class="fs-28 fw-500">小计：</text>
							<baseMoney :money="discountInfo.deduction.pay_price" symbolSize="28" integerSize="40" decimalSize="28"
								incolor="#333" weight></baseMoney>
						</view>
						<view class="fs-24 lh-34rpx text--w111-999"
							v-if="discountInfo.deduction.sum_price"
						>已优惠¥{{$util.$h.Sub(discountInfo.deduction.sum_price,discountInfo.deduction.pay_price)}}</view>
					</view>
				</view>
				<view class="w-176 h-72 rd-40rpx flex-center text--w111-fff fs-26 fw-500 bg-gradient"
					@tap="subOrder">去结算({{cart_num}})</view>
			</view>
		</view>
		<productWindow 
			:attr="attr" 
			:isShow='1' 
			:iSplus='1' 
			:iScart='1' 
			:type="2"
			:storeInfo='storeInfo' 
			@myevent="onMyEvent"
			@ChangeAttr="ChangeAttr" 
			@ChangeCartNum="ChangeCartNumDuo" 
			@attrVal="attrVal" 
			@iptCartNum="iptCartNum"
			@goCat="goCatNum"
			id='product-window' 
			:is_vip="is_vip" :fangda='false'></productWindow>
		<cartList 
			:cartData="cartData" 
			:hideEmpty="false"
			@closeList="closeList"
			@ChangeCartNumDan="ChangeCartList" 
			@ChangeSubDel="ChangeClearCart" 
			@ChangeOneDel="ChangeOneDel">
		</cartList>
		<filterDrawer
			:visible="showFilterDrawer"
			:promotionId="where.promotions_id"
			:brandList="brandList"
			:labelList="labelList"
			:promotionList="promotionList"
			@closeDrawer="()=>{showFilterDrawer = false}"
			@filterChange="filterConfirm"></filterDrawer>
		<view class="mask" v-if="showSortAbleBox" @tap="showSortAbleBox = false"></view>
		<home></home>
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import homeList from '@/components/homeList';
	import productWindow from '@/components/productWindow';
	import waterfallsFlow from "@/components/WaterfallsFlow/WaterfallsFlow.vue";
	import {
		getProductslist,
		getProductHot,
		searchFilterApi,
		levelCategoryApi,
		getAttr,
		postCartAdd,
		postCartNum
	} from '@/api/store.js';
	import {cartDel} from "@/api/order.js";
	import { toLogin } from '@/libs/login.js';
	import recommend from '@/components/recommend';
	import cartList from '@/components/cartList';
	import filterDrawer from '../components/filterDrwer/index.vue';
	import emptyPage from '@/components/emptyPage.vue';
	import { mapGetters } from "vuex";
	import { goShopDetail } from '@/libs/order.js';
	import { vcartList, getCartCounts, cartCompute } from '@/api/order.js';
	import colors from '@/mixins/color.js';
	import skuSelect from '@/mixins/skuSelect.js'
	import { HTTP_REQUEST_URL } from '@/config/app';
	export default {
		computed: {
			...mapGetters(['uid', 'isLogin'])
		},
		components: {
			recommend,
			homeList,
			waterfallsFlow,
			productWindow,
			filterDrawer,
			emptyPage,
			cartList
		},
		mixins: [colors, skuSelect],
		data() {
			return {
				id: 0,
				productValue: [], //系统属性
				is_vip: 0, //是否是会员
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				attrValue: '', //已选属性
				storeName: '',
				sysHeight: sysHeight,
				goodList: true,
				currentPage: false,
				tempArr: [],
				is_switch: true,
				where: {
					sid: 0,
					keyword: '',
					priceOrder: '',
					salesOrder: '',
					news: 0,
					page: 1,
					limit: 10,
					cid: 0,
					tid: 0,
					brand_id: '',
					promotions_id: 0,
					promotions_type:0,
					defaultOrder:0
				},
				price: 0,
				stock: 0,
				nows: false,
				loadend: false,
				loading: false,
				loadTitle: '加载更多',
				title: '',
				hostProduct: [],
				hotPage: 1,
				hotLimit: 10,
				hotScroll: false,
				brandList: [],
				promotionList:[],
				labelList:[],
				filterCateList:[],
				storeInfo: {},
				totalPrice: 0,
				promotionsInfo: {},
				totalNum: 0,
				imgHost: HTTP_REQUEST_URL,
				isShowAuth: false,
				marTop:'',
				showFilterDrawer:false,
				cateSearch:false,
				showSortAbleBox:false,
				sortableShowTab:[
					{title:'综合排序',value:0},
					{title:'好评优先',value:1},
					{title:'新品优先',value:2},
				],
				cartData: {
					cartList: [],
					iScart: false
				},
				cart_num: 0,
				discountInfo:{
					deduction:{},
				}
			};
		},
		onLoad: function(options) {
			this.where.cid = options.cid || 0;
			this.$set(this.where, 'sid', options.sid || 0);
			this.$set(this.where, 'tid', options.tid || 0);
			this.title = options.title || '';
			this.$set(this.where, 'keyword', options.searchValue || '');
			this.$set(this.where, 'productId', options.productId || '');
			this.$set(this.where, 'brand_id', options.brandId || 0);
			if (options.promotions_type) {
				this.where.promotions_type = options.promotions_type;
				this.where.promotions_id = options.promotions_id;
			}
			if(options.sid && options.sid != 0){
				//用于判断顶部分类选择内容是否展示
				this.cateSearch = true;
				this.getProCate();
			}
			this.getSearchFilter();
		},
		created() {
			this.$eventHub.$on('onCartAddChange',(data)=>{
				if(data.spec_type){
					this.goCartDuo(data);
				}else{
					this.goCartDan(data, 0);
				}
			});
		},
		methods: {
			addCartTap(data){
				if(data.spec_type){
					this.goCartDuo(data);
				}else{
					this.goCartDan(data, 0, true);
				}
			},
			// 购物车计算
			getCartCompute(cartId) {
				cartCompute({cartId}).then(res => {
					this.discountInfo.deduction = res.data.deduction;
				}).catch(err => {
					this.$util.Tips({
						title: err
					})
				})
			},
			getTotalPrice(){
				vcartList().then(res => {
					if (res.data.length) {
						let cartId = res.data.map(item=> item.id).join(',');
						this.getCartCompute(cartId);
					}
				})
			},
			getCartList(iSshow) {
				let that = this;
				vcartList().then(res => {
					that.$set(that.cartData, 'cartList', res.data);
					if (res.data.length) {
						that.$set(that.cartData, 'iScart', iSshow ? false : !that.cartData.iScart);
						let cartId = res.data.map(item=> item.id).join(',');
						that.getCartCompute(cartId);
					} else {
						that.$set(that.cartData, 'iScart', false);
					}
					if (!res.data.length) {
						this.$store.commit('indexData/setCartNum', 0);
						this.tempArr.forEach((item) => {
							item.cart_num = 0
						})
					}
				})
			},
			getCartNum() {
				let that = this;
				getCartCounts().then(res => {
					this.cart_num = res.data.count;
					this.$store.commit('indexData/setCartNum', res.data.count)
				});
			},
			goCart() {
				if (this.where.promotions_type) {
					uni.switchTab({
						url: '/pages/order_addcart/order_addcart'
					})
				} else {
					uni.switchTab({
						url: '/pages/goods_cate/goods_cate'
					})
				}
			},
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0).then(res => {
					uni.hideLoading();
					that.$set(that.attr, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that, 'storeInfo', res.data.storeInfo);
					that.DefaultSelect();
				})
			},
			getProCate(){
				levelCategoryApi({id:this.where.sid}).then(res=>{
					this.filterCateList = res.data;
				})
			},
			// 筛选
			getSearchFilter() {
				let data = {
					keyword: this.where.keyword,
					cid: this.where.cid,
					sid: this.where.sid,
					tid: this.where.tid,
					productId: this.where.productId,
					promotions_type: this.where.promotions_type,
					// promotions_id: this.where.promotions_id
				};
				searchFilterApi(data).then(res => {
					this.brandList = res.data.brand;
					this.labelList = res.data.store_label;
					this.promotionList = res.data.promotions;
					if(res.data.promotions.length){
						this.where.promotions_id = this.promotionList[0].id;
						if(this.isLogin){
							this.getCartList(1);
							this.getCartNum();
						}
						this.getProductList();
					}
					this.getMarTop();
				}).catch(err => {
					return this.$util.Tips({
						title: err.msg
					});
				})
			},
			toggleSortable(){
				this.showSortAbleBox = !this.showSortAbleBox;
			},
			checkSortable(val){
				this.where.defaultOrder = val;
				this.showSortAbleBox = false;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			promotionTap(item){
				this.where.promotions_id = this.where.promotions_id == 0 ? item.id : 0;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			clearWord(){
				this.where.keyword = ''
			},
			cateCheck(item){
				this.where.sid = item.id;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			filterConfirm(data){
				this.showFilterDrawer = false;
				Object.assign(this.where,data);
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			// 去详情页
			goDetail(item) {
				this.currentPage = false;
				goShopDetail(item, this.uid).catch(res => {
					uni.navigateTo({
						url: `/pages/goods_details/index?id=${item.id}`
					});
				});
			},
			Changswitch: function() {
				let that = this;
				this.currentPage = false
				that.is_switch = !that.is_switch
			},
			searchSubmit: function(e) {
				let that = this;
				this.currentPage = false
				that.$set(that.where, 'keyword', e.detail.value);
				that.loadend = false;
				that.$set(that.where, 'page', 1)
				this.getProductList(true);
				this.getSearchFilter();
			},
			/**
			 * 获取我的推荐
			 */
			get_host_product: function() {
				let that = this;
				if (that.hotScroll) return
				getProductHot(
					that.hotPage,
					that.hotLimit,
				).then(res => {
					that.hotPage++
					that.hotScroll = res.data.length < that.hotLimit
					that.hostProduct = that.hostProduct.concat(res.data)
					// that.$set(that, 'hostProduct', res.data)
				});
			},
			//点击事件处理
			set_where: function(e) {
				this.currentPage = false
				switch (e) {
					case 1:
						// #ifdef H5
						return history.back();
						// #endif
						// #ifndef H5
						return uni.navigateBack({
							delta: 1,
						})
						// #endif
						break;
					case 2:
						if (this.price == 0) this.price = 1;
						else if (this.price == 1) this.price = 2;
						else if (this.price == 2) this.price = 0;
						this.stock = 0;
						break;
					case 3:
						if (this.stock == 0) this.stock = 1;
						else if (this.stock == 1) this.stock = 2;
						else if (this.stock == 2) this.stock = 0;
						this.price = 0
						break;
					case 4:
						this.nows = !this.nows;
						break;

				}
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			//设置where条件
			setWhere: function() {
				if (this.price == 0) this.where.priceOrder = '';
				else if (this.price == 1) this.where.priceOrder = 'asc';
				else if (this.price == 2) this.where.priceOrder = 'desc';
				if (this.stock == 0) this.where.salesOrder = '';
				else if (this.stock == 1) this.where.salesOrder = 'asc';
				else if (this.stock == 2) this.where.salesOrder = 'desc';
				this.where.news = this.nows ? 1 : 0;
			},
			productslist(){
				
			},
			//查找产品
			getProductList: function(isPage) {
				let that = this;
				that.setWhere();
				if (that.loadend) return;
				if (that.loading) return;
				if (isPage === true) {
					that.$set(that, 'tempArr', []);
				}
				that.loading = true;
				that.loadTitle = '';
				getProductslist(that.where).then(res => {
					let list = res.data;
					let productList = that.$util.SplitArray(list, that.tempArr);
					let loadend = list.length < that.where.limit;
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.$set(that, 'tempArr', productList);
					that.$set(that.where, 'page', that.where.page + 1);
					if (!that.tempArr.length) this.get_host_product();
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},
			getMarTop(){
				if(this.promotionList.length || this.filterCateList.length){
					this.$nextTick(()=>{
						this.marTop = (115 + sysHeight) * 2;
					})
				}else{
					this.$nextTick(()=>{
						this.marTop = (75 + sysHeight) * 2
					})
				}
			},
			// 生成订单；
			subOrder() {
				let that = this,
					list = that.cartData.cartList,
					ids = [];
				if (list.length) {
					list.forEach(item => {
						if (item.attrStatus && item.status) {
							ids.push(item.id)
						}
					});
					uni.navigateTo({
						url: '/pages/goods/order_confirm/index?cartId=' + ids.join(',')
					});
					that.cartData.iScart = false;
				} else {
					return that.$util.Tips({
						title: '请选择产品'
					});
				}
			},
			goBack(){
				// uni.navigateBack()
				let pages = getCurrentPages(); // 获取当前打开过的页面路由数，
				if (pages.length > 1) {
					uni.navigateBack()
				} else {
					uni.switchTab({
						url: '/pages/index/index'
					});
				}
			},
			ChangeClearCart(event){
				let that = this,
					list = that.cartData.cartList,
					ids = [];
				list.forEach(item => {
					ids.push(item.id)
				});
				cartDel(ids.join(",")).then(res => {
					that.$set(that.cartData, 'cartList', []);
					that.cartData.iScart = false;
					that.discountInfo.deduction.pay_price = 0;
					that.discountInfo.deduction.sum_price = 0;
					that.getCartNum();
				})
			},
		},
		onPageScroll(e) {
			this.currentPage = false;
			uni.$emit('scroll');
		},
		onReachBottom() {
			if (this.tempArr.length > 0) {
				this.getProductList();
			} else {
				this.get_host_product();
			}
		},
		onPullDownRefresh() {
			if (this.tempArr.length > 0) {
				this.getProductList();
			} else {
				this.get_host_product();
			}
			setTimeout(function () {
				uni.stopPullDownRefresh();
			}, 1000);
		},
	}
</script>
<style scoped lang="scss">
	.z-200{
		z-index: 200;
	}
	.z-101{
		z-index: 101;
	}
	.pro_item ~ .pro_item{
		margin-top: 32rpx;
	}
	.border-picture {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		border-radius: 16rpx 16rpx 0 0;
		background: center/cover no-repeat;
	}
	.menu_line {
		width: 1px;
		height: 30rpx;
		background: #B3B3B3;
		margin: 0 20rpx;
	}
	.icon-ic_close1{
		position: absolute;
		right: 34rpx;
		top: 50%;
		transform: translateY(-50%);
	}
	.select_cate{
		&:after{
			content: '';
			position: absolute;
			top: 42rpx;
			left:50%;
			transform: translateX(-50%);
			width:36rpx;
			height:10rpx;
			background-image: url('../static/select_zs.png');
			background-size: 100%;
		}
	}
	.jinabian{
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 330rpx;
		background: linear-gradient(180deg, #FFFFFF 0%, rgba(255,255,255,0) 100%);
	}
	.info_box{
		padding: 16rpx 20rpx;
		border-radius: 0 0 20rpx 20rpx;
		background-color: #fff;
	}
	.text-primary-con{
		color: var(--view-theme);
	}
	.bg-primary-light{
		background: var(--view-minorColorT);
	}
	.bg--w111-484643{
		background: linear-gradient(90deg, #484643 0%, #1F1B17 100%);
	}
	.text--w111-FDDAA4{
		color: #FDDAA4;
	}
	.svip_rd{
		border-radius: 14rpx 0 8rpx 14rpx;
	}
	.op-border{
		border: 1px solid #f5f5f5;
	}
	.active{
		border: 1px solid var(--view-theme);
		color: var(--view-theme);
		background: var(--view-minorColorT);
	}
	.sortable-box{
		padding: 32rpx 32rpx 0;
		position: absolute;
		top: 88rpx;
		left: 0;
	}
	.cart-icon{
		background: #F9F9F9;
		border: 2rpx solid #F2F2F2;
	}
	.badge-style {
		position: absolute;
		top: -10rpx;
		right: -20rpx;
		/deep/ .uni-badge--error {
			background-color: var(--view-theme) !important;
		}
		.uni-badge {
			color: var(--view-theme);
			border: 1px solid var(--view-theme);
			z-index: 29;
		}
	}
</style>
