import { http } from "@/utils/http";
import { baseUrlApi, type Result, type ResultTable } from "@/api/utils";
/** 获取系统管理-用户管理-开始 */
/** 获取系统管理-用户管理列表 */
export const getUserList = (params?: object) => {
  return http.request<ResultTable>("get", baseUrlApi("getUserList"), {
    params
  });
};
export const addUser = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addUser"), { data });
};
export const updateUser = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("updateUser"), { data });
};
export const resetPassword = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("resetPassword"), { data });
};

/** 系统管理-用户管理-获取所有角色列表 */
export const getAllRoleList = () => {
  return http.request<Result>("get", baseUrlApi("getAllRoles"));
};
/** 系统管理-用户管理-根据userId，获取对应角色id列表（userId：用户id） */
export const getRoleIds = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getUserRoles"), { params });
};
export const setUserRole = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("setUserRole"), { data });
};
export const deleteUser = (data?: object) => {
  return http.request<Result>("delete", baseUrlApi("deleteUser"), { data });
};
export const setUserStatus = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("setUserStatus"), { data });
};
/** 获取系统管理-用户管理-结束 */

/** 获取系统管理-角色管理-开始 */
/** 获取系统管理-角色管理列表 */
export const getRoleList = (params?: object) => {
  return http.request<ResultTable>("get", baseUrlApi("getRoleList"), {
    params
  });
};

/** 获取角色管理-权限-菜单权限 */
export const getRoleMenu = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getRoleMenu"), { params });
};

/** 获取角色管理-权限-菜单权限-根据角色 id 查对应菜单 */
export const getRoleMenuIds = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getRoleMenuIds"), { params });
};

export const addRole = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addRole"), { data });
};

export const addAndUpdateRoleHhandle = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addAndUpdateRoleHhandle"), {
    data
  });
};
export const updateRole = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("updateRole"), { data });
};
/**删除角色**/
export const deleteRole = (data?: object) => {
  return http.request<Result>("delete", baseUrlApi("deleteRole"), { data });
};
/**设置角色状态**/
export const setRoleStatus = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("setRoleStatus"), { data });
};
/** 获取系统管理-角色管理-结束 */
/** 获取系统管理-菜单管理列表-开始 */
export const getMenuList = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getSystemMenu"), { params });
};

export const addSystemMenu = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addSystemMenu"), { data });
};
export const updateSystemMenu = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("updateSystemMenu"), { data });
};
/** 系统管理-部门管理-开始 */
/** 获取系统管理-部门管理列表 */
export const getDeptList = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getDepartmentList"), {
    params
  });
};
export const addDepartment = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addDepartment"), { data });
};

export const updateDepartment = (data?: object) => {
  return http.request<Result>("put", baseUrlApi("updateDepartment"), { data });
};
export const deleteDepartment = (data?: object) => {
  return http.request<Result>("delete", baseUrlApi("deleteDepartment"), {
    data
  });
};
/** 系统管理-部门管理-结束 */
/** 获取系统监控-在线用户列表 */
export const getOnlineLogsList = (data?: object) => {
  return http.request<ResultTable>("post", "/online-logs", { data });
};

/** 获取系统监控-登录日志列表 */
export const getLoginLogsList = (params?: object) => {
  return http.request<ResultTable>("get", baseUrlApi("getLoginLog"), {
    params
  });
};

/** 获取系统监控-操作日志列表 */
export const getOperationLogsList = (params?: object) => {
  return http.request<ResultTable>("get", baseUrlApi("getOperationLog"), {
    params
  });
};

/** 获取系统监控-系统日志列表 */
export const getSystemLogsList = (params?: object) => {
  return http.request<ResultTable>("get", baseUrlApi("getSystemLog"), {
    params
  });
};

/** 获取系统监控-系统日志-根据 id 查日志详情 */
export const getSystemLogsDetail = (params?: object) => {
  return http.request<Result>("get", baseUrlApi("getSystemLogDetail"), {
    params
  });
};
