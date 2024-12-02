<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-10 14:58
 */

namespace app\controller\api\system;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use think\facade\Db;

class System
{
    protected $middleware = [
        Auth::class
    ];

    public function getSystemMenu()
    {
        try {
            $handle=[
                'SystemMenu:resetSearch',
                'SystemMenu:search',
                'SystemMenu:view',
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
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
            foreach ($menu as &$item) {
                $item['formItems'] = [];
                if (!empty($item['auths'])) {
                    $item['auths'] = explode(',', $item['auths']);
                    $item['authCodes'] = explode(',', $item['authCodes']);
                    $item['authIds'] = explode(',', $item['authIds']);
                    for ($i = 0; $i < count($item['auths']); $i++) {
                        $item['formItems'][] = [
                            'pathName' => $item['auths'][$i],
                            'pathIdentifier' => $item['authCodes'][$i],
                            'authId' => $item['authIds'][$i]
                        ];
                    }
                } else {
                    $item['formItems'][] = [
                        'pathName' => '',
                        'pathIdentifier' => ''
                    ];
                }
            }
            return JsonResponse::getInstance()->successResponse(1700, $menu);
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function addSystemMenu()
    {
        try {
            $handle=[
                'SystemMenu:addMenu',
                'SystemMenu:addMenuNode'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            $is_menu = Db::name('system_menu')
                ->where('parent_Id', $data['parent_Id'])
                ->where('menu_name', $data['menu_name'])
                ->find();

            if ($is_menu) {
                return JsonResponse::getInstance()->failResponse(1703, []);
            }
            $is_menu_url = Db::name('system_menu')
                ->where('menu_url', $data['menu_url'])
                ->find();
            if ($is_menu_url) {
                return JsonResponse::getInstance()->failResponse(1704, []);
            }
            $is_add_menu = Db::name('system_menu')->insertGetId(
                [
                    'menu_name' => $data['menu_name'],
                    'menu_url' => $data['menu_url'],
                    'component_name' => $data['component_name'],
                    'parent_Id' => $data['parent_Id'],
                    'rank' => $data['rank'],
                    'showLink' => $data['showLink'],
                    'showParent' => $data['showParent'],
                    'icon' => $data['icon'],
                    'status' => 1
                ]
            );
            if ($is_add_menu) {
                if (isset($data['formItems']) && !empty($data['formItems'])) {
                    foreach ($data['formItems'] as &$item) {
                        $item['menu_Id'] = $is_add_menu;
                        $item['menu_auth_name'] = $item['pathName'];
                        $item['menu_auth_code'] = $item['pathIdentifier'];
                        unset($item['pathIdentifier']);
                        unset($item['pathName']);
                    }

                    $res = Db::name('system_menu_auth')->insertAll($data['formItems']);
                    if ($res) {
                        return JsonResponse::getInstance()->successResponse(1701, []);
                    } else {
                        return JsonResponse::getInstance()->failResponse(1705, []);
                    }
                } else {
                    return JsonResponse::getInstance()->successResponse(1701, []);
                }

            } else {
                return JsonResponse::getInstance()->successResponse(1701, []);
            }
        } catch (\Exception $e) {
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function updateSystemMenu()
    {

        Db::startTrans();
        try {
            $handle=[
                'SystemMenu:editMenu'
            ];
            $authUser = request()->authUser;
            if(!checkRoleHandle($handle,$authUser['role_Id'],$authUser['company_Id'])){
                return JsonResponse::getInstance()->failResponse(1009,[]);
            }
            $data = request()->param();
            $is_menu = Db::name('system_menu')
                ->where('Id', $data['id'])
                ->update(
                    [
                        'menu_name' => $data['menu_name'],
                        'menu_url' => $data['menu_url'],
                        'component_name' => $data['component_name'],
                        'parent_Id' => $data['parent_Id'],
                        'rank' => $data['rank'],
                        'showLink' => $data['showLink'],
                        'showParent' => $data['showParent'],
                        'icon' => $data['icon'],
                    ]
                );

            $res = Db::name('system_menu_auth')
                ->where('menu_Id', $data['id'])
                ->select()->toArray();
            $res = array_column($res, null, 'Id');
            foreach ($data['formItems'] as $key => $item) {
                if (isset($item['authId'])) {
                    //需要更新的
                    if (isset($res[$item['authId']])) {
                        $is_update = Db::name('system_menu_auth')
                            ->where('Id', $item['authId'])
                            ->update(
                                [
                                    'menu_auth_name' => $item['pathName'],
                                    'menu_auth_code' => $item['pathIdentifier'],
                                ]
                            );

                        unset($res[$item['authId']]);

                    }
                } else {
                    //需要新增的
                    Db::name('system_menu_auth')
                        ->insert(
                            [
                                'menu_Id' => $data['id'],
                                'menu_auth_name' => $item['pathName'],
                                'menu_auth_code' => $item['pathIdentifier'],
                            ]
                        );
                }

            }
            if (!empty($res)) {
                $is_delete = Db::name('system_menu_auth')
                    ->where('Id', 'in', array_keys($res))
                    ->delete();

            }

            Db::commit();
            return JsonResponse::getInstance()->successResponse(1702, []);
        } catch (\Exception $e) {
            Db::rollback();
            return JsonResponse::getInstance()->failSystemResponse($e);
        }

    }
}