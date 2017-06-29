<?php

namespace Haya\SimpleCache;

use Psr\SimpleCache\CacheInterface;

/**
 * 简略缓存
 * 
 * @create 2017-6-3
 * @author deatil
 */
class Cache implements CacheInterface
{

    /**
     * 当前实例对象
     * 
     * @create 2017-6-3
     * @author deatil
     */
    protected $handler = null;

    /**
     * 数据缓存驱动
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public $driver = 'Haya\\SimpleCache\\Driver\\File';

    /**
     * 数据缓存有效期 0表示永久缓存
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public $ttl = 0;
    
    /**
     * 配置
     * 
     * @create 2017-6-3
     * @author deatil
     */
    public $config = [];
    
    /**
     * 构造函数
     * 
     * @create 2017-6-23
     * @author deatil
     */
    public function __construct($config = [], $driver = null, $ttl = null)
    {
        if (!empty($config) && is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        if (!is_null($driver)) {
            $this->driver = $driver;
        }
        if (!is_null($ttl)) {
            $this->ttl = $ttl;
        }
    }

    /**
     * 静态魔术方法
     * 
     * @create 2016-4-7
     * @author deatil
     */
    public function __call($method, $args)
    {
        if ( method_exists($this->handler, $method) ) {
            return call_user_func_array([$this->handler, $method], $args);
        }

        throw new InvalidArgumentException(__CLASS__ . "：{$method}方法没有定义！");
    }
    
    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("{$key} is not a legal value.");
        }
        
        $data = $this->connect()->get($key);
        $data = unserialize($data);
        if (empty($data)) {
            $data = $default;
        }
        
        return $data;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                $key   The key of the item to store.
     * @param mixed                 $value The value of the item to store, must be serializable.
     * @param null|int|DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function set($key, $value, $ttl = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("{$key} is not a legal value.");
        }

        $value = serialize($value);
        
        if (is_null($ttl)) {
            $ttl = $this->ttl;
        }
        
        return $this->connect()->set($key, $value, $ttl);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function delete($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("{$key} is not a legal value.");
        }
        
        return $this->connect()->delete($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function clear()
    {
        return $this->connect()->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException("{$key} is neither an array nor a Traversable.");
        }
        
        if (empty($keys) || !is_array($keys)) {
            return [];
        }
        
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        
        return $data;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable              $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException("{$key} is neither an array nor a Traversable.");
        }
        
        if (empty($values) || !is_array($values)) {
            return false;
        }
        
        foreach ($values as $key => $value) {
            $status = $this->set($key, $value, $ttl);
            if ($status == false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function deleteMultiple($keys)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException("{$key} is neither an array nor a Traversable.");
        }
        
        if (empty($keys) || !is_array($keys)) {
            return false;
        }
        
        foreach ($keys as $key) {
            $status = $this->delete($key);
            if ($status == false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     * 
     * @create 2017-6-8
     * @author deatil
     */
    public function has($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("{$key} is not a legal value.");
        }
        
        $data = $this->get($key);
        
        if (empty($data)) {
            return false;
        }
        
        return true;
    }

    /**
     * 设置配置信息
     * 
     * @create 2017-6-23
     * @author deatil
     */
    public function withConfig($key, $value = null) 
    {
        $new = clone $this;
        if (is_array($key)) {
            $new->config = array_merge($new->config, $key);
        } else {
            $new->config[$key] = $value;
        }
        $new->connect(true);
        return $new;
    }   

    /**
     * 设置驱动
     * 
     * @create 2017-6-23
     * @author deatil
     */
    public function withDriver($driver) 
    {
        $new = clone $this;
        $new->driver = $driver;
        $new->connect(true);
        return $new;
    }   

    /**
     * 链接
     * 
     * @create 2016-4-7
     * @author deatil
     */
    protected function connect($isRefresh = false) 
    {
        if ($isRefresh || !is_object($this->handler)) {
            $this->handler = new $this->driver($this->config);
        }
        return $this->handler;
    }   
}
