<?php
/**
 *   Author: Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-24 15:44
 */

namespace app\controller\core\json;

use think\Response;

class JsonResponse
{
    // 私有静态属性，用于保存类的唯一实例
    private static $instance = null;

    // 私有构造方法，防止外部实例化
    private function __construct() {}

    // 防止克隆
    private function __clone() {}

    // 静态方法，用于获取类的唯一实例
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static $statusCodes = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        409 => 'Conflict',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        600 => 'Custom Status', // 自定义状态码示例

        // 公共
        1000 => '操作成功',
        1001 => '参数错误',
        1002 => '系统错误',
        1003 => '未登录',
        1004 => 'token过期',
        1005 => '错误token',
        1101 => 'RSA解密后密码错误',
        1006 => '换取token成功',
        1007 => '角色已禁用或不存在,请联系管理员',
        1103 => '账号已被禁用，请联系管理员',
        1008 => '还未分配角色，请联系管理员',
        1009 => '您没有权限',
        // 登录
        1100 => '账号或密码错误',
        1102 => '登录成功',
        //忘记密码
        1200 => '公司不存在',
        1201 => '账号或手机号不存在',
        1202 => '两次密码不一致',
        1203 => '新密码不能与旧密码一致',
        1204 => '修改密码成功',
        1205 => '修改失败',
        //注册
        1300 => '公司已存在',
        1301 => '账号已存在',
        1302 => '两次密码不一致',
        1303 => '注册成功',

        //用户
        1400 => '登录后成功获取菜单',
        1401 => '用户已存在',
        1402 => '添加用户成功',
        1403 => '添加用户失败',
        1404 => '修改用户成功',
        1405 => '修改用户失败',
        1406 => '重置密码成功',
        1407 => '获取可分配角色成功',
        1408 => '获取用户的角色列表成功',
        1409 => '给用户分配角色成功',
        1410 => '获取用户列表成功',
        1411 => '该用户为超级管理员或管理员，禁止禁用',
        1412 => '修改用户状态成功',
        1413 => '该用户为超级管理员或管理员，禁止删除',
        1414 => '删除用户成功',

        // 角色
        1500 => '获取角色列表成功',
        1501 => '角色已存在',
        1502 => '添加角色成功',
        1503 => '添加角色失败',
        1504 => '修改角色成功',
        1505 => '添加权限成功',
        1506 => '超级管理员无法修改',
        1507 => '获取权限列表成功',
        1508 => '获取角色权限列表成功',
        1509 => '该角色为超级管理员或管理员，禁止禁用',
        1510 => '修改角色状态成功',
        1511 => '该角色为超级管理员或管理员，禁止删除',
        1512 => '删除角色成功',
        // 部门
        1600 => '获取部门列表成功',
        1601 => '您只能创建一个公司',
        1602 => '添加部门成功',
        1603 =>'修改部门成功',
        1604 =>'部门下存在用户，禁止删除',
        1605 =>'删除部门成功',
        1606 => '已经存在该名称部门',
        //菜单管理
        1700 => '获取菜单列表成功',
        1701 => '添加菜单成功',
        1702 => '修改菜单成功',
        1703 => '菜单名称已存在',
        1704 => '菜单路径已存在',
        1705 => '菜单权限添加失败，可到修改菜单中添加权限',

        //监控-日志
        1800 => '获取监控日志成功',
        1801 => '获取操作日志成功',
        1802 => '获取登录日志成功',
        1803 => '获取系统日志成功',
        1804 => '获取日志成功',
    ];

    /**
     * 获取状态码对应的消息
     *
     * @param int $code 状态码
     * @return string 状态消息
     */
    public function getStatusMessage($code)
    {
        return isset(self::$statusCodes[$code]) ? self::$statusCodes[$code] : 'Unknown Status Code';
    }

    /**
     * 构建响应数据
     *
     * @param bool $success 是否成功
     * @param int $code 状态码
     * @param mixed $data 响应数据
     * @return array 响应数据数组
     */
    private function buildResponseData($success, $code, $data)
    {
        return [
            'message' => $this->getStatusMessage($code),
            'code' => $code,
            'requestId' => request()->requestId,
            'success' => $success,
            'data' => $data,
        ];
    }

    /**
     * 成功响应
     *
     * @param int $code 状态码
     * @param mixed $data 响应数据
     * @return Response
     */
    public function successResponse($code, $data)
    {
        $responseData = $this->buildResponseData(true, $code, $data);
        return json($responseData);
    }

    /**
     * 失败响应
     *
     * @param int $code 状态码
     * @param mixed $data 响应数据
     * @return Response
     */
    public function failResponse($code, $data,$error_message='')
    {
        $responseData = $this->buildResponseData(false, $code, $data);
        $responseData['error_message']=$error_message;
        return json($responseData);
    }

    public function failSystemResponse(\Exception $error_message)
    {
        return $this->failResponse(1002,[],$error_message->getMessage().$error_message->getFile().$error_message->getLine());
    }
}
