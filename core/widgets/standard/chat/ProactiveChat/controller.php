<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ProactiveChat extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['js']['request_source'] = CHATS_REQUEST_SOURCE_PROACTIVE;

        list($this->data['js']['prod'], $this->data['js']['cat']) = $this->getProductCategoryValues();

        // We need to fetch the legacy version (non-flat) of the profile object in order to be compatible with the profile_item widget attribute's naming conventions
        $profile = $this->CI->session->getProfile(false);
        if($profile !== null)
        {
            $contactID = $profile->c_id->value;

            if($contactID > 0)
            {
                $this->data['js']['c_id'] = $contactID;
                $organizationID = $profile->org_id->value;

                if($organizationID > 0)
                    $this->data['js']['org_id'] = $organizationID;

                $this->data['js']['contact_email'] = $profile->email->value;
                $this->data['js']['contact_fname'] = $profile->first_name->value;
                $this->data['js']['contact_lname'] = $profile->last_name->value;
            }
        }

        if($this->data['attrs']['seconds'] > 0)
            $this->data['js']['seconds_to_do'] = true;

        if($this->data['attrs']['searches'] > 0)
        {
            $this->data['js']['searches'] = $results = $this->CI->session->getSessionData('numberOfSearches');
            $this->data['js']['searches_to_do'] = true;
        }

        if($this->data['attrs']['profile_item'] && $this->data['attrs']['profile_operator'] && ($this->data['attrs']['profile_value'] != null))
        {
            $this->data['js']['profile_to_do'] = true;
            if($profile !== null)
            {
                if(property_exists($profile, $this->data['attrs']['profile_item']))
                {
                    $item = $this->data['attrs']['profile_item'];
                    $value = $this->data['attrs']['profile_value'];
                    if(is_string($profile->$item) && ctype_digit($profile->$item)){
                        $value = intval($profile->$item);
                    }
                    //always use the deepest level for default prod or cat selected in the profile
                    $profileItem = $profile->$item->value;
                    if(is_array($profileItem))
                    {
                        $profileItem = end(array_filter($profileItem));
                    }
                    switch($this->data['attrs']['profile_operator'])
                    {
                        case 'equals':
                            $this->data['js']['profile'] = (boolean) ($profileItem == $value);
                            break;
                        case 'less than or equals':
                            $this->data['js']['profile'] = (boolean) ($profileItem <= $value);
                            break;
                        case 'greater than or equals':
                            $this->data['js']['profile'] = (boolean) ($profileItem >= $value);
                            break;
                        case 'not equal':
                            $this->data['js']['profile'] = (boolean) ($profileItem != $value);
                            break;
                        case 'less than':
                            $this->data['js']['profile'] = (boolean) ($profileItem < $value);
                            break;
                        case 'greater than':
                            $this->data['js']['profile'] = (boolean) ($profileItem > $value);
                            break;
                        default:
                    }
                }
            }
        }

        $this->data['js']['interface_id'] = \RightNow\Api::intf_id();

        $this->data['js']['dqaWidgetType'] = WIDGET_TYPE_PAC;
        $this->data['js']['dqaInsertType'] = DQA_WIDGET_STATS;
        $dqaVar = (object)array('w' => $this->data['js']['dqaWidgetType'] . '', 'hit' => 1);
        $this->CI->model('Clickstream')->insertWidgetStats($this->data['js']['dqaInsertType'], $dqaVar);
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
}
