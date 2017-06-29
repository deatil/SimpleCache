<?php

namespace Haya\SimpleCache\Driver;

use Exception;

/**
 * 文件缓存
 * 
 * @create 2017-6-8
 * @author deatil
 */
class File
{
    
    /**
     * 配置
     * 
     * @create 2017-6-8
     * @author deatil
     */
    protected $options = [
        // 缓存时间
        'expire'    => 0,
        
        // 数据缓存是否压缩缓存
        'compress'  => false,
        // 数据缓存是否校验缓存
        'check'     => false,
        // 缓存路径设置 
        'temp'      => 'apps/runtime/cache/',
        // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
        'subdir'    => false,
        // 子目录缓存级别
        'level'     => 1,
    ];
    
    /**
     * 构造函数
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function __construct($options = []) 
    {
        $this->options = array_merge($this->options, $options);
        
        $this->checkTempPath();
    }
    
    /**
     * 检测缓存目录
     * 
     * @create 2017-6-8
     * @author deatil
     */
    private function checkTempPath() 
    {
        if (substr($this->options['temp'], -1) != '/') {
            $this->options['temp'] .= '/';
        } 
        
        // 创建应用缓存目录
        if (!is_dir($this->options['temp'])) {
            if (!mkdir($this->options['temp'])) {
                throw new Exception('缓存地址('.$this->options['temp'].')没有写入权限！');
            }
        }
    }
    
    /**
     * 获取文件完整地址
     * 
     * @create 2017-6-8
     * @author deatil
     */
    private function filename($name) 
    {
        $name   =   md5($name);
        if($this->options['subdir']) {
            // 使用子目录
            $dir   ='';
            for ($i = 0; $i < $this->options['level']; $i++) {
                $dir    .=  $name{$i}.'/';
            }
            if (!is_dir($this->options['temp'].$dir)) {
                mkdir($this->options['temp'].$dir, 0755, true);
            }
            $filename   =   $dir.$name.'.php';
        }else{
            $filename   =   $name.'.php';
        }
        return $this->options['temp'].$filename;
    }
    
    /**
     * 获取
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function get($name) 
    {
        $filename   =   $this->filename($name);
        if (!is_file($filename)) {
           return false;
        }

        $content    =   file_get_contents($filename);
        if( false !== $content) {
            $expire  =  (int)substr($content, 8, 12);
            if ($expire != 0 && time() > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                unlink($filename);
                return false;
            }
            if ($this->options['check']) {//开启数据校验
                $check     =  substr($content, 20, 32);
                $content   =  substr($content, 52, -3);
                if ($check != md5($content)) {//校验错误
                    return false;
                }
            } else {
                $content   =  substr($content, 20, -3);
            }
            if ($this->options['compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $content   =  gzuncompress($content);
            }
            return $content;
        }
        else {
            return false;
        }
    }
    
    /**
     * 设置
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function set($name, $value, $expire=null) 
    {
        if (is_null($expire)) {
            $expire =  $this->options['expire'];
        }
        $filename   =   $this->filename($name);
        $data       =   $value;
        if ( $this->options['compress'] && function_exists('gzcompress')) {
            //数据压缩
            $data   =   gzcompress($data, 3);
        }
        if ($this->options['check']) {//开启数据校验
            $check  =  md5($data);
        } else {
            $check  =  '';
        }
        $data    = "<?php\n//".sprintf('%012d',$expire).$check.$data."\n?>";
        $result  =   file_put_contents($filename, $data);
        if ($result) {
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function delete($name) 
    {
        return unlink($this->filename($name));
    }
    
    /**
     * 清空
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function clear() 
    {
        $path   =  $this->options['temp'];
        $files  =  scandir($path);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_dir($path.$file) ){
                    array_map( 'unlink', glob( $path.$file.'/*.*' ) );
                } elseif (is_file($path.$file)) {
                    unlink( $path . $file );
                }
            }
            return true;
        }

        return false;
    }
    
}
