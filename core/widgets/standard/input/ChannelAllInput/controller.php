<?php
namespace RightNow\Widgets;

class ChannelAllInput extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!$visibleChannels = $this->CI->model('Contact')->getChannelTypes()->result) {
            return false;
        }
        $fieldAliases = \RightNow\Utils\Connect::getArrayFieldAliases();
        $friendlyFieldNames = array_flip($fieldAliases['ChannelUsernameArray']);

        foreach($visibleChannels as $ID => $channel) {
            if($friendlyName = $friendlyFieldNames[$ID]) {
                $this->data['fields'][$friendlyName] = $channel['LookupName'];
            }
        }
    }
}
