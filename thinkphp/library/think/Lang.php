<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/21
 * Time: 13:44
 */

namespace think;


class Lang
{
    //语言转换数组
    private static $lang = [];
    //当前语言类型，作用域
    private static $range = 'zh-cn';
    //语言侦测变量名
    protected static $langDetectVar = 'lang';
    //语言Cookie侦测变量名
    protected static $langCookieVar = 'think_var';
    //语言cookie变量名的有效时间
    protected static $langCookieExpire = 3600;
    //系统允许的语言类型
    protected static $allowLangList = [];
    //接收转换的语言
    protected static $acceptLanguage = ['zh-hans-cn' => 'zh-cn'];


    //设置类
    /**
     * 设置当前的语言种类
     * @param string $rang
     * @return string
     */
    public static function range($rang = ''){
        if($rang){
            self::$range = $rang;
        }
        return self::$range;
    }

    /**
     * 设置语言探测变量名
     * 自我感觉这个地方不严谨
     * @param $var
     */
    public static function setLangDetectVar($var){
        self::$langDetectVar = $var;
    }

    /**
     * 设置侦测的cookie变量名
     * @param $var
     */
    public static function setLangCookieVar($var){
        self::$langCookieVar = $var;
    }

    /**
     * 设置语言的cookie的过期时间
     * @param $expire
     */
    public static function setLangCookieExpire($expire){
        self::$langCookieExpire = $expire;
    }

    /**
     * 设置系统允许的语言类型
     * @param $list
     */
    public static function setAllowLangList($list){
        self::$allowLangList = $list;
    }

    /**
     * 加载指定作用域下的语言文件，并返回该作用域下的所有数组内容
     * @param $file
     * @param string $range
     * @return mixed
     */
    public static function load($file, $range = ''){
        $range = $range ?: self::$range;

        $file = is_string($file) ? [$file] : $file;

        if(!isset(self::$lang[$range])){
            self::$lang[$range] = [];
        }

        $lang = [];
        foreach($file as $_file){
            if(is_file($_file)){
                //加载记录信息
                App::$debug && Log::record('[ lang ] '.$_file, 'info');

                $_lang = include $_file;
                if(is_array($_lang)){
                    $lang = array_change_key_case($_lang) + $lang;
                }
            }
        }

        if(!empty($lang)){
            self:;$lang[$range] = $lang + self::$lang[$range];
        }
        return self::$lang[$range];
    }

    /**
     * 设置语言变量内容
     * @param $name
     * @param null $value
     * @param string $range
     * @return array|null
     */
    public static function set($name, $value = null, $range = ''){
        $range = $range ?: self::$range;

        if(!isset(self::$lang[$range])){
            self::$lang[$range] = [];
        }

        if(is_array($name)){
            return self::$lang[$range] = array_change_key_case($name) + self::$lang[$range];
        }

        return self::$lang[$range][strtolower($name)] = $value;
    }













    //读取类
    /**
     * 读取作用域下的语言变量是否存在
     * @param $name
     * @param string $range
     * @return bool
     */
    public static function has($name, $range = ''){

        $range = $range ?: self::$range;

        return isset(self::$lang[$range][strtolower($name)]) ;
    }

    /**
     * 获取语言的内容
     * 支持变量替换
     * @param null $name
     * @param array $var
     * @param string $range
     * @return mixed|null
     */
    public static function get($name = null, $var =[], $range = ''){
        $range = $range ?: self::$range;

        if(empty($name)){
            return self::$lang[$range];
        }

        $key = strtolower($name);
        $value = isset(self::$lang[$range][$key]) ? self::$lang[$range][$key] : $name;//正常读取

        //存在变量解析
        if(!empty($var) && is_array($var)){
            if(key($var)===0){
                array_unshift($var, $value);
                $value = call_user_func_array('sprintf', $var);
            }else{
                $repalce = array_keys($var);
                foreach($repalce as &$v){
                    $v = "{:{$v}}";
                }

                str_replace($repalce,$var, $value);
            }
        }

        return $value;
    }

    /**
     * 自动探测语言种类，并返回
     * @return string
     */
    public static function detect(){
        //语言类型，即作用域
        $langSet = '';

        if(isset($_GET[self::$langDetectVar])){
            $langSet = strtolower($_GET[self::$langDetectVar]);
        }elseif(isset($_COOKIE[self::$langCookieVar])){
            $langSet = strtolower($_COOKIE[self::$langCookieVar]);
        }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
            //到这一步，需要查看系统是否有匹配的允许和接受转义的语言类型

            preg_match('/^(a-z\d\-)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $metches);
            $langSet = strtolower($metches[1]);
            $acceptLanguage = Config::get('header_accept_lang');

            if(isset($acceptLanguage[$langSet])){
                $langSet = $acceptLanguage[$langSet];
            }elseif(isset(self::$acceptLanguage[$langSet])){
                $langSet = self::$acceptLanguage[$langSet];
            }


        }

        //语言的总控
        if(empty(self::$allowLangList) || in_array($langSet, self::$allowLangList)){
            self::$range = $langSet ?: self::$range;
        }

        return $langSet;
    }






}
