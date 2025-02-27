<template>
  <span class="i-layout-header-trigger i-layout-header-trigger-min">
    <Dropdown
      trigger="click"
      class="i-layout-header-search-drop"
      ref="dropdown"
    >
      <Icon type="ios-search" />
      <DropdownMenu slot="list">
        <div class="i-layout-header-search-drop-main">
          <el-select
            v-model="currentVal"
            placeholder="搜索..."
            filterable
            size="medium"
            remote
            :remote-method="remoteMethod"
            :loading="loading"
            @change="handleSearch"
            class="search-menu-box"
          >
            <el-option
              v-for="(option, index) in menusList"
              :value="option.menu_path"
              :label="option.menu_name"
              :key="index"
              :disabled="option.type === 1"
              ></el-option
            >
          </el-select>
          <span
            class="i-layout-header-search-drop-main-cancel"
            @click="handleCloseSearch"
            >取消</span
          >
        </div>
      </DropdownMenu>
    </Dropdown>
  </span>
</template>
<script>
import { mapState } from "vuex";
import { menusListApi } from "@/api/account";
import { Debounce } from "@/utils";
export default {
  name: "iHeaderSearch",
  data() {
    return {
      currentVal: "",
      loading: false,
      menusList: [],
    };
  },
  computed: {
    ...mapState("admin/layout", ["isDesktop", "headerMenu"]),
  },
  created() {
    this.getMenusList();
  },
  methods: {
    handleCloseSearch() {
      this.$refs.dropdown.handleClick();
    },
    getMenusList() {
      this.loading = true;
      menusListApi({ keyword: "" }).then((res) => {
        this.loading = false;
        this.menusList = res.data;
      });
    },
    remoteMethod: Debounce(function(query) {
      if (query) {
        this.loading = true;
        menusListApi({ keyword: query }).then((res) => {
          this.loading = false;
          this.menusList = res.data;
        });
      } else {
        console.log(123);
      }
    }),
    handleSearch(){
      this.menusList = [];
      this.$router.push({ path: this.currentVal });
    }
  },
};
</script>
<style lang="less" scoped>
/deep/.ivu-select-dropdown-list {
  max-height: 200px !important;
  overflow-y: auto !important;
}
/deep/.i-layout-header-search-drop .ivu-select-dropdown {
  left: unset !important;
  right: 247px;
  width: 300px;
}
.search-menu-box{
  /deep/ .el-input__inner{
    border: none;
  }
}
</style>