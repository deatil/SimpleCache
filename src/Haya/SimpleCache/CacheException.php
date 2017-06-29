<?php

namespace Haya\SimpleCache;

use Exception;

/**
 * Interface used for all types of exceptions thrown by the implementing library.
 * 
 * @create 2017-6-3
 * @author deatil
 */
class CacheException extends Exception
{
	
    /**
     * 获取错误名称
     *
     * @create 2017-6-3
     * @author deatil
     */
    public function getName()
    {
        return 'SimpleCache Error.';
    }

}
