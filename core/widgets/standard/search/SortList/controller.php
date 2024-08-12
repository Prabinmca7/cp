<?php
namespace RightNow\Widgets;

class SortList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $filters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $headers = $this->CI->model('Report')->getReportHeaders($this->data['attrs']['report_id'],
            \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']), $filters, null)->result;

        $this->data['js'] = array(
            'headers' => $headers,
            'col_id' => isset($filters['sort_args']['filters']['col_id']) ? $filters['sort_args']['filters']['col_id'] : -1,
            'sort_direction' => isset($filters['sort_args']['filters']['sort_direction']) ? $filters['sort_args']['filters']['sort_direction'] : 1,
            'searchName' => 'sort_args'
        );
    }
}
