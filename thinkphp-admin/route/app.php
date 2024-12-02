<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

//登录注册
Route::post('api/login', 'api.system.user.Login/login');
Route::get('api/getRsaPublicKey', 'api.system.user.Login/getRsaPublicKey');
Route::post('api/refreshToken', 'api.system.user.Login/refreshToken');
Route::post('api/register', 'api.system.Login/register');
Route::put('api/updatePassword', 'api.system.Login/updatePassword');
Route::get('api/getMenu', 'api.system.user.User/getMenu');
//用户管理
Route::get('api/getUser', 'api.system.user.User/getUser');
Route::get('api/getUserById', 'api.system.user.User/getUserById');
Route::get('api/getUserInfo', 'api.system.user.User/getUserInfo');
Route::get('api/getUserList', 'api.system.user.User/getUserList');
Route::get('api/getAllRoles', 'api.system.user.User/getAllRoles');
Route::get('api/getUserRoles', 'api.system.user.User/getUserRoles');
Route::put('api/setUserRole', 'api.system.user.User/setUserRole');
Route::put('api/resetPassword', 'api.system.user.User/resetPassword');
Route::post('api/addUser', 'api.user.system.User/addUser');
Route::put('api/updateUser', 'api.user.system.User/updateUser');
Route::delete('api/deleteUser', 'api.user.system.User/deleteUser');
Route::put('api/setUserStatus', 'api.user.system.User/setUserStatus');
//菜单管理
Route::get('api/getSystemMenu', 'api.system.System/getSystemMenu');
Route::post('api/addSystemMenu', 'api.system.System/addSystemMenu');
Route::put('api/updateSystemMenu', 'api.system.System/updateSystemMenu');
//部门管理
Route::get('api/getDepartmentList', 'api.system.Department/getDepartmentList');
Route::post('api/addDepartment', 'api.system.Department/addDepartment');
Route::put('api/updateDepartment', 'api.system.Department/updateDepartment');
Route::delete('api/deleteDepartment', 'api.system.Department/deleteDepartment');

//角色管理
Route::get('api/getRoleList', 'api.system.Role/getRoleList');
Route::get('api/getRoleMenu', 'api.system.Role/getRoleMenu');
Route::get('api/getRoleMenuIds', 'api.system.Role/getRoleMenuIds');
Route::post('api/addRole', 'api.system.Role/addRole');
Route::post('api/addAndUpdateRoleHhandle', 'api.system.Role/addAndUpdateRoleHhandle');
Route::put('api/updateRole', 'api.system.Role/updateRole');
Route::put('api/setRoleStatus', 'api.system.Role/setRoleStatus');
Route::delete('api/deleteRole', 'api.system.Role/deleteRole');


Route::get('api/getLoginLog', 'api.system.Monitor/getLoginLog');
Route::get('api/getOperationLog', 'api.system.Monitor/getOperationLog');
Route::get('api/getSystemLog', 'api.system.Monitor/getSystemLog');
Route::get('api/getSystemLogDetail', 'api.system.Monitor/getSystemLogDetail');
