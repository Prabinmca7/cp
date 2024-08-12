<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Libraries\Search;

class SourceProductCategorySearchFilter extends \RightNow\Widgets\ProductCategorySearchFilter {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    /**
     * Retrieves the Search instance for the widget's source id.
     * @return object|boolean The Search library instance or false
     *                            if errors were encountered
     */
    protected function getSearchInstance () {
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
    protected function getLinkedProductID () {
        $productFilter = Search::getInstance($this->data['attrs']['source_id'])->getFilter('product');

        return $productFilter ? $productFilter['value'] : null;
    }

    /**
     * Gets the value of the prod / cat filter.
     * @param string $filterTypeKey Either 'p' or 'c'
     * @return string|bool Filter ID / value or false if there was an error
     */
    protected function getValue ($filterTypeKey = null) {
        if ($search = $this->getSearchInstance()) {
            $filterType = $this->normalizeFilterType();
            $this->data['js']['filter'] = $search->getFilter(strtolower($filterType));
            // Include legacy hm_type value for accessible hierarchy request that the widget's JS may invoke.
            $this->data['js']['hm_type'] = $filterType === self::PRODUCT ? HM_PRODUCTS : HM_CATEGORIES;

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
    protected function displayNotices (array $notices, $warning = false) {
        foreach ($notices as $notice) {
            echo $this->reportError($notice, !$warning);
        }

        return count($notices) > 0;
    }
}
