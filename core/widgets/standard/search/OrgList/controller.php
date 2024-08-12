<?php

namespace RightNow\Widgets;

class OrgList extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $profile = $this->CI->session->getProfile(true);
        if($profile->orgID <= 0)
        {
            // no organization associated to contact
            // nothing to see here move along
            return false;
        }
        if(\RightNow\Utils\Config::getConfig(MYQ_VIEW_ORG_INCIDENTS) < $this->data['attrs']['display_type'])
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(WARN_CFG_MYQ_VIEW_ORG_INC_SET_EQ_MSG));
            return false;
        }
        $incidentAlias = $this->CI->model('Report')->getIncidentAlias($this->data['attrs']['report_id'])->result;
        $organizationAlias = $this->CI->model('Report')->getOrganizationAlias($this->data['attrs']['report_id'])->result;

        if(!$incidentAlias || !$organizationAlias){
            echo $this->reportError(sprintf((!$incidentAlias) ? \RightNow\Utils\Config::getMessage(INCIDENTS_TABLE_REPORT_PCT_D_MSG) : \RightNow\Utils\Config::getMessage(ORGANIZATION_TABLE_REPORT_PCT_D_MSG), $this->data['attrs']['report_id']));
            return false;
        }

        if($this->data['attrs']['report_page'] === '{current_page}')
            $this->data['attrs']['report_page'] = '';

        // 0 - individual
        // 1 = organization
        // 2 - organization and subsidiaries
        $this->data['js'] = array(
            'defaultIndex' => intval(\RightNow\Utils\Url::getParameter('org')) ?: 0,
            'resetValue' => 0,
            'orgID'         => $profile->orgID,
            'rnSearchType'  => 'org',
            'searchName'    => 'org',
            'options'       => array(
                array(
                    'fltr_id'   => "$incidentAlias.c_id",
                    'val'       => $profile->contactID,
                    'oper_id'   => 1,
                    'label'     => $this->data['attrs']['label_individual'],
                ),
                array(
                    'fltr_id'   => "$incidentAlias.org_id",
                    'val'       => $profile->orgID,
                    'oper_id'   => 1,
                    'label'     => $this->data['attrs']['label_org'],
                ),
            ),
        );
        if($this->data['attrs']['display_type'] > 1){
            $organizationLevel = $profile->orgLevel ?: 1;
            $this->data['js']['options'][] = array(
                'fltr_id'   => "$organizationAlias.lvl{$organizationLevel}_id",
                'val'       => $profile->orgID,
                'oper_id'   => 1,
                'label'     => $this->data['attrs']['label_sub'],
            );
        }
    }
}
