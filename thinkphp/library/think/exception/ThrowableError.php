<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 18:09
 */

namespace think\exception;


class ThrowableError extends \Exception
{


    /**
     * 构造可抛出异常，基于php的异常基类
     * 多弄了一个trace的设置
     * @param \Throwable $e
     */
    public function __constrcut(\Throwable $e){

        if($e instanceof \PareseError){
            $message = 'Parse error :' .$e->getMessage();
            $serverity = E_PARSE;
        }elseif($e instanceof \TypeError){
            $message = 'Type error:'   . $e->getMemssage();
            $serverity = E_RECOVERABLE_ERROR;
        }else{
            $message = 'Fatal Error:'  .$e->getMessage();
            $serverity = E_ERROR;
        }

        //完成php的异常构造
        parent::__construct(
            $message,
            $e->getCode(),
            $serverity,
            $e->getLine(),
            $e->getFile()
        );

        $this->setTrace($e->getTace());
    }

    /**
     * 初始化补充，完成对trace属性的设置。
     * @param $trace
     */
    protected function setTrace($trace){

        $traceReflector = new \ReflectionProperty('Exception', 'trace');
        $traceReflector->setAccessible(true);
        $traceReflector->setValue($this, $trace);

    }

}
