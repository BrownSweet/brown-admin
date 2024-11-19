<script setup>
import { getAuths } from "@/router/utils";
defineOptions({
  name: "Test"
});
</script>
<template>
  <div>
    <h1>测试页面</h1>
    <p class="mb-2">当前拥有的code列表：{{ getAuths() }}</p>
    <el-button v-auth="'Test:add'" type="primary">新增</el-button>
    <el-button v-auth="'Test:delete'" type="warning">删除</el-button>
  </div>
</template>
