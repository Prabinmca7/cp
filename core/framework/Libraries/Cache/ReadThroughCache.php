<?php
namespace RightNow\Libraries\Cache;

/**
 * A class for in-process (i.e. short-lived) read through caching. The constructor takes the method to use to
 * calculate the value for the cache, which will only be executed once per unique key.
 */
class ReadThroughCache {
    protected $cache = array();
    protected $valueCalculationCallback;

    /**
     * Constructor.
     * @param \Closure|string $valueCalculationCallback If the requested key is not in the cache, this function is called to calculate the value. It
     * is passed all of the arguments that are passed to get().
     */
    public function __construct($valueCalculationCallback) {
        $this->valueCalculationCallback = $valueCalculationCallback;
    }

    /**
     * Retrieves an item out of the cache Note: If the valueCalculationCallback requires arguments beyond $key, they can be sent to get() below.
     * @param string|Object $key The key to use for the lookup
     * @throws \Exception If $key is an object it must implement a __toString method, or an exception will be thrown.
     * @return mixed The value int he cache.
     */
    public function get($key)  {
        $args = func_get_args();
        if (is_object($key)) {
            if (method_exists($key, "__toString"))
                $key = sprintf("%s", $key);
            else
                throw new \Exception("An object key is only valid if it implements the __toString function.");
        }
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $this->set($key, call_user_func_array($this->valueCalculationCallback, $args));
    }

    /**
     * Sets an item in the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store in the cache.
     * @return mixed Result of setting value in cache
     */
    protected function set($key, $value) {
        return $this->cache[$key] = $value;
    }

    /**
     * Remove a specific item from the cache
     * @param string $key Key to expire
     * @return void
     */
    public function expire($key) {
        if (array_key_exists($key, $this->cache)) {
            unset($this->cache[$key]);
        }
    }

    /**
     * Clear everything from the cache
     * @return void
     */
    public function clear() {
        $this->cache = array();
    }

    /**
     * Returns the size of items within the cache.
     * @return int Size of the cache
     */
    public function count() {
        return is_array($this->cache) ? count($this->cache) : 0;
    }
}