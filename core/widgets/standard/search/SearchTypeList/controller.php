<?php

namespace RightNow\Widgets;

class SearchTypeList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['attrs']['filter_list'] = trim($this->data['attrs']['filter_list']);

        $filters = array();
        if(strlen($this->data['attrs']['filter_list'])) {
            //Iterate over the list of filter names that we'll be adding and get their details. Add a warning if the filter doesn't exist.
            $addedFilters = explode(',', $this->data['attrs']['filter_list']);
            foreach($addedFilters as $filter) {
                $filter = trim($filter);
                $filterData = $this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $filter)->result;
                if($filterData === null || (is_array($filterData) && !in_array($filterData['data_type'], array(VDT_INT, VDT_VARCHAR)))) {
                    $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLTR_NAME_PCT_S_EX_RPT_ISNT_TYPE_MSG), $filter), false);
                    continue;
                }
                $filters[] = $filterData;
            }
        }
        else {
            //If search_type_only find all the filters that begin with 'search_' otherwise just get all the int and text filters.
            $filters = ($this->data['attrs']['search_type_only']) ?
                $this->CI->model('Report')->getSearchFilterData($this->data['attrs']['report_id'])->result :
                $this->CI->model('Report')->getRuntimeIntTextData($this->data['attrs']['report_id'])->result;
        }

        if(empty($filters)) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(SRCH_FLTRS_AVAIL_RPT_PCT_S_FLTRS_MSG), $this->data['attrs']['report_id'], 'filter_list'));
            return false;
        }

        //Make sure that the widget knows what value to reset to when a filter is removed
        $resetFilter = $this->CI->model('Report')->getSearchFilterTypeDefault($this->data['attrs']['report_id'])->result;
        foreach($filters as $filter) {
            if($filter['fltr_id'] === $resetFilter['fltr_id']) {
                $hasReset = true;
                break;
            }
        }

        //The reset value is not in the set of filters. Add it so that when the filter is reset it will be in the list.
        if(!$hasReset) {
            $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(RPT_PCT_S_DEF_FLTR_PCT_S_FLTR_SEL_MSG), $this->data['attrs']['report_id'], $resetFilter['expression1']), false);
            array_unshift($filters, $resetFilter);
        }

        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $urlFilters);
        $this->data['js'] = array('filters' => $filters,
                                  'defaultFilter' => $urlFilters['searchType']->filters->fltr_id,
                                  'resetFilter' => $resetFilter['fltr_id'],
                                  'rnSearchType' => 'searchType',
                                  'searchName' => 'searchType');
    }
}
