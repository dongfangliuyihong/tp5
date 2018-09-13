<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/11
 * Time: 18:30
 */

namespace think\cache\driver;

use think\cache\Driver;

/**
 * 文件类型的缓存
 * 缓存变量名为文件名，值为文件内容
 *
 * Class File
 * @package think\cache\driver
 */
class File extends Driver
{
    //默认的实例化缓存参数
    protected  $options = [
        'expire'        => 0,
        'cache_subdir'  => true,//是否使用子目录
        'prefix'        =>'',
        'path'          => CACHE_PATH,
        'data_compress' => false,
    ];

    protected $expire;

    /**
     * 构造方法，完成参数的初始化以及路径的检查
     * @param array $options
     */
    public function __construct($options = []){

        if(!empty($options)){
            $this->options = array_merge($this->options,$options);
        }

        if(substr($this->options['path'],-1) != DS){
            $this->options['path'] .= DS;
        }

        $this->init();
    }

    /**
     * 缓存小兵的初始化工作
     * 完成缓存路径的检查
     *
     * 不明白这个地方为什么要返回false
     * @return bool
     */
    private function init(){
        //创建项目缓存目录
        if(!is_dir($this->options['path'])){
            if(mkdir($this->options['path'], 0755, true)){
                return true;
            }
        }
        return false;
    }

    //设置类
    /**
     * 设置缓存变量，返回boolean
     * @param $name
     * @param $value
     * @param null $expire
     * @return bool
     */
    public function set($name, $value, $expire = null){
        if(is_null($expire)){
            $expire = $this->options['expire'];//有默认值
        }
        //有效时间是时间对象
        if($expire instanceof \DateTime){
            $expire = $expire->getTimestamp() - time();
        }

        $filename = $this->getCacheKey($name,true);//若没有目录创建了目录
        if($this->tag && !is_file($filename)){
            //再有缓存标志的情况下，是否有这个文件，表明要增加缓存标志内容
            $first =true;//增加内容
        }

        $data = serialize($value);

        //这也是一个bool值
        if($this->options['data_compress'] && function_exists('gzcompress')){
            $data = gzcompress($data, 3);//压缩的级别
        }

        //缓存的内容格式
        $data = "<?php\n//" . sprintf("%012d",$expire) . "\n exit();?>\n" . $data;

        $result = file_put_contents($filename, $data);//覆盖的模式。如果文件不存在，则创建文件
        if($result){
            //存在tag的时候才会考虑是否更新tag缓存内容
            isset($first) && $this->setTagItem($filename);//这里面存放的已经存在的文件名。一次性使用
            clearstatcache();//清除缓存文件内容
            return true;
        }else{
            return false;
        }

    }

    /**
     *缓存变量值自增
     * @param $name
     * @param int $step
     * @return bool|int|mixed|string
     */
    public function inc($name, $step = 1){
        if($this->has($name)){
           //如果存在则自增
            $value = $this->get($name) + $step;
            $expire = $this->expire;//使用原来的

        }else{
            //不存在则设置为单步长
            $value = $step;
            $expire = 0;

        }
        return $this->set($name, $value, $expire) ? $value : false;
    }

    /**
     * 缓存变量值自减
     * @param $name
     * @param int $step
     * @return bool|int|mixed|string
     */
    public function dec($name, $step = 1){

        if($this->has($name)){
            $value = $this->get($name) - $step;
            $expire = $this->expire;
        }else{
            $value = -$step;
            $expire = 0;
        }
        return $this->set($name, $value, $expire) ? $value : false;

    }

    /**
     * 删除缓存变量就是删除缓存文件
     * @param $name
     * @return bool
     */
    public function rm($name){
        $filename = $this->getCacheKey($name);
        try{
            return $this->unlink($filename);
        } catch (\Exception $e){

        }
    }

    /**
     * 删除缓存文件，先判别是否存在文件
     * @param $path
     * @return bool
     */
    public function unlink($path){
        return is_file($path) && unlink($path);
    }


    /**
     * 清空缓存,可以单独清除指定的tag下的缓存
     * @param null $tag
     * @return bool
     */
    public function clear($tag = null){

        if($tag){
            //指定了标签
            //获取该标签里的缓存变量key
            $keys = $this->getTagItem($tag);//返回的是数组,里面都是文件名
            $this->tag = null;//获取完之后，自行清除
            foreach($keys as $key){
                $this->unlink($key);
            }
            //删除该标签的缓存内容
            $this->rm('tag_' . md5($tag));
            return true;
        }

        //匹配文件.指定路径下的所有文件，若指定了前缀则加入前缀
        $files = (array) glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . DS : '') . '*');
        foreach($files as $path){
            if(is_dir($path)){
                $matches = glob($path . '/*php');//只能往下一层
                if(is_array($matches)){
                   array('unlink', $matches);
                }
                rmdir($path);

            }else{
                //这个地方已经确定存在了，则不需要用封装的unlink了
                unlink($path);
            }
        }

        //清除缓存成功
        return true;
    }

















    //获取类
    /**
     * 取得变量的存储文件名
     * @param $name
     * @param bool|false $auto
     * @return string
     */
    public function getCacheKey($name, $auto = false){
        $name = md5($name);
        if($this->options['cache_subdir']){
            $name = substr($name, 0, 2) . DS .substr($name, 2);
        }

        if($this->options['prefix']){
            //前缀若存在则单独为一个目录
            $name = $this->options['prefix'] . DS . $name;
        }

        $filename = $this->options['path'] . $name . '.php';//一个变量名一个文件
        $dir = dirname($filename);

        if($auto && !is_dir($dir)){
            mkdir($dir, 0755, true);
        }

        return $filename;

    }


    /**
     * 获取缓存变量文件的内容
     * 没有或者失败返回false
     * @param $name
     * @param bool|false $default
     * @return bool|mixed|string
     */
    public function get($name, $default = false){
        $filename = $this->getCacheKey($name);
        if(!is_file($filename)){
            return $default;
        }

        $content = file_get_contents($filename);//读取缓存内容

        $this->expire = null;

        if(false !== $content){
            //读取成功,验证有效期
            $expire = (int) substr($content, 8,12);
            if(0 !=$expire && time() > filemtime($filename) + $expire){
                return $default;//过了有效期
            }

            //没有过有效期
            $this->expire = $expire;//重置当前有效期期。留给后来的自己用
            $content = substr($content, 32);
            if($this->options['data_compress'] && function_exists('gzcompress')){
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;

        }else{

            //读取失败
            return false;
        }

    }

    /**
     * 判断缓存变量是否存在
     * 只能通过获取文件内容来。内容为空也是不存在变量。
     * @param $name
     * @return bool
     */
    public function has($name){
        //用get获取到的值，失败或者没有或者内容为假。
        return $this->get($name) ? true : false;
    }


}
