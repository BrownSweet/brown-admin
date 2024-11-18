<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-10-22 13:59
 */

namespace app\controller\api;

use app\controller\BaseConfig;
use app\controller\core\ImageNameFormatter;
use app\controller\core\ImageProcessing;
use app\controller\core\MyRedis;
use app\controller\UploadFile;
use think\response\Json;

class Image
{

    /**
     * 源文件转图片 默认转成png格式，并保存到cos中
     * @return Json
     * @author Brown 2024/10/22 下午1:19
     */
    public function OriginalToImage()
    {
        $path_arr=\request()->param('path_arr');
        $path_arr=[
            "original/程序员节A/程序员节A_1.ai",
            "original/程序员节A/程序员节A_2.ai",
            "original/程序员节A/程序员节A_3.ai",
            "original/程序员节A/程序员节A_4.ai",
            "original/程序员节A/程序员节A_5.ai",
            "original/程序员节A/程序员节A_6.ai",
            "original/程序员节A/程序员节A_7.ai",
            "original/程序员节A/程序员节A_8.ai",
            "original/程序员节A/程序员节A_9.ai",
            "original/程序员节A/程序员节A_10.ai",
            "original/程序员节A/程序员节A_11.ai",
            "original/程序员节A/程序员节A_12.ai",
        ];
        $time=date('YmdHis');
        $upload=new UploadFile();
        $processing=new ImageProcessing();
        $format=new ImageNameFormatter();
        $save_path_arr=[];
        $thumbnail_path_arr=[];
        foreach ($path_arr as $path){

            // 将"original/程序员节A/12.ai"  转成  "original/程序员节A/12.png"
            $save_path=$format->getOssImagePathName($path);
//            print_r($save_path);


            $local_path=$format->getLocalPathName($save_path);


            $internet_path=$format->getInternalName($path);

            //直接从oss中把.ai文件转成.png文件，并保存到本地
            $local_path=$processing->AiToPng($internet_path,$local_path,BaseConfig::BACKGROUND_COLOR_TRANSPARENT);

            $local_thumbnail_path=$format->getLocalPathName($local_path);

            $local_thumbnail_path=$processing->ResizeImage($local_path,$local_thumbnail_path,300);


            $thumbnail_path=$format->getOssThumbPathName($save_path);
            $res=$upload->uploadLocalOss($thumbnail_path,$local_thumbnail_path);
            if (isset($res['Key'])&&$res['Key']){
                array_push($thumbnail_path_arr,$res['Key']);
                unlink($local_thumbnail_path);
            }
            //上传到oss中  original-to-image/original/程序员节A/12.png
            $res=$upload->uploadLocalOss($save_path,$local_path);
            if (isset($res['Key'])&&$res['Key']){

                //original-to-image/original/程序员节A/12.png 保存到数组中
                array_push($save_path_arr,$res['Key']);
                //删除本地临时文件
                unlink($local_path);
            }
        }
        return json(['code'=>1,'msg'=>'上传成功','data'=>['oss_external_url'=>BaseConfig::OSS_EXTERNAL_URL,'path_arr'=>$save_path_arr,'thumbnail_arr'=>$thumbnail_path_arr]]);
    }


    public function CreateBackgroundImage()
    {
        $params=\request()->param();

        $unit=$params['unit'];
        $width=$params['width'];
        $height=$params['height'];
        $dpi=$params['dpi'];
        $color=$params['color'];

        $keywords='background';

        $format=new ImageNameFormatter();


        // 默认将宽度和高度转换为像素
        $processing=new ImageProcessing();
        [$new_width,$new_height]=$processing->UnitToPixel($unit,$width,$height,$dpi);
        $background_path=$format->getOssOriginPathName($color.'-background.png',0,$keywords);
        $background_path=$format->getOssImageResizePathName($background_path,$new_width,$new_height,$dpi);


        $background_thumbnail_path=$format->getOssThumbPathName($background_path);


        $background_local_path=$format->getLocalPathName($background_path);

        $background_local_path=$processing->MakeBackground($background_local_path,$new_width,$new_height,$dpi,$dpi,$color);

        $background_thumbnail_local_path=$format->getLocalPathName($background_thumbnail_path);
        $background_thumbnail_local_path=$processing->ResizeImage($background_local_path,$background_thumbnail_local_path,700);

        $upload=new UploadFile();
        $t_res=$upload->uploadLocalOss($background_thumbnail_path,$background_thumbnail_local_path);


        $res=$upload->uploadLocalOss($background_path,$background_local_path);
        unlink($background_local_path);
        unlink($background_thumbnail_local_path);
        return json(['code'=>1,'msg'=>'上传成功','data'=>['oss_external_url'=>BaseConfig::OSS_EXTERNAL_URL,'background_path'=>$res['Key']??'','thumbnail_path'=>$t_res['Key']??'']]);
    }

