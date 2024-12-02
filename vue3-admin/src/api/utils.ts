export const baseUrlApi = (url: string) => `/api/${url}`;
export type Result = {
  success: boolean;
  code: number;
  message: string;
  errors_message: string;
  requestId: string;
  data: Array<any>;
};

export type ResultTable = Result & {
  data?: {
    /** 列表数据 */
    list: Array<any>;
    /** 总条目数 */
    total?: number;
    /** 每页显示条目个数 */
    pageSize?: number;
    /** 当前页数 */
    currentPage?: number;
    lastPage?: number;
  };
};
