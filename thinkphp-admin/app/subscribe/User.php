<?php
declare (strict_types = 1);

namespace app\subscribe;

use think\facade\Db;

class User
{
    public function onLogin($user){
        print_r($user);
    }

    public function onRegisterSuccess($param)
    {
        $res=Db::name('system_role_handle')
            ->where('role_Id',3)
            ->where('is_delete',0)
            ->field('menu_Id,menu_auth_Id')
            ->select()
            ->toArray();
        $role_Id=Db::name('system_role')
            ->insertGetId([
                'name'=>'超级管理员',
                'company_Id'=>$param['company_Id'],
                'createTime'=>date('Y-m-d H:i:s'),
                'updateTime'=>date('Y-m-d H:i:s'),
                'code'=>'admin',
                'status'=>1,
                'remark'=>date('Y-m-d H:i:s').'注册时自动创建',
                'type'=>1
            ]);
        Db::name('system_user')
            ->where('Id',$param['user_Id'])
            ->update(['role_Id'=>$role_Id]);
        foreach ($res as $k=>&$v){
            $v['role_Id']=$role_Id;
            $v['is_delete']=0;
        }
        $res=Db::name('system_role_handle')->insertAll($res);
        return $res;
    }
}
