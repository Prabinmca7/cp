<?php

namespace RightNow\Widgets;

class LoginForm extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(\RightNow\Utils\Framework::isLoggedIn())
            return false;
        \RightNow\Utils\Url::redirectIfPageNeedsToBeSecure();
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        if(\RightNow\Utils\Url::getParameter('redirect'))
        {
            //We need to check if the redirect location is a fully qualified URL, or just a relative one
            $redirectLocation = urldecode(urldecode(\RightNow\Utils\Url::getParameter('redirect')));
            
            //Only set if redirect host in CP_REDIRECT_HOSTS config
            $checkRedirect = \RightNow\Utils\Url::isRedirectAllowedForHost($redirectLocation);
            if($checkRedirect === true) {
                $parsedURL = parse_url($redirectLocation);
            }
            
            if((isset($parsedURL['scheme']) && $parsedURL['scheme']) || (\RightNow\Utils\Text::beginsWith($parsedURL['path'], '/ci/') || \RightNow\Utils\Text::beginsWith($parsedURL['path'], '/cc/')))
            {
                $this->data['js']['redirectOverride'] = $redirectLocation;
            }
            else
            {
                $this->data['js']['redirectOverride'] = \RightNow\Utils\Text::beginsWith($redirectLocation, '/app/') ? $redirectLocation : "/app/$redirectLocation";
            }
        }

        //honor: (1) attribute's value (2) config
        $this->data['attrs']['disable_password'] = $this->data['attrs']['disable_password'] ?: !\RightNow\Utils\Config::getConfig(EU_CUST_PASSWD_ENABLED);
        $this->data['username'] = \RightNow\Utils\Url::getParameter('username');
        if($this->CI->agent->browser() === 'Internet Explorer')
            $this->data['isIE'] = true;
    }
}
