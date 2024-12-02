<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-11 12:24
 */

namespace app\controller\api\system\middleware;

use app\controller\core\json\JsonResponse;
use app\controller\core\JwtManager;
use Ramsey\Uuid\Uuid;
use think\Exception;
use think\facade\Db;

class Auth
{
    private $whitelist = ['login', 'register', 'getRsaPublicKey', 'updatePassword', 'refreshToken'];

    private $notSystemLog=['getLoginLog','getSystemLog','getOperationLog'];

    public function handle($request, \Closure $next)
    {
        $pathInfo = $request->pathinfo();
        $pathInfo = explode('/', $pathInfo);
        $action = $pathInfo[count($pathInfo) - 1];
        $request->requestId = Uuid::uuid4()->toString();
        if (in_array($action, $this->whitelist)) {
            return $next($request);
        }
        try {
            $token = $request->header('Authorization');
            preg_match('/Bearer\s(\S+)/', $token, $matches);
            $token = isset($matches[1]) ? $matches[1] : null;
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failResponse(1005, [], $e->getMessage() . $e->getFile() . $e->getLine());
        }

        $user = JwtManager::getInstance()->verifyAccessToken($token);
        if (!$user['valid']) {
            return JsonResponse::getInstance()->failResponse(1005, [], $user['error']);
        }
        $data = (array)$user['payload']['data'];

        $user = Db::name('system_user')
            ->where('id', $data['Id'])
            ->where('is_delete', 0)
            ->find();
        if ($user['status'] != 1) {
            return JsonResponse::getInstance()->failResponse(1103, [], '账号已被禁用，请联系管理员');
        }
        $role = Db::name('system_role')
            ->whereIn('id', explode(',', $data['role_Id']))
            ->where('status', 1)
            ->where('is_delete', 0)
            ->select()
            ->toArray();
        $role_arr = array_column($role, 'id');
        if (empty($role_arr)) {
            return JsonResponse::getInstance()->failResponse(1007, [], '角色已禁用或不存在');
        }
        $request->authUser = $data;
        return $next($request);
    }

    public function end(\think\Response $response)
    {
        try {
            $request = \request();
            $pathInfo = $request->pathinfo();
            $pathInfo = explode('/', $pathInfo);
            $action = $pathInfo[count($pathInfo) - 1];
            if (in_array($action, $this->notSystemLog)) {
                return;
            }

            $ip= isset($_SERVER['HTTP_X_REAL_IP'])?$_SERVER['HTTP_X_REAL_IP']:$request->ip();
            $data=[
                'userId' => isset(\request()->authUser)?\request()->authUser['Id']:'',
                'authuser' => isset(\request()->authUser)?json_encode(\request()->authUser):'',
                'username'=> isset(\request()->authUser)?\request()->authUser['username']:'',
                'api' => $request->pathinfo(),
                'method' => $request->method(),
                'ip' => $ip,
                'system' => $request->server('HTTP_USER_AGENT'),
                'browser' => $request->server('HTTP_USER_AGENT'),
                'takesTime' => round((microtime(true) * 1000 - $request->server('REQUEST_TIME_FLOAT') * 1000), 2),
                'requestId' => $request->requestId,
                'responseHeaders' => json_encode($response->getHeader()),
                'responseBody' => json_encode($response->getContent()),
                'requestHeaders' => json_encode($request->header()),
                'requestBody' => json_encode($request->param()),
                'requestTime' => date('Y-m-d H:i:s', $request->server('REQUEST_TIME')),
                'company_Id' => isset(\request()->authUser)?\request()->authUser['company_Id']:'',
                'department_Id' => isset(\request()->authUser)?\request()->authUser['department_Id']:'',
            ];
            $address = $this->get_ip_address($ip);
            if ($address){
                $data['prov'] = $address['prov'];
                $data['city'] = $address['city'];
                $data['zipcode'] = $address['zipcode'];
                $data['isp'] = $address['isp'];
                $data['adcode'] = $address['adcode'];
                $data['country'] = $address['country'];
            }else{
                $data['prov'] = null;
                $data['city'] = null;
                $data['zipcode'] = null;
                $data['isp'] = null;
                $data['adcode'] =null;
                $data['country'] = null;
            }
            if (isset($request->menu_auth)){
                $menu_auth=$request->menu_auth;
                $data['menu_name'] = $menu_auth['menu_name'];
                $data['menu_url'] = $menu_auth['menu_url'];
                $data['menu_auth_code'] = implode(',', $menu_auth['menu_auth_code']);
                $data['menu_auth_name'] = implode(',', $menu_auth['menu_auth_name']);
            }

            if (isset($request->is_auth)){
                $data['is_auth'] = $request->is_auth;
            }else{
                $data['is_auth'] = 1;
            }
            Db::name('system_log')
                ->insert($data);
            // 回调行为
        }catch (Exception $e){
            file_put_contents('error.log', $e->getMessage().$e->getFile().$e->getLine());
        }

    }

    function get_ip_address($ip)
    {
        $url = "https://api.ipplus360.com/ip/geo/v1/city/?key=mOECvV97R5oyuRShCYXCvtjtMdsYJxStisEq2zDTnfnVwJc7lxZrGlA3QKubxLx9&ip=$ip&coordsys=WGS84";

        // 初始化 cURL 会话
        $ch = curl_init();

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应数据返回而不是输出
        curl_setopt($ch, CURLOPT_HEADER, false); // 不包含头信息

        $headers = [
            'Host: qifu-api.baidubce.com',
            'Accept: */*',
            'Origin: https://www.baidu.com',
            'Content-Type: application/json;charset=UTF-8',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0',
            'Referer: https://www.baidu.com/s?wd=ip&rsv_spt=1&rsv_iqid=0xf4a5d1820014d22e&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=02003390_108_hao_pg&rsv_enter=1&rsv_dl=tb&rsv_btype=t&inputT=3488&rsv_t=e09f5or2659A1wQFr%2BldPJUtj2Kbzet3aVM0OyqfD%2F6tairb%2FSs%2FEo6QsD5iG3mnhpTABXViMW38Yw&oq=%25E4%25B8%2580%25E4%25B8%25AAapi%25E6%258E%25A5%25E5%258F%25A3%252C%25E4%25BD%25BF%25E7%2594%25A8postman%25E5%258F%25AF%25E4%25BB%25A5%25E8%25AE%25BF%25E9%2597%25AE%252C%25E4%25BD%2586%25E6%2598%25AF%25E5%259C%25A8%25E7%25A8%258B%25E5%25BA%258F%25E4%25B8%25AD%25E4%25BD%25BF%25E7%2594%25A8curl%25E5%2588%2599%25E7%25BC%25BA%25E5%25B0%2591%25E4%25BA%2586%25E4%25B8%2580%25E9%2583%25A8%25E5%2588%2586%25E6%2595%25B0%25E6%258D%25AE&rsv_pq=a87ae7d600023268&rsv_sug3=172&rsv_sug1=107&rsv_sug7=101&rsv_sug2=0&rsv_sug4=4865',
            'sec-ch-ua-platform: Windows',
            'Access-Control-Allow-Credentials: true'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // 执行 cURL 会话
        $response = curl_exec($ch);

        // 检查是否有错误发生
        if (curl_errno($ch)) {
            file_put_contents('error.log', curl_error($ch));
            return null;
        }

        // 关闭 cURL 会话
        curl_close($ch);

        // 解析响应数据
        $data = json_decode($response, true);
        if ($data['code'] == 'Success') {
            file_put_contents('error.log', json_encode($data));
            return $data['data'];
        } else {
            return null;
        }
    }
}