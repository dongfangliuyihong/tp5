<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/27
 * Time: 11:14
 */

namespace think;

use think\exception\ClassNotFoundException;
class Session
{
    protected static $init = null;
    protected static $prefix = '';

    //不会报错，只有成功与不成功
    public static function init(array $config = []){
//        $config = [
//            'user_trans_sid'    => 1,
//            'auto_start'        => 1,
//            'prefix'            => '',
//            'var_session_id'    =>'sid',
//            'id'                =>1,
//            'name'              => 'sid',
//            'path'              => '',
//            'domain'            =>'',
//            'empire'            =>0,
//            'secure'            =>'',
//            'httponly'          =>'',
//            'use_cookies'       =>'',
//            'cache_limiter'     =>'',
//            'cache_expire'      => 0,
//            'type'              =>'',
//
//        ];
        if(empty($config)){
            $config = Config::get('session');
        }
        //session类接管过来，全部类来控制，

        //记录初始化信息
        App::$debug && Log::record('[ SESSION ] INIT' .var_export($config,true), 'info');

        //是否初始化完成
        $isDoStart = false;
        if(isset($config['user_trans_sid'])){
            ////session传递，当客户端禁用cookie时，是否自动添加到URL上
            ini_set('session.user_trans_sid', $config['user_trans_id'] ? 1: 0);
        }
        //使用session必须session_start(),只是自动的话写在框架中，不是自动的不用谢，用户自己写

        // 'auto_start'     => true,
        //开启自动。
        //若已经开启，则不需要开启了。
        if(!empty($config['auto_start']) && PHP_SESSION_ACTIVE != session_status()){
            //自动开启,且没有使用session_start()的时候
            ini_set('sesion.auto_start',0);
            $isDoStart = true;
        }
        //前缀+请求session_id变量名
        if(isset($config['prefix']) && (''===self::$prefix || null===self::$prefix)){
            self::$prefix = $config['prefix'];
        }

        //取值
        if(isset($config['var_session_id']) && isset($_REQUEST[$config['var_session_id']])){
            // 获取/设置当前会话 ID
            session_id($_REQUEST[$config['var_session_id']]);
        }elseif(isset($config['id']) && !empty($config['id'])){
            session_id($config['id']);
        }
        //在 session_start() 前调用了 session_name('SID'); 则会改变session_name
        if(isset($config['name'])){
            session_name($config['name']);
        }
        if(isset($config['path'])){
            session_save_path($config['path']);
        }
        if(isset($config['domain'])){
            ini_set('session_cookie_domain',$config['domain']);
        }
        if(isset($config['expire'])){
            ini_set('session.gc_maxlifetime', $config['expire']);
            ini_set('session.cookie_lifetime',$config['expire']);
        }
        if(isset($config['secure'])){
            //表示创建的 Cookie 会被以安全的形式向服务器传输，也就是只能在 HTTPS 连接中被浏览器传递到服务器端进行会话验证，
            //如果是 HTTP 连接则不会传递该信息，所以不会被窃取到Cookie 的具体内容。
            ini_set('session.cookie_secure', $config['secure']);
        }
        if(isset($config['httponly'])){
            //那么通过程序(JS脚本、Applet等)将无法读取到Cookie信息，这样能有效的防止XSS攻击
            ini_set('session.cookie_httponly', $config['httponly']);
        }
        if(isset($config['use_cookies'])){
            //是否在客户端用 cookie 来存放会话 ID
            ini_set('session.use_cookies',$config['use_cookies'] ? 1 : 0);
        }
        if(isset($config['cache_limiter'])){
            session_cache_limiter($config['cache_limiter']);
        }
        if(isset($config['cache_expire'])){
            session_cache_expire($config['cache_expire']);
        }
        //是否需要驱动，默认不需要，直接使用文件
        if(isset($config['type'])){
            $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\session\\driver\\'.ucwords($config['type']);
            if(!class_exists($class) || !session_set_save_handler(new $class($config))){
                throw new ClassNotFoundException('error session handler:' . $class, $class);
            }
        }
        //以上是完成session的个性配置，也就是初始化。

        if($isDoStart){
            //如果参数为0，又没手动开启session，则会报错。
            session_start();
            self::$init = true;
        }else{
            //到这一步的几个原因。
            //设置不自动化开启
            //或者自动化开启，但是有会话环境存在

            self::$init = false;//标志没有启动，实际有可能启动了，也有可能没启动。若没有启动，则一定是关闭了
        }


    }

    /**
     * 每个请求需要用session时都要session_start().
     * 若为true则说明启动了
     *
     * 对关闭自动开启自己又忘了开启，还不初始化的无能为力。
     * session自动启动或者初始化
     */
    public static function boot(){
        if(is_null(self::$init)){
            self::init();//初始化
        }elseif(false === self::$init){
            //若已经开启则不需要在开启了
            if(PHP_SESSION_ACTIVE != session_status()){//关闭的情况，自己又忘了开启的情况
                //防止开两次
                //启动
                session_start();
            }
            //一定开启了
            self::$init = true;
        }
    }

    //设置类


    //一定会走一遍初始化，开启了就成功，不开启就失败

