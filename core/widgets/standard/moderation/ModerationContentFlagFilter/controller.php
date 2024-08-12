<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ModerationContentFlagFilter extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $filterMetaData = $this->CI->model("Report")->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['report_filter_name']);
        if ($filterMetaData->errors) {
            echo $this->reportError($filterMetaData->errors[0]);
            return false;
        }
        $urlFlagTypes = Url::getParameter($this->data['attrs']['report_filter_name']);
        $flagTypes = array_unique(array_merge($urlFlagTypes ? explode(",", $urlFlagTypes) : array(), $this->helper('Social')->mapFlagTypeAttribute($this->data['attrs']['flag_types'] === 'none' ? array() : $this->data['attrs']['flag_types'])));

        $flags = array();
        if (!empty($flagTypes)) {
            $flagLabels = $this->helper('Social')->getFlagTypeLabels($this->data['attrs']['object_type']);
            foreach ($flagTypes as $flagTypeID) {
                $flags[$flagTypeID] = $flagLabels[$flagTypeID];
            }
        }
        if (empty($flags)) {
            return false;
        }
        $this->data['js'] = array(
            'filter_id' => $filterMetaData->result['fltr_id'],
            'oper_id' => $filterMetaData->result['oper_id'],
            'flags' => $flags,
            'selected_flags' => !empty($filterMetaData->result['default_value']) ? $this->helper('Social')->parseReportDefaultFilterValue($filterMetaData->result['default_value'], $filterMetaData->result['data_type']) : null
        );
    }

}