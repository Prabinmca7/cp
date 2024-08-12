<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url,
    RightNow\Utils\Okcs,
    RightNow\Libraries\Search;

class FacetFilter extends \RightNow\Libraries\Widget\Base {
    private $productCategoryApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $resultCount = Url::getParameter('resultCount');
        if($this->data['attrs']['results_per_page']) {
            $this->data['perPageList'] = array_map('trim', explode(",", $this->data['attrs']['results_per_page']));
            $this->data['defaultCount'] = $resultCount ? $resultCount : $this->data['perPageList'][0];
        }
        $filters = array('docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
                        'resultCount' => array('value' => $this->data['defaultCount']),
                        'transactionID' => array('value' => null),
                        'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']));
        if (!is_null(Url::getParameter('searchType')))
            $filters['searchType'] = array('value' => Url::getParameter('searchType'));
        $facets = $search->addFilters($filters)->executeSearch()->searchResults['results']->facets;
        if(!is_null($facets)) {
            $this->data['facets'] = $facets;
            if(isset($this->data['attrs']['top_facet_list']) && !empty($this->data['attrs']['top_facet_list']) )
                $this->data['orderedFacets'] = $this->browseFacetsOrderbyList();
            else {
                $this->data['attrs']['top_facet_list'] = 'DOC_TYPES, Document Types|COLLECTIONS, Collections|CMS-PRODUCT, Product|CMS-CATEGORY_REF, Category';
                $this->data['orderedFacets'] = $this->browseFacetsOrderbyList();
            }
            if ($facets !== null) {
                foreach ($facets as $facetItem) {
                    foreach($facetItem as $key => $value){
                        if(!$this->getErrorDetails($key, $value)) {
                            return false;
                        }
                    }
                }
            }
            $filter = $search->getFilters();
            if ($filter) {
                $this->data['js'] = array('filter' => $filter);
            }
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
            $this->data['js']['selectedFacets'] = $selectedFacetsUrl;
            $this->data['js']['orderedFacets'] = $this->data['orderedFacets'];
            $okcs = new \RightNow\Utils\Okcs();
            if($selectedFacetsUrl) {
                $explodedCommaArray = (explode(',', $selectedFacetsUrl));
                $categList = $okcs->getCategList($selectedFacetsUrl);
                if($categList) {
                    $prodCatList = $this->CI->model('Okcs')->getProductCategoryListDetails($categList, $this->productCategoryApiVersion);
                }
                $facetObject = $okcs->createSelectedFacetDetailObject($explodedCommaArray, $prodCatList, $facets);
                $this->data['facetObject'] = $facetObject['facetFilter'];
            }
        }
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
     * Method to create a sorting order of the facet list based on top_facet_list attribute.
     * @return array sorting order list
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
}
