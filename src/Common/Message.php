<?php
namespace Machine\Common;

class Message {

    public static $SUCCESS = [ 'code'=>"200",'msg'=>"操作成功",'msgE'=>"Success Request" ];
    public static $FAIL = [ 'code'=>"404",'msg'=>"操作失败",'msgE'=>"Fail Request" ];
    public static $ERROR_LENGTH_FORMAT = ['code'=>"400",'msg'=>"参数有误，请使用符合要求的长度与格式",'msgE'=>'The Param is error,Please use correct value.'];
    public static $ERROR_SOFT_DELETE = ['code'=>"400",'msg'=>"请恢复软删除后，再强制删除",'msgE'=>'Please restore soft delete'];


    public static function INFO_AND_TIME($param,$result=null){
        $response['info'] = $param;
        $response['time'] = time();
        $response['result'] = $result;
        return $response;
    }

}
