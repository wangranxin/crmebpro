<template>
	<!-- 悬浮导航按钮 -->
	<view :style="colorStyle">
		<view style="touch-action: none;">
			<view class="home" style="position:fixed;" :style="{ top: top + 'px'}" id="right-nav" @touchmove.stop.prevent="setTouchMove">
				<view @click="open" class="pictrueBox">
					<view class="pictrue">
						<image :src="
		          homeActive === true
		            ? imgHost + '/statics/images/close.gif'
		            : imgHost + '/statics/images/open.gif'
		        " class="image" />
					</view>
				</view>
				<view class="homeCon bg-color" :class="homeActive === true ? 'on' : ''" v-if="homeActive">
					<navigator hover-class='none' open-type="navigate" url='/pages/admin/work/index' class='iconfont icon-ic_staging'>
					</navigator>
					<navigator hover-class='none' open-type="navigate" url='/pages/admin/goods/index' class='iconfont icon-ic_commodity'></navigator>
					<navigator hover-class='none' open-type="navigate" url='/pages/admin/orderList/index' class='iconfont icon-ic_order'></navigator>
					<navigator hover-class='none' open-type="navigate" url='/pages/admin/user/list' class='iconfont icon-ic_user1'></navigator>
					<slot name="bottom"></slot>
				</view>
			</view>
		</view>
	</view>
</template>
<script>
	import {
		mapGetters
	} from "vuex";
	import colors from '@/mixins/color.js';
	import {
		HTTP_REQUEST_URL
	} from '@/config/app';
	export default {
		name: "Home",
		props: {},
		mixins: [colors],
		data: function() {
			return {
				top: "545",
				imgHost: HTTP_REQUEST_URL
			};
		},
		computed: mapGetters(["homeActive"]),
		methods: {
			setTouchMove(e) {
				var that = this;
				if (e.touches[0].clientY < 545 && e.touches[0].clientY > 66) {
					that.top = e.touches[0].clientY
					// that.setData({
					// 	top: e.touches[0].clientY
					// })
				}
			},
			open: function() {
				this.homeActive ?
					this.$store.commit("CLOSE_HOME") :
					this.$store.commit("OPEN_HOME");
			}
		},
		created() {},
		beforeDestroy() {
			this.$store.commit("CLOSE_HOME")
		}
	};
</script>

<style scoped lang="scss">
	.pictrueBox {
		width: 130rpx;
		height: 120rpx;
	}

	/*返回主页按钮*/
	.home {
		position: fixed;
		color: white;
		text-align: center;
		z-index: 9999;
		left: 15rpx;
		display: flex;
	}

	.home .homeCon {
		border-radius: 50rpx;
		opacity: 0;
		height: 0;
		width: 0;
	}

	.home .homeCon.on {
		opacity: 1;
		animation: bounceInLeft 0.5s cubic-bezier(0.215, 0.610, 0.355, 1.000);
		width: 300rpx;
		height: 86rpx;
		margin-bottom: 20rpx;
		display: flex;
		justify-content: center;
		align-items: center;
		/* background: var(--view-theme) !important; */
		background: $primary-admin !important;
		/* border: 1px solid #fff; */
	}

	.home .homeCon .iconfont {
		font-size: 40rpx;
		color: #fff;
		display: inline-block;
		margin: 0 auto;
	}

	.home .pictrue {
		width: 86rpx;
		height: 86rpx;
		border-radius: 50%;
		margin: 0 auto;
		background: $primary-admin;
		/* border: 1px solid #fff; */
	}

	.home .pictrue .image {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		transform: rotate(-90deg);
		ms-transform: rotate(-90deg);
		moz-transform: rotate(-90deg);
		webkit-transform: rotate(-90deg);
		o-transform: rotate(-90deg);
	}
</style>