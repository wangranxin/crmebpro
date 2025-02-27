<template>
	<view>
		<view class="flex-col bg--w111-fff" :style="{ height: windowHeight + 'px' }">
			<view :style="{paddingTop: sysHeight + 'px'}" class="text--w111-999">
				<view class="h-80 px-32 flex-y-center">
					<!-- #ifdef MP -->
					<view class="w-508 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32" @tap="goSearch">
					<!-- #endif -->
					<!-- #ifndef MP -->
					<view class="w-full h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32" @tap="goSearch">
					<!-- #endif -->
						<text class="iconfont icon-ic_search fs-28"></text>
						<text class="fs-24 pl-18">请输入商品名称</text>
					</view>
				</view>
			</view>
				<view class="scroll_box flex flex-1">
					<view class="w-168 h-full bg--w111-f5f5f5">
						<scroll-view :scroll-top="0" scroll-y="true" class="h-full">
							<view class="w-168 h-96 flex-center fs-26 text--w111-666"
								v-for="(item,index) in categoryList":key="index"
								:class="index == navActive?'aside_active':''"
								@tap="tapNav(index,item)">
								{{item.cate_name}}
							</view>
							<view class="white-box"></view>
						</scroll-view>
					</view>
					<view class="relative w-full h-full">
						<view class="flex-1 pl-24 pr-20 abs-lt" v-if="categoryErList.length">
							<view class="flex mt-24 mb-16">
								<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-464"
									show-scrollbar="false">
									<view class="inline-block mr-16"
										v-for="(item,index) in categoryErList" :key="index"
										@tap="longClick(index,item)">
										<view
											class="w-144 h-56 rd-30rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
											:class="index===tabClick?'cate_active':''">{{item.cate_name}}</view>
									</view>
								</scroll-view>
								<view class="w-56 h-56 rd-30rpx bg--w111-f5f5f5 flex-center ml-16"
									v-if="categoryErList.length"
									@tap="openCateDrawer(false)">
									<text class="iconfont icon-ic_downarrow fs-32 text--w111-333"></text>
								</view>
							</view>
						</view>
						<view class="h-96" v-if="categoryErList.length"></view>
						<view class="px-24">
							<scroll-view :scroll-top="0" scroll-y="true" @scrolltolower="lower" @scroll="scroll"
								:style="{'height':scrollHeight + 'px'}">
								<!-- 大图模板 -->
								<view v-if="showType == 1">
									<view class="mb-24" v-for="(item,index) in tempArr" :key="index"
										@tap="goDetail(item)">
										<view class="picture-box">
											<easy-loadimage
												mode="widthFix"
												:image-src="item.recommend_image ? item.recommend_image : item.image"
												:borderSrc="item.recommend_image ? '' :item.activity_frame.image"
												width="100%"
												borderRadius="20rpx 20rpx 0 0"></easy-loadimage>
										</view>
										<view class="bg--w111-fff rd-b-20rpx pt-16 pl-24 pr-24 pb-24">
											<view class="w-full line2 fs-28 text--w111-333 lh-40rpx">
												<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
											</view>
											<view class="flex-between-center mt-20">
												<view class="flex-y-center flex-wrap flex-1">
													<baseMoney :money="item.price" symbolSize="24" integerSize="40"
														decimalSize="24" weight>
													</baseMoney>
													<view class="svip-label ml-8" v-if="item.vip_price && item.vip_price > 0">
														<text class="text">SVIP</text>
														<text class="px-8 SemiBold">¥{{item.vip_price}}</text>
													</view>
													<text class="fs-22 text--w111-8e8e8e pl-12" v-else>已售{{item.sales}}</text>
												</view>
												<view
													class="w-172 h-58 rd-30rpx bg-color flex-center fs-24 text--w111-fff relative"
													v-if="item.spec_type" @tap.stop="goCartDuo(item)">
													<text>{{item.cart_button == 0 ? '立即购买' : '加入购物车'}}</text>
													<uni-badge class="badge-style" v-if="item.cart_num > 0" :text="item.cart_num"></uni-badge>
												</view>
												<view v-if="!item.spec_type && !item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff "
														@tap.stop="goCartDan(item,index)">
														<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
													</view>
												</view>
												<view class="flex-y-center" v-if="!item.spec_type && item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg--w111-f5f5f5 text--w111-333"
														:class="{'disabled-btn': item.min_qty && item.cart_num == item.min_qty}"
														@tap.stop="ChangeCartNumDan(false,index,item)">
														<text class="iconfont icon-ic_Reduce fs-32"></text>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-if="item.showInput" @tap.stop="toggleInput(item,true)">
														<input v-model="item.copy_cart_num" type="text" 
															maxlength="2" :focus="true" 
															@input="inputClick($event,item)"
															@confirm="setProCartNum($event,item)"
															class="w-48 text-center" />
													</view>
													<view class="fs-30 text--w111-333 px-20" v-else @tap.stop="toggleInput(item,false)">{{item.cart_num}}</view>
													<view class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff"
														@tap.stop="CartNumAdd(index,item)">
														<text class="iconfont icon-ic_increase fs-32"></text>
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<!-- 小图模板 -->
								<view v-else-if="showType == 4">
									<view class="mb-24 flex justify-between" v-for="(item,index) in tempArr"
										:key="index" @tap="goDetail(item)">
										<easy-loadimage 
											mode="aspectFit"
											:image-src="item.image" 
											:borderSrc="item.activity_frame.image"
											width="176rpx" 
											height="176rpx"
											borderRadius="16rpx"></easy-loadimage>
										<view class="flex-1 flex-col justify-between pl-20">
											<view class="w-full">
												<view class="line1 w-346 fs-28 text-#333 lh-40rpx">
													<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
												</view>
												<view class="flex items-end flex-wrap mt-12 w-full">
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
											<view class="flex-between-center">
												<view class="flex-y-center flex-wrap flex-1">
													<baseMoney :money="item.price" symbolSize="24" integerSize="40"
														decimalSize="24" weight>
													</baseMoney>
													<view class="svip-label ml-8" v-if="item.vip_price && item.vip_price > 0">
														<text class="text">SVIP</text>
														<text class="px-8 SemiBold">¥{{item.vip_price}}</text>
													</view>
													<text class="fs-22 text--w111-8e8e8e pl-12" v-else>已售{{item.sales}}</text>
												</view>
												<view class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff"
													@tap.stop="goCartDuo(item)" v-if="item.spec_type">
													<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
												</view>
												<view v-if="!item.spec_type && !item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff "
														@tap.stop="goCartDan(item,index)">
														<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
													</view>
												</view>
												<view class="flex-y-center" v-if="!item.spec_type && item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg--w111-f5f5f5 text--w111-333"
														:class="{'disabled-btn': item.min_qty && item.cart_num == item.min_qty}"
														@tap.stop="ChangeCartNumDan(false,index,item)">
														<text class="iconfont icon-ic_Reduce fs-32"></text>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-if="item.showInput" @tap.stop="toggleInput(item,true)">
														<input v-model="item.copy_cart_num" type="text" 
															maxlength="2" :focus="true" 
															@input="inputClick($event,item)"
															@confirm="setProCartNum($event,item)"
															class="w-48 text-center" />
													</view>
													<view class="fs-30 text--w111-333 px-20" v-else @tap.stop="toggleInput(item,false)">{{item.cart_num}}</view>
													<view class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff"
														@tap.stop="CartNumAdd(index,item)">
														<text class="iconfont icon-ic_increase fs-32"></text>
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<view v-if="!tempArr.length && !loading">
									<emptyPage title="暂无商品，去看点别的吧～" ></emptyPage>
								</view>
								<view class="white-box"></view>
							</scroll-view>
						</view>
					</view>
				</view>
			</view>
			<view class="w-710 h-96 cart_box" :class="isFooter == 1 ? 'show-footer' : ''">
				<view class="w-full h-full flex-between-center">
					<view class="flex-y-center" @tap="getCartList(0)">
						<view class="w-96 h-96 rd-50rpx bg-gradient1 flex-center relative ml--4">
							<image src="@/static/img/cart_pic.png" class="w-54 h-54"></image>
							<uni-badge class="badge-style" v-if="cartNum > 0" :text="cartNum"></uni-badge>
						</view>
						<view class="flex items-baseline ml-32">
							<baseMoney :money="totalPrice" symbolSize="32" integerSize="48" decimalSize="32"
								incolor="#fff" weight></baseMoney>
							<view class="fs-24 text--w111-fff lh-34rpx pl-16" v-show="totalPrice > 0">查看明细 <text
									class="iconfont icon-ic_uparrow fs-24"></text> </view>
						</view>
					</view>
					<view class="w-186 h-72 rd-40rpx flex-center text--w111-fff fs-26 fw-500 bg-gradient1 mr-12"
						@tap="subOrder">去结算({{cartNum}})</view>
				</view>
			</view>
			<view class="more_box abs-lt w-full bg--w111-fff rd-b-32rpx z-20" v-show="showCateDrawer">
				<view :style="{paddingTop: sysHeight + 'px'}">
					<view class="h-80 px-32 flex-y-center">
						<!-- #ifdef MP -->
						<view class="w-508 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32" @tap="goSearch">
						<!-- #endif -->
						<!-- #ifndef MP -->
						<view class="w-full h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32" @tap="goSearch">
						<!-- #endif -->
							<text class="iconfont icon-ic_search fs-28"></text>
							<text class="fs-24 text--w111-999 pl-18">请输入商品名称</text>
						</view>
					</view>
				</view>
				<view class="pt-32 pl-30 pr-30">
					<view>
						<view class="fs-32 text--w111-333" v-if="categoryErList.length">
							{{categoryErList[tabClick].cate_name}}
						</view>
						<view class="grid-column-4 grid-gap-24rpx mt-24">
							<view class="w-154 h-56 rd-30rpx flex-center fs-24 text--w111-333 bg--w111-f5f5f5"
								v-for="(item,index) in categoryErList" :key="index" @tap="longClick(index,item)"
								:class="index===tabClick?'cate_active':''">
								{{item.cate_name}}
							</view>
						</view>
					</view>
					<view class="flex-center fs-24 text--w111-999 h-80 mt-32" @tap="closeCateDrawer">
						<text>点击收起 <text class="iconfont icon-ic_uparrow fs-24 pl-4"></text> </text>
					</view>
				</view>
			</view>
			<view class="mask" v-show="showCateDrawer" @tap="closeCateDrawer"></view>
			<cartList :cartData="cartData" :isFooter='isFooter' @closeList="closeList"
				@ChangeCartNumDan="ChangeCartList" @ChangeSubDel="ChangeSubDel" @ChangeOneDel="ChangeOneDel">
			</cartList>
			<productWindow
				:attr="attr"
				:isShow='1'
				:iSplus='1'
				:iScart='1'
				:is_vip="is_vip"
				:type="2"
				:storeInfo="storeInfo"
				@myevent="onMyEvent"
				@ChangeAttr="ChangeAttr"
				@ChangeCartNum="ChangeCartNumDuo"
				@attrVal="attrVal"
				@iptCartNum="iptCartNum"
				@goCat="goCatNum"
				@getImg="showImg"
				id='product-window' ></productWindow>
			<cusPreviewImg ref="cusPreviewImg" :list="skuArr" @changeSwitch="changeSwitch"></cusPreviewImg>
		</view>
