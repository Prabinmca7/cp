<?php
namespace RightNow\Widgets;

use RightNow\Utils;

class BasicLoginForm extends \RightNow\Widgets\LoginForm {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) {
            return false;
        }
        if($prefilledUsername = $this->CI->input->post('Contact_Login')){
            $this->data['username'] = $prefilledUsername;
        }
        $this->data['on_success_url'] = $this->data['js']['redirectOverride']
            ?: $this->data['attrs']['redirect_url'] 
            ?: Utils\Text::getSubstringAfter(($_SERVER['HTTP_RNT_REFERRER'] ?: $_SERVER['HTTP_REFERER']), Utils\Config::getConfig(OE_WEB_SERVER))
            ?: Utils\Url::getHomePage();

        if(!Utils\Config::getConfig(CP_COOKIES_ENABLED)){
            //Session cookies are disabled, so set a cookie when they hit a page with this widget so the login 
            //request can determine if the user has cookies enabled
            Utils\Framework::setTemporaryLoginCookie();
        }
    }
}
