<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 13:28
 */

namespace think\exception;


class HttpException extends \RuntimeException
{
    private $statusCode;
    private $headers;

    /**
     * 实例化http异常
     * @param $statusCode
     * @param null $message
     * @param \Exception|null $exception
     * @param array $headers
     * @param int $code
     */
    public function __constrct($statusCode, $message = null, \Exception $exception = null, array $headers = [], $code =0){

        //statusCode 与code的区别
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $exception);

    }

    /**
     * 获取异常号
     * @return mixed
     */
    public function getStatusCode(){
        return $this->statusCode;
    }

    /**
     * 获取http异常的头部
     * @return mixed
     */
    public function getHeaders(){

        return $this->headers;
    }
}
