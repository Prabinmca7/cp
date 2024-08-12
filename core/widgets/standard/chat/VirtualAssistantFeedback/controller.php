<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class VirtualAssistantFeedback extends \RightNow\Widgets\AnswerFeedback {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() 
    {
        if (Config::getConfig(MOD_VA_ENABLED) !== true)
        {
            $this->reportError(Config::getMessage(MOD_VAENABLED_CFG_ENABLED_WIDGET_MSG), false);
            return false;
        }
        
        $this->data['js']['buttonView'] = ($this->data['attrs']['options_count'] === 2);
        $this->data['rateLabels'] = $this->getRateLabels();

        $this->classList->add('rn_Hidden');

    }
}
