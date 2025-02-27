<template>
    <view>
        <view class="tips-box" :style="[fixedTop]" v-if="showTip">
            <view class="tips-content" :style="[contentStyle]" @click.stop="hideTip">
                <view class="span">
					<text>{{ tips }}</text>
					<text class="iconfont icon-ic_close2"></text> 
				</view>
                <view class="arrow" :style="[addStyle]"></view>
            </view>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            showTip: false,
            marginRight: '10px',
            fixedRight: 52
        };
    },
    mounted() {
        // #ifdef MP
        if (wx.canIUse('checkIsAddedToMyMiniProgram')) {
            this.checkIsAddedToMyMiniProgram();
        } else {
			this.showTip = true;
			setTimeout(() => {
				this.hideTip()
			}, this.duration * 1000);
        }
        // #endif
    },
    props: {
        tips: {
            type: String,
            default: '点击添加到我的小程序'
        },
        duration: {
            type: Number,
            default: 5
        },
        color: {
            type: String,
            default: '#FFFFFF'
        }
    },
    methods: {
        checkIsAddedToMyMiniProgram() {
            try {
                wx.checkIsAddedToMyMiniProgram({
                    success: (res) => {
                        if (res.added) {
                            this.showTip = false;
                        } else {
                            this.showTip = true;
							setTimeout(() => {
								this.hideTip()
							}, this.duration * 1000);
                        }
                    },
                    fail: () => {
                       this.showTip = true;
					   setTimeout(() => {
					   	this.hideTip()
					   }, this.duration * 1000);
                    }
                });
            } catch (error) {
                console.log('error: ', error);
            }
        },
        hideTip() {
            // #ifdef MP
            this.showTip = false;
            this.$emit('change');
            // #endif
        }
    },
    computed: {
        contentStyle() {
            return {
                backgroundColor: this.color,
                marginRight: this.marginRight
            };
        },
        fixedTop() {
            let res = wx.getMenuButtonBoundingClientRect();
            let sysHeight = wx.getSystemInfoSync().statusBarHeight;
            return {
                top: sysHeight + res.height + 8 + 'px'
            };
        },
        addStyle() {
            return {
                'border-color': 'transparent transparent ' + this.color + ' transparent',
                right: this.fixedRight + 'px'
            };
        }
    }
};
</script>

<style scoped>
.tips-box {
    position: fixed;
    right: 0;
    z-index: 99999;
    opacity: 0.8;
    display: flex;
    justify-content: flex-end;
    align-items: flex-end;
    flex-direction: column;
    width: 600rpx;
    animation: tips 1s linear infinite;
}

.tips-content {
    border-width: 0;
    margin-top: 20rpx;
    position: relative;
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18rpx 20rpx;
}

.span {
    color: #333333;
    font-size: 28rpx;
    font-weight: 400;
}
.icon-ic_close2{
	color: #aaa;
	font-size: 24rpx;
	padding-left: 8rpx;
}
.arrow {
    position: absolute;
    width: 0;
    height: 0;
    top: -38rpx;
    border-width: 20rpx;
    border-style: solid;
    display: block;
}

@keyframes tips {
    0% {
        opacity: 0.8;
    }

    50% {
        opacity: 1;
    }
}
</style>
