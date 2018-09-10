<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 14:46
 */

namespace think;


class Config
{
    //配置数据
    private static $config = [];
    //配置数据的作用域，第一级的键
    private static $range = '_sys_';


    //设置类
    /**
     * 设置配置参数的作用域，键名
     * @param $range
     */
    public static function range($range){
        self::$range = $range;
        if(!isset(self::$range[$range])) self::$config[$range] = [];
    }


    /**
     * 重置作用域下的全部配置
     * @param string $range
     */
    public static function reset($range = ''){
        $range = $range ? :self::$range;

        if(true === $range){
            //全部重置
            self::$config = [];
        }else{
            //重置单个的
            self::$config[$range] = [];
        }
    }

    /**
     * 设置配置，支持数组和字符串的形式
     * @param $name
     * @param null $value
     * @param string $range
     * @return array|null
     */
    public static function set($name, $value = null, $range = ''){
        $range = $range ?: self::$range;

        if(!isset(self::$config[$range])) self::$config[$range] = [];

        if(is_string($name)){
            if(!strpos($name,'.')){
                self::$config[$range][strtolower($name)] = $value;
            }else{
                //二维数组
                $name = explode('.',$name,2);
                self::$config[$range][strtolower($name[0])][strtolower($name[1])] = $value;
            }

            return $value;
        }

        if(is_array($name)){
            if(!empty($value)){
                self::$config[$range][$value] = isset(self::$config[$range][$value]) ?
                    array_merge(self::$config[$range][$value],$name) : array_change_key_case($name);

                return self::$config[$range][$value];
            }

            //如果为空
            return  self::$config[$range] = array_merge(self::$config[$range], array_change_key_case($name));

        }

        return self::$config[$range];
    }
    /**
     *读取文件中的配置，通过设置到配置数组，返回该值
     * @param $file
     * @param string $name
     * @param string $range
     * @return array|null
     */

    public static function load($file, $name = '', $range = ''){
        $range = $range ?: self::$range;

        //作用域
        if(!isset(self::$config[$range])) self::$config[$range] = [];

        //解析加载文件
        if(is_file($file)){
            //作为键名的
            $name = strtolower($name);
            //文件类型
            $type = pathinfo($file,PATHINFO_EXTENSION);

            if('php' == $type){
                //引入数组之后，设置为name的值，返回设置的值
                return self::set(include $file, $name, $range);
            }
            if('yaml' == $type && function_exists('yaml_parse_file')){
                return self::set(yaml_parse_file($file), $name. $range);
            }
            //其他类型的需要引入其他类来解析
            return self::parse($file, $type, $name, $range);


        }

        return self::$config[$range];
    }


    /**
     * 解析配置文件或内容，和load原理一样，只是解析方法不同
     * 注意也可以解析内容
     * @param $config
     * @param string $type
     * @param string $name
     * @param string $range
     * @return array|null
     */
    public static function parse($config, $type = '', $name = '', $range = ''){
        $range = $range ?: self::$range;

        if(empty($type)) $type = pathinfo($config,PATHINFO_EXTENSION);

        $class = false !== strpos($config,'\\') ?
            $type :
            '\\think\\config\\driver\\' . ucfirst($type);
        return self::set((new $class())->parse($config), $name, $range);
    }








    //获取类

    /**
     * 获取配置参数，二维数组，支持文件名+参数名的方式
     * @param null $name
     * @param string $range
     * @return null
     */
    public static function get($name = null, $range = ''){
        $range = $range ?: self::$range;

        if(empty($name) && isset(self::$config[$range])){
            //获取指定作用域下的全部配置参数
            return self::$config[$range];
        }
        //非二维数组
        if(!strpos($name,'.')){
            $name = strtolower($name);
            return isset(self::$config[$range][$name]) ? self::$config[$range][$name] :null;
        }

        //支持二维数组的设置和获取
        $name = explode('.',$name,2);
        $name[0] = strtolower($name[0]);

        //默认首个字段为文件名
        if(!isset(self::$config[$range][$name[0]])){
            //尝试获取一下
            $module = Request::instance()->module();
            $file = CONF_PATH . ($module ? $module . DS : '') . 'extra' . DS . $name[0] . CONF_EXT;
            is_file($file) && self::load($file,$name[0]);//这个地方应该加上$range;
        }

        //获取
        return isset(self::$config[$range][$name[0]][$name[1]]) ? self::$config[$range][$name[0]][$name[1]] : null;


    }

    //归为获取类的原因是，先获取，然后使用isset函数
    /**
     * 检查配置是否存在
     * @param $name
     * @param string $range
     * @return bool
     */
    public static function has($name, $range = ''){
        $range = $range ?: self::$range;

        if(!strpos($name, '.')){
            return isset(self::$config[$range][$name]);
        }

        //二维数组设置和获取
        $name = explode('.',$name,2);
        return isset(self::$config[$range][strtolower($name[0])][$name[1]]);//感觉这里应该再加一个转成小写的

    }






}
