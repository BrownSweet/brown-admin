<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-13 15:51
 */

namespace app\controller\api\system;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;


class Department
{
    protected $middleware = [
        Auth::class
    ];

    public function getDepartmentList()
    {
        try {
            $handle=["SystemDept:view","SystemDept:resetSearch"];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $res = Db::name('system_department')
                ->where('company_Id', $authUser['company_Id'])
                ->where('is_delete', 0)
                ->select()
                ->toArray();
            foreach ($res as &$item) {
                $item['createTime'] = strtotime($item['createTime']) * 1000;
            }
            return JsonResponse::getInstance()->successResponse(1600, $res);
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function addDepartment()
    {
        try {
            $handle=["SystemDept:addDept","SystemDept:addDeptNode"];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            if ($data['id'] == 0 && $data['parentId'] == 0) {
                return JsonResponse::getInstance()->failResponse(1601, []);
            }
            if ($this->check_department($data['name'], $authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1606, []);
            }
            $res = Db::name('system_department')->insert(
                [
                    'name' => $data['name'],
                    'parentId' => $data['parentId'] ,
                    'company_Id' => $authUser['company_Id'],
                    'createTime' => date('Y-m-d H:i:s', time()),
                    'updateTime' => date('Y-m-d H:i:s', time()),
                    'principal' => $data['principal'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'sort' => $data['sort'],
                    'status' => $data['status'],
                    'type' => $data['parentId'] == 0 ? 1 : 3,
                    'remark' => $data['remark']
                ]
            );
            if ($res || !$res) {

                return JsonResponse::getInstance()->successResponse(1602, []);

            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function updateDepartment()
    {
        try {
            $handle=["SystemDept:edit"];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            if (!isset($data['parentId'])){
                return JsonResponse::getInstance()->failResponse(1601, []);
            }
            if ($this->check_department($data['name'], $authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1606, []);
            }
            $res = Db::name('system_department')
                ->where('id', $data['id'])
                ->update(
                    [
                        'name' => $data['name'],
                        'parentId' => $data['parentId'],
                        'company_Id' => $authUser['company_Id'],
                        'principal' => $data['principal'],
                        'phone' => $data['phone'],
                        'email' => $data['email'],
                        'remark' => $data['remark'],
                        'sort' => $data['sort'],
                        'status' => $data['status']
                    ]
                );
            $department = Db::name('system_department')
                ->where('company_Id', $authUser['company_Id'])
                ->select()
                ->toArray();
            $ids = getChildIds($data['id'], $department);
            $getUser = Db::name('system_user')
                ->where('department_Id', 'in', $ids)
                ->select()
                ->toArray();
            foreach ($getUser as $key => $item) {
                $role = Db::name('system_role')
                    ->whereIn('id', explode(',', $item['role_Id']))
                    ->select()->toArray();
                foreach ($role as $roleItem) {
                    if ($roleItem['type'] == 1 || $roleItem['type'] == 0) {
                        unset($getUser[$key]);
                    }
                }
            }
            $getUser_Id = array_column($getUser, 'Id');
            Db::name('system_user')
                ->whereIn('Id', $getUser_Id)
                ->update([
                    'status' => $data['status']
                ]);
            Db::name('system_department')
                ->whereIn('id', $ids)
                ->update([
                    'status' => $data['status']
                ]);

            if ($res || !$res) {
                return JsonResponse::getInstance()->successResponse(1603, []);
            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function deleteDepartment()
    {
        try {
            $handle=["SystemDept:delete"];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $authUser = request()->authUser;
            $data = request()->param();
            $department = Db::name('system_department')
                ->where('company_Id', $authUser['company_Id'])
                ->select()
                ->toArray();
            $ids = getChildIds($data['id'], $department);
            $res = Db::name('system_user')
                ->whereIn('department_Id', $ids)
                ->where('status', 1)
                ->where('is_delete', 0)
                ->count();
            if ($res) {
                return JsonResponse::getInstance()->failResponse(1604, []);
            } else {
                $res = Db::name('system_department')
                    ->where('id', $data['id'])
                    ->update([
                        'is_delete' => 1
                    ]);
                if ($res || $res === 0) {
                    return JsonResponse::getInstance()->successResponse(1605, []);
                }
            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    /**
     * 检查部门名称是否重复
     * @param $name
     * @param $company_Id
     * @return array|false|mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Brown 2024/11/26 18:02
     */
    private function check_department($name, $company_Id)
    {
        $is_department=Db::name('system_department')
            ->where('company_Id',$company_Id)
            ->where('name',$name)
            ->where('is_delete',0)
            ->find();
        if ($is_department){
            return $is_department;
        }else{
            return false;
        }
    }

}