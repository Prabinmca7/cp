<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ProductCategoryBreadcrumb extends \RightNow\Libraries\Widget\Base {
    private static $paramKeys = array(
        'product' => 'p',
        'category' => 'c',
    );

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['paramKey'] = self::$paramKeys[$this->data['attrs']['type']];
        if (!$this->data['levels'] = $this->getLevels()) {
            return false;
        }

        $this->applyDefaultCategoryLabel();
    }

    /**
     * Returns an array of prodcat levels as specified by the url parameter, with the current and/or
     * first item pruned according to the 'display_current_item' and 'display_first_item' attributes.
     * @return array The pruned prodcat levels
     */
    protected function getLevels() {
        if ($id = $this->getIDFromEnvironment($this->data['paramKey'])) {
            $levels = $this->CI->model('Prodcat')->getFormattedChain($this->data['attrs']['type'], $id)->result;
        }
        else if ($answerID = Url::getParameter('a_id')) {
            $levels = $this->getChainFromAssociatedObject('Answer', $answerID);
        }
        else if ($questionID = Url::getParameter('qid')) {
            $levels = $this->getChainFromAssociatedObject('CommunityQuestion', $questionID);
        }

        if ($count = count($levels)) {
            $displayCurrent = $this->data['attrs']['display_current_item'];
            if ($count === 1 && (!$this->data['attrs']['display_first_item'] || !$displayCurrent)) {
                $levels = array();
            }
            else if ($count > 1 && !$displayCurrent) {
                array_pop($levels);
            }
        }

        return $levels ?: array();
    }

    /**
     * Returns the value for the given parameter key.
     * @param String $paramKey A key of 'p' or 'c'
     * @return String|null Value or null if the parameter doesn't exist in the URL
     */
    protected function getIDFromEnvironment($paramKey) {
        return \RightNow\Utils\Text::extractCommaSeparatedID(Url::getParameter($paramKey));
    }

    /**
     * Gets the prod/cat chain from the associated object.
     * @param String $modelName Name of model for associated object (Answer|CommunityQuestion)
     * @param Int $id ID of object
     * @return Array Hierarchy chain
     */
    protected function getChainFromAssociatedObject ($modelName, $id) {
        if (!$modelName || !$id || !($primaryObject = $this->CI->model($modelName)->get($id)->result)) return;
        $levels = array();

        $dataType = ucfirst($this->data['attrs']['type']);
        if ($modelName === 'Answer') {
            $dataType .= 's';
        }
        $prodCat = $primaryObject->{$dataType};

        if ($primaryObject && $prodCat) {
            if ($modelName === 'CommunityQuestion') {
                $prodCat = array($prodCat);
            }
            $levels = $this->getCommonAncestorChain($prodCat);
        }

        $lastItem = end($levels);
        return $lastItem ? $this->CI->model('Prodcat')->getFormattedChain($this->data['attrs']['type'], $lastItem['id'])->result : array();
    }

    /**
     * For the given ServiceProductArray / ServiceCategoryArray,
     * returns the common ancestor chain for the items within.
     * Example:
     *
     *      Given:
     *
     *      [ { ID: 5, LookupName: ..}, { ID: 6, LookupName: ..} ]
     *
     *      Where the hierarchy exists:
     *
     *      1 > 2 > 3 > 5
     *      1 > 2 > 3 > 6
     *
     *      Result:
     *
     *      [ { id: 1, label: 'prod1' }, { id: 2, label: 'prod2' }]
     *
     * @param Object $items Array (or ServiceProductArray|ServiceCategoryArray)
     *      that contains product or category Connect objects
     * @return Array Array containing ancestor chain (each item has 'id' and 'label' keys)
     */
    protected function getCommonAncestorChain($items) {
        if (!count($items)) return array();

        foreach ($items as $item) {
            $allValues []= $this->getParentChain($item);
        }

        if (count($allValues) > 1) {
            return call_user_func_array('array_uintersect', array_merge($allValues, array(function ($a, $b) {
                if ($a['id'] < $b['id']) return -1;
                if ($a['id'] > $b['id']) return 1;
                return 0;
            })));
        }

        return $allValues[0] ?: array();
    }

    /**
     * Builds up a hierarchy chain for the given ServiceProduct / ServiceCategory
     * @param Object $hierarchyItem ServiceProduct / ServiceCategory
     * @param array $chain Caller leaves empty
     * @return Array Beginning with root-most item, each item has 'id' and 'label' keys
     */
    protected function getParentChain($hierarchyItem, array $chain = array()) {
        array_unshift($chain, array('label' => $hierarchyItem->Name, 'id' => $hierarchyItem->ID));

        if ($hierarchyItem->Parent) {
            return $this->getParentChain($hierarchyItem->Parent, $chain);
        }

        return $chain;
    }

    /**
     * Changes the `label_screenreader_intro` attribute value if the widget's type is "category"
     * and the `label_screenreader_intro` value is left at its default (product) value.
     */
    protected function applyDefaultCategoryLabel() {
        if ($this->data['attrs']['type'] === 'category' && $this->data['attrs']['label_screenreader_intro'] === $this->attrs['label_screenreader_intro']->default) {
            $this->data['attrs']['label_screenreader_intro'] = \RightNow\Utils\Config::getMessage(CURRENT_CATEGORY_HIERARCHY_LBL);
        }
    }
}
