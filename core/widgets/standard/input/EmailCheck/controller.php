<?php
namespace RightNow\Widgets;

class EmailCheck extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['js']['contactToken'] = \RightNow\Utils\Framework::createTokenWithExpiration(1);
        $this->data['initialValue'] = $this->CI->session->getSessionData('previouslySeenEmail') ?: '';
        $this->data['isIE'] = $this->CI->agent->browser() === 'Internet Explorer';
        $this->data['attrs']['add_params_to_url'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
    }
}
