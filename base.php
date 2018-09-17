<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 12:25
 */

define('THINK_START_TIME', microtime(true));
define('THINK_START_MEM', memory_get_usage());


define('EXT','.php');
define('DS', DIRECTORY_SEPARATOR);


defined('THINK_PATH') or define('THINK_PATH', __DIR__ . DS);



defined('APP_PATH') or define('APP_PATH',dirname($_SERVER['SCRIPT_FILENAME']) . DS);//实际再入口文件中定义改变了
defined('ROOT_PATH') or define('ROOT_PATH',dirname(realpath(APP_PATH)) . DS);//项目的根目录



defined('RUNTIME_PATH') or define('RUNTIME_PATH',ROOT_PATH . 'runtime' . DS);//运行时文件目录
defined('CACHE_PATH') or define('CACHE_PATH',RUNTIME_PATH .'cache' . DS);//缓存路径


defined('CONF_PATH') or define('CONF_PATH',APP_PATH);//配置路径和app路径一致
defined('CONF_EXT') or define('CONF_EXT',EXT);//配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX','PHP_');//环境变量的配置前缀


//环境常量
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
