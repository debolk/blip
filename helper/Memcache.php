<?php

namespace Helper;

/**
 * Simple helper class atop of Memcache
 */
class Memcache
{
  /**
   * Connect to the memcache server
   * @return Memcache the Memcache class
   */
  private static function connect()
  {
    $memcache = new \Memcache();
    if (! $memcache->connect(getenv('MEMCACHE_HOST'), getenv('MEMCACHE_PORT'))) {
      throw new Exception('Could not connect to memcache server');
    }

    return $memcache;
  }

  /**
   * Write-through caching
   * @param  string $key        ID of the object to find in memcache
   * @param  callable $callable function to call if the object is not found
   * @return object             cached result
   */
  public function cache($key, callable $callable)
  {
    $memcache = self::connect();

    // Return the cached entry if we can
    if ($memcache->get($key)) {
      return $memcache->get($key);
    }

    // Get result, store in memcache and return
    $result = $callable();
    $memcache->set($key, $result);
    return $result;
  }

  /**
   * Flushes the memcache server
   * @return boolean indicating success
   */
  public function flush()
  {
    $memcache = self::connect();

    // Flush all memcache objects
    return $memcache->flush();
  }
}
