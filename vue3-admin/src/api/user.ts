import { http } from "@/utils/http";
import { baseUrlApi, type Result } from "@/api/utils";
export type UserResult = Result & {
  data: {
    /** 头像 */
    avatar: string;
    /** 用户名 */
    username: string;
    /** 昵称 */
    nickname: string;
    /** 当前登录用户的角色 */
    roles: Array<string>;
    /** 按钮级别权限 */
    permissions: Array<string>;
    /** `token` */
    accessToken: string;
    /** 用于调用刷新`accessToken`的接口时所需的`token` */
    refreshToken: string;
    /** `accessToken`的过期时间（格式'xxxx/xx/xx xx:xx:xx'） */
    expires: Date;
  };
};
export type RsaPublicKeyResult = Result & {
  data: {
    /** 公钥 */
    publicKey: string;
  };
};
export type RefreshTokenResult = Result & {
  data: {
    /** `token` */
    accessToken: string;
    /** 用于调用刷新`accessToken`的接口时所需的`token` */
    refreshToken: string;
    /** `accessToken`的过期时间（格式'xxxx/xx/xx xx:xx:xx'） */
    expires: Date;
  };
};
export type updatePasswordResult = Result & {
  data: any;
};
export type onRegisterResult = Result & {
  data: any;
};
export type UserInfo = {
  /** 头像 */
  avatar: string;
  /** 用户名 */
  username: string;
  /** 昵称 */
  nickname: string;
  /** 邮箱 */
  email: string;
  /** 联系电话 */
  phone: string;
  /** 简介 */
  description: string;
};

export type UserInfoResult = {
  success: boolean;
  data: UserInfo;
};

export type RegisterParams = {
  company: string;
  username: string;
  password: string;
  phone: string;
  repeatPassword: string;
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
export const getRsaPublicKey = (params?: object) => {
  return http.request<RsaPublicKeyResult>(
    "get",
    baseUrlApi("getRsaPublicKey"),
    { params }
  );
};
/** 登录 */
export const getLogin = (data?: object) => {
  return http.request<UserResult>("post", baseUrlApi("login"), { data });
};

/** 刷新`token` */
export const refreshTokenApi = (data?: object) => {
  return http.request<RefreshTokenResult>("post", baseUrlApi("refreshToken"), {
    data
  });
};

/** 账户设置-个人信息 */
export const getMine = (data?: object) => {
  return http.request<UserInfoResult>("get", baseUrlApi("getUserInfo"), {
    data
  });
};

/** 账户设置-个人安全日志 */
export const getMineLogs = (data?: object) => {
  return http.request<ResultTable>("get", "/mine-logs", { data });
};

/** 账户设置-修改密码 */
export const updatePassword = (data?: object) => {
  return http.request<updatePasswordResult>(
    "put",
    baseUrlApi("updatePassword"),
    { data }
  );
};

export const onRegister = (data?: object) => {
  return http.request<onRegisterResult>("post", baseUrlApi("register"), {
    data
  });
};
