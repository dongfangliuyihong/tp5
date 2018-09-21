<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/19
 * Time: 14:18
 */

namespace think\exception;

use Exception;
use think\Config;
use think\Log;
use think\Response;

use think\App;
use think\console\Output;
use think\Lang;
class Handle
{

    //渲染输出器
    protected $render;

    //忽略输出的异常
    protected $ignoreReport = [
        '\\think\\exception\\HttpException',
    ];

    //设置类
    /**
     * 设置渲染器
     * @param $render
     */
    public function setRender($render){
        $this->render = $render;
    }


    /**
     * 记录一个非屏蔽的异常信息
     * @param Exception $exception
     */
    public function report(Exception $exception){
        //得不在忽略的异常组里
        if(!$this->isIgnoreReport($exception)){
            //输出的内容根据模式的不同输出不同
            if(App::$debug){
                //调试模式
                $data = [
                    'code'      => $this->getCode($exception),
                    'message'   => $this->getMessage($exception),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),

                ];

                $log = "[{$data['code']}][{$data['message']}][{$data['file']}:{$data['line']}]";
            }else{
                //生产模式
                $data = [
                    'code'      => $this->getCode($exception),
                    'message'   => $this->getMessage($exception),
                ];

                $log = "[{$data['code']}][{$data['message']}]";
            }

            //如果开启记录路径,获取该异常的字符串类型返回异常追踪信息。
            if(Config::get('record_trace')){
                $log .= "\r\n" . $exception->getTraceAsString();
            }

            Log::record($log, 'error');//记录错误信息，异常属于错误信息。

        }
    }


    /**
     * 将异常推到控制台，油控制台完成异常渲染
     * @param Output $output
     * @param Exception $exception
     */
    public function renderForConsole(Output $output, Exception $exception){
        //是否开启调试
        if(APP::$debug){
            //设置冗余级别
            $output->setVerbosity(Output::VERBOSITY);
        }

        $output->renderException($exception);
    }



    /**
     *渲染一个异常到http响应中
     * @param Exception $exception
     * @return mixed
     */
    public function render(Exception $exception){
        if($this->render && $this->render instanceof \Closure){
            $result = call_user_func_array($this->render, [$exception]);

            if($result){
                return $result;
            }
        }

        //http异常，直接使用http渲染
        //非，则转换异常然后到响应中去

        //前瞻：如果开启了调试，实际相当于renderHttpException失效，调用convertExceptionToResponse
        if($exception instanceof HttpException){
            //这是一种自定义的网页访问异常处理反馈
            return $this->renderHttpException($exception);
        }else{
            return $this->convertExceptionToResponse($exception);
        }
    }


















    //读取类




    /**
     * 开启调试无效
     * 渲染一个http异常请求到响应中去
     * @param HttpException $e
     * @return mixed
     */
    public function renderHttpException(HttpException $e){
        $status = $e -> getStatusCode();//这是这个类特有的属性
        $template = Config::get('http_exception_template');//异常模板

        if(!App::$debug && !empty($status)){
            //关闭调试且有正常的http状态码，则自己构造响应。
            return Response::create($template[$status], 'view', $status)->assign(['e' => $e]);
        }else{
            //开启调试，或者获取的状态码为空，还是得走最终的路子
            return $this->convertExceptionToResponse($e);
        }
    }

    /**
     * 把异常信息加入到响应中去，最终返回一个响应。
     * @param Exception $exception
     * @return Response
     */
    public function convertExceptionToResponse(Exception $exception){
        //是否开启调试
        //若$exception为HttpException，倒着来肯定是开启的
        if(App::$debug){
            //获取调试的基本信息
            $data = [
                'name'      => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'message'   => $this->getMessage($exception),//使用自己封装的信息获取方式
                'trace'     => $exception->getTrace(),
                'code'      => $this->getCode($exception),
                'source'    => $this->getSourceCode($exception),//出错的内容前九后九
                'datas'     => $this->getExtendData($exception),//tp基类异常才有的额外数据，不然就为空
                'tables'    => [
                    'GET Data'  => $_GET,
                    'POST Data' => $_POST,
                    'Files'     => $_FILES,
                    'Cookies'   => $_COOKIE,
                    'Sessions'  => $_SESSION,
                    'Server/Request Data'   => $_SERVER,
                    'Environment'           => $_ENV,
                    'ThinkPHP Constants'    => $this->getConst(),//用户自定义的user常量
                ],
            ];
        }else{
            //关闭的情况下，获取那些信息呢
            $data = [
                'code'      => $this->getCode($exception),
                'message'   => $this->getMessage($exception),
            ];

            //是否关闭信息提示，在调试关闭的情况下才起作用,
            if(!Config::get('show_error_msg')){
                $data = Config::get('error_msg');
            }

        }

        //以上就是信息的获取了

        while(ob_get_level() > 1){
            ob_end_clean();
        }

        $data['echo']   = ob_get_clean();

        ob_start();
        extract($data);

        include Config::get('exception_tmpl');

        $content = ob_get_clean();//这是最终的展示页，需要加入到响应中去了

        $response = new Response($content, 'html');

        //若果是http异常，则需要额外设置获取
        if($exception instanceof HttpException){
            $statusCode = $exception->getStatusCode();
            $response->header($exception->getHeaders());
        }

        //其他类型异常，或者http异常代码编号没获取到
        if(!isset($statusCode)){
            $statusCode = 500;
            //不用增加header头部
        }

        $response->code($statusCode);
        return $response;
    }





    /**
     * 判断该异常是否在忽略异常数组中
     * @param Exception $exception
     * @return bool
     */
    public function isIgnoreReport(Exception $exception){
        foreach($this->ignoreReport as $class){
            if($exception instanceof $class){
                return true;
            }
        }
        return false;
    }

    /**
     * 获取异常编码
     * 有一类异常特别处理
     * 若获取不到且异常类型为ErrorException则使用错误级别作为错误编码
     * @param Exception $exception
     * @return int|mixed
     */
    public function getCode(Exception $exception){
        $code = $exception->getCode();//获取异常的错误编码

        //这里实在同一个命名空间里.获得错误码就不用判别了.
        if(!$code && $exception instanceof ErrorException){
            $code = $exception->getServerity();//错误级别
        }
        return $code;
    }

    /**
     * 获取异常信息
     * 区分环境
     * 信息尝试使用系统多语言来描述
     * @param Exception $exception
     * @return string
     */
    public function getMessage(Exception $exception){
        $message = $exception->getMessage();//获取异常的信息

        //区分访问方式
        if(IS_CLI){
            return $message;
        }

        if(strpos($message, ':')){
            $name = strstr($message, ':', true);//取前面的
            $message = Lang::has($name) ? Lang::get($name) . strstr($message, ':') : $message;
        }elseif(strpos($message, ',')){
            $name = strstr($message, ',', true);
            $message = Lang::has($name) ? Lang::get($name) .":" .substr(strstr($message, ','), 1) : $message;
        }elseif(Lang::has($message)){
            $message =  Lang::get($message);
        }

        return $message;

    }

    /**
     * 获取出错位置的文件内容
     * @param Exception $exception
     * @return array
     */
    public function getSourceCode(Exception $exception){
        $line = $exception->getLine();
        $first = ($line-9) > 0 ? $line - 9 : 1;

        try{
            $content = file($exception->getFile());//按行读取到数组中
            $source = [
                'first'     => $first,
                'source'    => array_slice($content, $first-1, 19),
            ];

        }catch (Exception $e){
            $source = [];
        }
        return $source;

    }

    /**
     * 获取额外的异常数据
     * 只有tp系统异常类才有的
     * @param Exception $exception
     * @return array
     */
    public function getExtendData(Exception $exception){
        $data = [];
        if($exception instanceof \think\Exception){
            $data = $exception->getData();
        }

        return $data;
    }

    /**
     * 获取用户自定义的常量
     * @return mixed
     */
    public function getConst(){
        return get_defined_constants(true)['user'];//让此函数返回一个多维数组，分类为第一维的键名，常量和它们的值位于第二维。
    }


}
