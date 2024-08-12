<?php
namespace RightNow\Internal\Libraries\Cache;

use RightNow\Internal\Api;

/**
 * A class for setting appropriate Cache-Control headers on pages.
 */
class CacheHeaders {
    private static $defaultProps = array(
        "EnableCache" => "0",
        "PageRegex" => array("#^(?:/|/app|/app/home)$#", "#^/app/answers#"),
        "PrivateMaxAge" => "180",
        "MemCacheTTL" => "60"
    );

    /**
    * Adds the appropriate headers to cache or not cache the requested page.
    *
    * @param string $requestUri The requested uri.
    * @param string $jsonPath The path to the cacheControl.json file.
    */
    public static function sendHeaders($requestUri, $jsonPath) {
        $json = self::_parseJson($jsonPath);
        $noCacheArray = array('Cache-Control: no-cache, no-store', "Expires: -1", "Pragma: no-cache");

        //Default to having cache disabled
        $enableCache = isset($json["EnableCache"]) ? $json["EnableCache"] : self::$defaultProps["EnableCache"];

        if ($enableCache === "1") {
            $pageRegex = $json["PageRegex"] ?: array();
            $matchFound = false;

            $maxAge  = $json['PrivateMaxAge'];
            // use browser cache with maxAge duration, and revalidate with server
            // if stale.
            $cacheControl = "private, max-age=$maxAge, must-revalidate";
            // get the expiresTime maxAge seconds in the future.
            $expiresTime  = date('D, d M Y H:i:s T', time() + $maxAge);
            // generate md5 hash to use in Etag.
            $etagHash = md5($requestUri);
            //\RightNow\Utils\Framework::logMessage("requestUri => $requestUri, etagHash => $etagHash, "
            //. "cacheControl => $cacheControl, expires => $expiresTime");

            foreach ($pageRegex as $regex) {
                if (preg_match($regex, $requestUri)) {
                    $matchFound = true;
                    // Passing 3 Cache related headers - Cache-Control, ETag and Expires.
                    // ETag and Expires were passed to make this work with IE.
                    return array(
                        "Cache-Control: $cacheControl",
                        "ETag: \"$etagHash\"",
                        "Expires: $expiresTime",
                    );
                }
            }

            if (!$matchFound) {
                return $noCacheArray;
            }
        }
        else {
            return $noCacheArray;
        }
    }

    /**
    * Parses CacheControl.json from memcache or file system.
    *
    * @param string $jsonPath The path to the cacheControl.json file.
    * @throws \Exception If we couldn't find file in memcache or file system.
    * @return json Array with cache settings defined.
    */
    private static function _parseJson($jsonPath) {
        //Try to fetch from memcache
        $memCacheKey = "CacheControl";
        $memCacheHandle = Api::memcache_value_deferred_get(1, array($memCacheKey));
        $json = Api::memcache_value_fetch(1, $memCacheHandle);

        //If json not in memcache
        if (!$json || $json[$memCacheKey] === "") {
            //Grab the file
            try {
                if (!is_readable($jsonPath)) {
                    throw new \Exception();
                }
                else {
                    $json = file_get_contents($jsonPath);
                    $json = json_decode($json, true);
                    //If file is blank or not correct throw exception
                    if (!$json || !isset($json["EnableCache"])) {
                        throw new \Exception();
                    }
                    //Ensure the regular expressions for pages are added
                    $json["PageRegex"] = isset($json["PageRegex"]) ? $json["PageRegex"] : self::$defaultProps["PageRegex"];
                }
            }
            //Catch exception and set default values
            catch (\Exception $e) {
                foreach (array_keys(self::$defaultProps) as $key) {
                    $json[$key] = isset($json[$key]) ? $json[$key] : self::$defaultProps[$key];
                }
            }
            //Store in memcache
            $memCacheTtl = isset($json["MemCacheTTL"]) ? $json["MemCacheTTL"] : self::$defaultProps["MemCacheTTL"];
            Api::memcache_value_set(1, $memCacheKey, json_encode($json), $memCacheTtl);
        }
        //If json came from memcache grab cache settings using memcache key
        else {
            $json = json_decode($json[$memCacheKey], true);
        }
        return $json;
    }
}
