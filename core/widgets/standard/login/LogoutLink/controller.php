<?php
namespace RightNow\Widgets;

use RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class LogoutLink extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'logout_ajax' => array(
                'method' => 'doLogout',
                'clickstream' => 'account_logout',
                'token_check' => false,
            ),
        ));
    }

    function getData()
    {
        if(!Framework::isLoggedIn() || (Framework::isPta() && !\RightNow\Utils\Config::getConfig(PTA_EXTERNAL_LOGOUT_SCRIPT_URL)))
            return false;

        if(Framework::isPta()){
            $this->data['js']['redirectLocation'] = Url::deleteParameter((\RightNow\Utils\Config::getConfig(SEC_END_USER_HTTPS, 'COMMON') ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 'sno');
        }
        else if($this->data['attrs']['redirect_url']){
            $this->data['js']['redirectLocation'] = $this->data['attrs']['redirect_url'];
        }
        else{
            $this->data['js']['redirectLocation'] = Url::deleteParameter($_SERVER['REQUEST_URI'], 'sno');
        }

        $this->data['js']['redirectLocation'] = Url::deleteParameter($this->data['js']['redirectLocation'], 'sno');
        if(\RightNow\Utils\Url::sessionParameter() !== '')
            $this->data['js']['redirectLocation'] = Url::addParameter($this->data['js']['redirectLocation'], 'session', Text::getSubstringAfter(Url::sessionParameter(), "session/"));

        if(\RightNow\Utils\Framework::isPta()){
            $this->data['js']['redirectLocation'] = str_ireplace('%source_page%', urlencode($this->data['js']['redirectLocation']), \RightNow\Utils\Config::getConfig(PTA_EXTERNAL_LOGOUT_SCRIPT_URL));
        }

        //If this interface utilizes the community module, make sure to log the user out of
        //there as well and tell them where to go afterwards
        if(\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED, 'RNW')){
            if($socialLogoutUrl = \RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL, 'RNW')){
                $socialLogoutUrl .= '/scripts/signout';
                //Check if redirect is fully qualified and on the same domain
                $redirectComponents = parse_url($this->data['js']['redirectLocation']);
                if($redirectComponents['host']){
                    $socialLogoutUrl .= '?redirectUrl=' . urlencode($this->data['js']['redirectLocation']);
                }
                else{
                    $socialLogoutUrl .= '?redirectUrl=' . urlencode(Url::getShortEufBaseUrl('sameAsCurrentPage', $this->data['js']['redirectLocation']));
                }
                $this->data['js']['redirectLocation'] = $socialLogoutUrl;
            }
            else{
                echo $this->reportError(\RightNow\Utils\Config::getMessage(COMMUNITY_ENABLED_CONFIG_SET_MSG));
                return false;
            }
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Logout user. Echos out JSON encoded result
     * @param array|null $parameters Post parameters
     */
    function doLogout($parameters)
    {
        Url::redirectToHttpsIfNecessary();

        $this->renderJSON($this->CI->model('Contact')->doLogout((isset($parameters['url']) && $parameters['url']) ? $parameters['url'] : '', $parameters['redirectUrl'])->result);
    }
}
