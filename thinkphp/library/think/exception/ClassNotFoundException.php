<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 11:07
 */

namespace think\exception;


class ClassNotFoundException extends \RuntimeException
{
    protected $class;

    /**
     * 构造类不存在异常
     * @param $message
     * @param string $class
     */
    public function __constrcut($message, $class = ''){
        $this->message = $message;
        $this->$class = $class;
    }

    /**
     * 获取异常类名
     * @return mixed
     *
     */
    public function getClass(){
        return $this->class;
    }
}
