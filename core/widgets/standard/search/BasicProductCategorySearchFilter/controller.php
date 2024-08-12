<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

class BasicProductCategorySearchFilter extends \RightNow\Libraries\Widget\Base {

    const PRODUCT = 'Product';
    const CATEGORY = 'Category';

    private $pageUrl;

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->pageUrl = "/app/{$this->CI->page}" . Url::getParametersFromList($this->data['attrs']['add_params_to_filter_url']);

        $dataType = $this->data['attrs']['filter_type'] = (Text::stringContains(strtolower($this->data['attrs']['filter_type']), 'prod'))
            ? self::PRODUCT
            : self::CATEGORY;

        if ($dataType === self::CATEGORY) {
            $this->data['attrs']['label_all_values'] = ($this->data['attrs']['label_all_values'] === Config::getMessage(ALL_PRODUCTS_LBL))
                ? Config::getMessage(ALL_CATEGORIES_LBL)
                : $this->data['attrs']['label_all_values'];

            $this->data['attrs']['label_title'] = ($this->data['attrs']['label_title'] === Config::getMessage(SELECT_A_PRODUCT_LBL))
                ? Config::getMessage(SELECT_A_CATEGORY_LBL)
                : $this->data['attrs']['label_title'];
        }

        //Get the active filters on the page to determine the default value
        $filters = array();
        Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $filterType = strtolower($this->data['attrs']['filter_type'][0]);
        if(!$filters[$filterType]->filters->optlist_id) {
            echo $this->reportError(sprintf(Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_D_LBL), $this->data['attrs']['filter_type'], $this->data['attrs']['report_id']));
            return false;
        }

        //Given the current selection, build up a list of links for each element to display a breadcrumb. The selectedItem will
        //either be the number in the URL, or a hyphen signifying no value.
        $selectionData = $levelData = array();
        $showSubItems = true;
        $selectedItems = explode(',', $filters[$filterType]->filters->data[0] ?: Url::getParameter($filterType));
        $selectedItem = end($selectedItems) ?: null;
        //Find a list of all the products on the current level and add in URLs
        if ($showSubItems) {
            $linkingOn = $this->data['attrs']['linking_off'] ? false : $this->CI->model('Prodcat')->getLinkingMode();
            if($linkingOn && $dataType === self::CATEGORY) {
                $selectedProductID = explode(',', $filters['p']->filters->data[0] ?: Url::getParameter('p'));
                $selectedProductID = end($selectedProductID) ?: null;
                //If no product has been selected, but a category has been, get it's children
                if($selectedProductID === null && $selectedItem){
                    $levelData = $this->CI->model('Prodcat')->getDirectDescendants($dataType, $selectedItem)->result;
                }
                else{
                    $linkedCategories = $this->CI->model('Prodcat')->getFormattedTree($dataType, $selectedItems, true, $selectedProductID)->result;
                    $levelData = $linkedCategories[$selectedItem] ?: $linkedCategories[0];
                }
            }
            else{
                $levelData = $this->CI->model('Prodcat')->getDirectDescendants($dataType, $selectedItem)->result;
            }
        }

        $selectionData = ($selectedItem)
            ? $this->CI->model('Prodcat')->getFormattedChain($dataType, $selectedItem)->result
            : array();
        $this->setUrlEndpoints($selectionData);
        $this->data['levelData'] = $this->addUrlKeysAndEscapeLabels($levelData, $filterType);
        $this->data['selectedData'] = $this->addUrlKeysAndEscapeLabels($selectionData, $filterType);
        $this->data['resetUrl'] = Url::deleteParameter($this->pageUrl, $filterType) . Url::sessionParameter();
        $this->data['allowNextStep'] = true;
        //Don't show the search button if the prod/cat is required and the user hasn't selected a value and there are value options to choose from
        if($this->data['attrs']['required'] && empty($this->data['selectedData']) && !empty($this->data['levelData'])){
            $this->data['allowNextStep'] = false;
        }
    }

    /**
     * Sets the URLs for the form buttons to perform a search and clear the search filters.
     * @param array|null $selectionData Currently selected data
     */
    protected function setUrlEndpoints($selectionData){
        $currentPage = Url::getShortEufAppUrl('sameAsCurrentPage', $this->CI->page);
        $this->data['applyUrl'] = $this->data['attrs']['report_page_url'] ?: $currentPage;
        $this->data['applyUrl'] .= Url::getParametersFromList($this->data['attrs']['add_params_to_search_url']) . Url::sessionParameter();

        $productValueToAdd = $categoryValueToAdd = null;

        if(($productValueToAdd = Url::getParameter('p')) === null && $this->data['attrs']['filter_type'] === self::PRODUCT && $selectionData){
            $lastProduct = end($selectionData);
            $productValueToAdd = $lastProduct['id'];
        }
        if(($categoryValueToAdd = Url::getParameter('c')) === null && $this->data['attrs']['filter_type'] === self::CATEGORY && $selectionData){
            $lastCategory = end($selectionData);
            $categoryValueToAdd = $lastCategory['id'];
        }

        if($productValueToAdd){
            $this->data['applyUrl'] = Url::addParameter($this->data['applyUrl'], 'p', $productValueToAdd);
        }
        if($categoryValueToAdd){
            $this->data['applyUrl'] = Url::addParameter($this->data['applyUrl'], 'c', $categoryValueToAdd);
        }
    }

    /**
     * Sets the URLs for each of the product/categories that are being displayed as well as HTML escapes the prod/cat labels.
     * @param array|null $items List of products or categories
     * @param string $filterParameter Parameter key of filter, either 'p' or 'c'
     */
    protected function addUrlKeysAndEscapeLabels($items, $filterParameter) {
        $augmentedItems = array();
        if (is_array($items)) {
            foreach ($items as $item) {
                if ((isset($item['hasChildren']) && $item['hasChildren'] === true)) {
                    $item['url'] = Url::addParameter($this->pageUrl, $filterParameter, $item['id']) . Url::sessionParameter();
                }
                else {
                    $item['url'] = Url::addParameter($this->data['applyUrl'], $filterParameter, $item['id']);
                }
                $item['label'] = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
                $augmentedItems[] = $item;
            }
        }
        return $augmentedItems;
    }
}
