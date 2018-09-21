<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/19
 * Time: 11:17
 */

namespace think;


class Exception extends \Exception
{
    /**
     * 保存异常页面的额外数据
     * @var array
     */
    protected  $data = [];

    //设置类
    /**
     * 设置异常页面的额外数据
     * @param $label 分类
     * @param array $data 必须为关联数组
     */
    final protected function setData($label, array $data){
        $this->data[$label] = $data;
    }

    //获取类
    /**
     *
     * @return array
     */
    final protected function getData(){
        return $this->data;
    }

}
