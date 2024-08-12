<?php
/**
 * File: controller.php
 * Abstract: Controller for FilteredTopAnswers widget. Retrieves the filtered report data and passes results down to the view.
 * Version: 1.0
 */

namespace Custom\Widgets\reports;

class FilteredTopAnswers extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $reportFilters = array();

        //Build up the details for each filter specified
        if($this->data['attrs']['product_filter_id']){
            $reportFilters['product_filter_id'] = array('key' => 'p',
                                                        'fullName' => 'product',
                                                        'shortName' => 'prod',
                                                        'value' => $this->data['attrs']['product_filter_id']);
        }

        if($this->data['attrs']['category_filter_id']){
            $reportFilters['category_filter_id'] = array('key' => 'c',
                                                         'fullName' => 'category',
                                                         'shortName' => 'cat',
                                                         'value' => $this->data['attrs']['category_filter_id']);
        }

        //Iterate over the set filters to build up the array to apply to the report
        foreach($reportFilters as $attributeName => $filter){
            //Make sure the report contains a filter for the product or category specified. If not, display a warning, but continue
            $definedReportFilter = $this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $filter['shortName']);
            if(!$definedReportFilter || !$definedReportFilter->result){
                echo $this->reportError("Report {$this->data['attrs']['report_id']} does not contain the proper {$filter['fullName']} filter. The value specified for the '$attributeName' filter is being ignored.", false);
                continue;
            }
            $definedReportFilter = $definedReportFilter->result;

            //Check if the value should be modified from a parameter in the URL
            if($this->data['attrs']['allow_url_filter_modification'] && ($urlValue = \RightNow\Utils\Url::getParameter($filter['key']))){
                $filter['value'] = $urlValue;
            }

            //Now that we have the ID to use, generate the full chain for the product/category (i.e. level 1 ID, level 2 ID, etc) since that data format is required for reports
            $filterChain = $this->CI->model('Prodcat')->getFormattedChain($filter['shortName'], $filter['value'], true)->result;
            $filterChain = implode(',', $filterChain);

            //Create a new filter for the report
            $reportFilters[$filter['key']] = (object)array(
                'filters' => (object)array(
                    'fltr_id' => $definedReportFilter['fltr_id'],
                    'oper_id' => $definedReportFilter['oper_id'],
                    'optlist_id' => $definedReportFilter['optlist_id'],
                    'report_id' => $this->data['attrs']['report_id'],
                    'rnSearchType' => 'menufilter',
                    'data' => array($filterChain),
                    ),
                'type' => $definedReportFilter['name'],
                'report_default' => $definedReportFilter['default_value'],
            );
        }
        $reportFilters['per_page'] = $this->data['attrs']['limit'];

        //Create a report token so that we can request a report
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);

        //Get report results
        $this->data['results'] = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $reportFilters, array())->result;
    }
}