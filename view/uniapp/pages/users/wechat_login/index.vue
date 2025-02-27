<template>
	<view :style="colorStyle" class="wrapper">
		<view class="bag">
			<img :src="imgHost+'/statics/images/users/login-bg.jpg'" alt="" srcset="">
		</view>
		<view class="system-height" :style="{ height: statusBarHeight }"></view>
		<!-- #ifdef MP -->
		<view class="title-bar height-add" >
			<view class="icon" @tap="back" v-if="!isHome">
				<image src="../static/left.png"></image>
			</view>
			<view class="icon" @tap="home" v-else>
				<image src="../static/home.png"></image>
			</view>
			商城登录
		</view>
		<!-- #endif -->
		<view class="merchant-msg">
			<img :src="configData.logo_url" />
			<view class="name">
				{{configData.site_name}}
			</view>
		</view>
		<view class="wechat-login">
			<view class="btn-wrapper">
				<!-- #ifdef H5 -->
				<button hover-class="none" @tap="wechatLogin" class="bg-color btn1">授权登录</button>
				<!-- #endif -->
				<!-- #ifdef MP -->
				<template v-if="configData.wechat_auth_switch">
					<button hover-class="none" v-if="bindPhone" open-type="getPhoneNumber" @getphonenumber="getphonenumber"
						class="bg-color btn1">授权登录</button>
					<button hover-class="none" v-else-if="!bindPhone" @tap="getAuthLogin"
						class="bg-color btn1">授权登录</button>
					<button hover-class="none" v-else-if="mp_is_new" @tap="userLogin"
						class="bg-color btn1">授权登录</button>
					<button v-else-if="canUseGetUserProfile && code" hover-class="none" @tap="getUserProfile"
							class="bg-color btn1">授权登录</button>
					<button v-else hover-class="none" open-type="getUserInfo" @getuserinfo="setUserInfo"
						class="bg-color btn1">授权登录</button>
				</template>
				<button v-if="configData.phone_auth_switch" hover-class="none" @tap="phoneLogin" class="btn2">手机号登录</button>
				<button hover-class="none" @tap="cancelLogin" class="btn3">取消登录</button>
				<!-- #endif -->
			</view>
		</view>
		<view class="protocol" v-if="!canGetPrivacySetting">
			<checkbox-group @change='changeIsDefault' v-if="configData.store_user_agreement">
				<checkbox :checked="protocol ? true : false" />已阅读并同意<text class="font-num"
					@tap="privacy('user')">《用户协议》</text>
				与<text class="font-num" @tap="privacy('privacy')">《隐私协议》</text>
			</checkbox-group>
			<view v-else>
				登录注册即同意商城
				<text class="font-num" @tap="privacy('user')">《用户协议》</text>与<text class="font-num" @tap="privacy('privacy')">《隐私协议》</text>
			</view>
		</view>
	
		<block>
			<editUserModal :isShow="isShow" @closeEdit="closeEdit" @editSuccess="editSuccess">
			</editUserModal>
		</block>
		<!-- #ifdef MP -->
		<privacyAgreementPopup v-if="canGetPrivacySetting" @onReject="onReject" @onAgree="onAgree">
		</privacyAgreementPopup>
		<!-- #endif -->
	</view>
</template>

