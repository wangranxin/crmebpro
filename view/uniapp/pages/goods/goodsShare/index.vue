<template>
	<view :style="colorStyle">
		<view class="px-20">
			<view class="w-full bg--w111-fff rd-24rpx p-24 mt-24"v-show="showCardOne">
				<view class='flex-y-center'>
					<text class="iconfont icon-icon_WeChat_1 fs-36"></text>
					<text class="fs-30 lh-42rpx fw-500 pl-8">方式一：商品卡片分享</text>
				</view>
				<view class="mt-24 w-full h-256 bg--w111-f9f9f9 rd-12rpx relative bg-cover" :style="[shareCardBg]">
					<view class="flex-y-center card-site">
						<image :src="mpData.site_logo" class="site-logo"></image>
						<text class="site-name line1">{{mpData.site_name}}</text>
					</view>
					<image :src="storeInfo.image" class="w-210 h-168 card-center-image" mode="aspectFill"></image>
					<!--  -->
				</view>
				<!-- #ifdef MP-WEIXIN -->
				<button class="w-full bg-gradient h-80 rd-40rpx flex-center fs-28 text--w111-fff mt-24" open-type="share" hover-class="none">分享商品卡片</button>
				<!-- #endif -->
				<!-- #ifdef APP || H5 -->
				<view class="w-full bg-gradient h-80 rd-40rpx flex-center fs-28 text--w111-fff mt-24" @tap="cardShare">分享商品卡片</view>
				<!-- #endif -->
			</view>
			<view class="w-full bg--w111-fff rd-24rpx p-24 mt-32">
				<view class="flex-between-center">
					<view class='flex-y-center'>
						<image src="../static/share_picture.png" class="w-36 h-36"></image>
						<text class="fs-30 lh-42rpx fw-500 pl-8">方式{{showCardOne ? '二' : '一'}}：商品图片分享</text>
					</view>
					<text class="iconfont fs-28 text--w111-666" 
						v-if="storeInfo.slider_image.length > 1"
						:class="isGrid ? 'icon-a-ic_Imageandtextsorting' : 'icon-a-ic_Picturearrangement'"
						@tap="()=>{isGrid = !isGrid}"></text>
				</view>
				<view v-if="storeInfo.slider_image.length == 1">
					<view class="mt-24 flex-x-center" >
						<view class="load-box poster-img" v-show="!posterImage"></view>
						<image v-show="posterImage" :src="posterImage" @tap="proviewPhoto(0)" class="poster-img" mode="aspectFill"></image>
					</view>
					<view class="w-full flex-between-center">
						<!-- #ifdef MP-WEIXIN || APP-PLUS -->
						<view class="w-full bg-gradient h-80 rd-40rpx flex-center fw-500 fs-28 text--w111-fff mt-24" @tap="savePoster">保存商品海报图片</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="w-full bg-gradient h-80 rd-40rpx flex-center fs-28 text--w111-fff mt-24" @tap="proviewPhoto(0)">保存分享</view>
						<!-- #endif -->
					</view>
				</view>
				<view v-else>
					<view class="mt-24" v-show="!isGrid">
						<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
							<view class="inline-block mr-16" v-show="!posterImage">
								<view class="load-box w-192 h-192 rd-8rpx"></view>
							</view>
							<view class="inline-block mr-16" v-for="(item,index) in productSwiper" :key="index">
								<view class="w-192 h-192 relative text--w111-fff" @tap="proviewPhoto(index)">
									<image :src="item.pic" class="w-full h-full rd-8rpx" 
										:class="{'b-e': item.isQrcode}" mode="aspectFill"></image>
									<view class="qr-tag flex-center fs-18 text--w111-fff" v-if="posterImage && item.isQrcode">二维码推广图</view>
									<!-- #ifdef MP-WEIXIN || APP-PLUS -->
									<text class="iconfont fs-36 select-icon"
										:class="item.select ? 'icon-a-ic_CompleteSelect' : 'icon-ic_unselect'" @tap.stop="selectPic(item)"></text>
									<!-- #endif -->
								</view>
							</view>
						</scroll-view>
					</view>
					<view class="w-full grid-column-3 grid-gap-16rpx mt-24" v-show="isGrid">
						<view class="load-box w-full h-210 rd-12rpx" v-show="!posterImage"></view>
						<view class="w-full h-210 relative text--w111-fff" 
							:class="{'b-e rd-12rpx': item.isQrcode}"
							v-for="(item,index) in productSwiper" :key="index"
							@tap="proviewPhoto(index)">
							<image :src="item.pic" class="w-full h-full rd-12rpx"
								 mode="aspectFill"></image>
							<view class="qr-tag flex-center fs-18 text--w111-fff" v-if="posterImage && item.isQrcode">二维码推广图</view>
							<!-- #ifdef MP-WEIXIN || APP-PLUS -->
							<text class="iconfont fs-36 select-icon"
								:class="item.select ? 'icon-a-ic_CompleteSelect' : 'icon-ic_unselect'" 
								@tap.stop="selectPic(item)"></text>
							<!-- #endif -->
						</view>
					</view>
					<view class="w-full flex-between-center">
						<!-- #ifdef MP-WEIXIN || APP-PLUS -->
						<view class="w-320 h-80 rd-40rpx flex-center fs-26 view-border fw-500 font-num mt-24" @tap="savePhoto('pic')">保存选中图片</view>
						<view class="w-320 bg-gradient h-80 rd-40rpx flex-center fw-500 fs-28 text--w111-fff mt-24" @tap="savePhoto('poster')">保存商品海报图片</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="w-full bg-gradient h-80 rd-40rpx flex-center fs-28 text--w111-fff mt-24" @tap="proviewPhoto(0)">保存分享</view>
						<!-- #endif -->
					</view>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-24rpx p-24 mt-24">
				<view class='flex-y-center'>
					<image src="../static/share_text.png" class="w-36 h-36"></image>
					<text class="fs-30 lh-42rpx fw-500 pl-8">方式{{showCardOne ? '三' : '二'}}：文案分享</text>
				</view>
				<!-- #ifdef MP-WEIXIN -->
				<view class="mt-24 bg--w111-f9f9f9 rd-12rpx p-16 fs-26 lh-36rpx space-line">
					{{storeInfo.share_content || storeInfo.store_name}} 
					<text><br/>{{shortLink}}</text>
				</view>
				<!-- #endif -->
				<!-- #ifdef H5 || APP-PLUS -->
				<view class="mt-24 bg--w111-f9f9f9 rd-12rpx p-16 fs-26 lh-36rpx space-line">
					{{storeInfo.share_content || storeInfo.store_name}} 
					<text><br/>{{codeVal}}</text>
				</view>
				<!-- #endif -->
				<view class="w-full flex-between-center">
					<view class="w-320 h-80 rd-40rpx flex-center fs-26 view-border fw-500 font-num mt-24" @tap="copyShare(0)">仅复制链接</view>
					<view class="w-320 bg-gradient h-80 rd-40rpx flex-center fw-500 fs-28 text--w111-fff mt-24" @tap="copyShare(1)">复制文案及链接</view>
				</view>
			</view>
			<view class="pb-safe">
				<view class="h-80"></view>
			</view>
		</view>
		<!-- #ifdef H5 || APP-PLUS -->
		<zb-code ref="qrcode" onval loadMake :show="codeShow" cid="1" :val="codeVal" @result="qrR" />
		<!-- #endif -->
		<canvas class="canvas" canvas-id="myCanvas" v-if="canvasStatus"></canvas>
	</view>
