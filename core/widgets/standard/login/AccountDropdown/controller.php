<?php

namespace RightNow\Widgets;

use RightNow\Utils\Text;

class AccountDropdown extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js']['isLoggedIn'] = \RightNow\Utils\Framework::isLoggedIn();
        if ($this->data['js']['isLoggedIn']) {
            $currentUser = $this->CI->model('Contact')->get()->result;

            $this->data['nameToDisplay'] = $this->getNameToDisplay($currentUser);
            if ($this->data['nameToDisplay'] && ($nameSuffix = \RightNow\Utils\Config::getMessage(NAME_SUFFIX_LBL)))
                $this->data['nameToDisplay'] .= " $nameSuffix";
        }

        //get sub-pages
        if($this->data['attrs']['subpages'])
        {
            $this->data['subpages'] = array();
            //get ea. comma-separated key > value pair
            $subPages = explode(',', $this->data['attrs']['subpages']);
            foreach($subPages as $value)
            {
                $splitPosition = strrpos($value, ' > ');
                if($splitPosition === false)
                {
                    echo $this->reportError("Invalid formatting of subpages attribute value '$value' : must be Name > URL separated.");
                    return false;
                }
                $pairValue['title'] = trim(substr($value, 0, $splitPosition));
                $pairValue['href'] = trim(substr($value, $splitPosition + 2));
                array_push($this->data['subpages'], $pairValue);
            }
        }
    }

    /**
     * Determine what to display for the user's name
     * @param Connect\Contact $contact Logged in user's contact record
     * @return string Name to display for user
     */
    private function getNameToDisplay($contact) {
        if (!$contact) return '';

        $socialUser = $this->CI->model('CommunityUser')->get()->result;

        if ($socialUser && $socialUser->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_USER_ACTIVE) {
            $this->data['currentSocialUser'] = $socialUser;
            return $socialUser->DisplayName;
        }

        return $contact->Emails[0]->Address;
    }
}
