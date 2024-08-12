<?php  

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ConditionalChatLink extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['js']['dqaWidgetType'] = WIDGET_TYPE_CCL;
        $this->data['js']['dqaInsertType'] = DQA_WIDGET_STATS;

        $this->data['js']['avail_type'] = PROACTIVE_CHAT_AVAIL_TYPE_SESSIONS;

        list($this->data['js']['prod'], $this->data['js']['cat']) = $this->getProductCategoryValues();

        $this->data['js']['interface_id'] = \RightNow\Api::intf_id();

        $profile = $this->CI->session->getProfile(true);
        if($profile !== null)
        {
            $contactID = $profile->contactID;

            if($contactID > 0)
            {
                $this->data['js']['c_id'] = $contactID;
                $organizationID = $profile->orgID;

                if($organizationID > 0)
                    $this->data['js']['org_id'] = $organizationID;

                $this->data['js']['contact_email'] = $profile->email;
                $this->data['js']['contact_fname'] = $profile->firstName;
                $this->data['js']['contact_lname'] = $profile->lastName;
            }
        }

        $linkUrl = $this->data['attrs']['chat_login_page'];
        if(isset($this->data['attrs']['is_persistent_chat']) && $this->data['attrs']['is_persistent_chat']) {
            $linkUrl = '/app/' .\RightNow\Utils\Config::getConfig(CP_CHAT_URL, 'RNW_UI');
        }

        //Adding Check for CP_COOKIES_ENABLED
        $this->data['js']['cookies_enabled'] = \RightNow\Utils\Config::getConfig(CP_COOKIES_ENABLED);

        if(!$this->data['attrs']['initiate_by_event'] && $this->data['attrs']['enable_availability_check'])
            $this->processDataFromCheckChatQueue($linkUrl);

        if($this->data['attrs']['auto_detect_incident'] && $incidentID = Url::getParameter('i_id'))
            $linkUrl .= "/i_id/$incidentID";

        $linkUrl .= '/request_source/' . CHATS_REQUEST_SOURCE_CCL;
        $this->data['js']['link_url'] = $linkUrl;
        $this->CI->model('Clickstream')->insertWidgetStats($this->data['js']['dqaInsertType'], (object)array('w' => (string)$this->data['js']['dqaWidgetType'], 'hit' => 1));
    }

    function getProductCategoryValues()
    {
        $prodValue = Url::getParameter('p');
        $catValue = Url::getParameter('c');

        if($prodValue)
        {
            if(strlen(trim($prodValue)) === 0)
            {
                $prodValue = null;
            }
            else
            {
                // QA 130606-000085. It's possible for p/c to be CSV, with the most specific value to be at the end.
                $prodValues = explode(',', $prodValue);
                $prodValue = end($prodValues);
            }
        }

        if($catValue)
        {
            if(strlen(trim($catValue)) === 0)
            {
                $catValue = null;
            }
            else
            {
                $catValues = explode(',', $catValue);
                $catValue = end($catValues);
            }
        }

        // If either prod/cat is specified in URL, keep the URL specified value(s).
        // If only one or none of prod/cat is specified in URL, attempt to fill in whichever ones aren't by page context (answer/incident).
        if(!$prodValue || !$catValue)
        {
            if($answerID = Url::getParameter('a_id'))
            {
                if($answer = $this->CI->model('Answer')->get($answerID)->result)
                {
                    if(!$prodValue && $answer->Products && ($prodValue = $this->CI->model('Answer')->getFirstBottomMostProduct($answerID)->result))
                        $prodValue = $prodValue['ID'];

                    if(!$catValue && $answer->Categories && ($catValue = $this->CI->model('Answer')->getFirstBottomMostCategory($answerID)->result))
                        $catValue = $catValue['ID'];
                }
            }
            else if($incidentID = Url::getParameter('i_id'))
            {
                if($incident = $this->CI->model('Incident')->get($incidentID)->result)
                {
                    if(!$prodValue && $incident->Product)
                        $prodValue = $incident->Product->ID;

                    if(!$catValue && $incident->Category)
                        $catValue = $incident->Category->ID;
                }
            }
        }

        return array($prodValue, $catValue);
    }

    function processDataFromCheckChatQueue(&$linkUrl)
    {
        // Issue the queue check, mark the appropriate response type
        $dataJs = $this->data['js'];
        $chatRouteRV = $this->CI->model('Chat')->chatRoute($dataJs['prod'], $dataJs['cat'], isset($dataJs['c_id']) ? $dataJs['c_id'] : '', isset($dataJs['org_id']) ? $dataJs['org_id'] : '', '', '', '', array())->result;
        $chatQueueResult = (is_object($chatRouteRV) && property_exists($chatRouteRV, 'result')) ? $this->CI->model('Chat')->checkChatQueue($chatRouteRV->result, PROACTIVE_CHAT_AVAIL_TYPE_SESSIONS, true)->result : null;

        if(isset($chatQueueResult['stats']))
        {
            $stats = $chatQueueResult['stats'];
            $expectedWaitSeconds = intval($stats['expectedWaitSeconds']);
            $availableSessionCount = intval($stats['availableSessionCount']);

            $this->data['js']['available_session_count'] = $availableSessionCount;
            $this->data['js']['expected_wait_seconds'] = $expectedWaitSeconds;

            if($expectedWaitSeconds <= $this->data['attrs']['wait_threshold'] && $availableSessionCount >= $this->data['attrs']['min_sessions_avail'] && ($expectedWaitSeconds > 0 || $availableSessionCount > 0))
            {
                $this->CI->model('Clickstream')->insertWidgetStats($this->data['js']['dqaInsertType'], (object)array('w' => (string)$this->data['js']['dqaWidgetType'], 'offers' => 1));
                $this->data['js']['offer_recorded'] = true;
            }
        }
        else if(isset($chatQueueResult['out_of_hours']) && $chatQueueResult['out_of_hours'])
        {
            $this->data['js']['unavailable_hours'] = true;
        }

        if(isset($chatQueueResult['survey_data']))
        {
            $surveyData = $chatQueueResult['survey_data'];

            if($surveyData['comp_id'])
                $linkUrl .= '/survey_comp_id/' . $surveyData['comp_id'];

            if($surveyData['comp_auth'])
                $linkUrl .= '/survey_comp_auth/' . $surveyData['comp_auth'];

            if($surveyData['term_id'])
                $linkUrl .= '/survey_term_id/' . $surveyData['term_id'];

            if($surveyData['term_auth'])
                $linkUrl .= '/survey_term_auth/' . $surveyData['term_auth'];

            if($surveyData['send_id'])
            {
                $linkUrl .= '/survey_send_id/' . $surveyData['send_id'];
                $linkUrl .= '/survey_send_delay/' . $surveyData['send_delay'];
            }

            if($surveyData['send_auth'])
                $linkUrl .= '/survey_send_auth/' . $surveyData['send_auth'];
        }

        if(!$this->data['attrs']['ignore_preroute'])
        {
            $routeData = '';

            if(isset($chatQueueResult['q_id']) && $chatQueueResult['q_id'] > 0)
                $routeData .= 'q_id=' . $chatQueueResult['q_id'] . '&';

            if(isset($chatQueueResult['rules']))
            {
                $ruleData = $chatQueueResult['rules'];

                if($ruleData['state'])
                    $routeData .= 'state=' . $ruleData['state'] . '&';

                if($ruleData['escalation'])
                    $routeData .= 'escalation=' . $ruleData['escalation'];
            }

            $this->data['js']['routeData'] = $routeData;

            if(strlen($routeData) !== 0)
                $linkUrl .= '/chat_data/' . base64_encode($routeData);
        }
    }
}
