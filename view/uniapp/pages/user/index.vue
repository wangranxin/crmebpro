<template>
	<!-- 个人中心模块 -->
	<view class="user-page" :style="colorStyle">
		<template v-if="isObjectData(diyData)">
			<user-member :userInfo="userInfo" :memberData="diyData.member" :orderAdminData="orderAdminData" :balanceStatus="balanceStatus" :isScrolling="isScrolling"></user-member>
			<user-order :orderMenu="orderMenu" :orderAdminData="orderAdminData" :userInfo="userInfo" :memberData="diyData.member" :orderData="diyData.order"></user-order>
			<user-order-static
				v-if="isObjectData(orderAdminData) && orderAdminData.order.user_order"
				:orderAdminData="orderAdminData.order"
				:orderStaticData="diyData.orderStatic"
			></user-order-static>
			<user-poster :posterData="diyData.poster"></user-poster>
			<user-menu :menuData="diyData.menu" :routineContact="routineContact"></user-menu>
			<user-menu v-if="storeMenuShow" :menuData="diyData.merMenu"></user-menu>
			<view class="copy_right pb-20">
				<template v-if="copyRightText">
					<image :src="copyRightImg" mode="aspectFill" class="copyRightImg"></image>
					<view class="copyRightText">
						{{ copyRightText }}
					</view>
				</template>
				<image v-else :src="copyRightImg" mode="aspectFill" class="support"></image>
			</view>
		</template>
		<view class="mark" v-show="isextension"></view>
		<pageFooter :style="colorStyle"></pageFooter>
		<ewcomerPop v-if="isComerGift" :comerGift="comerGift" @comerPop="comerPop"></ewcomerPop>
		<!-- #ifdef MP -->
		<editUserModal :isShow="editModal" @closeEdit="closeEdit" @editSuccess="editSuccess"></editUserModal>
		<!-- #endif -->
	</view>
</template>
<script>
import { storeCardApi } from '@/api/store.js';
import { newcomerGift } from '@/api/activity.js';
import { copyRight } from '@/api/api.js';
import { getMenuList, getUserInfo, setVisit, updateUserInfo, getRandCode, updateWechatInfo, getMenuData } from '@/api/user.js';
import { wechatAuthV2, silenceAuth } from '@/api/public.js';
import { toLogin } from '@/libs/login.js';
import { mapState, mapGetters } from 'vuex';
// #ifdef H5
import Auth from '@/libs/wechat';
// #endif
import { HTTP_REQUEST_URL } from '@/config/app';
const app = getApp();
import ewcomerPop from '@/components/ewcomerPop/index.vue';
import pageFooter from '@/components/pageFooter/index.vue';
import dayjs from '@/plugin/dayjs/dayjs.min.js';
import Routine from '@/libs/routine';
import colors from '@/mixins/color';
// #ifdef MP
import editUserModal from '@/components/eidtUserModal/index.vue';
// #endif
import userMember from './components/member/index.vue';
import userOrder from './components/order/index.vue';
import userOrderStatic from './components/order_static/index.vue';
import userMenu from './components/menus/index.vue';
import userPoster from './components/poster/index.vue';

