<?php
namespace RightNow\Widgets;

class ModerationAction extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData()
    {
        //fetch the report data and see if it has record
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, null)->result;
        
        if ($results['total_pages'] <= 0) {
            $this->classList->add('rn_Hidden');
        }
        $statuses = $this->CI->model($this->data['attrs']['object_type'])->getMappedSocialObjectStatuses()->result;
        if (!$statuses) {
            return false;
        }
        if ($this->data['attrs']['object_type'] !== 'CommunityUser') {
            $socialUserStatuses = $this->CI->model('CommunityUser')->getMappedSocialObjectStatuses()->result;
        }
        
        $statusMapping = $this->CI->model($this->data['attrs']['object_type'])->getSocialObjectMetadataMapping($this->data['attrs']['object_type'], 'allowed_actions')->result;

        //map action_id to respective action
        $this->data['actions'] = array();
        foreach ($statusMapping as $action => $actionID) {
            if (isset($statuses[$actionID]) && is_array($statuses[$actionID])) {
                $this->data['actions'][$action] = key($statuses[$actionID]);
            } 
            else if ($socialUserStatuses && isset($socialUserStatuses[$actionID]) && is_array($socialUserStatuses[$actionID])) {
                $this->data['actions'][$action] = key($socialUserStatuses[$actionID]);
            } 
            else {
                $this->data['actions'][$action] = $actionID;
            }
        }        
    }
}
