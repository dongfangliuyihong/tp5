<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/13
 * Time: 15:07
 */

namespace think;


class Hook
{
    private static $tags = [];

    //Hook::add('app_init',function(){
    //    echo 'Hello,world!';
    //});
    //return [
    //          'app_init'=> [
    //                        'app\\index\\behavior\\CheckAuth',
    //                        '_overlay'=>true
    //                        ],
    //           'app_end'=> [
    //                        'app\\admin\\behavior\\CronRun'
    //                        ]
    //          ]


    //设置类
    /**
     * 注册到$tags数组中，$behavior支持多种类型，闭包，对象，数组（支持批量注册，单个）
     * @param $tag
     * @param $behavior
     * @param bool|false $first
     */
    public static function add($tag, $behavior, $first = false){

        isset(self::$tags[$tag]) || self::$tags[$tag] = [];

        //判断行为的类型进行添加.只能是数组或者字符串或者对象

        if(is_array($behavior) && !is_callable($behavior)){
            ////此处array($object,$funcname)的用法,这种适用静态方法和普通方法
            //静态方法还可以$object::$funcname调用
            //比如批量的，是数组但不能被调用的！！！！记录在这里.
            if(!array_key_exists('_overlay',$behavior) || !$behavior['_overlay']){
                unset($behavior['_overlay']);
                self::$tags  = array_merge(self::$tags[$tag], $behavior);
            }else{
                //存在覆盖标志且要求覆盖
                unset($behavior['_overlay']);
                self::$tags[$tag] = $behavior;
            }

        }elseif($first){
            //如果不是数组的。
            //是数组但能调用的
            array_unshift(self::$tags[$tag], $behavior);
        }else{
            self::$tags[$tag][] = $behavior;
        }


    }


    /**
     * 批量导入，$tags为标签和行为组成的数组
     * @param array $tags
     * @param bool|true $recursive
     */
    public static function import(array $tags, $recursive = true){

        if($recursive){
            foreach($tags as $tag => $behavior){
                self::add($tag, $behavior);
            }

        }else{
            self::$tags = $tags + self::$tags;

        }
    }













    //获取类

    /**
     * 获取指定标签行为，没有返回空数组
     * 不指定则返回全部的
     * @param string $tag
     * @return array
     */
    public static function get($tag = ''){

        if(empty($tag)){
            return self::$tags;
        }

        return array_key_exists($tag, self::$tags) ? self::$tags[$tag] : [];
    }

    /**
     * 监听标签，触发行为
     * @param $tag
     * @param null $param
     * @param null $extra
     * @param bool|false $once
     * @return array|mixed
     */
    public static function listen($tag, &$param = null, $extra =null, $once = false){

        $result = [];

        foreach(static::get($tag) as $key => $name){
            $result[$key] = self::exec($name, $tag, $param, $extra);

            //返回false直接终止，要求只有一个有效值时终止
            if(false === $result[$key] || (!is_null($result[$key]) && $once)){

                break;
            }
        }

        return $once ? end($result) : $result;
    }

    /**
     * 刽子手
     * 用来去执行的。可以不用注册行为直接调用行为。也可以传入对象和方法
     * @param $class
     * @param string $tag
     * @param null $param
     * @param null $extra
     * @return mixed
     */
    public static function exec($class, $tag = '', &$param = null, $extra = null){

        //应用开启调试则记录行为
        App::$debug && Debug::remark('behavior_start', 'time');

        //有可能只是标签，有可能是方法（直接传过来的）
        $method = Loader::parseName($tag, 1, false);//首字母是否大写

        if($class instanceof \Closure){
            $result = call_user_func_array($class, [& $param, $extra]);
            $class = 'Closure';
        }elseif(is_array($class)){
            //数组形式会被拆分执行
            list($class, $method) = $class;

            $result = (new $class)->$method($param,$extra);
            $class = $class . '->' . $method;
        }elseif(is_object($class)){

            $result = $class->$method($param,$extra);
            $class = get_class($class);
        }elseif(strpos($class,'::')){
            $result = call_user_func_array($class, [& $param, $extra]);
            //并没有类的重定义
        }else{
            $obj = new $class();
            $method = ($tag && is_callable($obj,$method)) ? $method : 'run';
            $result = $obj->$method($param, $extra);
            //并没有类的重定义
        }

        if(App::$debug){
            Debug::remark('behavior_end','time');
            Log::record(' [ BEHAVIOR ] Run ' . $class . '@' . $tag . 'RunTime:' . Debug::getRangTime('behavior_start', 'behavior_end') . 's ]','info');
        }
        return $result;


    }




















}
