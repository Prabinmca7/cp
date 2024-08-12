<?

namespace RightNow\Models;

use RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Libraries\SearchMappers\OkcsSearchMapper,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Api;

require_once CPCORE . 'Libraries/SearchMappers/OkcsSearchMapper.php';
require_once CORE_FILES . 'compatibility/Internal/OkcsApi.php';

/**
 * Methods for retrieving search
 */
class OkcsSearch extends SearchSourceBase {

    /**
     * Searches OKCSSEARCH.
     */
    private $okcsFileResource;	
    private $okcsApi;

    public function __construct() {
        parent::__construct();
        $this->okcsApi = new \RightNow\compatibility\Internal\OkcsApi();
    }

    /**
    * Method to fetch the search results object
    * @param array $filters Filter list to fetch search results
    * @return object|null Search result
    */
    function search (array $filters = array()) {
        $contentSearchPerformed = true;

        if (isset($filters['searchType']['value']) && strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'newTab') {
            $docIdSearch = $this->verifyDocIdSearch($filters);
            if(isset($docIdSearch)) {
                return $docIdSearch;
            }
            $searchResults = $this->performSearch($filters);
        }
        else if (isset($filters['searchType']['value']) && strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'clearFacet') {
            $searchResults = $this->performContentSearch($filters);
        }
        else if (isset($filters['searchType']['value']) && strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'multiFacet') {
            $searchResults = $this->performMultiFacetSearch($filters);
        }
        else if (isset($filters['searchType']['value']) && strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'FACET') {
            $searchResults = $this->performFacetSearch($filters);
        }
        // Page request if 'page' param is not null
        else if (isset($filters['direction']['value']) && strlen($filters['direction']['value']) !== 0 && $filters['direction']['value'] !== '0') {
            $searchResults = $this->performPageSearch($filters);
        }
        else if (isset($filters['resultCountType']['value']) && $filters['resultCountType']['value'] === 'RC') {
            $searchResults = $this->performSearchWithResultCount($filters);
        }
        // Search request if 'query' param is not null
        else if (isset($filters['query']['value']) && strlen($filters['query']['value']) !== 0) {
            $docIdSearch = $this->verifyDocIdSearch($filters);
            if(isset($docIdSearch)) {
                return $docIdSearch;
            }
            $searchResults = $this->performContentSearch($filters);
        }
        else if (isset($filters['channelRecordID']['value'])) {
            $contentSearchPerformed = true;
            $categories = $this->CI->model('Okcs')->getChannelCategories($filters['channelRecordID']['value'], 'v1')->items;
            $searchResults = array('category' => $categories);
        }
        else {
            $result = new \stdClass();
            $result->results = array();
            $result->facets = array();
            $searchResults = $this->getResponseObject( array(
                                                            'searchState' => array(
                                                                                    'session' => $filters['okcsSearchSession']['value'], 
                                                                                    'transactionID' => $filters['transactionID']['value'], 
                                                                                    'priorTransactionID' => $filters['priorTransactionID']['value']), 
                                                            'searchResults' => array(
                                                                                        'page' => 0, 
                                                                                        'pageMore' => 0, 
                                                                                        'results' => $result, 
                                                                                        'facet' => 0, 
                                                                                        'selectedLocale' => null
                                                                                    )
                                                            ), 
                null);
        }
        $filters['sessionKey'] = $this->CI->model('Okcs')->decodeAndDecryptData($searchResults->result['searchState']['session']);
        if(isset($searchResults->errors[0])) {
            $filters['errors'] = $this->CI->model('Okcs')->formatErrorMessage($searchResults->errors[0]);
        }
        $resultMap = OkcsSearchMapper::toSearchResults($searchResults, $filters);
        if($contentSearchPerformed) {
            $resultAnswers = isset($resultMap->searchResults['results']->results[0]->resultItems) ? $resultMap->searchResults['results']->results[0]->resultItems : null;
            $answerLinks = array();
            if (is_array($resultAnswers) && count($resultAnswers) > 0) {
                foreach ($resultAnswers as $answer) {
                    $urlData = $this->getUrlData($answer);
                    $isPdfHtml = (isset($answer->fileType) && $answer->fileType !== 'CMS-XML' && !$urlData['isAttachment'] && $answer->type !== 'template');
                    $clickThruData = array('trackedURL' => $answer->clickThroughUrl, 'answerID' => $answer->answerId, 'docID' => $answer->docId);
                    if(Text::stringContains($answer->href, 'answer_data')) {
                        $answerLinks[$answer->answerId] = array(
                            'UrlData' => Text::getSubstringAfter($answer->href, '/answer_data/'),
                            'answerUrl' => $urlData['url'],
                            'clickThruData' => $clickThruData
                        );
                        $answer->href = Text::getSubstringBefore($answer->href, '/answer_data');
                        $answer->dataHref = $urlData['answerUrl'];
                        $answer->href .= '/s/' . $answer->answerId;
                    }
                    else {
                        $answerLinks[$answer->answerId] = array('UrlData' => $answer->href, 'clickThruData' => $clickThruData, 'answerUrl' => $urlData['url']);
                        $answer->dataHref = $urlData['answerUrl'];
                        $answer->href = "/ci/okcsFattach/get/{$answer->answerId}";
                        if(isset($answer->fileType) && $answer->fileType === 'PDF') {
                            $answerLinks[$answer->answerId]['UrlData'] = $urlData['url'];
                        }
                    }
                    if($isPdfHtml) {
                        $answer->href = "/ci/okcsFile/get/{$answer->answerId}";
                        $answerLinks[$answer->answerId]['answerUrl'] = $urlData['answerUrl'];
                    }
                    else if($urlData['isAttachment']) {
                        $answer->href = '/ci/okcsFattach/get/' . $urlData['url'];
                        $answer->href = isset($answer->fileType) && $answer->fileType === 'HTML' ? $answer->href . $urlData['anchor'] : $answer->href;
                    }
                }
            }
            $answerLinks['user'] = Framework::isLoggedIn() ? $this->CI->model('Contact')->get()->result->Login : 'guest';

            if(is_array($resultAnswers) && count($resultAnswers) > 0) {
                foreach ($resultAnswers as $answer) {
                    if((isset($answer->fileType) && $answer->fileType === 'CMS-XML') || ($answer->type === 'template')) {
                        $answer->href .= '_' . $filters['sessionKey'] . '/prTxnId/' . $resultMap->searchState['priorTransactionID'] .'#__highlight';
                    }
                    else if(!Text::stringContains($answer->href, 'okcsFattach')) {
                        $answer->href .= '/' . $filters['sessionKey'] . '/' . $resultMap->searchState['priorTransactionID'] . '/' . $answer->fileType . '#__highlight';
                    }
                    else if(!Text::stringContains($answer->href, '/s/')) {
                        $answer->href .= '/' . $answer->fileType . '/' . $filters['sessionKey'] . '/' . $resultMap->searchState['priorTransactionID'] . '/' . $answer->answerId . '#__highlight';
                    }
                }
            }
        }
        $searchText = $filters['query']['value'];
        if(preg_match('/' . $filters['docIdRegEx']['value'] . '/', '') === false) {
            Api::phpoutlog("getDocByIdFromSearchOrIm - Error: Invalid docIdRegEx pattern- " . $filters['docIdRegEx']['value']);
        }
        else if(isset($filters['docIdRegEx']['value']) && !empty($filters['docIdRegEx']['value']) && !is_null($searchText) && preg_match('/' . $filters['docIdRegEx']['value'] . '/', $searchText) === 1 && $searchResults->result['searchResults']['page'] === 0) {
            if(isset($searchResults->result['searchResults']['results']->results[0]->resultItems[0]->isHighlightingEnabled))
                $isHighlightingEnabled = $searchResults->result['searchResults']['results']->results[0]->resultItems[0]->isHighlightingEnabled;
            $searchResults->resultLocales = !isset($filters['loc']['value']) ? $filters['locale']['value'] : $filters['loc']['value'];
            $searchResults = $this->CI->model("Okcs")->getDocByIdFromSearchOrIm($searchResults, $searchText);
            if(!is_null($searchResults) && is_array($searchResults->result['searchResults']['results']->results) && count($searchResults->result['searchResults']['results']->results) > 0) {
                $searchResults->result['searchResults']['results']->results[0]->resultItems[0]->isHighlightingEnabled = $isHighlightingEnabled;
            }
        }
        return $this->getResponseObject($resultMap, is_string($searchResults) ? $searchResults : null);
    }

