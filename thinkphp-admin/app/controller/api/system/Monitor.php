<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-30 22:47
 */

namespace app\controller\api\system;

use app\controller\api\system\middleware\Auth;
use app\controller\core\json\JsonResponse;
use think\facade\Db;

class Monitor
{
    protected $middleware = [
        Auth::class
    ];
    public function getLoginLog(){

        try {
            $params = request()->param();
            $authUser = request()->authUser;
            if (!isset($params['pageSize'])){
                $params['pageSize'] = 10;
                $params['currentPage'] = 1;
            }

            $query=Db::name('system_log')
                ->where('api','api/login');
//                ->where('company_Id',$authUser['company_Id']);

            if (isset($params['username']) && $params['username'] != null) {
                $query->whereLike('requestBody', '%'.$params['username'].'%');
            }
            if (isset($params['status']) && $params['status'] != null) {
                $query->whereNotNull('authUser');
            }
            if(isset($params['loginTime']) && $params['loginTime'] != null) {
                $query->whereBetweenTime('requestTime', $params['loginTime'][0],$params['loginTime'][1]);
            }
            $res=$query->field('id,ip,system,responseBody,requestTime,prov,city,requestBody')
                ->order('requestTime','desc')
                ->paginate([
                'list_rows' => $params['pageSize'], // 每页数量
                'page' => $params['currentPage'], // 当前页
            ])

                ->toArray();

            foreach ($res['data'] as $key=>&$item){
                $response=json_decode(json_decode($item['responseBody'],true),true);
                $request=json_decode($item['requestBody'],true);
                $uaInfo = $this->parseUserAgent($item['system']);
                $item['address']=$item['prov'].'-'.$item['city'];
                $item['system'] = $uaInfo['platform'];
                $item['browser'] = $uaInfo['browser'];
                $item['behavior']='账号登录';
                $item['loginTime']=strtotime($item['requestTime'])*1000;
                if ($response['code']==1102){
                    $item['username']=$response['data']['username'];
                    $item['status']=1;
                }else{
                    $item['username']=$request['username'];
                    $item['status']=0;
                }
                unset($res['data'][$key]['department_Id']);
                unset($res['data'][$key]['requestTime']);
                unset($res['data'][$key]['responseBody']);
                unset($res['data'][$key]['requestBody']);
                unset($res['data'][$key]['prov']);
                unset($res['data'][$key]['city']);
            }
            return JsonResponse::getInstance()->successResponse(1802,[
                'list'=>$res['data'],
                'total'=>$res['total'],
                'pageSize' => $params['pageSize'],
                'currentPage' => $res['current_page'],
                'lastPage' => $res['last_page']
            ]);
        }catch (\Exception $e){
            return JsonResponse::getInstance()->failSystemResponse($e);
        }
    }

    public function getOperationLog()
    {
        $authUser = request()->authUser;
        $params = request()->param();
        if (!isset($params['pageSize'])){
            $params['pageSize'] = 10;
            $params['currentPage'] = 1;
        }
        $query=Db::name('system_log')
            ->whereNotNull('menu_name');
        if (isset($params['module']) && $params['module'] != null){
            $query->whereLike('menu_name','%'.$params['module'].'%');
        }
        if (isset($params['status']) && $params['status'] != null){
            $query->where('is_auth',$params['status']);
        }
        if (isset($params['operatingTime']) && $params['operatingTime'] != null){
            $query->whereBetweenTime('requestTime', $params['operatingTime'][0],$params['operatingTime'][1]);
        }

       $res=$query->field('id,ip,system,username,menu_name as module,menu_auth_name as summary ,requestTime,prov,city,is_auth as status')
           ->order('requestTime','desc')
           ->paginate([
                'list_rows' => $params['pageSize'], // 每页数量
                'page' => $params['currentPage'], // 当前页
            ])
            ->toArray();
        foreach ($res['data'] as $key=>&$item){
            $item['address']=$item['prov'].'-'.$item['city'];
            $uaInfo = $this->parseUserAgent($item['system']);
            $item['system'] = $uaInfo['platform'];
            $item['browser']=$uaInfo['browser'];
            $item['operatingTime']=strtotime($item['requestTime'])*1000;
            unset($res['data'][$key]['prov']);
            unset($res['data'][$key]['city']);
            unset($res['data'][$key]['requestTime']);
        }
        return JsonResponse::getInstance()->successResponse(1801,[
            'list'=>$res['data'],
            'total'=>$res['total'],
            'pageSize' => $params['pageSize'],
            'currentPage' => $res['current_page'],
            'lastPage' => $res['last_page']
        ]);
    }

