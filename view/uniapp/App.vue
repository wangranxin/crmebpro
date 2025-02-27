<script>
	import {checkLogin} from './libs/login';
	import {HTTP_REQUEST_URL} from './config/app';
	import { LOGIN_STATUS, SUBSCRIBE_MESSAGE } from "./config/cache.js"
	import { getShopConfig, silenceAuth, getLogo, remoteRegister } from '@/api/public';
	import { silenceBindingSpread } from '@/utils';
	import Auth from '@/libs/wechat.js';
	import Routine from './libs/routine.js';
	import { colorChange, copyRight } from '@/api/api.js';
	import { getUserInfo } from "@/api/user.js" 
	import { mapGetters } from "vuex"
	// #ifdef MP
	// let livePlayer = requirePlugin('live-player-plugin')
	// #endif
	export default {
		globalData: {
			spid: 0,
			code: 0,
			isLogin: false,
			userInfo: {},
			globalData: false,
			windowHeight: uni.getWindowInfo().windowHeight + 'px',
			sysHeight:uni.getWindowInfo().statusBarHeight,
		},
		computed: mapGetters(['isLogin', 'cartNum']),
		watch: {
			cartNum(newCart, b) {
				this.$store.commit('indexData/setCartNum', newCart + '')
				if (newCart > 0) {
					uni.setTabBarBadge({
						index: 3,
						text: newCart>99?'99+':newCart+''
					})
				} else {
					uni.hideTabBarRedDot({
						index: 3
					})
				}
			}
		},
		onShow(options) {
				const queryData = uni.getEnterOptionsSync(); // uni-app版本 3.5.1+ 支持
				if (queryData.query.spid) {
					this.$Cache.set('spread', queryData.query.spid);
					this.globalData.spid = queryData.query.spid;
					this.globalData.pid = queryData.query.spid;
					silenceBindingSpread(this.globalData);
				}
				// #ifdef MP
				if (queryData.query.scene) {
					let param = this.$util.getUrlParams(decodeURIComponent(queryData.query.scene));
					if (param.spid) {
						this.$Cache.set('spread', param.spid);
						this.globalData.spid = param.spid;
					}
					/** 直播间分享**/
					// const sceneList = [1007, 1008, 1014, 1044, 1045, 1046, 1047, 1048, 1049, 1073, 1154, 1155];
					//  if (sceneList.includes(queryData.query.scene)) {
					// 	livePlayer.getShareParams().then(res => {
					// 			//记录推广人uid
					// 			if(res.custom_params.pid){
					// 				 this.$Cache.set('spread', res.custom_params.pid);
					// 				 this.globalData.spid = res.custom_params.pid;
					// 			}
					// 		}).catch(err => {
					// 		})
					// }
					silenceBindingSpread(this.globalData);
				}
				// #endif
			},
		onLaunch(option) {
			uni.hideTabBar()
			//#ifdef APP
			plus.screen.lockOrientation("portrait-primary");
			//#endif
			let that = this;
			//获取并存储配置
			this.setConfig();
			//主题换色
			this.setTheme();
			// 通过vuex获取并储存商品详情,商品分类可视化的数据
			this.$store.dispatch("getDiyProduct");
			// 通过vuex获取并储存底部菜单的数据
			this.$store.dispatch("getPageFooter");
			// #ifdef H5
			if (!this.isLogin && option.query.hasOwnProperty('remote_token')) {
				this.remoteRegister(option.query.remote_token);
			}
			this.setScript();
			const queryData = uni.getEnterOptionsSync();
			uni.getSystemInfo({
				success(e) {
					/* 窗口宽度大于420px且不在PC页面且不在移动设备时跳转至 PC.html 页面 */
					if (e.windowWidth > 420 && !window.top.isPC && !/iOS|Android/i.test(e.system)) {
						window.location.pathname = '/static/html/pc.html';
					}
				}
			});
			// #endif
			// #ifdef MP
			// 小程序静默授权
			if (!this.$store.getters.isLogin) {
				Routine.getCode().then(code => {
					this.silenceAuth(code);
				}).catch(res => {
					uni.hideLoading();
				});
			}else if(this.$store.getters.isLogin && this.getExpired()){
				//处理小程序有token情况下但是静默授权失败
				this.$Cache.clear(LOGIN_STATUS);
				Routine.getCode().then(code => {
					this.silenceAuth(code);
				})
			}
			// #endif
		},
		methods: {
			remoteRegister(remote_token) {
				remoteRegister({ remote_token }).then((res) => {
					let data = res.data;
					if (data.get_remote_login_url) {
						location.href = data.get_remote_login_url
					} else {
						this.$store.commit("LOGIN", {
							'token': data.token,
							'time': data.expires_time - this.$Cache.time()
						});
						getUserInfo().then(res => {
							this.$store.commit("SETUID", res.data.uid);
							this.$store.commit("UPDATE_USERINFO", res.data);
							location.reload()
						}).catch(error => {
							return this.$util.Tips({
								title:err
							})
						})
					}
				});
			},
			// 小程序静默授权
			silenceAuth(code) {
				let that = this;
				let spid = that.globalData.spid ? that.globalData.spid : '';
				silenceAuth({
						code: code,
						spread_spid: spid,
						spread_code: that.globalData.code
					})
					.then(res => {
						if (res.data.token !== undefined && res.data.token) {
							uni.hideLoading();
							let time = res.data.expires_time - this.$Cache.time();
							that.$store.commit('LOGIN', {
								token: res.data.token,
								time: time
							});
							that.$store.commit('SETUID', res.data.userInfo.uid);
							that.$store.commit('UPDATE_USERINFO', res.data.userInfo);
						}
					}).catch(err => {
						return that.$util.Tips({
							title:err
						})
					});
			},
			/**
			 * 检测当前的小程序
			 * 是否是最新版本，是否需要下载、更新
			 */
			checkUpdateVersion() {
				//判断微信版本是否 兼容小程序更新机制API的使用
				if (wx.canIUse('getUpdateManager')) {
					const updateManager = wx.getUpdateManager();
					//检测版本更新
					updateManager.onCheckForUpdate(function(res) {
						if (res.hasUpdate) {
							updateManager.onUpdateReady(function() {
								wx.showModal({
									title: '温馨提示',
									content: '检测到新版本，是否重启小程序？',
									showCancel: false,
									success: function(res) {
										if (res.confirm) {
											// 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
											updateManager.applyUpdate()
										}
									}
								})
							})
							updateManager.onUpdateFailed(function() {
								// 新版本下载失败
								wx.showModal({
									title: '已有新版本',
									content: '请您删除小程序，重新搜索进入',
								})
							})
						}
					})
				} else {
					wx.showModal({
						title: '溫馨提示',
						content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后重试。'
					})
				}
			},
			async getExpired(){
				// 通过一个个人中心的接口判断token是否生效，catch捕获到就证明token过期，返回true
				try {
					await getUserInfo();
					return false; // token有效
				} catch (err) {
					return true; // token过期
				}
			},
			setConfig(){
				uni.removeStorageSync(SUBSCRIBE_MESSAGE);
				this.$Cache.clear('homeTop');
				let that = this;
				// #ifdef MP
				if (HTTP_REQUEST_URL == '') {
					uni.showToast({
						title: "请配置根目录下的config/app.js文件中的HTTP_REQUEST_URL",
						icon:'error'
					})
				}
				this.checkUpdateVersion();
				let menuButtonInfo = uni.getMenuButtonBoundingClientRect();
				that.globalData.navH = menuButtonInfo.top * 2 + menuButtonInfo.height / 2;
				const version = wx.getAppBaseInfo().SDKVersion;
				if (Routine.compareVersion(version, '2.21.2') >= 0) {
					that.$Cache.set('MP_VERSION_ISNEW', true)
				} else {
					that.$Cache.set('MP_VERSION_ISNEW', false)
				}
				// #endif
				getLogo().then(res => {
					uni.setStorageSync('BASIC_CONFIG', res.data)
				});
				//获取配置
				copyRight().then(res => {
					let data = res.data;
					uni.setStorageSync('wechatStatus', data.wechat_status)
					// #ifndef APP-PLUS
					this.site_config = data.record_No;
					// #endif
					if(!data.copyrightContext && !data.copyrightImage){
						data.copyrightImage = HTTP_REQUEST_URL + '/statics/images/product/support.png'
					}
					uni.setStorageSync('copyNameInfo', data.copyrightContext);
					uni.setStorageSync('copyImageInfo', data.copyrightImage);
					// #ifdef MP
					uni.setStorageSync('MPSiteData', JSON.stringify({site_logo:data.site_logo,site_name:data.site_name}));
					// #endif
				}).catch(err => {
					return this.$util.Tips({
						title: err.msg
					});
				});
			},
			setTheme(){
				let green =
					'--view-theme: #42CA4D;--view-priceColor:#FF7600;--view-minorColor:rgba(108, 198, 94, 0.5);--view-minorColorT:rgba(66, 202, 77, 0.1);--view-bntColor:#FE960F;--view-gradient:#4DEA4D'
				let red =
					'--view-theme: #e93323;--view-priceColor:#e93323;--view-minorColor:rgba(233, 51, 35, 0.5);--view-minorColorT:rgba(233, 51, 35, 0.1);--view-bntColor:#FE960F;--view-gradient:#FF7931'
				let blue =
					'--view-theme: #1DB0FC;--view-priceColor:#FD502F;--view-minorColor:rgba(58, 139, 236, 0.5);--view-minorColorT:rgba(9, 139, 243, 0.1);--view-bntColor:#22CAFD;--view-gradient:#5ACBFF'
				let pink =
					'--view-theme: #FF448F;--view-priceColor:#FF448F;--view-minorColor:rgba(255, 68, 143, 0.5);--view-minorColorT:rgba(255, 68, 143, 0.1);--view-bntColor:#282828;--view-gradient:#FF67AD'
				let orange =
					'--view-theme: #FE5C2D;--view-priceColor:#FE5C2D;--view-minorColor:rgba(254, 92, 45, 0.5);--view-minorColorT:rgba(254, 92, 45, 0.1);--view-bntColor:#FDB000;--view-gradient:#FF9451'
				let gold =
				    '--view-theme: #E0A558;--view-priceColor:#DA8C18;--view-minorColor:rgba(224, 165, 88, 0.5);--view-minorColorT:rgba(224, 165, 88, 0.1);--view-bntColor:#1A1A1A;--view-gradient:#FFCD8C'
				colorChange('color_change').then(res => {
					// let navigation = res.data.navigation; //判断悬浮导航是否显示
					let statusColor = res.data.status; //判断显示啥颜色
					// uni.setStorageSync('navigation', navigation);
					// uni.$emit('navOk', navigation);
					uni.setStorageSync('statusColor', statusColor);
					uni.$emit('colorOk', statusColor);
					let themeList = {
						1:blue,
						2:green,
						3:red,
						4:pink,
						5:orange,
						6:gold
					};
					for (let key in themeList) {
						let themeSelect = themeList[key];
						if(key == statusColor){
							uni.setStorageSync('viewColor', themeSelect)
							uni.$emit('ok', themeSelect)
						}
					}
				});
			},
			setScript(){
				// 添加crmeb chat 统计
				var __s = document.createElement('script');
				__s.src = `${HTTP_REQUEST_URL}/api/get_script`;
				document.head.appendChild(__s);
			},
		},
	};
</script>

<style lang="scss">
	/* #ifndef APP-PLUS-NVUE || APP-NVUE */
	@import url('@/plugin/emoji-awesome/css/tuoluojiang.css');
	@import url('@/plugin/animate/animate.min.css');
	@import 'static/css/base.css';
	@import 'static/iconfont/iconfont.css';
	@import 'static/css/unocss.css';
	@import 'static/fonts/font.scss';

	view {
		box-sizing: border-box;
	}

	page {
		font-family: PingFang SC;
	}

	.placeholder{
		color: #ccc;
	}

	.bg-color-red {
		background-color: var(--view-theme) !important;
	}

	.syspadding {
		padding-top: var(--status-bar-height);
	}

	.uni-scroll-view::-webkit-scrollbar {
		/* 隐藏滚动条，但依旧具备可以滚动的功能 */
		display: none;
	}

	::-webkit-scrollbar {
		width: 0;
		height: 0;
		color: transparent;
	}

	.uni-system-open-location .map-content.fix-position {
		height: 100vh;
		top: 0;
		bottom: 0;
	}
	/* #endif */
</style>
