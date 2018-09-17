<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 17:14
 */

namespace think;
use think\exception\ClassNotFoundException;

class Log
{
    const LOG = 'log';
    const ERROR = 'error';
    const INFO = 'info';
    const NOTICE = 'notice';
    const SQL = 'sql';
    const ALERT = 'alert';
    const DEBUG = 'debug';



    protected static $config = [];
    //暂存的日志信息
    protected static $log = [];
    protected static $type = ['log','info','error','notice','sql','debug','alert'];
    protected static $driver;
    protected static $key;


    /**
     * 初始化操作，没有驱动判别.需要在外部自行is_null判别
     * @param array $config
     */
    public static function init(array $config = []){
        $type = isset($config['type']) ? $config['type'] : 'File';
        $class = false !== strpos($type, '\\') ? $type : '\\think\\log\\driver\\' . ucwords($type);

        self::$config = $config;
        unset($config['type']);
        if(class_exists($class)){
            self::$driver = new $class($config);
        }else{

            throw new ClassNotFoundException('class not exists :' . $class, $class);
        }
        //记录初始化信息
        App::$debug && Log::record('[ LOG ] INIT ','info');

    }

    //设置类
    /**
     * 清空日志信息
     */
    public static function clear(){
        self::$log = [];
    }

    /**
     * 设置授权key
     * @param $key
     */
    public static function key($key){
        self::$key = $key;
    }





    /**
     * 记录到日志数组中
     * @param $msg
     * @param string $type
     */
    public static function record($msg, $type = 'log'){

        self::$log[$type][] = $msg;

        IS_CLI && self::save();

    }

    /**
     * 调用驱动保存日志缓存信息
     * 有授权检查
     * @return bool
     */
    public static function save(){
        //没有需要保存的信息
        if(empty(self::$log)){
            return true;
        }

        is_null(self::$driver) && self::init(Config::get('log'));

        //检查一下写入日志的权限
        if(!self::check(self::$config)){
            return false;
        }

        //是否区分记录类型
        if(empty(self::$config['level'])){

            //获取全部日志
            $log = self::$log;
            //是否包含调试日志
            if(!App::$debug && isset($log['debug'])){
                unset($log['debug']);
            }

        }else{
            $log = [];
            foreach(self::$config['level'] as $level){
                if(isset(self::$log[$level])){
                    $log[$level] = self::$log[$level];//这是该类别的所有的
                }
                //没有这个记录就不用记录
            }

        }

        if($result = self::$driver->save($log)){
            self::$log = [];//置空
        }

        Hook::listen('log_write_done', $log);

        return $result;


    }

    /**
     * 即时写入，没有授权检查
     * 钩子不一样
     * @param $msg
     * @param string $type
     * @param bool|false $force
     * @return bool
     */
    public static function write($msg, $type = 'log', $force = false){
        $log = self::$log;
        // 如果不是强制写入，而且信息类型不在可记录的类别中则直接返回 false 不做记录
        if($force !== false && !empty(self::$config['level']) && !in_array($type, self::$config['level'])){
            return false;
        }

        $log[$type][] = $msg;//包含之前的
        Hook::listen('log_write',$log);

        is_null(self::$driver) && self::init(Config::get('log'));

        //开始写入
        if($result = self::$driver->save($log)){
            self::$log = [];
        }

        return $result;
    }



    //读取类
    /**
     * 读取指定类型的日志
     * 或全部日志
     * @param string $type
     * @return array
     */
    public static function getLog($type = ''){
        return $type ?self::$log[$type] : self::$log;
    }

    /**
     * 检查给的配置日志写入权限
     * @param $config
     * @return bool
     */
    public static function check($config){
        return !self::$key || empty($config['allow_key']) || in_array(self::$key, $config['allow_key']);
    }



    //召唤师
    /**
     * 静态方法调用
     *
     * 实际调用的是record
     * @param $method
     * @param $args
     */
    public static function __callStatic($method, $args){
        //限定方法，限定$args就是信息，方法实际为类型
        if(in_array($method,self::$type)){
            array_push($args,$method);

            call_user_func_array('\\think\\Log::record',$args);

        }
    }




}
