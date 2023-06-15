<?php
namespace Machine\Common;

class Message {

    public static $SUCCESS = [ 'code'=>"200",'msg'=>"操作成功" ];
    public static $FAIL = [ 'code'=>"404",'msg'=>"操作失败" ];
    public static $ERROR_LENGTH_FORMAT = ['code'=>"400",'msg'=>"参数有误，请使用符合要求的长度与格式"];
}
