<template>
  <div class="Modal">
    <Row>
      <Col span="24">
        <Tabs v-model="uploadName.file_type" @on-click="onhangeTab">
          <TabPane
            v-for="(item, index) in headTab"
            :key="index"
            :label="item.title"
            :name="item.name"
            :disabled="!!fileType && fileType !== item.name"
          ></TabPane>
        </Tabs>
      </Col>
    </Row>
    <Row>
      <Col span="6">
        <div class="input">
          <Input
            placeholder="请输入分类名称"
            v-model="uploadName.name"
            style="width: 90%"
            @on-search="changePage"
            search
          />
        </div>
      </Col>
      <Col span="18">
        <Button
          :disabled="checkPicList.length === 0"
          @click="checkPics"
          class="mr10"
          v-if="isShow !== 0"
          style="width: 100px"
          >{{
            uploadName.file_type == 1 ? "使用选中图片" : "使用选中视频"
          }}</Button
        >
        <!-- <Upload
          :show-upload-list="false"
          :action="fileUrl"
          class="mr10"
          :before-upload="beforeUpload"
          :data="uploadData"
          :headers="header"
          :multiple="true"
          :on-success="handleSuccess"
          style="margin-top: 1px; display: inline-block"
          v-if="uploadName.file_type==1"
        >
          <Button>上传图片</Button>
        </Upload> -->
        <Button
          class="mr10"
          v-if="uploadName.file_type == 1"
          @click="openUpload"
          >上传图片</Button
        >
        <Button class="mr10" v-else @click="uploadVideo">上传视频</Button>
        <!--<Button type="success" @click.stop="add" class="mr10">添加分类</Button>-->
        <Button
          class="mr10"
          :disabled="checkPicList.length === 0"
          @click.stop="editPicList('图片')"
          >{{ uploadName.file_type == 1 ? "删除图片" : "删除视频" }}</Button
        >
        <i-select
          :value="pids"
          :placeholder="uploadName.file_type == 1 ? '图片移动至' : '视频移动至'"
          style="width: 180px"
          class="treeSel mr10"
        >
          <i-option
            v-for="(item, index) of list"
            :value="item.value"
            :key="index"
            style="display: none"
          >
            {{ item.title }}
          </i-option>
          <Tree
            :data="treeData2"
            :render="renderContentSel"
            ref="reference"
            :load-data="loadData"
            class="selectTree"
          ></Tree>
        </i-select>
        <Input
          search
          placeholder="搜索图片名称"
          v-model="fileName"
          @on-search="pageChange(1)"
          style="width: 200px"
        />
      </Col>
    </Row>
    <Row class="colLeft">
      <Col span="6" class="colLeft left">
        <div class="Nav">
          <div class="trees-coadd">
            <div class="scollhide">
              <div class="trees">
                <Tree
                  :data="treeData"
                  :render="renderContent"
                  :load-data="loadData"
                  class="treeBox"
                  ref="tree"
                >
                </Tree>

                <div
                  class="searchNo"
                  v-if="searchClass && treeData.length <= 1"
                >
                  此分类暂无数据
                </div>
              </div>
            </div>
          </div>
        </div>
      </Col>
      <Col span="18" class="colLeft right">
        <div class="conter">
          <div class="pictrueList acea-row">
            <Row :gutter="24" class="conter">
              <div v-show="isShowPic" class="imagesNo">
                <Icon type="ios-images" size="60" color="#dbdbdb" />
                <span class="imagesNo_sp">{{
                  uploadName.file_type == 1 ? "图片库为空" : "视频库为空"
                }}</span>
              </div>
              <div class="acea-row">
                <div
                  class="pictrueList_pic"
                  v-for="(item, index) in pictrueList"
                  :key="index"
                  @mouseenter="enterLeave(item)"
                  @mouseleave="enterLeave(item)"
                >
                  <p class="number" v-if="item.num > 0">
                    <Badge :count="item.num" type="error" :offset="[11, 12]">
                      <a href="#" class="demo-badge"></a>
                    </Badge>
                  </p>
                  <div class="picimage">
                    <img
                      v-if="uploadName.file_type == 1"
                      :class="item.isSelect ? 'on' : ''"
                      v-lazy="item.satt_dir"
                      @click.stop="changImage(item, index, pictrueList)"
                    />
                    <video
                      v-else
                      :autoplay="false"
                      :controls="false"
                      :class="item.isSelect ? 'on' : ''"
                      :src="item.att_dir"
                      @click.stop="changImage(item, index, pictrueList)"
                    ></video>
                  </div>
                  <div class="picName">
                    <p v-if="!item.isEdit">
                      {{ item.editName }}
                    </p>
                    <Input
                      size="small"
                      type="text"
                      v-model="item.real_name"
                      v-else
                      @on-blur="bindTxt(item)"
                    />
                    <div class="picMenu">
                      <ButtonGroup>
                        <Button
                          size="small"
                          @click="item.isEdit = !item.isEdit"
                        >
                          <Icon type="ios-create" />
                        </Button>
                        <Button
                          size="small"
                          class="preview"
                          @click="openView(item)"
                        >
                          <Icon type="ios-eye" />
                        </Button>
                        <Button size="small" @click="editPicList(item.att_id)">
                          <Icon type="ios-trash" />
                        </Button>
                      </ButtonGroup>
                    </div>
                  </div>
                </div>
              </div>
            </Row>
          </div>
          <div class="nameStyle" v-show="openImgShow">
            <img v-if="uploadName.file_type == 1" :src="viewImg" />
            <div v-else-if="uploadName.file_type == 2" id="player"></div>
          </div>
          <div class="footer acea-row row-right">
            <Page
              :total="total"
              show-elevator
              show-total
              @on-change="pageChange"
              :current="fileData.page"
              :page-size="fileData.limit"
            />
          </div>
        </div>
      </Col>
    </Row>
    <Modal
      v-model="modalVideo"
      width="1024px"
      scrollable
      footer-hide
      closable
      title="上传视频"
      :mask-closable="false"
      :z-index="9"
    >
      <uploadVideo @getVideo="getvideo" :pid="fileData.pid"></uploadVideo>
    </Modal>
    <!-- 上传图片 -->
    <!-- <uploadImg v-model="uploadVisible" :categories="treeData" :category-id="treeId" @success="uploadSuccess"></uploadImg> -->
  </div>
