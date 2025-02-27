export const defaultObj = {
  is_presale_product: 0, //预售商品开关
  is_limit: 0, //是否限购开关
  limit_type: 1, //1单次限购，2长期限购
  limit_num: 1, //限购数量
  is_vip_product: 0, //付费会员专属开关
  is_support_refund: 1,
  disk_info: "", //卡密简介
  presale_day: 1, //预售发货时间-结束
  presale_time: [],
  auto_on_time: "",
  auto_off_time: "",
  video_open: false, //视频按钮是否显示
  store_name: "",
  freight: 1, //运费设置
  postage: 0, //设置运费金额
  custom_form: [], //自定义留言
  cate_id: [],
  label_id: [],
  ensure_id: [],
  keyword: "",
  unit_name: "",
  specs_id: 0,
  store_info: "",
  bar_code: "",
  code: "",
  image: "",
  recommend_image: "",
  slider_image: [],
  description: "",
  ficti: 0,
  give_integral: 0,
  sort: 0,
  is_show: 1,
  is_hot: 0,
  is_benefit: 0,
  is_best: 0,
  is_new: 0,
  is_good: 0,
  is_postage: 0,
  is_sub: [],
  id: 0,
  spec_type: 0,
  video_link: "",
  temp_id: "",
  attr: {
    pic: "",
    price: 0,
    settle_price: 0,
    cost: 0,
    ot_price: 0,
    stock: 0,
    bar_code: "",
    code: "",
    weight: 0,
    volume: 0,
    brokerage: 0,
    brokerage_two: 0,
    vip_price: 0,
    virtual_list: [],
    write_times: 0, //核销次数
    write_valid: 1, //核销时效
    days: 1,
  },
  attrs: [],
  items: [
    {
      pic: "",
      price: 0,
      cost: 0,
      ot_price: 0,
      stock: 0,
      bar_code: "",
      code: "",
    },
  ],
  coupons: [],
  couponName: [],
  header: [],
  selectRule: "",
  coupon_ids: [],
  command_word: "",
  delivery_type: ["1"],
  specs: [],
  recommend_list: [],
  brand_id: [],
  product_type: 0,
  store_label_id: [],
  off_show: 0,
  header: [],
  min_qty: 1, //起购数量
  share_content: "", //分销文案
  presale_status: 1,
  system_form_id: ""
};

export const GoodsTableHead = [
  {
    title: '图片',
    slot: 'pic',
    align: 'center',
    minWidth: '80px',
  },
  {
    title: '售价',
    slot: 'price',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '成本价',
    slot: 'cost',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '划线价',
    slot: 'ot_price',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '库存',
    slot: 'stock',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '商品编号',
    slot: 'code',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '商品条形码',
    slot: 'bar_code',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '重量（KG）',
    slot: 'weight',
    align: 'center',
    minWidth: '100px',
  },
  {
    title: '体积(m³)',
    slot: 'volume',
    align: 'center',
    minWidth: '100px',
  },
  {
    title: '默认选中规格',
    slot: 'selected_spec',
    fixed: 'right',
    align: 'center',
    minWidth: '100px',
  },
  {
    title: '操作',
    slot: 'action',
    fixed: 'right',
    align: 'center',
    minWidth: '120px',
  },
];
//   虚拟商品
export const VirtualTableHead = [
  {
    title: '图片',
    slot: 'pic',
    align: 'center',
    minWidth: 80,
  },
  {
    title: '售价',
    slot: 'price',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '成本价',
    slot: 'cost',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '划线价',
    slot: 'ot_price',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '库存',
    slot: 'stock',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '产品编号',
    slot: 'code',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '商品条形码',
    slot: 'bar_code',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '默认选中规格',
    slot: 'selected_spec',
    fixed: 'right',
    align: 'center',
    minWidth: 90,
  },
  {
    title: '操作',
    slot: 'action',
    fixed: 'right',
    align: 'center',
    minWidth: 120,
  },
];

//   虚拟商品
export const VirtualTableHead2 = [
  {
    title: '图片',
    slot: 'pic',
    align: 'center',
    minWidth: 80,
  },
  {
    title: '售价',
    slot: 'price',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '成本价',
    slot: 'cost',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '划线价',
    slot: 'ot_price',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '库存',
    slot: 'stock',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '产品编号',
    slot: 'code',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '商品条形码',
    slot: 'bar_code',
    align: 'center',
    minWidth: '120px',
  },
  {
    title: '添加卡密/网盘',
    slot: 'fictitious',
    align: 'center',
    minWidth: 120,
  },
  {
    title: '默认选中规格',
    slot: 'selected_spec',
    fixed: 'right',
    align: 'center',
    minWidth: 90,
  },
  {
    title: '操作',
    slot: 'action',
    fixed: 'right',
    align: 'center',
    minWidth: 120,
  },
];

export const columns2 = [
  {
    title: "图片",
    slot: "pic",
    align: "center",
    minWidth: 80,
  },
  {
    title: "售价",
    slot: "price",
    align: "center",
    minWidth: 95,
  },
  {
    title: "成本价",
    slot: "cost",
    align: "center",
    minWidth: 95,
  },
  {
    title: "划线价",
    slot: "ot_price",
    align: "center",
    minWidth: 95,
  },
];