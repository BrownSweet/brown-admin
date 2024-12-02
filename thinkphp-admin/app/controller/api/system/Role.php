<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-14 21:12
 */

namespace app\controller\api\system;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use think\facade\Db;
use think\Response;

class Role
{
    protected $middleware = [
        Auth::class
    ];

    /**
     * 获取角色列表
     * @return Response
     * @author Brown 2024/11/28 15:24
     */
    public function getRoleList()
    {
        try {
            $authUser = request()->authUser;
            $handle=['SystemRole:search','SystemRole:resetSearch', "SystemRole:view"];
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $params = request()->param();

            $query = Db::name('system_role')
                ->where('company_Id', $authUser['company_Id'])
                ->where('is_delete', 0);

            if (isset($params['name']) && $params['name'] != '') {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            }
            if (isset($params['code']) && $params['code'] != '') {
                $query->where('code', 'like', '%' . $params['code'] . '%');
            }
            if (isset($params['status']) && $params['status'] != '') {
                $query->where('status', $params['status']);
            }
            $res = $query->paginate([
                'page' => $params['currentPage'],
                'list_rows' => $params['pageSize']
            ])
                ->toArray();
            return JsonResponse::getInstance()->successResponse(1500, [
                'list' => $res['data'],
                'total' => $res['total'], // 总数
                'pageSize' => $params['pageSize'], // 每页显示条数
                'currentPage' => $res['current_page'], // 当前页码
                'lastPage' => $res['last_page']
            ]);
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 获取菜单权限
     *如果是总管理员  则获取全部菜单和权限
     * 如果是分部管理员或者普通角色 则获取自己的菜单和权限
     *
     * @return Response
     * @author Brown 2024/11/28 15:25
     */
    public function getRoleMenu()
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
                sm.menu_name as title,
                sm.parent_Id as parentId,
                GROUP_CONCAT(sma.menu_auth_name SEPARATOR ',') as auths,
                GROUP_CONCAT(sma.menu_auth_code SEPARATOR ',') as authCodes, 
                GROUP_CONCAT(sma.Id SEPARATOR ',') as authIds
                "
                )
                ->select()
                ->toArray();
            $super_role=Db::name('system_role')
                ->where('company_Id', $authUser['company_Id'])
                ->where('is_delete', 0)
                ->where('status',1)
                ->where('type',0)
                ->find();
            if ($super_role){
                $resultArray=[];
                //总管理员 全部权限  直接读取全部菜单的全部权限
                foreach ($menu as &$item) {
                    // 将当前项直接添加到结果数组中
                    $item['menuType']=0;
                    if (!empty($item['authIds']) && !empty($item['authCodes'])) {
                        $authIds = explode(',', $item['authIds']);
                        $authCodes = explode(',', $item['authCodes']);
                        $auths = explode(',', $item['auths']);

                        foreach ($authIds as $index => $authId) {
                            $resultArray[] = [
                                'id' => $authId . ':' . $authCodes[$index],
                                'title' => $auths[$index],
                                'parentId' => $item['id'],
                                'menuType' => 3,
                            ];
                        }
                    }
                    unset($item['auths']);
                    unset($item['authCodes']);
                    unset($item['authIds']);
                    $resultArray[] = $item;
                }
                return JsonResponse::getInstance()->successResponse(1507, $resultArray);
            }

            $department_super_role=Db::name('system_role')
                ->where('company_Id', $authUser['company_Id'])
                ->whereIn('id', explode(',', $authUser['role_Id']))
                ->where('is_delete', 0)
                ->where('status',1)
                ->where('type',1)
                ->select()->toArray();
            //如果是分部管理员  则获取其分部管理员的权限  如果是普通角色具有分配权限的能力，则只能获取自己的权限。
            $role_menu = Db::name('system_role_handle')
//                ->whereIn('role_Id', explode(',', $authUser['role_Id']))
                ->whereIn('role_Id', explode(',', !empty($department_super_role)?$department_super_role[0]['id']:$authUser['role_Id']))
                ->where('is_delete', 0)
                ->field('menu_Id')
                ->select()
                ->toArray();
            $role_menu = array_column($role_menu, 'menu_Id');
            //将全部菜单、权限和对应角色的菜单、权限进行对比，获取当前角色拥有的权限
            $menu = filterTree($menu, $role_menu);

            $role_handle = Db::name('system_role_handle')
                ->alias('srh')
                ->join('system_menu_auth sma', 'srh.menu_auth_Id=sma.Id')
                ->whereIn('role_Id', explode(',', $authUser['role_Id']))
                ->where('is_delete', 0)
                ->group('srh.menu_Id')
                ->field("srh.menu_Id,
            GROUP_CONCAT( sma.Id SEPARATOR ',') as Id,
            GROUP_CONCAT( sma.menu_auth_code SEPARATOR ',') as menu_auth_code,
            GROUP_CONCAT( sma.menu_auth_name SEPARATOR ',') as menu_auth_name
            ")
                ->distinct()
                ->select()->toArray();

            $role_handle = array_column($role_handle, null, 'menu_Id');

            $resultArray = [];

            foreach ($menu as &$item) {
                // 将当前项直接添加到结果数组中

                $item['menuType'] = 0;
                if (isset($role_handle[$item['id']])) {
                    $authIds = explode(',', $role_handle[$item['id']]['Id']);
                    $authCodes = explode(',', $role_handle[$item['id']]['menu_auth_code']);
                    $auths = explode(',', $role_handle[$item['id']]['menu_auth_name']);
                    foreach ($authIds as $index => $authId) {
                        $resultArray[] = [
                            'id' => $authId . ':' . $authCodes[$index],
                            'title' => $auths[$index],
                            'parentId' => $item['id'],
                            'menuType' => 3,
                        ];
                    }
                }
                unset($item['auths']);
                unset($item['authCodes']);
                unset($item['authIds']);
                $resultArray[] = $item;
            }
            return JsonResponse::getInstance()->successResponse(1507, $this->uniqueByColumn($resultArray, 'id'));
        }catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }
    private function uniqueByColumn($array, $column) {
        $temp = array_map(function($v) use ($column) {
            return is_array($v) ? $v[$column] : '';
        }, $array);

        $temp = array_unique($temp);
        $output = array_intersect_key($array, $temp);
        return array_values($output);
    }

