<?php

namespace RightNow\Widgets;

class ModerationStatusFilter extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $filterMetaData = $this->CI->model("Report")->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['report_filter_name']);
        $statusTypes = $this->CI->model($this->data['attrs']['object_type'])->getSocialObjectMetadataMapping($this->data['attrs']['object_type'], 'status_type_ids')->result;
        $this->data['hide_status_type_ids'] = $this->data['attrs']['hide_pending_status'] ? array($statusTypes['pending']) : array();
        if ($filterMetaData->errors) {
            echo $this->reportError($filterMetaData->errors[0]);
            return false;
        }
        $this->data['js'] = array(
            'filter_id' => $filterMetaData->result['fltr_id'],
            'oper_id' => $filterMetaData->result['oper_id'],
            'default_value' => $filterMetaData->result['default_value'] ? str_replace(";", ",", $filterMetaData->result['default_value']) : ''
        );
    }
}
