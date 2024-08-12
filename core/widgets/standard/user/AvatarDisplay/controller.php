<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class AvatarDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!($userID = $this->data['attrs']['user_id']) && !($userID = intval(\RightNow\Utils\Url::getParameter('user')))) {
            echo $this->reportError(Config::getMessage(NO_USER_ID_SPECIFIED_LBL));
            return false;
        }

        if (!$this->data['js']['socialUser'] = $this->CI->model('CommunityUser')->get($userID)->result) {
            echo $this->reportError(Config::getMessage(NO_USER_FOUND_FOR_USER_ID_SPECIFIED_LBL));
            return false;
        }
    }
}
