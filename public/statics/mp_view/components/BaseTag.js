(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/BaseTag"],{"41bf":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var r={name:"tag",props:{size:{type:String,default:"normal"},text:{type:String,default:""},circle:{type:[Boolean,String],default:!1},background:{type:String,default:"#e93323"},color:{type:String,default:"#ffffff"},borderColor:{type:String,default:""},imgSrc:{type:String,default:""}},computed:{tagStyle:function(){return{background:this.background,color:this.color,border:this.borderColor?"1rpx solid ".concat(this.borderColor):"none"}}}};e.default=r},"91e8":function(t,e,n){"use strict";n.d(e,"b",(function(){return r})),n.d(e,"c",(function(){return o})),n.d(e,"a",(function(){}));var r=function(){var t=this.$createElement,e=(this._self._c,this.imgSrc?null:this.__get_style([this.tagStyle]));this.$mp.data=Object.assign({},{$root:{s0:e}})},o=[]},b3d7:function(t,e,n){"use strict";n.r(e);var r=n("41bf"),o=n.n(r);for(var a in r)["default"].indexOf(a)<0&&function(t){n.d(e,t,(function(){return r[t]}))}(a);e["default"]=o.a},c41a:function(t,e,n){},ddf8:function(t,e,n){"use strict";n.r(e);var r=n("91e8"),o=n("b3d7");for(var a in o)["default"].indexOf(a)<0&&function(t){n.d(e,t,(function(){return o[t]}))}(a);n("ed02");var i=n("828b"),u=Object(i["a"])(o["default"],r["b"],r["c"],!1,null,null,null,!1,r["a"],void 0);e["default"]=u.exports},ed02:function(t,e,n){"use strict";var r=n("c41a"),o=n.n(r);o.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/BaseTag-create-component',
    {
        'components/BaseTag-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("ddf8"))
        })
    },
    [['components/BaseTag-create-component']]
]);
