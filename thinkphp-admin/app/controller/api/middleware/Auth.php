<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-11 12:24
 */

namespace app\controller\api\middleware;

use app\controller\core\JwtManager;

class Auth
{
    public function handle($request, \Closure $next){
        $token = request()->header('Authorization');
        
        preg_match('/Bearer\s(\S+)/', $token, $matches);
        $token = isset($matches[1]) ? $matches[1] : null;

        $user = JwtManager::getInstance()->verifyAccessToken($token);
        if (!$user['valid']){
            return json(['success'=>false,
                'data'=>[
                    'code'=>401,
                    'msg'=>'token已失效',
                    'error_msg'=>$user['error']
                ]
            ]);
        }
        $data=(array)$user['payload']['data'];
        $request->authUser=$data;
        return $next($request);
    }
}