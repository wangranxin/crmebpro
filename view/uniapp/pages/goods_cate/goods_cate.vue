<template>
  <!-- 商品分类 -->
	<view :style="colorStyle">
    <!-- 商品分类的三种样式布局 -->
		<template1 v-if="activeIndex == 0" :level="level" ref="classOne"></template1>
		<template2 v-if="showTemplateTwo"  :level="level" :showType="activeIndex" :isFooter="isFooter" ref="classTwo"></template2>
		<template3 v-if="showTemplateThree" :showType="activeIndex" :isFooter="isFooter" ref="classThree"></template3>
		<template4 v-if="showTemplateFour"  :level="level" :isFooter="isFooter" @toggleBar="toggleBar" ref="classFour"></template4>
		<pageFooter @newDataStatus="newDataStatus" v-show="showBar"></pageFooter>
	</view>
</template>

<script>
	import colors from "@/mixins/color";
	import template1 from './template/template1.vue';
	import template2 from './template/template2.vue';
	import template3 from './template/template3.vue';
	import template4 from './template/template4.vue';
	import pageFooter from '@/components/pageFooter/index.vue'
	import { colorChange } from '@/api/api.js';
	import { mapGetters } from 'vuex';
	export default {
		computed: {
			...mapGetters(['isLogin', 'uid', 'diyCategory']),
			//diyCategory 暴露两个属性 level 和 index ,分别为分类等级和模板下标
			level(){
				return this.diyCategory.level
			},
			activeIndex(){
				return this.diyCategory.index
			},
			showTemplateTwo(){
				if(([2,3].includes(this.activeIndex) && this.level == 2) || ([1,2].includes(this.activeIndex) && this.level == 3)){
					return true
				}
			},
			showTemplateThree(){
				if([1,4].includes(this.activeIndex) && this.level == 2){
					return true
				}
			},
			showTemplateFour(){
				if((this.activeIndex == 5 && this.level == 2) || (this.activeIndex == 3 && this.level == 3)){
					return true
				}
			}
		},
		components: {
			template1,
			template2,
			template3,
			template4,
			pageFooter
		},
		mixins: [colors],
		data() {
			return {
				isFooter:false,
				showBar: false
			}
		},
		onUnload(){
			uni.$off('newAttrNum')
		},
		methods: {
			newDataStatus(val){
				this.isFooter = val ? true : false;
				this.showBar = val ? true : false;
			},
			toggleBar(val){
				this.showBar = val;
			}
		},
		onPageScroll(e) {
			if(this.showTemplateFour){
				uni.$emit('scroll');
			}
		},
		onReachBottom() {
			if(this.showTemplateFour){
				this.$refs.classFour.productslist();
			}

		}
	}
</script>
