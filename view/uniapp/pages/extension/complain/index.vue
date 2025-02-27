<template>
	<view :style="colorStyle">
		<view class="px-20 mt-20 bg--w111-f5f5f5">
			<view v-if="confirm.length">
				<view class="cell bg--w111-fff mb-20 rd-16rpx p-24 flex justify-between"
					v-for="(item, index) in confirm" :key="index">
					<text class="relative text--w111-333 fs-28" :class="item.titleShow.val ? 'pl-16' : ''">
						<text class="asterisk" v-if="item.titleShow.val">*</text>
						{{ item.titleConfig.value }}
					</text>
					<!-- radio -->
					<view v-if="item.name == 'radios'" class="discount">
						<radio-group @change="(e) => radioChange(e, index ,item)" class="acea-row row-middle row-right">
							<label class="radio" v-for="(j, jindex) in item.wordsConfig.list" :key="jindex">
								<view class="acea-row row-middle">
									<!-- #ifndef MP -->
									<radio :value="jindex.toString()" :checked="j.show" />
									<!-- #endif -->
									<!-- #ifdef MP -->
									<radio :value="jindex" :checked="j.show" />
									<!-- #endif -->
									<view>{{ j.val }}</view>
								</view>
							</label>
						</radio-group>
					</view>
					<!-- checkbox -->
					<view v-if="item.name == 'checkboxs'" class="discount">
						<checkbox-group @change="checkboxChange($event, index, item)" class="acea-row row-middle row-right">
							<label class="radio" v-for="(j, jindex) in item.wordsConfig.list" :key="jindex">
								<view class="acea-row row-middle">
									<!-- #ifndef MP -->
									<checkbox :value="jindex.toString()" :checked="j.show" style="transform: scale(0.9)" />
									<!-- #endif -->
									<!-- #ifdef MP -->
									<checkbox :value="jindex" :checked="j.show" style="transform: scale(0.9)" />
									<!-- #endif -->
									<view>{{ j.val }}</view>
								</view>
							</label>
						</checkbox-group>
					</view>
					<!-- text -->
					<view v-if="item.name == 'texts' && item.valConfig.tabVal == 0" class="discount">
						<input type="text" :placeholder="item.tipConfig.value" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- number -->
					<view v-if="item.name == 'texts' && item.valConfig.tabVal == 4" class="discount">
						<input type="number" :placeholder="item.tipConfig.value" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- email -->
					<view v-if="item.name == 'texts' && item.valConfig.tabVal == 3" class="discount">
						<input type="text" :placeholder="item.tipConfig.value" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- data -->
					<view v-if="item.name == 'dates'" class="discount">
						<picker mode="date" :value="item.value" @change="bindDateChange($event, index)">
							<view class="acea-row row-between-wrapper">
								<view v-if="item.value == ''">{{ item.tipConfig.value }}</view>
								<view v-else>{{ item.value }}</view>
								<text class="iconfont icon-jiantou"></text>
							</view>
						</picker>
					</view>
					<!-- dateranges -->
					<view v-if="item.name == 'dateranges'" class="discount">
						<uni-datetime-picker v-model="item.value" type="daterange" @maskClick="maskClick">
							{{ item.value.length ? item.value[0] + ' - ' + item.value[1] : item.tipConfig.value }}
							<text class="iconfont icon-jiantou"></text>
						</uni-datetime-picker>
					</view>
					<!-- time -->
					<view v-if="item.name == 'times'" class="discount">
						<picker mode="time" :value="item.value" @change="bindTimeChange($event, index)" :placeholder="item.tipConfig.value">
							<view class="acea-row row-between-wrapper">
								<view v-if="item.value == ''">{{ item.tipConfig.value }}</view>
								<view v-else>{{ item.value }}</view>
								<text class="iconfont icon-jiantou"></text>
							</view>
						</picker>
					</view>
					<!-- timeranges -->
					<view v-if="item.name == 'timeranges'" class="discount acea-row row-between-wrapper" @click="getTimeranges(index)">
						<view v-if="item.value">{{ item.value }}</view>
						<view v-else>{{ item.tipConfig.value }}</view>
						<text class="iconfont icon-jiantou"></text>
					</view>
					<!-- select -->
					<view v-if="item.name == 'selects'" class="discount">
						<picker :value="item.value" :range="item.wordsConfig.list" @change="bindSelectChange($event, index, item)" range-key="val">
							<view class="acea-row row-between-wrapper">
								<view v-if="item.value == ''">请选择</view>
								<view v-else>{{ item.value }}</view>
								<text class="iconfont icon-jiantou"></text>
							</view>
						</picker>
					</view>
					<!-- city -->
					<view v-if="item.name == 'citys'" class="discount" @click="changeRegion(index)">
						<view class="acea-row row-middle row-right">
							<view class="city" v-if="item.value == ''">{{ item.tipConfig.value }}</view>
							<view class="city" v-else>{{ item.value }}</view>
							<text class="iconfont icon-jiantou"></text>
						</view>
					</view>
					<!-- id -->
					<view v-if="item.name == 'texts' && item.valConfig.tabVal == 2" class="discount">
						<input type="idcard" :placeholder="item.tipConfig.value" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- phone -->
					<view v-if="item.name == 'texts' && item.valConfig.tabVal == 1" class="discount">
						<input type="number" :placeholder="item.tipConfig.value" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- img -->
					<view v-if="item.name == 'uploadPicture'" class="flex-1">
						<view class="flex justify-end" v-if="item.value.length < 3">
							<view class="relative" v-for="(items, indexs) in item.value" :key="indexs">
								<image class="w-128 h-128 rd-12rpx ml-16" :src="items"></image>
								<view class="abs-rt w-32 h-32 bg--w111-bbb clear-btn flex-center fs-24 text--w111-fff" @click="DelPic(index, indexs)">
									<text class="iconfont icon-ic_close"></text>
								</view>
							</view>
							<view class="w-128 h-128 rd-12rpx bg--w111-f5f5f5 flex-col flex-center ml-16" v-if="item.value.length < item.numConfig.val" @tap="uploadpic(index)">
								<text class="iconfont icon-ic_camera fs-40"></text>
								<view class="fs-20 text--w111-333">上传图片</view>
							</view>
						</view>
						<view class="flex justify-end" v-else>
							<scroll-view scroll-x="true" scroll-with-animation class="white-nowrap vertical-middle w-508" show-scrollbar="false">
								<view class="w-full h-full flex">
									<view class="inline-block h-128 mr-12">
										<view
											class="w-128 h-128 rd-12rpx bg--w111-f5f5f5 ml-16 flex-col flex-center"
											v-if="item.value.length < item.numConfig.val"
											@tap="uploadpic(index)"
										>
											<text class="iconfont icon-ic_camera fs-40"></text>
											<view class="fs-20 text--w111-333">上传图片</view>
										</view>
									</view>
									<view class="inline-block mr-12 relative" v-for="(items, indexs) in item.value" :key="index">
										<image class="w-128 h-128 rd-12rpx" :src="items"></image>
										<view class="abs-rt w-32 h-32 bg--w111-bbb rd-rt-12rpx flex-center fs-24 text--w111-fff" @click="DelPic(index, indexs)">
											<text class="iconfont icon-ic_close"></text>
										</view>
									</view>
								</view>
							</scroll-view>
						</view>
					</view>
				</view>
			</view>
			<view class="mx-20">
				<view class="mt-52 h-72 flex-center rd-36px bg-color fs-26 text--w111-fff" @tap="confirmForm">确定</view>
			</view>
			<timeranges :isShow="isShow" :time="timeranges" @confrim="confrim" @cancel="cancels"></timeranges>
			<areaWindow ref="areaWindow" :display="display" :address="addressInfoArea" :cityShow="cityShow" @submit="OnAreaAddress" @changeClose="changeAddressClose"></areaWindow>
		</view>
	</view>
