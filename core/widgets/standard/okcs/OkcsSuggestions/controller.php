<?php
namespace RightNow\Widgets;
use RightNow\Libraries\Search,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Url;

class OkcsSuggestions extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if($this->isCPCookiesDisabledForSuggestionSearch() && !$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }else if (!$this->isCPCookiesDisabledForSuggestionSearch() && (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this) || !($this->CI->session->canSetSessionCookies()))) {
            return false;
        }
        if(strlen($this->data['attrs']['product_category']) == 0){
            $this->data['attrs']['product_category'] = $this->getProductCategory();
        }
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $filters = array('docIdRegEx' => array('value' => isset($this->data['attrs']['document_id_reg_ex']) ? $this->data['attrs']['document_id_reg_ex'] : null),
                        'docIdNavigation' => array('value' => isset($this->data['attrs']['doc_id_navigation']) ? $this->data['attrs']['doc_id_navigation'] : null),
                        'truncate' => array('value' => isset($this->data['attrs']['truncate_size']) ? $this->data['attrs']['truncate_size'] : null));
        $this->data['js'] = array(
                                'filter' => $search->getFilters(),
                                'sources' => $search->getSources(),
                                'truncateSize' => $this->data['attrs']['truncate_size'],
                                'applyCtIndexStatus' => $this->data['attrs']['apply_oracle_knowledge_search_index']);
    }

    /**
     * Method to get filter parameters from url.
     * @return string product/category record id
     */
    private function getProductCategory() {
        $productRecordID = Url::getParameter('productRecordID');
        $categoryRecordID = Url::getParameter('categoryRecordID');
        $productCategory = $productRecordID;
        if($productCategory !== null) {
            $productCategory .= ',' . $categoryRecordID;
        }
        else {
            $productCategory = $categoryRecordID;
        }
        return $productCategory;
    }

    /**
    * This method is used to check if CP COOKIES is removed or not for Suggestion Search
    * @return string either 1 or 0.
    */
    function isCPCookiesDisabledForSuggestionSearch() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_OKCS_CP_COOKIES_DISABLED')->Value;
        }
        catch (\Exception $err ) {
            return '0';
        }
    }
}