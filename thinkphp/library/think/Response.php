<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 15:21
 */

namespace think;


class Response
{
    //原始数据
    protected $data;

    protected $code = 200;

    protected $header = [];

    protected $options = [];

    //输出内容类型
    protected $contentType = 'text/html';
    //输出内容的字符集
    protected $charset = 'utf-8';
    protected $content = null;

    /**
     * 创建response对象的入口静态方法
     * @param string $data
     * @param string $type
     * @param int $code
     * @param array $header
     * @param array $options
     * @return static
     */
    public static function create($data='',$type='', $code=200, array $header=[],$options =[]){
        //创建什么样的对象
        $class = false !== strpos($type,'\\') ? $type : '\\think\\response\\'.ucfirst(strtolower($type));
        if(class_exists($class)){//存在会自动加载
            $response = new $class($data,$code,$header,$options);
        }else{
            $response = new static($data,$code,$header,$options);
        }
        return $response;
    }
    /**
     * 具有构造函数的类会在每次创建新对象时先调用此方法
     * @param string $data 数据
     * @param int $code 状态码
     * @param array $header 头信息
     * @param array $options 参数信息
     */
    public function __construct($data='', $code=200, array $header = [],$options = []){
        //初始化相应类的实例

        //数据处理
        $this->data($data);
        //参数赋值
        if(!empty($options)){
//            $this->options = $options;
            $this->options = array_merge($this->options,$options);
        }
        //设置页面输出类型.先构造header中的content-type
        $this->contentType($this->contentType,$this->charset);
        //header信息赋值
        if(!empty($header)){
//            $this->header = $header;
            $this->header = array_merge($this->header,$header);
        }
        //状态码赋值
        $this->code = $code;

    }

    /**
     * 发送数据到客户端
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function send(){
        //真正发送数据之前，按个钩子,将对象传进去了
        Hook::listen('response_send',$this);

        //获取当前的数据，做一下记录或者处理.
        $data = $this->getContent();//这里的data就是要输出的data了。

        //获取环境变量值,
        //获取配置参数 为空则获取所有配置 private static $config ;
        //若开启则   调试信息注入到响应中
        if(Env::get('app_trace',Config::get('app_trace'))){
            Debug::inject($this,$data);
        }

        //为输出做准备

        //若httpcode正常，先处理缓存
        if(200 == $this->code){
            //读取请求缓存设置.
            //todo 一定要看$cache的内容
            $cache = Request::instance()->getCache();
            if($cache){
                $this->header['Cache-Control'] = 'max-age' . $cache[1] . ',must-revalidate';
                $this->header['Last-Modified'] = gmdate('D,d M Y H:i:s') . ' GMT';
                $this->header['Expires']        = gmdate('D,d M Y H:i:s', $_SERVER['REQUEST_TIME']+$cache[1]) .  ' GMT';
                Cache::tag($cache[2])->set($cache[0],[$data, $this->header], $cache[1]);
            }
        }

        //检查 HTTP 标头是否已被发送
        //一旦报头块已经发送，就不能使用 header() 函数 来发送其它的标头
        if(!headers_sent()&& !empty($this->header)){

            //发送状态妈
            http_response_code($this->code);
            //发送头部信息
            foreach($this->header as $name => $val){
                if(is_null($val)){
                    //向客户端发送原始的 HTTP 报头。
                    header($name);
                }else{
                    header($name . ':' . $val);
                }
            }
        }

        echo $data;

        //后续的处理
        if(function_exists('fastcgi_finish_request')){
            fastcgi_finish_request();//优先关闭链接，后台在处理一些别的事情，增加响应速度。
        }

        //钩子安插
        Hook::listen('response_end',$this);

        // 清空当次请求有效的数据
        //todo不知道为什么清除。
        if(!($this instanceof RedirectResponse)){
            Session::flush();
        }

    }

    //若在发送之前想要更改一些内容的方法



    //设置类
    /**
     * 将输出类型添加头部参数中
     * @param $contentType
     * @param string $charset
     * @return $this
     */
    public function contentType($contentType,$charset = 'utf-8'){
        $this->header['Content-Type'] = $contentType.' ; charset=' . $charset;
        return $this;
    }

