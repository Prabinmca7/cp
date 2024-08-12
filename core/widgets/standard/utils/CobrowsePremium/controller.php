<?php
namespace RightNow\Widgets;
use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\ActionCapture;

class CobrowsePremium extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if (Config::getConfig(MOD_COBROWSE_ENABLED) ||
            !Config::getConfig(MOD_COBROWSE_PREMIUM_ENABLED))
        {
            echo $this->reportError(Config::getMessage(WDGET_MOD_COBROWSEENABLED_CFG_MSG), false);
            return false;
        }

        $launcherScript = Config::getConfig(COBROWSE_PREMIUM_LAUNCHER_SCRIPT);
        if(Text::isValidUrl($launcherScript))
        {
            $this->loadJavaScriptResource($launcherScript);
            ActionCapture::record('cobrowsePremium', 'include');
        }
        else
        {
            echo $this->reportError(Config::getMessage(COBROWSE_PREMIUM_LAUNCHERSCRIPT_MSG), false);
            return false;
        }
    }
}