    public function getSystemLog()
    {
        $authUser = request()->authUser;
        $params = request()->param();
        if (!isset($params['pageSize'])){
            $params['pageSize'] = 10;
            $params['currentPage'] = 1;
        }
        $query=Db::name('system_log');
        if (isset($params['module']) && $params['module'] != null){
            $query->whereLike('menu_name','%'.$params['module'].'%');
        }
        if (isset($params['requestTime']) && $params['requestTime'] != null){
            $query->whereBetweenTime('requestTime', $params['requestTime'][0],$params['requestTime'][1]);
        }

        $res=$query->field('id,ip,api as url,method,takesTime,menu_name as module,system,requestTime,prov,city,is_auth as status')
            ->order('requestTime','desc')
            ->paginate([
                'list_rows' => $params['pageSize'], // 每页数量
                'page' => $params['currentPage'], // 当前页
            ])
            ->order('requestTime','desc') // 按照时间倒序
            ->toArray();
        foreach ($res['data'] as $key=>&$item){
            $item['address']=$item['prov'].'-'.$item['city'];
            $uaInfo = $this->parseUserAgent($item['system']);
            $item['system'] = $uaInfo['platform'];
            $item['browser']=$uaInfo['browser'];
            $item['operatingTime']=strtotime($item['requestTime'])*1000;
            unset($res['data'][$key]['prov']);
            unset($res['data'][$key]['city']);
            unset($res['data'][$key]['requestTime']);
        }
        return JsonResponse::getInstance()->successResponse(1801,[
            'list'=>$res['data'],
            'total'=>$res['total'],
            'pageSize' => $params['pageSize'],
            'currentPage' => $res['current_page'],
            'lastPage' => $res['last_page']
        ]);


    }

    public function getSystemLogDetail()
    {
        $params = request()->param();
        $res=Db::name('system_log')
            ->where('Id',$params['id'])
            ->field('id,ip,api as url,prov,city,system,menu_name as module,requestTime,method,takesTime,requestId,requestHeaders,requestBody,responseHeaders,responseBody')
            ->find();
        $auiInfo = $this->parseUserAgent($res['system']);
        $res['address']=$res['prov'].'-'.$res['city'];
        $res['system']=$auiInfo['platform'];
        $res['browser']=$auiInfo['browser'];
        $res['requestTime']=strtotime($res['requestTime'])*1000;

        $res['responseBody']=json_decode(json_decode($res['responseBody'],true),true);
        $res['responseHeaders']=json_decode($res['responseHeaders'],true);

        $res['requestHeaders']=json_decode($res['requestHeaders'],true);
        $res['requestBody']=json_decode($res['requestBody'],true);

        unset($res['prov']);
        unset($res['city']);

        return JsonResponse::getInstance()->successResponse(1803,$res);

    }
    function parseUserAgent($userAgent)
    {
        $browser = 'Unknown';
        $version = 'Unknown';
        $platform = 'Unknown';

        // 检查浏览器
        if (preg_match('/MSIE ([0-9]+)[.0-9]*/i', $userAgent, $matches)) {
            $browser = 'Internet Explorer';
            $version = $matches[1];
        } elseif (preg_match('/Trident\/.*rv:([0-9]+)\./i', $userAgent, $matches)) {
            $browser = 'Internet Explorer';
            $version = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9]+)[.0-9]*/i', $userAgent, $matches)) {
            $browser = 'Firefox';
            $version = $matches[1];
        } elseif (preg_match('/Chrome\/([0-9]+)[.0-9]*/i', $userAgent, $matches)) {
            $browser = 'Chrome';
            $version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9]+)[.0-9]*/i', $userAgent, $matches)) {
            $browser = 'Safari';
            $version = $matches[1];
        } elseif (preg_match('/Opera\/([0-9]+)[.0-9]*/i', $userAgent, $matches)) {
            $browser = 'Opera';
            $version = $matches[1];
        }

        // 检查平台
        if (preg_match('/Windows NT 10.0/i', $userAgent)) {
            $platform = 'Windows 10';
        } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
            $platform = 'Windows 8';
        } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/Windows NT 6.0/i', $userAgent)) {
            $platform = 'Windows Vista';
        } elseif (preg_match('/Windows NT 5.1/i', $userAgent)) {
            $platform = 'Windows XP';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $platform = 'Mac OS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iOS/i', $userAgent)) {
            $platform = 'iOS';
        }

        return [
            'browser' => $browser,
            'version' => $version,
            'platform' => $platform
        ];
    }

}