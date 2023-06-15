<?php
namespace Machine\Common;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class Helper {


    public $titleWX = null;

    public $bodyWX = null;

    public $imgWX = null;

    public $image_url = '';

    /**
     * 
     * Combination data insert and update data
     * @param $param
     * @param $values
     * @param int $type =0 is new data insert ,!=0  is old data update
     * @param $user
     * @return mixed
     * @internal param int $time
     * @internal param $request
     * @internal param $value
     * @internal param $array
     * @internal param $data
     */
    public function composeData($param,$values, $type=0,$user)
    {
        $data = array();

        // TODO: Implement composeData() method.

        $params = explode(',',$param);
        foreach($params as $item=>$value){
            //if is all,then equal
            if($value == '*'){
                $data = $params;
            }else{
                if(array_key_exists($value,$values)){
                    $v = htmlspecialchars($values[$value]);
                    $data[$value] = $v;
                }
            }

        }
        //=0 is new data insert ,!=0  is old data update
        if($type == 0){
            $data['user_id'] = $user['id'];
            $data['create_date'] = Carbon::createFromDate()->toDateString();
            $data['create_times'] = Carbon::createFromTime()->toTimeString();
            $data['create_int'] = time();
        }


        return $data;
    }

    /**
     * Check Repet Data
     * @param $table
     * @param $where
     * @param $con
     * @return mixed
     * @internal param $connect
     */
    public function checkRepeat($table, $where, $con=null)
    {

        // TODO: Implement checkRepeat() method.
        if(empty($con)){
            $result = DB::table($table)->where($where)->first();
        }else{
            $result = DB::connection($con)->table($table)->where($where)->first();
        }

        return $result;
    }

    /**
     * Get a 16 string 
     *  @param int $length
     */
    public function getRand16String($length=16){
        if ($length > 60){
            return 'The Length is Fail, Do not greater than 60';
        }
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	    $string = '';
        for ($i=1;$i<=$length;$i++){	
            $rand=rand(0,61);
            $string.= substr($charset,$rand,1);
        }

        return $string;

    }

    /**
     * just a format to other format
     * @param object $obj
     */
    public function getObjToJsonToArray($obj){
        $json = json_encode($obj);

        $array = json_decode($json,true);

       return $array;
    }

    /**
     * $changeStrArray = array([{'changeStr': "source str"}],[{'changeToStr',"target Str"}])
     * $changeFromPreg = array([{'changePreg': "php preg"}],[{'changeToStr': "target Str"}])
     * @param string $contentResponse
     * @param string $sUrlKey
     * @param array $changeStrArray
     * @param array $changeFromPreg
     * 
     */
    public function getWX($contentResponse,$sUrlKey,$changeStrArray=array(),$changeFromPreg=array()){

        $sPatTitle = '/<meta property=\"og:title\" content=\"(.*?)" \/>/';
        $sPatBody = '/id=\"js_content\" style=\"visibility: hidden;\">(.*?)<\/div>/is';
        $sPaImg = '/<img(.*?)\/>/is';
        $sPaImgSrc = '/data-src=\"(.*?)\"/';
        $arrSrcList = array();

        //all flow get image
        preg_match($sPatTitle,$contentResponse,$arrMatchesTitle);
        preg_match($sPatBody,$contentResponse,$arrMatchesBody);
        // dump($arrMatchesBody);

        preg_match_all($sPaImg,$arrMatchesBody[1],$arrMatchesImg);

        $sDir = "uploads/down/".date("Y")."/".date("m")."/".date("d")."/".$sUrlKey."/";

        $iStatus = $this->createDIR($sDir);
        
        Storage::disk('oss'); 

        //get all the image src
        foreach ($arrMatchesImg[1] as $i => $sValue){
            preg_match($sPaImgSrc,$sValue,$arrMatchesImgSrc);

            // $filename = $this->file_exists_S3($arrMatchesImgSrc[1],$sDir);
            //上传到oss
            $filename = $this->file_upload_oss($arrMatchesImgSrc[1],$sDir);
      
            if (!empty($filename)){
                $arrSrcList[$i][0] = $arrMatchesImgSrc[1];
                $arrSrcList[$i][1] = $filename;
                //change body html image to new filename
            };

        }

        $sBody = $arrMatchesBody[1];


        
        foreach ($arrSrcList as $i => $sValue){
            $sBody = str_replace('data-src="'.$sValue[0],'src="'.$this->image_url.$sValue[1],$sBody);

            foreach ($changeStrArray as $changeValue){
                $sBody = str_replace($changeValue['changeStr'],$changeValue['changeToStr'],$sBody);
            }
            foreach ($changeFromPreg as $changePregValue){
                $sBody = preg_replace($changePregValue['changePreg'],$changeValue['changeToStr'],$sBody);
            }

        }

        $this->titleWX = $arrMatchesTitle[1];
        $this->bodyWX = $sBody;
        $this->imgWX = $this->image_url .$arrSrcList[0][1];
    }

    public function createDIR($path){
        //if path exists and echo text, else create path
        if (is_dir($path)){
//            echo "对不起！目录 " . $path . " 已经存在！";
        }else{
            //第三个参数是“true”表示能创建多级目录，iconv防止中文目录乱码
            $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true);
            if ($res){
                return true;
            }else{
                return false;
            }
        }
    }

    public function file_upload_oss($url,$path){

        $state = @file_get_contents($url,0,null,0,1);// get the image content

        if($state){

            $rand = rand(100,200);

            $filename = $path.date("dMYHis").$rand.'.jpg';// build file name 

            ob_start();// open 

            readfile($url);// output image file 

            $img = ob_get_contents();// get the browser the output

            ob_end_clean();// clean and close 

            $size = strlen($img);// get image size 
 
            $fp2 = @fopen($filename, "a");

            fwrite($fp2, $img);// write a image to the file and rename 

            fclose($fp2);

            // $OssState = Storage::putFile($path, $fp2); // upload file from local path
            $OssState = Storage::put($filename, $img);

            // print_r($OssState);
            if ($OssState){
                Log::info("Success:" . $filename);
            }
           
            return $filename;

        } else{

            return 0;

        }

       
    }

    /**
     * check the string format
     * @param mixed $str
     * @param string $type
     * @param int $length
     */
    public function checkParamFormat($str,$type,$length=20){
        switch($type){
            case 'int':
                $string = is_numeric($str) ? $str : 'null'; //check is_numeric
                $string = strlen($string) < $length ? $string : 'null'; //check length
                break;
            case 'string':
                $string = is_string($str) ? $str : 'null';
                $string = strlen($string) < $length ? $string : 'null';
                break;
            default:
                $string = 'null';
                break;
        }
        return $string;
    }

}

