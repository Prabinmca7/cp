<?php
namespace RightNow\Widgets;

class ChatLaunchButton extends FormSubmit
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        parent::getData();
        
        $this->data['js']['chatWindowName'] = \RightNow\Utils\Config::getConfig(DB_NAME) . \RightNow\Api::intf_id();
        if (isset($this->attrs['protocol_selection']) && $this->attrs['protocol_selection']->value === 'auto')
            $this->data['js']['baseUrl'] = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage');
        else
        {
            $protocolValue = '';
            if (!empty($this->attrs['protocol_selection']) && isset($this->attrs['protocol_selection']->value)) {
                $protocolValue = $this->attrs['protocol_selection']->value;
            }
            $this->data['js']['baseUrl'] = 'http' . ($protocolValue === 'ssl' ? 's' : '') . '://' . \RightNow\Utils\Config::getConfig(OE_WEB_SERVER);
        }
        //determine if we are within chat hours; we will only show the form if we 
        //are within chat hours
        $chatHours = $this->CI->model('Chat')->getChatHours()->result;
        $this->data['js']['isBrowserSupported'] = $this->CI->model('Chat')->isBrowserSupported();
        $this->data['js']['showForm'] = $chatHours['inWorkHours'] && !$chatHours['holiday'] && $this->data['js']['isBrowserSupported'];
    }
}
