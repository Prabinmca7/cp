<?php
namespace RightNow\Widgets;

class CommunityUserDisplay extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs){
        parent::__construct($attrs);
    }

    function getData(){
        if(!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED, 'RNW')){
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if(\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL, 'RNW') === ''){
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }
        $userHash = \RightNow\Utils\Url::getParameter('people');
        if(!$userHash && !($userHash = $this->data['attrs']['user_hash']))
            return false;

        $userObject = $this->CI->model('Social')->getCommunityUser(array('userHash' => $userHash))->result;
        if($userObject && $userObject->user && !$userObject->error)
            $this->data['user'] = $userObject->user;
        else
            return false;
    }
}
