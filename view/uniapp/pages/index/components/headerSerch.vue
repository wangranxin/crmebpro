<template>
	<!-- 搜索框 -->
	<view v-if="!dataConfig.isHide">
		<view class="header" :style="[headerStyle]">
			<!-- #ifdef MP-WEIXIN || APP-PLUS  -->
			<view :style="{height:statusBarHeight + 'px'}"></view> 
			<!-- #endif -->
			<view class="serch-wrapper acea-row row-middle" :style="[serchWrapperStyle]">
				<view class="logo skeleton-rect" v-if="!dataConfig.styleConfig.tabVal && dataConfig.logoConfig.url">
					<image :src="dataConfig.logoConfig.url" mode="heightFix"></image>
				</view>
				<view class="title" v-if="dataConfig.styleConfig.tabVal == 1 && dataConfig.titleConfig.value" @click="goLink">{{ dataConfig.titleConfig.value }}</view>
				<navigator url="/pages/goods/goods_search/index" class="input acea-row row-middle skeleton-rect" hover-class="none">
					<view class="search acea-row row-middle" :class="{ 'row-center': dataConfig.styleConfig.tabVal == 2 && !dataConfig.styleSearchConfig.tabVal }" :style="[searchStyle]">
						<text class="iconfont icon-ic_search"></text>
						<swiper v-if="hotWords.length" :autoplay="true" :interval="3000" :duration="1000" :vertical="true" :circular="true" class="swiper"
							:style="{ color: dataConfig.hotWordsColor.color[0].item }">
							<swiper-item v-for="(item, index) in hotWords" :key="index">
								{{ item.val }}
							</swiper-item>
						</swiper>
						<text v-else>{{ dataConfig.tipConfig.value }}</text>
					</view>
					<template v-if="dataConfig.styleConfig.tabVal == 2">
						<view v-if="dataConfig.styleSearchConfig.tabVal == 1" class="button"
							:style="{ background: dataConfig.searchBgColor.color[0].item, color: dataConfig.searchTxtColor.color[0].item }">搜索</view>
						<view v-else-if="dataConfig.styleSearchConfig.tabVal == 2" class="button2" :style="{ color: dataConfig.searchColor.color[0].item }">搜索</view>
					</template>
				</navigator>
				<!-- #ifdef MP-WEIXIN  -->
				<view :style="[menuButtonStyle]"></view>
				<!-- #endif -->
			</view>
		</view>
		<view :style="{height: marTop + 'px'}"></view>
	</view>
</template>

<script>
	let statusBarHeight = uni.getWindowInfo().statusBarHeight;
	export default {
		name: 'headerSerch',
		props: {
			dataConfig: {
				type: Object,
				default: () => {}
			},
			special: {
				type: Number,
				default: 0
			}
		},
		data() {
			return {
				statusBarHeight: statusBarHeight,
				marTop: 40,
				bgColor: this.dataConfig.moduleColor.color,
				boxStyle: '',
				logoConfig: '',
				mbConfig: '',
				txtStyle: '',
				hotWords: [],
				prConfig: '',
				tabVal: '',
				radioVal: '',
				textColor: '',
				textStyle: '',
				titleConfig: '',
			};
		},
		computed: {
			headerStyle() {
				return {
					padding: `${this.dataConfig.topConfig.val*2}rpx ${this.dataConfig.prConfig.val*2}rpx ${this.dataConfig.bottomConfig.val*2}rpx`,
					background: this.dataConfig.bottomBgColor.color[0].item,
				};
			},
			serchWrapperStyle() {
				let borderRadius = [];
				if (this.dataConfig.fillet.type) {
					for (let i = 0; i < this.dataConfig.fillet.valList.length; i++) {
						borderRadius.push(`${this.dataConfig.fillet.valList[i].val*2}rpx`);
					}
				} else {
					for (let i = 0; i < 4; i++) {
						borderRadius.push(`${this.dataConfig.fillet.val*2}rpx`);
					}
				}
				return {
					borderRadius: borderRadius.join(' '),
					background: `linear-gradient(90deg, ${this.dataConfig.moduleColor.color[0].item} 0%, ${this.dataConfig.moduleColor.color[1].item} 100%)`,
				};
			},
			searchStyle() {
				return {
					background: this.dataConfig.searchBoxColor.color[0].item,
					color: this.dataConfig.tipColor.color[0].item,
				};
			},
			menuButtonStyle(){
				let res = wx.getMenuButtonBoundingClientRect();
				return {
					width: res.width + 'px',
				}
			}
		},
		mounted() {
			let that = this;
			that.hotWords = that.dataConfig.hotWords.list.filter(item => {
				if (item.val) {
					return item;
				}
			});
			uni.setStorageSync('hotList', that.hotWords);
			that.$store.commit('hotWords/setHotWord', that.hotWords);
			setTimeout(() => {
				// 获取小程序头部高度
				let info = uni.createSelectorQuery().in(this).select(".header");
				info.boundingClientRect(function(data) {
					that.marTop = data.height
				}).exec()
			}, 100)
		},
		methods: {
			goLink() {
				let url = this.dataConfig.linkConfig.value;
				this.$util.JumpPath(url);
			}
		}
	}
</script>

<style lang="scss" scoped>
	.serch-wrapper {

		&.center {
			justify-content: center;
		}
	}

	.title {
		margin-right: 30rpx;
		font-weight: 500;
		font-size: 30rpx;
		color: #333333;
	}

	.header {
		z-index: 30;
		position: fixed;
		left: 0;
		top: 0;
		width: 100%;

		.serch-wrapper {
			height: 96rpx;
			padding: 18rpx 30rpx;

			.logo {
				height: 60rpx;
				margin-right: 20rpx;

				image {
					width: 100%;
					height: 100%;
				}
			}
			.input {
				position: relative;
				flex: 1;
				.search {
					flex: 1;
					height: 60rpx;
					padding: 0 32rpx;
					border-radius: 30rpx;
					background: #F5F5F5;
					font-size: 28rpx;
					line-height: 32rpx;
				}

				.iconfont {
					margin-right: 16rpx;
					font-size: 32rpx;
				}

				.swiper {
					flex: 1;
					height: 32rpx;
				}

				.button {
					position: absolute;
					top: 4rpx;
					right: 4rpx;
					height: 52rpx;
					padding: 0 24rpx;
					border-radius: 26rpx;
					background: #E93323;
					font-weight: 500;
					line-height: 52rpx;
					font-size: 22rpx;
					color: #FFFFFF;
				}

				.button2 {
					margin-left: 20rpx;
					font-size: 30rpx;
					color: #E93323;
				}
			}
		}
	}
	.row-center uni-swiper-item, .row-center swiper-item{
		text-align: center;
	}
</style>