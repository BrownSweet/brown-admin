<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-04 13：37
 */

namespace app\controller\api;

use app\controller\core\JwtManager;
use app\controller\core\MyRedis;
use think\facade\Db;
use app\controller\api\middleware\Auth;

class User
{
    protected $middleware = [
        Auth::class => ['except' => ['login', 'register', 'getRsaPublicKey', 'updatePassword']]
    ];

    private $access_token_expires_in = 3600*24;
    private $refresh_token_expires_in = 3600*24*7;
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
            return json([
                'success' => true,
                'data' => [
                    'publicKey' => $key
                ]
            ]);
        }
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
            return json([
                'success' => false,
                'data' => [
                    'msg' => '密码错误'
                ]
            ]);
        }
        $user = Db::name('system_user')
            ->where('username', $username)
            ->where('password', $password)
            ->find();
        if (!$user) {
            return json([
                'success' => 40100,
                'msg' => '用户名或密码错误',
                'data' => [

                ]
            ]);
        }
        $token = JwtManager::getInstance()->generateAccessToken([
            'Id' => $user['Id'],
            'company_Id' => $user['company_Id'],
            'department_Id' => $user['department_Id'],
            'role_Id' => $user['role_Id'],
        ], $this->access_token_expires_in);
        $refreshToken = JwtManager::getInstance()->generateRefreshToken([
            'is_refresh' => true,
            'Id' => $user['Id'],
            'company_Id' => $user['company_Id'],
            'department_Id' => $user['department_Id'],
            'role_Id' => $user['role_Id'],
        ], $this->refresh_token_expires_in);

        $role = Db::name('system_role')
            ->whereIn('Id', explode(',',$user['role_Id']))
            ->select()->toArray();

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
            array_push($permissions,$v['menu_auth_code']);
        }
        return json([
            'success' => true,
            'data' => [
                'avatar' => $user['avatar'],
                'accessToken' => $token,
                'refreshToken' => $refreshToken,
                'expires' => date('Y-m-d H:i:s', time() + $this->access_token_expires_in),
                'username' => $username,
                'nickname' => $user['nickname'],
                'roles' => $role_Ids,
                'permissions' => $permissions,
                'companyId'=>$user['company_Id']
            ]
        ]);
    }



    public function updatePassword()
    {
        $params = request()->param();
        $company = $params['company'];
        $username = $params['username'];
        $password = $this->decryptDataWithPrivateKey($params['password']);
        $repeatPassword = $this->decryptDataWithPrivateKey($params['repeatPassword']);
        $phone = $params['phone'];

        $is_company = Db::name('system_company')
            ->where('company', $company)
            ->find();
        if (!$is_company) {
            return json([
                'success' => 40100,
                'msg' => '公司不存在',
                'data' => [

                ]
            ]);
        }
        $company_id = $is_company['Id'];
        $is_user = Db::name('system_user')
            ->where('username', $username)
            ->where('company_id', $company_id)
            ->where('phone', $phone)
            ->find();
        if (!$is_user) {
            return json([
                'success' => 40100,
                'msg' => '账号或手机号不存在',
                'data' => [

                ]
            ]);
        }
        $user_id = $is_user['Id'];
        if ($password != $repeatPassword) {
            return json([
                'success' => 40100,
                'msg' => '密码不一致',
                'data' => [

                ]
            ]);
        }
        if ($password == $is_user['password']) {
            return json([
                'success' => 40100,
                'msg' => '新密码不能与旧密码一致',
                'data' => [

                ]
            ]);
        }
        $data = [
            'password' => $password
        ];
        $res = Db::name('system_user')
            ->where('Id', $user_id)
            ->update($data);
        if ($res) {
            return json([
                'success' => 200,
                'msg' => '修改成功',
                'data' => [

                ]
            ]);
        } else {
            return json([
                'success' => 40100,
                'msg' => '修改失败',
                'data' => [

                ]
            ]);
        }
    }


    public function register()
    {
        $params = request()->param();
        $username = $params['username'];
        $password = $this->decryptDataWithPrivateKey($params['password']);
        $repeatPassword = $this->decryptDataWithPrivateKey($params['repeatPassword']);
        if ($password != $repeatPassword) {
            return json([
                'success' => false,
                'code' => 40100,
                'data' => [
                    'msg' => '密码不一致'
                ]
            ]);
        }
        $phone = $params['phone'];
        $company = $params['company'];
        $is_company = Db::name('system_company')
            ->where('company', $company)
            ->count();
        if ($is_company) {
            return json([
                'success' => true,
                'code' => 40100,
                'data' => [
                    'msg' => '您注册的公司已存在'
                ]
            ]);
        }
        $is_user = Db::name('system_user')
            ->where('username', $username)
            ->count();
        if ($is_user) {
            return json([
                'success' => true,
                'code' => 40100,
                'data' => [
                    'msg' => '账户已存在'
                ]
            ]);
        }
        Db::startTrans();
        try {
            $company_id = Db::name('system_company')->insertGetId([
                'company' => $company,
                'phone' => $phone
            ]);
            $user_id = Db::name('system_user')->insertGetId([
                'username' => $username,
                'nickname' => $username,
                'password' => $password,
                'company_Id' => $company_id,
                'phone' => $phone
            ]);
            if ($user_id) {
                Db::commit();
                return json([
                    'success' => true,
                    'code' => '200',
                    'data' => [
                        'msg' => '注册成功'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'success' => false,
                'data' => [
                    'msg' => $e->getMessage()
                ]
            ]);
        }
    }

    public function getUserInfo()
    {
        $authUser = request()->authUser;

        return json([
            'success' => true,
            'data' => [
                'avatar' => 'https://avatars.githubusercontent.com/u/44761321',
                'username' => 'admin',
                'nickname' => 'Brown',
                'email' => '455764041@qq.com',
                'phone' => '13930390572',
                'description' => '1111'
            ]
        ]);
    }

    public function addUser()
    {
        $authUser = request()->authUser;
        $params = request()->param();

        $is_user=Db::name('system_user')
            ->where('username', $params['username'])
            ->find();
        if ($is_user) {
            return json([
                'success' => false,
                'data' => [
                    'msg' => '用户名称已存在'
                ]
            ]);
        }
        $data = [
            'username' => $params['username'],
            'nickname' => $params['nickname'],
            'password' => $params['password'],
            'company_Id' => 1,
            'sex'=> $params['sex'],
            'phone' => $params['phone'],
            'email' => $params['email'],
            'department_Id' => $params['parentId'],
            'role_Id' => 0,
            'createTime' => date('Y-m-d H:i:s'),
            'updateTime' => date('Y-m-d H:i:s'),
            'status' => 1,
            'remark' => $params['remark']
        ];
        $res = Db::name('system_user')->insert($data);
        if ($res) {
            return json([
                'success' => true,
                'data' => [
                    'msg' => '添加成功'
                ]
            ]);
        } else {
            return json([
                'success' => false,
                'data' => [
                    'msg' => '添加失败'
                ]
            ]);
        }

    }
    public function resetPassword()
    {
        $params = request()->param();
        $res=Db::name('system_user')
            ->where('Id', $params['id'])
            ->update([
                'password' => $params['password']
            ]);
        if ($res) {
            return json([
                'success' => true,
                'data'=>[
                    'message'=>'修改成功'
                ]
            ]);
        }else{
            return json([
                'success' => false,
                'data'=>[
                    'message'=>'修改失败'
                ]
            ]);
        }
    }
    public function updateUser(){
        $params = request()->param();
        $res=Db::name('system_user')
            ->where('Id', $params['id'])
            ->update([
                'username' => $params['username'],
                'nickname' => $params['nickname'],
                'sex'=> $params['sex'],
                'phone' => $params['phone'],
                'email' => $params['email'],
                'department_Id' => $params['parentId'],
                'updateTime' => date('Y-m-d H:i:s'),
                'remark' => $params['remark']
            ]);
        if ($res) {
            return json([
                'success' => true,
                'data' => [
                    'message' => '修改成功'
                ]
            ]);
        }else {
            return json([
                'success' => false,
                'data' => [
                    'message' => '修改失败'
                ]
            ]);
        }

    }

    public function getAllRoles()
    {
        $authUser = request()->authUser;
        $res=Db::name('system_role')
            ->where('company_Id', 1)
            ->where('status', 1)
            ->field(
                'id,name'
            )
            ->select()
            ->toArray();
        return json([
            'success' => true,
            'data' => $res
        ]);
    }
    public function getUserRoles()
    {
        $authUser = request()->authUser;
        $prams=request()->param();

        $res=Db::name('system_user')
            ->where('Id', $prams['userId'])
            ->where('status', 1)
            ->field(
                'role_Id'
            )
            ->find();
        if ($res['role_Id']==0){
            $data=[];
        }else{
            $data= array_map("intval",explode(',',$res['role_Id']));
        }

        return json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function setUserRole()
    {
        $authUser = request()->authUser;
        $params = request()->param();

        $res=Db::name('system_user')
            ->where('Id', $params['userId'])
            ->update([
                'role_Id' => implode(',',$params['ids'])
            ]);

        if ($res){
            return json([
                'success' => true,
                'data' => [
                    'message' => '设置成功'
                ]
            ]);
        }else{
            return json([
                'success' => false,
                'data' => [
                    'message' => '设置失败'
                ]
            ]);
        }


    }
    public function refreshToken()
    {
        $params = request()->param();

        $refreshToken = $params['refreshToken'];
//
        $is_refresh = JwtManager::getInstance()->refreshToken($refreshToken);
        if ($is_refresh) {
            $decode=JwtManager::getInstance()->verifyRefreshToken($refreshToken);
            $data=(array)$decode['payload']['data'];
            return json([
                'success' => true,
                'data' => [
                    'accessToken' => JwtManager::getInstance()->generateAccessToken($data, 3600*24),
                    'refreshToken' => JwtManager::getInstance()->generateRefreshToken(array_merge(['is_refresh' => true], $data), 3600 * 24 * 14),
                    'expires' => date('Y-m-d H:i:s', time()),
                ]
            ]);
        } else {
            return json([
                'success' => false,
                'data' => [
                    'msg' => 'token已过期'
                ]
            ]);
        }
    }

    public function getMenu()
    {
        $authUser = request()->authUser;

        $menu = Db::name('system_menu')
            ->alias('sm')
            ->join('system_menu_auth sma', 'sm.Id = sma.menu_Id', 'left')
            ->where('sm.status', 1)
            ->group('sm.Id')
            ->field(
                "sm.Id as id,
                sm.menu_name,
                sm.component_name,
                sm.icon,
                sm.menu_url,
                sm.showLink,
                sm.showParent,
                sm.rank,
                sm.parent_Id as parentId,
                GROUP_CONCAT(sma.menu_auth_name SEPARATOR ',') as auths,
                GROUP_CONCAT(sma.menu_auth_code SEPARATOR ',') as authCodes,
                GROUP_CONCAT(sma.Id SEPARATOR ',') as authIds
                "
            )
            ->select()
            ->toArray();

        $role_menu=Db::name('system_role_handle')
            ->whereIn('role_Id', explode(',', $authUser['role_Id']))
            ->where('is_delete', 0)
            ->field('menu_Id')
            ->select()
            ->toArray();
        $role_menu=array_column($role_menu,'menu_Id');
        $menu=filterTree($menu,$role_menu);
        $role_handle = Db::name('system_role_handle')
            ->alias('srh')
            ->join('system_menu_auth sma', 'srh.menu_auth_Id=sma.Id')
            ->whereIn('role_Id', explode(',', $authUser['role_Id']))
            ->where('is_delete', 0)
            ->group('srh.menu_Id')
            ->field("srh.menu_Id,
            GROUP_CONCAT(sma.Id SEPARATOR ',') as Id,
            GROUP_CONCAT(sma.menu_auth_code SEPARATOR ',') as menu_auth_code,
            GROUP_CONCAT(sma.menu_auth_name SEPARATOR ',') as menu_auth_name
            ")

            ->select()->toArray();
        $role_handle=array_column($role_handle,null,'menu_Id');

        foreach ($menu as $key => &$value) {
            $value['path'] = $value['menu_url'];
//            $menu_url_arr=explode('/',$value['menu_url']);
            $value['name'] = $value['component_name'];
            $value['new_auths'] = [];

            if(isset($role_handle[$value['id']])){
                $value['auths'] = explode(',', $role_handle[$value['id']]['menu_auth_name']);
                $value['authCodes'] = explode(',', $role_handle[$value['id']]['menu_auth_code']);
                $value['authIds'] = explode(',', $role_handle[$value['id']]['Id']);
                for ($i = 0; $i < count($value['auths']); $i++) {
                    array_push($value['new_auths'],  $value['authCodes'][$i]);
                }
            }

            $value['meta'] = [
                'title' => $value['menu_name'],
                'icon' => $value['icon'],
                'rank' => $value['rank'],
                'showLink' => $value['showLink'] == 1 ? true : false,
                'showParent' => $value['showParent'] == 1 ? true : false,
                'auths' => $value['new_auths']
            ];
            unset($value['menu_name']);
            unset($value['icon']);
            unset($value['menu_url']);
            unset($value['showLink']);
            unset($value['showParent']);
            unset($value['rank']);
            unset($value['auths']);
            unset($value['authCodes']);
            unset($value['authIds']);
            unset($value['new_auths']);
        }

        $menu = buildTree($menu);
        $new_menu = [];
        foreach ($menu as $key => $value) {
            array_push($new_menu, $value);
        }
        return json([
            'success' => true,
            'data' => $new_menu
        ]);
    }

    public function getUserList()
    {
        $authUser = request()->authUser;
        $res=Db::name('system_user')
            ->where('company_id', 1)
            ->field(
                'id,avatar,username,nickname,phone,email,sex,status,remark,createTime,department_Id'
            )
            ->select()
            ->toArray();
        $dept=Db::name('system_department')
            ->where('company_Id', 1)
            ->select()
            ->toArray();
        $dept=array_column($dept,null,'id');
        foreach ($res as $key => &$value) {
            $value['dept']=[
                'id' => $value['department_Id'],
                'name' => $dept[$value['department_Id']]['name']
            ];
            $value['createTime']=strtotime($value['createTime'])*1000;
            unset($value['department_Id']);
        }

        return json([
            'success' => true,
            'data' => [
                'list' => $res,
                'total' => 2,
                'pageSize' => 10,
                'currentPage' => 1
            ]
        ]);
    }
}