<script setup lang="ts">
import { ref, watch } from "vue";
import ReCol from "@/components/ReCol";
import { formRules } from "./utils/rule";
import { FormProps } from "./utils/types";
import { transformI18n } from "@/plugins/i18n";
import { IconSelect } from "@/components/ReIcon";
import Segmented from "@/components/ReSegmented";
import {
  menuTypeOptions,
  showLinkOptions,
  fixedTagOptions,
  keepAliveOptions,
  hiddenTagOptions,
  showParentOptions,
  frameLoadingOptions
} from "./utils/enums";

const props = withDefaults(defineProps<FormProps>(), {
  formInline: () => ({
    menuType: 0,
    higherMenuOptions: [],
    parentId: 0,
    title: "",
    name: "",
    path: "",
    component: "",
    rank: 99,
    redirect: "",
    icon: "",
    extraIcon: "",
    enterTransition: "",
    leaveTransition: "",
    activePath: "",
    auths: "",
    frameSrc: "",
    frameLoading: true,
    keepAlive: false,
    hiddenTag: false,
    fixedTag: false,
    showLink: true,
    showParent: false,
    formItems: [{ pathName: "", pathIdentifier: "" }]
  })
});
const ruleFormRef = ref();
const newFormInline = ref(props.formInline);
function getRef() {
  return ruleFormRef.value;
}
let formItems = newFormInline.value.formItems;
const addRow = (index: number) => {
  formItems.splice(index + 1, 0, { pathName: "", pathIdentifier: "" });
};
const deleteRow = (index: number) => {
  if (formItems.length > 1) {
    formItems.splice(index, 1);
  }
};
defineExpose({ getRef });
</script>

<template>
  <el-form
    ref="ruleFormRef"
    :model="newFormInline"
    :rules="formRules"
    label-width="82px"
  >
    <el-row :gutter="30">
      <re-col>
        <el-form-item label="菜单类型">
          <Segmented
            v-model="newFormInline.menuType"
            :options="menuTypeOptions"
          />
        </el-form-item>
      </re-col>

      <re-col>
        <el-form-item label="上级菜单">
          <el-cascader
            v-model="newFormInline.parentId"
            class="w-full"
            :options="newFormInline.higherMenuOptions"
            :props="{
              value: 'id',
              label: 'menu_name',
              emitPath: false,
              checkStrictly: true
            }"
            clearable
            filterable
            placeholder="请选择上级菜单"
          >
            <template #default="{ node, data }">
              <span>{{ transformI18n(data.menu_name) }}</span>
              <span v-if="!node.isLeaf"> ({{ data.children.length }}) </span>
            </template>
          </el-cascader>
        </el-form-item>
      </re-col>

      <re-col :value="12" :xs="24" :sm="24">
        <el-form-item label="菜单名称" prop="title">
          <el-input
            v-model="newFormInline.title"
            clearable
            placeholder="请输入菜单名称"
          />
        </el-form-item>
      </re-col>

      <re-col v-if="newFormInline.menuType !== 1" :value="12" :xs="24" :sm="24">
        <el-form-item label="路由路径" prop="path">
          <el-input
            v-model="newFormInline.path"
            clearable
            placeholder="请输入路由路径"
          />
        </el-form-item>
      </re-col>
      <re-col v-if="newFormInline.menuType !== 3" :value="12" :xs="24" :sm="24">
        <el-form-item label="路由名称" prop="name">
          <el-input
            v-model="newFormInline.name"
            clearable
            placeholder="请输入路由名称"
          />
        </el-form-item>
      </re-col>
      <re-col v-if="newFormInline.menuType !== 1" :value="12" :xs="24" :sm="24">
        <el-form-item label="菜单排序">
          <el-input-number
            v-model="newFormInline.rank"
            class="!w-full"
            :min="1"
            :max="9999"
            controls-position="right"
          />
        </el-form-item>
      </re-col>

      <re-col
        v-show="newFormInline.menuType !== 1"
        :value="12"
        :xs="24"
        :sm="24"
      >
        <el-form-item label="菜单图标">
          <IconSelect v-model="newFormInline.icon" class="w-full" />
        </el-form-item>
      </re-col>
      <re-col v-if="newFormInline.menuType === 1" :value="12" :xs="24" :sm="24">
        <!-- 按钮级别权限设置 -->
        <el-form-item label="权限标识" prop="auths">
          <el-input
            v-model="newFormInline.auths"
            clearable
            placeholder="请输入权限标识"
          />
        </el-form-item>
      </re-col>

      <re-col
        v-show="newFormInline.menuType !== 1"
        :value="12"
        :xs="24"
        :sm="24"
      >
        <el-form-item label="菜单">
          <Segmented
            :modelValue="newFormInline.showLink ? 0 : 1"
            :options="showLinkOptions"
            @change="
              ({ option: { value } }) => {
                newFormInline.showLink = value;
              }
            "
          />
        </el-form-item>
      </re-col>
      <re-col
        v-show="newFormInline.menuType !== 1"
        :value="12"
        :xs="24"
        :sm="24"
      >
        <el-form-item label="父级菜单">
          <Segmented
            :modelValue="newFormInline.showParent ? 0 : 1"
            :options="showParentOptions"
            @change="
              ({ option: { value } }) => {
                newFormInline.showParent = value;
              }
            "
          />
        </el-form-item>
      </re-col>
      <re-col v-if="newFormInline.menuType !== 1">
        <el-form-item label="添加权限">
          <div
            v-for="(item, index) in formItems"
            :key="index"
            class="container"
          >
            <div class="input-container">
              <el-input
                v-model="item.pathName"
                clearable0
                placeholder="请输入权限名称"
                class="input-field"
              />
              <el-input
                v-model="item.pathIdentifier"
                clearable
                placeholder="请输入权限标识"
                class="input-field"
              />
            </div>
            <div class="button-container">
              <el-button text type="primary" @click="addRow(index)"
                >添加</el-button
              >
              <el-button text type="danger" @click="deleteRow(index)"
                >删除</el-button
              >
            </div>
          </div>
        </el-form-item>
      </re-col>
    </el-row>
  </el-form>
</template>
<style scoped>
.container {
  display: flex;
}
.input-container {
  display: flex;
  gap: 10px; /* 调整输入框之间的间距 */
  width: 75%; /* 使输入框容器占满整个宽度 */
}

.input-field {
  flex: 1; /* 使输入框平分容器宽度 */
  width: 20%; /* 调整输入框的宽度 */
}

.button-container {
  margin-left: 5px;
  display: flex;
  gap: 5px; /* 调整按钮之间的间距 */
  justify-content: flex-end; /* 将按钮右对齐 */
}
</style>
