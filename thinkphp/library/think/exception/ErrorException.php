<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 13:39
 */

namespace think\exception;

use think\Exception;
class ErrorException extends  Exception
{
    protected $serverity;

    public function __constrct($serverity, $message, $file, $line, array $context =[]){

        $this->serverity = $serverity;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->code = 0;

        empty($context) || $this->setData('Error Context', $context);
    }


    /**
     * 获取错误级别
     * @return mixed
     */
    public function getServerity(){
        return $this->serverity;
    }

}
