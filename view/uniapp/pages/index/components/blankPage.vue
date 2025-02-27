<template>
	<!-- 辅助空白 -->
	<view v-show="!dataConfig.isHide">
		<view class="blankPage" :style="[blankWrapStyle]">
			<view class="bankCon" :style="[blankStyle]"></view>
		</view>
	</view>

</template>

<script>
	export default {
		name: 'blankPage',
		props: {
			dataConfig: {
				type: Object,
				default: () => {}
			},
			isSortType: {
				type: String | Number,
				default: 0
			}
		},
		data() {
			return {
				bgColor: '',
				heightConfig: 0
			};
		},
		computed: {
			blankWrapStyle() {
				return {
					'padding': `${this.dataConfig.topConfig.val * 2}rpx ${this.dataConfig.lrEdge.val * 2}rpx ${this.dataConfig.bottomConfig.val * 2}rpx`,
					'background': this.dataConfig.bottomBgColor.color[0].item,
				};
			},
			blankStyle() {
				let borderRadius = [];
				if (this.dataConfig.fillet.type) {
					for (let i = 0; i < this.dataConfig.fillet.valList.length; i++) {
						borderRadius.push(`${this.dataConfig.fillet.valList[i].val * 2}rpx`);
					}
				} else {
					borderRadius = [`${this.dataConfig.fillet.val * 2}rpx`];
				}
				return {
					'height': `${this.dataConfig.heightConfig.val * 2}rpx`,
					'border-radius': borderRadius.join(' '),
					'background': this.dataConfig.bgColor.color[0].item,
				};
			},
		},
		created() {},
		methods: {

		}
	}
</script>

<style lang="scss">
	.blankPage {
		.bankCon {
			width: 100%;
			height: 100%;
			background-color: blue;
		}
	}
</style>