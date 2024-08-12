<?php

namespace RightNow\Helpers;

/**
 * Common URL manipulation functions
 */
class UrlHelper {
    /**
     * Returns the value of the 'user' url parameter
     * @return string|null The value of the 'user' url parameter or null.
     */
    static function userFromUrl() {
        return \RightNow\Utils\Url::getParameter('user');
    }
}
