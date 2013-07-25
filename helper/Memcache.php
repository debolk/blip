<?php

namespace Helper;

/**
 * Simple helper class atop of Memcache
 */
class Memcache
{
  /**
   * Write-through caching
   * @param  string $key        ID of the object to find in memcache
   * @param  callable $callable function to call if the object is not found
   * @return object             cached result
   */
  public function cache($key, callable $callable)
  {
    // Connect to memcache
    $memcache = new \Memcache();
    if (! $memcache->connect(getenv('MEMCACHE_HOST'), getenv('MEMCACHE_PORT'))) {
      throw new Exception('Could not connect to memcache server');
    }

    // Return the cached entry if we can
    if ($memcache->get($key)) {
      return $memcache->get($key);
    }

    // Get result, store in memcache and return
    $result = $callable();
    $memcache->set($key, $result);
    return $result;
  }

  //FIXME Some way to invalidate the cache
}