    //只要你初始化，就一定能设置成功。
    //不初始化，设置开启，但自己开启session，也能设置成功
    /**
     * 设置session变量值
     * 可直接用，要去开启session
     * @param $name
     * @param string $value
     * @param null $prefix
     */
    public static function set($name, $value = '', $prefix = null){
        empty(self::$init) && self::boot();

        $prefix = !is_null($prefix) ? $prefix : self::$prefix;
        if(strpos($name,'.')){
            list($name1, $name2) = explode('.', $name);
            if($prefix){
                $_SESSION[$prefix][$name1][$name2] = $value;
            }else{
                $_SESSION[$name1][$name2] = $value;
            }
        }elseif($prefix){
            $_SESSION[$prefix][$name] = $value;
        }else{
            $_SESSION[$name] = $value;
        }
    }

    /**
     * 追加到名为key的数组中去
     * @param $key
     * @param $value
     */
    public static function push($key, $value){
        $array = self::get($key);
        if(is_null($array)){
            $array = [];
        }
        $array[] = $value;
        self::set($key, $array);//带有检查
    }

    /**
     * 清空session数据，服务端的
     * 成批的
     * @param null $prefix
     */
    public static function clear($prefix = null){
        empty(self::$init) && self::boot();
        $prefix = !is_null($prefix) ? $prefix : self::$prefix;

        if($prefix){
            unset($_SESSION[$prefix]);
        }else{
            $_SESSION = [];
        }
    }

    /**
     * 删除指定session变量或者一组变量
     * @param string $name
     * @param null $prefix
     */
    public static function delete($name ='',$prefix = null){
        empty(self::$init) && self::boot();
        $prefix = !is_null($prefix) ? $prefix : self::$prefix;
        if(is_array($name)){
            foreach($name as $key){
                self::delete($key);
            }
        }elseif(strpos($name, '.')){
            list($name1, $name2) = explode('.', $name);
            if($prefix){
                unset($_SESSION[$prefix][$name1][$name2]);
            }else{
                unset($_SESSION[$name1][$name2]);
            }
        }else{
            if($prefix){
               unset($_SESSION[$prefix][$name]);
            }else{
                unset($_SESSION[$name]);
            }
        }
    }

    /**
     * 读完即销毁
     * @param $name
     * @param null $prefix
     * @return array|null|void
     */
    public static function pull($name, $prefix = null){
        $result = self::get($name, $prefix);//带检查
        if($result){
            self::delete($name, $prefix);
            return $result;
        }else{
            return;
        }
    }


    /**
     * 闪存数据，支持闪删
     * @param $name
     * @param $value
     */
    public static function flash($name, $value){
        //闪存
        self::set($name, $value);//自带检查
        if(!self::has('__flash__.__time__')){
            //这是二维
            self::set('__flash__.__time__', $_SERVER['REQUEST_TIME_FLOAT']);//请求开始时的时间戳，微秒级别的精准度
        }
        self::push('__flash__',$name);//有个set
    }

    /**
     * 清空闪存session数据
     */
    public static function flush(){
        //必须初始化完成才能闪删。
        if(self::$init){//session启动后
            $item = self::get('__flash__');
            if(!empty($item)){
                $time = $item['__time__'];
                if($_SERVER['REQUEST_TIME_FLOAT']>$time){
                    unset($item['__time__']);//只剩名字了
                    self::delete($item);
                    self::set('__flash__',[]);
                }
            }
        }
    }












    //获取类

    /**
     * 获取session变量
     * 多值返回空数组，单值返回null
     * @param string $name
     * @param null $prefix
     * @return array|null
     */
    public static function get($name = '', $prefix = null){
        empty(self::$init) && self::boot();
        $prefix = !is_null($prefix) ? $prefix : self::$prefix;

        if('' === $name){
            $value = $prefix ? (!empty($_SESSION[$prefix]) ? $_SESSION[$prefix] : []) : $_SESSION;
        }elseif($prefix){
            if(strpos($name, '.')){
                list($name1, $name2) = explode('.', $name);
                $value = isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            }else{
                $value = isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        }else{
            if(strpos($name, '.')){
                list($name1, $name2) = $name;
                $value = isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            }else{
                $value = isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }

        }
        return $value;
    }

    /**
     * 判断是否存在该session变量
     * @param $name
     * @param null $prefix
     * @return bool
     */
    public static function has($name, $prefix = null){
        empty(self::$init) && self::boot();
        $prefix = !is_null($prefix) ? $prefix : self::$init;
        if(strpos($name, '.')){
            list($name1, $name2) = explode('.', $name);
            return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
        }else{
            return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * 设置和获取前缀
     * @param string $prefix
     * @return string
     */
    public static function prefix($prefix = ''){
        empty(self::$init) && self::boot();
        if(empty($prefix) && null !== self::$prefix){
            //也可以返回空值
            return self::$prefix;
        }else{
            self::$prefix = $prefix;
        }

    }

    //操作类
    /**
     * 启动session
     */
    public static function start(){
        session_start();
        self::$init = true;
    }

    /**
     * 暂停session
     */
    public static function pause(){
        session_write_close();
        self::$init = false;
    }

    /**
     * 重新生成id
     * @param bool|false $delete
     */
    public static function regenerrate($delete = false){
        session_regenerate_id($delete);
    }

    /**
     * 销毁session
     */
    public static function destroy(){
        if(!empty($_SESSION)){
            $_SESSION = [];//内存中的
        }
        session_unset();//;//释放所有的会话变量就是$_SESSION,但是不删除session文件以及不释放对应的session id；
        session_destroy();//删除当前用户对应的session文件以及释放session id，内存中$_SESSION变量内容依然保留；
        self::$init = null;
    }
}
