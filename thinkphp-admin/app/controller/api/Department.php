<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-13 15:51
 */

namespace app\controller\api;

use app\controller\api\middleware\Auth;
use think\facade\Db;


class Department
{
    protected $middleware = [
        Auth::class
    ];

    public function getDepartmentList()
    {
        $authUser=request()->authUser;
        $res=Db::name('system_department')
            ->where('company_Id',1)
            ->select()
            ->toArray();
        foreach ($res as &$item){
            $item['createTime']=strtotime($item['createTime'])*1000;
        }
        return json([
            'success'=>true,
            'data'=>$res
        ]);
    }

    public function addDepartment(){
        $authUser=request()->authUser;
        $data=request()->post();

        if ($data['parentId']==0){
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>500,
                   'message'=>'您只能有一个公司'
                ]
            ]);
        }
        $res=Db::name('system_department')->insert(
            [
                'name' => $data['name'],
                'parentId' => $data['parentId'],
                'company_Id' => 1,
                'createTime' => date('Y-m-d H:i:s', time()),
                'principal'=> $data['principal'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'sort'=> $data['sort'],
                'status'=> $data['status'],
                'type'=> $data['parentId']==0?1:3,
                'remark'=> $data['remark']
            ]
        );
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

    public function updateDepartment()
    {
        $authUser=request()->authUser;
        $data=request()->post();
        $res=Db::name('system_department')
            ->where('id',$data['id'])
            ->update(
                [
                    'name' => $data['name'],
                    'parentId' => $data['parentId'],
                    'company_Id' => 1,
                    'principal'=> $data['principal'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'remark'=> $data['remark'],
                   'sort'=> $data['sort'],
                   'status'=> $data['status']
                ]
            );
        if($res){
            return json([
                'success'=>true,
                'data'=>[
                    'code'=>200,
                    'message'=>'修改成功'
                ]
            ]);
        }else{
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>500,
                    'message'=>'修改失败'
                ]
            ]);
        }

    }
}