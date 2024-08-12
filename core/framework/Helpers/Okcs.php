<?php

namespace RightNow\Helpers;

use RightNow\Utils\Config;

/**
 * Common functions for use with Okcs widgets
 */
class OkcsHelper {

    /**
     * Verify if OKCS_ENABLED flag is enabled or not.
     * Throw an error if disabled
     * @param string $path Path
     * @param \RightNow\Libraries\Widget\Base $widget Widget Instance
     * @return boolean OKCS Enabled
     */
    function checkOkcsEnabledFlag($path, \RightNow\Libraries\Widget\Base $widget) {
        if(!Config::getConfig(OKCS_ENABLED)) {
            $widget->setPath($path);
            echo $widget->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        return true;
    }
}
