<?php

namespace RightNow\Widgets;

use RightNow\Utils\Text;

class ProductCategorySearchFilter extends \RightNow\Libraries\Widget\Base {
    const PRODUCT = 'Product';
    const CATEGORY = 'Category';

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // also set this.data.js.data_type for consistency with input prodcat widgets
        $this->data['attrs']['filter_type'] = $this->data['js']['data_type'] = $this->normalizeFilterType();
        $filterTypeReportKey = strtolower($this->data['attrs']['filter_type'][0]);

        $filterValue = $this->getValue($filterTypeReportKey);

        if ($filterValue === false)
            return false;

        $this->setLabelDefaults();

        $this->data['js']['readableProdcatIds'] = $this->data['js']['readableProdcatIdsWithChildren'] = array();

        $this->data['js']['linkingOn'] = $this->productLinkingIsEnabled();
        $this->data['js']['formattedChain'] = $this->getFormattedChain();

        $this->data['js']['initial'] = array();
        if ($filterValue && count($this->data['js']['formattedChain']) > 0)
            $this->data['js']['initial'] = explode(',', $filterValue);

        $this->data['js']['hierData'] = $this->getTreeData($this->data['js']['initial'], $this->data['attrs']['filter_type']);

        $this->data['js']['link_map'] = array(array());
        if ($this->data['js']['linkingOn']) {
            if($this->data['attrs']['filter_type'] === 'Product') {
                $filterLastValue = null;
                if($this->data['js']['filter']['value']) {
                    $filter = explode(',', $this->data['js']['filter']['value']);
                    $filterLastValue = $filter[count($filter) - 1];
                }
                $linkedCategories = $this->CI->model('Prodcat')->getLinkedCategories($filterLastValue)->result;
                \RightNow\Utils\Framework::setCache('ProductCategorySearchFilter_cachedLinkedCategories', $linkedCategories);
            }
            else {
                $cachedLinkedCategories = \RightNow\Utils\Framework::checkCache('ProductCategorySearchFilter_cachedLinkedCategories');
                if(count($cachedLinkedCategories) > 0) {
                    $selectedProdcats = array();
                    foreach($this->data['js']['hierData'] as $hierarchyGroup) {
                        foreach($hierarchyGroup as $prodcat) {
                            if(isset($prodcat['selected']) && $prodcat['selected'])
                                $selectedProdcats []= $prodcat['id'];
                        }
                    }

                    foreach($cachedLinkedCategories as &$linkedProdcatGroup) {
                        foreach($linkedProdcatGroup as &$linkedProdcat) {
                            $linkedProdcat['selected'] = in_array($linkedProdcat['id'], $selectedProdcats);
                        }
                    }

                    $this->data['js']['link_map'] = $cachedLinkedCategories;
                }
                array_unshift($this->data['js']['link_map'][0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
            }
        }
        // If hierData is empty or prodcats are not readable, no reason to render the widget.
        if(count($this->data['js']['hierData']) === 0)
            return false;
    }

    /**
     * Determines whether the product linking feature is enabled
     * and caches the result.
     * @return boolean T if product linking is enabled
     */
    protected function productLinkingIsEnabled () {
        static $cachedResult;

        if (is_null($cachedResult))
            $cachedResult = $this->data['attrs']['linking_off'] ? false : $this->CI->model('Prodcat')->getLinkingMode();

        return $cachedResult;
    }

    /**
     * Gets the initial set of tree view data.
     * @param  array  $chainOfValues Current selected value chain
     * @param  string $dataType      Product or Category
     * @return array                Initial set of tree view data
     */
    protected function getTreeData (array $chainOfValues, $dataType) {
        $values = array();
        $linkedProductOrCategoryID = $this->getLinkedID($dataType);

        if($this->data['attrs']['verify_permissions']) {
            $readableProdcatHierarchy = $this->CI->model('Prodcat')->getPermissionedListSocialQuestionRead($dataType === self::PRODUCT)->result;
            // If $readableProdcatHierarchy is null there are no readable prodcats
            if(is_null($readableProdcatHierarchy))
                return array();
            // If $readableProdcatHierarchy is an array, $this->data['js']['readableProdcatIds']
            // is set to a list of readable product IDs. If $readableProdcatHierarchy is not an
            // array and not null, $this->data['js']['readableProdcatIds'] is kept empty, which
            // when $this->data['attrs']['verify_permissions'] is true, implies all products are
            // readable.
            if(is_array($readableProdcatHierarchy))
                list($this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']) = $this->getProdcatInfoFromPermissionedHierarchies($readableProdcatHierarchy);
        }

        if(count($chainOfValues) === 1) {
            $chainForTree = array();
            foreach($this->CI->model('Prodcat')->getFormattedChain($dataType, $linkedProductOrCategoryID)->result as $ancestor) {
                $chainForTree []= $ancestor['id'];
            }
            $chainOfValues = $chainForTree;
        }
        $values = $this->CI->model('Prodcat')->getFormattedTree($dataType, $chainOfValues)->result;
        if($this->data['attrs']['verify_permissions'] && is_array($this->data['js']['readableProdcatIds']) && count($this->data['js']['readableProdcatIds']) > 0)
            $this->updateProdcatsForReadPermissions($values, $this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']);

        if ($this->data['attrs']['enable_prod_cat_no_value_option'])
            array_unshift($values[0], array('id' => -1, 'label' => $this->data['attrs']['label_no_value'], 'selected' => (int)$this->data['js']['filter']['value'] === -1 ? true : false));

        $this->prependAllValueNode($values);
        $this->prependAllValueNode($this->data['js']['link_map']);
        return $values;
    }

    /**
     * Retrieve formatted chain for selected item
     * @return array Formatted chain of readable products or categories
     */
    protected function getFormattedChain() {
        if (!$this->data['js']['filter'] || !$this->data['js']['filter']['value']) {
            return array();
        }

        $filter = explode(',', $this->data['js']['filter']['value']);
        if ($this->data['attrs']['enable_prod_cat_no_value_option'] && (int)$filter[0] === -1) {
            return array(array('id' => -1, 'label' => $this->data['attrs']['label_no_value']));
        }
        $filterLength = count($filter);
        $selected = ($filterLength === 1) ? $filter[0] : $filter[$filterLength - 1];

        $chain = $this->CI->model('Prodcat')->getFormattedChain($this->data['attrs']['filter_type'], $selected)->result;
        if (!$this->isChainReadable($chain)) {
            $chain = array();
        }

        return $chain;
    }

    /**
     * For a given prodcat chain verify that its items are readable.
     * @param array $chain Chain of products or categories
     * @return boolean Whether the chain of products or categories is readable. This
     * function will return false if any part of the chain is not readable.
     */
    protected function isChainReadable(array $chain) {
        if (count($this->data['js']['readableProdcatIds']) > 0) {
            foreach ($chain as $prodcat) {
                if (!in_array($prodcat['ID'], $this->data['js']['readableProdcatIds'])) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Gets the value of the prod / cat filter.
     * @param  string $filterTypeKey Either 'p' or 'c'
     * @return string|false          Filter ID / value
     */
    protected function getValue ($filterTypeKey) {
        //Get the active filters on the page to determine the default value
        $filters = $this->getReportFilters();

        $filterType = strtolower($this->data['attrs']['filter_type']);
        $filterKey = $filterType[0];
        $this->data['js']['filter'] = array(
            'type'  => $filterType,
            'key'   => $filterKey,
            'value' => $filters[$filterKey]->filters->data[0]
        );

        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        if(!$filters[$filterTypeKey]->filters->optlist_id) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_D_LBL), $this->data['attrs']['filter_type'], $this->data['attrs']['report_id']));
            return false;
        }

        $this->populateJSStateForReport($filters, $filterTypeKey);

        return $filters[$filterTypeKey]->filters->data[0];
    }

    /**
     * Sets js properties to report state properties.
     * @param  array $filters    Report filters
     * @param  string $filterTypeKey Either 'p' or 'c'
     */
    protected function populateJSStateForReport (array $filters, $filterTypeKey) {
        $filter = $filters[$filterTypeKey];
        $this->data['js']['searchName'] = $filterTypeKey;
        $this->data['js']['hm_type'] = ($filterTypeKey === 'p') ? HM_PRODUCTS : HM_CATEGORIES;
        $this->data['js']['oper_id'] = $filter->filters->oper_id;
        $this->data['js']['fltr_id'] = $filter->filters->fltr_id;
        $this->data['js']['report_def'] = $filter->report_default;
    }

    /**
     * Converts whatever was specified for the filter_type attribute into a
     * string value that's expected everywhere.
     * @return string Product or Category
     */
    protected function normalizeFilterType () {
        return (Text::stringContains(strtolower($this->data['attrs']['filter_type']), 'prod'))
            ? self::PRODUCT
            : self::CATEGORY;
    }

    /**
     * Gets the current product or category ID, if any.
     * @param string $type Type of ID to get, either 'Product' or 'Category'
     * @return string|null Filter ID / value or null
     */
    protected function getLinkedID ($type = 'Product') {
        $filtersKey = ($type === 'Product') ? 'p' : 'c';
        $filters = $this->getReportFilters();
        $explodedFilters = explode(',', isset($filters[$filtersKey]->filters->data[0]) ? $filters[$filtersKey]->filters->data[0] : '');
        return end($explodedFilters) ?: null;
    }

    /**
     * Gets filter values from the report definition and the URL parameters
     * received in the request.
     * @return array Report filters
     */
    protected function getReportFilters() {
        static $filters;

        if (is_null($filters)) {
            $filters = array();
            \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        }

        return $filters;
    }

    /**
     * Adds a 'all values' node element to the front of the given array.
     * @param  array &$hierarchyValues Array of hierarchy values to modify
     * @return bool                  T if $hierarchyValues was modified, F
     *                                 if $hierarchyValues was falsy an unmodified
     */
    protected function prependAllValueNode (&$hierarchyValues) {
        $allValueNode = array('id' => 0, 'label' => $this->data['attrs']['label_all_values']);

        if ($hierarchyValues) {
            array_unshift($hierarchyValues[0], $allValueNode);
            return true;
        }

        return false;
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

            $this->data['attrs']['label_nothing_selected'] =
                ($this->data['attrs']['label_nothing_selected'] === \RightNow\Utils\Config::getMessage(SELECT_A_PRODUCT_LBL))
                ? \RightNow\Utils\Config::getMessage(SELECT_A_CATEGORY_LBL)
                : $this->data['attrs']['label_nothing_selected'];
        }
    }

    /**
     * Returns two single-dimensional arrays representing information about readable
     * products from a permission hierarchy array. First array contains unique readable
     * product ids from the permission hierarchy. The second array contains unique
     * readable product ids which have readable children.
     * @param array $prodcatHierarchies An array of arrays of prodcat hierarchies
     * @return array An array containing two arrays; first one is a list of readable
     *    product ids, the second is readable product ids with readable childen.
     */
    protected function getProdcatInfoFromPermissionedHierarchies(array $prodcatHierarchies) {
        $productCatIds = $prodcatIdsWithChildren = array();

        foreach($prodcatHierarchies as $prodcatHierarchy) {
            for($i = 1; $i < 7; $i++) {
                // Hierarchy data has 6 'levels', represented with the keys 'Level1', 'Level2',
                // all the way up to 'Level6'.
                $level = 'Level' . $i;
                if(!$prodcatHierarchy[$level])
                    break;

                $productCatIds []= (int) $prodcatHierarchy[$level];
                if($prodcatHierarchy['Level' . ($i + 1)])
                    $prodcatIdsWithChildren []= (int) $prodcatHierarchy[$level];
            }
        }

        return array(
            array_values(array_unique($productCatIds)),
            array_values(array_unique($prodcatIdsWithChildren))
        );
    }

    /**
     * Removes non-readable prodcats from an array representing a (presumably readable) prodcat
     * hierarchy, and updates the 'hasChildren' attributes of contained prodcats pertaining to
     * whether or not they have readable children.
     * @param array &$prodcats An array representing a prodcat hierarchy.
     * @param array $readableProdcatIds An single-dimensional array of prodcat ids for which to remove.
     * @param array $readableProdcatIdsWithChildren An single-dimensional array of readable prodcat ids
     *    which have readable childen.
     */
    protected function updateProdcatsForReadPermissions(array &$prodcats, array $readableProdcatIds, array $readableProdcatIdsWithChildren) {
        foreach($prodcats as &$prodcatGroup) {
            foreach($prodcatGroup as $prodcatKey => &$prodcat) {
                if(!in_array($prodcat['id'], $readableProdcatIds)) {
                    unset($prodcatGroup[$prodcatKey]);
                }
                else if(in_array($prodcat['id'], $readableProdcatIdsWithChildren)) {
                    $prodcat['hasChildren'] = true;
                }
                else {
                    $prodcat['hasChildren'] = null;
                }
            }
        }
    }
}
