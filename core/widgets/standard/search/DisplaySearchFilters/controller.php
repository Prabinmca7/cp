<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Config;

class DisplaySearchFilters extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $displayedFilters = $defaultFilters = array();
        $searchPage = $this->data['attrs']['report_page_url'] ?: "/app/{$this->CI->page}/";

        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);

        //Add in the Product and Category filters
        if($chain = !empty($filters['p']->filters->data[0]) ? trim($filters['p']->filters->data[0]) : null) {
            $displayedFilters[] = $this->getFormattedFilter('p', $filters['p']->filters->fltr_id, Config::getMessage(PRODUCT_LBL), $this->getHierarchyData('p', $chain, $searchPage));
        }
        if($chain = !empty($filters['c']->filters->data[0]) ? trim($filters['c']->filters->data[0]) : null) {
            $displayedFilters[] = $this->getFormattedFilter('c', $filters['c']->filters->fltr_id, Config::getMessage(CATEGORY_LBL), $this->getHierarchyData('c', $chain, $searchPage));
        }

        //Add in the search type filter if it's different from the default
        $reportID = (int) $this->data['attrs']['report_id'];
        $defaultSearchType = $this->CI->model('Report')->getSearchFilterTypeDefault($reportID)->result;
        $displayedSearchType = $this->CI->model('Report')->getFilterById($reportID, $filters['searchType']->filters->fltr_id)->result;
        $defaultFilters[] = array('name' => 'st', 'defaultValue' => $defaultSearchType['fltr_id']);

        if ($reportID === CP_WIDX_REPORT_DEFAULT) {
            // As the report model will send the standard filterID when there is no search term specified,
            // we need to set both WIDX and standard as default filters so this widget will remain hidden.
            $standardDefaultType = $this->CI->model('Report')->getSearchFilterTypeDefault(CP_REPORT_DEFAULT)->result;
            $defaultFilters[] = array('name' => 'st', 'defaultValue' => $standardDefaultType['fltr_id']);
        }

        if($displayedSearchType['fltr_id'] && $defaultSearchType['fltr_id'] !== $displayedSearchType['fltr_id']) {
            $displayedFilters[] = $this->getFormattedFilter('st', $displayedSearchType['fltr_id'], Config::getMessage(SEARCH_TYPE_LBL), $this->getFlatData($displayedSearchType['fltr_id'], $displayedSearchType['prompt']));
        }

        //Add in the org filter and assume that the default value is 0 (the value that isn't displayed). Choose a standard filter message
        //using the default messages from the OrgList widget. This can create a problem if the attributes on the widget
        //are changed since these will no longer be in sync, but there is no way to know those values at this point.
        if(($organizationID = $this->CI->session->getProfileData('orgID')) && $organizationID > 0) {
            $defaultOrgFilter = 0;
            $displayedOrgFilter = intval(\RightNow\Utils\Url::getParameter('org'));
            $defaultFilters[] = array('name' => 'org', 'defaultValue' => $defaultOrgFilter);
            if($displayedOrgFilter === 1) {
                $displayedMessage = Config::getMessage(FROM_ANYONE_IN_MY_ORGANIZATION_LBL);
            }
            else if($displayedOrgFilter === 2) {
                $displayedMessage = Config::getMessage(MY_ORGANIZATION_AND_SUBSIDIARIES_LBL);
            }
            if($displayedMessage) {
                $displayedFilters[] = $this->getFormattedFilter('org', $displayedOrgFilter, Config::getMessage(ORGANIZATION_LBL), $this->getFlatData($displayedOrgFilter, $displayedMessage));
            }
        }

        if(empty($displayedFilters)) {
            $this->classList->add('rn_Hidden');
        }

        $this->data['js'] = array(
            'defaultFilters' => $defaultFilters,
            'filters' => $displayedFilters,
            'searchPage' => $searchPage
        );
    }

    /**
     * Utility function to format filter data
     * @param string $urlParameter The URL parameter associated with the filter
     * @param int $filterID ID for the filter
     * @param string $typeLabel Label to use for the filter
     * @param mixed $filterData The data to be applied to the filter
     * @return array Keys include 'urlParameter', 'filterID', 'label' and 'data'
     */
    protected function getFormattedFilter($urlParameter, $filterID, $typeLabel, $filterData) {
        return array(
            'urlParameter' => $urlParameter,
            'filterID' => $filterID,
            'label' => $typeLabel,
            'data' => $filterData,
        );
    }

    /**
     * Utility function to format a filter
     * @param int $filterID ID for the filter
     * @param string $label Label used for the filter
     * @return array Two-dimensional array; Keys include 'id', 'label' and 'linkUrl'
     */
    protected function getFlatData($filterID, $label) {
        return array(array('id' => $filterID, 'label' => $label, 'linkUrl' => 'javascript:void(0)'));
    }

    /**
     * Return hier menu data for a chain
     * @param string $filterName The name of the filter ('p', 'c')
     * @param string $chain Comma seperated list of hier menu ID's
     * @param string $searchPage Path to the search page
     * @return array An array of parents to the root with labels and links attached
     */
    protected function getHierarchyData($filterName, $chain, $searchPage) {
        $chainData = $this->CI->model('Prodcat')->getFormattedChain(($filterName === 'p') ? 'Product' : 'Category', end(explode(',', $chain)))->result;
        foreach($chainData as &$value) {
            $value += array('linkUrl' => "{$searchPage}{$filterName}/{$value['id']}");
        }
        $chainData[count($chainData) - 1]['linkUrl'] = 'javascript:void(0)';
        return $chainData;
    }
}
