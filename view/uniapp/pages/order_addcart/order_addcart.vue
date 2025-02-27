<template>
	<!-- 购物车模块 -->
	<view :style="colorStyle">
		<view class="px-20">
			<view class="rd-24rpx  bg--w111-fff mt-20 pt-32" v-if="cartList.valid.length > 0">
				<view class="px-24 flex-between-center">
					<view class="flex-y-center">
						<text class=" text--w111-333 fs-24 lh-34rpx">共
						<text class="text-primary-con Regular fs-28 px-4">{{cartNum || 0}}</text>件宝贝</text>
					</view>
					<view class="flex-y-center">
						<view class="w-104 h-40 rd-20rpx flex-center bg-primary-light text-primary-con fs-24" @click="couponTap">优惠券</view>
						<text class="inline-block w-1 h-20  bg--w111-ccc mx-32"></text>
						<text class="fs-24  text--w111-333 lh-34rpx" v-if="cartList.valid.length > 0 || cartList.invalid.length > 0" @click="manage">{{ footerswitch ? '管理' : '取消'}}</text>
					</view>
				</view>
				<view class="mt-24 pl-24">
					<checkbox-group @change="checkboxChange">
						<view v-for="(j,jindex) in cartList.valid" :key="jindex">
							<view class="pr-24 flex-between-center mt-40" v-for="p in j.promotions" :key="p.id">
								<view class="flex-y-center ">
									<text
										class="max-120 inline-block h-28 rd-6rpx px-6 lh-28rpx text--w111-fff bg-color fs-20 text-center line1">{{p.title}}</text>
									<text class="w-422 text--w111-333 fs-26 pl-12 lh-36rpx line1">{{p.desc}}<text v-if="p.differ_threshold>0">还差{{p.differ_threshold}}{{p.threshold_type==1?'元':'件'}}</text> </text>
								</view>
								<view class="text--w111-333 fs-24 lh-34rpx" @click="goCollect(p)"> {{p.is_valid == 1?'去逛逛':'去凑单'}} <text
										class="iconfont icon-ic_rightarrow fs-24"></text>
								</view>
							</view>
							<tui-swipe-action :operateWidth="136"  @click="handlerButton"  v-for="(item,index) in j.valid" :key="index">
							  <template v-slot:content>
								<view class="flex-between-center py-28">
									<!-- #ifndef MP -->
									<checkbox :value="(item.id).toString()" :checked="item.checked"
										:disabled="(!item.attrStatus || item.is_gift?true:false) && footerswitch" />
									<!-- #endif -->
									<!-- #ifdef MP -->
									<checkbox :value="item.id" :checked="item.checked"
										:disabled="(!item.attrStatus || item.is_gift?true:false) && footerswitch" />
									<!-- #endif -->
									<view class="flex-1 ml-22 flex">
										<view class="w-200 h-200 rd-16rpx">
											<image class="w-200 h-200 rd-16rpx" v-if="item.productInfo.attrInfo" :src="item.productInfo.attrInfo.image"
												@tap="goPage(1,'/pages/goods_details/index?id=' + item.productInfo.id)" mode="aspectFit"></image>
											<image class="w-200 h-200 rd-16rpx" v-else :src="item.productInfo.image"
												@tap="goPage(1,'/pages/goods_details/index?id=' + item.productInfo.id)" mode="aspectFit"></image>
										</view>
										<view class="ml-20 flex-1 flex-col justify-between">
											<view class="w-full">
												<view class="w-382 line1 fs-28 fw-500  text--w111-333 lh-40rpx">{{item.productInfo.store_name}}</view>
												<view class="inline-block max-w-322 h-38 lh-38rpx mt-12  bg--w111-f5f5f5  text--w111-999 rd-20rpx px-12 text-center fs-22"
													v-if="item.productInfo.attrInfo && item.productInfo.spec_type && !item.is_gift && item.attrStatus"
													@click.stop="cartAttr(item)">
													<view class="flex">
														<text class="line1">{{item.productInfo.attrInfo.suk}}</text>
														<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
													</view>
												</view>
												<view class="inline-block max-w-322 h-38 lh-38rpx mt-12  bg--w111-f5f5f5  text--w111-999 rd-20rpx px-12 text-center fs-22"
													v-else>
													<view class="flex">
														<text class="line1">{{item.productInfo.attrInfo.suk}}</text>
														<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
													</view>
												</view>
												<view class="flex items-end flex-wrap mt-12 w-382">
													<BaseTag
														:text="label.label_name"
														:color="label.color"
														:background="label.bg_color"
														:borderColor="label.border_color"
														:circle="label.border_color ? true : false"
														:imgSrc="label.icon"
														v-for="(label, idx) in item.productInfo.store_label" :key="idx"></BaseTag>
												</view>
											</view>
											<view class="flex-between-center "
												:class="item.productInfo.store_label.length ? 'mt-12' : 'mt-50'"
												v-if="item.attrStatus && !item.is_gift">
												<view>
													<baseMoney :money="item.sum_price" symbolSize="24" integerSize="36" decimalSize="24"
													 weight></baseMoney>
												</view>
												<view class="flex-y-center pr-24 text--w111-333">
													<view class="flex-center w-48 h-48">
														<text class="iconfont icon-ic_Reduce fs-24" 
															:class="{'disabled-btn': item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty}"
															@click.stop='subCart(jindex,index)'></text>
													</view>
														<input type="number" maxlength="3" class="w-72 h-36 rd-4rpx bg--w111-f5f5f5 flex-center text-center fs-24 text--w111-333 mx-8"
														@input="setValue($event,item)" v-model="item.cart_num" />
													<view class="flex-center w-48 h-48">
														<text class="iconfont icon-ic_increase fs-24" @click.stop='addCart(jindex,index,item)'></text>
													</view>
												</view>
											</view>
											<view class="flex-between-center pr-24" v-if="!item.attrStatus">
												<text class="fs-24 lh-34rpx">请重新选择商品规格</text>
												<view class="w-96 h-48 rd-24rpx flex-center bg--w111-fff fs-24 font-num con_border" @click.stop="reElection(item)">重选</view>
											</view>
										</view>
									</view>
								</view>
							  </template>
							  <template v-slot:button>
							    	<view class="flex justify-end h-full">
							    		<view class="w-120 flex-center fs-24 text--w111-fff bg-collect" @tap="customBtn(0,item.product_id)">收藏</view>
							    		<view class="w-120 flex-center fs-24 text--w111-fff bg-color"
										:class="index == cartList.valid.length - 1? 'del_btn' : ''"
											@tap="customBtn(1,item.id)"
											>删除</view>
							    	</view>
							    </template>
							</tui-swipe-action>
						</view>
					</checkbox-group >
				</view>
			</view>
			<view class="pt-20" v-if="cartList.valid.length == 0">
				<emptyPage title="暂无商品，去加点别的吧～"></emptyPage>
			</view>
			<view class="rd-24rpx  bg--w111-fff mt-20 pt-32" v-if="cartList.invalid.length > 0">
				<view class="px-24 flex-between-center">
					<text class="fs-28  text--w111-333 lh-40rpx fw-500">失效商品(2)</text>
					<text class="fs-24 lh-28rpx  text--w111-999" @click='unsetCart'>一键清空</text>
				</view>
				<view class="mt-24 px-24">
					<view class="flex-between-center py-28" v-for="(item,ind) in cartList.invalid" :key='ind'>
						<text class="iconfont icon-ic_Disable"></text>
						<view class="flex-1 ml-22 flex">
							<view class="relative">
								<image class="w-200 h-200 rd-16rpx" v-if="item.productInfo.attrInfo" :src='item.productInfo.attrInfo.image' mode="aspectFit"></image>
								<image class="w-200 h-200 rd-16rpx" v-else :src='item.productInfo.image' mode="aspectFit"></image>
								<view class="over flex-center fs-24 text--w111-fff">失效</view>
							</view>
							<view class="ml-20">
								<view class="w-382 line1 fs-28 fw-500 text--w111-ccc lh-40rpx">{{item.productInfo.store_name}}</view>
								<view
									class="inline-block h-38 lh-38rpx mt-12  bg--w111-f5f5f5  text--w111-ccc rd-20rpx px-12 text-center fs-22">
									<view class="flex" v-if="item.productInfo.attrInfo">
										<text>{{item.productInfo.attrInfo.suk}}</text>
										<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
									</view>
								</view>
								<view class="flex my-20">
									<view
										class="h-26 lh-22rpx px-6 rd-4rpx fs-18 bg--w111-fff disabled-tag"  v-if="item.productInfo.freight == 1">
										包邮</view>
									<view
										class="h-26 lh-22rpx px-6 rd-4rpx fs-18 bg--w111-fff disabled-tag ml-8">
										7天无理由</view>
								</view>
								<view class="flex-between-center">
									<view>
										<baseMoney :money="item.sum_price" symbolSize="24" integerSize="36" decimalSize="24" color="#bbbbbb"
										 weight></baseMoney>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class='px-20'>
			<!-- 热门推荐显示 -->
			<recommend :hostProduct='hostProduct'></recommend>
		</view>
		<view v-if="cartList.valid.length == 0 && cartList.invalid.length == 0 && loadend"
			class="h-30"></view>
		<view v-else class="h-200"></view>
		<view class="tips acea-row row-middle" :class="isFooter?'':'on'" v-if="isTips"><text
				class="iconfont icon-tishi"></text>部分活动不能叠加，系统已自动为您计算最优惠的价格</view>
		<!-- 订单结算 -->
		<view class="fixed-lb fixed_bt w-full" :class="isFooter ? 'show-footer' : ''"
			v-if="cartList.valid.length">
			<view class="h-80 bg--w111-FFF0D1 flex-between-center mt-24 px-20"
				v-show="!discountInfo.discount"
				v-if="!discountInfo.svip_status && discountInfo.svip_price">
				<view class="flex-y-center">
					<image src="@/static/img/vip_leval.png" class="w-36 h-36"></image>
					<view class="pl-8">
						<text class="fs-24 text--w111-7E4B06">开通 SVIP会员 预计省</text>
						<text class="font-color Regular px-4 fs-28">¥{{discountInfo.svip_price}}</text>
						<text class="fs-24 text--w111-7E4B06">元</text>
					</view>
				</view>
				<view class="fs-24 text--w111-7E4B06" @click="goPage(1,'/pages/annex/vip_paid/index')">
					<text>立即开通</text>
					<text class="iconfont icon-ic_rightarrow fs-24"></text>
				</view>
			</view>
			<view class="h-96 pl-32 pr-20 flex-between-center bg--w111-fff">
				<checkbox-group @change="checkboxAllChange">
					<checkbox value="all" :checked="!!isAllSelect" />
					<text class="fs-26 text--w111-333 lh-36rpx">全选</text>
				</checkbox-group>
				<view class="flex-y-center" v-if="footerswitch==true && discountInfo.deduction">
					<view @tap="discountTap">
						<view class="lh-40rpx text--w111-333 fs-28">合计: <text class="fs-32 fw-600">￥{{selectValue.length?discountInfo.deduction.pay_price:0}}</text>
						</view>
						<view class="fs-22 text--w111-333 mt-6"
						 v-if="(Object.prototype.toString.call(discountInfo.coupon) === '[object Object]' || discountInfo.deduction.first_order_price || discountInfo.deduction.promotions_price) && selectValue.length">
						已优惠: ￥{{$util.$h.Sub(discountInfo.deduction.sum_price,discountInfo.deduction.pay_price)}} 查看详情
							<text class="iconfont icon-ic_uparrow fs-20"></text>
						</view>
					</view>
					<form @submit="subOrder">
						<button v-if="selectValue.length" class="w-186 h-72 rd-36rpx flex-center bg-color text--w111-fff fs-26 fw-500 ml-20" formType="submit"
						>{{Object.prototype.toString.call(discountInfo.coupon) === "[object Array]" || discountInfo.coupon.used?'去':'领券'}}结算({{selectValue.length}})</button>
						<button v-else class='w-186 h-72 rd-36rpx flex-center bg-color text--w111-fff fs-26 fw-500 ml-20' formType="submit"
						>{{Object.prototype.toString.call(discountInfo.coupon) === "[object Array]" || discountInfo.coupon.used?'去':'领券'}}结算({{selectValue.length}})</button>
					</form>
				</view>
				<view class="flex-y-center justify-end" v-else>
					<view class="w-144 h-56 rd-28rpx flex-center fs-24 fw-500 text--w111-333 bg--w111-fff border_ccc" @click="subCollect">收藏商品</view>
					<view class="w-96 h-56 rd-28rpx flex-center fs-24 fw-500 text-primary-con bg--w111-fff border_con ml-20" @click="subDel">删除</view>
				</view>
			</view>
		</view>
		<!-- 产品属性显示 -->
		<productWindow 
			:attr="attr" 
			:isShow='1' 
			:iSplus='1' 
			:iScart='1' 
			:showFooter="isFooter"
			:storeInfo="storeInfo" 
			:is_vip="is_vip"
			:type="2"
			:fangda="false"
			@myevent="onMyEvent" 
			@ChangeAttr="ChangeAttr" 
			@ChangeCartNum="ChangeCartNum" 
			@attrVal="attrVal"
			@iptCartNum="iptCartNum" 
			@goCat="reGoCat" id='product-window'></productWindow>
		<!-- 优惠明细显示 -->
		<cartDiscount :discountInfo="discountInfo" :showFooter="isFooter" @myevent="myDiscount"></cartDiscount>
		<view class="uni-p-b-98"></view>
		<pageFooter @newDataStatus="newDataStatus"></pageFooter>
		<!-- 优惠券列表弹框显示 -->
		<couponListWindow
			:coupon="coupon"
			:openType="0"
			@ChangCouponsClone="ChangCouponsClone"
			@ChangCouponsUseState="ChangCouponsUseState"
			@ChangCoupons="ChangCouponsClone"
			@tabCouponType="tabCouponType"
			@ruleToggle="ruleToggle"
			v-if="coupon">
		</couponListWindow>
	</view>
