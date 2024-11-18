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

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello', 'Index/hello');
Route::get('index', 'Index/index');
Route::get('pdf', 'Index/pdf');
Route::post('echoimage', 'Index/echoimage');
Route::get('name11', 'Index/name11');

Route::post('api/UploadOroiginal', 'api.Upload/UploadOroiginal');
Route::post('api/OriginalToImage', 'api.Image/OriginalToImage');
Route::post('api/CreateBackgroundImage', 'api.Image/CreateBackgroundImage');
Route::post('api/CopyImage', 'api.Image/CopyImage');
Route::post('api/ResizeImage', 'api.Image/ResizeImage');

Route::get('api/test', 'job.CopyToBackground/test');

Route::post('api/login', 'api.User/login');
Route::get('api/getRsaPublicKey', 'api.User/getRsaPublicKey');
Route::post('api/refreshToken', 'api.User/refreshToken');
Route::get('api/getMenu', 'api.User/getMenu');
Route::get('api/getUserInfo', 'api.User/getUserInfo');
Route::post('api/getUserList', 'api.User/getUserList');
Route::get('api/getAllRoles', 'api.User/getAllRoles');
Route::post('api/getUserRoles', 'api.User/getUserRoles');
Route::post('api/setUserRole', 'api.User/setUserRole');
Route::post('api/resetPassword', 'api.User/resetPassword');
Route::post('api/addUser', 'api.User/addUser');
Route::put('api/updateUser', 'api.User/updateUser');
Route::post('api/register', 'api.User/register');
Route::put('api/updatePassword', 'api.User/updatePassword');

Route::post('api/getSystemMenu', 'api.System/getSystemMenu');
Route::post('api/addSystemMenu', 'api.System/addSystemMenu');
Route::put('api/updateSystemMenu', 'api.System/updateSystemMenu');

Route::post('api/getDepartmentList', 'api.Department/getDepartmentList');
Route::post('api/addDepartment', 'api.Department/addDepartment');
Route::put('api/updateDepartment', 'api.Department/updateDepartment');

Route::post('api/getRoleList', 'api.Role/getRoleList');
Route::post('api/getRoleMenu', 'api.Role/getRoleMenu');
Route::post('api/getRoleMenuIds', 'api.Role/getRoleMenuIds');

Route::post('api/addRole', 'api.Role/addRole');
Route::post('api/addAndUpdateRoleHhandle', 'api.Role/addAndUpdateRoleHhandle');
Route::post('api/updateRole', 'api.Role/updateRole');
