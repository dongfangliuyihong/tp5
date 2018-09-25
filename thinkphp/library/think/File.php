<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/25
 * Time: 9:50
 */

namespace think;

use SplFileObject;
class File  extends SplFileObject
{
    //完整的文件名
    protected $filename;

    //保存上传的文件信息
    protected $info;

    protected $rule = 'date';//文件名生成规则
    //最终的上传文件名，有上面作用的可能
    protected $saveName;
    protected $isTest;
    //验证规则
    protected $validate = [];
    //错误信息
    private $error = '';
    //文件的hash信息
    protected $hash =[];

    //          [file2] => Array
    //                      (
    //                          [name] => MyFile.jpg
    //                          [type] => image/jpeg
    //                          [tmp_name] => /tmp/php/php6hst32
    //                          [error] => UPLOAD_ERR_OK
    //                          [size] => 98174
    //                         )
    //      new File($file['tmp_name']

    /**
     * 构造方法，构造php标准文件对象
     * @param $filename
     * @param string $mode
     */
    public function __constrcut($filename, $mode = 'r'){
        parent::__construct($filename,$mode);
        //完整的文件名,方法获取来的
        $this->filename = $this->getRealPath() ?: $this->getPathname();
    }
















    //设置类
    /**
     * 设置上传信息
     * @param $info
     * @return $this
     */
    public function setUploadInfo($info){
        $this->info = $info;
        return $this;
    }

    /**
     * 设置上传后的文件名
     * @param $saveName
     * @return $this
     */
    public function setSaveName($saveName){
        $this->saveName = $saveName;
        return $this;
    }

    /**
     * 总控完成上传，包含校验，上传，反馈
     * @param $path
     * @param bool|true $savename
     * @param bool|true $repacle
     * @return bool|File
     */
    public function move($path, $savename = true,$repacle = true){

        //移动步骤
        //1：检查上传是否出错
        //2：检查合法性,是否是通过 HTTP POST 上传的
        //3：验证上传
        //4:检查保存目录
        //5：移动文件（移动前检查文件是否已经存在）
        //6:返回移动成功的文件对象
        if(!empty($this->info['error'])){
            $this->error($this->info['error']);//获取错误代码信息
            return false;
        }
        if(!$this->isvalid()){
            $this->error = 'upload illegal files';
            return false;
        }

        if(!$this->check()){
            return false;
        }
        //要保存的位置
        $path = rtrim($path, DS) . DS;
        $savename = $this->buildSaveName($savename);
        $filename = $path . $savename;

        if(false === $this->checkPath(dirname($filename))){
            return false;
        }

        if(!$repacle && is_file($filename)){
            $this->error = ['has the same filename: {:$filename}', ['filename' => $filename]];
            return false;
        }

        if($this->isTest){
            rename($this->filename, $filename);
        }elseif(!move_uploaded_file($this->filename, $filename)){//将临时文件移动到确认的地方
            $this->error = 'upload write error';
            return false;
        }
        //到这其实上传成功了
        $file = new self($filename);
        $file->setSaveName($savename)->setUploadInfo($this->info);

        return $file;
    }

    /***************************************************************************/
    /**
     * 设置文件的规则，支持日期，hash算法和闭包，方法
     * @param $rule
     * @return $this
     */
    public function rule($rule){
        $this->rule = $rule;
        return $this;
    }

    /**
     * 设置文件校验规则，可在check中传入动态规则
     * @param array $rule
     * @return $this
     */
    public function validate(array $rule =[]){
        $this->validate = $rule;
        return $this;
    }




    //获取类
    /**
     * 获取上传后的文件名
     * @return mixed
     */
    public function getSaveName(){
        return $this->saveName;
    }

    /*********************************************************************************************************/

    /**
     * 检查保存路径，是否可写
     * @param $path
     * @return bool
     */
    public function checkPath($path){
        if(is_dir($path) || mkdir($path, 0755, true)){
            return true;
        }
        $this->error = ['directory {:path} creation failed', ['path' => $path]];
        return false;
    }
    /**
     * 获取需要设置的文件名
     * @param $savename
     * @return mixed|string
     */
    public function buildSaveName($savename){
        if($savename === true){
            //自动生成文件名
            if($this->rule instanceof \Closure){
                $savename = call_user_func_array($this->rule, [$this]);
            }else{
                switch($this->rule){
                    case 'data':
                        $savename = date('Ymd') . DS . md5(microtime(true));
                        break;
                    default:
                        ////返回已注册的哈希算法列表. 包含了受支持的哈希算法名称。sha1等
                        if(in_array($this->rule, hash_algos())){
                            $hash = $this->hash($this->rule);//使用这种加密算法，返回散列值
                            $savename = substr($hash, 0, 2) . DS . substr($hash, 2);
                        }elseif(is_callable($this->rule)){
                            $savename = call_user_func($this->rule);
                        }else{
                            //和date方式一样
                            $savename = date('Ymd') . DS . md5(microtime(true));
                        }
                }
            }

        }elseif(''=== $savename || false === $savename){
            $savename = $this->getInfo('name');//使用上传信息的名称
        }
        //加后缀
        if(!strpos($savename, '.')){
            $savename .= '.' . pathinfo($this->getInfo('name'), PATHINFO_EXTENSION);//读取后缀名
        }

        return $savename;
    }