</template>

<script>
	// #ifdef APP-PLUS || MP
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	// #endif
	// #ifdef H5
	let sysHeight = 0
	// #endif
	import {
		getCartList,
		getCartCounts,
		changeCartNum,
		cartDel,
		getResetCart,
		cartCompute
	} from '@/api/order.js';
	import {
		setCouponReceive,
		getCoupons
	} from '@/api/api.js';
	import {
		getProductHot,
		collectAll,
		getProductDetail,
		getAttr,
	} from '@/api/store.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import recommend from '@/components/recommend';
	import productWindow from '@/components/productWindow';
	import cartDiscount from '@/components/cartDiscount';
	import couponListWindow from '@/components/couponListWindow';
	import pageFooter from '@/components/pageFooter/index.vue'
	import tuiSwipeAction from "@/components/tui-swipe-action/index.vue";
	import emptyPage from '@/components/emptyPage.vue';
	import colors from "@/mixins/color";
	import {HTTP_REQUEST_URL} from '@/config/app';
	import {Debounce} from '@/utils/validate.js'
	export default {
		components: {
			couponListWindow,
			pageFooter,
			recommend,
			productWindow,
			cartDiscount,
			tuiSwipeAction,
			emptyPage
		},
		mixins: [colors],
		data() {
			return {
				isFooter: false,
				isTips: false,
				//属性是否打开
				coupon: {
					coupon: false,
					type: -1,
					list: [],
					count: [],
					goFrom: 1
				},
				discountInfo: {
					discount: false,
					deduction: {},
					coupon: {},
					svip_price:0,
					svip_status:false
				},
				goodsHidden: true,
				footerswitch: true,
				hostProduct: [],
				cartList: {
					valid: [],
					invalid: []
				},
				isAllSelect: false, //全选
				selectValue: [], //选中的数据
				selectCountPrice: 0.00,
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				hotScroll: false,
				hotPage: 1,
				hotLimit: 10,
				loading: false,
				loadend: false,
				loadTitle: '没有更多内容啦~', //提示语
				page: 1,
				limit: 20,
				loadingInvalid: false,
				loadendInvalid: false,
				loadTitleInvalid: '加载更多', //提示语
				pageInvalid: 1,
				limitInvalid: 20,
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				productValue: [], //系统属性
				storeInfo: {},
				attrValue: '', //已选属性
				attrTxt: '请选择', //属性页面提示
				cartId: 0,
				product_id: 0,
				sysHeight: sysHeight,
				footerSee: false,
				isCart: 0,
				imgHost: HTTP_REQUEST_URL,
				is_vip: 0, //是否是会员
			};
		},
		computed: mapGetters(['isLogin', 'cartNum']),
		onLoad: function(options) {
			this.hotPage = 1;
			this.hostProduct = [],
			this.hotScroll = false,
			this.getHostProduct();
		},
		onShow: function() {
			uni.setStorageSync('form_type_cart', 1);
			uni.pageScrollTo({
				duration: 0,
				scrollTop: 0
			})
			if (this.isLogin == true) {
				this.resetData();
				this.getCartNum();
			} else {
				// toLogin()
			}
		},
		methods: {
			onLoadFun() {
				this.resetData();
			},
			resetData() {
				this.loadend = false;
				this.page = 1;
				this.cartList.valid = [];
				// 1:表示只有在onShow里面调用;
				this.getCartList();
				this.loadendInvalid = false;
				this.pageInvalid = 1;
				this.cartList.invalid = [];
				// this.getCartNum();
				this.goodsHidden = true;
				this.footerswitch = true;
				this.hotLimit = 10;
				this.isAllSelect = false; //全选
				this.selectValue = []; //选中的数据
				this.selectCountPrice = 0.00;
				this.isShowAuth = false;
			},
			newDataStatus(val) {
				this.isFooter = val ? true : false;
			},
			tabCouponType: function(type) {
				this.$set(this.coupon, 'type', type);
				this.getCouponList(type);
			},
			ChangCouponsUseState(index) {
				let that = this;
				that.coupon.list[index].is_use = true;
				that.$set(that.coupon, 'list', that.coupon.list);
				that.$set(that.coupon, 'coupon', false);
			},
			ChangCouponsClone: function() {
				this.$set(this.coupon, 'coupon', false);
			},
			ruleToggle(index){
				this.coupon.list[index].ruleshow = !this.coupon.list[index].ruleshow;
			},
			/**
			 * 获取优惠券
			 *
			 */
			getCouponList(type) {
				let that = this,
				obj = {
					page: 1,
					limit: 20,
					product_id: that.id ? that.id : '',
					type: type ? type : ''
				};
				getCoupons(obj).then(res => {
					that.$set(that.coupon, 'count', res.data.count);
					if (type === undefined || type === null) {
						let count = [...that.coupon.count],
							indexs = '';
						let index = count.findIndex(item => item);
						let delCount = that.coupon.count,
							newDelCount = [];
						let countIndex = 0;
						delCount.forEach((item, index) => {
							if (item === 0) {
								countIndex = index;
							} else {
								newDelCount.push(item)
							}
						});
						if (newDelCount.length == 3) {
							indexs = 2;
						} else if (newDelCount.length == 2) {
							if (countIndex === 2) {
								indexs = 1;
							} else {
								indexs = 2;
							}
						} else {
							indexs = delCount.findIndex(item => item === count[index]);
						}
						that.$set(that.coupon, 'type', indexs);
						that.getCouponList(indexs);
					} else {
						res.data.list.map(item=>{
							that.$set(item,'ruleshow',false);
						})
						that.$set(that.coupon, 'list', res.data.list);
					}
				});
			},
			/**
			 * 打开优惠券插件
			 */
			couponTap: function() {
				let that = this;
				that.getCouponList();
				that.$set(that.coupon, 'coupon', true);
			},
			goCollect(item) {
				uni.navigateTo({
					url: `/pages/goods/goods_list/index?sid=0&title=默认&promotions_type=${item.promotions_type}&promotions_id=${item.id}`
				})
			},
			myDiscount() {
				this.discountInfo.discount = false;
			},
			discountTap() {
				this.coupon.coupon = false;
				this.discountInfo.discount = !this.discountInfo.discount;
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e;
			},
			// 修改购物车
			reGoCat: function() {
				let that = this,
					productSelect = that.productValue[this.attrValue];
				//如果有属性,没有选择,提示用户选择
				if (
					that.attr.productAttr.length &&
					productSelect === undefined
				) {
					return that.$util.Tips({
						title: "产品库存不足，请选择其它"
					});
				}
				let q = {
					id: that.cartId,
					product_id: that.product_id,
					num: that.attr.productSelect.cart_num,
					unique: that.attr.productSelect !== undefined ?
						that.attr.productSelect.unique : ""
				};
				getResetCart(q)
					.then(function(res) {
						that.attr.cartAttr = false;
						that.$util.Tips({
							title: "添加购物车成功",
							success: () => {
								that.loadend = false;
								that.page = 1;
								that.cartList.valid = [];
								that.getCartList();
								that.getCartNum();
							}
						});
					})
					.catch(res => {
						return that.$util.Tips({
							title: res.msg
						});
					});
			},
			onMyEvent: function() {
				this.$set(this.attr, 'cartAttr', false);
			},
			// 点击切换属性
			cartAttr(item) {
				this.isCart = 1;
				this.getGoodsDetails(item);
			},
			// 重选
			reElection: function(item) {
				this.isCart = 0;
				this.getGoodsDetails(item)
			},
			/**
			 * 获取产品详情
			 *
			 */
			getGoodsDetails: function(item) {
				uni.showLoading({
					title: '加载中',
					mask: true
				});
				let that = this;
				that.cartId = item.id;
				that.product_id = item.product_id;
				getAttr(item.product_id, 0).then(res => {
					uni.hideLoading();
					that.attr.cartAttr = true;
					let storeInfo = res.data.storeInfo;
					that.$set(that, 'storeInfo', storeInfo);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that.attr, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.DefaultSelect();
				}).catch(err => {
					uni.hideLoading();
				})
			},
			/**
			 * 属性变动赋值
			 *
			 */
			ChangeAttr: function(res) {
				let productSelect = this.productValue[res];
				if (productSelect && productSelect.stock > 0) {
					this.$set(this.attr.productSelect, "image", productSelect.image);
					this.$set(this.attr.productSelect, "price", productSelect.price);
					this.$set(this.attr.productSelect, "stock", productSelect.stock);
					this.$set(this.attr.productSelect, "unique", productSelect.unique);
					this.$set(this.attr.productSelect, "cart_num", 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, "attrValue", res);
					this.$set(this, "attrTxt", "已选择");
				} else {
					this.$set(this.attr.productSelect, "image", this.storeInfo.image);
					this.$set(this.attr.productSelect, "price", this.storeInfo.price);
					this.$set(this.attr.productSelect, "stock", 0);
					this.$set(this.attr.productSelect, "unique", "");
					this.$set(this.attr.productSelect, "cart_num", 0);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, "attrValue", "");
					this.$set(this, "attrTxt", "请选择");
				}
			},
			/**
			 * 默认选中属性
			 *
			 */
			DefaultSelect: function() {
				let productAttr = this.attr.productAttr;
				let value = [],
					stock = 0,
					attrValue = [];
				for (var key in this.productValue) {
					if (this.productValue[key].stock > 0) {
						value = this.attr.productAttr.length ? key.split(",") : [];
						break;
					}
				}
				//isCart 1切换属性 0为重选
				if (this.isCart) {
					//购物车默认打开时，随着选中的属性改变
					// let attrValue = [];
					this.cartList.valid.forEach(j => {
						j.valid.forEach(item => {
							if (item.id == this.cartId) {
								attrValue = item.productInfo.attrInfo.suk.split(",");
							}
						})
					})
					let key = attrValue.join(",");
					stock = this.productValue[key].stock;
					for (let i = 0; i < productAttr.length; i++) {
						this.$set(productAttr[i], "index", stock ? attrValue[i] : value[i]);
					}
				} else {
					for (let i = 0; i < productAttr.length; i++) {
						this.$set(productAttr[i], "index", value[i]);
					}
				}

				//sort();排序函数:数字-英文-汉字；
				let productSelect = this.productValue[(this.isCart && stock) ? attrValue.join(",") : value.join(",")];
				if (productSelect && productAttr.length) {
					this.$set(
						this.attr.productSelect,
						"store_name",
						this.storeInfo.store_name
					);
					this.$set(this.attr.productSelect, "image", productSelect.image);
					this.$set(this.attr.productSelect, "price", productSelect.price);
					this.$set(this.attr.productSelect, "stock", productSelect.stock);
					this.$set(this.attr.productSelect, "unique", productSelect.unique);
					this.$set(this.attr.productSelect, "cart_num", 1);
					this.$set(this, "attrValue", value.join(","));
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, "attrTxt", "已选择");
				} else if (!productSelect && productAttr.length) {
					this.$set(
						this.attr.productSelect,
						"store_name",
						this.storeInfo.store_name
					);
					this.$set(this.attr.productSelect, "image", this.storeInfo.image);
					this.$set(this.attr.productSelect, "price", this.storeInfo.price);
					this.$set(this.attr.productSelect, "stock", 0);
					this.$set(this.attr.productSelect, "unique", "");
					this.$set(this.attr.productSelect, "cart_num", 0);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, "attrValue", "");
					this.$set(this, "attrTxt", "请选择");
				} else if (!productSelect && !productAttr.length) {
					this.$set(
						this.attr.productSelect,
						"store_name",
						this.storeInfo.store_name
					);
					this.$set(this.attr.productSelect, "image", this.storeInfo.image);
					this.$set(this.attr.productSelect, "price", this.storeInfo.price);
					this.$set(this.attr.productSelect, "stock", this.storeInfo.stock);
					this.$set(
						this.attr.productSelect,
						"unique",
						this.storeInfo.unique || ""
					);
					this.$set(this.attr.productSelect, "cart_num", 1);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, "attrValue", "");
					this.$set(this, "attrTxt", "请选择");
				}
			},
			attrVal(val) {
				this.$set(this.attr.productAttr[val.indexw], 'index', this.attr.productAttr[val.indexw].attr_values[val
					.indexn]);
			},
			/**
			 * 购物车数量加和数量减
			 *
			 */
			ChangeCartNum: function(changeValue) {
				//changeValue:是否 加|减
				//获取当前变动属性
				let productSelect = this.productValue[this.attrValue];
				//如果没有属性,赋值给商品默认库存
				if (productSelect === undefined && !this.attr.productAttr.length)
					productSelect = this.attr.productSelect;
				//无属性值即库存为0；不存在加减；
				if (productSelect === undefined) return;
				let stock = productSelect.stock || 0;
				let num = this.attr.productSelect;
				if (changeValue) {
					num.cart_num++;
					if (num.cart_num > stock) {
						this.$set(this.attr.productSelect, "cart_num", stock ? stock : 1);
						this.$set(this, "cart_num", stock ? stock : 1);
					}
				} else {
					num.cart_num--;
					if (num.cart_num < 1) {
						this.$set(this.attr.productSelect, "cart_num", 1);
						this.$set(this, "cart_num", 1);
					}
				}
			},
			/**
			 * 购物车手动填写
			 *
			 */
			iptCartNum: function(e) {
				this.$set(this.attr.productSelect, 'cart_num', e);
			},
			subDel: function(event) {
				let that = this,
					selectValue = that.selectValue;
				if (selectValue.length > 0)
					cartDel(selectValue).then(res => {
						that.loadend = false;
						that.page = 1;
						that.cartList.valid = [];
						that.getCartList();
						that.getCartNum();
					});
				else
					return that.$util.Tips({
						title: '请选择产品'
					});
			},
			getSelectValueProductId: function() {
				let that = this;
				let validList = that.cartList.valid;
				let selectValue = that.selectValue;
				let productId = [];
				if (selectValue.length > 0) {
					for (let j in validList) {
						for (let index in validList[j].valid) {
							if (that.inArray(validList[j].valid[index].id, selectValue)) {
								productId.push(validList[j].valid[index].product_id);
							}
						}
					}
				};
				return productId;
			},
			subCollect: function(event) {
				let that = this,
					selectValue = that.selectValue;
				if (selectValue.length > 0) {
					let selectValueProductId = that.getSelectValueProductId();
					collectAll(that.getSelectValueProductId().join(',')).then(res => {
						return that.$util.Tips({
							title: res.msg,
							icon: 'success'
						});
					}).catch(err => {
						return that.$util.Tips({
							title: err
						});
					});
				} else {
					return that.$util.Tips({
						title: '请选择产品'
					});
				}
			},
			subOrder(event) {
				let that = this,
					selectValue = that.selectValue;
				if (selectValue.length > 0) {
					let coupon = this.discountInfo.coupon;
					if (Object.prototype.toString.call(coupon) === '[object Object]' && !coupon.used) {
						setCouponReceive(this.discountInfo.coupon.id).then(res => {
							uni.navigateTo({
								url: '/pages/goods/order_confirm/index?cartId=' + selectValue.join(',') +
									'&couponId=' + res.data.id + '&couponTitle=' + coupon.coupon_title
							});
						}).catch(err => {
							return that.$util.Tips({
								title: err
							});
						})
					} else {
						let url = '';
						if (Object.prototype.toString.call(coupon) === '[object Array]') {
							url = '/pages/goods/order_confirm/index?cartId=' + selectValue.join(',')
						} else {
							url = '/pages/goods/order_confirm/index?cartId=' + selectValue.join(',') + '&couponId=' +
								coupon.used.id + '&couponTitle=' + coupon.coupon_title
						}
						uni.navigateTo({
							url: url
						});
					}
				} else {
					return that.$util.Tips({
						title: '请选择产品'
					});
				}
			},
			checkboxAllChange: function(event) {
				let value = event.detail.value;
				if (value.length > 0) {
					this.setAllSelectValue(1)
				} else {
					this.setAllSelectValue(0)
				}
			},
			setAllSelectValue: function(status) {
				let that = this;
				let selectValue = [];
				let valid = that.cartList.valid;
				if (valid.length > 0) {
					valid.forEach(j => {
						j.valid.forEach(item => {
							if (status) {
								if (that.footerswitch) {
									if (item.attrStatus && !item.is_gift) {
										item.checked = true;
										selectValue.push(item.id);
									} else {
										item.checked = false;
									}
								} else {
									item.checked = true;
									selectValue.push(item.id);
								}
								that.isAllSelect = true;
							} else {
								item.checked = false;
								that.isAllSelect = false;
							}
						})
					})
					that.$set(that.cartList, 'valid', valid);
					that.selectValue = selectValue;
					that.switchSelect();
				}
			},
			checkboxChange: function(event) {
				let that = this;
				let value = event.detail.value;
				let valid = that.cartList.valid;
				let arr1 = [];
				let arr2 = [];
				let arr3 = [];
				let len = 0;
				valid.forEach(j => {
					j.valid.forEach(item => {
						len = len + 1;
						if (that.inArray(item.id, value)) {
							if (that.footerswitch) {
								if (item.attrStatus && !item.is_gift) {
									item.checked = true;
									arr1.push(item);
								} else {
									item.checked = false;
								}
							} else {
								item.checked = true;
								arr1.push(item);
							}
						} else {
							item.checked = false;
							arr2.push(item);
						}
					})
				})
				if (that.footerswitch) {
					arr3 = arr2.filter(item => !item.attrStatus || item.is_gift);
				}
				that.$set(that.cartList, 'valid', valid);
				that.isAllSelect = len === arr1.length + arr3.length;
				that.selectValue = value;
				that.switchSelect();
			},
			inArray: function(search, array) {
				for (let i in array) {
					if (array[i] == search) {
						return true;
					}
				}
				return false;
			},
			switchSelect: function() {
				let that = this;
				let validList = that.cartList.valid;
				let selectValue = that.selectValue;
				let selectCountPrice = 0.00;
				let cartId = [];
				if (selectValue.length < 1) {
					that.selectCountPrice = selectCountPrice;
				} else {
					for (let j in validList) {
						for (let index in validList[j].valid) {
							if (that.inArray(validList[j].valid[index].id, selectValue)) {
								cartId.push(validList[j].valid[index].id)
								selectCountPrice = that.$util.$h.Add(selectCountPrice, that.$util.$h.Mul(validList[j]
									.valid[index]
									.cart_num, validList[j].valid[
										index].truePrice))
							}
						}
					}
					that.selectCountPrice = selectCountPrice;
				}
				let data = {
					cartId: cartId.join(',')
				}
				if (cartId.length) {
					this.getCartCompute(data);
				}
			},
			setValue: Debounce(function(e,item){
				let num = e.detail.value, that = this;
				if (item.productInfo.limit_num > 0 && num > item.productInfo.limit_num) {
					item.cart_num = item.productInfo.limit_num;
					return this.$util.Tips({
						title: '购物车数量不能大于限购数量'
					});
				}

				that.setCartNum(item.id, item.cart_num, function(data) {
					that.getCartNum();
					that.loadend = false;
					that.loading = false;
					that.page = 1;
					that.getCartList('addCart');
				});
			}),
			subCart: Debounce(function(jindex, index) {
				let that = this;
				let status = false;
				let item = that.cartList.valid[jindex].valid[index];
				// 开启起购的话做一下不让减少的限制
				if(item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty) return
				item.cart_num = Number(item.cart_num) - 1;
				if (item.cart_num < 1) status = true;
				if (item.cart_num <= 1) {
					item.cart_num = 1;
					item.numSub = true;
				} else {
					item.numSub = false;
					item.numAdd = false;
				}
				if (false == status) {
					that.setCartNum(item.id, item.cart_num, function(data) {
						that.cartList.valid[jindex].valid[index] = item;
						that.getCartNum();
						that.loadend = false;
						that.page = 1;
						that.getCartList('subCart');
					});
				}
			}),
			addCart: Debounce(function(jindex, index, obj) {
				let that = this;
				let item = that.cartList.valid[jindex].valid[index];
				if (obj.numAdd || (obj.productInfo.limit_num > 0 && obj.cart_num >= obj.productInfo.limit_num)) {
					item.cart_num = item.productInfo.limit_num;
					return this.$util.Tips({
						title: '购物车数量不能大于限购数量'
					});
				}

				item.cart_num = Number(item.cart_num) + 1;
				let productInfo = item.productInfo;
				if (productInfo.hasOwnProperty('attrInfo') && item.cart_num >= item.productInfo.attrInfo.stock) {
					item.cart_num = item.productInfo.attrInfo.stock;
					item.numAdd = true;
					item.numSub = false;
				} else {
					item.numAdd = false;
					item.numSub = false;
				}
				that.setCartNum(item.id, item.cart_num, function(data) {
					that.cartList.valid[jindex].valid[index] = item;
					that.getCartNum();
					that.loadend = false;
					that.page = 1;
					that.getCartList('addCart');
				});
			}),
			setCartNum(cartId, cartNum, successCallback) {
				let that = this;
				changeCartNum(cartId, cartNum).then(res => {
					successCallback && successCallback(res.data);
				});
			},
			getCartNum: function() {
				let that = this;
				getCartCounts(0).then(res => {
					this.$store.commit('indexData/setCartNum', res.data.count)
					if (res.data.count > 0) {
						wx.setTabBarBadge({
							index: 3,
							text: res.data.count + ''
						})
					} else {
						wx.hideTabBarRedDot({
							index: 3
						})
					}

				});
			},
			// 购物车计算
			getCartCompute(cartId) {
				cartCompute(cartId).then(res => {
					this.discountInfo.coupon = res.data.coupon;
					this.discountInfo.deduction = res.data.deduction;
					this.discountInfo.svip_price = res.data.svip_price;
					this.discountInfo.svip_status = res.data.svip_status;
				}).catch(err => {
					this.$util.Tips({
						title: err
					})
				})
			},
			getCartList: function(handle) {
				let that = this;
				if (this.loadend) return false;
				if (this.loading) return false;
				let data = {
					page: that.page,
					limit: that.limit,
					status: 1
				}
				getCartList(data).then(res => {
					this.getInvalidList();
					// this.discountInfo.deduction = res.data.deduction;
					// this.discountInfo.coupon = res.data.coupon;
					this.isTips = false;
					let cartList = res.data.valid;
					let valid = cartList.map(x => {
						return {
							valid: x.cart,
							promotions: x.promotions
						}
					})
					let loadend = valid.length < that.limit;
					// let validList = that.$util.SplitArray(valid, that.cartList.valid);
					let validList = valid;
					let numSub = [{
						numSub: true
					}, {
						numSub: false
					}];
					let numAdd = [{
							numAdd: true
						}, {
							numAdd: false
						}],
						selectValue = [];
					if (validList.length > 0) {
						for (let j in validList) {
							if (validList[j].promotions.length > 1) {
								that.isTips = true;
							}
							for (let index in validList[j].valid) {
								if (validList[j].valid[index].cart_num == 1) {
									validList[j].valid[index].numSub = true;
								} else {
									validList[j].valid[index].numSub = false;
								}
								let productInfo = validList[j].valid[index].productInfo;
								if (productInfo.hasOwnProperty('attrInfo') && validList[j].valid[index]
									.cart_num == validList[j].valid[index].productInfo.attrInfo.stock) {
									validList[j].valid[index].numAdd = true;
								} else if (validList[j].valid[index].cart_num == validList[j].valid[index]
									.productInfo.stock) {
									validList[j].valid[index].numAdd = true;
								} else {
									validList[j].valid[index].numAdd = false;
								}
								if (validList[j].valid[index].attrStatus && !validList[j].valid[index]
									.is_gift) {
									if (['addCart', 'subCart'].includes(handle)) {
										validList[j].valid[index].checked = false;
										for (let k = 0; k < that.selectValue.length; k++) {
											if (that.selectValue[k] == validList[j].valid[index].id) {
												validList[j].valid[index].checked = true;
												break;
											}
										}
										if (validList[j].valid[index].checked) {
											selectValue.push(validList[j].valid[index].id);
										}
									} else {
										validList[j].valid[index].checked = true;
										selectValue.push(validList[j].valid[index].id);
									}
								} else if (!this.footerswitch) {
									validList[j].valid[index].checked = true;
								} else {
									validList[j].valid[index].checked = false;
								}
							}
						}
					}

					that.$set(that.cartList, 'valid', res.data.valid.length ? validList : []);
					that.loadend = true;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page = that.page + 1;
					that.loading = false;
					// that.goodsHidden = cartList.valid.length <= 0 ? false : true;
					that.selectValue = selectValue;
					let newArr = [],
						newAttr = [];
					validList.forEach(j => {
						j.valid.forEach(item => {
							if (item.attrStatus && !item.is_gift) {
								newArr.push(item)
							}
							let obj = {
								id: item.product_id,
								num: item.cart_num
							}
							newAttr.push(obj)
						})
					})
					that.isAllSelect = newArr.length == selectValue.length && newArr.length;
					that.switchSelect();
					uni.$emit('newAttrNum', newAttr)
				}).catch(function(err) {
					that.loading = false;
					that.loadTitle = '加载失败';
					that.$util.Tips({
						title: err
					})
				})
			},
			getInvalidList: function() {
				let that = this;
				if (this.loadendInvalid) return false;
				if (this.loadingInvalid) return false;
				let data = {
					page: that.pageInvalid,
					limit: that.limitInvalid,
					status: 0
				}
				getCartList(data).then(res => {
					let cartList = res.data,
						invalid = cartList.invalid,
						loadendInvalid = invalid.length < that.limitInvalid;
					// let invalidList = that.$util.SplitArray(invalid, that.cartList.invalid);
					let invalidList = invalid;
					that.$set(that.cartList, 'invalid', invalidList);
					that.loadendInvalid = loadendInvalid;
					that.loadTitleInvalid = loadendInvalid ? '没有更多内容啦~' : '加载更多';
					that.pageInvalid = that.pageInvalid + 1;
					that.loadingInvalid = false;
				}).catch(res => {
					that.loadingInvalid = false;
					that.loadTitleInvalid = '加载更多';
				})

			},
			getHostProduct: function() {
				let that = this;
				if (that.hotScroll) return
				getProductHot(
					that.hotPage,
					that.hotLimit,
				).then(res => {
					that.hotPage++
					that.hotScroll = res.data.length < that.hotLimit
					that.hostProduct = that.hostProduct.concat(res.data)
				});
			},
			goodsOpen: function() {
				let that = this;
				that.goodsHidden = !that.goodsHidden;
			},
			manage: function() {
				let that = this;
				that.footerswitch = !that.footerswitch;
				let arr1 = [];
				let arr2 = [];
				let len = 0;
				that.cartList.valid.forEach(j => {
					j.valid.forEach(item => {
						len = len + 1;
						if (that.footerswitch) {
							if (item.attrStatus && !item.is_gift) {
								if (item.checked) {
									arr1.push(item.id);
								}
							} else {
								item.checked = false;
								arr2.push(item);
							}
						} else {
							if (item.checked) {
								arr1.push(item.id);
							}
						}
					})
				})
				if (that.footerswitch) {
					that.isAllSelect = len === arr1.length + arr2.length;
				} else {
					that.isAllSelect = len === arr1.length;
				}
				that.selectValue = arr1;
				if (that.footerswitch) {
					that.switchSelect();
				}
			},
			unsetCart: function() {
				let that = this,
					ids = [];
				for (let i = 0, len = that.cartList.invalid.length; i < len; i++) {
					ids.push(that.cartList.invalid[i].id);
				}
				cartDel(ids).then(res => {
					that.$util.Tips({
						title: '清除成功'
					});
					that.$set(that.cartList, 'invalid', []);
					that.getCartNum();
				}).catch(res => {

				});
			},
			customBtn(type,id){
				if(type == 0){
					collectAll(id).then(res => {
						return this.$util.Tips({
							title: res.msg,
							icon: 'success'
						});
					}).catch(err => {
						return this.$util.Tips({
							title: err
						});
					});
				}else{
					cartDel(id).then(res => {
						this.loadend = false;
						this.page = 1;
						this.cartList.valid = [];
						this.getCartList();
						this.getCartNum();
					});
				}

			},
			goPage(type, url){
				if(type == 1){
					uni.navigateTo({
						url
					})
				}else if(type == 2){
					uni.switchTab({
						url
					})
				}else if(type == 3){
					uni.navigateBack();
				}

			}
		},
		onPageScroll(e) {
			uni.$emit('scroll');
		},
		onReachBottom() {
			let that = this;
			that.getHostProduct();
		},
		onPullDownRefresh() {
			if (this.isLogin) {
				this.resetData();
			}
			setTimeout(()=> {
				uni.stopPullDownRefresh();
			}, 1000);
		},
	}
