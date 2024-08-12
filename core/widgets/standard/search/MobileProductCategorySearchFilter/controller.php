<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text;

class MobileProductCategorySearchFilter extends \RightNow\Libraries\Widget\Base {
    const PRODUCT = 'Product';
    const CATEGORY = 'Category';

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['attrs']['filter_type'] = (Text::stringContains(strtolower($this->data['attrs']['filter_type']), 'prod'))
            ? self::PRODUCT
            : self::CATEGORY;

        // all of the label defaults are for products - if the filter type is category, see if
        // the labels have the product default value and replace them with the category default value
        // otherwise, the attribute values were modified and should persist
        if ($this->data['attrs']['filter_type'] === self::CATEGORY)
        {
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

            $this->data['attrs']['label_filter_type'] =
                ($this->data['attrs']['label_filter_type'] === \RightNow\Utils\Config::getMessage(PRODUCTS_LBL))
                ? \RightNow\Utils\Config::getMessage(CATEGORIES_LBL)
                : $this->data['attrs']['label_filter_type'];
        }

        $filters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);

        $filterType = strtolower($this->data['attrs']['filter_type'][0]);
        $defaultValue = $filters[$filterType]->filters->data[0];
        $defaultValue = ($defaultValue) ? explode(',', $defaultValue) : array();

        $optlistID = $filters[$filterType]->filters->optlist_id;
        if(!$optlistID) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_D_LBL), $this->data['attrs']['filter_type'], $this->data['attrs']['report_id']));
            return false;
        }

        $this->data['js'] = array(
            'name' => isset($filters[$filterType]->filters->name) ? $filters[$filterType]->filters->name : null,
            'oper_id' => isset($filters[$filterType]->filters->oper_id) ? $filters[$filterType]->filters->oper_id : null,
            'fltr_id' => isset($filters[$filterType]->filters->fltr_id) ? $filters[$filterType]->filters->fltr_id : null,
            'linkingOn' => $this->data['attrs']['linking_off'] ? 0 : $this->CI->model('Prodcat')->getLinkingMode(),
            'report_def' => isset($filters[$filterType]->report_default) ? $filters[$filterType]->report_default : null,
            'searchName' => $filterType,
            'hm_type' => $filterType === 'p' ? HM_PRODUCTS : HM_CATEGORIES,
            'searchPage' => $this->data['attrs']['report_page_url'] ? $this->data['attrs']['report_page_url'] . '/' : "/app/{$this->CI->page}/"
        );

        //if linking is on we need to get all values for prods as well as cats
        if($filterType === 'c' && $this->data['js']['linkingOn'])
            $selectedProds = ($filters['p']) && isset($filters['p']->filters->data[0]) ? explode(',', $filters['p']->filters->data[0]) : null;

        $this->data['firstLevel'] = array(); //only populated with the first-level items and only passed to the view

        //if this is the categories widget AND there's some pre-selected product(s) AND linking is on -> get prod linking defaults.
        //Otherwise just get the data normally
        $defaultSelection = (isset($selectedProds) && $selectedProds[0])
                                ? $this->_setProdLinkingDefaults($this->data['firstLevel'], $selectedProds, $defaultValue)
                                : $this->_setDefaults($this->data['firstLevel'], $defaultValue);

        if($defaultSelection) {
            $this->data['js']['initial'] = $defaultSelection;
        }

        //If there are no products or categories either don't render the widget at all, or if it's possible that it will display later, just hide it.
        if(empty($this->data['firstLevel'])) {
            if($this->data['js']['linkingOn'] && $filterType === 'c') {
                $this->classList->add('rn_HideEmpty');
            }
            else {
                return false;
            }
        }
    }

    /**
     * Utility function to retrieve hier menus and massage the data for our usage.
     * @param array|null &$firstLevelItems To be populated with the first-level of items
     * @param array|null $hierItems List of hier menu IDs
     * @return array|bool Populated array containing the pre-selected items; False if there's an error.
     */
    protected function _setDefaults(&$firstLevelItems, $hierItems) {
        $selection = array(); //populated list of what items are already chosen via URL parameter values
        $model = $this->CI->model('Prodcat');

        if ($hierItems) {
            // Get the hierarchy chain for the specified ids.
            $lastItem = end($hierItems);
            if (!$selection = $model->getFormattedChain($this->data['attrs']['filter_type'], $lastItem)->result) {
                return false;
            }
        }
        if (!$firstLevelItems = $model->getDirectDescendants($this->data['attrs']['filter_type'])->result) {
            return false;
        }

        if ($selection) {
            $firstLevelSelectedItem = $selection[0]['id'];
            foreach ($firstLevelItems as &$item) {
                if ($item['id'] == $firstLevelSelectedItem) {
                    $item['selected'] = true;
                    break;
                }
            }
        }

        //add an additional 'no value' node to the front
        array_unshift($firstLevelItems, array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));

        return $selection;
    }

    /**
     * Utility function to retrieve hier menus for prod linking and massage the data for our usage.
     * @param array|null &$firstLevelItems To be populated with the first-level of items
     * @param array|null $productArray List of product hier menu IDs
     * @param array|null $catArray List of category hier menu IDs
     * @return array Populated array containing the pre-selected items
     */
    protected function _setProdLinkingDefaults(&$firstLevelItems, $productArray, $catArray) {
        //selectedProds is an array of 0 - 5
        //find the last product in selectedProds
        $productArray = array_filter($productArray);
        $lastProdID = end($productArray);

        $selection = array(); //populated list of what items are already chosen via URL parameter values
        $hierList = '';

        if (!($lastProdId = end($productArray)) || !($hierArray = $this->CI->model('Prodcat')->getLinkedCategories($lastProdId)->result))
            return false;

        ksort($hierArray);
        $matchIndex = 0;
        foreach($hierArray as $parentID => $child) {
            if(!count($child)) {
                //for some reason there's empty arrays floating around...
                unset($hierArray[$parentID]);
                continue;
            }
            foreach($child as $dataArray) {
                $id = $dataArray['id'];
                if(isset($catArray[$matchIndex]) && $id === intval($catArray[$matchIndex])) {
                    $selected = true;
                    $matchIndex++;
                    $hierList .= $id;
                    $selection []= $dataArray + array('hierList' => $hierList);
                    $hierList .= ',';
                }
                else {
                    $selected = false;
                }
                if($parentID === 0) {
                    //only want to pass first-level items to the view
                    $firstLevelItems []= $dataArray + array('selected' => $selected);
                }
            }
        }
        //add an additional 'no value' node to the front
        array_unshift($firstLevelItems, array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        $this->data['js']['linkMap'] = $hierArray;
        return $selection;
    }
}
