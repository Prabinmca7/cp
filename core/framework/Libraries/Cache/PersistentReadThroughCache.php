<?php
namespace RightNow\Libraries\Cache;

use RightNow\Api;

/**
 * A read-through cache which writes to memcache.
 */
class PersistentReadThroughCache extends ReadThroughCache {
    /**
     * Official memcache key limit is 250; C API prefixes everything w/ site + define + etc. up to SITE_PREFIX_LEN which is 22; remove an extra one to prevent exceptions
     */
    const MAX_MEMCACHE_KEY_LENGTH = 227;
    /**
     * Length of md5 32 + space (down below)
     */
    const MD5_LENGTH = 33;

    protected static $modePrefixKey;
    private $cacheTimeInSeconds;

    /**
     * Constructor.
     * @param int $cacheTimeInSeconds How long the value is to be cached.
     * @param \Closure|null $valueCalculationCallback If the requested key is not in the cache, this function is called to calculate the value. It
     * is passed all of the arguments that are passed to get().
     */
    public function __construct($cacheTimeInSeconds, $valueCalculationCallback = null) {
        self::$modePrefixKey || $this->setModePrefixKey();
        $this->cacheTimeInSeconds = $cacheTimeInSeconds;
        parent::__construct($valueCalculationCallback);
    }

    /**
     * Returns the value returned from valueCalculationCallback the first time and the cached
     * value subsequent times. May be passed any number of arguments in addition to key.
     * @param string $key The memcache key
     * @return mixed The value returned from valueCalculationCallback
     * @throws \Exception If there's a problem communicating with memcached or a problem getting or storing the value
     */
    public function get($key) {
        if(!function_exists('memcache_value_deferred_get')){
            throw new \Exception("Memcache cannot currently be used.");
        }
        $value = $this->check($key);
        if ($value !== null && $value !== "") {
            return @unserialize($value);
        }
        if ($this->valueCalculationCallback) {
            return $this->set($key, call_user_func_array($this->valueCalculationCallback, func_get_args()));
        }
    }

    /**
     * Expires the value associated the key in memcache
     * @param string $key Memcache key
     * @return void
     * @throws \Exception If there's a problem communicating with memcached or a problem deleting the value
     */
    public function expire($key) {
        Api::memcache_value_delete(MEMCACHE_TYPE_CP_GENERIC, $this->getMemcacheKey($key));
    }

    /**
     * Sets the value for the key in memcache.
     * @param string $key Memcache key
     * @param mixed $value Value to store; will be serialized
     * @return mixed Value of $value passed in
     * @throws \Exception If there's a problem communicating with memcached or a problem storing the value
     */
    protected function set($key, $value) {
        Api::memcache_value_set(MEMCACHE_TYPE_CP_GENERIC, $this->getMemcacheKey($key), serialize($value), $this->cacheTimeInSeconds);
        return $value;
    }

    /**
     * Returns the value associated with the key in memcache (if any).
     * @param string $key Memcache key
     * @return string|null String the raw, serialized value or null if not found
     */
    protected function check($key) {
        $memcacheKey = $this->getMemcacheKey($key);
        try {
            $result = Api::memcache_value_fetch(MEMCACHE_TYPE_CP_GENERIC, Api::memcache_value_deferred_get(MEMCACHE_TYPE_CP_GENERIC, array($memcacheKey)));
        }
        catch (\Exception $e) {
            return;
        }
        return $result[$memcacheKey];
    }

    /**
     * Returns a key that properly conforms with memcache's key length restrictions.
     * @param string $key The intended key
     * @return string Value of $key passed in
     */
    private function getMemcacheKey($key) {
        $key = self::$modePrefixKey . '-' . $key;
        if (strlen($key) > self::MAX_MEMCACHE_KEY_LENGTH) {
            return substr($key, 0, self::MAX_MEMCACHE_KEY_LENGTH - self::MD5_LENGTH) . ' ' . md5($key);
        }
        return $key;
    }

    /**
     * Sets the mode prefix key for all subsequent requests to memcache.
     * @param string $mode Override mode to use, purely for testing purposes.
     * @return void
     */
    private function setModePrefixKey($mode = null) {
        if (IS_TARBALL_DEPLOY || get_class(get_instance()) === 'RightNow\Controllers\Admin\Deploy' || $mode === 'deploy') {
            // break the cache during a deploy
            $prefixKey = time();
        }
        else if (IS_OPTIMIZED || $mode === 'optimized') {
            if ($timestamp = (\RightNow\Utils\FileSystem::getLastDeployTimestampFromFile() ?: \RightNow\Utils\FileSystem::getLastDeployTimestampFromDir())) {
                // use the 'current' cache in optimized
                $prefixKey = $timestamp;
            }
            else {
                // break the cache if we can't get a timestamp value
                $prefixKey = time();
            }
        }
        else {
            // keep using the cache in dev, admin, and reference cases
            $prefixKey = 'DEV';
        }
        self::$modePrefixKey = $prefixKey;
    }
}

/**
* Provides a traditional memcache interface to simply stash and grab items;
* protects against key length out-of-bounds errors.
*/
class Memcache extends PersistentReadThroughCache {
    /**
     * Calls into PersistentReadThroughCache's protected set method.
     * @param string $key Key to use within memcache
     * @param string $value Value to store in memcache
     * @return mixed Result of setting value in memcache
     * @throws \Exception If there's a problem communicating with memcached or a problem storing the value
     */
    function set($key, $value) {
        return parent::set($key, $value);
    }

    /**
     * Calls into PersistentReadThroughCache's protected check method.
     * @param string $key Key to retrieve
     * @return mixed Null if not found otherwise the stashed value
     */
    function get($key) {
        $result = parent::check($key);
        if ($result !== null && $result !== '') {
            return @unserialize($result);
        }
        return false;
    }
}