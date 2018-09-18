<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 17:40
 */

namespace think\config\driver;


class json
{

    /**
     * 解析
     * @param $config
     * @return mixed
     */
    public function parse($config){
        if(is_file($config)){
            $config = file_get_contents($config);//读取文件
        }

        $result = json_decode($config);
        return $result;

    }
}
