<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Okcs;

class OkcsProductCategoryBreadcrumb extends \RightNow\Widgets\ProductCategoryBreadcrumb {
    private $productCategoryApiVersion = 'v1';
    private $answerViewApiVersion = 'v1';

    private static $okcsParamKeys = array(
        'product' => 'p',
        'category' => 'c',
    );

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['paramKey'] = 'categoryRecordID';
        $this->data['isAnswerDetail'] = false;
        if (!$this->data['levels'] = $this->getLevels()) {
            return false;
        }
        $this->data['paramKeyPc'] = self::$okcsParamKeys[$this->data['attrs']['type']];
        $this->applyDefaultCategoryLabel();
    }

    /**
     * Returns an array of prodcat levels as specified by the url parameter, with the current and/or
     * first item pruned according to the 'display_current_item' and 'display_first_item' attributes.
     * @return array The pruned prodcat levels
     */
    protected function getLevels() {
        $levels = array();
        if ($categoryRecordId = Url::getParameter('categoryRecordID')) {
            $levels = $this->getHierarchyObjectForCategory($categoryRecordId);
        }
        else if ($answerID = Url::getParameter('a_id')) {
            $this->data['isAnswerDetail'] = true;
            $levels = $this->getHierarchyObjectForAnswer();
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
     * Returns an array of prodcat levels for the category record id.
     * @param string $categoryRecordId Category reference key
     * @return array The prodcat levels
     */
    protected function getHierarchyObjectForCategory($categoryRecordId) {
        $prodCat = $this->CI->model('Okcs')->getProductCategoryDetails($categoryRecordId, $this->productCategoryApiVersion);
        $okcs = new \RightNow\Utils\Okcs();
        return $okcs->getCategoryHierarchy($prodCat);
    }

    /**
     * Returns an array of prodcat levels for the the current answer.
     * @return array The prodcat levels
     */
    protected function getHierarchyObjectForAnswer() {
        $prodCatList = null;
        $prodList = array();
        $docID = Url::getParameter('a_id');
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $attributes = $this->data['attrs'];
        $answerID = !is_null(Url::getParameter('s')) ? Text::getSubstringBefore(Url::getParameter('s'), '_') : null;
        $searchSession = !is_null(Url::getParameter('s')) ? Text::getSubstringAfter(Url::getParameter('s'), '_') : null;
        $externalType = $this->data['attrs']['type'];
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData($docID, $locale, $searchData, $answerData, $this->answerViewApiVersion);

        if($answer['categories'] && count($answer['categories']) > 0) {
            $categList = array();
            for($i = 0; $i < count($answer['categories']); $i++) {
                if($answer['categories'][$i]->externalType === strtoupper($externalType)) {
                    array_push($categList, $answer['categories'][$i]->referenceKey);
                    $this->data['attrs']['type'] = strtolower($answer['categories'][$i]->externalType);//Over-write the OOTB type value for the Answer flow
                }
            }
            if($categList) {
                $prodCatList = $this->CI->model('Okcs')->getProductCategoryListDetails($categList, $this->productCategoryApiVersion);
            }
        }

        $categoryHierList = array();
        if(isset($prodCatList->items) && is_array($prodCatList->items) && count($prodCatList->items) > 0) {
            $prodList = array();
            $prodUrl = '/app/'. \RightNow\Utils\Config::getConfig(CP_PRODUCTS_DETAIL_URL);
            $categUrl = '/app/'. \RightNow\Utils\Config::getConfig(CP_CATEGORIES_DETAIL_URL);
            for($i = 0; $i < count($prodCatList->items); $i++) {
                $prodCat = $prodCatList->items[$i];
                $levels = array();
                $type = isset($prodCat->parents[0]->externalType) ? $prodCat->parents[0]->externalType : null;
                $this->data['attrs']['link_url'] = (!is_null($type) && (strtolower($type) === 'product')) ? $prodUrl : $categUrl;
                if(isset($prodCat->parents) && $prodCat->parents && count($prodCat->parents) > 0) {
                    for($j = 0; $j < count($prodCat->parents); $j++) {
                        array_push($levels, array("id" => $prodCat->parents[$j]->referenceKey, "label" => $prodCat->parents[$j]->name, "externalId" => $prodCat->parents[$j]->externalId));//**TODO BC extId changes needed here also
                    }
                }
                array_push($levels, array("id" => $prodCat->referenceKey, "label" => $prodCat->name, "externalId" => $prodCat->externalId));
                array_push($prodList, $levels);
                
                if($prodList) {
                    array_push($categoryHierList, $prodList);
                }
            }
        }
        return $levels = $this->getCommonAncestorChain($prodList);
    }

    /**
     * For the given product/category hierarchy list, returns the common ancestor chain for the items within.
     * Example:
     *      Where the hierarchy exists:
     *
     *      1 > 2 > 3 > 5
     *      1 > 2 > 3 > 6
     *
     *      Result:
     *
     *      [ { id: 1, label: 'prod1' }, { id: 2, label: 'prod2' }, { id: 3, label: 'prod3' }]
     *
     * @param Array $categoryHierList Array of product or category hierarchies
     * @return Array Array containing ancestor chain (each item has 'id' and 'label' keys)
     */
    protected function getCommonAncestorChain($categoryHierList) {
        if (is_array($categoryHierList) && count($categoryHierList) > 1) {
            return call_user_func_array('array_uintersect', array_merge($categoryHierList, array(function ($a, $b) {
                return strcmp($a['id'], $b['id']);
            })));
        }
        return isset($categoryHierList[0]) ? $categoryHierList[0] : array();
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
