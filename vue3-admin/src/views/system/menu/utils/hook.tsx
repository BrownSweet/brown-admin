import editForm from "../form.vue";
import { handleTree } from "@/utils/tree";
import { message } from "@/utils/message";
import { getMenuList, addSystemMenu, updateSystemMenu } from "@/api/system";
import { transformI18n } from "@/plugins/i18n";
import { addDialog } from "@/components/ReDialog";
import { reactive, ref, onMounted, h } from "vue";
import type { FormItemProps } from "../utils/types";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { cloneDeep, isAllEmpty, deviceDetection } from "@pureadmin/utils";

export function useMenu() {
  const form = reactive({
    title: ""
  });

  const formRef = ref();
  const dataList = ref([]);
  const loading = ref(true);
  const columns: TableColumnList = [
    {
      label: "菜单名称",
      prop: "menu_name",
      align: "left",
      cellRenderer: ({ row }) => (
        <>
          <span class="inline-block mr-1">
            {h(useRenderIcon(row.icon), {
              style: { paddingTop: "1px" }
            })}
          </span>
          <span>{row.menu_name}</span>
        </>
      )
    },
    {
      label: "路由路径",
      prop: "menu_url"
    },
    {
      label: "权限标识",
      prop: "auths"
    },
    {
      label: "排序",
      prop: "rank",
      width: 100
    },
    {
      label: "隐藏",
      prop: "showLink",
      formatter: ({ showLink }) => (showLink ? "否" : "是"),
      width: 100
    },
    {
      label: "显示父层级",
      prop: "showParent",
      formatter: ({ showParent }) => (showParent ? "否" : "是"),
      width: 100
    },
    {
      label: "操作",
      fixed: "right",
      width: 210,
      slot: "operation"
    }
  ];

  function handleSelectionChange(val) {
    console.log("handleSelectionChange", val);
  }

  function resetForm(formEl) {
    if (!formEl) return;
    formEl.resetFields();
    onSearch();
  }

  async function onSearch() {
    loading.value = true;
    const { data } = await getMenuList(); // 这里是返回一维数组结构，前端自行处理成树结构，返回格式要求：唯一id加父节点parentId，parentId取父节点id
    let newData = data;
    if (!isAllEmpty(form.title)) {
      // 前端搜索菜单名称
      newData = newData.filter(item =>
        transformI18n(item.title).includes(form.title)
      );
    }
    dataList.value = handleTree(newData); // 处理成树结构
    setTimeout(() => {
      loading.value = false;
    }, 500);
  }

  function formatHigherMenuOptions(treeList) {
    if (!treeList || !treeList.length) return;
    const newTreeList = [];
    for (let i = 0; i < treeList.length; i++) {
      treeList[i].title = transformI18n(treeList[i].title);
      formatHigherMenuOptions(treeList[i].children);
      newTreeList.push(treeList[i]);
    }
    return newTreeList;
  }

  function openDialog(title = "新增", row?: any) {
    console.log(row);
    addDialog({
      title: `${title}菜单`,
      props: {
        formInline: {
          menuType: row?.menuType ?? 0,
          higherMenuOptions: formatHigherMenuOptions(cloneDeep(dataList.value)),
          parentId: row?.parentId ?? 0,
          title: row?.menu_name ?? "",
          name: row?.component_name ?? "",
          path: row?.menu_url ?? "",
          component: row?.component ?? "",
          rank: row?.rank ?? 99,
          redirect: row?.redirect ?? "",
          icon: row?.icon ?? "",
          extraIcon: row?.extraIcon ?? "",
          enterTransition: row?.enterTransition ?? "",
          leaveTransition: row?.leaveTransition ?? "",
          activePath: row?.activePath ?? "",
          auths: row?.auths ?? "",
          frameSrc: row?.frameSrc ?? "",
          frameLoading: row?.frameLoading ?? true,
          keepAlive: row?.keepAlive ?? false,
          hiddenTag: row?.hiddenTag ?? false,
          fixedTag: row?.fixedTag ?? false,
          showLink: row?.showLink ?? true,
          showParent: row?.showParent ?? false,
          formItems: row?.formItems ?? [{ pathName: "", pathIdentifier: "" }]
        }
      },
      width: "45%",
      draggable: true,
      fullscreen: deviceDetection(),
      fullscreenIcon: true,
      closeOnClickModal: false,
      contentRenderer: () => h(editForm, { ref: formRef }),
      beforeSure: (done, { options }) => {
        const FormRef = formRef.value.getRef();
        const curData = options.props.formInline as FormItemProps;
        function chores() {
          message(
            `您${title}了菜单名称为${transformI18n(curData.title)}的这条数据`,
            {
              type: "success"
            }
          );
          done(); // 关闭弹框
          onSearch(); // 刷新表格数据
        }
        FormRef.validate(valid => {
          if (valid) {
            // 表单规则校验通过
            let data = {
              menu_name: curData.title,
              component_name: curData.name,
              menu_url: curData.path,
              parent_Id: curData.parentId,
              rank: curData.rank,
              icon: curData.icon,
              formItems: curData.formItems,
              showLink: curData.showLink,
              showParent: curData.showParent,
              id: row?.id
            };
            if (title === "新增") {
              addSystemMenu(data).then(res => {
                if (res.success) {
                  chores();
                } else {
                  message(res.message, { type: "error" });
                }
              });
              // 实际开发先调用新增接口，再进行下面操作
              // chores();
            } else {
              updateSystemMenu(data).then(res => {
                if (res.success) {
                  chores();
                } else {
                  message(res.message, { type: "error" });
                }
              });
              // 实际开发先调用修改接口，再进行下面操作
              // chores();
            }
          }
        });
      }
    });
  }

  function handleDelete(row) {
    message(`您删除了菜单名称为${transformI18n(row.title)}的这条数据`, {
      type: "success"
    });
    onSearch();
  }

  onMounted(() => {
    onSearch();
  });

  return {
    form,
    loading,
    columns,
    dataList,
    /** 搜索 */
    onSearch,
    /** 重置 */
    resetForm,
    /** 新增、修改菜单 */
    openDialog,
    /** 删除菜单 */
    handleDelete,
    handleSelectionChange
  };
}
