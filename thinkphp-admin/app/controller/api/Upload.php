<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time:
 */

namespace app\controller\api;

use app\controller\BaseConfig;
use app\controller\core\ImageNameFormatter;
use app\controller\core\ImageProcessing;
use app\controller\UploadFile;
use think\response\Json;

class Upload
{


    /**
     * 上传原始文件，并保存到cos中
     * @return Json
     * @author Brown 2024/10/22 下午1:19
     */
    public function UploadOroiginal()
    {
        $upload=new UploadFile();
        $files=\request()->file('file');
        $params=\request()->param();

        //dir中不能包含特殊字符，只允许字母、数字、下划线、中划线
        $dir=$params['dir'];
        $is_create_name=$params['is_create_name'];
        $path_arr=[];
        $coustom_name=$params['coustom_name'];

        $format=new ImageNameFormatter();
        foreach ($files as $key=>$file){
            $savename=$format->getOssOriginPathName($file->getOriginalName(),$is_create_name,$dir,$coustom_name);
            $res=$upload->uploadOss($savename,$file);
            $path_arr[$key]['path_url']=$res['Key'];
            $path_arr[$key]['CRC']=$res['CRC'];
        }
        return json(['code'=>1,'msg'=>'上传成功','data'=>$path_arr]);
    }
}