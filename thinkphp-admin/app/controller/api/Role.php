<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-14 21:12
 */

namespace app\controller\api;

use app\controller\api\middleware\Auth;
use think\facade\Db;

class Role
{
    protected $middleware = [
        Auth::class
    ];

    public function getRoleList()
    {
        $authUser = request()->authUser;

        $res=Db::name('system_role')
            ->where('company_Id',1)
            ->select();
        return json([
            'success' => true,
            'data' => [
                'list' => $res,
                'total' => 1, // 总数
                'pageSize' => 10, // 每页显示条数
                'currentPage' => 1 // 当前页码
            ]
        ]);
    }

    public function getRoleMenu()
    {
        $authUser = request()->authUser;

        $menu = Db::name('system_menu')
            ->alias('sm')
            ->join('system_menu_auth sma', 'sm.Id = sma.menu_Id','left')
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
        $resultArray = [];

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
        return json([
            'success' => true,
            'data' => $resultArray
        ]);
    }

    public function getRoleMenuIds(){
        $authUser = request()->authUser;
        $data = request()->param();

        $res=Db::name('system_role_handle')
            ->alias('srm')
            ->join('system_menu_auth sma', 'srm.menu_auth_Id = sma.Id')
            ->where('srm.role_Id',$data['id'])
            ->where('srm.is_delete',0)
            ->field('sma.Id,menu_auth_code')
            ->select()
            ->toArray();
        $arr=[];
        foreach ($res as &$item){
            array_push($arr,$item['Id'].':'.$item['menu_auth_code']);
        }
        return json([
            'success'=>true,
            'data'=>$arr
        ]);
    }

    public function addRole()
    {
        $authUser = request()->authUser;
        $data = request()->param();
        $is_role=Db::name('system_role')
            ->where('name',$data['name'])
            ->where('company_Id',1)
            ->find();
        if($is_role){
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>500,
                   'message'=>'角色名称已存在'
                ]
            ]);
        }
        $res=Db::name('system_role')
            ->insert([
                'name' => $data['name'],
                'company_Id' => 1,
                'createTime' => date('Y-m-d H:i:s'),
                'updateTime'=>date('Y-m-d H:i:s'),
                'code'=>$data['code'],
                'status'=>1,
                'remark'=>$data['remark']
            ]);
        if($res){
            return json([
                'success'=>true,
                'data'=>[
                    'code'=>200,
                    'message'=>'添加成功'
                ]
            ]);
        }else{
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>500,
                    'message'=>'添加失败'
                ]
            ]);
        }

    }


    public function addAndUpdateRoleHhandle()
    {
        $authUser = request()->authUser;
        $data = request()->param();
        $menu = Db::name('system_menu')
            ->alias('sm')
            ->join('system_menu_auth sma', 'sm.Id = sma.menu_Id','right')
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
        $menu=array_column($menu,null,'menuAuthId');

        $res=Db::name('system_role_handle')
            ->alias('srm')
            ->join('system_menu_auth sma', 'srm.menu_auth_Id = sma.Id')
            ->where('srm.role_Id',$data['id'])
            ->field('sma.Id,menu_auth_code,sma.menu_Id,is_delete')
            ->select()
            ->toArray();

        $res=array_column($res,null,'Id');
        $delete_res=[];
        foreach ($res as $key=>$value){
            if($value['is_delete']==1){
                array_push($delete_res,$value['Id']);
            }
        }
        $new_res=[];
        foreach ($res as $key=>$value){
            array_push($new_res,$value['Id']);
        }
        $new_arr=[];
        foreach ($data['menuIds'] as $value){
            if(strpos($value,':')){
                list($menu_auth_id, $code) = explode(':', $value);
                array_push($new_arr,$menu_auth_id);
            }
        }

        $addedItems = array_diff($new_arr, $new_res);
        Db::startTrans();
        try {
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
//            print_r($new_arr);
//            print_r($delete_res);
            foreach ($new_arr as $value){
                if(in_array($value,$delete_res)){
                    Db::name('system_role_handle')
                        ->where('role_Id', $data['id'])
                        ->where('menu_auth_Id', $value)
                        ->update(['is_delete' => 0]);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    public function updateRole()
    {
        $authUser = request()->authUser;
        $data = request()->param();

        $res=Db::name('system_role')
            ->where('id', $data['id'])
            ->update([
                'name' => $data['name'],
                'code' => $data['code'],
               'remark' => $data['remark'],
            ]);
        if ($res) {
            return json([
               'success' => true,
                'data' => [
                    'code' => 200,
                   'message' => '更新成功'
                ]
            ]);
        } else {
            return json([
               'success' => false,
                'data' => [
                    'code' => 500,
                   'message' => '更新失败'
                ]
            ]);
        }
    }

    public function updateRoleHhandle()
    {
        $authUser = request()->authUser;
        $data = request()->param();

        print_r($data);
    }
}