    /**
     * 设置参数
     * @param array $options
     * @return $this
     */
    public function options($options = []){
        $this->options = array_merge($this->options,$options);
        return $this;
    }

    /**
     * 设置response类的header属性
     * @param $name
     * @param null $value
     * @return $this
     */
    public function header($name, $value = null){
        if(is_array($name)){
            $this->header = array_merge($this->header,$name);
        }else{
            $this->header['name'] = $value;
        }
        return $this;
    }

    /**
     * 设置http状态码
     * @param $code
     * @return $this
     */
    public function code($code){
        $this->code = $code;
        return $this;
    }

    /**
     * 标记此文件在服务器端最后被修改的时间
     * @param $time
     * @return $this
     */
    public function lastModified($time){
        $this->header['Last-Modified'] = $time;
        return $this;
    }

    /**
     *一个网页或URL地址不再被浏览器缓存的时间
     * 请求时间+缓存设置时间。
     * 这里直接是时间节点
     * @param $time
     * @return $this
     */
    public function expires($time){
        $this->header['Expires'] = $time;
        return $this;
    }

    /**
     * 服务器开发者会把ETags和GET请求的“If-None-Match”头一起使用，这样可利用客户端（例如浏览器）的缓存。
     * 因为服务器首先产生ETag，服务器可在稍后使用它来判断页面是否已经被修改。
     * 本质上，客户端通过将该记号传回服务器要求服务器验证其（客户端）缓存
     * 未被修改，直接返回响应304（未修改——Not Modified）和一个空的响应体。
     * 用于标示URL对象是否改变
     * @param $eTag
     */
    public function eTag($eTag){
        $this->header['ETag'] = $eTag;
        return;
    }

    /**
     * 页面缓存设置
     * @param string $cache
     * @return $this
     */
    public function cacheControl($cache){
        $this->header['Cache-Control'] = $cache;
        return $this;
    }

    /**
     * 设置页面输出内容
     * @param $content
     * @return $this
     */
    public function content($content){
        if(null !== $content &&!is_string($content) && !is_numeric($content) && !is_callable([$content,'__toString'])){
            throw new \InvalidArgumentException("variable type error： %s", gettype($content));
        }
        $this->content = (string) $content;

        return $this;
    }
    /**
     * 数据初始化
     * @param $data
     * @return $this
     */
    public function data($data){
        $this->data = $data;
        return $this;
    }















    //读取类
    /**
     * 获取头部信息
     * @param string $name
     * @return array|null
     */
    public function getHeader($name=''){
        if(!empty($name)){
            return isset($this->header[$name]) ? $this->header[$name] : null;
        }else{
            return $this->header;
        }
    }

    /**
     * 获取原始数据
     * @return mixed
     */
    public function getData(){
        return $this->data;
    }

    /**
     * 获取当前状态码
     * @return int
     */
    public function getCode(){
        return $this->code;
    }


    /**
     * 处理数据
     * 这里只是直接返回原样的数据
     * @param $data
     * @return mixed
     */
    public function output($data){
        return $data;
    }

    /**
     * 获取处理后的内容
     * @return null
     */
    public function getContent(){
        //要先判别是否存在内容
        if(null==$this->content){
            //处理一下原始数据。
            $content = $this->output($this->data);
            //检查类型
            if(null !==$content&& !is_string($content) && !is_numeric($content) && !is_callable([$content,'__toString',])){
                //当不为null，不是字符串不是数字，也不是对象（字符转化函数不可用）跑出一个不值异常(类型错误)
                throw new \InvalidArgumentException(sprintf('variable type error %s',gettype($content)));//TODO
            }

            $this->content = (string)$content;//若为对象，此时会调用__toString
        }
        return $this->content;
    }
}
