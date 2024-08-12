<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class OkcsSimpleSearch extends \RightNow\Widgets\SimpleSearch {
    private $productCategoryApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
        require_once CPCORE . 'Utils/Okcs.php';
    }

    function getData() {
        $filterData = $this->getDataFromFilter();
        $this->data['js']['url_parameters'] = $this->getUrlParameters($this->data['attrs']['add_params_to_url'], $filterData);
        $this->data['js']['placeholder'] = $this->getPlaceholderText($filterData);
        $this->data['js']['filterName'] = isset($filterData['object']) ? $filterData['object']->name : null;
        $this->data['js']['accessType'] = $this->data['attrs']['access_type'];

        $userLocale = \RightNow\Utils\Okcs::getInterfaceLocale();
        $locParam = Url::getParameter('loc');
        $selectedLocale = isset($locParam) ? urldecode($locParam) : null;
        $this->data['js']['defaultLocale'] = str_replace("_", "-", $userLocale);
        $this->data['js']['selected'] = !empty($selectedLocale) ? explode(",", $selectedLocale) : (!isset($userLocale) ? array($this->data['js']['defaultLocale']) : explode(",", $this->data['js']['defaultLocale']));

        $this->data['js']['prodCatFacet'] = '';
        if ($categoryRecordId = Url::getParameter('categoryRecordID')) {
            $searchFacet = null;
            if($prodId = Url::getParameter('p')) {
                $searchFacet = 'CMS-PRODUCT';
            }
            else if($categoryId = Url::getParameter('c')) {
                $searchFacet = 'CMS-CATEGORY_REF';
            }
            if($searchFacet !== null) {
                $levels = $this->getHierarchyObjectForCategory($categoryRecordId);
                for($i = 0; $i < count($levels); $i++) {
                    $searchFacet .= '.' . $levels[$i]['id'];
                }
                $this->data['js']['prodCatFacet'] = $searchFacet;
            }
        }
    }

    /**
     * Builds an array of url parameters
     * @param string $parameterString The value from `add_params_to_url`
     * @param array $filterData An array specifying which filter_type ('categoryRecordID') is being used and the corresponding prodcat object.
     * @return array An array containing the url parameters and corresponding values.
     */
    protected function getUrlParameters($parameterString, array $filterData) {
        $parameters = $this->getParametersFromHelper($parameterString);
        if ($filterData) {
            $parameters[$filterData['parameter']] = $filterData['object']->name;
        }

        return $parameters;
    }

    /**
     * Returns the text to use as the search input's placeholder attribute.
     * @param array $filterData An array specifying which filter_type ('categoryRecordID') is being used and the corresponding prodcat object.
     * @return string The text to use as the search input's placeholder.
     */
    protected function getPlaceholderText(array $filterData) {
        if ($filterData) {
            if ($placeholderLabel = $this->data['attrs']['label_filter_type_placeholder']) {
                return \RightNow\Utils\Text::stringContains($placeholderLabel, '%s')
                    ? sprintf($placeholderLabel, $filterData['object']->name)
                    : $placeholderLabel;
            }
            return '';
        }

        return $this->data['attrs']['label_placeholder'];
    }

    /**
     * Returns either the product or category (determined by 'filter_type') if present in the corresponding url parameter ('categoryRecordID').
     * @return array An array having 'parameter' and 'object' as keys when a product or category is specified, else an empty array.
     */
    protected function getDataFromFilter() {
        $filterData = array();
        if (($filterType = $this->data['attrs']['filter_type']) !== 'none') {
            $parameter = $filterType = 'categoryRecordID';
            if (($filterValue = \RightNow\Utils\Url::getParameter($parameter))
                && ($object = $this->CI->model('Okcs')->getProductCategoryDetails($filterValue))) {
                $filterData = array('parameter' => $parameter, 'object' => $object);
            }
        }

        return $filterData;
    }

    /**
     * Returns an array of prodcat levels for the category record id.
     * @param string $categoryRecordId Category reference key
     * @return array The prodcat levels
     */
    protected function getHierarchyObjectForCategory($categoryRecordId) {
        $prodCat = $this->CI->model('Okcs')->getProductCategoryDetails($categoryRecordId, $this->productCategoryApiVersion);
        $okcs = new \RightNow\Utils\Okcs();
        return $okcs->getCategoryHierarchy($prodCat);
    }
}
