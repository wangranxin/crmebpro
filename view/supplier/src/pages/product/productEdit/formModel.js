export const defaultObj = {
  supplier_id: 0, //供应商
  is_limit: 0, //是否限购开关
  limit_type: 1, //1单次限购，2长期限购
  limit_num: 1, //限购数量
  disk_info: "", //卡密简介
  auto_on_time: "",
  video_open: false, //视频按钮是否显示
  store_name: "",
  freight: 1, //运费设置
  postage: 0, //设置运费金额
  custom_form: [], //自定义留言
  system_form_id: 0, //自定义表单id
  cate_id: [],
  store_label_id:[],
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
  sort: 0,
  is_show: 0,
  is_hot: 0,
  is_benefit: 0,
  is_best: 0,
  is_new: 0,
  is_good: 0,
  is_postage: 0,
  id: 0,
  spec_type: 0,
  video_link: "",
  temp_id: "",
  attr:{
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
  header: [],
  selectRule: "",
  coupon_ids: [],
  command_word: "",
  delivery_type: ["1"],
  specs: [],
  recommend_list: [],
  brand_id: [],
  product_type: 0,
};

export const GoodsTableHead = [
  {
    title: "图片",
    slot: "pic",
    align: "center",
    minWidth: "80px",
  },
  {
    title: "结算价",
    slot: "settle_price",
    align: "center",
    minWidth: "120px",
  },
  {
    title: "库存",
    slot: "stock",
    align: "center",
    minWidth: "120px",
  },
  {
    title: "商品编号",
    slot: "code",
    align: "center",
    minWidth: "120px",
  },
  {
    title: "商品条形码",
    slot: "bar_code",
    align: "center",
    minWidth: "120px",
  },
  {
    title: "重量（KG）",
    slot: "weight",
    align: "center",
    minWidth: "100px",
  },
  {
    title: "体积(m³)",
    slot: "volume",
    align: "center",
    minWidth: "100px",
  },
  {
    title: "默认选中规格",
    slot: "selected_spec",
    fixed: "right",
    align: "center",
    minWidth: "100px",
  },
  {
    title: "操作",
    slot: "action",
    fixed: "right",
    align: "center",
    minWidth: "120px",
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
