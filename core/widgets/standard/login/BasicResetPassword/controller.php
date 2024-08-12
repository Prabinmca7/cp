<?php

namespace RightNow\Widgets;

class BasicResetPassword extends ResetPassword
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(!\RightNow\Utils\Config::getConfig(CP_COOKIES_ENABLED)){
            //Session cookies are disabled, so set a cookie when they hit a page with this widget so the login 
            //request can determine if the user has cookies enabled
            \RightNow\Utils\Framework::setTemporaryLoginCookie();
        }
        return parent::getData();
    }
}
