<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-04 13：37
 */

namespace app\controller\api\system\user;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use think\Response;

class User
{
    protected $middleware = [
        Auth::class
    ];

    public function getMenu()
    {
        try {
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
            $role=Db::name('system_role')
                ->whereIn('id', explode(',', $authUser['role_Id']))
                ->where('status',1)
                ->where('is_delete',0)
                ->select()
                ->toArray();
            $role_arr=array_column($role,'id');

            $role_menu=Db::name('system_role_handle')
                ->whereIn('role_Id', $role_arr)
                ->where('is_delete', 0)
                ->field('menu_Id')
                ->select()
                ->toArray();
            $role_menu=array_column($role_menu,'menu_Id');
            $menu=filterTree($menu,$role_menu);
            $role_handle = Db::name('system_role_handle')
                ->alias('srh')
                ->join('system_menu_auth sma', 'srh.menu_auth_Id=sma.Id')
                ->whereIn('role_Id', $role_arr)
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
            return JsonResponse::getInstance()->successResponse(1400, $new_menu);
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failResponse(1002,[],$e->getMessage().$e->getLine());
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
        try {
            $handle=[
                'SystemUser:add'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $authUser = request()->authUser;
            $params = request()->param();
            $is_user=Db::name('system_user')
                ->where('username', $params['username'])
                ->where('is_delete', 0)
                ->find();
            if ($is_user) {
                return JsonResponse::getInstance()->failResponse(1401,[]);
            }
            $data = [
                'username' => $params['username'],
                'nickname' => $params['nickname'],
                'password' => $params['password'],
                'company_Id' => $authUser['company_Id'],
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
                return JsonResponse::getInstance()->successResponse(1402,[]);
            } else {
                return JsonResponse::getInstance()->failResponse(1403,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }
    public function resetPassword()
    {

        try {
            $handle=[
                'SystemUser:resetPassword'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();

            $res=Db::name('system_user')
                ->where('Id', $params['id'])
                ->update([
                    'password' => $params['password']
                ]);
            if ($res||!$res) {
                return JsonResponse::getInstance()->successResponse(1406,[]);
            }
        }catch (Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 修改用户信息
     * @return Response
     * @author Brown 2024/11/28 17:33
     */
    public function updateUser(){
        try {
            $handle=[
                "SystemUser:edit"
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();
            $is_user=Db::name('system_user')
                ->where('username', $params['username'])
                ->find();
            if ($is_user){
                return JsonResponse::getInstance()->failResponse(1401,[]);
            }
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
                return JsonResponse::getInstance()->successResponse(1404,[]);
            }else {
                return JsonResponse::getInstance()->successResponse(1405,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }


    }

    /**
     * 获取可分配角色列表
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Brown 2024/11/24 09:57
     */
    public function getAllRoles()
    {
        try {
            $authUser = request()->authUser;
            $res=Db::name('system_role')
                ->where('company_Id', $authUser['company_Id'])
                ->where('status', 1)
                ->where('is_delete', 0)
                ->field(
                    'id,name'
                )
                ->select()
                ->toArray();
            return JsonResponse::getInstance()->successResponse(1407,$res);
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 通过用户id获取当前用户所拥有的角色
     * @return JsonResponse|Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Brown 2024/11/24 10:05
     */
    public function getUserRoles()
    {
        try {
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
            return JsonResponse::getInstance()->successResponse(1408,$data);
        }catch (Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }

    }

    /**
     * 分配用户角色
     * @return Response|void
     * @author Brown 2024/11/28 17:32
     */
    public function setUserRole()
    {
        try {
            $handle = [
                'SystemUser:dispatchRole',
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();
            $res=Db::name('system_user')
                ->where('Id', $params['userId'])
                ->update([
                    'role_Id' => implode(',',$params['ids'])
                ]);

            if ($res||!$res){
                return JsonResponse::getInstance()->successResponse(1409,[]);
            }
        }catch (Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }


    /**
     * 获取用户列表
     * @return Response
     * @author Brown 2024/11/28 17:31
     */
    public function getUserList()
    {
        try {
            $handle = [
                'SystemUser:search',
                'SystemUser:view',
                'SystemUser:resetSearch',

            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }

            $params = request()->param();
            if (!isset($params['pageSize'])){
                $params['pageSize'] = 10;
                $params['page'] = 1;
            }
            $query = Db::name('system_user')
                ->alias('su')
                ->join('system_department sd', 'sd.Id=su.department_id')
                ->where('su.company_id', $authUser['company_Id'])
                ->where('su.is_delete', 0);
            if (isset($params['deptId']) && $params['deptId'] != null) {
                $query->where('su.department_id', $params['deptId']);
            }
            if(isset($params['username']) && $params['username'] != null) {
                $query->where('su.username', 'like', '%' . $params['username'] . '%');
            }
            if (isset($params['status']) && $params['status'] != null) {
                $query->where('su.status', $params['status']);
            }
            if(isset($params['phone']) && $params['phone'] != null) {
                $query->where('su.phone', 'like', '%' . $params['phone'] . '%');
            }

            $res = $query
                ->field(
                    'su.id,sd.name as department_name, avatar,username,nickname,su.phone,su.email,sex,su.status,su.remark,su.createTime,department_Id'
                )
                ->paginate([
                    'list_rows' => $params['pageSize'], // 每页数量
                    'page' => $params['currentPage'], // 当前页
                ])
                ->toArray();
            foreach ($res['data'] as &$value) {
                $value['dept'] = [
                    'id' => $value['department_Id'],
                    'name' => $value['department_name']
                ];
                $value['createTime'] = strtotime($value['createTime']) * 1000;
                unset($value['department_Id']);
                unset($value['department_name']);
            }
            return JsonResponse::getInstance()->successResponse(1410,[
                'list' => $res['data'],
                'total' => $res['total'],
                'pageSize' => $params['pageSize'],
                'currentPage' => $res['current_page'],
                'lastPage' => $res['last_page']
            ]);
        } catch (Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }


    /**
     * 设置用户状态
     * @return Response|void
     * @author Brown 2024/11/28 17:32
     */
    public function setUserStatus()
    {
        try {
            $handle = [
                'SystemUser:status'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();
            $is_user=Db::name('system_user')
                ->alias('su')
                ->where('su.id', $params['id'])
                ->find();
            $role=Db::name('system_role')
                ->whereIn('Id', explode(',', $is_user['role_Id']))
                ->select()
                ->toArray();
            foreach ($role as $value){
                if ($value['type']==0||$value['type']==1){
                    return JsonResponse::getInstance()->failResponse(1411,[],'该用户为超级管理员或管理员，禁止禁用');
                }
            }
            $res=Db::name('system_user')
                ->where('id', $params['id'])
                ->update([
                    'status' => $params['status']
                ]);
            if ($res||!$res){
                return JsonResponse::getInstance()->successResponse(1412,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }

    }

    /**
     * 删除用户
     * @return Response|void
     * @author Brown 2024/11/28 17:32
     */
    public function deleteUser()
    {
        try {
            $handle = [
                'SystemUser:delete'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();
            $is_user=Db::name('system_user')
                ->alias('su')
                ->where('su.id', $params['id'])
                ->find();
            $role=Db::name('system_role')
                ->whereIn('Id', explode(',', $is_user['role_Id']))
                ->select()
                ->toArray();
            foreach ($role as $value){
                if ($value['type']==0||$value['type']==1){
                    return JsonResponse::getInstance()->failResponse(1413,[],'该用户为超级管理员或管理员，禁止禁用');
                }
            }
            $res=Db::name('system_user')
                ->where('id', $params['id'])
                ->update([
                    'is_delete' => 1
                ]);
            if ($res||!$res){
                return JsonResponse::getInstance()->successResponse(1414,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

}