<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/11
 * Time: 11:46
 */

namespace think;

use think\cache\Driver;
class Cache
{

    //存储缓存实例对象
    public static $instance = [];

    //缓存读取的次数
    public static $readTimes = 0;
    //缓存写入的次数
    public static $writeTimes = 0;


    //当前操作对象
    public static $handler;

    /**
     * 初始化缓存，去调用连接
     * @param array $options
     * @return mixed
     */
    public static function init(array $options = []){

        //当前操作对象不存在时，自动生成一个对象
        if(is_null(self::$handler)){
            if(empty($options) && 'complex'==Config::get('cache.type')){//缓存类型
                $default = Config::get('cache.default');
                $options = Config::get('cache.' . $default['type']) ?: $default;
            }elseif(empty($options)){
                //参数为空，但缓存类型不是complex
                $options = Config::get('cache');
            }
            //以上解决没有带参数的的行为。
            //如果带参数了就不用走上面的路子了
            self::$handler = self::connect($options);//开始实例化缓存了
        }
        return self::$handler;
    }
    /**
     * 切换缓存类型，必须要求为complex，$name即作为标志也作为配置类型的键。（）
     * 配置好才能连接成功驱动。
     * 前换到指定的标志缓存实例下，或者使用当前的实例
     * @param string $name
     * @return mixed
     */
    public static function store($name = ''){
        if(''!== $name && 'complex' === Config::get('cache.type') ){
            //连接驱动去
            return self::connect(Config::get('cache.' . $name),strtolower($name));
        }

        return self::init();
    }

    /**
     * 根据累心实例化缓存对象，又叫做连接缓存驱动
     * @param array $options
     * @param bool|false $name
     * @return mixed
     */
    public static function connect(array $options = [], $name = false){
        $type = !empty($options['type']) ? $options['type'] : 'File';//备胎file类型缓存

        if($name === false){
            //获取缓存标识符
            $name = md5(serialize($options));
        }
        //生成实例化对象的情况
        if(true === $name || !isset(self::$instance[$name])){
            //每个单词的首字符转换为大写
            $class = false === strpos($type,'.') ?
                '\\think\\cache\\driver\\' . ucwords($type) :
                $type;
            //记录缓存初始化信息
            App::$debug && Log::record(' [ CACHE ] INIT ' . $type, 'info');

            if(true === $name){
                //强制实例化缓存小兵
                return new $class($options);
            }

            //实例化首次标志来得
            self::$instance[$name] = new $class($options);
        }
        //返回实例对象用来处理缓存的
        return self::$instance[$name];
    }


    //设置类
    //使用self::init()，是因为静态防止没有生成实例
    /**
     *  写入缓存
     * @param $name
     * @param $value
     * @param null $expire
     * @return mixed
     */
    public static function set($name, $value, $expire = null){

        self::$writeTimes++;//写记录增加一

        return self::init()->set($name, $value, $expire);
    }

    /**
     * 缓存值自增
     * @param $name
     * @param int $step
     * @return mixed
     */
    public static function inc($name, $step = 1){

        self::$writeTimes++;//写记录增1

        return self::init()->inc($name, $step);
    }

    /**
     *缓存值自减
     * @param $name
     * @param int $step
     * @return mixed
     */
    public static function dec($name, $step = 1){
        self::$writeTimes++;

        return self::init()->dec($name, $step);
    }

    /**
     * 删除缓存变量
     * @param $name
     * @return mixed
     */
    public static function rm($name){
        self::$writeTimes++;

        return self::init()->rm($name);
    }

    /**
     * 清空（指定标签）缓存，
     * @param null $tag
     * @return mixed
     */
    public static function clear($tag = null){
        self::$writeTimes++;

        return self::init()->clear($tag);
    }
    /**
     * 缓存标签
     * @param $name
     * @param null $key
     * @param null $overlay
     * @return mixed
     */
    public static function tag($name, $key = null, $overlay = null){

        return self::init()->tag($name, $key, $overlay);
    }


    //即读取也设置
    /**
     * 读取并删除缓存变量
     * @param $name
     * @return mixed
     */
    public static function pull($name){
        self::$readTimes++;
        self::$writeTimes++;

        return self::init()->pull($name);
    }
    /**
     * 获取变量值，若不存在，则缓存下来
     * @param $name
     * @param null $value
     * @param null $expire
     * @return mixed
     */
    public static function remember($name, $value = null, $expire = null){
        self::$readTimes++;

        return self::init()->remember($name, $value, $expire);
    }





    //读取类
    /**
     * 获取缓存变量值
     * @param $name
     * @param bool|false $default
     * @return mixed
     */
    public static function get($name, $default = false){
        self::$readTimes++;

        return self::init()->get($name, $default);
    }

    /**
     * 判断缓存是否存在
     * @param $name
     * @return mixed
     */
    public static function has($name){
        self::$readTimes++;

        return self::init()->has($name);
    }




}
