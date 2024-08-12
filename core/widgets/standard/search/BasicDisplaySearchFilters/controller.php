<?php

namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Config,
    \RightNow\Utils\Text;

class BasicDisplaySearchFilters extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $this->data['filters'] = array(
            $this->getFormattedFilter('p', $this->data['attrs']['label_product_title'], $this->getHierarchyData('p', trim($filters['p']->filters->data[0]))),
            $this->getFormattedFilter('c', $this->data['attrs']['label_category_title'], $this->getHierarchyData('c', trim($filters['c']->filters->data[0])))
        );
    }

    /**
     * Returns the details for all products or categories given the ID chain
     * @param string $filterName Type of filter, either 'p' or 'c'.
     * @param string $chain Comma-separated list of product or category IDs e.g. 1,4,10
     * @return array Array of hierarchy containing labels and search URLs
     */
    protected function getHierarchyData($filterName, $chain) {
        if (($chain = explode(',', $chain)) && ($ID = end($chain)) && ($chainData = $this->CI->model('Prodcat')->getFormattedChain(($filterName === 'p') ? 'Product' : 'Category', $ID)->result)) {
            $currentUrl = Url::getShortEufAppUrl('sameAsCurrentPage', $this->CI->page) . Url::getParametersFromList($paramsToAddToUrl, array(($filterName !== 'p') ? 'p' : 'c'));

            foreach($chainData as &$value) {
                $value += array('linkUrl' => Url::addParameter($currentUrl, $filterName, $value['id']) . Url::sessionParameter());
            }
            //Remove URL from the last item, since clicking on it would just refresh the page
            unset($chainData[count($chainData) - 1]['linkUrl']);
            return $chainData;
        }
        return array();
    }

    /**
     * Returns an array of details for each filter to display to the user
     * @param string $urlParameter Key to use as a URL parameter for the filter
     * @param string $typeLabel Label to use for the filter
     * @param array|null $filterData List of data to display for this filter
     * @return array Easily consumable array of all data to display for the selected filter
     */
    protected function getFormattedFilter($urlParameter, $typeLabel, $filterData) {
        return array(
            'urlParameter' => $urlParameter,
            'label' => $typeLabel,
            'data' => $filterData,
        );
    }

    /**
     * Returns the URL to remove the current filter from the search
     * @param string $filterName Name of filter parameter to remove
     * @return string URL with filter parameter removed
     */
    public function getRemovalUrl($filterName) {
        $url = Url::getShortEufAppUrl('sameAsCurrentPage', $this->CI->page) . Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        return Url::deleteParameter($url, $filterName) . Url::sessionParameter();
    }
}
