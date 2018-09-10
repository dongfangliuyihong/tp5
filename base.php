<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 12:25
 */

define('EXT','.php');
define('DS', DIRECTORY_SEPARATOR);

defined('APP_PATH') or define('APP_PATH',dirname($_SERVER['SCRIPT_FILENAME']) . DS);//实际再入口文件中定义改变了

defined('CONF_PATH') or define('CONF_PATH',APP_PATH);//配置路径和app路径一致
defined('CONF_EXT') or define('CONF_EXT',EXT);//配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX','PHP_');//环境变量的配置前缀
