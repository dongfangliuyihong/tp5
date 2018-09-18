<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 14:17
 */

namespace think\log\driver;

use think\App;
use think\Request;

class File
{
    protected $config = [
        'single'        => false,
        'time_format'   => 'c',//ISO 8601 格式的日期
        'file_maxs'     => 0,
        'file_size'     => 2097152,//2MB
        'path'          =>LOG_PATH,
        'apart_level'   =>[],

    ];

    protected $writed =[];

    /**
     * 构造方法完成配置的初始化
     * @param array $config
     */
    public function __construct($config = []){
        if(is_array($config)){
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 完成日志的记录的整理，以及文件名称的定义。调用最终的写入方法
     * @param array $log
     * @return bool|void
     */
    public function save(array $log = []){

        if($this->config['single']){
            $destination = $this->config['path'] . 'single.log';
        }else{
            $cli = IS_CLI ? '_cli' : '';
            if($this->config['file_maxs']){
                $filename = date('Ymd') . $cli .'.log';
                $files = glob($this->config['path'] . '*.log');
                //判断文件数量
                if(count($files) > $this->config['file_maxs']){
                    unlink($files[0]);
                }
            }else{
                $filename = date('Ym') . '/' .date('d') . $cli .'.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        $dirname = dirname($destination);//不含有反斜杠
        !is_dir($dirname) && mkdir($dirname, 0755, true);

        $info = '';//总文件信息

        foreach($log as $type => $val){
            $level = '';//类别总信息

            foreach($val as $msg){
                if(!is_string($msg)){
                    $msg = var_export($msg, true);
                }
                $level .= '[' . $type .']' . $msg .'\r\n';//记录信息的拼接
            }

            if(in_array($type, $this->config['apart_level'])){
                //需要独立的日志,则需要重新构造文件名，添加类别
                if($this->config['single']){
                    $filename = $dirname .DS .$type . '.log';
                }else{
                    if($this->config['file_maxs']){
                        $filename = $dirname . DS . date('Ymd') . "_" .$type .$cli . '.log';
                    }else{
                        $filename = $dirname .DS . date("Ym") . DS .date('d') . "_" .$type . $cli . ".log";
                    }
                }

                $this->write($level, $filename, true);//写入该类别


            }else{
                $info .= $level;//写入总的信息集团中
            }
        }

        if($info){
            return $this->write($info, $filename);//写入总文件
        }

        return true;



    }

    public function write($message, $destination, $apart = false){
        //先判别文件是否已经超出了容量。
        if(is_file($destination) && filesize($destination) > $this->config['file_maxs']){
            try{
                rename($destination, dirname($destination) . DS .time() ."-" .basename($destination));
            }catch (\Exception $e){

            }
            $this->writed[$destination] = false;
        }

        //是否添加访问总信息
        if(empty($this->writed[$destination]) && !IS_CLI){
            if(APP::$debug &&  !$apart ){
                if(isset($_SERVER['HTTP_HOST'])){
                   $uri = $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];
                }else{
                    $uri = 'cmd:' . implode(" " , $_SERVER['argv']);
                }

                $runtime = number_format(microtime(true) - THINK_START_TIME, 10);//运行时间

                $reqs = $runtime > 0 ? number_format(1 / $runtime, 2) :  '∞';//吞吐率

                $time_str = '[ 运行时间：' . number_format($runtime, 6) . 's][吞吐率：' . $reqs . 'req/s';

                $memory_use = number_format((memory_get_usage()-THINK_START_MEM)/1024,2);//内存
                $memory_str = '[内存消耗：' .$memory_use . 'kb]';

                $file_load = '[文件加载：' . count(get_included_files()) . ']';

                //将基础调试信息添加到记录信息中
                $message = '[ info ] ' . $uri . $time_str . $memory_str . $file_load . "\r\n" . $message;
            }

            //客户端信息
            $now = date($this->config['time_format']);
            $ip = Request::instance()->ip();
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
            $uri =    isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            //组装客户端信息到记录信息中
            $message = "--------------------------------------------------------------" . "\r\n" . "[{$now}]" ."[{$ip}]"."[{$method}]"."[{$uri}]" .$message;

            $this->writed[$destination] = true;//该文件完成读取了，后续直接写入即可
        }

        if(IS_CLI){
            $now = date($this->config['time_format']);
            $message = "[ {$now} ]" .$message;
        }

        return error_log($message, 3, $destination);//写入


    }







}
