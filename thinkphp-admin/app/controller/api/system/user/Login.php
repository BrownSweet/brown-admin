<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time:
 */

namespace app\controller\api\system\user;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use app\controller\core\JwtManager;
use think\facade\Db;

class Login
{
    protected $middleware = [
        Auth::class
    ];
    private $access_token_expires_in = 3600*2;
    private $refresh_token_expires_in = 3600*2*2;
    public function getRsaPublicKey()
    {
        if (!file_exists('public.key')) {
            $key = $this->generateRsaKeys();

            file_put_contents('public.key', $key['publicKey']);
            file_put_contents('private.key', $key['privateKey']);
            return json([
                'success' => true,
                'data' => [
                    'publicKey' => $key['publicKey']
                ]
            ]);
        } else {
            $key = file_get_contents('public.key');

        }
        return JsonResponse::getInstance()->successResponse(1000,[
            'publicKey' => $key
        ]);
    }

    private function generateRsaKeys($bits = 2048)
    {
        // 配置选项
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        // 创建资源
        $res = openssl_pkey_new($config);

        // 获取私钥
        openssl_pkey_export($res, $privateKey);

        // 获取公钥
        $details = openssl_pkey_get_details($res);
        $publicKey = $details["key"];

        return array(
            "privateKey" => $this->formatPemKey($privateKey),
            "publicKey" => $this->formatPemKey($publicKey)
        );
    }

    private function formatPemKey($key)
    {
        // 移除多余的空格和换行符
        $key = trim($key);

        // 分割成数组
        $lines = explode("\n", $key);

        // 确保每行不超过64个字符
        $formattedLines = [];
        foreach ($lines as $line) {
            if (strpos($line, '-----BEGIN') === 0 || strpos($line, '-----END') === 0) {
                $formattedLines[] = $line;
            } else {
                while (strlen($line) > 64) {
                    $formattedLines[] = substr($line, 0, 64);
                    $line = substr($line, 64);
                }
                if (!empty($line)) {
                    $formattedLines[] = $line;
                }
            }
        }

        // 重新组合成多行格式
        return implode("\n", $formattedLines) . "\n";
    }
    private function decryptDataWithPrivateKey($encryptedData)
    {
        $decryptedData = '';
        $privateKey = file_get_contents('private.key');
        $success = openssl_private_decrypt(base64_decode($encryptedData), $decryptedData, $privateKey);
        if (!$success) {
            return false;
        }
        return $decryptedData;
    }
    public function login()
    {
        $params = request()->param();

        $username = $params['username'];
        $password = $params['password'];
        $password = $this->decryptDataWithPrivateKey($params['password']);
        if (!$password) {
            return JsonResponse::getInstance()->failResponse(1101,[]);
        }
        $user = Db::name('system_user')
            ->where('username', $username)
            ->where('password', $password)
            ->find();
        if (!$user||$user['is_delete']) {
            return JsonResponse::getInstance()->failResponse(1100,[]);
        }
        if ($user['status']!=1){
            return JsonResponse::getInstance()->failResponse(1103,[]);
        }
        if ($user['role_Id']==0){
            return JsonResponse::getInstance()->failResponse(1008,[]);
        }
        $role = Db::name('system_role')
            ->whereIn('Id', explode(',',$user['role_Id']))
            ->where('status',1)
            ->where('is_delete',0)
            ->select()->toArray();
        $role_arr=array_column($role,'id');
        if (empty($role_arr)){
            return JsonResponse::getInstance()->failResponse(1007,[],'角色已禁用或不存在');
        }
        $token = JwtManager::getInstance()->generateAccessToken([
            'Id' => $user['Id'],
            'company_Id' => $user['company_Id'],
            'department_Id' => $user['department_Id'],
            'role_Id' => $user['role_Id'],
            'username'=> $user['username'],
        ], $this->access_token_expires_in);
        $refreshToken = JwtManager::getInstance()->generateRefreshToken([
            'is_refresh' => true,
            'Id' => $user['Id'],
            'company_Id' => $user['company_Id'],
            'department_Id' => $user['department_Id'],
            'role_Id' => $user['role_Id'],
        ], $this->refresh_token_expires_in);



        $role_Ids=array_column($role,'id');

        $role_handle = Db::name('system_role_handle')
            ->alias('srm')
            ->join('system_menu_auth sma', 'srm.menu_auth_Id = sma.Id')
            ->whereIn('srm.role_Id',$role_Ids)
            ->where('srm.is_delete',0)
            ->field('sma.Id,menu_auth_code')
            ->select()
            ->toArray();
        $permissions=[];
        foreach ($role_handle as $k=>$v){
            array_push($permissions,$v['Id'].':'.$v['menu_auth_code']);
        }
        return JsonResponse::getInstance()->successResponse(1102,[
            'avatar' => $user['avatar'],
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'expires' => date('Y-m-d H:i:s', time() + $this->access_token_expires_in),
            'username' => $username,
            'nickname' => $user['nickname'],
            'roles' => $role_Ids,
            'permissions' => $permissions
        ]);
    }

