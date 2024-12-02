import { http } from "@/utils/http";
import { baseUrlApi, type Result } from "@/api/utils";

type getAsyncRoutesResult = Result & {
  data: Array<any>;
};

export const getAsyncRoutes = () => {
  return http.request<getAsyncRoutesResult>("get", baseUrlApi("getMenu"));
};
