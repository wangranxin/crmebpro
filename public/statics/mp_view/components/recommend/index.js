(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/recommend/index"],{"7eeb":function(t,e,n){"use strict";n.r(e);var o=n("7efe"),a=n.n(o);for(var u in o)["default"].indexOf(u)<0&&function(t){n.d(e,t,(function(){return o[t]}))}(u);e["default"]=a.a},"7efe":function(t,e,n){"use strict";(function(t){var o=n("47a9");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var a=n("8f59"),u=n("95b5"),i=o(n("89cc")),c={props:{hostProduct:{type:Array,default:function(){return[]}},title:{type:String,default:"猜你喜欢"}},components:{waterfallsFlow:function(){n.e("components/WaterfallsFlow/WaterfallsFlow").then(function(){return resolve(n("33ab"))}.bind(null,n)).catch(n.oe)}},mixins:[i.default],computed:(0,a.mapGetters)(["uid"]),methods:{goDetail:function(e){(0,u.goShopDetail)(e,this.uid).catch((function(n){t.navigateTo({url:"/pages/goods_details/index?id=".concat(e.id)})}))}}};e.default=c}).call(this,n("df3c")["default"])},a60c:function(t,e,n){"use strict";n.r(e);var o=n("df7f"),a=n("7eeb");for(var u in a)["default"].indexOf(u)<0&&function(t){n.d(e,t,(function(){return a[t]}))}(u);var i=n("828b"),c=Object(i["a"])(a["default"],o["b"],o["c"],!1,null,null,null,!1,o["a"],void 0);e["default"]=c.exports},df7f:function(t,e,n){"use strict";n.d(e,"b",(function(){return o})),n.d(e,"c",(function(){return a})),n.d(e,"a",(function(){}));var o=function(){var t=this.$createElement;this._self._c},a=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/recommend/index-create-component',
    {
        'components/recommend/index-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("a60c"))
        })
    },
    [['components/recommend/index-create-component']]
]);