    public function updatePassword()
    {
        $params = request()->param();
        $company = $params['company'];
        $username = $params['username'];
        $password = $params['password'];
        $repeatPassword = $params['repeatPassword'];
        $password = $this->decryptDataWithPrivateKey($params['password']);
        $repeatPassword = $this->decryptDataWithPrivateKey($params['repeatPassword']);
        if (!$password || !$repeatPassword){
            return JsonResponse::getInstance()->failResponse(1101,[]);
        }
        $phone = $params['phone'];
        $is_company = Db::name('system_company')
            ->where('company', $company)
            ->find();
        if (!$is_company) {
            return JsonResponse::getInstance()->failResponse(1200,[]);
        }
        $company_id = $is_company['Id'];
        $is_user = Db::name('system_user')
            ->where('username', $username)
            ->where('company_id', $company_id)
            ->where('phone', $phone)
            ->find();
        if (!$is_user) {
            return JsonResponse::getInstance()->failResponse(1201,[]);
        }
        $user_id = $is_user['Id'];
        if ($password != $repeatPassword) {
            return JsonResponse::getInstance()->failResponse(1202,[]);
        }
        if ($password == $is_user['password']) {
            return JsonResponse::getInstance()->failResponse(1203,[]);
        }
        $data = [
            'password' => $password
        ];
        $res = Db::name('system_user')
            ->where('Id', $user_id)
            ->update($data);
        if ($res) {
            return JsonResponse::getInstance()->successResponse(1204,[]);
        } else {
            return JsonResponse::getInstance()->failResponse(1205,[]);
        }
    }


    public function register()
    {
        $params = request()->param();
        $username = $params['username'];
//        $password = $this->decryptDataWithPrivateKey($params['password']);
//        $repeatPassword = $this->decryptDataWithPrivateKey($params['repeatPassword']);
        $password = $params['password'];
        $repeatPassword = $params['repeatPassword'];
        if (!$password){
            return JsonResponse::getInstance()->failResponse(1101,[]);
        }

        if ($password != $repeatPassword) {
            return JsonResponse::getInstance()->failResponse(1302,[]);
        }

        $phone = $params['phone'];
        $company = $params['company'];
        $is_company = Db::name('system_company')
            ->where('company', $company)
            ->count();
        if ($is_company) {
            return JsonResponse::getInstance()->failResponse(1300,[]);
        }
        $is_user = Db::name('system_user')
            ->where('username', $username)
            ->count();
        if ($is_user) {
            return JsonResponse::getInstance()->failResponse(1301,[]);
        }
        Db::startTrans();
        try {
            $company_id = Db::name('system_company')->insertGetId([
                'company' => $company,
                'phone' => $phone
            ]);
            $department_id = Db::name('system_department')->insertGetId([
                'parentId' => 0,
                'name' => $company,
                'company_Id' => $company_id,
                'status' => 1,
                'principal' => $username,
                'type'=>1,
                'createTime'=>date('Y-m-d H:i:s'),
                'remark'=>date('Y-m-d H:i:s').'注册账号时自动创建',
                'phone'=>$phone,
                'updateTime'=>date('Y-m-d H:i:s')
            ]);

            $user_id = Db::name('system_user')->insertGetId([
                'username' => $username,
                'nickname' => $username,
                'password' => $password,
                'company_Id' => $company_id,
                'phone' => $phone,
                'department_Id' => $department_id,
                'createTime' => date('Y-m-d H:i:s'),
                'updateTime' => date('Y-m-d H:i:s'),
                'role_Id' => 0,
                'status'=>1,
                'sex'=>1,
                'avatar'=>'https://avatars.githubusercontent.com/u/44761321',
                'remark'=> date('Y-m-d H:i:s').'注册账号时自动创建'
            ]);

            if ($user_id) {
                $res=event('RegisterSuccess',[
                    'user_Id'=>$user_id,
                    'department_Id'=>$department_id,
                    'company_Id'=>$company_id,
                ]);
                Db::commit();
                return JsonResponse::getInstance()->successResponse(1303,[]);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return JsonResponse::getInstance()->failResponse(1002,[],$e->getMessage().$e->getFile().$e->getLine());
        }
    }

    public function refreshToken()
    {
        $params = request()->param();

        $refreshToken = $params['refreshToken'];
        $is_refresh = JwtManager::getInstance()->refreshToken($refreshToken);
        if ($is_refresh) {
            $decode=JwtManager::getInstance()->verifyRefreshToken($refreshToken);
            $data=(array)$decode['payload']['data'];
            return JsonResponse::getInstance()->successResponse(1006,[
                'accessToken' => JwtManager::getInstance()->generateAccessToken($data, $this->access_token_expires_in),
                'refreshToken' => JwtManager::getInstance()->generateRefreshToken(array_merge(['is_refresh' => true], $data), $this->refresh_token_expires_in),
                'expires' => date('Y-m-d H:i:s', time()+$this->access_token_expires_in),
            ]);
        } else {
            return JsonResponse::getInstance()->failResponse(1004,[]);
        }
    }
}