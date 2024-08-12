<?php
namespace RightNow\Widgets;

class ModerationProfile extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $user = (int) \RightNow\Utils\Url::getParameter('user');
        $socialUserContact = $this->CI->model('Contact')->getForSocialUser($user)->result;
        $this->data['userOnPage'] = $this->CI->model('CommunityUser')->get($user)->result;
        $this->data['loggedInUser'] = $this->CI->model('CommunityUser')->get()->result;

        if(!($socialUserContact &&
            $this->data['userOnPage'] &&
            $this->data['loggedInUser'] &&
            $this->data['loggedInUser']->SocialPermissions->canReadContactDetails()))
            return false;

        $this->data['userData'] = array(
            'email'     => ($socialUserContact->Emails[0]->AddressType->ID == CONNECT_EMAIL_PRIMARY) ? $socialUserContact->Emails[0]->Address : '',
            'firstName' => $socialUserContact->Name->First,
            'lastName'  => $socialUserContact->Name->Last
        );
    }
}
