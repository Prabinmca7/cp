<?php

namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\ActionCapture;

class ChatVideoChat extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!Config::getConfig(MOD_VIDEO_CHAT_ENABLED) || !(Config::getConfig(MOD_CHAT_ENABLED) || Config::getConfig(MOD_ENGAGEMENT_CHANNELS_ENABLED))) {
            echo $this->reportError(Config::getMessage(MODVIDEOCHAT_MODCHAT_CONFIG_ENABLED_MSG), false);
            return false;
        }

        $this->data['js']['interfaceID'] = \RightNow\Api::intf_id();
    }
}
