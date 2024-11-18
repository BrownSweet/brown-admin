<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-10 14:58
 */

namespace app\controller\api;

use app\BaseController;
use app\controller\api\middleware\Auth;
use think\facade\Db;

class System
{
    protected $middleware = [
        Auth::class
    ];
    public function getSystemMenu()
    {
        $authUser=request()->authUser;
        $menu = Db::name('system_menu')
            ->alias('sm')
            ->join('system_menu_auth sma', 'sm.Id = sma.menu_Id','left')
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
        foreach ($menu as &$item){
            $item['formItems']=[];
            if(!empty($item['auths'])){
                $item['auths']=explode(',',$item['auths']);
                $item['authCodes']=explode(',',$item['authCodes']);
                $item['authIds']=explode(',',$item['authIds']);
                for ($i=0;$i<count($item['auths']);$i++){
                    $item['formItems'][]=[
                        'pathName'=>$item['auths'][$i],
                        'pathIdentifier'=>$item['authCodes'][$i],
                        'authId'=>$item['authIds'][$i]
                    ];
                }
            }else{
                $item['formItems'][]=[
                    'pathName'=>'',
                    'pathIdentifier'=>''
                ];
            }
        }
        return json([
            'success'=>true,
            'data'=>$menu
        ]);
    }

    public function addSystemMenu(){
        $data=request()->post();
        $authUser=request()->authUser;
        $is_menu=Db::name('system_menu')
            ->where('parent_Id', $data['parent_Id'])
            ->where('menu_name', $data['menu_name'])
            ->find();

        if($is_menu){
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>400,
                    'message'=>'菜单名称已存在'
                ]
            ]);
        }
        $is_menu_url=Db::name('system_menu')
            ->where('menu_url', $data['menu_url'])
            ->find();
        if($is_menu_url){
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>400,
                    'message'=>'菜单路径已存在'
                ]
            ]);
        }
        $res=Db::name('system_menu')->insertGetId(
            [
                'menu_name'=>$data['menu_name'],
                'menu_url'=>$data['menu_url'],
                'component_name'=>$data['component_name'],
                'parent_Id'=>$data['parent_Id'],
                'rank'=>$data['rank'],
                'showLink'=>$data['showLink'],
                'showParent'=>$data['showParent'],
                'icon'=>$data['icon'],
                'status'=>1
            ]
        );
        if($res){
            if(isset($data['formItems'])&&!empty($data['formItems'])){
                foreach ($data['formItems'] as &$item){
                    $item['menu_Id']=$res;
                    $item['menu_auth_name']=$item['pathName'];
                    $item['menu_auth_code']=$item['pathIdentifier'];
                    unset($item['pathIdentifier']);
                    unset($item['pathName']);
                }

                $res=Db::name('system_menu_auth')->insertAll($data['formItems']);
                if ($res) {
                    return json([
                        'success' => true,
                        'data' => [
                            'code' => 200,
                            'message' => '添加成功'
                        ]
                    ]);
                } else {
                    return json([
                        'success' => false,
                        'data' => [
                            'code' => 40100,
                            'message' => '添加权限失败，可到修改菜单中添加权限'
                        ]
                    ]);
                }
            }else{
                return json([
                    'success'=>true,
                    'data'=>[
                        'code'=>200,
                        'message'=>'添加成功'
                    ]
                ]);
            }

        }else{
            return json([
                'success'=>true,
                'data'=>[
                'code'=>200,
                'message'=>'添加成功'
                ]
            ]);
        }

    }

    public function updateSystemMenu(){
        $data=request()->post();
        $authUser=request()->authUser;
        Db::startTrans();
        try{
            $is_menu=Db::name('system_menu')
                ->where('Id',$data['id'])
                ->update(
                    [
                        'menu_name'=>$data['menu_name'],
                        'menu_url'=>$data['menu_url'],
                        'component_name'=>$data['component_name'],
                         'parent_Id'=>$data['parent_Id'],
                         'rank'=>$data['rank'],
                        'showLink'=>$data['showLink'],
                        'showParent'=>$data['showParent'],
                         'icon'=>$data['icon'],
                    ]
                );

            $res=Db::name('system_menu_auth')
                ->where('menu_Id',$data['id'])
                ->select()->toArray();
            $res=array_column($res,null,'Id');
            foreach ($data['formItems'] as $key=>$item){
                if(isset($item['authId'])){
                    //需要更新的
                    if(isset($res[$item['authId']])){
                        $is_update=Db::name('system_menu_auth')
                            ->where('Id',$item['authId'])
                            ->update(
                                [
                                    'menu_auth_name'=>$item['pathName'],
                                    'menu_auth_code'=>$item['pathIdentifier'],
                                ]
                            );

                        unset($res[$item['authId']]);

                    }
                }else{
                    //需要新增的
                    Db::name('system_menu_auth')
                        ->insert(
                            [
                                'menu_Id'=>$data['id'],
                                'menu_auth_name'=>$item['pathName'],
                                'menu_auth_code'=>$item['pathIdentifier'],
                            ]
                        );
                }

            }
            if(!empty($res)){
                $is_delete=Db::name('system_menu_auth')
                    ->where('Id','in',array_keys($res))
                    ->delete();

            }

            Db::commit();
            return json([
                'success'=>true,
                'data'=>[
                    'code'=>200,
                    'message'=>'修改成功'
                ]
            ]);
        }catch (\Exception $e){
            echo $e->getMessage().$e->getLine();
            Db::rollback();
            return json([
                'success'=>false,
                'data'=>[
                    'code'=>500,
                    'message'=>'系统错误'
                ]
            ]);
        }

    }
}