    /**
     * 获取错误代码信息
     * 对应$_FILE的错误代码
     * @param $errorNo
     * @return $this
     */
    public function error($errorNo){
        switch($errorNo){
            case 1:
            case 2:
                $this->error = 'upload File size exceeds the maximum value';
                break;
            case 3:
                $this->error = 'only the portion of file is uploaded';
                break;
            case 4:
                $this->error = 'no file to upload';
                break;
            case 6:
                $this->error = 'upload temp dir not found';
                break;
            case 7:
                $this->error = 'file write error';
                break;
            default:
                $this->error = 'unknown upload error';
        }
        return $this;
    }

    /**
     * 获取错误信息的多语言表达
     * @return mixed|null|string
     */
    public function getError(){
        if(is_array($this->error)){
            list($msg, $vars) = $this->error;
        }else{
            $msg = $this->error;
            $vars = [];
        }
        return Lang::has($msg) ? Lang::get($msg, $vars) : $msg;
    }

    /**
     *一个文件对应一个对象，
     * 一个算法对应一个内容的散列值
     * @param string $type
     * @return mixed
     */
    public function hash($type = 'sha1'){
        if(!isset($this->hash[$type])){
            $this->hash[$type] = hash_file($type, $this->filename);
        }
        return $this->hash[$type];
    }

    /**
     * 检测文件是否合法
     * @return bool
     */
    public function isvalid(){
        //指定的文件是否是通过 HTTP POST 上传的
        return $this->isTest() ? is_file($this->filename) : is_uploaded_file($this->filename);
    }


    /**
     * 检查文件是否符合规则
     * @param array $rule
     * @return bool
     */
    public function check($rule = []){
        $rule = $rule ?: $this->validate;//否则使用默认的验证规则
        //检查步骤
        // 1：检查文件大小
        //2：检查文件mime类型
        //3:检查文件后缀
        //4：检查图像文件

        if(isset($rule['size']) && !$this->checkSize($rule['size'])){
            //规则中若没有，则不用检查
            $this->error = 'filesize not match';
            return false;
        }
        if(isset($rule['type']) && !$this->checkMime($rule['type'])){
           $this->error = 'mimetype to upload is not allowed';
            return false;
        }
        if(isset($rule['ext']) && !$this->checkExt($rule['ext'])){
            $this->error = 'extensions to upload is not allowed';
            return false;
        }
        if(!$this->checkImg()){
            $this->error = 'illegal image files';
            return false;
        }
        return true;
    }

    /**
     * 检查图像类型和后缀
     * 这里用的全称
     * @return bool
     */
    public function checkImg(){
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        //false表示不符合
        return !in_array($extension,['gif','jpg','jpeg','bmp','png','swf']) || in_array($this->getImgType($this->filename), [1,2,3,4,5,13]);
    }

    /**
     * 判断图像类型
     * @param $image
     * @return bool|int
     */
    public function getImgType($image){
        if(function_exists('exif_imagetype')){
            return exif_imagetype($image);
        }
        try{
            $info = getimagesize($image);//获取图像信息索引 2 给出的是图像的类型
            return $info ? $info[2] : false;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 检查文件后缀，用的上传信息来验证
     * @param $ext
     * @return bool
     */
    public function checkExt($ext){
        if(is_string($ext)){
            $ext = explode(',', $ext);
        }
        //用上传信息来检查是否符合后缀规则。
        $extension = strtolower(pathinfo($this->info['name'], PATHINFO_EXTENSION));
        return in_array($extension, $ext);
    }

    /**
     * 检查文件的mime类型是否符合规则
     * @param $mime
     * @return bool
     */
    public function checkMime($mime){
        //要求使用数组
        $mime = is_string($mime) ? explode(',',$mime) : $mime;//设置的
        return in_array(strtolower($this->getMime()), $mime);
    }

    /**
     * 获取文件mime类型信息
     * @return mixed
     */
    public function getMime(){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);// 创建一个 fileinfo 资源,返回 mime 类型。
        return finfo_file($finfo,$this->filename);////返回一个文件的信息,接受一个finfo_open() 函数所返回的 fileinfo 资源。
    }

    /**
     * 检查文件大小比对。传入的的是最大设置值
     * @param $size
     * @return bool
     */
    public function checkSize($size){
        return $this->getSize() <= $size;
    }



    /**
     * 获取上传信息，可指定名称，否则返回全部
     * @param string $name
     * @return mixed
     */
    public function getInfo($name = ''){
        //单独数组空字符串键名会有notice提示，isset会返回false
        return isset($this->info[$name]) ? $this->info[$name] : $this->info;
    }

    /**
     * 判断是否是测试模式
     * @param bool|false $test
     * @return $this
     */
    public function isTest($test = false){
        $this->isTest = $test;
        return $this;
    }

    /**
     * $this->sha1();获取文件的加密hash
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args){
        return $this->hash($method);
    }


}