</script>
<style scoped lang="scss">
	.text-primary-con {
		color: var(--view-theme);
	}

	.bg-primary-light {
		background: var(--view-minorColorT);
	}

	.fixed_bt {
		/* #ifdef H5 */
		bottom: calc(94rpx + env(safe-area-inset-bottom));
		/* #endif */
		/* #ifdef MP || APP-PLUS */
		bottom: 0 ;
		/* #endif */
		z-index: 999;
	}
	.max-120{
		max-width: 120rpx;
	}

	.tips {
		position: fixed;
		z-index: 9;
		width: 100%;
		height: 56rpx;
		background: #FEF4E7;
		color: #FE960F;
		font-size: 24rpx;
		padding: 0 20rpx;
		box-sizing: border-box;
		bottom: 192rpx;
		bottom: calc(192rpx + constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		bottom: calc(192rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/

		.iconfont {
			margin-right: 12rpx;
		}

		&.on {
			// #ifndef H5
			bottom: 96rpx;
			// #endif
		}
	}
	.bg-collect{
		background:var(--view-bntColor);
	}
	.del_btn{
		border-radius:0 0 24rpx 0;
	}
	.border_ccc {
	  border: 1px solid #ccc;
	}
	.border_con {
	  border: 1px solid var(--view-theme);
	}

	.uni-p-b-96 {
		height: 96rpx;
	}
	.w-322{
		width: 322rpx;
	}
	.max-w-322{
		max-width: 322rpx;
	}
	.con_border{
		border: 1rpx solid var(--view-theme);
	}
	.disabled-tag{
		border: 1px solid rgba(255, 125, 0, .3);
		color: rgba(255, 125, 0, .3);
	}
	.icon-ic_Disable{
		color: #ddd;
		font-size: 37rpx;
	}
	.disabled-btn{
		color: #DEDEDE;
	}
	.over{
		width:104rpx;
		height:104rpx;
		border-radius: 50%;
		background-color: rgba(51, 51, 51, 0.6);
		position: absolute;
		top:50%;
		left:50%;
		transform: translate(-50%,-50%);
	}
	.show-footer{
		/* #ifdef MP || APP-PLUS */
		bottom: calc(94rpx + env(safe-area-inset-bottom));
		/* #endif */
	}
</style>
