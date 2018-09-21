<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 16:04
 */

namespace think;

use think\console\Output as ConsoleOutput;
use think\exception\ErrorException;
use think\exception\ThrowableError;
use think\exception\Handle;


class Error
{

    //设置类
    /**
     * 注册异常处理，错误也是一种异常
     */
    public static function register(){
        error_reporting(E_ALL);
        set_error_handler([__CLASS__,'appError']);
        //用于没有用 try/catch 块来捕获的异常。 在 exception_handler 调用后异常会中止。
        set_exception_handler([__CLASS__,'appException']);
        register_shutdown_function([__CLASS__,'appShutdown']);
    }






    //读取类

    /**
     * @param $e
     */
    public static function appException($e){
        if(! $e instanceof \Exception){
            $e = new ThrowableError($e);
        }

        $handler = self::getExceptionHandler();
        $handler->report($e);//记录下来该异常

        if(IS_CLI){
            $handler->renderForConsole(new ConsoleOutput(),$e);
        }else{
            $handler->render($e)->send();
        }

    }


    /**
     * 获取异常处理对象
     * @return Handle
     */
    public static function getExceptionHandler(){
        static $handler;
        if(!$handler){
            $class = Config::get('exception_handle');

            if($class && class_exists($class) && is_subclass_of($class, "\\think\\exception\\Handle")){
                $handler = new $class;
            }else{
                $handler = new Handle();

                if($class instanceof \Closure){
                    $handler->setRender($class);
                }
            }


        }
        return $handler;
    }

    /**
     * 处理应用错误的
     * 生成一个错误异常
     * 错误用异常的形式告知
     * @param $errno
     * @param $errstr
     * @param string $errfile
     * @param int $errline
     * @throws ErrorException
     */

    public static function appError($errno, $errstr, $errfile = '', $errline =0){
        //首先要生成一个错误异常
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);//其实还有一个参数是额外内容的，他继承系统异常呢

        if(error_reporting() & $errno){
            throw $exception;
        }

        self::getExceptionHandler()->report($exception);//记录下来这个错误异常

    }

    /**
     * 处理程序异常终止，属于致命的错误的直接抛出，然后记录
     * exit；就能导致shutdown，但此时只是记录
     */
    public static function appShutdown(){
        if(!is_null($data = error_get_last()) && self::isFatal($data['type'])){
            self::appException(new ErrorException([
                $data['type'], $data['message'], $data['file'], $data['line']
            ]));
        }

        //写入日志
        Log::save();

    }

    /**
     * 判别是否为致命错误
     * @param $type
     * @return bool
     */
    public static function isFatal($type){
        return in_array($type,[E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_PARSE]);
    }


}
