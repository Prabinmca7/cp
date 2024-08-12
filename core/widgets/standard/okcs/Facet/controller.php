<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Libraries\Search,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class Facet extends \RightNow\Libraries\Widget\Base {
    private $productCategoryApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $multiSelect = $this->data['attrs']['enable_multi_select'];
        $resultCount = Url::getParameter('resultCount');

        if($this->data['attrs']['results_per_page']) {
            $this->data['perPageList'] = array_map('trim', explode(",", $this->data['attrs']['results_per_page']));
            $this->data['defaultCount'] = $resultCount ? $resultCount : $this->data['perPageList'][0];
        }
        $filters = array('truncate' => array('value' => isset($this->data['attrs']['truncate_size']) ? $this->data['attrs']['truncate_size'] : 0),
                        'docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
                        'resultCount' => array('value' => isset($this->data['defaultCount']) ? $this->data['defaultCount'] : 0),
                        'transactionID' => array('value' => null),
                        'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']));
        if (!is_null(Url::getParameter('searchType')))
            $filters['searchType'] = array('value' => Url::getParameter('searchType'));
        $search->addFilters($filters)->executeSearch()->searchResults['results'];
        $facets = isset($searchResults->facets) ? $searchResults->facets : null;
        $filter = $search->getFilters();

        if ($facets !== null) {
            foreach ($facets as $facetItem) {
                foreach($facetItem as $key => $value){
                    if(!$this->getErrorDetails($key, $value)) {
                        return false;
                    }
                }
            }
        }
        if(isset($this->data['attrs']['top_facet_list']) && !empty($this->data['attrs']['top_facet_list']) ){
            $facets = $this->facetsOrderbyList($facets);
        }
        $this->data['facets'] = $facets;
        if(isset($this->data['attrs']['top_facet_list']) && !empty($this->data['attrs']['top_facet_list']) )
            $this->data['orderedFacets'] = $this->browseFacetsOrderbyList();
        else {
            $this->data['attrs']['top_facet_list'] = 'DOC_TYPES, Document Types|COLLECTIONS, Collections|CMS-PRODUCT, Product|CMS-CATEGORY_REF, Category';
            $this->data['orderedFacets'] = $this->browseFacetsOrderbyList();
        }
        if($multiSelect)
        {
            $selectedFacetsUrl = Url::getParameter('facet');
            $prodRefKey = Url::getParameter('product');
            $categRefKey = Url::getParameter('category');
            if($prodRefKey) {
                $prodRefKey = 'CMS-PRODUCT.' . $prodRefKey;
                $selectedFacetsUrl .= ',' . $prodRefKey;
            }
            if($categRefKey) {
                $categRefKey = 'CMS-CATEGORY_REF.' . $categRefKey;
                $selectedFacetsUrl .= ',' . $categRefKey;
            }
            $this->populateSelectedFacets($selectedFacetsUrl);
            $this->fetchProductCategories();

            $okcs = new \RightNow\Utils\Okcs();
            if($selectedFacetsUrl) {
                $explodedCommaArray = !is_null($selectedFacetsUrl) ? explode(',', $selectedFacetsUrl) : array();
                $categList = $okcs->getCategList($selectedFacetsUrl);
                if($categList) {
                    $prodCatList = $this->CI->model('Okcs')->getProductCategoryListDetails($categList, $this->productCategoryApiVersion);
                }
                $facetObject = $okcs->createSelectedFacetDetailObject($explodedCommaArray, $prodCatList, $facets);
                $this->data['facetObject'] = $facetObject;
            }
            $this->data['js'] = array(
                'filter' => $filter,
                'sources' => $search->getSources(),
                'facets' => json_encode($facets),
                'selectedFacetsUrl' => $selectedFacetsUrl,
                'selectedFacetDetails' => isset($facetObject['facet']) ? $facetObject['facet'] : null,
                'orderedFacets' => $this->data['orderedFacets'],
                'urlProduct' => $this->data['urlProduct'],
                'urlCategory' => $this->data['urlCategory'],
                'productCategoryApiVersion' => $this->productCategoryApiVersion,
                'noDataFoundMessage' => isset($this->data['attrs']['filter_type']) && strtoupper($this->data['attrs']['filter_type']) === 'PRODUCT' ? $this->data['attrs']['label_no_products'] : $this->data['attrs']['label_no_categories'],
                'products' => $this->data['products'],
                'categories' => $this->data['categories']
            );
        }
        else {
            if ($filter) {
                $this->data['js'] = array(
                    'filter'  => $filter,
                    'sources' => $search->getSources(),
                    'orderedFacets'  => $this->data['orderedFacets'],
                    'facets'  => json_encode($facets)
                );
            }
            if (isset($this->data['attrs']['hide_when_no_results']) && $this->data['attrs']['hide_when_no_results'] && !$this->data['results']->total) {
                $this->classList->add('rn_Hidden');
            }
        }
    }

    /**
    * Renders current facet.
    * @param object $currentFacet Current facet
    * @param boolean $hasChildren True if current facet has children False if no children for current facet
    * @param boolean $closeList True if you want to close a list item created earlier False if creating a new list item
    */
    function processChildren($currentFacet, $hasChildren, $closeList) {
        echo $this->render('facetLink',
            array(
                'facetID' => $currentFacet->id,
                'description' => $currentFacet->desc,
                'facetClass' => $currentFacet->inEffect ? 'rn_FacetLink rn_ActiveFacet' : 'rn_FacetLink',
                'hasChildren' => $hasChildren,
                'closeList' => $closeList
            )
        );
    }

    /**
    * Checks children of the current facet recursively. Process current facet if no children found.
    * @param object $facet Current facet
    * @param object $parentLi Parent list node
    * @param int $maxSubFacetSize Number of facets to be displayed
    */
    function findChildren($facet, $parentLi, $maxSubFacetSize) {
        $length = isset($facet->children) && is_array($facet->children) ? count($facet->children) : 0;
        $displayFacetLength = $length;
        if ($maxSubFacetSize !== null && $maxSubFacetSize > 0 && $length > $maxSubFacetSize) {
            $displayFacetLength = $maxSubFacetSize;
        }
        for ($i = 0; $i < $displayFacetLength; ++$i) {
            $currentFacet = $facet->children[$i];
            if ($currentFacet !== null) {
                if (isset($currentFacet->children) && is_array($currentFacet->children) && count($currentFacet->children) !== 0) {
                    $this->processChildren($currentFacet, true, false);
                    echo $this->render('facetIndent',
                        array(
                            'facetID' => $currentFacet->id,
                            'startListIndent' => true
                        )
                    );

                    $this->findChildren($currentFacet, $parentLi, $maxSubFacetSize);
                    echo $this->render('facetIndent',
                        array(
                            'startListIndent' => false
                        )
                    );
                    $this->processChildren($currentFacet, true, true);
                }
                else {
                    $this->processChildren($currentFacet, false, true);
                }
            }
        }
        if ($maxSubFacetSize != null && $maxSubFacetSize > 0 && $length > $maxSubFacetSize)
            echo $this->render('morelink', array('facetID' => $facet->id, 'description' => $currentFacet->desc));
    }

    /**
     * Fetches all associated products and categories
    */
    private function fetchProductCategories() {
        $products = $this->CI->model('Okcs')->getChannelCategories('', $this->productCategoryApiVersion, 0, 'PRODUCT');
        $categories = $this->CI->model('Okcs')->getChannelCategories('', $this->productCategoryApiVersion, 0, 'CATEGORY');
        if($products)
            $prodArray = $this->data['products'] = $this->processProdCateg($products, 'Product');
        if($categories)
            $categArray = $this->data['categories'] = $this->processProdCateg($categories, 'Category');
    }

    /**
    * Process products and categories into required object structure
    * @param object $categories Categories object
    * @param string $filterType Filter type
    * @return array organized product category hierarchy
    */
    private function processProdCateg($categories, $filterType) {
        if (!($categories->items) && $categories->errors) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($categories->error));
            return false;
        }
        if ($categories !== null) {
            $this->data['results'] = $this->getCategoriesByType($categories, strtoupper($filterType));
            $explorerViewClass = 'rn_' . Text::getSubstringBefore($this->instanceID, '_') . 'ExplorerView';
            $this->classList->add($explorerViewClass);
            if($this->data['results']['topLevel' . $filterType]) {
                foreach ($this->data['results']['topLevel' . $filterType] as $key => $category) {
                    if($category->hasChildren) {
                        $childCategoryResponse = $this->CI->model('Okcs')->getChildCategories($category->referenceKey, 100, 0);
                        $indexedChildCategories = [];
                        $childCategories = $childCategoryResponse->items;
                    }
                    else {
                        $childCategoryResponse = null;
                        $indexedChildCategories = [];
                        $childCategories = null;
                    }
                    //include active facet css for top levels
                    $isClassApplied = false;
                    $selector = 'url' . $filterType;
                    foreach($this->data[$selector] as $refKey){
                        if($category->referenceKey === $refKey) {
                            $category->selectedClass = 'rn_CategoryExplorerLink rn_ActiveFacet';
                            $isClassApplied = true;
                            break;
                        }
                    }
                    if (is_array($childCategories) && count($childCategories) > 0) {
                        if(!$isClassApplied)
                            $category->selectedClass = 'rn_CategoryExplorerLink';
                        foreach ($childCategories as $childCategory) {
                            $childCategory->selectedClass = 'rn_CategoryExplorerLink';
                            $childCategory->depth = 0;
                            $childCategory->type = $filterType;
                            $this->setCssClassForParentAndChildCategory($category, $childCategory);
                            $indexedChildCategories[$childCategory->referenceKey] = $childCategory;
                        }
                        if($childCategoryResponse->hasMore) {
                            $referenceKey = $filterType === 'PRODUCT' ? 'MoreChildProd_' . $category->referenceKey : 'MoreChildCateg_' . $category->referenceKey;
                            $moreChildCategory = (object) array(
                                                    'referenceKey' => $referenceKey,
                                                    'name' => $this->data['attrs']['label_more'],
                                                    'selectedClass' => 'rn_CategoryExplorerLink',
                                                    'type' => $filterType,
                                                    'depth' => '0'
                                                );
                            array_push($indexedChildCategories[$childCategory->referenceKey], $moreChildCategory);
                        }
                    }
                    $this->data['results']['topLevel' . $filterType][$key]->children = $indexedChildCategories;
                }
            }
            return $this->data['results'];
        }
    }

    /**
     * Method to filter categories based on the filter type, Product or Category.
     * @param array $categories Category list
     * @param string $categoryType Filter type
     * @return array category list
     */
    protected function getCategoriesByType($categories, $categoryType) {
        $index = 0;
        $parentProdArray = [];$indexedCategories = [];$key = '';
        foreach($categories->items as $category){
            if($categoryType === 'PRODUCT')
            {
                if($category->externalType === 'PRODUCT') {
                    $key = 'topLevelProduct';
                    $categories->items[$index]->type = 'Product';
                    $categories->items[$index]->depth = 0;
                }
                else
                    unset($categories->items[$index]);
            }
            else
            {
                if(!$category->externalType || $category->externalType === 'CATEGORY') {
                    $key = 'topLevelCategory';
                    $categories->items[$index]->type = 'Category';
                    $categories->items[$index]->depth = 0;
                }
                else
                    unset($categories->items[$index]);
            }
            $index++;
            $indexedCategories[$category->referenceKey] = $category;
        }
        $parentProdArray[$key] = $indexedCategories;
        return $parentProdArray;
    }

    /**
    * Method to set css class for the parent level product or category
    * @param object $category Parent product or category object
    * @param string $childCategory Child product or category object
    */
    private function setCssClassForParentAndChildCategory($category, $childCategory) {
        $selector = 'url' . $category->type;
        foreach($this->data[$selector] as $refKey){
            if($childCategory->parent->referenceKey === $refKey) {
                $category->selectedClass = 'rn_CategoryExplorerLink rn_ActiveFacet';
                break;
            }
            else if($childCategory->referenceKey === $refKey) {
                $childCategory->selectedClass = 'rn_CategoryExplorerLink rn_ActiveFacet';
                break;
            }
        }
    }

    /**
     * Checks for a source_id error. Emits an error message if a problem is found.
     * @return boolean True if an error was encountered False if all is good
    */
    private function sourceError () {
        if (\RightNow\Utils\Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }
        return false;
    }

    /**
    * Logs the error for each facet item.
    * @param string $facetItem Current facet property key
    * @param string $facetProperty Current facet property value
    * @return boolean False if an error was encountered True if all is good
    */
    private function getErrorDetails($facetItem, $facetProperty){
        if (is_null($facetProperty)){
            echo $this->reportError(sprintf(Config::getMessage(RESULT_OBJECT_PROPERTY_S_NOT_AVAILABLE_LBL), $facetItem));
            return false;
        }
        return true;
    }
    
    /**
    * Sorting the order of the list based on top_facet_list attribute.
    * @param array $facets Facets in default order
    * @return array after sorting the order of the list based on top_facet_list attribute
    */
    private function facetsOrderbyList($facets){
        $tempFacets = $facetOrder = $facetOrderDesc = array();
        $notFoundList = $facets;
        $descItemsCount = substr_count($this->data['attrs']['top_facet_list'], ',');
        if(($descItemsCount === 0) ){
            $facetOrder = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrder as $facetOrderItemKey) {
                foreach($facets as $facetItem){
                    if($facetItem->id === trim($facetOrderItemKey)){
                        $tempFacets[] = $facetItem;
                    }
                }
            }
        }
        if($descItemsCount > 0 && ($descItemsCount - substr_count($this->data['attrs']['top_facet_list'], '|') === 1)) {
            $facetOrderPairs = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrderPairs as $facetItem)
            {
                $facetItemPair = explode(',', $facetItem);
                $facetOrder[$facetItemPair[0]] = $facetItemPair[1];
            }
            foreach($facetOrder as $facetOrderItemKey => $facetOrderItemValue) {
                foreach ($facets as $facetItem) {
                    if($facetItem->id === trim($facetOrderItemKey)) {
                        $facetItem->desc = trim($facetOrderItemValue);
                        $tempFacets[]  = $facetItem;
                    }
                }
            }
        }
        return $tempFacets;
    }

    /**
    * Sorting the order of the list based on top_facet_list attribute for multi-facet flow only.
    * @return array after sorting the order of the list based on top_facet_list attribute
    */
    private function browseFacetsOrderbyList(){
        $descItemsCount = substr_count($this->data['attrs']['top_facet_list'], ',');
        if(($descItemsCount === 0) ){
            $facetOrder = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrder as $facetOrderItemKey) {
                foreach($facets as $facetItem){
                    if($facetItem->id === trim($facetOrderItemKey)){
                        $tempFacets[] = $facetItem;
                    }
                }
            }
            return $tempFacets;
        }
        else if($descItemsCount > 0) {
            $facetOrderPairs = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrderPairs as $facetItem)
            {
                $facetItemPair = explode(',', $facetItem);
                $facetOrder[trim($facetItemPair[0])] = (trim($facetItemPair[1]));
            }
            return $facetOrder;
        }
    }

    /**
    * Method to construct product and category arrays from comma separated string.
    * @param string $selectedString Comma separated facet reference keys
    */
    private function populateSelectedFacets($selectedString) {
        $explodedCommaArray = !is_null($selectedString) ? explode(',', $selectedString) : array();
        $prodArray = array();$categArray = array();
        foreach($explodedCommaArray as $item) {
            if(strpos($item, 'CMS-CATEGORY_REF') !== false){
                $explodedDotArray = (explode('.', $item));
                $refKey = $explodedDotArray[count($explodedDotArray) - 1];
                array_push($categArray, $refKey);
            }
            else if(strpos($item, 'CMS-PRODUCT') !== false){
                $explodedDotArray = (explode('.', $item));
                    $refKey = $explodedDotArray[count($explodedDotArray) - 1];
                    array_push($prodArray, $refKey);
            }
        }
        $this->data['urlProduct'] = $prodArray;
        $this->data['urlCategory'] = $categArray;
    }
}