<script>
	const app = getApp();
	let statusBarHeight = uni.getWindowInfo().statusBarHeight + 'px';
	import { HTTP_REQUEST_URL } from '@/config/app';
	import editUserModal from '@/components/eidtUserModal/index.vue'
	import privacyAgreementPopup from '@/components/privacyAgreementPopup/index.vue'
	
	import {
		getLogo,
		silenceAuth,
		routineBindingPhone, 
		wechatAuthV2,
		authLogin,
		authType,
		wechatAuthLogin
	} from '@/api/public';
	import {
		LOGO_URL,
		EXPIRES_TIME,
		USER_INFO,
		STATE_R_KEY,
		WX_AUTH,
		STATE_KEY
	} from '@/config/cache';
	import {
		getUserInfo
	} from '@/api/user.js';
	import Routine from '@/libs/routine';
	import wechat from '@/libs/wechat';
	import colors from '@/mixins/color.js';
	export default {
		mixins:[colors],
		data() {
			return {
				isUp: false,
				phone: '',
				statusBarHeight: statusBarHeight,
				isHome: false,
				isPhoneBox: false,
				code: '',
				authKey: '',
				options: '',
				userInfo: {},
				codeNum: 0,
				canUseGetUserProfile: false,
				canGetPrivacySetting: false,
				mp_is_new: this.$Cache.get('MP_VERSION_ISNEW') || false,
				configData: this.$Cache.get('BASIC_CONFIG'), 
				imgHost: HTTP_REQUEST_URL,
				loginBg:'',
				protocol: false,
				bindPhone: false,
				isShow: false,
			};
		},
		components: {
			editUserModal,
			privacyAgreementPopup
		},
		onLoad(options) {
			if (uni.getUserProfile) {
				this.canUseGetUserProfile = true
			}
			let that = this;
			// #ifdef MP
			if (wx.getPrivacySetting) {
				this.canGetPrivacySetting = true
			}
			this.userLogin();
			// #endif
			// #ifdef H5
			const {
				code,
				state
			} = options;
			this.options = options;
			// 获取确认授权code
			this.code = code || '';
			if (code) {
				let spread_spid = this.$Cache.get("spread") || '';
				let backUrl = options.back_url;
				this.wechatAuthLogin({
					code,
					spread_spid
				}, backUrl)
			}
			// #endif
			let pages = getCurrentPages();
			let prePage = pages[pages.length - 2];
			if (prePage && prePage.route == 'pages/order_addcart/order_addcart') {
				this.isHome = true;
			} else {
				this.isHome = false;
			}
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
		},
		methods: {
			cancelLogin(){
				uni.navigateBack()
			},
			wechatAuthLogin(d, back_url) {
				let that = this;
				uni.showLoading({
					title: '正在登录中'
				});
				wechatAuthLogin(d).then(res => {
					uni.hideLoading();
					this.$Cache.set(WX_AUTH, d.code);
					this.$Cache.clear(STATE_KEY);
					if (res.data.bindPhone) {
						this.authKey = res.data.key
						uni.navigateTo({
							url: `/pages/users/binding_phone/index?authKey=${this.authKey}&backUrl=${back_url}`
						})
					} else {
						let time = res.data.expires_time - this.$Cache.time();
						this.$store.commit('LOGIN', {
							token: res.data.token,
							time: time
						});
						that.$util.Tips({
							title: '登录成功',
							icon: 'success'
						}, {
							tab: 4,
							url: back_url || '/pages/user/index'
						});
						// that.getUserInfo(0, back_url)
					}
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({
						title: err,
						icon: 'none',
						duration: 2000
					});
				});
			},
			// 小程序 22.11.8日删除getUserProfile 接口获取用户昵称头像
			userLogin() {
				Routine.getCode().then(code => {
						this.code = code
						authType({
							code,
							spread_spid: app.globalData.spid,
							spread_code: app.globalData.code
						}).then(res => {
							this.authKey = res.data.key;
							this.bindPhone = res.data.bindPhone;
						}).catch(err => {
							uni.hideLoading();
							uni.showToast({
								title: err,
								icon: 'none',
								duration: 2000
							});
						})
					})
					.catch(err => {
						console.log(err)
					});
			},
			// 授权登录
			getAuthLogin() {
				if (!this.protocol) {
					return this.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				uni.showLoading({
					title: '正在登录中'
				});
				authLogin({
					key: this.authKey
				}).then(res => {
					let time = res.data.expires_time - this.$Cache.time();
					this.$store.commit('LOGIN', {
						token: res.data.token,
						time: time
					});
					this.getUserInfo(res.data.store_user_avatar)
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({
						title: err,
						icon: 'none',
						duration: 2000
					});
				});
			},
			changeIsDefault(e) {
				this.$set(this, 'protocol', !this.protocol);
			},
			editSuccess() {
				this.isShow = false;
			},
			closeEdit() {
				this.isShow = false
				this.$util.Tips({
					title: '登录成功',
					icon: 'success'
				}, {
					tab: 3
				});
			},
			phoneLogin(){
				uni.navigateTo({
					url: `/pages/users/binding_phone/index?authKey=${this.authKey}&pageType=0`
				})
			},
			onAgree() {
				this.protocol = true
			},
			onReject() {
				uni.navigateBack();
			},
			back() {
				uni.navigateBack();
			},
			home() {
				uni.switchTab({
					url: '/pages/index/index'
				})
			},
			// 弹窗关闭
			maskClose() {
				this.isUp = false;
			},
			bindPhoneClose(data) {
				if (data.isStatus) {
					this.isPhoneBox = false;
					this.$util.Tips({
						title: '登录成功',
						icon: 'success'
					}, {
						tab: 3
					});
				} else {
					this.isPhoneBox = false;
				}
			},
			privacy(type) {
				uni.navigateTo({
					url: "/pages/users/privacy/index?type=" + type
				})
			},
			// #ifdef MP
			// 小程序获取手机号码
			getphonenumber(e) {
				if(e.detail.errMsg == 'getPhoneNumber:fail user deny'){
					return false
				}
				if (!this.protocol) {
					return this.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				uni.showLoading({
					title: '正在登录中'
				});
				Routine.getCode()
					.then(code => {
						this.getUserPhoneNumber(e.detail.encryptedData, e.detail.iv, code);
					})
					.catch(error => {
						uni.$emit('closePage', false);
						uni.hideLoading();
					});
			},
			// 小程序获取手机号码回调
			getUserPhoneNumber(encryptedData, iv, code) {
				routineBindingPhone({
						encryptedData: encryptedData,
						iv: iv,
						code: code,
						spread_spid: app.globalData.spid,
						spread_code: app.globalData.code,
						key: this.authKey
					})
					.then(res => {
						let time = res.data.expires_time - this.$Cache.time();
						this.$store.commit('LOGIN', {
							token: res.data.token,
							time: time
						});
						// this.userInfo = res.data.userInfo;
						// this.$store.commit('SETUID', res.data.userInfo.uid);
						// this.$store.commit('UPDATE_USERINFO', res.data.userInfo);
						this.$Cache.clear('snsapiKey');
						this.getUserInfo(res.data.store_user_avatar)
						// this.$util.Tips({
						// 	title: '登录成功',
						// 	icon: 'success'
						// }, {
						// 	tab: 3
						// });
					})
					.catch(res => {
						this.$util.Tips({
							title: res
						});
						uni.hideLoading();
					});
			},
			/**
			 * 获取个人用户信息
			 */
			getUserInfo(new_user, back_url) {
				let that = this;
				getUserInfo().then(res => {
					uni.hideLoading();
					that.userInfo = res.data;
					that.$store.commit('SETUID', res.data.uid);
					that.$store.commit('UPDATE_USERINFO', res.data);
					if (new_user) {
						this.isShow = true
					} else {
						// #ifdef MP
						that.$util.Tips({
							title: '登录成功',
							icon: 'success'
						}, {
							tab: 3
						});
						// #endif
						// #ifndef MP
						that.$util.Tips({
							title: '登录成功',
							icon: 'success'
						}, {
							tab: 4,
							url: back_url || '/pages/user/index'
						});
						// #endif
					}
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({
						title: err.msg,
						icon: 'none',
						duration: 2000
					});
				});
			},
			
			setUserInfo(e) {
				uni.showLoading({
					title: '正在登录中'
				});
				Routine.getCode()
					.then(code => {
						this.getWxUser(code);
					})
					.catch(res => {
						uni.hideLoading();
					});
			},
			//小程序授权api替换 getUserInfo
			getUserProfile() {
				uni.showLoading({
					title: '正在登录中'
				});
				let self = this;
				Routine.getUserProfile()
					.then(res => {
						let userInfo = res.userInfo;
						userInfo.code = this.code;
						userInfo.spread_spid = app.globalData.spid || this.$Cache.get('spread'); //获取推广人ID
						userInfo.spread_code = app.globalData.code; //获取推广人分享二维码ID
						Routine.authUserInfo(userInfo)
							.then(res => {
								if (res.data.key !== undefined && res.data.key) {
									uni.hideLoading();
									self.authKey = res.data.key;
									self.isPhoneBox = true;
								} else {
									uni.hideLoading();
									let time = res.data.expires_time - self.$Cache.time();
									self.$store.commit('LOGIN', {
										token: res.data.token,
										time: time
									});
									this.getUserInfo()
								}
							})
							.catch(res => {
								uni.hideLoading();
								uni.showToast({
									title: res.msg,
									icon: 'none',
									duration: 2000
								});
							});
					})
					.catch(res => {
						uni.hideLoading();
					});
			},
			getWxUser(code) {
				let self = this;
				Routine.getUserInfo()
					.then(res => {
						let userInfo = res.userInfo;
						userInfo.code = code;
						userInfo.spread_spid = app.globalData.spid; //获取推广人ID
						userInfo.spread_code = app.globalData.code; //获取推广人分享二维码ID
						Routine.authUserInfo(userInfo)
							.then(res => {
								if (res.data.key !== undefined && res.data.key) {
									uni.hideLoading();
									self.authKey = res.data.key;
									self.isPhoneBox = true;
								} else {
									uni.hideLoading();
									let time = res.data.expires_time - self.$Cache.time();
									self.$store.commit('LOGIN', {
										token: res.data.token,
										time: time
									});
									self.$util.Tips({
										title: res.msg,
										icon: 'success'
									}, {
										tab: 3
									});
								}
							})
							.catch(res => {
								uni.hideLoading();
								uni.showToast({
									title: res.msg,
									icon: 'none',
									duration: 2000
								});
							});
					})
					.catch(res => {
						uni.hideLoading();
					});
			},

			// #endif
			// #ifdef H5
			// 获取url后面的参数
			getQueryString(name) {
				var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
				var reg_rewrite = new RegExp('(^|/)' + name + '/([^/]*)(/|$)', 'i');
				var r = window.location.search.substr(1).match(reg);
				var q = window.location.pathname.substr(1).match(reg_rewrite);
				if (r != null) {
					return unescape(r[2]);
				} else if (q != null) {
					return unescape(q[2]);
				} else {
					return null;
				}
			},
			// 公众号登录
			wechatLogin() {
				if (!this.protocol && this.configData.store_user_agreement) {
					return this.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				if ((!this.code && !this.authKey) || (this.options.scope != 'snsapi_base' && this.options.scope != 'snsapi_userinfo')) {
					this.$wechat.newAuth('snsapi_userinfo', location.origin + '/pages/users/wechat_login/index');
					// this.$wechat.oAuth('snsapi_userinfo', '/pages/users/wechat_login/index');
				}else{
					if (this.authKey) {
						uni.navigateTo({
							url: `/pages/users/binding_phone/index?authKey=${this.authKey}`
						})
					}
				}
			},
			// #endif
		}
	};
</script>

<style lang="scss">
	page {
		background: #fff;
	}
	.wrapper {
		position: relative;
		height: 100vh;
	
		.bag {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			z-index: -1;
			/* #ifdef H5 */
			z-index: 0;
	
			/* #endif */
			img {
				width: 100%;
				height: 544rpx;
			}
		}
	
		.merchant-msg {
			padding-top: 252rpx;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			z-index: 2;
			/* #ifdef H5 */
			position: relative;
	
			/* #endif */
			img {
				width: 152rpx;
				height: 152rpx;
				border-radius: 50%;
			}
	
			.name {
				font-size: 40rpx;
				font-weight: 500;
				color: #333333;
				line-height: 56rpx;
				margin-top: 32rpx;
			}
		}
	}
	.protocol{
		position: fixed;
		bottom: 52rpx;
		left: 0;
		width: 100%;
		margin: 0 auto;
		color: #999999;
		font-size: 24rpx;
		line-height: 22rpx;
		text-align: center;
		bottom: calc(52rpx + constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		bottom: calc(52rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		
		/deep/uni-checkbox .uni-checkbox-input {
			width: 32rpx !important;
			height: 32rpx !important;
		}
		
		/deep/uni-checkbox .uni-checkbox-input.uni-checkbox-input-checked::before {
			font-size: 24rpx
		}
		
		/*checkbox 选项框大小  */
		/deep/checkbox .wx-checkbox-input {
			width: 32rpx;
			height: 32rpx;
			margin-bottom: 4rpx;
		}
		
		/*checkbox选中后样式  */
		/deep/checkbox .wx-checkbox-input.wx-checkbox-input-checked {
			background: white;
		}
		
		/*checkbox选中后图标样式  */
		/deep/checkbox .wx-checkbox-input.wx-checkbox-input-checked::before {
			width: 32rpx;
			height: 32rpx;
			line-height: 32rpx;
			text-align: center;
			font-size: 22rpx;
			background: transparent;
			transform: translate(-50%, -50%) scale(1);
			-webkit-transform: translate(-50%, -50%) scale(1);
		}
	}
	.height-add {
	  height: 43px;
	}
	.wechat-login {
		padding: 72rpx 34rpx;

		.img image {
			width: 100%;
		}

		.btn-wrapper {
			margin-top: 86rpx;
			padding: 0 66rpx;

			button {
				width: 100%;
				height: 86rpx;
				line-height: 86rpx;
				margin-bottom: 40rpx;
				border-radius: 120rpx;
				font-size: 30rpx;
				// background-color: var(--view-theme);
				// border:1px solid var(--view-theme);

				&.btn1 {
					color: #fff;
				}

				&.btn2 {
					color: #666666;
					border: 1px solid #E4E4E4;
				}
				&.btn3 {
					color: #999999;
					border: none;
					height: unset;
					line-height: unset;
				}
			}
		}
	}

	.title-bar {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 36rpx;
	}

	.icon {
		position: absolute;
		left: 30rpx;
		top: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 86rpx;
		height: 86rpx;

		image {
			width: 40rpx;
			height: 40rpx;
		}
	}
</style>
