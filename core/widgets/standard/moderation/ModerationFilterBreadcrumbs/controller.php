<?php

namespace RightNow\Widgets;

use \RightNow\Utils\Config,
    \RightNow\Utils\Text;

class ModerationFilterBreadcrumbs extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $dateFormat = Text::getDateFormatFromDateOrderConfig();
        $this->data['js']['date_format'] = $dateFormat["short"];
        $displayedFilters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $this->data['js']['defaultFilters'] = $this->getReportDefautlFilterValues();
        //Find selected filters
        $filterMetaData = $this->getFilterMetaData($this->data['attrs']['object_type']);
        foreach ($filterMetaData as $filterName => $filterData) {
            $selectedFilterString = isset($filters[$filterName]) && isset($filters[$filterName]->filters) && isset($filters[$filterName]->filters->data) ? (is_array($filters[$filterName]->filters->data) ? implode(",", $filters[$filterName]->filters->data) : $filters[$filterName]->filters->data) : '';
            if ($selectedFilterString && $selectedFilterArray = $this->getAppliedFilter($filterName, $selectedFilterString)) {
                $displayedFilters[] = $this->getFormattedFilter($filterName, $filters[$filterName]->filters->fltr_id, $filterData['caption'], $selectedFilterArray);
            }
        }
        if (empty($displayedFilters)) {
            $this->classList->add('rn_Hidden');
        }
        $this->data['js']['filters'] = $displayedFilters;
        $this->data['js']['allAvailableFilters'] = $filterMetaData;
    }

    /**
     * Utility function to format filter data
     * @param string $urlParameter The URL parameter associated with the filter
     * @param int $filterID ID for the filter
     * @param string $typeLabel Label to use for the filter
     * @param mixed $filterData The data to be applied to the filter
     * @return array Keys include 'urlParameter', 'filterID', 'label' and 'data'
     */
    protected function getFormattedFilter ($urlParameter, $filterID, $typeLabel, $filterData) {
        return array(
            'urlParameter' => $urlParameter,
            'filterID' => $filterID,
            'label' => $typeLabel,
            'data' => $filterData
        );
    }

    /**
     * Return ID and LookupName of the selected filters
     * @param string $filterName The name of the filter, e.g. questions.status, questions.updated etc.
     * @param string $selectedFilterString Comma separated list of selected filters
     * @return array An array of selected filters with thier IDs and LookupNames
     */
    protected function getAppliedFilter ($filterName, $selectedFilterString) {
        $selectedFilterArray = array();
        if (empty($selectedFilterString) || $this->isDefaultFilter($filterName, $selectedFilterString)) {
            return null;
        }
        $formatter = $this->getFilterValueFormatter($filterName);
        if (($filterName === 'p' || $filterName === 'c') && $formatter) {
            return $formatter($selectedFilterString, $this, $filterName);
        }

        $appliedFilters = explode(',', $selectedFilterString);
        $availableFilters = $this->getFilterMetaData($this->data['attrs']['object_type'], $filterName);
        
        foreach ($appliedFilters as $filterID) {
            $filterValue = $availableFilters['filters'][$filterID];
            $filterValue = !$filterValue && $formatter ? $formatter($filterID, $this, $filterName) : $filterValue;
            if (!$filterValue) {
                continue;
            }
            $selectedFilterArray[] = array('id' => $filterID, 'label' => $filterValue);
        }
        return $selectedFilterArray;
    }

    /**
     * Check if given filterName and value is a default filter
     * @param string $filterName Name of the filter
     * @param string $selectedFilterString Selected filter value
     * @return boolean Return true of filter is a default filter otherwise false.
     */
    protected function isDefaultFilter($filterName, $selectedFilterString) {
        if (is_array($this->data['js']['defaultFilters'][$filterName])) {
            $selectedValues = explode(",", $selectedFilterString);
            return count($this->data['js']['defaultFilters'][$filterName]) === count($selectedValues) && count(array_intersect($this->data['js']['defaultFilters'][$filterName], $selectedValues)) === count($selectedValues);
        }
        return $selectedFilterString === $this->data['js']['defaultFilters'][$filterName];
    }

    /**
     * Gets the default filter values from the report metadata.
     * @return array Array of default filters
     */
    protected function getReportDefautlFilterValues() {
        $defaultFilters = array();
        $filterMetaData = $this->getFilterMetaData($this->data['attrs']['object_type']);
        if (($allowedFilters = $this->getAllowedReportFilters($this->data['attrs']['object_type']))) {
            foreach ($allowedFilters as $filterType => $filterName) {
                $parseOptions = array(
                    "allowedOptions" => isset($filterMetaData[$filterName]["filters"]) ? $filterMetaData[$filterName]["filters"] : array(),
                    "dateFormat" => isset($this->data['js']['date_format']) ? $this->data['js']['date_format'] : '',
                    "filterName" => $filterName
                );                
                $reportfilterMetadata = $this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $filterName);
                if ($reportfilterMetadata->result && $reportfilterMetadata->result['default_value']) {
                    $defaultFilters[$filterName] = $this->helper('Social')->parseReportDefaultFilterValue($reportfilterMetadata->result['default_value'], $reportfilterMetadata->result['data_type'], $parseOptions);
                }
            }
        }
        return $defaultFilters;
    }

    /**
     * Get all allowed filter for a given sociail object name
     * @param string|null $socialObjectName Name of the social object
     * @return array return list of allowed filter names or return only filters specific to given social object if valid $socialObjectName is passed.
     */
    protected function getAllowedReportFilters ($socialObjectName = null) {
        static $metadataMappings = array(
            'CommunityQuestion' => array("date_filter" => "questions.updated", "status_filter" => "questions.status", "prod_filter" => "p", "cat_filter" => "c", "flag_filter" => "question_content_flags.flag"),
            'CommunityComment' => array("date_filter" => "comments.updated", "status_filter" => "comments.status", "prod_filter" => "p", "cat_filter" => "c", "flag_filter" => "comment_cnt_flgs.flag"),
            'CommunityUser' => array("status_filter" => "users.status")
        );
        return ($socialObjectName && $metadataMappings[$socialObjectName]) ? $metadataMappings[$socialObjectName] : $metadataMappings;
    }

    /**
     * Get all filter names and possible values for a given social object
     * @param string $socialObjectName Name of the social object
     * @param string|null $filterName Name of the filter
     * @return array filter data for a given social object
     */
    protected function getFilterMetaData ($socialObjectName, $filterName = null) {
        static $allFilterData = array();
        
        if ((!isset($allFilterData[$socialObjectName]) || !$allFilterData[$socialObjectName]) && isset($socialObjectName) && $socialObjectName) {    
            $filters = $this->getAllowedReportFilters($socialObjectName);
            if (isset($filters['date_filter']) && $filters['date_filter']) {
                $filtersKey = isset($filters['date_filter']) ? $filters['date_filter'] : '';
                $allFilterData[$socialObjectName][$filtersKey]['filters'] = $this->helper('Social')->formatListAttribute($this->data['attrs']['date_filter_options']);
                $allFilterData[$socialObjectName][$filters['date_filter']]['caption'] = $this->data['attrs']['label_date_filter'];
            }
            if ($filters['status_filter']) {
                $statusTypes = $this->CI->model($this->data['attrs']['object_type'])->getSocialObjectMetadataMapping($this->data['attrs']['object_type'], 'status_type_ids')->result;
                $hideStatusTypeIDs = $this->data['attrs']['hide_pending_status'] ? array($statusTypes['pending']) : array();
                $allFilterData[$socialObjectName][$filters['status_filter']]['filters'] = $this->helper('Social')->getStatusLabels($socialObjectName, $hideStatusTypeIDs);
                $allFilterData[$socialObjectName][$filters['status_filter']]['caption'] = $this->data['attrs']['label_status_filter'];
            }
            if (isset($filters['prod_filter']) && $filters['prod_filter']) {
                $allFilterData[$socialObjectName][$filters['prod_filter']]['caption'] = $this->data['attrs']['label_product_filter'];
            }
            if (isset($filters['cat_filter']) && $filters['cat_filter']) {
                $allFilterData[$socialObjectName][$filters['cat_filter']]['caption'] = $this->data['attrs']['label_category_filter'];
            }
            if (isset($filters['flag_filter']) && $filters['flag_filter']) {
                $allFilterData[$socialObjectName][$filters['flag_filter']]['filters'] = $this->helper('Social')->getFlagTypeLabels($socialObjectName);
                $allFilterData[$socialObjectName][$filters['flag_filter']]['caption'] = $this->data['attrs']['label_flag_filter'];
            }
        }
        if (isset($allFilterData[$socialObjectName]) && isset($allFilterData[$socialObjectName][$filterName])) {
            return $allFilterData[$socialObjectName][$filterName];
        }
        return ($allFilterData[$socialObjectName]) ? $allFilterData[$socialObjectName] : array();
    }

    /**
     * Creates specific formatter functions for the filter values
     *
     * @param string $filterName Name of the Filter
     * @return Function The function to format the fiter value to be displayed
     */
    protected function getFilterValueFormatter ($filterName) {
        static $formatters = null;
        if (!$formatters) {
            $dateFilterFormatter = function($value, $thisContext, $filterName) {
                $dateRange = Text::validateDateRange($value, $thisContext->data['js']['date_format'], "|", false, $thisContext->data['attrs']['max_date_range_interval']);
                return str_replace("|", " - ", $dateRange);
            };

            $prodCatFilterFormatter = function($value, $thisContext, $filterName) {
                //If it is a "No Value" filter, return the static label.
                if((int)$value === -1){
                    return array(array('id' => -1, 'label' => $thisContext->data['attrs']['label_prod_cat_no_value']));
                }
                $chainValues = explode(',', $value);
                $selectedFilterArray = $thisContext->CI->model('Prodcat')->getFormattedChain($filterName === 'p' ? 'Product' : 'Category', end($chainValues))->result;
                return $selectedFilterArray;

            };
            $formatters = array('questions.updated' => $dateFilterFormatter,
                'comments.updated' => $dateFilterFormatter,
                'p' => $prodCatFilterFormatter,
                'c' => $prodCatFilterFormatter);
        }

        return isset($formatters[$filterName]) ? $formatters[$filterName] : null;
    }
}