    /**
     * Searches answers for the search text to display in the new tab.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performSearch (array $filters) {
        $query = trim($filters['query']['value']);
        if(!is_null($query) && strlen($query) === 0)
            return null;
        $searchFilters = array(
            'kw' => $query,
            'loc' => $filters['locale']['value'],
            'facet' => $filters['facet']['value'],
            'page' => $filters['page']['value'],
            'resultCount' => $filters['resultCount']['value'],
            'querySource' => $filters['querySource']['value']
        );
        try {
            $result = $this->CI->model('Okcs')->getSearchResultForNewTab($searchFilters);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers for the search text.
     * @param array $filters Filter values
     * @return string|array|null Error message or results
     */
    private function performContentSearch (array $filters) {
        $accessType = null;
        $facets = null;
        if (isset($filters['accessType']) && !is_null($filters['accessType']['value']) && !empty($filters['accessType']['value'])) {
            $accessType = trim($filters['accessType']['value']);
        }
        if($filters['collectFacet']['value'] && !is_null($filters['multiFacetsToRetain']['value'])) {
            $filters['facet']['value'] = $filters['multiFacetsToRetain']['value'];
            return $this->performMultiFacetSearch($filters);
        }
        $query = trim($filters['query']['value']);
        if (!empty($filters['prod']['value']))
            $facets = 'CMS-PRODUCT.' . trim($filters['prod']['value']);

        if (!empty($filters['cat']['value'])) {
            $cat = 'CMS-CATEGORY_REF.' . trim($filters['cat']['value']);
            $facets = is_null($facets) ? $cat : $facets . ',' . $cat;
        }

        if (!empty($filters['facet']['value']))
            $facets = trim($filters['facet']['value']);

        $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
        if(Text::endsWith($searchSession, '_SEARCH'))
            $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');

        $filters = array(
            'query' => $query,
            'locale' => !isset($filters['loc']['value']) ? $filters['locale']['value'] : $filters['loc']['value'],
            'session' => $searchSession,
            'transactionID' => $filters['transactionID']['value'],
            'collectFacet' => $filters['collectFacet']['value'],
            'priorTransactionID' => $filters['priorTransactionID']['value'],
            'resultCount' => $filters['resultCount']['value'],
            'facets' => $facets,
            'querySource' => $filters['querySource']['value'],
            'accessType' => $accessType
        );

        try {
            $result = $this->CI->model('Okcs')->getSearchResult($filters);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }

    /**
     * Searches answers for the search text.
     * @param array $filters Filter values
     * @return string|array|null Error message or results
     */
    public function performMultiFacetSearch (array $filters) {
        $query = trim($filters['query']['value']);

        $accessType = null;
        if (isset($filters['accessType']) && !is_null($filters['accessType']['value']) && !empty($filters['accessType']['value'])) {
            $accessType = trim($filters['accessType']['value']);
        }

        if (!empty($filters['facet']['value']))
            $facets = trim($filters['facet']['value']);

        $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
        if(Text::endsWith($searchSession, '_SEARCH'))
            $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
        $filters = array(
            'query' => $query,
            'locale' => !isset($filters['loc']['value']) ? $filters['locale']['value'] : $filters['loc']['value'],
            'session' => $searchSession,
            'transactionID' => $filters['transactionID']['value'],
            'collectFacet' => $filters['collectFacet']['value'],
            'priorTransactionID' => $filters['priorTransactionID']['value'],
            'resultCount' => $filters['resultCount']['value'],
            'facets' => $facets,
            'querySource' => $filters['querySource']['value'],
            'accessType' => $accessType
        );

        try {
            $result = $this->CI->model('Okcs')->getMultiFacetSearchResult($filters);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }

    /**
     * Searches answers for the selected facet.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performFacetSearch (array $filters) {
        try {
            $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
            if(Text::endsWith($searchSession, '_SEARCH'))
                $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
            $facetFilter = array(
                'session' => $searchSession,
                'transactionID' => $filters['transactionID']['value'],
                'priorTransactionID' => $filters['priorTransactionID']['value'],
                'facet' => $filters['facet']['value'],
                'resultLocale' => isset($filters['loc']['value']) ? $filters['loc']['value'] : null,
                'resultCount' => $filters['resultCount']['value'],
                'querySource' => $filters['querySource']['value']
            );
            $result = $this->CI->model('Okcs')->getAnswersForSelectedFacet($facetFilter);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers for the requested page.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performPageSearch (array $filters) {
        try {
            $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
            if(Text::endsWith($searchSession, '_SEARCH'))
                $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
            $pageFilter = array(
                'session' => $searchSession,
                'priorTransactionID' => $filters['priorTransactionID']['value'],
                'page' => intval($filters['page']['value']) - 1,
                'type' => $filters['direction']['value'],
                'resultLocale' => isset($filters['loc']['value']) ? $filters['loc']['value'] : null,
                'resultCount' => $filters['resultCount']['value'],
                'querySource' => $filters['querySource']['value']
            );
            $result = $this->CI->model('Okcs')->getSearchPage($pageFilter);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers with the requested limit.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performSearchWithResultCount (array $filters) {
        try {
            $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
            if(Text::endsWith($searchSession, '_SEARCH'))
                $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
            $pageFilter = array(
                'session' => $searchSession,
                'priorTransactionID' => $filters['priorTransactionID']['value'],
                'resultLocale' => $filters['loc']['value'],
                'resultCount' => $filters['resultCount']['value'],
                'querySource' => $filters['querySource']['value']
            );
            $result = $this->CI->model('Okcs')->performSearchWithResultCount($pageFilter);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }

    /**
     * For the given filter type name, returns the
     * values for the filter.
     * @param  string $filterType Filter type
     * @return array Filter values
     */
    function getFilterValuesForFilterType ($filterType) {
        $sortOption = new KnowledgeFoundation\ContentSortOptions();
        $metaData = $sortOption::getMetadata();
        if ($filterType === 'sort') {
            $result = $metaData->SortField->named_values;
        }
        else if ($filterType === 'direction') {
            $result = $metaData->SortOrder->named_values;
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * This method returns an array of url data
     * @param object $result Search result answer
     * @return array Url data
     */
    function getUrlData($result) {
        $anchor = "";
        if (isset($result->title) && $result->title && isset($result->title->url)) {
            $linkUrl = $result->title->url;
        }
        else if ($result->link) {
            $linkUrl = $result->link;
            $data = $this->getValidatedLinkUrl($linkUrl, true);
            $linkUrl = $data['linkUrl'];
            $anchor = $data['anchor'];
        }
        else if ($result->clickThroughLink && Text::stringContains($result->clickThroughLink, 'turl=')) {
            $linkUrl = Text::getSubstringAfter($result->clickThroughLink, 'turl=');
            $data = $this->getValidatedLinkUrl($linkUrl, false);
            $linkUrl = $data['linkUrl'];
            $anchor = $data['anchor'];
        }
        $highlightUrl = isset($result->highlightedLink) ? $result->highlightedLink : null;
        if (Text::stringContains($linkUrl, 'IM:')) {
            $articleData = explode(':', $linkUrl);
            $answerLocale = $articleData[3];
            $answerStatus = $articleData[4];
            $answerID = $articleData[6];
            if(Text::stringContains($linkUrl, ':#') !== false) {
                $answerID = strtoupper($answerStatus) === 'PUBLISHED' ? $answerID : $answerID . "_d";
                $attachment = Text::getSubstringAfter($linkUrl, ':#');
                $answerUrl = "/ci/okcsFattach/getFile/{$answerID}/{$attachment}";
                if(Text::stringContains($highlightUrl, '#xml='))
                    $attachment .= '#xml=' . str_replace('%23', '', Text::getSubstringAfter($highlightUrl, '#xml='));
                $attachmentUrl = $answerID . "/file/" . Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe('ATTACHMENT:'.$attachment));
                return array('isAttachment' => true, 'url' => $attachmentUrl, 'answerUrl' => $answerUrl, 'anchor' => $anchor);
            }
            if(!is_null($answerID))
                $linkUrl = "/a_id/{$answerID}";
            if(!is_null($answerLocale))
                $linkUrl .= "/loc/{$answerLocale}";
            if(!$result->isHighlightingEnabled && strtoupper($answerStatus) !== 'PUBLISHED') {
                $linkUrl .= "/draft";
            }
            return array('isAttachment' => false, 'url' => $linkUrl, 'answerUrl' => $linkUrl, 'anchor' => $anchor);
        }
        return array('isAttachment' => false, 'url' => $result->href, 'answerUrl' => $linkUrl);
    }

    /**
    * This method returns key value from the Url.
    * Sample url format /key1/value1/key2/value2
    * @param string $url Url
    * @param string $key Url parameter key
    * @return string Url parameter value
    */
    private function getUrlParameter($url, $key) {
        if (preg_match("/&$key=([^&]*)(&|$)/", $url, $matches)) return $matches[1];
    }

    /**
    * This method validates for doc id search and returns document.
    * @param array $filters Filter values
    * @return object Document result object
    */
    private function verifyDocIdSearch($filters) {
        if($filters['docIdNavigation']['value'] === 'true' && \RightNow\Utils\Url::getParameter('nlpsearch') !== 'true' && isset($filters['query']['value']) && strlen($filters['query']['value']) !== 0 && isset($filters['docIdRegEx']['value']) && !empty($filters['docIdRegEx']['value']) && preg_match('/' . $filters['docIdRegEx']['value'] . '/', $filters['query']['value']) === 1) {
            $resultLocale = !isset($filters['loc']['value']) ? $filters['locale']['value'] : $filters['loc']['value'];
            if($resultLocale !== null) {
                $searchDocumentDetails = $this->okcsApi->getDocumentByDocIdLocale($filters['query']['value'], explode(',', $resultLocale)[0]);
            }
            else {
                $searchDocumentDetails = $this->okcsApi->getDocumentByDocId($filters['query']['value']);
            }
            if(!isset($searchDocumentDetails->errors)) {
                $this->CI->session->setSessionData(array('searchByDocId' => $searchDocumentDetails->documentId));
                $srchObj = new \stdClass();
                $resultObj = new \RightNow\Libraries\SearchResult();
                $resultObj->docIdSearch = true;
                if($resultLocale !== null) {
                    $resultObj->redirectUrl = "/app/" . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/" . $searchDocumentDetails->answerId . '/loc/' . urlencode($resultLocale);
                }
                else {
                    $resultObj->redirectUrl = "/app/" . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/" . $searchDocumentDetails->answerId;
                }
                $srchObj->result = new \RightNow\Libraries\SearchResults();
                $srchObj->result->results = array($resultObj);
                return $srchObj;
            }
        }
        return null;
    }

    /**
    * Method to check if link url consists of # and return the proper url
    * @param string $linkUrl Url
    * @param boolean $isHTML This flag is used to decide whether linkurl should be decode or send directly
    * @return array populated with required header values
    */
    function getValidatedLinkUrl($linkUrl, $isHTML) {
        $anchor = '';
        if(Text::stringContains($linkUrl, '#') && $isHTML){
            $fileName = Text::getSubstringAfter($linkUrl, '#');
            $linkUrl = Text::getSubstringBefore($linkUrl, '#');
            if(Text::stringContains($fileName, '#')){
                $anchor = '#' . Text::getSubstringAfter($fileName, '#');
                $fileName = Text::getSubstringBefore($fileName, '#');
            }
            $linkUrl .= '#'.$fileName;          
        }
        else {
            if(Text::stringContains($linkUrl, '#')) {
                $anchor = Text::getSubstringAfter($linkUrl, '#');
                $linkUrl = Text::getSubstringBefore($linkUrl, '#');
            }
            $linkUrl = urldecode($linkUrl);               
        }
        return array('linkUrl' => $linkUrl, 'anchor' => $anchor); 
    }
}

