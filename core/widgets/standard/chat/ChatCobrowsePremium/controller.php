<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\ActionCapture;

class ChatCobrowsePremium extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (Config::getConfig(MOD_COBROWSE_ENABLED) ||
            !Config::getConfig(MOD_COBROWSE_PREMIUM_ENABLED) ||
            !(Config::getConfig(MOD_ENGAGEMENT_CHANNELS_ENABLED) ||
            Config::getConfig(MOD_CHAT_ENABLED)))
        {
            echo $this->reportError(Config::getMessage(WIDGET_MOD_COBROWSEENABLED_CFG_MSG), false);
            return false;
        }

        $launcherScript = Config::getConfig(COBROWSE_URL).'/'.Config::getConfig(COBROWSE_PREMIUM_CHAT_LAUNCHER_SCRIPT).'?api_key='.Config::getConfig(COBROWSE_SITE_ID);
        if(Text::isValidUrl($launcherScript))
        {
            $this->addJavaScriptInclude($launcherScript);
            ActionCapture::record('chatCobrowsePremium', 'include');
        }
        else
        {
            echo $this->reportError(Config::getMessage(COBROWSE_PREMIUM_CHAT_LAUNCHER_MSG), false);
            return false;
        }
    }
}
