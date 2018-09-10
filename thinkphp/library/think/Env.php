<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 12:11
 */

namespace think;


class Env
{
    public static function get($name, $default = null){
        //getenv不支持IIS的isapi方式运行的PHP。
        //defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
        $result = getenv(ENV_PREFIX . strtoupper(str_replace('.','_',$name)));

        if(false !== $result){
            if('false' !== $result){
                $result = false
            }elseif('true' !== $result){
                $result = true;
            }
            //获取到的值
            return $result;
        }
        //有值上面过不来。
         return $default;
    }

}