</template>

<script>
	import colors from "@/mixins/color";
	import {HTTP_REQUEST_URL} from '@/config/app';
	import { getProductDetail, getProductCode, getShortLinkApi } from "@/api/store.js";
	import { getUserInfo, userShare } from '@/api/user.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from 'vuex';
	import zbCode from '@/components/zb-code/zb-code.vue'
	export default {
		data() {
			return {
				imgHost: HTTP_REQUEST_URL,
				id: 0,
				canvasStatus: false,
				//二维码参数
				codeShow: false,
				codeVal: "", // 要生成的二维码值
				shareQrcode: 0,
				PromotionCode: '',
				followCode: '',
				siteName: '', //商城名称
				posterTitle: '',
				themeColor: '#e93323',
				fontColor: '#e93323',
				posterImage: '', //海报路径
				storeImage: '', //海报产品图
				isDown: true,
				posterImageStatus: false,
				storeInfo:{
					slider_image: []
				},
				productSwiper: [],
				showCardOne: true,
				shortLink: '',
				isGrid: true,
				mpData:{}
			};
		},
		components: { zbCode },
		computed:{
			...mapGetters(['isLogin', 'uid']),
			shareCardBg(){
				return {
					backgroundImage: 'url('+this.imgHost+'/statics/images/promoter/promoter_group9.png'+')'
				}
			},
		},
		mixins: [colors],
		onLoad(options) {
			// #ifdef H5
			this.showCardOne = this.$wechat.isWeixin() ? true : false;
			// #endif
			if (!options.id) {
				return this.$util.Tips({
					title: '缺少参数无法查看商品'
				}, {
					tab: 3
				});
			}
			this.id = options.id || 0;
			this.getInfo();
			// #ifdef MP-WEIXIN
			let MPSiteData = uni.getStorageSync('MPSiteData');
			this.mpData = JSON.parse(MPSiteData);
			this.getShortLink();
			// #endif
		},
		onReady: function() {
			this.isNodes++;
			let uid = this.isLogin ? this.$store.state.app.uid : ''
			// #ifdef H5
			this.codeVal = window.location.origin + '/pages/goods_details/index?id=' + this.id +
				'&spid=' + uid
			// #endif
			// #ifdef APP-PLUS
			this.codeVal = HTTP_REQUEST_URL + '/pages/goods_details/index?id=' + this.id +
				'&spid=' + uid
			// #endif
		},
		methods:{
			async getInfo(){
				let res = await getProductDetail(this.id, {promotions_type: 0});
				let storeInfo = res.data.storeInfo;
				this.$set(this, 'storeInfo', storeInfo);
				this.$set(this, 'shareQrcode', res.data.share_qrcode);
				this.$set(this, 'siteName', res.data.site_name);
				this.$set(this, 'posterTitle', res.data.product_poster_title);
				this.productSwiper = storeInfo.slider_image.map(item=>{
					return {
						pic: item, 
						select: false,
						isQrcode: false,
					}
				})
				this.goPoster();
			},
			getShortLink(){
				getShortLinkApi(this.id).then(res=>{
					this.shortLink = res.data.code;
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			copyShare(type){
				let that = this;
				let shareText = this.storeInfo.share_content || this.storeInfo.store_name;
				let copyText = '';
				// #ifdef MP-WEIXIN
				copyText = type == 1 ? shareText + ' ' + this.shortLink : this.shortLink;
				// #endif
				// #ifdef APP-PLUS || H5
				copyText = type == 1 ? shareText + ' ' + this.codeVal : this.codeVal;
				// #endif
				uni.setClipboardData({
					data: copyText,
					success:()=>{
						uni.showToast({
							title: '复制成功，快去分享吧',
							icon: 'none'
						})
					}
				})
			},
			qrR(res) {
				// #ifdef H5
				if (!this.$wechat.isWeixin() || this.shareQrcode != '1') {
					this.PromotionCode = res;
					this.followCode = ''
				}
				// #endif
				// #ifdef APP-PLUS
				this.PromotionCode = res;
				// #endif
			},
			//替换安全域名
			setDomain: function(url) {
				url = url ? url.toString() : '';
				//本地调试打开,生产请注销
				if (url.indexOf('https://') > -1) return url;
				else return url.replace('http://', 'https://');
			},
			//图片转符合安全域名路径
			downloadFileImage(url) {
				url = this.setDomain(url)
				return new Promise((resolve, reject) => {
					let that = this;
					uni.downloadFile({
						url: url,
						success: function(res) {
							resolve(res.tempFilePath);
						},
						fail: function(err) {
							console.error(err.errMsg);
						}
					});
				})
			},
			async goPoster() {
				let that = this;
				let storeImage = await this.downloadFileImage(this.storeInfo.image);
				let posterbackgd = await this.downloadFileImage(this.imgHost + '/statics/images/product/posterbackgd.png');
				// #ifdef MP-WEIXIN
				// 小程序端获取小程序码
				let res = await getProductCode(that.id);
				if(!res.data.code){
					return that.$util.Tips({
						title: '小程序二维码需要发布正式版后才能获取到'
					});
				}
				let PromotionCode = await this.downloadFileImage(res.data.code)
				// #endif
				// #ifdef H5
				let PromotionCode = '';
				if(this.$wechat.isWeixin()){
					// 公众号端获取公众号二维码
					let res = await getProductCode(that.id);
					PromotionCode = await this.downloadFileImage(res.data.code)
				}else{
					// h5端获取本地生成的二维码
					PromotionCode = this.PromotionCode;
				}
				// #endif
				// #ifdef APP-PLUS
				let PromotionCode = this.PromotionCode;
				// #endif
				that.$set(that, 'canvasStatus', true);
				
				let arr2 = [posterbackgd, storeImage, PromotionCode];
				that.$nextTick(function() {
					setTimeout(()=>{
						that.$util.PosterCanvas(that.fontColor, that.themeColor, that.siteName,arr2, 
						that.storeInfo.store_name, 
						that.storeInfo.price, 
						that.storeInfo.ot_price, 
						that.posterTitle,
							function(tempFilePath) {
								that.$set(that, 'posterImage', tempFilePath);
								that.$set(that, 'posterImageStatus', true);
								that.$set(that, 'canvasStatus', false);
								that.productSwiper.unshift({
									pic:tempFilePath,
									select: false,
									isQrcode:true
								})
							});
					},1000)
				})
			},
			cardShare(){
				// #ifdef H5
				this.ShareInfo();
				uni.showToast({
					title: '请点击右上角三个点进行分享',
					icon: 'none'
				})
				// #endif
				// #ifdef APP-PLUS
				this.appShare();
				// #endif
			},
			//#ifdef H5
			ShareInfo() {
				let data = this.storeInfo;
				if (this.$wechat.isWeixin()) {
					let configAppMessage = {
						desc: data.store_info,
						title: data.store_name,
						link: '/pages/goods_details/index?id=' + this.id + '&spid=' + this.uid,
						imgUrl: data.image
					};
					this.$wechat.wechatEvevt([
						'updateAppMessageShareData', 
						'updateTimelineShareData',
						'onMenuShareAppMessage',
						'onMenuShareTimeline'
					], configAppMessage).then(res => {}).catch(err => {});
				}
			},
			//#endif
			appShare() {
				let that = this;
				uni.share({
					provider: "weixin",
					scene: 'WXSceneSession',
					type: 0,
					href: `${HTTP_REQUEST_URL}/pages/goods_details/index?id=${that.id}&spid=${that.uid}`,
					title: that.storeInfo.store_name,
					summary: that.storeInfo.store_info,
					imageUrl: that.storeInfo.small_image,
					success: function(res) {
						
					},
					fail: function(err) {
						uni.showToast({
							title: '分享失败',
							icon: 'none',
							duration: 2000
						})
					}
				});
			},
			selectPic(item){
				item.select = !item.select;
			},
			savePhoto(type){
				// #ifdef MP-WEIXIN
				let that = this;
				uni.getSetting({
					success(res){
						if (!res.authSetting['scope.writePhotosAlbum']) {
							uni.authorize({
								scope: 'scope.writePhotosAlbum',
								success() {
									if(type == 'poster'){
										that.savePoster()
									}else{
										that.mpSave();
									}
									
								}
							});
						}else{
							if(type == 'poster'){
								that.savePoster()
							}else{
								that.mpSave();
							}
						}
					}
				})
				// #endif
				// #ifdef APP-PLUS
				if(type == 'poster'){
					this.savePoster()
				}else{
					this.mpSave();
				}
				// #endif
			},
			mpSave(){
				let that = this;
				let arr = this.productSwiper.filter(val => val.select);
				let picLength = arr.length; // 要下载的总条数
				let index = 0;
				uni.showLoading({
					title: '图片下载中',
					mask: true
				});
				for (let i = 0; i < arr.length; i++) {
					if(arr[i].isQrcode){
						this.savePoster();	
					}else{
						uni.downloadFile({
							url: arr[i].pic,
							success: function(res) {
								var temp = res.tempFilePath
								uni.saveImageToPhotosAlbum({
									filePath: temp,
									success(res1) {
										index++;
										// 全部下载完后触发
										if (index == picLength) {
											uni.hideLoading()
											that.$util.Tips({
												title: '保存成功',
												icon: 'success'
											});
										}
									},
									fail: function(res) {
										uni.hideLoading()
										that.$util.Tips({
											title: '保存失败'
										});
									}
								})
							},
							fail(err) {	
								uni.hideLoading()
							}
						})
					}
				}
			},
			savePoster(){
				let that = this;
				uni.saveImageToPhotosAlbum({
					filePath: that.posterImage,
					success(res1) {
						that.$util.Tips({
							title: '保存成功',
							icon: 'success'
						});
					},
					fail: function(res) {
						that.$util.Tips({
							title: '保存失败'
						});
					}
				})
			},
			proviewPhoto(index){
				let list = this.productSwiper.map(item=> item.pic);
				uni.previewImage({
					current: index,
					urls: list
				});
			},
		},
		// #ifdef MP
		onShareAppMessage: function() {
			let that = this;
			userShare();
			return {
				title: that.storeInfo.store_name || '',
				imageUrl: that.storeInfo.image || '',
				path: '/pages/goods_details/index?id=' + that.id + '&spid=' + that.uid
			};
		},
		onShareTimeline() {
			let that = this;
			userShare();
			return {
				title: that.storeInfo.store_name || '',
				imageUrl: that.storeInfo.image || '',
				path: '/pages/goods_details/index?id=' + that.id + '&spid=' + that.uid
			};
		},
		// #endif
	}
</script>

<style lang="scss">
.icon-icon_WeChat_1{
	color: rgba(11, 190, 36, 1);
}
.h-256{
	height: 256rpx;
}
.bg-cover{
	background-size: cover;
}
.card-site{
	position: absolute;
	left: 50%;
	top: 24rpx;
	transform: translateX(-50%);
	width: 210rpx;
	.site-logo{
		width: 18rpx;
		height: 18rpx;
		border-radius: 50%;
	}
	.site-name{
		width: 152rpx;
		font-size: 18rpx;
		color: #727272;
		line-height: 16rpx;
		padding-left: 4rpx;
	}
}
.card-center-image{
	position: absolute;
	left: 50%;
	top: 48rpx;
	transform: translateX(-50%);
}
.poster-img{
	width: 254rpx;
	height: 438rpx;
	border-radius: 12rpx;
	border: 1rpx solid #EEEEEE;
}
.load-box{
	animation: looming-gray 1s infinite linear;
	background-color: #e3e3e3;
}
.view-border{
	border: 1px solid var(--view-theme);
}
.icon-a-ic_CompleteSelect{
	color: var(--view-theme);
}
.space-line{
	white-space: pre-line
}
.select-icon{
	position: absolute;
	top: 12rpx;
	right: 12rpx;
}
.b-e{
	border: 1rpx solid #EEEEEE;
}
.qr-tag{
	position: absolute;
	bottom: 0;
	left: 0;
	width: 100%;
	height: 40rpx;
	border-radius: 0 0 8rpx 8rpx;
	background-color: rgba(0, 0, 0, 0.5);
}
.canvas {
	position: fixed;
	top: -1300px;
	left: 0;
	width: 750px;
	height: 1300px;
}
@keyframes looming-gray {
	0% {
		background-color: #e3e3e3aa;
	}

	50% {
		background-color: #e3e3e3;
	}

	100% {
		background-color: #e3e3e3aa;
	}
}
</style>
