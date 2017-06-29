<?php

namespace Haya\SimpleCache;

/**
 * Exception interface for invalid cache arguments.
 *
 * When an invalid argument is passed it must throw an exception which implements
 * this interface
 */
class InvalidArgumentException extends CacheException
{
	
    /**
     * 获取错误名称
     * 
     * @create 2017-6-3
     * @author deatil
     */
    public function getName()
    {
        return 'Invalid SimpleCache Error';
    }

}
