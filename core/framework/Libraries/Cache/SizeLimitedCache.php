<?php
namespace RightNow\Libraries\Cache;

/**
 * Provides a cache which has a maximum size on the amount of content that can be stored. The size is
 * computed by the string length of the items stored. For complex types such as arrays or objects, the
 * size is computed after serializing the value. Once the max size has been reached, items at the beginning
 * of the cache are forcefully expired.
 */
class SizeLimitedCache extends ReadThroughCache {
    protected $maxSize;
    protected $size = 0;

    /**
     * Constructor
     *
     * @param int $maxSize Maximum size of the cache
     * @param \Closure|string $valueCalculationCallback Method to use to calculate the value of the cache
     */
    public function __construct($maxSize, $valueCalculationCallback) {
        parent::__construct($valueCalculationCallback);
        $this->maxSize = $maxSize;
    }

    /**
     * Sets a value within the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     *
     * @return mixed The value set in the cache
     */
    protected function set($key, $value) {
        $value = parent::set($key, $value);
        $valueLength = $this->getDataSize($value);
        $this->size += $valueLength;
        if ($valueLength > $this->maxSize) {
            $this->expire($key);
        }
        else {
            while ($this->size > $this->maxSize && count($this->cache) > 0) {
                $this->expire(key($this->cache));
            }
        }
        return $value;
    }

    /**
     * Expires a specific item in the cache.
     *
     * @param string $key Cache key
     * @param string $valueLength Length of item in order to reduce current cache size.
     * @return void
     * @internal
     */
    protected function expireInternal($key, $valueLength) {
        $this->size -= $valueLength;
        unset($this->cache[$key]);
    }

    /**
     * Remove a specific item from the cache
     *
     * @param string $key Key to expire
     * @return void
     */
    public function expire($key) {
        if (array_key_exists($key, $this->cache)) {
            $this->expireInternal($key, $this->getDataSize($this->cache[$key]));
        }
    }

    /**
     * Clear everything from the cache
     * @return void
     */
    public function clear() {
        parent::clear();
        $this->size = 0;
    }

    /**
     * Calculates the size (in terms of string length) of the value passed in.
     *
     * @param mixed $value Value to be set in the cache
     * @return int Size of value
     */
    protected function getDataSize($value) {
        if (is_string($value)) {
            return strlen($value);
        }
        if (is_array($value) || is_object($value)) {
            return strlen(serialize($value));
        }
        return 16; // Arbitrary generous guess for the size of a numeric type.
    }
}