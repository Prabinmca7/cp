<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Libraries\Search;

class MobileSourceProductCategorySearchFilter extends \RightNow\Widgets\ProductCategorySearchFilter  {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    /**
     * Retrieves the Search instance for the widget's source id.
     * @return object|boolean The Search library instance or false
     *                            if errors were encountered
     */
    protected function getSearchInstance() {
        $search = Search::getInstance($this->data['attrs']['source_id']);

        if ($this->displayNotices($search->getErrors())) {
            return false;
        }
        $this->displayNotices($search->getWarnings(), true);

        return $search;
    }

    /**
     * Gets the current product value, if any.
     * @return string|null Filter ID / value or null
     */
    protected function getLinkedProductID() {
        $productFilter = Search::getInstance($this->data['attrs']['source_id'])->getFilter('product');

        return $productFilter ? $productFilter['value'] : null;
    }

    /**
     * Gets the value of the prod / cat filter.
     * @param string $filterTypeKey Either 'p' or 'c'
     * @return string|bool Filter ID / value or false if there was an error
     */
    protected function getValue($filterTypeKey = null) {
        if ($search = $this->getSearchInstance()) {
            $this->data['js']['searchPage'] = $this->data['attrs']['search_results_url'] ? $this->data['attrs']['search_results_url'] : "/app/{$this->CI->page}";
            $this->data['js']['filter'] = $search->getFilter(strtolower($this->data['attrs']['filter_type']));
            // Include legacy hm_type value for accessible hierarchy request that the widget's JS may invoke.
            $this->data['js']['hm_type'] = isset($this->data['js']['filterType']) && $this->data['js']['filterType'] === self::PRODUCT ? HM_PRODUCTS : HM_CATEGORIES;

            // Refetch the search instance to verify that the call to getFilter() did not cause an error.
            if ($this->getSearchInstance() === false)
                return false;

            return $this->data['js']['filter']['value'];
        }

        return false;
    }

    /**
     * Outputs error or warning messages.
     * @param  array  $notices Messages
     * @param  boolean $warning Whether to consider the messages warnings
     * @return boolean          Whether anything was output
     */
    protected function displayNotices(array $notices, $warning = false) {
        foreach ($notices as $notice) {
            echo $this->reportError($notice, !$warning);
        }

        return count($notices) > 0;
    }

    /**
     * All of the label defaults are for products - if the filter type is category, see if
     * the labels have the product default value and replace them with the category default value
     * otherwise, the attribute values were modified and should persist.
     */
    protected function setLabelDefaults() {
        if ($this->data['attrs']['filter_type'] === self::CATEGORY) {
            $this->data['attrs']['label_all_values'] =
                ($this->data['attrs']['label_all_values'] === \RightNow\Utils\Config::getMessage(ALL_PRODUCTS_LBL))
                ? \RightNow\Utils\Config::getMessage(ALL_CATEGORIES_LBL)
                : $this->data['attrs']['label_all_values'];

            $this->data['attrs']['label_input'] =
                ($this->data['attrs']['label_input'] === \RightNow\Utils\Config::getMessage(FILTER_BY_PRODUCT_LBL))
                ? \RightNow\Utils\Config::getMessage(FILTER_BY_CATEGORY_LBL)
                : $this->data['attrs']['label_input'];

            $this->data['attrs']['label_prompt'] =
                ($this->data['attrs']['label_prompt'] === \RightNow\Utils\Config::getMessage(SELECT_A_PRODUCT_LBL))
                ? \RightNow\Utils\Config::getMessage(SELECT_A_CATEGORY_LBL)
                : $this->data['attrs']['label_prompt'];
        }
    }
}
