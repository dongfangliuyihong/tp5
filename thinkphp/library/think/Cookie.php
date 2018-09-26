<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/26
 * Time: 13:12
 */

namespace think;


class Cookie
{
    protected static $config = [
        'prefix'        =>  '',
        'expire'        =>  0,
        'path'          =>  '/',
        'domain'        =>  '',
        'secure'        =>  false,
        'httponly'      => false,
        'setcookie'     => true,
    ];

    //是否初始化
    protected static $init;

    /**
     * 初始化操作
     * @param array $config
     */
    public static function init(array $config = []){
        if(empty($config)){
            $config = Config::get('cookie');
        }

        self::$config = array_merge(self::$config, $config);

        if(!empty(self::$config['httponly'])){
            ini_set('session.cookie_httponly', 1);
        }

        self::$init = true;
    }

    //设置类
    /**
     * 设置前缀和获取前缀
     * @param string $prefix
     * @return string
     */
    public static function prefix($prefix = ''){
        if(empty($prefix)){
            return self::$config['prefix'];
        }

        return self::$config['prefix'] = $prefix;
    }

    /**
     * 设置cookie的值，
     * 删除感觉不彻底
     * @param $name
     * @param string $value
     * @param null $options
     */
    public static function set($name, $value = '', $options = null){

        !isset(self::$init) && self::init();
        //配置整合
        if(!is_null($options)){
            if(is_numeric($options)){
                $options = ['expire'    =>  $options];
            }elseif(is_string($options)){
                parse_str($options, $options);
            }
            $config = array_merge(self::$config, array_change_key_case($options));
        }else{
            $config = self::$config;
        }

        $name = $config['prefix'].$name;

        if(is_array($value)){
            array_walk_recursive($value,'self::jsonFormatProtect', 'encode');
            $value = 'think:' . json_encode($value);
        }

        $expire = !empty($config['expire']) ?
            $_SERVER['REQUEST_TIME'] + intval($config['expire']) :
            0;
        if($config['setcookie']){
            //发送给浏览器的
            setcookie(
                $name, $value,$expire, $config['path'], $config['domain'],$config['secure'], $config['httponly']
            );
        }

        $_COOKIE[$name] = $value;

    }

    /**
     * 设置变量永久的时效期
     * @param $name
     * @param string $value
     * @param null $option
     */
    public static function forever($name, $value = '',$option = null){
        if(is_null($option) || is_numeric($option)){
            $option = [];
        }
        $option['expire'] = 315360000;

        self::set($name, $value, $option);
    }

    /**
     * 删除指定的cookie变量
     * @param $name
     * @param null $prefix
     */
    public static function delete($name, $prefix = null){
        !isset(self::$init) && self::init();

        $config = self::$config;
        $prefix = !is_null($prefix) ?: $config['prefix'];
        $name = $prefix . $name;

        if($config['setcookie']){
            setcookie(
                $name, '', $_SERVER['REQUEST_TIME']-3600,$config['path'], $config['domain'],$config['secure'],$config['httponly']
            );
        }

        unset($_COOKIE[$name]);

    }

    /**
     * 清除指定前缀的cookie
     * @param null $prefix
     */
    public static function clear($prefix= null){
        if(empty($_COOKIE)){
            return ;
        }
        !isset(self::$init) && self::init();
        $config = self::$config;
        $prefix = !empty($prefix) ? $prefix : $config['prefix'];

        if($prefix){
            foreach($_COOKIE as $key=> $value){
                if(0 === strpos($key, 'think:')){
                    if($config['setcookie']){
                        setcookie(
                            $key, '',$_SERVER['REQUEST_TIME']-3600, $config['path'],$config['domain'],$config['secure'],$config['httponly']
                        );

                        unset($_COOKIE[$key]);
                    }
                }
            }
        }

    }

    /**
     * 在进行json之前先url保护
     * 或者解析之后url在拆除
     * @param $val
     * @param $key
     * @param string $type
     */
    protected static function jsonFormatDetect(&$val, $key, $type ='encode'){
        if(!empty($val) && true !== $val){
            $val = 'decode' == $type ? urldecode($val) : urlencode($val);
        }
    }







    //读取类
    /**
     * 读取cookie的值
     * @param $name
     * @param null $prefix
     * @return bool
     */
    public static function has($name, $prefix = null){
        !isset(self::$init) && self::init();

        $prefix = !is_null($prefix) ?: self::$config['prefix'];

        return isset($_COOKIE[$prefix.$name]);
    }

    /**
     * 获取cookie的值
     * @param $name
     * @param null $prefix
     * @return array|bool|mixed
     */
    public static function get($name, $prefix = null){
        !isset(self::$init) && self::init();

        $prefix = !is_null($prefix) ? $prefix : self::$config['prefix'];
        $key = $prefix . $name;

        if('' == $name){
            //读取全部
            //是否存在前缀
            if($prefix){
                $value = [];
                foreach($_COOKIE as $key=> $val){
                    if(strpos($key,$prefix)){
                        $value[$key] = $val;
                    }
                }
            }else{
                $value = $_COOKIE;
            }
        }elseif(isset($_COOKIE[$key])){
            //读取单值
            $value = $_COOKIE[$key];

            if(0 === strpos($value, 'think:')){
                $value = json_decode(substr($value, 6),true);
                $value = array_walk_recursive($value, 'self::jsonFormatProtect', 'decode');
            }

        }else{
            $value = null;
        }
        return $value;
    }


















}
