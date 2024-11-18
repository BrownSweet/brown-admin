import { http } from "@/utils/http";
import { baseUrlApi } from "@/api/utils";

type Result = {
  success: boolean;
  data?: Array<any>;
};

type ResultTable = {
  success: boolean;
  data?: {
    /** 列表数据 */
    list: Array<any>;
    /** 总条目数 */
    total?: number;
    /** 每页显示条目个数 */
    pageSize?: number;
    /** 当前页数 */
    currentPage?: number;
  };
};

/** 获取系统管理-用户管理列表 */
export const getUserList = (data?: object) => {
  return http.request<ResultTable>("post", baseUrlApi("getUserList"), { data });
};
export const addUser = (data?: object) => {
  return http.request<any>("post", baseUrlApi("addUser"), { data });
};
export const updateUser = (data?: object) => {
  return http.request<any>("put", baseUrlApi("updateUser"), { data });
};
export const resetPassword = (data?: object) => {
  return http.request<any>("post", baseUrlApi("resetPassword"), { data });
};
/** 系统管理-用户管理-获取所有角色列表 */
export const getAllRoleList = () => {
  return http.request<Result>("get", baseUrlApi("getAllRoles"));
};
/** 系统管理-用户管理-根据userId，获取对应角色id列表（userId：用户id） */
export const getRoleIds = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("getUserRoles"), { data });
};
export const setUserRole = (data?: object) => {
  return http.request<any>("post", baseUrlApi("setUserRole"), { data });
};
/** 获取系统管理-角色管理列表 */
export const getRoleList = (data?: object) => {
  return http.request<ResultTable>("post", baseUrlApi("getRoleList"), { data });
};

/** 获取系统管理-菜单管理列表 */
export const getMenuList = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("getSystemMenu"), { data });
};

export const addSystemMenu = (data?: object) => {
  return http.request<any>("post", baseUrlApi("addSystemMenu"), { data });
};
export const updateSystemMenu = (data?: object) => {
  return http.request<any>("put", baseUrlApi("updateSystemMenu"), { data });
};
/** 获取系统管理-部门管理列表 */
export const getDeptList = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("getDepartmentList"), {
    data
  });
};
export const addDepartment = (data?: object) => {
  return http.request<any>("post", baseUrlApi("addDepartment"), { data });
};

export const updateDepartment = (data?: object) => {
  return http.request<any>("put", baseUrlApi("updateDepartment"), { data });
};

/** 获取系统监控-在线用户列表 */
export const getOnlineLogsList = (data?: object) => {
  return http.request<ResultTable>("post", "/online-logs", { data });
};

/** 获取系统监控-登录日志列表 */
export const getLoginLogsList = (data?: object) => {
  return http.request<ResultTable>("post", "/login-logs", { data });
};

/** 获取系统监控-操作日志列表 */
export const getOperationLogsList = (data?: object) => {
  return http.request<ResultTable>("post", "/operation-logs", { data });
};

/** 获取系统监控-系统日志列表 */
export const getSystemLogsList = (data?: object) => {
  return http.request<ResultTable>("post", "/system-logs", { data });
};

/** 获取系统监控-系统日志-根据 id 查日志详情 */
export const getSystemLogsDetail = (data?: object) => {
  return http.request<Result>("post", "/system-logs-detail", { data });
};

/** 获取角色管理-权限-菜单权限 */
export const getRoleMenu = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("getRoleMenu"), { data });
};

/** 获取角色管理-权限-菜单权限-根据角色 id 查对应菜单 */
export const getRoleMenuIds = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("getRoleMenuIds"), { data });
};

export const addRole = (data?: object) => {
  return http.request<any>("post", baseUrlApi("addRole"), { data });
};

export const addAndUpdateRoleHhandle = (data?: object) => {
  return http.request<Result>("post", baseUrlApi("addAndUpdateRoleHhandle"), {
    data
  });
};
export const updateRole = (data?: object) => {
  return http.request<any>("post", baseUrlApi("updateRole"), { data });
};
