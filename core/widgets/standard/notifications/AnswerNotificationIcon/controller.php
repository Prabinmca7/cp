<?php
namespace RightNow\Widgets;

use RightNow\Utils\Framework, RightNow\Utils\Url;

class AnswerNotificationIcon extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

    }

    function getData() {
        $answerID = Url::getParameter('a_id');
        if($answerID === null)
            return false;

        $this->data['js'] = array(
            'f_tok'    => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            'answerID' => $answerID,
            'autoOpen' => Url::getParameter('notif') // Catch our url parm that indicates that we were just logged in by this same widget.
        );

        if(\RightNow\Utils\Config::getConfig(PTA_ENABLED) && !Framework::isLoggedIn()) {
            $this->data['loginUrl'] = Url::replaceExternalLoginVariables(0, $this->CI->page . "/a_id/$answerID/notif/1");
            $this->data['js']['pta'] = true;
        }
    }
}