</template>

<script>
import {
  getCategoryListApi,
  createApi,
  fileListApi,
  categoryEditApi,
  moveApi,
  fileUpdateApi,
} from "@/api/uploadPictures";
import Setting from "@/setting";
// import { getCookies } from "@/libs/util";
import util from "@/libs/util";
import uploadVideo from "@/components/uploadVideos";
export default {
  name: "uploadPictures",
  components: { uploadVideo },
  props: {
    isChoice: {
      type: String,
      default: "",
    },
    gridBtn: {
      type: Object,
      default: null,
    },
    gridPic: {
      type: Object,
      default: null,
    },
    isShow: {
      type: Number,
      default: 1,
    },
    fileType: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      searchClass: false,
      spinShow: false,
      fileUrl: Setting.apiBaseURL + "/file/upload",
      modalPic: false,
      treeData: [],
      treeData2: [],
      pictrueList: [],
      uploadData: {}, // 上传参数
      checkPicList: [],
      uploadName: {
        name: "",
        file_type: "1",
      },
      FromData: null,
      treeId: 0,
      isJudge: false,
      buttonProps: {
        type: "default",
        size: "small",
      },
      fileData: {
        pid: 0,
        page: 1,
        limit: 18,
      },
      total: 0,
      pids: 0,
      list: [],
      modalTitleSs: "",
      isShowPic: false,
      header: {},
      ids: [], // 选中附件的id集合
      viewImg: "",
      openImgShow: false,
      headTab: [{ title: "图片", name: "1" }, { title: "视频", name: "2" }],
      modalVideo: false,
      fileName: "",
      // uploadVisible: false,
      // uploadList:[]
    };
  },
  watch: {
    pictrueList() {
      this.checkPicList = this.ids = [];
    },
  },
  mounted() {
    this.uploadName.file_type = this.fileType || "1";
    this.getToken();
    this.getList();
    this.getFileList();
    document.addEventListener(
      "click",
      (event) => {
        if (
          !document.querySelector(".nameStyle").contains(event.target) &&
          !event.target.classList.contains("ivu-icon-ios-eye") &&
          !event.target.classList.contains("preview")
        ) {
          this.openImgShow = false;
          this.viewImg = "";
          if (this.player) {
            this.player.dispose();
            this.player = null;
          }
        }
      },
      true
    );
    window.addEventListener("message", (e) => {
      if (e.data.type == "uploadImgSuccess") {
        this.fileData.page = 1;
        this.getFileList();
      }
    });
  },
  methods: {
    createPoster(el) {
      new Promise((resolve, reject) => {
        let video = document.createElement("video");
        video.setAttribute("src", el.att_dir);
        video.setAttribute("crossOrigin", "anonymous");
        video.setAttribute("width", 100);
        video.setAttribute("height", 100);
        video.setAttribute("preload", "auto");
        video.addEventListener("canplay", () => {
          let canvas = document.createElement("canvas");
          let context = canvas.getContext("2d");
          let width = video.width;
          let height = video.height;
          canvas.width = width;
          canvas.height = height;
          context.drawImage(video, 0, 0, width, height);
          resolve(canvas.toDataURL("image/jpeg"));
        });
      }).then((url) => {
        el.poster = url;
      });
    },
    createPlayer(item) {
      if (this.player) {
        this.player.dispose();
        this.player = null;
      }
      this.player = new Aliplayer({
        id: "player",
        width: "100%",
        height: "100%",
        autoplay: true,
        source: item.att_dir,
      });
    },
    uploadVideo() {
      this.modalVideo = true;
    },
    getvideo() {
      this.modalVideo = false;
      this.fileData.page = 1;
      this.getFileList();
    },
    onhangeTab() {
      this.treeId = 0;
      this.getList();
      this.getFileList();
      this.checkPicList = [];
    },
    enterMouse(item) {
      item.realName = !item.realName;
    },
    enterLeave(item) {
      item.isShowEdit = !item.isShowEdit;
    },
    // 上传头部token
    getToken() {
      this.header["Authori-zation"] = "Bearer " + util.cookies.get("token");
    },
    // 树状图
    renderContent(h, { root, node, data }) {
      let operate = [];
      if (data.pid == 0) {
        operate.push(
          h(
            "div",
            {
              class: ["ivu-dropdown-item"],
              on: {
                click: () => {
                  this.append(root, node, data);
                },
              },
            },
            "添加分类"
          )
        );
      }
      if (data.id) {
        operate.push(
          h(
            "div",
            {
              class: ["ivu-dropdown-item"],
              on: {
                click: () => {
                  this.editPic(root, node, data);
                },
              },
            },
            "编辑分类"
          ),
          h(
            "div",
            {
              class: ["ivu-dropdown-item"],
              on: {
                click: () => {
                  this.remove(root, node, data, "分类");
                },
              },
            },
            "删除分类"
          )
        );
      }
      return h(
        "span",
        {
          class: ["ivu-span"],
          style: {
            display: "inline-block",
            width: "88%",
            height: "32px",
            lineHeight: "32px",
            position: "relative",
            color: "rgba(0,0,0,0.6)",
            cursor: "pointer",
          },
          on: {
            mouseenter: () => {
              this.onMouseOver(root, node, data);
            },
            mouseleave: () => {
              this.onMouseOver(root, node, data);
            },
          },
        },
        [
          h(
            "span",
            {
              on: {
                click: (e) => {
                  this.appendBtn(root, node, data, e);
                },
              },
            },
            data.title
          ),
          h(
            "div",
            {
              style: {
                display: "inline-block",
                float: "right",
              },
            },
            [
              h("Icon", {
                props: {
                  type: "ios-more",
                },
                style: {
                  marginRight: "8px",
                  fontSize: "20px",
                  display: data.flag ? "inline" : "none",
                },
                on: {
                  click: () => {
                    this.onClick(root, node, data);
                  },
                },
              }),
              h(
                "div",
                {
                  class: ["right-menu ivu-poptip-inner"],
                  style: {
                    width: "80px",
                    position: "absolute",
                    zIndex: "9",
                    top: "32px",
                    right: "12px",
                    display: data.flag2 ? "block" : "none",
                  },
                },
                operate
              ),
            ]
          ),
        ]
      );
    },

    renderContentSel(h, { root, node, data }) {
      return h(
        "div",
        {
          style: {
            display: "inline-block",
            width: "90%",
          },
        },
        [
          h("span", [
            h(
              "span",
              {
                style: {
                  cursor: "pointer",
                },
                class: ["ivu-tree-title"],
                on: {
                  click: (e) => {
                    this.handleCheckChange(root, node, data, e);
                  },
                },
              },
              data.title
            ),
          ]),
        ]
      );
    },
    // 下拉树
    handleCheckChange(root, node, data, e) {
      this.list = [];
      // this.pids = 0;
      let value = data.id;
      let title = data.title;
      this.list.push({
        value,
        title,
      });
      if (this.ids.length) {
        this.pids = value;
        this.getMove();
      } else {
        this.$Message.warning("请先选择图片");
      }
      let selected = this.$refs.reference.$el.querySelectorAll(
        ".ivu-tree-title-selected"
      );
      for (let i = 0; i < selected.length; i++) {
        selected[i].className = "ivu-tree-title";
      }
      e.path[0].className = "ivu-tree-title"; // 当前点击的元素
    },
    // 移动分类
    getMove() {
      let data = {
        pid: this.pids,
        images: this.ids.toString(),
      };
      moveApi(data)
        .then(async (res) => {
          this.$Message.success(res.msg);
          this.getFileList();
          this.pids = 0;
          this.checkPicList = [];
          this.ids = [];
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 删除图片
    editPicList(tit) {
      let ids = {
        ids: this.ids.toString(),
      };
      if (typeof tit == "number") {
        ids = {
          ids: tit.toString(),
        };
      }
      let delfromData = {
        title: this.uploadName.file_type == 1 ? "删除选中图片" : "删除选中视频",
        url: `file/file/delete`,
        method: "POST",
        ids: ids,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getFileList();
          this.checkPicList = [];
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 鼠标移入 移出
    onMouseOver(root, node, data) {
      event.preventDefault();
      data.flag = !data.flag;
      if (data.flag2) {
        data.flag2 = false;
      }
    },
    onClick(root, node, data) {
      data.flag2 = !data.flag2;
    },
    // 点击树
    appendBtn(root, node, data, e) {
      this.treeId = data.id ? data.id : 0;
      this.fileData.page = 1;
      this.getFileList();
      let selected = this.$refs.tree.$el.querySelectorAll(
        ".ivu-tree-title-selected"
      );
      for (let i = 0; i < selected.length; i++) {
        selected[i].className = "ivu-tree-title";
      }
      e.path[0].className = "ivu-tree-title"; // 当前点击的元素
    },
    // 点击添加
    append(root, node, data) {
      this.treeId = data.id || 0;
      this.getFrom();
    },
    // 删除分类
    remove(root, node, data, tit) {
      this.tits = tit;
      let delfromData = {
        title: "删除 [ " + data.title + " ] " + "分类",
        url: `file/category/${data.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
          this.checkPicList = [];
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 确认删除树
    // submitModel () {
    //     if (this.tits === '图片') {
    //         this.getFileList();
    //         this.checkPicList = [];
    //     } else {
    //         this.getList();
    //         this.checkPicList = [];
    //     }
    // },
    // 编辑树表单
    editPic(root, node, data) {
      this.$modalForm(
        categoryEditApi(data.id, { file_type: this.uploadName.file_type })
      ).then(() => this.getList());
    },
    // 搜索分类
    changePage() {
      this.getList("search");
    },
    // 分类列表树
    getList(type) {
      let data = {
        title: this.uploadName.file_type == 1 ? "全部图片" : "全部视频",
        id: "",
        pid: 0,
        selected: true,
        name: this.uploadName.name,
      };
      getCategoryListApi(this.uploadName)
        .then(async (res) => {
          this.treeData = res.data.list;
          this.treeData.unshift(data);
          if (type !== "search") {
            this.treeData2 = [...this.treeData];
          } else {
            this.searchClass = true;
          }
          this.addFlag(this.treeData);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    loadData(item, callback) {
      getCategoryListApi({
        pid: item.id,
        file_type: this.uploadName.file_type,
      })
        .then(async (res) => {
          const data = res.data.list;
          callback(data);
        })
        .catch((res) => {});
    },
    addFlag(treedata) {
      treedata.map((item) => {
        this.$set(item, "flag", false);
        this.$set(item, "flag2", false);
        item.children && this.addFlag(item.children);
      });
    },
    // 新建分类
    add() {
      this.treeId = 0;
      this.getFrom();
    },
    // 文件列表
    getFileList() {
      this.fileData.pid = this.treeId;
      this.fileData.file_type = this.uploadName.file_type;
      this.fileData.name = this.fileName;
      fileListApi(this.fileData)
        .then(async (res) => {
          res.data.list.forEach((el) => {
            el.isSelect = false;
            el.isEdit = false;
            el.isShowEdit = false;
            el.realName = false;
            el.num = 0;
            this.editName(el);
            if (el.file_type == 2) {
              el.poster = "";
              if (el.satt_dir.includes("uploads")) {
                this.createPoster(el);
              }
            }
          });
          this.pictrueList = res.data.list;

          if (this.pictrueList.length) {
            this.isShowPic = false;
          } else {
            this.isShowPic = true;
          }
          this.total = res.data.count;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    pageChange(index) {
      this.fileData.page = index;
      this.getFileList();
      this.checkPicList = [];
    },
    // 新建分类表单
    getFrom() {
      this.$modalForm(
        createApi({ id: this.treeId, file_type: this.uploadName.file_type })
      ).then((res) => {
        this.getList();
      });
    },
    // 上传之前
    beforeUpload(res) {
      // console.log('dfdgfgfg',this.uploadList.length);
      // if (this.uploadList.length > 4) {
      //   // this.$Message.warning("一次最多只能上传5张图片");
      //   return false;
      // }
      //控制文件上传格式
      let imgTypeArr = ["image/png", "image/jpg", "image/jpeg", "image/gif"];
      let imgType = imgTypeArr.indexOf(res.type) !== -1;
      if (!imgType) {
        this.$Message.warning({
          content: "文件  " + res.name + "  格式不正确, 请选择格式正确的图片",
          duration: 5,
        });
        return false;
      }
      // 控制文件上传大小
      let imgSize = this.$cache.local.getJSON("file_size_max");
      let Maxsize = res.size < imgSize;
      let fileMax = imgSize / 1024 / 1024;
      if (!Maxsize) {
        this.$Message.warning({
          content: "文件体积过大,图片大小不能超过" + fileMax + "M",
          duration: 5,
        });
        return false;
      }
      // this.uploadList.push(res);
      this.uploadData = {
        pid: this.treeId,
      };
      let promise = new Promise((resolve) => {
        this.$nextTick(function() {
          resolve(true);
        });
      });
      return promise;
    },
    // 上传成功
    handleSuccess(res, file, fileList) {
      if (res.status === 200) {
        // this.uploadList = [];
        this.fileData.page = 1;
        this.$Message.success(res.msg);
        this.getFileList();
      } else {
        this.$Message.error(res.msg);
      }
    },
    // 关闭
    cancel() {
      this.$emit("changeCancel");
    },
    // 选中图片
    changImage(item, index, row) {
      this.openImgShow = false;
      this.viewImg = "";
      let activeIndex = 0;
      // 如果是单选且选择了图片
      if (this.isChoice === "单选" && this.checkPicList.length >= 1) {
        // 如果选择的图片是列表的第一张图片
        if (this.checkPicList[0].att_id == item.att_id) {
          // 取消勾选
          item.isSelect = false;
          // 遍历图片列表使角标为0
          this.pictrueList.forEach((j) => {
            if (j.att_id == this.checkPicList[0].att_id) {
              j.num = 0;
            }
          });
          // 重置已选中数组
          this.checkPicList = [];
        } else {
          // 将选中的第一张图片角标为0
          this.checkPicList[0].num = 0;
          // 取消勾选
          this.checkPicList[0].isSelect = false;
          // 将这张图片删除
          this.checkPicList.splice(0, 1);
          // 添加下一张选中的图片
          this.checkPicList.push(item);
          // 勾选
          item.isSelect = true;
          // 显示角标为1
          item.num = 1;
        }
        return;
      }
      if (!item.isSelect) {
        item.isSelect = true;
        this.checkPicList.push(item);
      } else {
        item.isSelect = false;
        this.checkPicList.map((el, index) => {
          if (el.att_id == item.att_id) {
            activeIndex = index;
          }
        });
        this.checkPicList.splice(activeIndex, 1);
      }
      this.ids = [];
      this.checkPicList.map((item, i) => {
        this.ids.push(item.att_id);
      });
      this.pictrueList.map((el, i) => {
        if (el.isSelect) {
          this.checkPicList.filter((el2, j) => {
            if (el.att_id == el2.att_id) {
              el.num = j + 1;
            }
          });
        } else {
          el.num = 0;
        }
      });
    },
    // 点击使用选中图片
    checkPics() {
      if (this.isChoice === "单选") {
        if (this.checkPicList.length > 1)
          return this.$Message.warning("最多只能选一张图片");
        this.$emit("getPic", this.checkPicList[0]);
      } else {
        if (!this.$route) {
          this.$emit("getPicD", this.checkPicList);
        } else {
          let maxLength = this.$route.query.maxLength;
          if (
            maxLength != undefined &&
            this.checkPicList.length > Number(maxLength)
          )
            return this.$Message.warning("最多只能选" + maxLength + "张图片");
          this.$emit("getPicD", this.checkPicList);
        }
      }
    },
    editName(item) {
      let it = item.real_name.split(".");
      let it1 = it[1] == undefined ? [] : it[1];
      let len = it[0].length + it1.length;
      item.editName = item.real_name;
      // item.editName =
      //         len < 10
      //                 ? item.real_name
      //                 : item.real_name.substr(0, 2) + "..." + item.real_name.substr(-5, 5);
    },
    // 修改图片文字上传
    bindTxt(item) {
      if (item.real_name == "") {
        this.$Message.error("请填写内容");
      }
      fileUpdateApi(item.att_id, {
        real_name: item.real_name,
      })
        .then((res) => {
          this.editName(item);
          item.isEdit = false;
          this.$Message.success(res.msg);
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
    //大图预览
    openView(item) {
      // if(this.openImgShow){
      //   this.viewImg = "";
      //   this.$set(this,'viewImg',item.satt_dir);
      //   this.openImgShow = false;
      // }else{
      //   this.viewImg = "";
      //   this.$set(this,'viewImg',item.satt_dir);
      //   this.openImgShow = true;
      // }
      if (item.satt_dir == this.viewImg) {
        this.openImgShow = false;
        this.viewImg = "";
      } else {
        this.openImgShow = true;
        if (item.file_type == 1) {
          this.viewImg = item.satt_dir;
        } else {
          this.createPlayer(item);
        }
      }
    },
    openUpload() {
      this.$UploadImg({
        categories: this.treeData,
        categoryId: this.treeId,
        onClose: () => {
          this.uploadSuccess();
        },
      });
    },
    uploadSuccess() {
      // this.uploadVisible = false;
      this.fileData.page = 1;
      this.getFileList();
    },
  },
};
</script>

<style scoped lang="stylus">
.searchNo {
  margin-top: -250px;
  text-align: center;
}

.nameStyle {
  position: absolute;
  white-space: nowrap;
  z-index: 999;
  background: #eee;
  left: 150px;
  top: 100px;
  height: 300px;
  width: 300px;
  color: #555;
  border: 1px solid #ebebeb;
  padding: 0 !important;
}

.nameStyle img {
  position: absolute;
  white-space: nowrap;
  width: 99%;
  height: 99%;
  object-fit: contain;
}

.iconbianji1 {
  font-size: 13px;
}

/deep/.ivu-badge-count {
  margin-top: 18px !important;
  margin-right: 19px !important;
}

.Nav /deep/.ivu-icon-ios-arrow-forward:before {
  content: '\F341' !important;
  font-size: 20px;
}

/deep/.ivu-btn-icon-only.ivu-btn-small {
  padding: unset !important;
}

.selectTree {
}

.treeBox {
  width: 100%;
  height: 100%;
  margin-top: 10px;

  >>> ul {
    padding: 0;
  }

  >>> ul li {
    margin: 0;
  }

  >>> .ivu-tree-arrow {
    width: 17px;
    color: #626262;
  }

  >>> .ivu-span:hover {
    // background: #F5F5F5;
    color: rgba(0, 0, 0, 0.4) !important;
  }

  >>> .ivu-tree-arrow i {
    vertical-align: bottom;
  }

  // /deep/ivu-tree-title-selected:hover {
  // color: unset;
  // background-color: unset;
  // }
  >>> .ivu-tree-title {
    padding: 5px 0;
    display: unset !important;
    vertical-align: unset;
  }

  >>> .ivu-tree-title .ivu-span>span {
    padding-left: 5px;
  }

  // >>> .ivu-tree-title-selected{
  // width : 100%;
  // }
  >>> .ivu-tree-title-selected, .ivu-tree-title-selected:hover {
    color: #2D8cF0 !important;
    background-color: #f0faff !important;
  }

  // >>> .ivu-tree-title-selected span{
  // color: #2D8cF0 !important;
  // background-color: #f0faff !important;
  // }
  >>> .ivu-btn-icon-only {
    width: 20px !important;
    height: 20px !important;
  }

  >>> .ivu-tree-title:hover {
    color: #2D8cF0 !important;
    background-color: #f0faff !important;
  }
}

.trees-coadd {
  width: 100%;
  border-radius: 4px;
  overflow: hidden;
  position: relative;

  .scollhide {
    overflow-x: hidden;
    overflow-y: scroll;
    padding: 0;
    box-sizing: border-box;

    .trees {
      width: 100%;
      height: 450px;
    }
  }

  .scollhide::-webkit-scrollbar {
    width: 4px !important; /* 对垂直流动条有效 */
  }

  /* 定义滑块 内阴影+圆角 */
  ::-webkit-scrollbar-thumb {
    -webkit-box-shadow: inset 0 0 6px #999;
  }
}

.treeSel >>>.ivu-select-dropdown-list {
  padding: 0 5px !important;
  box-sizing: border-box;
  width: 200px;
}

.imagesNo {
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  margin: 65px auto;

  .imagesNo_sp {
    font-size: 13px;
    color: #dbdbdb;
    line-height: 3;
  }
}

.Modal {
  width: 100%;
  height: 100%;
  background: #fff !important;
}

.Nav {
  width: 90%;
  border-right: 1px solid #eee;
}

.colLeft {
  padding-right: 0 !important;
  height: 100%;
}

.conter {
  width: 100%;
  height: 100%;
  margin-left: -4px !important;
  min-height: 405px;
  display: block;
  position: relative;
}

.conter .bnt {
  width: 100%;
  padding: 0 13px 0 12px;
  box-sizing: border-box;
}

.conter .pictrueList_pic {
  position: relative;
  width: 100px;
  height: 125px;
  cursor: pointer;
  margin: 5px 8px;
  padding: 0px;
  border-radius: 3px;
  display: inline-block;

  .picimage {
    width: 100px;
    height: 100px;
    background-color: #f0f0f0 !important;
  }

  .picimage img, .picimage video {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  // .picimage .picMenu {
  // position: absolute
  // bottom: 30px
  // // background-color: rgba(0,0,0,0.5)
  // width:100px
  // // display:none
  // text-align: center
  // }
  .picName {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #fff !important;
    padding: 5px;
  }

  .picName .picMenu {
    position: absolute;
    bottom: 18px;
    left: 0;
    // background-color: rgba(0,0,0,0.5)
    width: 100px;
    display: none;
    text-align: center;
  }

  .picName:hover .picMenu {
    display: block;
  }

  p {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    height: 20px;
    text-align: center;
  }

  .number {
    height: 33px;
  }

  .number {
    position: absolute;
    right: 0;
    top: 0;
  }
}

.conter .pictrueList {
  width: 100%;
  height: 100%;
  margin-top: 10px;
}

.conter .pictrueList img {
  width: 100%;
  vertical-align: middle;
}

.conter .pictrueList img.on {
  border: 2px solid #5FB878;
}

.conter .footer {
  padding: 10px 20px;
}

.demo-badge {
  width: 42px;
  height: 42px;
  background: transparent;
  border-radius: 6px;
  display: inline-block;
}

.bnt /deep/ .ivu-tree-children {
  padding: 5px 0;
}

.trees-coadd /deep/ .ivu-tree-children .ivu-tree-arrow {
  line-height: 18px;
}

/deep/.prism-player .prism-setting-btn {
  display: none;
}

/deep/.prism-player .prism-cc-btn {
  display: none;
}
</style>
