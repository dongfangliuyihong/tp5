<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/11
 * Time: 18:30
 */

namespace think\cache\driver;

use think\cache\Driver;

/**
 * 文件类型的缓存
 * Class File
 * @package think\cache\driver
 */
class File extends Driver
{
    //默认的实例化缓存参数
    protected  $options = [
        'expire'        => 0,
        'cache_subdir'  => true,
        'prefix'        =>'',
        'path'          => CACHE_PATH,
        'data_compress' => false,
    ];

    protected $expire;

    /**
     * 构造方法，完成参数的初始化以及路径的检查
     * @param array $options
     */
    public function __construct($options = []){

        if(!empty($options)){
            $this->options = array_merge($this->options,$options);
        }

        if(substr($this->options['path'],-1) != DS){
            $this->options['path'] .= DS;
        }

        $this->init();
    }

    /**
     * 缓存小兵的初始化工作
     * 完成缓存路径的检查
     *
     * 不明白这个地方为什么要返回false
     * @return bool
     */
    private function init(){
        //创建项目缓存目录
        if(!is_dir($this->options['path'])){
            if(mkdir($this->options['path'], 0755, true)){
                return true;
            }
        }
        return false;
    }






}
