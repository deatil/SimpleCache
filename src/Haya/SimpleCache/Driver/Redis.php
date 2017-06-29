<?php

namespace Haya\SimpleCache\Driver;

use Exception;

/**
 * Redis 缓存驱动
 * 
 * @create 2017-6-8
 * @author deatil
 */
class Redis
{
    
    /**
     * 缓存句柄
     * 
     * @create 2017-6-8
     * @author deatil
     */
    protected $handler;

    /**
     * 配置
     * 
     * @create 2017-6-8
     * @author deatil
     */
    protected $options = [
        'host'          => '127.0.0.1',
        'port'          => '6379',
        'timeout'       => false,
        'persistent'    => false,

        // 过期时间
        'expire'        => 0,
    ];
    
    /**
     * 构造函数
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function __construct($options = array()) 
    {
        $this->options =  array_merge($this->options, $options);
        $this->connect();
    }

    /**
     * 获取
     * 
     * @create 2017-6-8
     * @author deatil
     */
    private function connect() 
    {
        if ( !extension_loaded('redis') ) {
            throw new Exception("缓存驱动(redis)不存在！");
        }

        $func = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new Redis;
        $this->options['timeout'] === false ?
            $this->handler->$func($this->options['host'], $this->options['port']) :
            $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);
    }

    /**
     * 获取
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function get($name) 
    {
        return $this->handler->get($name);
    }

    /**
     * 设置
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function set($name, $value, $expire = null) 
    {
        if (is_null($expire)) {
            $expire  =  $this->options['expire'];
        }

        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }

        return $result;
    }

    /**
     * 删除
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function delete($name) 
    {
        return $this->handler->delete($name);
    }

    /**
     * 清空
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function clear() 
    {
        return $this->handler->flushDB();
    }

}
