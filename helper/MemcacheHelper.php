<?php

namespace Helper;

use Exception;

/**
 * Simple helper class atop of Memcache
 */
class MemcacheHelper
{

	static $mem_host;
	static $mem_port;
	static $expiry;

	/**
	 * Connect to the memcache server
	 * @return \Memcache|bool the Memcache class
	 */
    private static function connect(): bool|\Memcache {
        $memcache = new \Memcache();
        if (! $memcache->connect(self::$mem_host, self::$mem_port)) {
            syslog(LOG_ERR, 'Could not connect to memcache server');
			return false;
        }

        return $memcache;
    }

	public static function Initialise($memcache_host, $memcache_port, $memcache_expiry): void {
		self::$mem_host = $memcache_host;
		self::$mem_port = $memcache_port;
		self::$expiry = $memcache_expiry;
	}

    /**
     * Write-through caching
     * @param  string        $key         ID of the object to find in memcache
     * @param  callable     $callable   function to call if the object is not found
     * @param mixed|null $param     parameter to pass to the caching callback
     * @return                   object        cached result
     */
    public static function cache(string $key, callable $callable, mixed ...$param) : mixed
    {
        $memcache = self::connect();

		if (!$memcache){
			return call_user_func($callable, $param);
		} else if ($memcache->get($key)) { //get the key if possible
			return $memcache->get($key);
        }

        // Get result, store in memcache and return
        $result = call_user_func($callable, $param);
        $memcache->set($key, $result, null, self::$expiry);
        return $result;
    }

    /**
     * Flushes the memcache server
     * @return boolean indicating success
     */
    public static function flush()
    {
        $memcache = self::connect();

		if (!$memcache) return false;
        // Flush all memcache objects
        return $memcache->flush();
    }
}
