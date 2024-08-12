<?php
namespace RightNow\Widgets;

class EmailAnswerLink extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'send_email_ajax' => array(
                'method' => 'emailAnswer',
                'clickstream' => 'email_answer',
            ),
            'send_discussion_email_ajax' => array(
                'method' => 'emailDiscussion',
                'clickstream' => 'email_discussion',
            )
        ));
    }

    function getData()
    {
        if ($this->data['attrs']['object_type'] === 'question')
        {
            if(($objectID = \RightNow\Utils\Url::getParameter('qid')) === null)
                return false;

            if (!($socialQuestion = $this->CI->model('CommunityQuestion')->get($objectID)->result))
                return false;

            // If the social question is not active then hide the widget
            if (!$socialQuestion->SocialPermissions->isActive())
                $this->classList->add('rn_Hidden');

            $this->data['js'] = array(
                'objectID' => $objectID,
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                'activeStatusWithTypeID' => STATUS_TYPE_SSS_QUESTION_ACTIVE
            );
        }
        else
        {
            if(($objectID = \RightNow\Utils\Url::getParameter('a_id')) === null)
                return false;

             $this->data['js'] = array(
                'objectID' => $objectID,
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                'isProfile' => false,
            );

            if($profile = $this->CI->session->getProfile(true))
            {
                // @codingStandardsIgnoreStart
                $this->data['js']['senderName'] = trim((\RightNow\Utils\Config::getConfig(intl_nameorder)) ? $profile->lastName . ' ' . $profile->firstName : $profile->firstName . ' ' . $profile->lastName);
                // @codingStandardsIgnoreEnd
                $this->data['js']['senderEmail'] = $profile->email;
                $this->data['js']['isProfile'] = true;
            }
            else
            {
                $this->data['js']['senderEmail'] = $this->CI->session->getSessionData('previouslySeenEmail') ?: '';
            }
        }
    }

    /**
     * Emails answer link via Ajax request. Echos out JSON encoded result
     * @param array|null $parameters Post parameters
     */
    static function emailAnswer($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        echo \RightNow\Utils\Framework::jsonResponse(get_instance()->model('Answer')->emailToFriend($parameters['to'], $parameters['name'], $parameters['from'], $parameters['a_id'])->toJson(), false);
    }

    /**
     * Emails discussion link via Ajax request. Echoes out JSON encoded result
     * @param array|null $parameters Post parameters
     */
    static function emailDiscussion($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        echo get_instance()->model('CommunityQuestion')->emailToFriend($parameters['to'], $parameters['qid'])->toJson();
    }
}
