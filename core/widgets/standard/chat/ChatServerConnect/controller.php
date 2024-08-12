<?php

namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Config;

class ChatServerConnect extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'authenticate_chat_ajax' => 'authenticateChat'
        ));
    }

    function getData()
    {
        $this->loadMoment();

        $this->data['js']['interfaceID'] = \RightNow\Api::intf_id();
        $contactID = $this->CI->session->getProfileData('contactID');
        $this->data['js']['maUrl'] = \RightNow\Utils\Url::getShortEufBaseUrl(false, '/ci/documents/detail/1/' . \RightNow\Api::generic_track_encode($contactID) . '/6/' . TBL_CHATS);
        $this->data['js']['isSpider'] = $this->CI->rnow->isSpider();

        $this->data['chatHours'] = $this->CI->model('Chat')->getChatHours()->result;
        if(!$this->data['chatHours']['inWorkHours'] || $this->data['chatHours']['holiday'])
            $this->data['outOfChatHours'] = true;

        $profile = $this->CI->session->getProfile(true);

        if($profile !== null)
        {
            $contactID = $profile->contactID;
            $organizationID = $profile->orgID;

            if($contactID > 0)
            {
                $this->data['js']['contactID'] = $contactID;

                if(is_int($organizationID) && $organizationID > 0)
                    $this->data['js']['organizationID'] = $organizationID;

                if(isset($profile->email) && $profile->email !== null && $profile->email !== '')
                    $this->data['js']['contactEmail'] = $profile->email;

                if(isset($profile->firstName) && $profile->firstName !== null && $profile->firstName !== '')
                    $this->data['js']['contactFirstName'] = $profile->firstName;

                if(isset($profile->lastName) && $profile->lastName !== null && $profile->lastName !== '')
                    $this->data['js']['contactLastName'] = $profile->lastName;
            }
        }

        $this->data['js']['sessionID'] = $this->CI->session->getSessionData('sessionID');
        $requestSource = \RightNow\Utils\Url::getParameter('request_source');

        // If 'request_source' is specified on the URL, use it. Otherwise look for 'pac' parameter. If neither are found, default to standard.
        if($requestSource)
        {
            $this->data['js']['requestSource'] = $requestSource;
        }
        else
        {
            $pac = \RightNow\Utils\Url::getParameter('pac');
            $this->data['js']['requestSource'] = $pac ? $pac : CHATS_REQUEST_SOURCE_STANDARD;
        }

        $this->data['js']['customFields'] = $this->CI->model('Chat')->getBlankCustomFields()->result;
        $this->data['js']['dateField'] = EUF_DT_DATE;
        $this->data['js']['dateTimeField'] = EUF_DT_DATETIME;
        $this->data['js']['radioField'] = EUF_DT_RADIO;

        foreach($_POST as $key => $value)
        {
            if(\RightNow\Utils\Text::beginsWith($key, 'Incident_CustomFields'))
            {
                $this->data['js']['postedCustomFields'][$key] = html_entity_decode($value);
            }
            else if($key === 'Incident_Subject')
            {
                $this->data['js']['postedSubject'] = $value;
            }
            else if($key === 'Incident_Product')
            {
                $this->data['js']['postedProduct'] = $value;
            }
            else if($key === 'Incident_Category')
            {
                $this->data['js']['postedCategory'] = $value;
            }
            else if($key === 'chat_data')
            {
                $this->data['js']['chat_data'] = $value;
            }
            else if($key === 'referrerUrl')
            {
                $this->data['js']['referrerUrl'] = $value;
            }
            else if($profile === null) // Only use POSTed contact info if not logged in
            {
                if($key === 'Contact_Emails_PRIMARY_Address')
                    $this->data['js']['contactEmail'] = $value;
                else if($key === 'Contact_Name_First')
                    $this->data['js']['contactFirstName'] = $value;
                else if($key === 'Contact_Name_Last')
                    $this->data['js']['contactLastName'] = $value;
            }
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    function authenticateChat()
    {
        $chatType = 1; // hardcoded to standard chat since that is the only mode supported at this time
        $chatResult = $this->CI->model('Chat')->authenticateChat($chatType);
        if ($chatResult !== null && (!empty($chatResult->errors) || !$chatResult->result)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        } else {
            echo json_encode(array(
                'jwt' => $chatResult->result['jwt'],
                'pool' => $chatResult->result['pool'],
            ));
        }
    }

    function loadMoment()
    {
        if (($clientLoader = $this->CI->clientLoader) && !$clientLoader->isJavaScriptModuleNone())
        {
            $clientLoader->addJavaScriptInclude(\RightNow\Utils\Url::getCoreAssetPath('thirdParty/js/moment.min.js'));
        }
    }
}