</template>

<script>
const CACHE_CITY = {};
// import { getComplainFormApi, complainConfirmApi } from "@/api/api.js";
import timeranges from '@/components/timeranges';
import areaWindow from '@/components/areaWindow';
import colors from "@/mixins/color";
import {Debounce} from '@/utils/validate.js';
import dayjs from '@/plugin/dayjs/dayjs.min.js';
const jsonData = {};
export default {
	data() {
		return {
			confirm:[],
			timeranges:[],
			isShow:false,
			display: false,
			addressInfoArea: [],
			cityShow: 2,
			timerangesIndex:0,
			newImg:[]
		};
	},
	mixins: [colors],
	components: {
		timeranges,
		areaWindow,
	},
	onLoad() {
		this.getForm();
	},
	methods:{
		confrim(e){
		  this.isShow = false;
		  this.confirm[this.timerangesIndex].value = e.time;
		  let arrayNew = [];
		  e.val.forEach(item=>{
		    arrayNew.push(Number(item))
		  })
		  this.timeranges = arrayNew;
		},
		cancels(){
			this.isShow = false;
		},
		OnAreaAddress(address){
		  let addr = '';
		  if (address.length==4){
			  addr = address[0].label+'/'+address[1].label+'/'+address[2].label+'/'+address[3].label;
		  }else if (address.length==3){
			 addr = address[0].label+'/'+address[1].label+'/'+address[2].label;
		  }else if(address.length==2){
			 addr = address[0].label+'/'+address[1].label;
		  }else{
			 addr = address[0].label;
		  }
		  this.confirm[this.timerangesIndex].value = addr;
		  CACHE_CITY[this.timerangesIndex] = address;
		},
		changeRegion(index){
		   if(!this.confirm[index].value){
			 this.addressInfoArea = [];
		   }
		   this.timerangesIndex = index;
		   this.cityShow = Number(this.confirm[index].valConfig.tabVal) + 1;
		   this.display = true;
		   if(CACHE_CITY[index]){
			 this.addressInfoArea = CACHE_CITY[index];
		   }
		},
		// 单选
		radioChange(e,index,item){
			this.confirm[index].value = item.wordsConfig.list[e.detail.value].val;
		},
		// 多选
		checkboxChange(e, index, item){
			let obj = e.detail.value;
			  let val = '';
			  item.wordsConfig.list.forEach((j,jindex)=>{
				  obj.forEach(x=>{
					  if(jindex == x){
					  	 val = val +(val?',':'') + j.val;
					  }
				  })
			  })
			this.confirm[index].value = val
		},
		// 关闭地址弹窗；
		changeAddressClose: function() {
		   this.display = false;
		},
		/**
		 * 删除图片
		 *
		 */
		DelPic: function(index, indexs) {
			let that = this,
				pic = this.confirm[index].value;
			that.confirm[index].value.splice(indexs, 1);
			that.$set(that.confirm[index], 'value', that.confirm[index].value);
		},

		/**
		 * 上传文件
		 *
		 */
		uploadpic: function(index) {
			let that = this;
			this.$util.uploadImageOne('upload/image', function(res) {
				that.newImg.push(res.data.url);
				that.$set(that.confirm[index], 'value', that.newImg);
			});
		},
		objToArr(data) {
			let obj = Object.keys(data);
			let m = obj.map(key => data[key]);
			return m;
		},
		getForm(){
			// getComplainFormApi().then(res=>{
				let confirm = this.objToArr(jsonData.value);
				console.log(jsonData.value);
				confirm.forEach((item, index, arr)=>{
					CACHE_CITY[index] = ''; //清空省市区
					if(item.name == 'texts'){
						if(item.defaultValConfig.value){
							item.value = item.defaultValConfig.value
						}else{
							item.value = ''
						}
					}else if(item.name == 'radios'){
						item.value = item.wordsConfig.list[0].val
					}else if(item.name == 'uploadPicture'){
						item.value = [];
					}else if(item.name == 'dateranges'){
						if(item.valConfig.tabVal==0){
							if(item.valConfig.tabData==0){
							let obj = dayjs(new Date(Number(new Date().getTime()))).format('YYYY-MM-DD');
							item.value = [obj,obj]
							}else{
							let data1 = dayjs(new Date(Number(new Date(item.valConfig.specifyDate[0]).getTime()))).format('YYYY-MM-DD');
							let data2 = dayjs(new Date(Number(new Date(item.valConfig.specifyDate[1]).getTime()))).format('YYYY-MM-DD');
							item.value = [data1,data2];
							}
						}else{
							item.value = [];
						}
					}else{
						if(['times','dates','timeranges'].indexOf(item.name) != -1){
						  if(item.valConfig.tabVal==0){
							  if(item.valConfig.tabData==0){
								if(item.name == 'times'){
								  item.value = dayjs(new Date(Number(new Date().getTime()))).format('HH:mm');
								}else if(item.name == 'dates'){
								  item.value = dayjs(new Date(Number(new Date().getTime()))).format('YYYY-MM-DD');
								}else{
								  let current = dayjs(new Date(Number(new Date().getTime()))).format('HH:mm');
								  item.value = current+' - '+current;
								}
							  }else{
								if(item.name == 'times' || item.name == 'dates'){
								  item.value = item.valConfig.specifyDate;
								}else{
								  item.value = item.valConfig.specifyDate[0]+' - '+item.valConfig.specifyDate[1];
								}
							  }
						  }else{
							item.value = '';
						  }
						}else{
						  item.value = ''; 
						}
					}
				})
				this.$set(this, 'confirm', confirm);
			// })
		},
		confirmForm: Debounce(function(){
			let that = this;
			for (var i = 0; i < that.confirm.length; i++) {
			  let data = that.confirm[i]
			  if (['radios'].indexOf(data.name) == -1 && (data.titleShow.val || (['uploadPicture','dateranges'].indexOf(data.name) == -1 && data.value && data.value.trim()))) {
			    if ((data.name === 'texts' && data.valConfig.tabVal == 0) || ['dates','times','selects','citys','checkboxs'].indexOf(data.name) != -1) {
			      if (!data.value || (data.value && !data.value.trim())) {
			        return that.$util.Tips({
			          title: `请填写${data.titleConfig.value}`
			        });
			      }
			    }
				if(data.name === 'timeranges'){
					if(!data.value){
						return that.$util.Tips({
						  title: `请选择${data.titleConfig.value}`
						});
					}
				}
				if (data.name === 'dateranges') {
				  if (!data.value.length) {
				    return that.$util.Tips({
				      title: `请选择${data.titleConfig.value}`
				    });
				  }
				}
			    if (data.name === 'texts' && data.valConfig.tabVal == 4) {
			      if (data.value <= 0) {
			        return that.$util.Tips({
			          title: `请填写大于0的${data.titleConfig.value}`
			        });
			      }
			    }
			    if (data.name === 'texts' && data.valConfig.tabVal == 3) {
			      if (!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(data.value)) {
			        return that.$util.Tips({
			          title: `请填写正确的${data.titleConfig.value}`
			        });
			      }
			    }
			    if (data.name === 'texts' && data.valConfig.tabVal == 1) {
			      if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(data.value)) {
			        return that.$util.Tips({
			          title: `请填写正确的${data.titleConfig.value}`
			        });
			      }
			    }
			
			    if (data.name === 'texts' && data.valConfig.tabVal == 2) {
			      if (!
			        /^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i
			        .test(data.value)) {
			        return that.$util.Tips({
			          title: `请填写正确的${data.titleConfig.value}`
			        });
			      }
			    }
			
			    if (data.name === 'uploadPicture') {
			      if (!data.value.length) {
			        return that.$util.Tips({
			          title: `请上传${data.titleConfig.value}`
			        });
			      }
			    }
			  }
			}
			let data = {
				system_form_id:1,
				value:this.confirm
			};
			complainConfirmApi(data).then(res=>{
				this.$util.Tips({
					title:res.msg
				},{
					tab:3
				})
			}).catch(err=>{
				this.$util.Tips({
					title:err
				})
			})
		}),
	}
}
</script>

<style lang="scss">
/deep/.uni-date-x--border {
    border: 0;
}
/deep/.uni-icons {
    font-size: 0 !important;
}
/deep/.uni-date-x {
    color: #999;
    font-size: 15px;
}
/deep/.uni-date__x-input {
    font-size: 15px;
}
.cell input{
	width: 450rpx;
	text-align:right;
}
.cell .radio {
	margin: 0 22rpx;
	padding: 10rpx 0;
}
.placeholder {
	color: #ccc;
}
.asterisk{
	position: absolute;
	color:red;
	left:0;
}
</style>