    public function ResizeImage()
    {
        $params=\request()->param();
        $path=$params['path'];
        $format=new ImageNameFormatter();
        $processing=new ImageProcessing();
        $upload=new UploadFile();
        $path_arr=[];
        foreach ($path as $item){
            $width=$item['width'];
            $unit=$item['unit'];
            $dpi=$item['dpi'];
            $width=$format->convertToPixels($width,$dpi,$unit);

            $save_path=$format->getOssImageResizePathName($item['url'],$width,0,$dpi);
            $local_path=$format->getLocalPathName($save_path);
            $local_path=$processing->ResizeImage($format->getInternalName($item['url']),$local_path,$width,90);
            $res=$upload->uploadLocalOss($save_path,$local_path);
            if (isset($res['Key'])&&$res['Key']){
                array_push($path_arr,$res['Key']);
                unlink($local_path);
            }
        }
        return json(['code'=>1,'msg'=>'上传成功','data'=>['oss_external_url'=>BaseConfig::OSS_EXTERNAL_URL,'path_arr'=>$path_arr]]);
    }
    public function CopyImage()
    {
        $arr=[
            'background_path'=>'original/background/transparent-background_width=7086*height=14173_dpi=150.png',
            'path_arr'=>[
                '田雨客户排版1'=>[
                [
                    'path'=>'original/程序员节A/程序员节A_1.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>4
                ],
                [
                    'path'=>'original/程序员节A/程序员节A_2.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>4
                ],
                [
                    'path'=>'original/程序员节A/程序员节A_3.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>4
                ],
                [
                    'path'=>'original/程序员节A/程序员节A_4.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>4
                ],[
                    'path'=>'original/程序员节A/程序员节A_5.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>21
                ],
                [
                    'path'=>'original/程序员节A/程序员节A_6.png',
                    'width'=>0.4,
                    'unit'=>'m',
                    'dpi'=>150,
                    'num'=>21
                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_7.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_8.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_9.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_10.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_11.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_12.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
                ],
                '田雨客户排版2'=>[
                    [
                        'path'=>'original/程序员节A/程序员节A_1.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>4
                    ],
                    [
                        'path'=>'original/程序员节A/程序员节A_2.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>4
                    ],
                    [
                        'path'=>'original/程序员节A/程序员节A_3.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>4
                    ],
                    [
                        'path'=>'original/程序员节A/程序员节A_4.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>4
                    ],[
                        'path'=>'original/程序员节A/程序员节A_5.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>21
                    ],
                    [
                        'path'=>'original/程序员节A/程序员节A_6.png',
                        'width'=>0.4,
                        'unit'=>'m',
                        'dpi'=>150,
                        'num'=>21
                    ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_7.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_8.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_9.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_10.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_11.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
//                [
//                    'path'=>'original/程序员节A/程序员节A_12.png',
//                    'width'=>0.4,
//                    'unit'=>'m',
//                    'dpi'=>150,
//                    'num'=>21
//                ],
                ]
            ]
        ];
        $file=new UploadFile();

        $processing=new ImageProcessing();
        $processing_arr=[];
        $format=new ImageNameFormatter();
        foreach ($arr['path_arr'] as $key=>$items){
            foreach ($items as $item){
                $width_pixel=$format->convertToPixels($item['width'],$item['dpi'],$item['unit']);

                $name=$format->getOssImageResizePathName($item['path'],$width_pixel,0,$item['dpi']);

                $res=$file->SelectFile($name);
                if (!$res){
                    $local_path=$processing->ResizeImage($format->getInternalName($item['path']),$format->getLocalPathName($name),$width_pixel,90);
                    $res=$file->uploadLocalOss($name,$local_path);
                    if (isset($res['Key'])&&$res['Key']){
                        $name=$res['Key'];
                    }
                }

                $processing_arr[$key][]=[
                    'path'=>$format->getInternalName($name),
                    'num'=>$item['num']
                ];

            }
        }


        $processing_arr=$this->expandArray($processing_arr);
        print_r($processing_arr);
        die;
        $background_path=$format->getInternalName($arr['background_path']);


//        print_r(json_encode(['processing_arr'=>$processing_arr,'background_path'=>$background_path,'name'=>$arr['name']]));
        $res=MyRedis::getInstance()->sendMessage('copy_image_to_background',json_encode(['processing_arr'=>$processing_arr,'background_path'=>$background_path]));

        if ($res){
            return json(['code'=>1,'msg'=>'上传成功','data'=>'']);
        }


//        $processing->CopyToBackground1($processing_arr,$format->convertToPixels(0.001,150,'mm'),$background_path,$format->getOutputPathName($arr['name'],$arr['name']));

    }

    function expandArray($inputArray) {
        $result = [];
        foreach ($inputArray as $key => $items) {
            foreach ($items as $item) {
                for ($i = 0; $i < $item['num']; $i++) {
                    $result[$key][] = $item['path'];
                }
            }
        }
        return $result;
    }
}