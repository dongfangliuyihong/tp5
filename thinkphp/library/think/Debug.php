<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/14
 * Time: 16:28
 */

namespace think;

use think\exception\ClassNotFoundException;
use think\response\Redirect;
class Debug
{
    protected static $info = [];
    protected static $mem = [];


    //设置类
    /**
     * 记录内存和时间
     * 没有返回值；
     * @param $name
     * @param string $value
     */
    public static function remark($name, $value = ''){
        self::$info[$name] = is_float($value) ? $value : microtime(true);

        if('time' != $value){
            self::$mem['mem'][$name] = is_float($value) ? $value : memory_get_usage();
            self::$mem['peak'][$name] = memory_get_peak_usage();
        }
    }

    /**
     * 将调试信息插入响应中
     * 没有返回值，原地址操作
     * @param Response $response
     * @param $content
     * @throws ClassNotFoundException
     */
    public static function inject(Response $response, &$content){
        $config  = Config::get('trace');
        $type = isset($config['type']) ? $config['type'] : 'Html';
        $class = false !== strpos($type,'\\') ? $type : '\\think\\debug\\' . ucwords($type);//每个单词的首字符转换为大写:

        unset($config['type']);
        if(!class_exists($class)){
            throw new ClassNotFoundException('class not found'.$class, $class);
        }

        $trace = new $class($config);

        if($response instanceof Redirect){
            //todo
        }else{
            $output = $trace->output($response, Log::getLog());

            //必须是字符串才行
            if(is_string($content)){
                $pos = strripos($content,'</body>');
                if($pos !==false){
                    $content = substr($content, 0, $pos) . $output . substr($content, $pos);
                }else{
                    $content = $content . $output;
                }
            }

        }

    }







    //获取类
    /**
     * 获取两个标签之间的时间差
     * @param $start
     * @param $end
     * @param int $dec
     * @return string
     */
    public static function getRangTime($start, $end, $dec = 6){
        if(!isset(self::$info[$end])){
            self::$info[$end] = microtime(true);
        }
        return number_format((self::$info[$end] - self::$info[$start]),$dec);
    }

    /**
     * 获取两个标志间使用的内存容量
     * @param $start
     * @param $end
     * @param int $dec
     * @return string
     */
    public static function getRangMem($start, $end, $dec = 2){
        if(!isset(self::$mem['mem'][$end])){
            self::$mem['mem'][$end] = memory_get_usage();
        }
        $size = self::$mem['mem'][$end] - self::$mem['mem'][$start];
        $a = ['b', 'kb', 'mb', 'gb','tb'];
        $pos = 0;

        while($size >= 1024){

            $size /= 1024;
            $pos++;
        }
        return round($size,$dec) . " " . $a[$pos];
    }

    /**
     * 获取两个标志之间的高峰值内存
     * @param $start
     * @param $end
     * @param int $dec
     * @return string
     */
    public static function getMemPeak($start, $end, $dec = 2){
        if(!isset(self::$mem['peak'][$end])){
            self::$mem['peak'][$end] = memory_get_peak_usage();
        }

        $size = self::$mem['peak'][$end] - self::$mem['peak'][$start];
        $a =['b','kb','mb','gb','tb'];

        $pos = 0;
        while($size >= 1024){
            $size /= 1024;
            $pos ++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    /**
     * 获取从系统开始到现在调用的时间
     * @param int $dec
     * @return string
     */
    public static function getUseTime($dec = 6){
        return number_format((microtime(true) - THINK_START_TIME),$dec);
    }

    /**
     * 获取从系统开始到现在使用的内存空间
     * @param int $dec
     * @return string
     */
    public static function getUseMem($dec = 2){
        $size = memory_get_usage() - THINK_START_MEM;
        $a = ['b','kb','mb','gb','tb'];
        $pos = 0;

        while($size >= 1024){
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " .$a[$pos];
    }

    /**
     * 获取当前访问的吞吐量
     * @return string
     */
    public static function getThroughputRate(){
        return  number_format(1/self::getUseTime(), 2) . 'req/s';
    }

    /**
     * 获取当前引入文件的信息或者数量
     * @param bool|false $details
     * @return array|int
     */
    public static function getFile($details = false){
        $files = get_included_files();//别名 get_included_files()

        if($details){
            $info = [];
            foreach($files as $file){
                $info[] = $file . ' (' . number_format(filesize($files)/1024 ,2) . "kb )";
            }

            return $info;
        }
        return count($files);
    }


    /**
     * 浏览器友好输出变量信息，可指定是输出还是返回字符串
     * @param $var
     * @param bool|true $echo
     * @param null $label
     * @param int $flag
     * @return int|string|void
     */
    public static function dump($var, $echo = true, $label = null, $flag = ENT_SUBSTITUTE){
        $label = ($label === null) ? '' : rtrim($label) . ":";

        ob_start();//开始缓冲区
        var_dump($var);

        //替换掉空格
        $output = preg_match('/\]\=\>\n(\s+)/m',']=>',ob_get_clean());

        if(IS_CLI){
            $output = PHP_EOL . $label . $output . PHP_EOL;
        }else{

            if(!extension_loaded('xdebug')){
                $output = htmlspecialchars($output, $flag);
            }

            $output = '<pre>' .$label . $output .'</pre>';

        }

        if($echo){
            echo($output);
            return;
        }
        return $output;
    }















}