</template>

<script>
	let windowHeight = uni.getWindowInfo().windowHeight;
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import {
		getCategoryList,
		getProductslist,
		getAttr,
		postCartNum
	} from '@/api/store.js';
	import {
		vcartList,
		getCartCounts,
		cartDel,
		changeCartNum
	} from '@/api/order.js';
	import productWindow from '@/components/productWindow';
	import cartList from '@/components/cartList';
	import {
		mapState,
		mapGetters
	} from 'vuex';
	import {
		goShopDetail
	} from '@/libs/order.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import skuSelect from '@/mixins/skuSelect.js'
	import emptyPage from '@/components/emptyPage.vue';
	import cusPreviewImg from '@/components/cusPreviewImg';
	export default {
		props: {
			showType: {
				type: Number,
				default: 1
			},
			isFooter:{
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				windowHeight: windowHeight - 50,
				showCateDrawer: false,
				sysHeight: sysHeight,
				categoryList: [],
				navActive: 0,
				categoryTitle: '',
				categoryErList: [],
				tabLeft: 0,
				isWidth: 0, //每个导航栏占位
				tabClick: 0, //导航栏被点击
				iSlong: false,
				tempArr: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				page: 1,
				limit: 10,
				cid: 0, //一级分类
				sid: 0, //二级分类
				tid: 0, //三级分类
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				productValue: [],
				attrValue: '', //已选属性
				storeName: '', //多属性产品名称
				id: 0,
				cartData: {
					cartList: [],
					iScart: false
				},
				totalPrice: 0.00,
				lengthCart: 0,
				is_vip: 0, //是否是会员
				cart_num: 0,
				storeInfo: {},
				scrollHeight: 0,
				threeCateList: [],
				threeClick: 0,
				topNavShow: true,
				selectSku: {},
				skuArr: []
			}
		},
		components: {
			productWindow,
			cartList,
			emptyPage,
			cusPreviewImg
		},
		mixins: [skuSelect],
		computed: {
			...mapState({
				cartNum: state => state.indexData.cartNum
			}),
			...mapGetters(['isLogin', 'uid', 'cartNum'])
		},
		mounted() {
			this.getAllCategory();
			setTimeout(() => {
				this.getScrollHeight();
			}, 500)
			if(this.isLogin){
				this.getCartList(1);
			}
			uni.$on('newAttrNum', (data) => {
				if (data.length) {
					this.tempArr.forEach((item) => {
						data.forEach(j => {
							if (item.id == j.id) {
								item.cart_num = j.num
							}
						})
					})
				}
			})
			uni.$on('newCartNum', (data) => {
				if (data.length) {
					this.tempArr.forEach((item) => {
						data.forEach(j => {
							if (item.id == j.product_id) {
								item.cart_num = 0
							}
						})
					})
				}
			})
		},
		methods: {
			getScrollHeight() {
				let sysH = uni.getWindowInfo().statusBarHeight;
				this.scrollHeight = windowHeight - 88 - sysH;
			},
			getAllCategory() {
				let that = this;
				getCategoryList().then(res => {
					if (!res.data.length) return
					res.data.map(item=>{
						if(item.children && item.children.length){
							item.children.unshift({
								id:item.id,
								cate_name: '全部商品'
							})
						}
					})
					let data = res.data;
					that.categoryTitle = data[0].cate_name;
					that.cid = data[0].id;
					that.sid = 0;
					that.tid = 0;
					that.navActive = 0;
					that.tabClick = 0;
					that.categoryList = data;
					that.categoryErList = res.data[0].children ? res.data[0].children : [];
					that.page = 1;
					that.loadend = false;
					that.tempArr = [];
					that.productslist();
				})
			},
			// 产品列表
			productslist() {
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				that.loadTitle = '';
				getProductslist({
					page: that.page,
					limit: that.limit,
					type: 1,
					cid: that.cid,
					sid: that.sid,
					tid: that.tid,
					is_big: that.showType == 1 ? 1 : ''
				}).then(res => {
					res.data.map(item=>{
						this.$set(item,'copy_cart_num',item.cart_num);
					})
					let list = res.data,
						loadend = list.length < that.limit;
					that.tempArr = that.$util.SplitArray(list, that.tempArr);
					that.$set(that, 'tempArr', that.tempArr);
					that.loading = false;
					that.loadend = loadend;
					that.loadTitle = loadend ? "没有更多内容啦~" : "加载更多";
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多'
				});
			},
			tapNav(index, item) {
				uni.pageScrollTo({
					duration: 0,
					scrollTop: 0
				})
				let list = this.categoryList[index];
				this.navActive = index;
				this.categoryTitle = list.cate_name;
				this.categoryErList = item.children ? item.children : [];
				this.tabClick = 0;
				this.tabLeft = 0;
				this.cid = list.id;
				this.sid = 0;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			// 导航栏点击
			longClick(index, item) {
				this.tabClick = index; //设置导航点击了哪一个
				this.sid = this.categoryErList[index].id;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			deepClone(obj) {
				let newObj = Array.isArray(obj) ? [] : {}
				if (obj && typeof obj === "object") {
					for (let key in obj) {
						if (obj.hasOwnProperty(key)) {
							newObj[key] = (obj && typeof obj[key] === 'object') ? this.deepClone(obj[key]) : obj[key];
						}
					}
				}
				return newObj
			},
			haveAll(arr) {
				let cloneMap = this.deepClone(arr),
					list = [];
				cloneMap.forEach(item => {
					list.push(item.id);
				})
				return list.includes(0) ? true : false
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
			// 计算总价；
			getTotalPrice() {
				let that = this,
					list = that.cartData.cartList,
					totalPrice = 0.00;
				list.forEach(item => {
					if (item.attrStatus && item.status) {
						totalPrice = that.$util.$h.Add(totalPrice, that.$util.$h.Mul(item.cart_num, item
							.truePrice));
					}
				})
				that.$set(that, 'totalPrice', totalPrice);
			},
			getCartList(iSshow) {
				let that = this;
				vcartList().then(res => {
					that.$set(that.cartData, 'cartList', res.data);
					if (res.data.length) {
						that.$set(that.cartData, 'iScart', iSshow ? false : !that.cartData.iScart);
					} else {
						that.$set(that.cartData, 'iScart', false);
					}
					if (!res.data.length) {
						this.$store.commit('indexData/setCartNum', 0);
						this.tempArr.forEach((item) => {
							item.cart_num = 0
						})
					}
					that.getTotalPrice();
				})
			},
			getCartNum() {
				let that = this;
				getCartCounts().then(res => {
					this.$store.commit('indexData/setCartNum', res.data.count)
				});
			},
			CartNumAdd(index, item) {
				if (item.is_limit && item.cart_num >= item.limit_num) {
					this.$util.Tips({
						title: "购买最多不能超过" + item.limit_num
					});
				} else {
					this.ChangeCartNumDan(true, index, item);
				}
			},
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0).then(res => {
					// uni.hideLoading();
					that.$set(that.attr, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that, 'storeInfo', res.data.storeInfo);
					that.skuArr = [];
					for (let key in res.data.productValue) {
						let obj = res.data.productValue[key];
						that.skuArr.push(obj)
					}
					if (!that.skuArr.length) {
						that.skuArr = [{
							image: this.storeInfo.image,
							suk: this.storeInfo.store_name,
							price: this.storeInfo.price
						}];
					}
					this.$set(this, "selectSku", that.skuArr[0]);
					that.DefaultSelect();
				})
			},
			//点击sku图片打开轮播图
			showImg(index) {
				this.$refs.cusPreviewImg.open(this.selectSku.suk)
			},
			//滑动轮播图选择商品
			changeSwitch(e) {
				let productSelect = this.skuArr[e];
				this.$set(this, 'selectSku', productSelect);
				var skuList = productSelect.suk.split(',');
				skuList.forEach((i, index) => {
					this.$set(this.attr.productAttr[index], 'index', skuList[index]);
				})
				if (productSelect) {
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrValue', productSelect.suk);
					this.$set(this, 'attrTxt', '已选择');
				}
			},
			// 去详情页
			goDetail(item) {
				goShopDetail(item, this.uid).catch(res => {
					uni.navigateTo({
						url: `/pages/goods_details/index?id=${item.id}&fromType=1`
					});
				});
			},
			closeCateDrawer() {
				this.showCateDrawer = false;
			},
			openCateDrawer(type) {
				this.topNavShow = type;
				this.showCateDrawer = true;
			},
			lower(e) {
				this.productslist();
			},
			scroll(e) {
				uni.$emit('scroll');
			},
			goSearch() {
				uni.navigateTo({
					url: '/pages/goods/goods_search/index'
				})
			},
			toggleInput(item,type){
				if(type) return
				this.$set(item,'showInput',true);
			},
			inputClick(e,item) {
				const o = e.detail.value
				const inputRule = /[^\d]/g
				this.$nextTick(() => {
					item.copy_cart_num = o.replace(inputRule, '');
				})
			},
			setProCartNum(e,item){
				if (item.is_limit && item.copy_cart_num >= item.limit_num) {
					this.$util.Tips({
						title: "购买最多不能超过" + item.limit_num
					});
				} else {
					changeCartNum(item.product_id,item.copy_cart_num,2).then((res)=> {
						this.$util.Tips({
							title: res.msg
						});
						item.cart_num = item.copy_cart_num;
						item.showInput =false;
						this.getCartNum();
						this.getCartList(1);
					})
					.catch(err => {
						item.copy_cart_num = item.cart_num;
						return this.$util.Tips({
							title: err
						});
					});
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.scroll_box {
		overflow: hidden;
	}

	.aside_active {
		background-color: #fff;
		width: 100%;
		text-align: center;
		color: var(--view-theme);
		font-weight: 500;
		position: relative;

		&::after {
			content: '';
			width: 6rpx;
			height: 48rpx;
			background-color: var(--view-theme);
			position: absolute;
			left: 0;
			top: 50%;
			transform: translateY(-50%);
		}
	}

	.cate_active {
		color: var(--view-theme);
		background: var(--view-minorColorT);
		border: 1rpx solid var(--view-theme);
	}

	.cart_box {
		width: 710rpx;
		height: 96rpx;
		border-radius: 48rpx;
		background-color: rgba(0, 0, 0, 0.83);
		position: fixed;
		/* #ifdef H5 */
		bottom: 140rpx;
		/* #endif */
		/* #ifdef MP || APP-PLUS */
		bottom: 20px;
		/* #endif */
		left: 50%;
		transform: translateX(-50%);
		z-index: 101;
	}
	.picture-box{
		max-height: 382px;
		overflow-y: hidden;
	}
	.show-footer{
		/* #ifdef MP || APP-PLUS */
		bottom: calc(140rpx + env(safe-area-inset-bottom));
		/* #endif */
	}

	.jianbian {
		background: linear-gradient(90deg, #ff7931 0%, #e93323 100%);
	}

	.text-primary-con {
		color: var(--view-theme);
	}

	.bg-primary-light {
		background: var(--view-minorColorT);
	}

	.pl-30 {
		padding-left: 30rpx;
	}

	.con_border {
		border: 1px solid var(--view-theme);
	}

	.border_e {
		border: 1px solid #eee;
	}

	.active_pic {
		width: 104rpx;
		height: 104rpx;
		background-color: #fff;
		padding: 3rpx;
		border-radius: 50%;
		border: 3rpx solid var(--view-theme);

		image {
			width: 100%;
			height: 100%;
			border-radius: 50%;
		}
	}

	.scroll_pic {
		image {
			width: 92rpx;
			height: 92rpx;
			border-radius: 50%;
		}
	}

	.active_cate_text {
		background: linear-gradient(90deg, var(--view-gradient) 0, var(--view-theme) 100%);
		color: #fff;
		border-radius: 20rpx;
		margin-top: 8rpx;
	}

	.w-346 {
		width: 346rpx;
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
	.white-box{
		height: calc(300rpx + env(safe-area-inset-bottom));
	}
	.disabled-btn{
		color: #DEDEDE;
	}
</style>
