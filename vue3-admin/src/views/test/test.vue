<script setup>
import { getAuths, hasAuth } from "@/router/utils";
defineOptions({
  name: "Test"
});
</script>
<template>
  <div>
    <h1>测试页面</h1>
    <p class="mb-2">当前拥有的code列表：{{ getAuths() }}</p>
    <el-button v-if="hasAuth('Test:add')" type="primary">新增</el-button>
    <Auth value="Test:delete">
      <el-button type="warning">删除</el-button>
    </Auth>
  </div>
</template>