export default {
	components: {
		userMember,
		userOrder,
		userOrderStatic,
		userMenu,
		userPoster,
		pageFooter,
		ewcomerPop,
		// #ifdef MP
		editUserModal
		// #endif
	},
	computed: {
		...mapGetters({
			cartNum: 'cartNum',
			isLogin: 'isLogin'
		})
	},
	filters: {},
	mixins: [colors],
	provide() {
		return {
			tapQrCode: this.tapQrCode,
			bindPhone: this.bindPhone,
			intoPage: this.intoPage,
			goMenuPage: this.goMenuPage,
			getMenuData: this.getMenuData,
			goEdit: this.goEdit
		};
	},
	data() {
		return {
			diyData: {},
			orderAdminData: {},
			// #ifdef MP
			getHeight: this.$util.getWXStatusHeight(),
			// #endif
			vipStatus: 0,
			stu: false,
			orderMenu: [
				{
					icon: '',
					title: '待付款',
					url: '/pages/goods/order_list/index?status=0'
				},
				{
					icon: '',
					title: '待发货',
					url: '/pages/goods/order_list/index?status=1'
				},
				{
					icon: '',
					title: '待收货',
					url: '/pages/goods/order_list/index?status=2'
				},
				{
					icon: '',
					title: '待评价',
					url: '/pages/goods/order_list/index?status=3'
				},
				{
					icon: '',
					title: '售后/退款',
					url: '/pages/users/user_return_list/index'
				}
			],
			imgUrls: [],
			orderStatusNum: {},
			userInfo: {
				pay_vip_status: 0,
				avatar: '',
				uid: '',
				nickname: '',
				level: '',
				phone: '',
				service_num: null,
				level_status: 0,
				is_promoter: 0
			},
			storeMenuShow: false, // 商家管理是否显示
			showStatus: 1,
			// #ifdef H5
			isWeixin: Auth.isWeixin(),
			//#endif
			footerSee: false,
			member_style: 1,
			my_banner_status: 1,
			menu_status: 1,
			service_status: 1,
			newcomer_status: 1,
			codeIndex: 0,
			config: {
				bar: {
					code: '',
					color: ['#000'],
					bgColor: '#FFFFFF', // 背景色
					width: 480, // 宽度
					height: 110 // 高度
				},
				qrc: {
					code: '',
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
			isCode: false,
			isextension: false,
			extension: {
				code: '',
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
			},
			imgHost: HTTP_REQUEST_URL,
			copyRightText: '',
			copyRightImg: '',
			giftPic: '',
			vip_type: 1,
			newcomer_style: 1,
			newList: [],
			newBg: '',
			comerGift: {},
			isComerGift: false,
			memberStatus: 0,
			balanceStatus: 0, // 余额是否展示
			editModal: false, // 编辑头像信息
			isScrolling: false,
			orderStyle: {
				1: ['icon-ic_daifukuan12', 'icon-ic_daifahuo11', 'icon-ic_daishouhuo1', 'icon-ic_daipingjia1', 'icon-ic_daituikuan1'],
				2: ['icon-ic_daifukuan2', 'icon-ic_daifahuo2', 'icon-ic_daishouhuo2', 'icon-ic_daipingji2', 'icon-ic_daituikuan2'],
				3: ['icon-ic_daifukuan', 'icon-ic_daifahuo', 'icon-ic_daishouhuo', 'icon-ic_daipingjia', 'icon-ic_daituikuan']
			},
			routineContact: 0
		};
	},
	onShow() {
		if (this.cartNum > 0) {
			uni.setTabBarBadge({
				index: 3,
				text: this.cartNum > 99 ? '99+' : this.cartNum + ''
			});
		} else {
			uni.hideTabBarRedDot({
				index: 3
			});
		}
		this.copyRightText = uni.getStorageSync('copyNameInfo');
		this.copyRightImg = uni.getStorageSync('copyImageInfo');
		uni.removeStorageSync('form_type_cart');
		if (!this.copyRightText) {
			this.getCopyRight();
		}
		if (this.isLogin) {
			this.getUserInfo();
			this.getMyMenus();
			this.setVisit();
			this.getMenuData();
		} else {
			// #ifdef H5
			let allPages = getCurrentPages(); //获取当前页面栈的实例；
			let lastPages = allPages.length - 1; // 获得倒数第二个元素的索引；
			let option = allPages[lastPages].options; // 获得上个页面传递的参数；
			let WX_AUTH = this.$Cache.get('WX_AUTH');
			if (Auth.isWeixin() && option.code) {
				//微信公众号静默授权
				if (WX_AUTH) {
					this.wechatAuthoize(option.code);
				} else {
					uni.navigateTo({
						url: '/pages/users/wechat_login/index'
					});
				}
			} else {
				this.getMyMenus();
			}
			// #endif
			// #ifdef MP || APP-PLUS
			this.getMyMenus();
			// #endif
		}
	},
	// #ifdef MP || APP-PLUS
	onPageScroll(e) {
		if (e.scrollTop > 50) {
			this.isScrolling = true;
		} else if (e.scrollTop < 50) {
			this.isScrolling = false;
		}
	},
	// #endif
	methods: {
		isObjectData(obj) {
			return Object.keys(obj).length !== 0;
		},
		// #ifdef MP
		editSuccess() {
			this.editModal = false;
			this.getUserInfo();
		},
		closeEdit() {
			this.editModal = false;
		},
		// #endif
		getCopyRight() {
			copyRight()
				.then((res) => {
					let data = res.data;
					if (!data.copyrightContext && !data.copyrightImage) {
						data.copyrightImage = HTTP_REQUEST_URL + '/statics/images/product/support.png'
					}
					uni.setStorageSync('copyNameInfo', data.copyrightContext);
					uni.setStorageSync('copyImageInfo', data.copyrightImage);
					this.copyRightText = data.copyrightContext;
					this.copyRightImg = data.copyrightImage;
				})
				.catch((err) => {
					return this.$util.Tips({
						title: err.msg
					});
				});
		},
		// 查看订单
		intoPage(url) {
			if (this.isLogin) {
				this.$util.JumpPath(url);
			} else {
				toLogin();
			}
		},
		// 编辑页面
		goEdit() {
			if (this.isLogin == false || !this.userInfo.uid) {
				toLogin();
			} else {
				// #ifdef MP
				if (this.userInfo.is_default_avatar) {
					this.editModal = true;
					return;
				}
				// #endif
				uni.navigateTo({
					url: '/pages/users/user_set/index'
				});
			}
		},
		goDetail(item) {
			uni.navigateTo({
				url: `/pages/goods_details/index?id=${item.id}&fromPage='newVip'`
			});
		},
		comerPop() {
			this.isComerGift = false;
		},
		getNewcomerGift() {
			if (uni.getStorageSync('newcomerGift')) {
				return (this.isComerGift = false);
			}
			newcomerGift()
				.then((res) => {
					this.comerGift = res.data;
					if (Object.prototype.toString.call(this.comerGift) == '[object Object]') {
						if (res.data.coupon_count || res.data.product_count || res.data.register_give_money || res.data.first_order_discount || res.data.register_give_integral) {
							uni.setStorageSync('newcomerGift', true);
							this.isComerGift = true;
						}
					}
				})
				.catch((err) => {
					return this.$util.Tips({
						title: err
					});
				});
		},
		tapQrCode() {
			if(this.isLogin){
				uni.navigateTo({
					url: '/pages/users/user_member_code/index'
				});
			}else{
				toLogin();
			}
			
		},
		getRoutineUserInfo(e) {
			updateUserInfo({
				userInfo: e.detail.userInfo
			})
				.then((res) => {
					this.getUserInfo();
					return this.$util.Tips('更新用户信息成功');
				})
				.catch((err) => {
					return this.$util.Tips(err);
				});
		},
		// 记录会员访问
		setVisit() {
			setVisit({
				url: '/pages/user/index'
			}).then((res) => {});
		},
		// 打开授权
		openAuto() {
			toLogin();
		},
		// 授权回调
		onLoadFun(e) {
			this.getUserInfo(e);
			this.getMyMenus();
			this.setVisit();
		},
		// 绑定手机
		bindPhone() {
			uni.navigateTo({
				url: '/pages/users/user_phone/index'
			});
		},
		// 获取行销数据
		getMenuData() {
			getMenuData().then((res) => {
				this.orderAdminData = res.data;
			});
		},
		/**
		 * 获取个人用户信息
		 */
		getUserInfo() {
			let that = this;
			getUserInfo().then((res) => {
				that.userInfo = res.data;
				that.stu = res.data.svip_open;
				that.balanceStatus = res.data.balance_func_status;
				that.memberStatus = parseInt(res.data.member_func_status);
				that.vipStatus = res.data.vip_status;
				that.$store.commit('SETUID', res.data.uid);
				that.$store.commit('UPDATE_USERINFO', res.data);
				that.orderMenu.forEach((item, index) => {
					switch (item.title) {
						case '待付款':
							this.$set(item, 'num', res.data.orderStatusNum.unpaid_count);
							break;
						case '待发货':
							this.$set(item, 'num', res.data.orderStatusNum.unshipped_count);
							break;
						case '待收货':
							this.$set(item, 'num', res.data.orderStatusNum.received_count);
							break;
						case '待评价':
							this.$set(item, 'num', res.data.orderStatusNum.evaluated_count);
							break;
						case '售后/退款':
							this.$set(item, 'num', res.data.orderStatusNum.refunding_count);
							break;
					}
				});
			});
		},
		getMyMenus: function () {
			let that = this;
			// if (this.MyMenus.length) return;
			getMenuList().then((res) => {
				res.data.routine_my_menus.forEach((el, index, arr) => {
					if (el.type == '2') {
						this.storeMenuShow = true;
					}
				});
				this.diyData = res.data.diy_data;
				this.switchTab(this.diyData.order.style);
				this.my_banner_status = res.data.diy_data.my_banner_status;
				this.menu_status = res.data.diy_data.menu_status;
				this.service_status = res.data.diy_data.service_status;
				this.vip_type = res.data.diy_data.vip_type;
				this.newcomer_style = res.data.diy_data.newcomer_style;
				this.newcomer_status = res.data.diy_data.newcomer_status;
				this.imgUrls = res.data.routine_my_banner;
				this.routineContact = Number(res.data.routine_contact_type);
			});
		},
		switchTab(style) {
			this.orderMenu.forEach((item, index) => {
				item.icon = this.orderStyle[style][index];
			});
		},
		goMenuPage(url, name) {
			if (this.isLogin) {
				let arr = url.split('@APPID=');
				if (arr.length > 1) {
					//#ifdef MP
					uni.navigateToMiniProgram({
						appId: arr[arr.length - 1], // 此为生活缴费appid
						path: arr[0], // 此为生活缴费首页路径
						envVersion: 'release',
						success: (res) => {
							console.log('打开成功', res);
						},
						fail: (err) => {
							console.log('sgdhgf', err);
						}
					});
					//#endif
					//#ifndef MP
					this.Tips({
						title: 'h5与app端不支持跳转外部小程序'
					});
					//#endif
				} else {
					if (url == '/pages/extension/customer_list/chat' || url == 'https://chat.crmeb.net/chat/mobile') {
						this.$util.getCustomer(this.userInfo);
					} else {
						if (url.indexOf('http') === -1) {
							// #ifdef H5
							if (name && name === '订单核销') {
								return (window.location.href = `${location.origin}${url}`);
							}
							// #endif
							// #ifdef MP
							if (url != '#' && url == '/pages/users/user_set/index') {
								uni.openSetting({
									success: function (res) {}
								});
							}
							// #endif
							if (url == '/pages/store_spread/index') {
								storeCardApi()
									.then((res) => {
										this.isextension = true;
										this.$nextTick(function () {
											this.extension.code = res.data.url;
										});
									})
									.catch((err) => {
										uni.hideLoading();
										this.$util.Tips({
											title: err
										});
									});
							}
							if (url == '/pages/users/agent/apply') {
								if (this.userInfo.division_status == -1) {
									uni.navigateTo({
										url: '/pages/users/agent/apply'
									});
								} else {
									uni.navigateTo({
										url: '/pages/users/agent/state'
									});
								}
								return;
							}
							if (url == '/pages/users/distributor/apply') {
								if (this.userInfo.promoter_status == -1) {
									uni.navigateTo({
										url: '/pages/users/distributor/apply'
									});
								} else {
									uni.navigateTo({
										url: '/pages/users/agent/state?type=promoter'
									});
								}
								return;
							}

							if (
								['/pages/goods_cate/goods_cate', '/pages/order_addcart/order_addcart', '/pages/user/index', '/pages/store_cate/store_cate', '/pages/index/index'].indexOf(url) == -1
							) {
								uni.navigateTo({
									url: url
								});
							} else {
								uni.reLaunch({
									url: url
								});
							}
						} else {
							// #ifdef H5
							this.$util.JumpPath(url);
							// #endif
							// #ifndef H5
							uni.navigateTo({
								url: `/pages/annex/web_view/index?url=${url}`
							});
							// #endif
						}
					}
				}
			}else{
				toLogin()
			}
		},
		isWork() {
			return navigator.userAgent.toLowerCase().indexOf('wxwork') !== -1 && navigator.userAgent.toLowerCase().indexOf('micromessenger') !== -1;
		},
		wechatAuthoize(code) {
			let that = this;
			if (!this.isWork()) {
				this.$Cache.set('WX_AUTH', code);
				silenceAuth({
					code: code,
					snsapi: 'snsapi_base',
					spread_spid: that.$Cache.get('spid')
				})
					.then((res) => {
						if (res.data.key) {
							this.$Cache.set('snsapiKey', res.data.key);
						} else {
							let time = res.data.expires_time - this.$Cache.time();
							this.$store.commit('LOGIN', {
								token: res.data.token,
								time: time
							});

							this.$store.commit('SETUID', res.data.userInfo.uid);
							this.$store.commit('UPDATE_USERINFO', res.data.userInfo);
							location.reload();
						}
					})
					.catch((err) => {
						uni.hideLoading();
						return this.$util.Tips({
							title: error
						});
					});
			}
		}
	}
};
</script>

<style lang="scss" scoped>
.footer-placeholder {
	height: calc(98rpx + constant(safe-area-inset-bottom));
	height: calc(98rpx + env(safe-area-inset-bottom));
	height: 98rpx;
}
.user-page {
	padding-bottom: calc(100rpx+ constant(safe-area-inset-bottom));
	padding-bottom: calc(100rpx + env(safe-area-inset-bottom));
	padding-bottom: 100rpx;
}
.copy_right {
	text-align: center;
	color: #ccc;
	font-size: 22rpx;
	margin-top: 40rpx;
	.copyRightImg {
		width: 219rpx;
		height: 74rpx;
		margin: 16rpx auto;
		display: block;
	}
	.support {
		width: 219rpx;
		height: 74rpx;
		margin: 54rpx auto;
		display: block;
	}
	.copyRightText {
		margin-top: 0rpx;
		color: #ccc;
		font-size: 20rpx;
		margin-bottom: 20rpx;
	}
}
</style>
