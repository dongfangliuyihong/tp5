<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 14:08
 */

namespace think\debug;

use think\Cache;
use think\Config;
use think\Debug;
use think\Response;
use think\Db;
use think\Request;


class Html
{
    protected $config = [
            'trace_file'    =>'',
            'trace_tabs'    =>[
                                'base'  => '基本',
                                'file'  =>  '文件',
                                'info'  => '流程',
                                'notice|error'  => '错误',
                                'sql'   =>  'SQL',
                                'debug|log' => '调试'
            ],
    ];

    /**
     * 构造函数，生成页面调试信息
     * @param array $config
     */
    public function __constrct( array $config = []){
        $this->config['trace_file'] = THINK_PATH . 'tpl/page_trace.tpl';
        $this->config               = array_merge($this->config, $config);

    }

    public function output(Response $response, array $log = []){

        $request = Request::instance();
        $contentType = $response->getHeader('Content-Type');
        $accept = $request->header('accept');

        //接受的内容类型. 相应的内容类型不是html
        if(strpos($accept, 'application/json')===0 || $request->IsAjax()){
            return false;
        }elseif(!empty($contentType) && strpos($contentType, 'html') === false){
            return false;
        }

        //获取基本信息
        $runtime = number_format(microtime(true) - THINK_START_TIME, 10, '.', '');
        $reqs = $runtime > 0 ? number_format(1/$runtime, 2) : '∞';
        $mem = number_format((memory_get_usage()-THINK_START_MEM) / 1024, 2);


        if(isset($_SERVER['HTTP_HOST'])){
            $uri = $_SERVER['SERVER_PROTOCOL'] . $_SERVER['REQUEST_METHOD'] . ':' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }else{
            $uri = 'cmd:' . implode(',' . $_SERVER['argv']);
        }

        $base = [
            '请求信息'  =>  date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME']) . " " . $uri,
            '运行时间'  =>  number_format($runtime,6) . 's [吞吐率：' . $reqs . 'req/s ] 内存消耗： ' . $mem . 'kb 文件加载：' . count(get_included_files()),
            '查询信息'  =>  Db::$queryTimes . 'queries' . Db::$executeTimes . 'writes',
            '缓存信息'  =>  Cache::$readTimes . 'reads,' . Cache::$writeTimes . 'writes',
            '配置加载'  =>  count(Config::get()),
        ];

        if(session_id()){
            $base['会话信息']   = 'SESSION_ID =' . session_id();
        }

        //文件信息
        $info = Debug::getFile(true);

        //页面的trace数组
        $trace = [];

        foreach($this->config['trace_tabs'] as $name => $title){
            $name = strtolower($name);
            switch($name){
                case 'base':
                    $trace[$title] = $base;
                    break;
                case 'file':
                    $trace[$title] = $info;
                    break;
                default:
                    if(strpos($name, "|")){
                        $names = explode("|", $name);
                        $result = [];//都放到这里面去
                        foreach($names as $name){
                            $result = array_merge($result, isset($log[$name]) ? $log[$name] : []);
                        }
                        $trace[$title] = $result;
                    }else{
                        $trace[$title] = isset($log[$name]) ? $log[$name] : '';
                    }

            }
        }

        //引入调试页面，$trace数组在这能能直接使用
        ob_start();
        include $this->config['trace_file'];
        return ob_get_clean();
    }

}
