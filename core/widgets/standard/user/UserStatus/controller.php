<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class UserStatus extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!$user = $this->CI->model('CommunityUser')->get(\RightNow\Utils\Url::getParameter('user'))->result) {
            return false;
        }

        $status = $this->getUserStatus($user);
        if(($status === 'active' && !$this->data['attrs']['display_active']) || $status === 'deleted') {
            // we don't expect to be able to retrieve a deleted user from the model function above,
            // but returning false here insures we don't try to process it, anyway
            return false;
        }

        $this->classList->add('rn_' . ucfirst($status));
        $this->data['status'] = $status;
    }

     /*
     * Retrieve the user's status
     * @param object $user The question Connect object
     * @return string The user's status
     */
    protected function getUserStatus($user) {
        $statusType = 'active';
        foreach(array('active', 'pending', 'suspended', 'deleted', 'archived') as $status) {
            $method = 'is' . ucfirst($status);
            if($user->SocialPermissions->$method()) {
                $statusType = $status;
                break;
            }
        }

        return $statusType;
    }
}