    /**
     * 获取角色菜单权限
     * @return Response
     * @author Brown 2024/11/28 15:26
     */
    public function getRoleMenuIds()
    {
        try {
            $authUser = request()->authUser;
            $data = request()->param();

            $res = Db::name('system_role_handle')
                ->alias('srm')
                ->join('system_menu_auth sma', 'srm.menu_auth_Id = sma.Id')
                ->where('srm.role_Id', $data['id'])
                ->where('srm.is_delete', 0)
                ->field('sma.Id,menu_auth_code')
                ->select()
                ->toArray();
            $arr = [];
            foreach ($res as &$item) {
                array_push($arr, $item['Id'] . ':' . $item['menu_auth_code']);
            }
            return JsonResponse::getInstance()->successResponse(1508, $arr);
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 添加角色
     * @return Response|void
     * @author Brown 2024/11/28 15:26
     */
    public function addRole()
    {
        try {
            $authUser = request()->authUser;
            $handle=["SystemRole:add"];
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            $is_role = Db::name('system_role')
                ->where('name', $data['name'])
                ->where('company_Id', $authUser['company_Id'])
                ->find();
            if ($is_role) {
                return JsonResponse::getInstance()->failResponse(1501, []);
            }
            $res = Db::name('system_role')
                ->insert([
                    'name' => $data['name'],
                    'company_Id' => $authUser['company_Id'],
                    'createTime' => date('Y-m-d H:i:s'),
                    'updateTime' => date('Y-m-d H:i:s'),
                    'code' => $data['code'],
                    'status' => 1,
                    'remark' => $data['remark']
                ]);
            if ($res || !$res) {
                return JsonResponse::getInstance()->successResponse(1502, []);
            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }


    /**
     * 添加角色权限
     * @return Response
     * @author Brown 2024/11/28 15:27
     */
    public function addAndUpdateRoleHhandle()
    {

        try {
            $authUser = request()->authUser;
            $handle=["SystemRole:permission"];
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            $is_super = Db::name('system_role')
                ->where('company_Id', $authUser['company_Id'])
                ->where('id', $data['id'])
                ->where('type', 1)
                ->find();
            if ($is_super) {
                return JsonResponse::getInstance()->failResponse(1506, []);
            }
            $menu = Db::name('system_menu')
                ->alias('sm')
                ->join('system_menu_auth sma', 'sm.Id = sma.menu_Id', 'right')
                ->where('sm.status', 1)
                ->field(
                    "sm.Id as menuId,
                sm.parent_Id as parentId,
                menu_auth_name as auth,
                sma.menu_auth_code as authCode,
                sma.Id as menuAuthId"
                )
                ->select()
                ->toArray();
            $menu = array_column($menu, null, 'menuAuthId');

            $res = Db::name('system_role_handle')
                ->alias('srm')
                ->join('system_menu_auth sma', 'srm.menu_auth_Id = sma.Id')
                ->where('srm.role_Id', $data['id'])
                ->field('sma.Id,menu_auth_code,sma.menu_Id,is_delete')
                ->select()
                ->toArray();

            $res = array_column($res, null, 'Id');
            $delete_res = [];
            foreach ($res as $key => $value) {
                if ($value['is_delete'] == 1) {
                    array_push($delete_res, $value['Id']);
                }
            }
            $new_res = [];
            foreach ($res as $key => $value) {
                array_push($new_res, $value['Id']);
            }
            $new_arr = [];
            foreach ($data['menuIds'] as $value) {
                if (strpos($value, ':')) {
                    list($menu_auth_id, $code) = explode(':', $value);
                    array_push($new_arr, $menu_auth_id);
                }
            }

            $addedItems = array_diff($new_arr, $new_res);
            Db::startTrans();
            if (!empty($addedItems)) {
                $insertData = [];
                foreach ($addedItems as $value) {
//                    echo '新增';
                    $insertData[] = [
                        'role_Id' => $data['id'],
                        'menu_auth_Id' => $value,
                        'menu_Id' => $menu[$value]['menuId'],
                        'is_delete' => 0
                    ];
                }

                Db::name('system_role_handle')
                    ->insertAll($insertData);
            }
            $removedItems = array_diff($new_res, $new_arr);
            if (!empty($removedItems)) {
//                echo '删除';
                foreach ($removedItems as $value) {
                    $menu_auth_id = $value;
                    Db::name('system_role_handle')
                        ->where('role_Id', $data['id'])
                        ->where('menu_auth_Id', $menu_auth_id)
                        ->update(['is_delete' => 1]);
                }
            }
            foreach ($new_arr as $value) {
                if (in_array($value, $delete_res)) {
                    Db::name('system_role_handle')
                        ->where('role_Id', $data['id'])
                        ->where('menu_auth_Id', $value)
                        ->update(['is_delete' => 0]);
                }
            }
            Db::commit();
            return JsonResponse::getInstance()->successResponse(1505, []);
        } catch (\Exception $e) {
            Db::rollback();
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 更新角色名称等
     * @return Response|void
     * @author Brown 2024/11/28 15:28
     */
    public function updateRole()
    {
        try {

            $authUser = request()->authUser;
            $handle=["SystemRole:edit"];
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();

            $is_role = Db::name('system_role')
                ->where('name', $data['name'])
                ->where('company_Id', $authUser['company_Id'])
                ->find();
            if ($is_role&&$is_role['id']!=$data['id']) {
                return JsonResponse::getInstance()->failResponse(1501, []);
            }
            $res = Db::name('system_role')
                ->where('id', $data['id'])
                ->update([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'remark' => $data['remark'],
                ]);
            if ($res || !$res) {
                return JsonResponse::getInstance()->successResponse(1504, []);
            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 删除角色
     * @return Response|void
     * @author Brown 2024/11/28 15:28
     */
    public function deleteRole()
    {
        try {
            $params = request()->param();
            $authUser = request()->authUser;
            $handle=["SystemRole:delete"];
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $is_role=Db::name('system_role')
                ->where('id', $params['id'])
                ->find();
            if ($is_role['type']==0||$is_role['type']==1){
                return JsonResponse::getInstance()->failResponse(1511,[]);
            }
            $res=Db::name('system_role')
                ->where('id', $params['id'])
                ->update([
                    'is_delete' => 1
                ]);
            if ($res||!$res){
                return JsonResponse::getInstance()->successResponse(1512,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }

    }

    /**
     * 设置角色状态
     * @return Response|void
     * @author Brown 2024/11/28 17:27
     */
    public function setRoleStatus()
    {
        $params = request()->param();
        $authUser = request()->authUser;
        $handle=["SystemRole:status"];
        if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
            return JsonResponse::getInstance()->failResponse(1009,[]);
        }
        try {
            $is_role=Db::name('system_role')
                ->where('id', $params['id'])
                ->find();
            if ($is_role['type']==0||$is_role['type']==1){
                return JsonResponse::getInstance()->failResponse(1509,[]);
            }
            $res=Db::name('system_role')
                ->where('id', $params['id'])
                ->update([
                    'status' => $params['status']
                ]);
            if ($res||$res===0){
                return JsonResponse::getInstance()->successResponse(1510,[]);
            }
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }
}