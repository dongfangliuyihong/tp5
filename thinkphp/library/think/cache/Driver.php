<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/12
 * Time: 11:02
 */

namespace think\cache;

/**
 * 缓存的基础类
 * Class Driver
 * @package think\cache
 */

abstract class Driver
{
    protected $options =[];

    protected $handler = null;

    protected $tag;

    //设置类
    /**
     * 设置缓存
     * @param $name
     * @param $value
     * @param null $expire
     * @return mixed
     */
    abstract public function set($name, $value, $expire = null);

    /**
     * 缓存变量自增
     * @param $name
     * @param int $step
     * @return mixed
     */
    abstract public function inc($name, $step = 1);

    /**
     * 缓存变量值自减
     * @param $name
     * @param int $step
     * @return mixed
     */
    abstract public function dec($name, $step = 1);

    /**
     * 删除缓存变量值
     * @param $name
     * @return mixed
     */
    abstract public function rm($name);

    /**
     * 清空标签缓存
     * @param $tag
     * @return mixed
     */
    abstract public function clear($tag);


    /**
     * @param $name 标签名
     * @param null $keys 缓存标识，变量，可以是数组，字符串
     * @param bool|false $overlay
     * @return $this
     */
    public function tag($name, $keys = null, $overlay = false){
        if(is_null($name)){

        }elseif(is_null($keys)){
            //设置当前的标签
            $this->tag = $name;
        }else{
            $key = 'tag_' . md5($name);
            if(is_string($keys)){
                $keys = explode(',',$keys);
            }
            //要求时数组
            $keys = array_map([$this,'getCacheKey'], $keys);
            //这里看来其实是作为值了。
            if($overlay){
                $value = $keys;
            }else{
                $value = array_unique(array_merge($this->getTagItem($name),$keys));
            }

            $this->set($key, implode(',', $value), 0);//无期限
        }

        return $this;
    }

    /**
     * 更新到当前缓存标签的缓存标志
      * 传进来的$name是经过getCacheKey之后的文件名
     * @param $name
     */
    protected function setTagItem($name){
        if($this->tag){
            //当前标签下的缓存标志
            $key =  'tag_' . md5($this->tag);//标签名

            $this->tag = null;//一次性的

            if($this->has($key)){
                $value = explode(',' , $this->getIagItem($key));
                $value[] = $name;//追加进去
                $value = implode(',', array_unique($value));
            }else{
                //标签下的的缓存标志
                $value = $name;
            }

            $this->set($key, $value, 0);
        }

        //若当前标签不存在则无法处理
    }


    //读取和设置类

    /**
     * 读取缓存并删除
     * @param $name
     * @return mixed
     */
    public function pull($name){
        $result = $this->get($name, false);
        if($result){
            $this->rm($name);
            return $result;
        }else{
            return;
        }
    }

    /** *
     * 如不存在则缓存起来
     * 安全类的set：加了一道锁
     * 带有
     * 存在就读取
     * @param $name
     * @param $value
     * @param null $expire
     */
    public function remember($name, $value, $expire = null){
        if(!$this->has($name)){
            $time = time();
            //在5秒内，有锁定
            while($time + 5 > time() && $this->has($name . '_lock')){
                usleep(200000);
            }
            //超过5秒，不用管有没有锁定
            //没超过5秒但没有锁定
            try{
                $this->set($name . '_lock', true);
                if($value instanceof \Closure){
                    $value = call_user_func($value);
                }
                $this->set($name, $value, $expire);
                $this->rm($name. '_lock');

            } catch (\Exception $e) {
                $this->rm($name. '_lock');
                throw $e;
            } catch (\throwable $e){
                $this->rm($name. '_lock');
                throw $e;
            }

        }else{
            $this->get($name);
        }
        return $value;
    }


    //读取类
    /**
     * 获取缓存值
     * @param $name
     * @param null $default
     * @return mixed
     */
    abstract public function get($name, $default = null);

    /**
     *判别缓存变量是否存在
     * @param $name
     * @return mixed
     */
    abstract public function has($name);

    /**
     * 获取添加前缀的缓存变量
     * @param $name
     * @return string
     */
    public function getCacheKey($name){
        return $this->options['prefix'] . $name;
    }


    /**
     * 获取指定的标签下的缓存标志
     * @param $tag
     * @return array
     */
    protected function getTagItem($tag){
        $key = 'tag_' . $tag;
        $value = $this->get($key);
        if($value){
            return array_filter(explode(',',$value));//如果没有提供 callback 函数， 将删除 array 中所有等值为 FALSE 的条目
        }else{
            return [];
        }

    }

    /**
     * 获取当前的缓存句柄
     * @return null
     */
    public function handler(){
        return $this->handler;
    }

}
