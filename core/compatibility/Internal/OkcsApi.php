<?php

namespace RightNow\compatibility\Internal;
require_once CORE_FILES . 'compatibility/Internal/OkcsSamlAuth.php';
require_once CPCORE . 'Utils/Okcs.php';
if(\RightNow\Internal\Utils\Version::compareVersionNumbers(\RightNow\Internal\Utils\Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.5") >= 0) {
    if (!class_exists('\RightNow\Helpers\OkcsWhiteListHelper')) {
        require_once CPCORE . 'Helpers/OkcsWhiteList.php';
    }
}

use RightNow\Utils\Text,
    RightNow\Api,
    RightNow\ActionCapture,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Utils\Framework,
    RightNow\Internal\OkcsSamlAuth,
    RightNow\Helpers\OkcsWhiteList,
    RightNow\Utils\Okcs;

/**
 * Methods for retrieving agent accounts
 */
class OkcsApi extends \RightNow\Models\Base {
    private $cache;
    private $retryAttempts = 2;
    private $tokenRetryFlag = true;
    private $retryCount = 0;
    private $apiDuration = 0;
    private $okcsApiVersion = 'v1';
    private $okcsWhiteList;
    private $customApiCall = false;

    private $imCurlHandle;
    private $searchCurlHandle;
    private $imConsoleCurlHandle;
    const REQUEST_SOURCE_KEY_VALUE = 'IM';
    const USER_CACHE_KEY = 'userCacheKey';
    const OKCS_USER_TOKEN = 'okcsUserToken';
    const INVALID_CACHE_KEY = 'invalidCacheKey';
    const CP_INTEGRATION_TOKEN = 'CP_INTEGRATION_TOKEN';
    const INTERNAL_INTEGRATION_TOKEN = 'INTERNAL_INTEGRATION_TOKEN';
    const GUEST_USER = 'GUEST';
    const APP_ID = 'OKCS_CP';
    const HTTPS_PROTOCOL = 'HTTPS';
    const CONTACT_USER_TYPE = 'CONTACT';
    const TOKEN_ERROR_CODE = 'OK-GEN0003';
    const CACHE_TIME_IN_SECONDS = 82800;
    const INTERNAL_URL = 'INTERNAL_URL';
    const API_HOST = 'API_HOST';
    const OKCS_OK_RESPONSE = 200;
    const OKCS_CREATED_RESPONSE = 201;
    const OKCS_NO_CONTENT_RESPONSE = 204;
    const OKCS_BAD_REQUEST = 400;
    const OKCS_FORBIDDEN_REQUEST = 403;
    const OKCS_NOT_FOUND_REQUEST = 404;
    const OKCS_NOT_ALLOWED_REQUEST = 405;
    const OKCS_TIMEOUT_RESPONSE = 408;
    const OKCS_CONFLICT_REQUEST = 409;
    const OKCS_INTERNAL_SERVER_ERROR = 500;
    const OKCS_SERVICE_UNAVAILABLE = 503;
    const UNKNOWN_TYPE = 'unknown type';
    const RESOURCE_TYPE = 'resource';
    const TIMEOUT_MULTIPLIER_FACTOR = 5;
    const MAX_API_LIMIT = 1000;
    const MAX_URL_LIMIT = 4000;
    const CP_INTEGRATION_TOKEN_INVALID = 'CP_INTEGRATION_TOKEN_INVALID';
    const CP_INTEGRATION_TOKEN_RESP_HEADER = 'CP_INTEGRATION_TOKEN_RESP_HEADER';
    const DB_FAILOVER_MSG = 'Database failover maintenance in progress';

    function __destruct() {
        if(gettype($this->imCurlHandle) === self::RESOURCE_TYPE)
            curl_close($this->imCurlHandle);
        if(gettype($this->searchCurlHandle) === self::RESOURCE_TYPE)
            curl_close($this->searchCurlHandle);
        if(gettype($this->imConsoleCurlHandle) === self::RESOURCE_TYPE)
            curl_close($this->imConsoleCurlHandle);
    }

    /**
    * Gets a limited list of articles sorted by the requested filter
    * @param array $filter Filter list to fetch Infomanager articles
    * @param string $localeCode Locale of the interface
    * @return object Api response object
    */
    public function getArticlesSortedBy(array $filter, $localeCode) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($localeCode || true);
        $answerListApiVersion = $filter['answerListApiVersion'];
        $pageNumber = $filter['pageNumber'];
        $pageSize = $filter['pageSize'];
        $type = strtolower($filter['type']);
        $status = $filter['status'];
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $apiEndpoint = $baseUrl . $answerListApiVersion . '/content';
        $queryParameters = array('mode' => 'sortArticles');
        $sortColumnID = $filter['sortColumnId'] !== null ? $filter['sortColumnId'] : "publishDate";
        $sortDirection = $filter['sortDirection'] !== null ? $filter['sortDirection'] : 'DESC';
        $queryParameters['limit'] = $pageSize;
        $queryParameters['offset'] = ($pageNumber === 0) ? 0 : ($pageNumber - 1) * $pageSize;

        if(isset($filter['contentTypeAnsList']) && $filter['contentTypeAnsList']){
            $queryParameters['contentType'] = str_replace(':', '","', strtoupper($filter['contentTypeAnsList']));
        }
        else{
            $queryParameters['contentType'] = isset($filter['contentType']) ? str_replace(':', '","', strtoupper($filter['contentType'])) : '';
        }

        if(isset($filter['productCategoryAnsList']) && $filter['productCategoryAnsList']){
            $queryParameters['category'] = str_replace(':', '","', strtoupper($filter['productCategoryAnsList']));
        }
        else{
            $queryParameters['category'] = isset($filter['category']) ? str_replace(':', '","', strtoupper($filter['category'])) : null;
        }
        
        if(isset($filter['categoryExtId']))
            $queryParameters['categoryExtId'] = $filter['categoryExtId'];

        switch($type){
            case 'popular':
                $queryParameters['orderBy'] = 'mostPopular';
                break;
            case 'recent':
                $queryParameters['orderBy'] = 'mostRecent';
                break;
            default:
                $queryParameters['orderBy'] = $sortColumnID . ':' . $sortDirection;
        }

        if(is_bool($status)) {
            $queryParameters['contentState'] = $status ? 'LATESTVALID' : 'PUBLISHED';
        }
        else if(!is_null($status) && strtoupper($status) === 'DRAFT') {
            $queryParameters['contentState'] = 'LATESTVALID';
        }
        else {
            $queryParameters['contentState'] = 'PUBLISHED';
        }

        $requestUrl = $apiEndpoint . Okcs::getRestQueryString($queryParameters);
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getArticlesSortedBy', 'acsEventName' => 'SortArticles', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets a limited list of recommendations sorted by the requested filter
    * @param string $userID Logged in user
    * @param array $filter Filter list to fetch recommendations
    * @return object Api response object
    */
    public function getRecommendationsSortedBy($userID, array $filter) {
        $manageRecommendationsApiVersion = $filter['manageRecommendationsApiVersion'];
        $pageNumber = $filter['pageNumber'];
        $pageSize = $filter['pageSize'];
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $apiEndpoint = $baseUrl . $manageRecommendationsApiVersion . '/contentRecommendations';
        $queryParameters = array('mode' => 'sortRecommendations');
        $sortColumnID = $filter['sortColumnId'] !== null ? $filter['sortColumnId'] : "dateAdded";
        $sortDirection = $filter['sortDirection'] !== null ? $filter['sortDirection'] : 'DESC';
        $queryParameters['limit'] = $pageSize;
        $queryParameters['offset'] = ($pageNumber === 0) ? 0 : ($pageNumber - 1) * $pageSize;
        $queryParameters['userID'] = $userID;
        $queryParameters['orderBy'] = $sortColumnID . ':' . $sortDirection;

        $requestUrl = $apiEndpoint . Okcs::getRestQueryString($queryParameters);
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getRecommendationsSortedBy', 'acsEventName' => 'SortRecomm', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets Recommendations view
    * @param string $recordId Record Id of the recommendation to be retrieved
    * @return object Api response object
    */
    public function getRecommendationsView($recordId) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/contentRecommendations/{$recordId}";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getRecommendationsView', 'acsEventName' => 'RecommView', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets a list of content types
    * @param string $contentTypeApiVersion API version of content type widget
    * @return object|string Api response object
    */
    public function getChannels($contentTypeApiVersion) {
        $apiEndpoint = $contentTypeApiVersion . '/contentTypes?orderBy=referenceKey';
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $apiEndpoint;
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getChannels', 'acsEventName' => 'getChannels', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
     * Retrieves related answers associated to a specific answer ID. Makes a batch call to
     * grab manually related answers and learned link answers.
     *
     * @param int $answerID Answer ID from which to get related answers
     * @return array Results from query
     */
    public function getRelatedAnswers($answerID) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/batch";

        $postData = array(
            'asynchronous' => false,
            'requests' => array(
                array(
                    'id' => 1,
                    'method' => 'GET',
                    'relativeUrl' => $this->okcsApiVersion . '/content/answers/' . $answerID . '/documentLinks/manual'
                ),
                array(
                    'id' => 2,
                    'method' => 'GET',
                    'relativeUrl' => $this->okcsApiVersion . '/content/answers/' . $answerID . '/documentLinks/learned'
                )
            )
        );

        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getRelatedAnswers', 'acsEventName' => 'RelatedAnswers', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
     * Retrieves related answers associated to a specific answer ID. Makes a batch call to
     * grab manually/learned related answers.
     * @param int $answerID Answer ID from which to get related answers
     * @param string $linkType Type of Related Answer
     * @return array Results from query
     */
    public function getManualOrLearnedLinks($answerID, $linkType){
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null){
            return $baseUrl;
        }
        $requestUrl = $baseUrl . $this->okcsApiVersion . '/content/answers/' . $answerID . '/documentLinks/' . $linkType;
        $results = $this->makeRequest($requestUrl, 'GET');
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getLinks', 'acsEventName' => 'RelatedAnswers', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * Gets details of seleted channel
    * @param string $channel Selected channel reference key
    * @return object Api response object
    */
    public function getChannelDetails($channel) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/contentTypes/{$channel}?mode=FULL";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getChannelDetails', 'acsEventName' => 'getChannelDetails', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets the content of a document in Info Manager
    * @param array $contentData Content data to fetch content details
    * @return object Api response object
    */
    public function getHighlightContent(array $contentData) {
        $isPDF = ($contentData['answerType'] === 'PDF') ? "true" : "false";
        $localeCode = str_replace("-", "_", $contentData['locale']);
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/answer?priorTransactionId=" . $contentData['priorTransactionId'] . '&answerId=' . $contentData['answerId'] . '&highlightFlag=true&trackClickFlag=true';

        if(isset($contentData['accessType']) && !is_null($contentData['accessType']) && !empty($contentData['accessType'])){
            $contentData['accessType'] = urlencode($contentData['accessType']);
            $requestUrl .= "&activityType={$contentData['accessType']}";
        }

        $session = $contentData['searchSession'];
        if(Text::endsWith($session, '_SEARCH'))
            $session = Text::getSubstringBefore($session, '_SEARCH');

        $postData = array(
            'session' => $session,
            'locale' => str_replace("_", "-", $localeCode),
            'transactionId' => $contentData['transactionID'] + 1
        );
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getHighlightContent', 'acsEventName' => 'getHighlightedCont', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets the contentSchema of a document
    * @param string $contentTypeID Record ID of contentType
    * @param string $locale Locale of contentType schema
    * @param string $answerViewApiVersion API version
    * @param boolean $isGuestUser Flag to identify if guest user
    * @return object Api response object
    */
    public function getIMContentSchema($contentTypeID, $locale, $answerViewApiVersion, $isGuestUser = false) {
        $baseUrl = $this->getBaseUrl();
        $kmAuthToken = '';
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $answerViewApiVersion . "/contentTypes/{$contentTypeID}?mode=EXTENDED";

        if(!is_null($locale))
            $requestUrl .= "&requestLocale={$locale}";

        if($isGuestUser)
            $kmAuthToken = $this->getGuestUserToken();
        $response = $this->makeRequest($requestUrl, 'GET', '', $kmAuthToken);
        return $response;
    }

    /**
    * Method to fetch document Rating
    * @param string $ratingID Rating ID
    * @param string $documentRatingApiVersion API version of document rating
    * @return object Api response object
    */
    public function getDocumentRating($ratingID, $documentRatingApiVersion) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $documentRatingApiVersion . "/dataForms/{$ratingID}";
        return $this->makeRequest($requestUrl);
    }

    /**
    * Gets the list of supported languages
    * @param string $supportedLanguageApiVersion API version of supported language
    * @return object Api response object
    */
    public function getSupportedLanguages($supportedLanguageApiVersion) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $supportedLanguageApiVersion . "/repositories/default/availableLocales?mode=full";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getSupportedLanguages', 'acsEventName' => 'getSupportedLang', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets the preferred languages of a user
    * @param string $user User login
    * @return object Api response object
    */
    public function getUserLocale($user) {
        $response = Framework::checkCache($user);
        if($response !== null) {
            return $response;
        }
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/users/{$user}";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getUserLocale', 'acsEventName' => 'getUserLocale', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;

        if(!isset($response->errors))
            Framework::setCache($user, $response, true);
        return $response;
    }

    /**
    * Gets the document for a given doc id
    * @param string $docId Document Id
    * @return object Api response object
    */
    public function getDocumentByDocId($docId) {
        $document = Framework::checkCache($docId);
        if($document !== null)
            return $document;
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/content/docId/{$docId}";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getDocumentByDocId', 'acsEventName' => 'getDocumentByDocId', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        if(!isset($response->errors))
            Framework::setCache($docId, $response, true);
        return $response;
    }

    /**
    * Gets the document for a given doc id and locale
    * @param string $docId Document Id
    * @param string $locale Document Locale
    * @return object Api response object
    */
    public function getDocumentByDocIdLocale($docId, $locale) {
        $interfaceLocale = $this->getInterfaceLocale();
        $locale = !$locale ? $this->getInterfaceLocale() : $locale;
        $locale = str_replace("-", "_", $locale);
        $document = Framework::checkCache($docId . '_' . $locale);
        if($document !== null)
            return $document;
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/content/docId/{$docId}?langpref={$locale}";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getDocumentByDocIdLocale', 'acsEventName' => 'getDocByDocIdLoc', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        if(!isset($response->errors))
            Framework::setCache($docId . '_' . $locale, $response, true);
        return $response;
    }

    /**
    * Method to call clickThrough API
    * @param array $clickThroughInput Array of input parameters to call REST API
    * @return object Api response object
    */
    public function clickThrough(array $clickThroughInput) {
        $session = $clickThroughInput['session'];
        $transactionID = $clickThroughInput['transactionID'];
        $priorTransactionID = $clickThroughInput['priorTransactionID'];
        $answerID = $clickThroughInput['answerID'];
        $docID = $clickThroughInput['docID'];
        $isUnstructured = $clickThroughInput['isUnstructured'];
        $localeCode = $clickThroughInput['localeCode'];
        $trackedUrl = $clickThroughInput['trackedUrl'];
        $requestLocale = $clickThroughInput['requestLocale'];
        $resultLocale = $clickThroughInput['resultLocale'];

        $requestUrl = $this->getBaseUrl('SRCH_API_URL') . $this->okcsApiVersion . "/search/click-thru?priorTransactionId={$priorTransactionID}&answerId={$answerID}&isUnstructured={$isUnstructured}&trackedURL={$trackedUrl}&requestLocale={$requestLocale}";

        $postData = array(
            'session' => $session,
            'transactionId' => $transactionID,
            'locale' => $localeCode,
            'resultLocales' => $resultLocale,
            'querySource' => 'answeropen',
            'uiMode' => 'answer',
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer(),
                'host' => $this->getHost(),
                'requestHeaders' => array(
                    (object)array( 'key' => 'accept-language', 'value' => 'en' ),
                    (object)array( 'key' => 'accept', 'value' => '*/*' ),
                    (object)array( 'key' => 'accept-encoding', 'value' => 'gzip, deflate' ),
                    (object)array( 'key' => 'user-agent', 'value' => $this->getHttpUserAgent() ),
                    (object)array( 'key' => 'connection', 'value' => 'keep-alive' ),
                    (object)array( 'key' => 'answersPage', 'value' => 'answers' ),
                    (object)array( 'key' => 'searchid', 'value' => $transactionID )
                ),
                'requestParameters' => array(
                    (object)array('key' => 'charset', 'value' => 'UTF-8')
                )
            )
        );
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'clickThrough', 'acsEventName' => 'clickThrough', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets answers for a question and then cache them.
    * @param array $searchFilter Array of search filters to call REST API
    * @return object Api response object
    */
    public function getSearchResult(array $searchFilter) {
        $apiEndPoint = $searchFilter['requestType'] === 'SEARCH' ? 'search' : 'contactDeflection';
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/{$apiEndPoint}/question?question={$searchFilter['searchText']}&pageSize={$searchFilter['resultCount']}&requestLocale={$searchFilter['requestLocale']}";
        if(!is_null($searchFilter['facets']) && !empty($searchFilter['facets'])){
            $searchFilter['facets'] = urlencode($searchFilter['facets']);
            $requestUrl .= "&facet={$searchFilter['facets']}";
        }
        if(isset($searchFilter['accessType']) && !is_null($searchFilter['accessType']) && !empty($searchFilter['accessType'])){
            $searchFilter['accessType'] = urlencode($searchFilter['accessType']);
            $requestUrl .= "&activityType={$searchFilter['accessType']}";
        }
        $postData = array(
            'session' => $searchFilter['searchSession'],
            'transactionId' => $searchFilter['transactionID'],
            'locale' => $searchFilter['localeCode'],
            'resultLocales' => $searchFilter['resultLocale'],
            'querySource' => $searchFilter['querySource'],
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer() ?: '',
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array( array( 'key' => 'question_box', 'value' => $searchFilter['searchText'] ) )
            )
        );

        if($searchFilter['collectFacet'] && !is_null($searchFilter['facetPriorTransactionID']) && !empty($searchFilter['facetPriorTransactionID'])){
            $requestUrl .= "&startover=false";
            $postData['facetPriorTransactionId'] = $searchFilter['facetPriorTransactionID'];
        }

        if ($searchFilter['requestType'] === 'SMART_ASSISTANT')
            $postData['isDeflection'] = true;

        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getSearchResult', 'acsEventName' => 'getSearchResult', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

     /**
    * Gets answers for a question employing multiple filtering.
    * @param array $searchFilter Array of search filters to call REST API
    * @return object Api response object
    */
    public function getMultiFacetSearchResult(array $searchFilter) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/question?question={$searchFilter['searchText']}&pageSize={$searchFilter['resultCount']}&requestLocale={$searchFilter['requestLocale']}";
        if(!is_null($searchFilter['facets']) && !empty($searchFilter['facets'])){
            $searchFilter['facets'] = urlencode($searchFilter['facets']);
            $requestUrl .= "&facet={$searchFilter['facets']}&multiFacets=AND";
        }
        if(isset($searchFilter['accessType']) && !is_null($searchFilter['accessType']) && !empty($searchFilter['accessType'])){
            $searchFilter['accessType'] = urlencode($searchFilter['accessType']);
            $requestUrl .= "&activityType={$searchFilter['accessType']}";
        }
        $postData = array(
            'session' => $searchFilter['searchSession'],
            'transactionId' => $searchFilter['transactionID'],
            'locale' => $searchFilter['localeCode'],
            'resultLocales' => $searchFilter['resultLocale'],
            'querySource' => $searchFilter['querySource'],
            'pageSize' => $searchFilter['resultCount'],
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer() ?: '',
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array( array( 'key' => 'question_box', 'value' => $searchFilter['searchText'] ) )
            )
        );

        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getMfSearchResult', 'acsEventName' => 'getMfSearchResult', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * Gets answers to display answers on the new tab/window.
    * @param array $filter Filters to fetch search results
    * @return object Api response object
    */
    public function getSearchResultForNewTab(array $filter) {
        $searchText = urlencode($filter['kw']);
        $selectedFacet = $filter['facet'];
        $selectedPage = $filter['page'];
        $selectedLocale = $filter['loc'];
        $localeCode = $filter['localeCode'];
        $requestLocale = $filter['requestLocale'];
        $resultLocale = $filter['resultLocale'];
        $resultCount = $filter['resultCount'];
        $searchSession = $filter['searchSession'];
        $transactionID = $filter['transactionID'];
        $querySource = $filter['querySource'];

        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/batch";

        $searchData = array(
            'session' => $searchSession,
            'transactionId' => $transactionID,
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'locale' => $localeCode,
            'resultLocales' => $resultLocale,
            'pageSize' => $resultCount,
            'querySource' => $querySource
        );
        $postData = array(
            'asynchronous' => false,
            'requests' => array(
                array(
                    'id' => $transactionID,
                    'method' => 'POST',
                    'relativeUrl' => $this->okcsApiVersion . "/search/question?question={$searchText}&startOver=true&requestLocale={$requestLocale}",
                    'bodyClassName' => 'oracle.km.bo.model.search.session.SearchSessionBO',
                    'body' => json_encode($searchData),
                    'headers' => array((Object)array())
                )
            )
        );

        if(!is_null($selectedFacet) && !empty($selectedFacet)) {
            $searchState = $this->addPostData($requestType = 'FACET', $selectedFacet, $postData, $searchData);
            $postData = $searchState['postData'];
            $searchData['transactionId'] = $searchState['transactionID'];
        }
        if(!is_null($selectedPage) && !empty($selectedPage)) {
            $searchState = $this->addPostData($requestType = 'PAGE', $selectedPage, $postData, $searchData);
            $postData = $searchState['postData'];
        }

        $lastRequestIndex = count($postData['requests']) - 1;
        $results = json_decode($this->makeRequest($requestUrl, 'POST', json_encode($postData))->items[$lastRequestIndex]->body);

        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getSearchResultForNewTab', 'acsEventName' => 'NewTabSearchResult', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * This method returns modified post data to call batch API
    * @param string $requestType Type of search request. Possible values are 'FACET' and 'PAGE'.
    * @param array $filterData Filters to fetch search results
    * @param array $postData Search post data
    * @param array $searchData Array of Search state
    * @return array Search Post data
    */
    protected function addPostData($requestType, $filterData, $postData, $searchData) {
        $transactionID = $searchData['transactionId'];
        $localeCode = $searchData['locale'];
        $requestLocale = str_replace("-", "_", $localeCode);
        if($requestType === 'FACET') {
            $facets = explode(',', $filterData);
            $facetCount = count($facets);
            for($count = 0; $count < $facetCount; $count++) {
                $facet = $facets[$count];
                if(\RightNow\Internal\Utils\Version::compareVersionNumbers(\RightNow\Internal\Utils\Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.7") >= 0) {
                    $url = $this->okcsApiVersion . "/search/navigation?priorTransactionId={$transactionID}&facet={$facet}&facetShowAllFlag=true&requestLocale={$requestLocale}";
                }
                else {
                    $url = $this->okcsApiVersion . "/search/navigation?priorTransactionId={$transactionID}&facet={$facet}&facetShowAll=false&requestLocale={$requestLocale}";
                }
                $transactionID++;
                $batchData = $this->getBatchData($url, $searchData, $transactionID, $localeCode);
                array_push($postData['requests'], $batchData);
            }
        }
        else if($requestType === 'PAGE') {
            $requestedPage = $filterData;
            for($count = 1; $count < $requestedPage; $count++) {
                $pageNumber = $count - 1;
                $url = $this->okcsApiVersion . "/search/pagination?priorTransactionId={$transactionID}&purposeName=ANSWER&pageDirection=next&pageNumber={$pageNumber}";
                $batchData = $this->getBatchData($url, $searchData, $transactionID, $localeCode);
                array_push($postData['requests'], $batchData);
            }
        }
        return array('postData' => $postData, 'transactionID' => $transactionID);
    }

    /**
    * This method returns post data for individual batch request
    * @param string $url Relative url of the batch request
    * @param array $searchData Search data array
    * @param int $transactionID Transaction ID
    * @param string $localeCode Locale of the search
    * @return array Search Post data
    */
    protected function getBatchData($url, array $searchData, $transactionID, $localeCode) {
        $data = array(
            'session' => $searchData['session'],
            'transactionId' => $transactionID,
            'pageSize' => $searchData['pageSize'],
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'locale' => $localeCode,
            'resultLocales' => $searchData['resultLocales'],
            'querySource' => $searchData['querySource']
        );
        $batchData = array(
            'id' => $transactionID,
            'method' => 'POST',
            'relativeUrl' => $url,
            'bodyClassName' => 'oracle.km.bo.model.search.session.SearchSessionBO',
            'body' => json_encode($data),
            'headers' => array((Object)array())
        );
        return $batchData;
    }

    /**
    * This method returns search session to perform search on ask question tab
    * @param string $localeCode Search request locale
    * @return object Api response object
    */
    public function getContactSearchSession($localeCode) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/contactDeflection/initial";
        $postData = array(
            'session' => '',
            'transactionId' => 1,
            'locale' => $localeCode,
            'resultLocales' => $localeCode
        );
        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        return $results;
    }

    /**
    * This method returns search session to perform search on ask question tab
    * @param string $sessionID New generated sessionID
    * @param string $locale User interface locale
    * @param string $requestLocales Search request locale
    * @param int $transactionID Search transactionID
    * @return object Api response object
    */
    public function getSearchSession($sessionID, $locale, $requestLocales, $transactionID) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/initialScreen";
        $postData = array(
            'session' => $sessionID,
            'transactionId' => $transactionID,
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'locale' => $locale,
            'resultLocales' => $requestLocales
        );
        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        return $results;
    }

    /**
    * This method refines last performed search result based on the selected facet.
    * @param array $searchFilter List of search filters
    * @return object Api response object
    */
    public function getAnswersForSelectedFacet(array $searchFilter) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        if($searchFilter['facet'] !== null)
            $searchFilter['facet'] = urlencode($searchFilter['facet']);
        if(\RightNow\Internal\Utils\Version::compareVersionNumbers(\RightNow\Internal\Utils\Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.7") >= 0) {
            $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/navigation?priorTransactionId={$searchFilter['priorTransactionID']}&facet={$searchFilter['facet']}&facetShowAllFlag=true";
        }
        else {
            $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/navigation?priorTransactionId={$searchFilter['priorTransactionID']}&facet={$searchFilter['facet']}&facetShowAll=false";
        }

        if($searchFilter['transactionID'] === null){
            $searchFilter['transactionID'] = $searchFilter['priorTransactionID'] + 1;
        }

        $postData = array(
            'session' => $searchFilter['session'],
            'transactionId' => $searchFilter['transactionID'],
            'locale' => $searchFilter['localeCode'],
            'resultLocales' => $searchFilter['resultLocale'],
            'querySource' => $searchFilter['querySource'],
            'pageSize' => $searchFilter['resultCount'],
            'uiMode' => 'navigate',
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer() ?: '',
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array( array( 'key' => 'facet', 'value' => $searchFilter['facet'] ) )
            )
        );
        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getAnswersForSelectedFacet', 'acsEventName' => 'FacetSelection', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * Gets answer data for a requestedPage.
    * @param array $searchFilter List of search filters
    * @return object Api response object
    */
    public function getSearchPage(array $searchFilter) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/pagination?purposeName=ANSWER&pageDirection={$searchFilter['pageDirection']}&pageSize={$searchFilter['page']}&pageNumber={$searchFilter['page']}&priorTransactionId={$searchFilter['priorTransactionID']}";

        $postData = array(
            'session' => $searchFilter['session'],
            'transactionId' => $searchFilter['priorTransactionID'],
            'locale' => $searchFilter['localeCode'],
            'resultLocales' => $searchFilter['resultLocale'],
            'pageSize' => $searchFilter['resultCount'],
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'uiMode' => 'paging',
            'querySource' => $searchFilter['querySource'],
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer() ?: '',
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array(
                    array( 'key' => 'navigation_purpose', 'value' => 'ANSWER' ),
                    array( 'key' => 'direction', 'value' => $searchFilter['pageDirection'] ),
                    array( 'key' => 'page_number', 'value' => $searchFilter['page'] )
                )
            )
        );

        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        return $results;
    }

    /**
    * Fetches answer data with a particular limit.
    * @param array $searchFilter List of search filters
    * @return array Search result for the requested limit
    */
    public function performSearchWithResultCount(array $searchFilter) {
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/search/pagination?purposeName=ANSWER&pageDirection=current&pageNumber=0&newPageSize={$searchFilter['resultCount']}&priorTransactionId={$searchFilter['priorTransactionID']}";

        $postData = array(
            'session' => $searchFilter['session'],
            'transactionId' => $searchFilter['priorTransactionID'],
            'locale' => $searchFilter['localeCode'],
            'resultLocales' => $searchFilter['resultLocale'],
            'pageSize' => $searchFilter['resultCount'],
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'uiMode' => 'paging',
            'querySource' => $searchFilter['querySource'],
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer() ?: '',
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array(
                    array( 'key' => 'navigation_purpose', 'value' => 'ANSWER' ),
                    array( 'key' => 'direction', 'value' => 'current' ),
                    array( 'key' => 'page_number', 'value' => 0 )
                )
            )
        );

        $results = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        return $results;
    }


    /**
    * Method to retrieve user recordID.
    * @param string $user User login
    * @return object Api response object
    */
    public function getUserRecordID($user) {
        $response = Framework::checkCache($user);
        if($response !== null) {
            return $response;
        }
        $response = Framework::checkCache($user);
        if($response !== null) {
            return $response;
        }
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/users/{$user}";
        $response = $this->makeRequest($requestUrl);
        if(isset($response->result->errors) && $response->result->errors !== null) {
            Framework::setCache($user, $response, true);
        }

        if(!isset($response->result->errors) && !isset($response->error))
            Framework::setCache($user, $response, true);
        return $response;
    }

    /**
    * Method to retrieve user preferences.
    * @param string $userRecordID User recordID
    * @return object Api response object
    */
    public function getUserLanguagePreferences($userRecordID) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $apiEndpoint = "{$baseUrl}{$this->okcsApiVersion}/users/{$userRecordID}/userKeyValues";
        $queryParameters = array('mode' => 'user', 'key' => 'search_language', 'userInformation.recordId' => $userRecordID);
        $requestUrl = $apiEndpoint . Okcs::getRestQueryString($queryParameters);
        $response = $this->makeRequest($requestUrl);
        return $response;
    }

    /**
    * Method to get contact deflection response
    * @param array $contactDeflectionData Array of contact defelection data to submit response
    * @return object Api response object
    */
    public function getContactDeflectionResponse(array $contactDeflectionData) {
        $priorTransactionID = $contactDeflectionData['priorTransactionID'];
        $deflected = $contactDeflectionData['deflected'];
        $baseUrl = $this->getBaseUrl('SRCH_API_URL');
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/contactDeflection/response?priorTransactionId={$priorTransactionID}&deflectedFlag={$deflected}";

        $postData = array(
            'session' => $contactDeflectionData['searchSession'],
            'transactionId' => $contactDeflectionData['transactionID'],
            'locale' => $contactDeflectionData['localeCode'],
            'resultLocales' => isset($contactDeflectionData['resultLocale']) ? $contactDeflectionData['resultLocale'] : null,
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array(
                'referrer' => $this->getHttpReferer(),
                'host' => $this->getHost(),
                'requestHeaders' => $this->createRequestHeader(),
                'requestParameters' => array(array('key' => 'charset', 'value' => 'UTF-8'))
            )
        );
        if ($deflected === true || $deflected === 'true') {
            $postData['ccaInfo'] = array(
                    'name' => '',
                    'connected' => true,
                    'caseDescription' => '',
                    'extSolutionList' => '',
                    'SRKey' => '',
                    'system' => '',
                    'contentIds' => '',
                    'answerSolutionList' => '',
                    'types' => ''
            );
        }
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getContactDeflectionResponse', 'acsEventName' => 'ContactDeflection', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to get categories corresponding to a praticular channel
    * @param string $channelReferenceKey Channel Reference Key
    * @param string $productCategoryApiVersion API version of product categories
    * @param int $offset Offset in category list
    * @param string $externalType Category external type
    * @return object Api response object
    */
    public function getChannelCategories($channelReferenceKey, $productCategoryApiVersion, $offset = 0, $externalType = null) {
        $baseUrl = $this->getBaseUrl();
        $queryExtType = $externalType === null ? "externalType+in+('PRODUCT','CATEGORY')" : "externalType={$externalType}";
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        if(empty($channelReferenceKey) || is_null($channelReferenceKey))
            $requestUrl = $baseUrl . $productCategoryApiVersion . "/categories?orderBy=sortOrder,dateAdded:desc&limit=100&offset=" . $offset . "&mode=FULL&q=topLevelOnly+eq+true+and+" . ($externalType === null ? $queryExtType : "externalType+eq+'" . $externalType . "'") . "&childrenCount=true";
        else
            $requestUrl = $baseUrl . $productCategoryApiVersion . "/contentTypes/{$channelReferenceKey}/categories?orderBy=sortOrder,dateAdded:desc&limit=100&offset=" . $offset . "&mode=FULL&childrenCount=true" . ($externalType === null ? "&q=" : "&"). $queryExtType;
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getChannelCategories', 'acsEventName' => 'getChannelCat', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to get product category hierarchy
    * @param array $batchData Post data
    * @return object Category hierarchy Object
    */
    public function getProdCatHierarchy(array $batchData) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        return $this->makeRequest("{$baseUrl}{$this->okcsApiVersion}/batch", 'POST', json_encode($batchData));
    }

    /**
    * Method to get children corresponding to a particular category
    * @param string $categoryID CategoryID
    * @param string $limit No. of categories to be fetched
    * @param string $offset Offset in category list
    * @return object Api response object
    */
    public function getChildCategories($categoryID, $limit, $offset) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/categories/{$categoryID}/children?limit=" . $limit . "&offset=" . $offset . "&mode=FULL&orderBy=sortOrder&childrenCount=true";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getChildCategories', 'acsEventName' => 'getChildCategories', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to get Product/Category details corresponding to an identifier
    * @param string $categoryID CategoryID
    * @param string $productCategoryApiVersion API version of product categories
    * @return object Api response object
    */
    public function getProductCategoryDetails($categoryID, $productCategoryApiVersion) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $productCategoryApiVersion . "/categories/" . $categoryID . "?withParents=true";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getProductCategoryDetails', 'acsEventName' => 'getProdCatDetails', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to get product/category details corresponding to list of categories
    * @param array $categoryList Array of product/category reference keys
    * @param string $productCategoryApiVersion Product category api version
    * @return object|null Api response object
    */
    public function getProductCategoryListDetails($categoryList, $productCategoryApiVersion = 'v1') {
        if(count($categoryList) > 0 ) {
            $categoryQuery = '';
            for($i = 0; $i < count($categoryList); $i++) {
                $categoryQuery .= ($categoryQuery === '') ? "'" . $categoryList[$i] . "'" : ",'" . $categoryList[$i] . "'";
            }
            $baseUrl = $this->getBaseUrl();
            if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
                return $baseUrl;
            $requestUrl = $baseUrl . $productCategoryApiVersion . "/categories?q=referenceKey+in+(" . $categoryQuery . ")&withParents=true&mode=KEY";
            $response = $this->makeRequest($requestUrl);
            Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getProductCategoryListDetails', 'acsEventName' => 'getPrdCtLstDetails', 'apiDuration' => $this->apiDuration));
            $this->apiDuration = 0;
        }
        return $response;
    }

    /**
    * Method to update article rating
    * @param string $surveyRecordID Survey recordID
    * @param string $answerRecordID AnswerID
    * @param string $contentRecordID ContentID
    * @param string $localeRecordID Locale of the article
    * @param string $answerComment Feedback comment for the rating provided
    * @return object Api response object
    */
    public function submitRating($surveyRecordID, $answerRecordID, $contentRecordID, $localeRecordID, $answerComment) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/content/{$contentRecordID}/rate";
        $postData = array(
            'survey' => array('recordId' => $surveyRecordID),
            'locale' => array('recordId' => $localeRecordID),
            'answer' => array('recordId' => $answerRecordID),
            'answerComment' => $answerComment
        );
        return $this->makeRequest($requestUrl, 'POST', json_encode($postData));
    }

    /**
    * Method to update search rating
    * @param int $rating User rating
    * @param string $feedback User feedback
    * @param int $priorTransactionID Prior TransactionID
    * @param string $searchSession Search Session
    * @return object Api response object
    */
    public function submitSearchRating($rating, $feedback, $priorTransactionID, $searchSession) {
        $requestUrl = "{$this->getBaseUrl('SRCH_API_URL')}{$this->okcsApiVersion}/search/feedback?priorTransactionId={$priorTransactionID}&userRating={$rating}&userFeedback={$feedback}";
        $postData = array(
            'session' => $searchSession,
            'requestSource' => self::REQUEST_SOURCE_KEY_VALUE,
            'clientInfo' => array('requestParameters' => array(array('key' => 'charset', 'value' => 'UTF-8')))
        );
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'submitSearchRating', 'acsEventName' => 'submitSearchRating', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets the internal article from Info Manager
    * @param string $answerID AnswerID in Info Manager
    * @param string $status Publish status of the article
    * @param string $answerViewApiVersion API version of answer view
    * @param boolean $isGuestUser Flag to identify if guest user
    * @param boolean $ignoreContentViewEvent Flag to record entry in okcs_stg_content_view table
    * @return object Api response object
    */
    public function getArticle($answerID, $status, $answerViewApiVersion, $isGuestUser = false, $ignoreContentViewEvent = true) {
        $answerViewType = $this->CI->session->getSessionData('answerViewType');
        if(!is_null($answerViewType) && $answerViewType === "okcsAnswerPreview") {
            $answerPreviewDetails = array(
                'answerId' => $answerID,
                'apiVersion' => $answerViewApiVersion,
                'integrationToken' => $this->CI->session->getSessionData('integrationToken'),
                'answerVersionId' => $this->CI->session->getSessionData('answerVersionId'),
                'userGroups' => $this->CI->session->getSessionData('userGroups'));
            return $this->getArticlePreview($answerPreviewDetails);
        }
        $baseUrl = $this->getBaseUrl();
        $kmAuthToken = '';
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$answerViewApiVersion}/content/answers/{$answerID}";
        if(is_null($status) || empty($status) || strtoupper($status) === 'PUBLISHED')
            $queryParameters = array('mode' => 'getArticle', 'contentState' => 'PUBLISHED');
        else
            $queryParameters = array('mode' => 'getArticle', 'contentState' => 'LATESTVALID');
        $requestUrl .= Okcs::getRestQueryString($queryParameters);

        if($this->getHttpUserAgent() === 'rightnow_webindexer') {
            $ignoreContentViewEvent = false;
        }
        if(!$ignoreContentViewEvent) {
            $requestUrl .= '&recordContentViewEvent=false';
        }
        if(!(is_null(Url::getParameter('accessType')))){
            $requestUrl .= '&activityType='.Url::getParameter('accessType');
        }
        if($isGuestUser)
            $kmAuthToken = $this->getGuestUserToken();

        $response = $this->makeRequest($requestUrl, 'GET', '', $kmAuthToken);
        return $response;
    }

    /**
    * Gets the internal article from Info Manager
    * @param array $answerPreviewDetails Array of parameters to fetch InfoManager article
    * @return object Api response object
    */
    private function getArticlePreview($answerPreviewDetails) {
        $baseUrl = $this->getBaseUrl();
        $kmAuthToken = '';
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = "{$baseUrl}{$answerPreviewDetails['apiVersion']}/content/versions/{$answerPreviewDetails['answerVersionId']}/previewThisVersion";

        $kmAuthToken = array(
            'userToken' => '',
            'localeId' => str_replace("-", "_", $this->getInterfaceLocale()),
            'interfaceId' => \RightNow\Api::intf_id(),
            'billableSessionId' => '',
            'knowledgeInteractionId' => '',
            'appId' => self::APP_ID,
            'siteName' => $this->getSiteName(),
            'integrationUserToken' => $answerPreviewDetails['integrationToken']
        );
        $userGroupsArr = array();

        if($answerPreviewDetails['userGroups'] && trim($answerPreviewDetails['userGroups']) !== "") {
            $userGroups = explode(',', $answerPreviewDetails['userGroups']);
            $userGroupCount = !empty($userGroups) ? count($userGroups) : 0;
            for($i = 0; $i < $userGroupCount; $i++) {
                array_push($userGroupsArr, array("referenceKey" => trim($userGroups[$i])));
            }
        }

        $postData = array('userGroups' => $userGroupsArr);

        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData), $kmAuthToken);
        $this->CI->session->setSessionData(array('integrationToken' => ''));
        $this->CI->session->setSessionData(array('answerVersionId' => ''));
        $this->CI->session->setSessionData(array('userGroups' => ''));
        $this->CI->session->setSessionData(array('answerViewType' => ''));
        return $response;
    }

    /**
    * Gets article list for sitemap
    * @param int $pageNumber Page number
    * @param int $pageSize Size of the page
    * @return object Api response object
    */
    public function getArticlesForSiteMap($pageNumber = 0, $pageSize = 0) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $interfaceId = intf_id();
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/content?mode=KEY&interfaceId={$interfaceId}";
        if($pageNumber !== 0 ) {
            $startIndex = ($pageNumber - 1) * $pageSize;
            $requestUrl .= "&offset={$startIndex}&limit={$pageSize}";
        }
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getArticlesForSiteMap', 'acsEventName' => 'ArticlesForSiteMap', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets article list/count for sitemap
    * @param array $siteMapFilter SiteMap details
    * @return object Api response object
    */
    public function getArticlesForSiteMapBatch(array $siteMapFilter) {
        $pageSize = 0;
        $pageNumber = $siteMapFilter['pageNumber'];
        $maxSiteMapLinks = $siteMapFilter['maxSiteMapLinks'];
        $contentStatus = $siteMapFilter['contentStatus'];
        $contentTypes = $siteMapFilter['contentTypes'];

        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $interfaceId = intf_id();
        $batchData = array();
        // For page zero/site map index we are only interested in the count of articles.
        if($pageNumber === 0 ) {
            foreach($siteMapFilter['contentTypes'] as $key => $contentType){
                $contentType = trim(trim($contentType), '""');
                $relativeUrl = "{$this->okcsApiVersion}/contentTypes/{$contentType}/publicDocumentCount";
                $data = array('id' => $key, 'method' => 'GET', 'relativeUrl' => $relativeUrl, 'bodyClassName' => null, 'body' => null);
                array_push($batchData, $data);
            }
        }
        else {
            $startIndex = $siteMapFilter['offSet'];
            $endIndex = $siteMapFilter['offSet'] + ($siteMapFilter['maxPerBatch'] * self::MAX_API_LIMIT);
            $endIndex = $endIndex > $siteMapFilter['pageMax'] ? $siteMapFilter['pageMax'] : $endIndex;
            $limit = self::MAX_API_LIMIT;
            for($count = 0; $startIndex < $endIndex; $count++) {
                $relativeUrl = "{$this->okcsApiVersion}/content?q=filterMode.contentState eq '{$contentStatus}'";
                if(!empty($contentTypes) && $contentTypes !== '*') {
                    $relativeUrl .= " and contentType.referenceKey in {$contentTypes} ";
                }
                if($limit + $startIndex > $endIndex && $pageNumber !== 0) {
                    // Modifying limit for adjust for the last batch if there are less than 1000 records remaining
                    $limit = $endIndex - $startIndex;
                }
                $relativeUrl .= "&offset={$startIndex}&limit={$limit}&interfaceId={$interfaceId}&orderBy=recordId&mode=KEY";
                $data = array('id' => $count, 'method' => 'GET', 'relativeUrl' => $relativeUrl, 'bodyClassName' => null, 'body' => null);
                array_push($batchData, $data);
                if($pageNumber === 0) {
                    $startIndex = $startIndex + $siteMapFilter['pageSize'];
                }
                else {
                    $startIndex = $startIndex + $limit;
                }
            }
        }
        $postData = array('asynchronous' => false, 'requests' => $batchData);
        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/batch";
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getArticlesForSiteMapBatch', 'acsEventName' => 'ArticlesForSiteMap', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to store userData into the cache.
    * @param string $data User data to be cached
    * @param string $action Valid values are 'POST' or 'PUT'
    * @param string $userCacheKey User Cache key
    * @return string Cache Key
    */
    public function cacheUserData($data, $action, $userCacheKey = null) {
        $baseUrl = null;
        $requestUrl = "{$this->getBaseUrl('SRCH_API_URL')}{$this->okcsApiVersion}/keyValueCache";
        $httpHeader = 'kmauthtoken: ' . json_encode($this->getAuthToken(true));
        if($action === 'PUT') {
            $requestUrl .= "/{$userCacheKey}";
        }
        else if($action === 'DELETE') {
            $baseUrl = $this->getBaseUrl('SRCH_API_URL');
            if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
                return $baseUrl;
            //In case of 'DELETE' request, We need to call batch API with POST data
            $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/batch";
            $action = 'POST';
        }

        if(Framework::checkCache($userCacheKey) !== null) {
            $cacheData = Framework::checkCache($userCacheKey);
            if($cacheData === $data){
                return $userCacheKey;
            }
        }
        $apiResponse = $this->curlRequest($requestUrl, $httpHeader, $action, $data);
        if($requestUrl === "{$baseUrl}{$this->okcsApiVersion}/batch")
            return $apiResponse;
        $apiResponse = $apiResponse->result;
        $statusCode = $apiResponse['statusCode'];
        //retry if request is failed
        if(!($statusCode === self::OKCS_CREATED_RESPONSE || $statusCode === self::OKCS_NO_CONTENT_RESPONSE)) {
            $this->getMemCache()->set(self::INTERNAL_INTEGRATION_TOKEN, false);
            $httpHeader = 'kmauthtoken: ' . json_encode($this->getAuthToken(true));
            $apiResponse = $this->curlRequest($requestUrl, $httpHeader, $action, $data)->result;
            $statusCode = $apiResponse['statusCode'];
            //return INVALID_CACHE_KEY string if API is not successfull.
            if(!($statusCode === self::OKCS_CREATED_RESPONSE || $statusCode === self::OKCS_NO_CONTENT_RESPONSE)) {
                return self::INVALID_CACHE_KEY;
            }
            else {
                if($action === 'POST'){
                    Framework::setCache($apiResponse['response'], $data, true);
                } else if($action === 'GET' || $action === 'PUT'){
                    Framework::setCache($userCacheKey, $data, true);
                }
            }
        }
        else {
            if($action === 'POST'){
                Framework::setCache($apiResponse['response'], $data, true);
            } else if($action === 'GET' || $action === 'PUT'){
                Framework::setCache($userCacheKey, $data, true);
            }
        }
        return $apiResponse['response'];
    }

    /**
    * Method to return cacheKey value
    * @param string $userCacheKey User Cache key
    * @return bool|object CacheKey value. This will return cache Key if there are no errors.
    */
    public function getCacheData($userCacheKey) {
        $cacheResponse = (!$userCacheKey) ? null : Framework::checkCache($userCacheKey);
        if (!is_null($cacheResponse))
            return $cacheResponse;

        $requestUrl = "{$this->getBaseUrl('SRCH_API_URL')}{$this->okcsApiVersion}/keyValueCache/{$userCacheKey}";
        $httpHeader = 'kmauthtoken: ' . json_encode($this->getAuthToken(true));
        $apiResponse = $this->curlRequest($requestUrl, $httpHeader, 'GET', '')->result;
        //retry if request is failed
        if($apiResponse['statusCode'] !== self::OKCS_OK_RESPONSE) {
            if(isset($apiResponse['response']->error) && json_decode($apiResponse['response'])->error[0]->code === 'OKDOM-GEN0002') return false;
            $this->getMemCache()->set(self::INTERNAL_INTEGRATION_TOKEN, false);
            $httpHeader = 'kmauthtoken: ' . json_encode($this->getAuthToken(true));
            $apiResponse = $this->curlRequest($requestUrl, $httpHeader, 'GET', '')->result;
            if($apiResponse['statusCode'] !== self::OKCS_OK_RESPONSE) return false;
        }
        Framework::setCache($userCacheKey, $apiResponse['response'], true);
        return $apiResponse['response'];
    }

    /**
    * Method to retrieve file attachments
    * @param string $attachmentUrl Attachment Url
    * @return object Attachment data
    */
    public function getAttachment($attachmentUrl) {
        if (!extension_loaded('curl') && !@Api::load_curl())
            return null;
        $ch = curl_init();
        $apiAndHost = $this->getInternalApiAndHost($attachmentUrl);
        $apiHostHeader = $apiAndHost[self::API_HOST] !== null ? 'Host: ' . $apiAndHost[self::API_HOST] : null;
        $options = array(
            CURLOPT_URL => $apiAndHost[self::INTERNAL_URL],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array($apiHostHeader)
        );

        if(Framework::isLoggedIn())
            array_push($options[CURLOPT_HTTPHEADER], 'kmauthtoken: ' . json_encode($this->getAuthToken()));

        curl_setopt_array($ch, $options);

        //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the OKCS API
        //Forcing commit before disconnecting
        $hooks = &load_class('Hooks');
        $hooks->_run_hook(array(
                            'class' => 'RightNow\Hooks\SqlMailCommit',
                            'function' => 'commit',
                            'filename' => 'SqlMailCommit.php',
                            'filepath' => 'Hooks'
                        ));
        //closing the open connection.
        Api::sql_disconnect();

        $response = @curl_exec($ch);
        //Open database connection for further use.
        Api::sql_open_db();

        if (curl_errno($ch) || Text::stringContains($response, 'OK-GEN0017')) {
            $response = 'ERROR:3';
        }
        else if(Text::stringContains($response, 'OK-FMS0003')) {
            $response = 'ERROR:1';
        }
        curl_close($ch);
        return $response;
    }

    /**
    * Method to set user language preferences.
    * @param string $userLanguages Selected languages
    * @param object $preferredLanguage User preferred language
    * @param string $userRecordID User recordID
    * @return object Api response object
    */
    public function setUserLanguagePreference($userLanguages, $preferredLanguage, $userRecordID) {
        $preferredLanguageRecordID = null;
        if(!is_null($preferredLanguage)){
            $preferredLanguageRecordID = $preferredLanguage->recordId;
            $preferredLanguageDateAdded = $preferredLanguage->dateAdded;
            $preferredLanguageDateModified = $preferredLanguage->dateModified;
        }
        $methodType = (is_null($preferredLanguageRecordID) || empty($preferredLanguageRecordID)) ? 'POST' : 'PUT';
        $requestUrl = "{$this->getBaseUrl()}{$this->okcsApiVersion}/users/{$userRecordID}/userKeyValues";
        if($methodType === 'PUT') {
            $requestUrl .= "/{$preferredLanguageRecordID}";
            $postData = array(
                'dateAdded' => $preferredLanguageDateAdded,
                'dateModified' => $preferredLanguageDateModified,
                'key' => 'search_language',
                'value' => $userLanguages,
                'recordId' => $preferredLanguageRecordID,
                'userInformation' => array('recordId' => $userRecordID)
            );
        }
        else {
            $postData = array(
                'key' => 'search_language',
                'value' => $userLanguages,
                'userInformation' => array('recordId' => $userRecordID)
            );
        }
        $response = $this->makeRequest($requestUrl, $methodType, json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'setUserLanguagePreference', 'acsEventName' => 'setUserLanguage', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets article list for notification
    * @param string $userID Logged in user
    * @param string $answerId Answer Id for which to get subscription details
    * @return object Api response object
    */
    public function getSubscriptionList($userID, $answerId = null) {
        $baseUrl = $this->getBaseUrl();
        $answerIdParam = '';
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        if ($answerId)
            $answerIdParam = "&answerId={$answerId}";
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions?orderBy=subscriptionType:desc,dateAdded:desc&mode=FULL" . $answerIdParam;
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getSubscriptionList', 'acsEventName' => 'SubscriptionList', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets sorted notification list
    * @param string $userID Logged in user
    * @param string $sortColumn Sorting column
    * @param string $direction Sorting order
    * @return object Api response object
    */
    public function getSortedSubscriptionList($userID, $sortColumn, $direction) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $sortColumn = ($sortColumn === 'documentId') ? 'name' : 'dateAdded';
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions?orderBy={$sortColumn}:{$direction}&mode=FULL";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getSortedSubscriptionList', 'acsEventName' => 'SubscriptionList', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets contentType based notification list
    * @param string $userID Logged in user
    * @param string $offset Offset in the notification list
    * @param string $limit Limit of the notification list
    * @return object Api response object
    */
    public function getContentTypeSubscriptionList($userID, $offset, $limit) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions?orderBy=subscriptionType&mode=FULL&offset={$offset}&limit={$limit}";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getContentTypeSubscriptionList', 'acsEventName' => 'SubscriptionList', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Gets subscription details
    * @param array $batchData An array of subscriptionIDs
    * @return object Api response object
    */
    public function getSubscriptionDetails(array $batchData) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $a = $this->makeRequest("{$baseUrl}{$this->okcsApiVersion}/batch", 'POST', json_encode($batchData));
        return $a;
    }

    /**
    * Gets content data for doc id's
    * @param string $docIdQuery Doc id's of Info Manager articles
    * @return object Api response object
    */
    public function getAnswerForDocId($docIdQuery) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $localeCode = str_replace("-", "_", $this->getInterfaceLocale());
        $ansLimit = count(explode(',', $docIdQuery));
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/content?q=locale.recordId+in+('{$localeCode}')+and+documentId+in+({$docIdQuery})&mode=key&limit={$ansLimit}";
        if($ansLimit < self::MAX_API_LIMIT && strlen($requestUrl) < self::MAX_URL_LIMIT) {
            $response = $this->makeRequest($requestUrl);
        }
        else {
            $baseRequestUrl = $this->okcsApiVersion . "/content?q=locale.recordId in ('{$localeCode}') and documentId in(";
            $requestArray = $this->createPostData($baseRequestUrl, $docIdQuery);
            $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/batch";
            $postData = array(
                'asynchronous' => false,
                'requests' => $requestArray
            );
            $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
            $mergedArray = array();
            foreach($response->items as $value) {
                $itemsObject = (json_decode($value->body));
                $mergedArray = array_merge($mergedArray, $itemsObject->items);
            }
            $response->items = $mergedArray;
        }
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getAnswerForDocId', 'acsEventName' => 'getAnswerForDocId', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Create post data for batch call for fetching answerids for documentids
    * @param string $baseRequestUrl Partial requestUrl for contructing relative url
    * @param string $docIdQuery Query of comma separated document ids
    * @return object Api response object
    */
    private function createPostData($baseRequestUrl, $docIdQuery) {
        $ansLimit = count(explode(',', $docIdQuery));
        $maxApiLimit = self::MAX_API_LIMIT;
        $batchLimit = ($ansLimit / $maxApiLimit);
        $intermedCount = ($ansLimit % $maxApiLimit) !== 0 ? $ansLimit % $maxApiLimit : 0;
        $batchCount = $ansLimit > $maxApiLimit ? ($intermedCount === 0 ? $batchLimit : $batchLimit + 1) : 1;
        $requestArray = array();
        $docIdArr = ((explode(',', $docIdQuery)));
        $sliceCount = 0;
        for($count = 1; $count <= $batchCount; $count++) {
            $slicedArray = array_slice($docIdArr, $sliceCount, $maxApiLimit);
            $queryStr = implode(',', $slicedArray);
            $sliceCount += $maxApiLimit;
            $limit = substr_count($queryStr, ',') + 1;
            $relativeUrl = $baseRequestUrl . $queryStr . ')&mode=key&limit=' . $limit . '"';
            //Iteration logic for length of url exceeding 4000
            if(strlen($relativeUrl) > self::MAX_URL_LIMIT) {
                $urlArray = $this->splitUrl($queryStr);
                $batchCount += count($urlArray);
                foreach($urlArray as $url) {
                    $limit = substr_count($url, ',') + 1;
                    $relativeUrl = $baseRequestUrl . $url . ')&mode=key&limit=' . $limit;
                    $data = array('id' => $count, 'method' => 'GET', 'relativeUrl' => $relativeUrl);
                    array_push($requestArray, $data);
                    ++$count;
                }
            }
            else {
                    $data = array('id' => $count, 'method' => 'GET', 'relativeUrl' => $relativeUrl);
                    array_push($requestArray, $data);
            }
        }
        return $requestArray;
    }

    /**
    * Convert a long comma separated string into an array of shorter strings
    * @param string $queryStr Query of omma separated document ids
    * @return object Api response object
    */
    private function splitUrl($queryStr) {
        $lenSubStr = 0;
        $lenSubStrInter = strrpos(substr($queryStr, $lenSubStr, self::MAX_URL_LIMIT), ',');
        $urlArray = array();
        while($lenSubStrInter = strrpos(substr($queryStr, $lenSubStr, self::MAX_URL_LIMIT), ',')) {
                $splitStr = substr($queryStr, $lenSubStr, $lenSubStrInter);
                array_push($urlArray, $splitStr);
            $lenSubStr += $lenSubStrInter + 1;
        }
        return $urlArray;
    }

    /**
    * Unsubscribe answer
    * @param string $userID Logged in user
    * @param string $subscriptionID SubscriptionID
    * @return object Api response object
    */
    public function unsubscribeAnswer($userID, $subscriptionID) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions/{$subscriptionID}";
        $response = $this->makeRequest($requestUrl, 'DELETE');
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'unsubscribeAnswer', 'acsEventName' => 'UnsubscribeAnswer', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Retry integration token generation in case of bad request
    * @param string $url Rest api url
    * @param string $methodType Type of http method e.g. 'GET' or 'POST'
    * @param string $postData Post data
    * @return object Api response object
    */
    private function retryBadRequest($url, $methodType, $postData) {
        $retryResponse = null;
        if($this->tokenRetryFlag) {
            if($this->retryCount++ < $this->retryAttempts) {
                $this->getMemCache()->set(self::CP_INTEGRATION_TOKEN, false);
                $retryResponse = $this->makeRequest($url, $methodType, $postData);
            }
            $this->tokenRetryFlag = true;
            $this->retryCount = 0;
        }
        return $retryResponse;
    }

    /**
    * Make request to OKCS REST API endpoint. Handles all authentication bits as well.
    * @param string $url Rest api url
    * @param string $methodType Type of http method e.g. 'GET' or 'POST'
    * @param string $postData Post data
    * @param string $kmAuthToken API header token
    * @return object Api response object
    */
    private function makeRequest($url, $methodType = 'GET', $postData = '', $kmAuthToken = '') {
        $isGuestRequest = $kmAuthToken === '';
        $kmAuthToken = ($kmAuthToken === '') ? $this->getAuthToken() : $kmAuthToken;
        $okcsUrl = @parse_url($url);
        $okcsProtocol = strtoupper($okcsUrl['scheme']);
        $httpHeader = 'kmauthtoken: ' . json_encode($kmAuthToken);
        $cpIntegTokenInvalid = Framework::checkCache(self::CP_INTEGRATION_TOKEN_INVALID);
        if($cpIntegTokenInvalid !== null && 'INVALID' === $cpIntegTokenInvalid ) {
            $cpIntegTokenMessage = 'HTTP: ' . Framework::checkCache(self::CP_INTEGRATION_TOKEN_RESP_HEADER) . ' ,Error in token generation';
            Okcs::eventLog(array('requestUrl' => $url, 'response' => $cpIntegTokenMessage, 'requestOrigin' => 'makeRequest', 'acsEventName' => 'makeRequest', 'postData' => $postData));
            return $this->getResponseObject(null, null, $cpIntegTokenMessage);
        }
        if(is_null($kmAuthToken['integrationUserToken']) || empty($kmAuthToken['integrationUserToken'])) {
            $cpIntegTokenMessage = 'HTTP: ' . Framework::checkCache(self::CP_INTEGRATION_TOKEN_RESP_HEADER) . ' ,Error in token generation';
            Framework::setCache(self::CP_INTEGRATION_TOKEN_INVALID, 'INVALID', true);
            Okcs::eventLog(array('requestUrl' => $url, 'response' => $cpIntegTokenMessage, 'requestOrigin' => 'makeRequest', 'acsEventName' => 'makeRequest', 'postData' => $postData));
            return $this->getResponseObject(null, null, $cpIntegTokenMessage);
        }
        $apiResponse = $this->curlRequest($url, $httpHeader, $methodType, $postData)->result;

        if($this->customApiCall) {
            $this->customApiCall = false;
            return $apiResponse;
        }

        $content = $apiResponse['response'];
        $statusCode = $apiResponse['statusCode'];
        $response = json_decode($content);

        if(isset($response->errors) && !is_null($response->errors) && $response->errors[0]->code === self::TOKEN_ERROR_CODE) {
            if(!$isGuestRequest)
                $this->CI->session->setSessionData(array(self::USER_CACHE_KEY => ''));
            $this->getMemCache()->set(self::INTERNAL_INTEGRATION_TOKEN, false);
            $this->getMemCache()->set(self::CP_INTEGRATION_TOKEN, false);
            $statusCode = self::OKCS_FORBIDDEN_REQUEST;
        }
        else if ($response === null && $statusCode === null) {
            $statusCode = self::OKCS_INTERNAL_SERVER_ERROR;
        }

        $statusCode = $statusCode === 0 ? self::OKCS_TIMEOUT_RESPONSE : $statusCode;
        switch($statusCode){
            case self::OKCS_TIMEOUT_RESPONSE:
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(API_REQUEST_TIMED_OUT_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_NO_CONTENT_RESPONSE:
                $response = json_decode($content);
                break;
            case self::OKCS_BAD_REQUEST:
                $retryResponse = $this->retryBadRequest($url, $methodType, $postData);
                if($retryResponse !== null)
                    $response = $retryResponse;
                else
                    $response = $this->getResponseObject($response->errorDetails[0]->errorCode, null, new \RightNow\Libraries\ResponseError(Config::getMessage(MALFORMED_URL_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_FORBIDDEN_REQUEST:
                if($this->tokenRetryFlag) {
                    if($this->retryCount++ < $this->retryAttempts)
                        $this->makeRequest($url, $methodType, $postData, $kmAuthToken);

                    $this->tokenRetryFlag = true;
                    $this->retryCount = 0;
                }
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(INVALID_CREDENTIALS_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_NOT_ALLOWED_REQUEST:
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(METHOD_NOT_ALLOWED_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_NOT_FOUND_REQUEST:
                $response = $this->getResponseObject(isset($response->errorDetails[0]->errorCode) ? $response->errorDetails[0]->errorCode : null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(RESOURCE_NOT_FOUND_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_CONFLICT_REQUEST:
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(CONFLICT_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_INTERNAL_SERVER_ERROR:
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError($this->isDbFailover($response) ? Config::getMessage(DATABASE_FAILOVER_IN_SESS_ERR_SOMETIME_MSG) : Config::getMessage(API_UNAVAILABLE_LBL), 'HTTP ' . $statusCode, $url));
                break;
            case self::OKCS_SERVICE_UNAVAILABLE:
                $response = $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(Config::getMessage(SERVICE_TEMPORARILY_UNAVAILABLE_LBL), 'HTTP ' . $statusCode, $url));
                break;
            default:
                $response = json_decode($content);
        }
        return $response;
    }

    /**
    * Gets authentication token required to call REST APIs.
    * @param boolean $isInternalTokenAvailable This flag is used to decide whether an internalToken is required or not
    * @return string Authenticated token
    */
    private function getAuthToken($isInternalTokenAvailable = false) {
        $userToken = null;
        $cpSession = $this->CI->session->getSessionData('sessionID');
        if($isInternalTokenAvailable) {
            $internalIntegrationToken = $this->getMemCache()->get(self::INTERNAL_INTEGRATION_TOKEN);
            if((is_bool($internalIntegrationToken) && !$internalIntegrationToken) || is_null($internalIntegrationToken)) {
                $internalIntegrationToken = $this->getIntegrationUserToken(Config::getConfig(OKCS_IU_INTERNAL_USERNAME), Config::getConfig(OKCS_IU_INTERNAL_PASSWORD));
                $this->getMemCache()->set(self::INTERNAL_INTEGRATION_TOKEN, $internalIntegrationToken);
            }
        }
        else {
            $userToken = $this->getUserTokenFromCache();
            $cpIntegrationToken = $this->getMemCache()->get(self::CP_INTEGRATION_TOKEN);
            if((is_bool($cpIntegrationToken) && !$cpIntegrationToken) || is_null($cpIntegrationToken)) {
                $cpIntegrationToken = $this->getIntegrationUserToken(Config::getConfig(OKCS_IU_CP_USERNAME), Config::getConfig(OKCS_IU_CP_PASSWORD));
                $this->getMemCache()->set(self::CP_INTEGRATION_TOKEN, $cpIntegrationToken);
            }
        }
        $authToken = array(
            'localeId' => str_replace("-", "_", $this->getInterfaceLocale()),
            'interfaceId' => \RightNow\Api::intf_id(),
            'billableSessionId' => Config::getConfig(ACS_BILLING_ID),
            'knowledgeInteractionId' => $cpSession,
            'appId' => self::APP_ID,
            'siteName' => $this->getSiteName(),
            'integrationUserToken' => $isInternalTokenAvailable ? $internalIntegrationToken : $cpIntegrationToken,
            'clientIP' => get_instance()->input->ip_address(),
            'referrer' => $this->cleanseReferrer()
        );

        if(!is_null($userToken))
            $authToken['userToken'] = $userToken;
        if($this->getHttpUserAgent() === 'rightnow_webindexer')
            $authToken['captureAnalytics'] = false;
        return $authToken;
    }

    /**
    * Fetches the referrer and encodes value of kw parameter.
    * @return string Referrer
    */
    private function cleanseReferrer() {
        $referrer = $this->getHttpReferer();
        if(!isset($referrer))
        {
            return $referrer;
        }
        $refUrl = parse_url(htmlspecialchars_decode($referrer, ENT_QUOTES));
        $pathData = array();
        $toBeEncoded = false;
        $exploded = explode('/', $refUrl['path']);
        foreach($exploded as $key => $param)
        {
            if($param === 'kw' || $toBeEncoded) {
                $toBeEncoded = true;
                array_push($pathData, urlencode($param));
                if($param !== 'kw') {
                    $toBeEncoded = false;
                }
            }
            else {
                array_push($pathData, $param);
            }
        }
        $encodedPathString = implode('/', $pathData);
        $cleansedReferrer = $refUrl['scheme'] . '://' . $refUrl['host'] . $encodedPathString;
        return $cleansedReferrer;
    }

    /**
    * Gets authentication token required to call cache API.
    * @return string api token
    */
    private function getUserTokenFromCache() {
        $userTokenCache = Framework::checkCache(self::OKCS_USER_TOKEN);
        if (!is_null($userTokenCache))
            return $userTokenCache;
        $userCacheKey = $this->CI->session->getSessionData(self::USER_CACHE_KEY);
        $isLoggedInUser = Framework::isLoggedIn();
        $keySuffix = $isLoggedInUser ? $this->getLoggedInUser() : self::GUEST_USER;
        if(!Framework::isLoggedIn()) {
            return '';
        }
        if((!is_null($userCacheKey) && (strlen($userCacheKey) > 0) ) || ($isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $this->getLoggedInUser())) ||
            (!$isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $keySuffix))){
            return $this->getUserToken();
        }
        else {
            $userCacheKey = Text::getSubstringBefore($userCacheKey, '_' . $keySuffix);
            $cacheData = (array)json_decode($this->getCacheData($userCacheKey));
            Framework::setCache(self::OKCS_USER_TOKEN, $cacheData['userToken'], true);
            if((is_bool($cacheData) && !$cacheData) || (is_null($cacheData['userToken']) || empty($cacheData['userToken']))) {
                return $this->getUserToken();
            }
            return $cacheData['userToken'];
        }
    }

    /**
    * Gets the user token to generate KMAuth token which is required to call REST APIs.
    * @return object|string User token response object
    */
    private function getUserToken() {
        $isSSOEnabled = true;
        if(IS_DEVELOPMENT){
            $isSSOEnabled = $this->get('SSO_ENABLED');
            if(is_null($isSSOEnabled))
                $isSSOEnabled = true;
        }
        $keySuffix = Framework::isLoggedIn() ? $this->getLoggedInUser() : self::GUEST_USER;
        $internalIntegrationToken = $this->getMemCache()->get(self::INTERNAL_INTEGRATION_TOKEN);
        if(empty($internalIntegrationToken)) {
            $internalIntegrationToken = $this->getIntegrationUserToken(Config::getConfig(OKCS_IU_INTERNAL_USERNAME), Config::getConfig(OKCS_IU_INTERNAL_PASSWORD));
        }
        $this->getMemCache()->set(self::INTERNAL_INTEGRATION_TOKEN, $internalIntegrationToken);
        if($isSSOEnabled) {
            $userToken = OkcsSamlAuth::authenticate($internalIntegrationToken);
            Framework::setCache(self::OKCS_USER_TOKEN, $userToken, true);
            $cacheKey = $this->cacheUserData(json_encode(array('userToken' => $userToken)), $action = 'POST');
            if($cacheKey === self::INVALID_CACHE_KEY)
                $this->CI->session->setSessionData(array(self::USER_CACHE_KEY => ''));
            else
                $this->CI->session->setSessionData(array(self::USER_CACHE_KEY => $cacheKey . '_' . $keySuffix));
        }
        else {
            $requestUrl = $this->getBaseUrl('IM_APP_URL') . 'wa/generateAPIToken';
            // Accessing dev Url as SSO is not enabled
            $requestUrl = str_replace('WebObjects', 'dev/WebObjects', $requestUrl);
            $tokenHeader = 'devtoken:' . json_encode(array('login' => $this->getUser(), 'userType' => self::CONTACT_USER_TYPE)) . '#integrationUserToken:' . $internalIntegrationToken;
            $apiResponse = $this->curlRequest($requestUrl, $tokenHeader)->result;
            if($apiResponse['statusCode'] === self::OKCS_OK_RESPONSE) {
                $userToken = $apiResponse['response'];
                $cacheKey = $this->cacheUserData(json_encode(array('userToken' => $userToken)), $action = 'POST');
                if($cacheKey === self::INVALID_CACHE_KEY)
                    $this->CI->session->setSessionData(array(self::USER_CACHE_KEY => ''));
                else
                    $this->CI->session->setSessionData(array(self::USER_CACHE_KEY => $cacheKey . '_' . $keySuffix));
            }
            else {
                $userToken = $this->getResponseObject(null, null, 'Invalid user token');
            }
            Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $apiResponse, 'requestOrigin' => 'getUserToken', 'acsEventName' => 'getUserToken', 'tokenHeader' => $tokenHeader, 'apiDuration' => $this->apiDuration));
            $this->apiDuration = 0;
        }
        return $userToken;
    }

    /**
    * Gets the integration user token.
    * @param string $user Username
    * @param string $password Password
    * @return object Integration user token
    */
    private function getIntegrationUserToken($user, $password) {
        $requestUrl = $this->getBaseUrl() . $this->okcsApiVersion . '/auth/integration/authorize';
        $siteName = $this->getSiteName();
        $postData = array(
            'login' => $user,
            'password' => $password,
            'siteName' => $siteName
        );
        $token = array(
            'siteName' => $siteName,
            'localeId' => str_replace("-", "_", $this->getInterfaceLocale())
        );
        $httpHeader = 'kmauthtoken: ' . json_encode($token);
        $apiResponse = $this->curlRequest($requestUrl, $httpHeader, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $apiResponse, 'requestOrigin' => 'getIntegrationUserToken', 'acsEventName' => 'getIntUserToken', 'tokenHeader' => $httpHeader, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        Framework::setCache(self::CP_INTEGRATION_TOKEN_RESP_HEADER, $apiResponse->result['statusCode'], true);
        $decodedResponse = json_decode($apiResponse->result["response"]);
        return isset($decodedResponse->authenticationToken) ? $decodedResponse->authenticationToken : null;
    }

    /**
    * Gets the guest user token.
    * @return array User token array
    */
    private function getGuestUserToken() {
        $cpIntegrationToken = $this->getIntegrationUserToken(Config::getConfig(OKCS_IU_CP_USERNAME), Config::getConfig(OKCS_IU_CP_PASSWORD));
        return array(
            'userToken' => '',
            'localeId' => str_replace("-", "_", $this->getInterfaceLocale()),
            'interfaceId' => \RightNow\Api::intf_id(),
            'billableSessionId' => '',
            'knowledgeInteractionId' => '',
            'appId' => self::APP_ID,
            'siteName' => $this->getSiteName(),
            'integrationUserToken' => $cpIntegrationToken
        );
    }

    /**
    * Method fetch time-out value based on endpoint
    * @param string $requestUrl API url
    * @return int Timeout value
    */
    private function getApiTimeOut($requestUrl) {
        // Temporary fix to address batch API timeout
        if(Text::stringContains($requestUrl, '/batch'))
           return self::TIMEOUT_MULTIPLIER_FACTOR * Config::getConfig(OKCS_API_TIMEOUT);
        return Config::getConfig(OKCS_API_TIMEOUT);
    }

    /**
    * Method to call APIs through curl
    * @param string $requestUrl API url
    * @param string $tokenHeader HTTP token header
    * @param string $methodType HTTP method type, GET or POST
    * @param string $postData Post data
    * @return array Array of response and http statusCode
    */
    private function curlRequest($requestUrl, $tokenHeader, $methodType = null, $postData = null) {
        if(strpos($methodType, 'POST') !== false && Text::stringContains($requestUrl, '/contentRecommendations') && array_key_exists('file', json_decode($postData, true))){
            return $this->curlRequestCreateRecommendationWithAttachment($requestUrl, $tokenHeader, $methodType, $postData);
        }
        if (!extension_loaded('curl') && !@Api::load_curl())
            return null;
        $ch = $this->getCurlHandle($requestUrl);
        $apiAndHost = $this->getInternalApiAndHost($requestUrl);
        $apiHostHeader = $apiAndHost[self::API_HOST] !== null ? 'Host: ' . $apiAndHost[self::API_HOST] : null;
        $options = array(
            CURLOPT_URL => $apiAndHost[self::INTERNAL_URL],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT_MS => $this->getApiTimeOut($requestUrl),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                $apiHostHeader,
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Content-Type: application/json; charset=UTF-8',
                'User-Agent: ' . $this->getHttpUserAgent(),
                'Host: ' . $this->getHost(),
                'Referer: ' . $this->getHttpReferer()
            )
        );

        $headers = explode('#', $tokenHeader);
        for($count = 0; $count < count($headers); $count++) {
            array_push($options[CURLOPT_HTTPHEADER], $headers[$count]);
        }
        if($methodType !== null)
            $options[CURLOPT_CUSTOMREQUEST] = $methodType;
        if($postData !== null)
            $options[CURLOPT_POSTFIELDS] = $postData;
        curl_setopt_array($ch, $options);

        //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the OKCS API
        //Forcing commit before disconnecting
        $hooks = &load_class('Hooks');
        $hooks->_run_hook(array(
                            'class' => 'RightNow\Hooks\SqlMailCommit',
                            'function' => 'commit',
                            'filename' => 'SqlMailCommit.php',
                            'filepath' => 'Hooks'
                        ));
        //closing the open connection.
        Api::sql_disconnect();

        $response = @curl_exec($ch);
        //Open database connection for further use.
        Api::sql_open_db();

        $info = curl_getinfo($ch);
        $statusCode = $info['http_code'];
        $this->apiDuration = $info['total_time'];
        $timingArray = array(
                            array('key' => ($requestUrl . ' | ' . $methodType . ' | ' . 'Status code - ' . $statusCode), 'value' => $this->apiDuration)
                        );
        $response = array('response' => $response, 'statusCode' => $statusCode);
        Okcs::setTimingToCache('timingCacheKey', $timingArray);
        return $this->getResponseObject($response, 'is_array');
    }

    /**
    * Method to get existing curl handle
    * @param string $requestUrl API url
    * @return object Curl handle object
    */
    private function getCurlHandle($requestUrl) {
        if(Text::stringContains($requestUrl, '/km/')) {
            $curlHandle = $this->imCurlHandle;
            if(gettype($curlHandle) === 'NULL' || gettype($curlHandle) === self::UNKNOWN_TYPE)
                $curlHandle = $this->imCurlHandle = curl_init();
        }
        else if(Text::stringContains($requestUrl, '/srt/')) {
            $curlHandle = $this->searchCurlHandle;
            if(gettype($curlHandle) === 'NULL' || gettype($curlHandle) === self::UNKNOWN_TYPE)
                $curlHandle = $this->searchCurlHandle = curl_init();
        }
        else {
            $curlHandle = $this->imConsoleCurlHandle;
            if(gettype($curlHandle) === 'NULL' || gettype($curlHandle) === self::UNKNOWN_TYPE)
                $curlHandle = $this->imConsoleCurlHandle = curl_init();
        }
        return $curlHandle;
    }

    /**
    * Method to retrieve Internal API URL's and API hosts
    * @param string $requestUrl API url
    * @return array Array of internal API and host
    */
    private function getInternalApiAndHost($requestUrl) {
        $apiAndHost = array();
        if(strpos($requestUrl, $this->getBaseUrl()) !== false){
            $imInternalUrl = $this->getBaseUrl('IM_API_INTERNAL_URL');
            if($imInternalUrl !== null && $imInternalUrl !== '' && $imInternalUrl->error == null){
                $apiAndHost[self::INTERNAL_URL] = str_replace(parse_url($requestUrl, PHP_URL_HOST), parse_url($imInternalUrl, PHP_URL_HOST), $requestUrl);
                $apiAndHost[self::API_HOST] = parse_url($requestUrl, PHP_URL_HOST);
                return $apiAndHost;
            }
        }

        else if(strpos($requestUrl, $this->getBaseUrl('SRCH_API_URL')) !== false){
            $srchInternalUrl = $this->getBaseUrl('SRCH_INTERNAL_URL');
            if($srchInternalUrl !== null && $srchInternalUrl !== '' && $srchInternalUrl->error == null){
                $apiAndHost[self::INTERNAL_URL] = str_replace(parse_url($requestUrl, PHP_URL_HOST), parse_url($srchInternalUrl, PHP_URL_HOST), $requestUrl);
                $apiAndHost[self::API_HOST] = parse_url($requestUrl, PHP_URL_HOST);
                return $apiAndHost;
            }
        }

        else if(strpos($requestUrl, $this->getBaseUrl('IM_APP_URL')) !== false){
            $imInternalAppUrl = $this->getBaseUrl('IM_APP_INTERNAL_URL');
            if($imInternalAppUrl !== null && $imInternalAppUrl !== '' && $imInternalAppUrl->error == null){
                $apiAndHost[self::INTERNAL_URL] = str_replace(parse_url($requestUrl, PHP_URL_HOST), parse_url($imInternalAppUrl, PHP_URL_HOST), $requestUrl);
                $apiAndHost[self::API_HOST] = parse_url($requestUrl, PHP_URL_HOST);
                return $apiAndHost;
            }
        }

        else {
            $attachmentInternalUrl = $this->getBaseUrl('IM_ATTACHMENT_INTERNAL_URL');
            if($attachmentInternalUrl !== null && $attachmentInternalUrl !== '' && $attachmentInternalUrl->error == null){
                $apiAndHost[self::INTERNAL_URL] = str_replace(parse_url($requestUrl, PHP_URL_HOST), parse_url($attachmentInternalUrl, PHP_URL_HOST), $requestUrl);
                $apiAndHost[self::API_HOST] = parse_url($requestUrl, PHP_URL_HOST);
                return $apiAndHost;
            }
        }
        $apiAndHost[self::INTERNAL_URL] = $requestUrl;
        $apiAndHost[self::API_HOST] = null;
        return $apiAndHost;
    }

    /**
    * Method to create Request Header for REST API call
    * @return array populated with required header values
    */
    private function createRequestHeader() {
        return array(
            array( 'key' => 'accept-language', 'value' => $this->getInterfaceLocale() ),
            array( 'key' => 'accept', 'value' => '*/*' ),
            array( 'key' => 'accept-encoding', 'value' => 'gzip, deflate' ),
            array( 'key' => 'user-agent', 'value' => $this->getHttpUserAgent() ),
            array( 'key' => 'connection', 'value' => 'keep-alive' )
        );
    }

    /**
    * Method to get host address
    * @return string host address
    */
    private function getHost() {
        $host = null;
        if(isset($_SERVER['REMOTE_ADDR'])) {
            $host = $_SERVER['REMOTE_ADDR'];
        }
        return $host;
    }

    /**
    * Method to get page referer
    * @return string Http page referer
    */
    private function getHttpReferer() {
        $referer = null;
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }
        return $referer;
    }

    /**
    * Method to get http user agent
    * @return string Http user agent
    */
    private function getHttpUserAgent() {
        $httpUserAgent = null;
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        return $httpUserAgent;
    }

    /**
    * Method to get base API urls
    * @param string $apiType Type of API call
    * @return string|null Api base url
    */
    private function getBaseUrl($apiType = 'IM_API_URL') {
        $baseUrl = null;
        switch($apiType) {
            case "IM_API_INTERNAL_URL":
                $baseUrl = Config::getConfig(OKCS_IM_API_INTERNAL_URL);
                break;
            case "IM_APP_INTERNAL_URL":
                $baseUrl = Config::getConfig(OKCS_IM_APP_INTERNAL_URL);
                break;
            case "SRCH_INTERNAL_URL":
                $baseUrl = Config::getConfig(OKCS_SRCH_API_INTERNAL_URL);
                break;
            case "IM_APP_URL":
                $baseUrl = Config::getConfig(OKCS_IM_APP_URL);
                break;
            case "IM_API_URL":
                $baseUrl = Config::getConfig(OKCS_IM_API_URL);
                break;
            case "SRCH_API_URL":
                $baseUrl = Config::getConfig(OKCS_SRCH_API_URL);
                break;
            case "IM_ATTACHMENT_INTERNAL_URL":
                $baseUrl = Config::getConfig(OKCS_IM_ATTACHMENT_INTERNAL_URL);
                break;
        }

        if($baseUrl === null)
            return $baseUrl;

        if (($baseUrl === '') || Text::stringContains($baseUrl, 'http://host:port'))
           return $this->getResponseObject(false, null, sprintf(Config::getMessage(CONFIG_PCT_S_IS_NOT_SET_MSG), $apiType));

        if (!preg_match("~^(?:f|ht)tps?://~i", $baseUrl))
            return $this->getResponseObject(false, null, sprintf(Config::getMessage(URL_PROTOCOL_FOR_CFG_PCT_S_IS_NOT_MSG), $apiType));

        if (!Text::endsWith($baseUrl, '/'))
            $baseUrl = $baseUrl . '/';

        return $baseUrl;
    }

    /**
    * Method to get site name thru DB name
    * @return string Site name
    */
    private function getSiteName() {
        if(IS_DEVELOPMENT){
            $siteName = $this->get('SITE_NAME');
            if(!is_null($siteName))
                return $siteName;
        }
        return Config::getConfig(DB_NAME);
    }

    /**
    * Method to get key values from the ini file
    * @param string $key Key for the property
    * @return string Key value
    */
    private function get($key) {
        $iniArray = @parse_ini_file(APPPATH . 'config/okcs.ini');
        if(!(is_bool($iniArray) && !$iniArray)) {
            return $iniArray[$key];
        }
        return null;
    }

    /**
    * This method returns logged in or guest user
    * @return string Guest or logged in user id
    */
    private function getUser() {
        return Framework::isLoggedIn() ? $this->getLoggedInUser() : $this->getAnonymousUser();
    }

    /**
    * This method returns intefcae locale
    * @return string Interface Locale
    */
    private function getInterfaceLocale() {
        if(\RightNow\Internal\Utils\Version::compareVersionNumbers(\RightNow\Internal\Utils\Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.7") >= 0) {
            return Okcs::getInterfaceLocale();
        }
        return Text::getLanguageCode();
    }

    /**
    * Method to get anonymousUser
    * @return string Anonymous User
    */
    private function getAnonymousUser() {
        if(IS_DEVELOPMENT){
            $guestUser = $this->get('GUEST_USER');
            if(!is_null($guestUser))
                return $guestUser;
        }
        return strtolower(self::GUEST_USER);
    }

    /**
    * This method returns logged in or guest user
    * @return string Guest or logged in user id
    */
    private function getLoggedInUser() {
        return $this->CI->model('Contact')->get()->result->Login;
    }

    /**
    * Method returns mem cache instance.
    * @return object Cache instance
    */
    private function getMemCache() {
        return ($this->cache === null) ? new \RightNow\Libraries\Cache\Memcache(self::CACHE_TIME_IN_SECONDS) : $this->cache;
    }

    /**
    * Method to add subscription.
    * @param string $userID User identifier
    * @param string $answerID Answer id
    * @param string $versionID Content version id
    * @param string $documentID Document id
    * @return object|string Api Response object
    */
    public function addSubscription($userID, $answerID, $versionID, $documentID) {
        $baseUrl = $this->getBaseUrl();
        if (isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions/";
        $postData = array(
            'content' => array('versionId' => $versionID , 'answerId' => $answerID),
            'name' => $documentID,
            'active' => 'true'
        );

        $response = $this->makeRequest($requestUrl,  'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'addSubscription', 'acsEventName' => 'addSubscription', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to add content type subscription.
    * @param string $userID User identifier
    * @param array $subscriptionData Subscription data
    * @return object|string Api Response object
    */
    public function addContentTypeSubscription($userID, $subscriptionData) {
        $baseUrl = $this->getBaseUrl();
        $prodCatArray = array();
        if($subscriptionData['productRecordId'] )
            array_push($prodCatArray, array('recordId' => $subscriptionData['productRecordId']));
        if($subscriptionData['categoryRecordId'] )
            array_push($prodCatArray, array('recordId' => $subscriptionData['categoryRecordId']));

        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions/";
        $postData = array(
            'contentType' => array('recordId' => $subscriptionData['ctRecordId']),
            'categories' => $prodCatArray,
            'name' => $subscriptionData['name']
        );

        $response = $this->makeRequest($requestUrl,  'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'addContentTypeSubscription', 'acsEventName' => 'addContentType', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }
    
    /**
    * Method to get API response
    * @param string $methodName Name of the method to get Rest API url information
    * @param array $dataArray Data array is an array which includes method type and queryString
    * @param array $postData Post data
    * @return object|string Api Response object
    */
    public function getApiResponse($methodName = '', $dataArray = array(), $postData = array()) {
        if(\RightNow\Internal\Utils\Version::compareVersionNumbers(\RightNow\Internal\Utils\Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.5") >= 0) {
            $this->okcsWhiteList = new \RightNow\Helpers\OkcsWhiteListHelper();
            $this->okcsWhiteList->loadEndPointUrl();
            $apiVersion = $dataArray['apiVersion'] ?: $this->okcsApiVersion;
            if(!$methodName){
                $response = $this->getResponseObject(null, null, Config::getMessage(METHOD_NAME_IS_REQUIRED_LBL));
            }
            else {
                $requestUrlData = $this->okcsWhiteList->getApiUrlData($methodName);
                if(is_array($requestUrlData) && !is_null($requestUrlData['methodType'])) {
                    if(!is_null($dataArray['methodType']) && $dataArray['methodType'] !== $requestUrlData['methodType']) {
                        $response = $this->getResponseObject(null, null, Config::getMessage(INVALID_METHOD_TYPE_LBL));
                    }
                    else{
                        $methodType = $requestUrlData['methodType'];
                        $endpointUrlData = $this->okcsWhiteList->getUpdatedEndPointUrlData($methodName, $dataArray);
                        if(is_array($endpointUrlData) && $endpointUrlData['pathParameter'] !== null) {
                            $response = $this->getResponseObject(null, null, Config::getMessage(PATH_PARAMETER_IS_NOT_PROVIDED_LBL) . " : " . $endpointUrlData['pathParameter']);
                        }
                        else {
                            $endpointUrl = $endpointUrlData['apiEndPointUrl'];
                            $baseUrl = $this->getBaseUrl($requestUrlData['apiType']);
                            if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null) {
                                return $baseUrl;
                            }
                            $apiEndpointUrl = $baseUrl . $apiVersion . $endpointUrl;

                            if($dataArray['queryString']) {
                                $apiEndpointUrl = $apiEndpointUrl . '?' . $dataArray['queryString'];
                            }
                            $response = $this->makeRequest($apiEndpointUrl,  $methodType, json_encode($postData));
                        }
                    }
                }
                else {
                    $response = $this->getResponseObject(null, null, Config::getMessage(INVALID_METHOD_NAME_LBL));
                }
            }
        }
        else {
            $response = $this->getResponseObject(null, null, Config::getMessage(METHOD_NOT_SUPPORTED_LBL));
        }
        Okcs::eventLog(array('methodName' => $methodName, 'requestUrl' => $apiEndpointUrl, 'responseError' => $response->errors, 'requestOrigin' => 'getApiResponse', 'acsEventName' => 'getApiResponse', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to create recommended content in Info Manager
    * @param array $recommendationData Recommendation data array
    * @return object|null Api Response object
    */
    public function createRecommendation(array $recommendationData) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/contentRecommendations";

        if($recommendationData['isRecommendChange'] === 'true') {
            $key = 'content';
            $recommendationArray = array(
                'recordId' => $recommendationData['contentRecordId'],
                'answerId' => $recommendationData['answerId'],
                'documentId' => $recommendationData['documentId'],
                'versionId' => $recommendationData['versionID']
            );
        }
        else {
            $key = 'contentType';
            $recommendationArray = array(
                'recordId' => $recommendationData['contentTypeRecordId'],
                'referenceKey' => $recommendationData['contentTypeReferenceKey'],
                'name' => $recommendationData['contentTypeName']
            );
        }
        $postData = array(
            'caseNumber' => $recommendationData['caseNumber'],
            'comments' => $recommendationData['comments'],
            'title' => $recommendationData['title'],
            $key => $recommendationArray
        );
        if($recommendationData['priority'] !== 'None'){
            $postData['priority'] = $recommendationData['priority'];
        }
        if(array_key_exists('file', $recommendationData) && !empty($recommendationData['file']['name'])){
            $postData['file'] = $recommendationData['file'];
            $postData['fileName'] = $recommendationData['file']['name'];
        }
        $response = $this->makeRequest($requestUrl, 'POST', json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'createRecommend', 'acsEventName' => 'createRecommend', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
     * Retrieves answers for specified answer id's.
     * @param array $answerIDs OKCS answer id's
     * @param string $locale Locale of the interface
     * @return array Results from query
     */
    public function getAnswers($answerIDs, $locale) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $contentLocales = $this->CI->session->getSessionData('contentLocales');
        $localeFilter = "'" . $locale . "'";
        if ($contentLocales && count($contentLocales) > 0) {
            foreach($contentLocales as $localeCode) {
                $localeFilter = $localeFilter . ",'" . $localeCode . "'";
            }
        }

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/content?q=answerId+IN(" . implode(',', $answerIDs) . ")+and+locale.recordId+IN(" . $localeFilter . ")";

        $results = $this->makeRequest($requestUrl, 'GET');
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getAnswers', 'acsEventName' => 'GetOkcsAnswers', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * Gets article list for notification based on userId and offset
    * @param string $userID Logged in user
    * @param string $offset Offset in the notification list
    * @return object Api response object
    */
    public function getPaginatedSubscriptionList($userID, $offset = 0) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptions?orderBy=subscriptionType:desc,dateAdded:desc&mode=FULL" . "&offset={$offset}&limit=20";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getSubscriptionList', 'acsEventName' => 'SubscriptionList', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
     * Retrieves aggregate rating for specified record id's.
     * @param string $recordId RecordId to retrieve the aggregate rating
     * @return array Results from query
     */
    public function getAggregateRating($recordId) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null){
            return $baseUrl;
        }
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/content/{$recordId}/ratingsAggregate/";
        $results = $this->makeRequest($requestUrl, 'GET');
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getAggregateRating', 'acsEventName' => 'getAggregateRating', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * Method to fetch suggestions for the specified query
    * @param string $query Query to retrieve suggestions
    * @param string $limit No. of suggestions to be fetched
    * @param array $additionalParameters Additional optional parameters for filtering suggestions
    * @return object Api response object
    */
    public function getSuggestions($query, $limit, $additionalParameters = array()) {
        $baseUrl = $this->getBaseUrl();
        $query = urlencode($query);
        $productCategory = isset($additionalParameters['productCategory']) ? $additionalParameters['productCategory'] : null;
        $matchAllCategories = isset($additionalParameters['matchAllCategories']) ? $additionalParameters['matchAllCategories'] : null;
        $applyCtIndexStatus = $additionalParameters['applyCtIndexStatus'];
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $localeCode = str_replace("-", "_", $this->getInterfaceLocale());
        if($productCategory){
            $productCategory = str_replace(':', ',', strtoupper($productCategory));
            $requestUrl = $baseUrl . $this->okcsApiVersion . "/suggestedSearch?q=" . $query . "&applyCTIndexStatus=" . $applyCtIndexStatus . "&contentState=PUBLISHED&offset=0&limit=" . $limit . "&categories=" . $productCategory . "&matchAllCategories=" . $matchAllCategories;
        }else{
            $requestUrl = $baseUrl . $this->okcsApiVersion . "/suggestedSearch?q=" . $query . "&applyCTIndexStatus=" . $applyCtIndexStatus . "&contentState=PUBLISHED&offset=0&limit=" . $limit;
        }
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getSuggestions', 'acsEventName' => 'getSuggestions', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to fetch the Email Article Link template by calling KM REST API
    * @return string|null $emailTemplate Email Article Link template or null when no template found
    */
    public function getOkcsSendEmailTemplate() {
        $baseUrl = $this->getBaseUrl();
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/emailarticleLinkTemplate";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getEmailTemplate', 'acsEventName' => 'getEmailTemplate', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return json_decode(preg_replace("/\s+/", " ", $response->emailTemplate))->html;
    }

    /**
    * Method to fetch all translations for a particular answerId
    * @param string $answerId Answer id
    * @return object Api response object
    */
    public function getAllTranslations($answerId) {
        $baseUrl = $this->getBaseUrl();
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/content/answers/" . $answerId . "/allTranslations?additionalFields=allowWebUserAccess";
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getAllTranslations', 'acsEventName' => 'getAllTranslations', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
    * Method to retrieve favorite articles of user.
    * @param string $userRecordID User recordID
    * @return object Api response object
    */
    public function getFavoriteList($userRecordID) {
        $baseUrl = $this->getBaseUrl();
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;
        $apiEndpoint = "{$baseUrl}{$this->okcsApiVersion}/users/{$userRecordID}/userKeyValues";
        $queryParameters = array('mode' => 'user', 'key' => 'favorite_document', 'userInformation.recordId' => $userRecordID);
        $requestUrl = $apiEndpoint . Okcs::getRestQueryString($queryParameters);
        $response = $this->makeRequest($requestUrl);
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'getFavoriteList', 'acsEventName' => 'getFavoriteList', 'apiDuration' => $this->apiDuration));
        return $response;
    }

    /**
    * Method to add or removed favorite answers.
    * @param array $favoriteArray Array of favorite details
    * @return object Api response object
    */
    public function addOrRemoveFavorite($favoriteArray) {
        $userRecordID = $favoriteArray['userRecordID'];
        $favoriteDetails = $favoriteArray['favoriteDetails'];
        $answerId = $favoriteArray['answerId'];
        $favoriteIdsToUpdate = $favoriteArray['favoriteIdsToUpdate'];
        if(!is_null($favoriteDetails)){
            $favoriteRecordID = $favoriteDetails->recordId;
            $favoriteDateAdded = $favoriteDetails->dateAdded;
            $favoriteDateModified = $favoriteDetails->dateModified;
        }
        $methodType = (is_null($favoriteRecordID) || empty($favoriteRecordID)) ? 'POST' : 'PUT';
        $requestUrl = "{$this->getBaseUrl()}{$this->okcsApiVersion}/users/{$userRecordID}/userKeyValues";
        if($methodType === 'PUT') {
            $requestUrl .= "/{$favoriteRecordID}";
            $postData = array(
                'dateAdded' => $favoriteDateAdded,
                'dateModified' => $favoriteDateModified,
                'key' => 'favorite_document',
                'value' => $favoriteIdsToUpdate,
                'recordId' => $favoriteRecordID,
                'userInformation' => array('recordId' => $userRecordID)
            );
        }
        else {
            $postData = array(
                'key' => 'favorite_document',
                'value' => $answerId,
                'userInformation' => array('recordId' => $userRecordID)
            );
        }
        $response = $this->makeRequest($requestUrl, $methodType, json_encode($postData));
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $response, 'requestOrigin' => 'addorRemFav', 'acsEventName' => 'addorRemFav', 'postData' => $postData, 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $response;
    }

    /**
     * Retrieves answers for specified answer id's across locales.
     * @param array $answerIDs OKCS answer id's
     * @return array Results from query
     */
    public function getAnswersForAnswerId($answerIDs) {
        $baseUrl = $this->getBaseUrl();
        $limit = is_array($answerIDs) ? count($answerIDs) : null;
        if (is_object($baseUrl) && isset($baseUrl->error) && $baseUrl->error !== null)
            return $baseUrl;

        $requestUrl = "{$baseUrl}{$this->okcsApiVersion}/content?q=answerId+IN(" . (is_array($answerIDs) ? implode(',', $answerIDs) : '') . ")&limit={$limit}";
        $results = $this->makeRequest($requestUrl, 'GET');
        Okcs::eventLog(array('requestUrl' => $requestUrl, 'response' => $results, 'requestOrigin' => 'getAnswersForAnswerId', 'acsEventName' => 'getAnsForAnsId', 'apiDuration' => $this->apiDuration));
        $this->apiDuration = 0;
        return $results;
    }

    /**
    * This method returns KA API response for custom API requests
    * @param string $url KA REST API URL
    * @param string $methodType Type of http method e.g. 'GET' or 'POST'.
    * @param array $postData Post data array
    * @return string JSON string returned by KA REST API
    */
    public function makeApiRequest($url, $methodType = 'GET', $postData = '') {
        $this->customApiCall = true;
        $response = $this->makeRequest($url, $methodType, json_encode($postData));
        return json_encode($response);
    }

    /**
    * This method returns KA API response for subscription schedule
    * @param string $userID Logged in user
    * @param string $scheduleValue Okcs schedule value
    * @return object Api response object
    */
    public function setSubscriptionSchedule($userID, $scheduleValue) {
        $baseUrl = $this->getBaseUrl();
        $requestUrl = $baseUrl . $this->okcsApiVersion . "/users/{$userID}/subscriptionSchedule";
        $postData = array(
            'schedule' => $scheduleValue,
        );
        $response = $this->makeRequest($requestUrl, 'PATCH', json_encode($postData));
        return $response;
    }

    /**
    * This method checks if the input contains dbFailover maintenance message
    * @param array $response KA API response
    * @return boolean value true or false
    */
    private function isDbFailover($response){
        if(!is_null($response->error) && !is_null($response->error->title) && strpos($response->error->title, self::DB_FAILOVER_MSG) !== false)
            return true;
        return false;
    }

    /**
    * Method to create recommended content with attachment through curl
    * @param string $requestUrl API url
    * @param string $tokenHeader HTTP token header
    * @param string $methodType HTTP method type, GET or POST
    * @param string $postData Post data
    * @return array Array of response and http statusCode
    */
    private function curlRequestCreateRecommendationWithAttachment($requestUrl, $tokenHeader, $methodType = null, $postData = null) {
        if (!extension_loaded('curl') && !@Api::load_curl())
            return null;
        $ch = $this->getCurlHandle($requestUrl);
        $apiAndHost = $this->getInternalApiAndHost($requestUrl);
        $apiHostHeader = $apiAndHost[self::API_HOST] !== null ? 'Host: ' . $apiAndHost[self::API_HOST] : null;
        $options = array(
            CURLOPT_URL => $apiAndHost[self::INTERNAL_URL],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT_MS => $this->getApiTimeOut($requestUrl),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                $apiHostHeader,
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Content-Type: multipart/form-data',
                'User-Agent: ' . $this->getHttpUserAgent(),
                'Host: ' . $this->getHost(),
                'Referer: ' . $this->getHttpReferer()
            )
        );
        $headers = explode('#', $tokenHeader);
        for($count = 0; $count < count($headers); $count++) {
            array_push($options[CURLOPT_HTTPHEADER], $headers[$count]);
        }
        if($methodType !== null)
            $options[CURLOPT_CUSTOMREQUEST] = $methodType;
        if($postData !== null){
            $postArray = json_decode($postData, true);
            if(!empty($postArray['file']) && !empty($postArray['file']['name'])){
                $file = $postArray['file'];
                unset($postArray['file']);
                $tmpFile = tmpfile();
                $jsonData = json_encode($postArray);
                fwrite($tmpFile, $jsonData);
                fseek($tmpFile, 0);
                if (stream_get_meta_data($tmpFile)['uri']){
                    $data = array( 'fileToUpload' => new \CURLFile($file['tmp_name'], $file['type'], $file['name']), 'contentRecommendationBO' => new \CURLFile(stream_get_meta_data($tmpFile)['uri'], 'application/json', 'data.json'));
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            }
        }
        curl_setopt_array($ch, $options);
        
        //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the OKCS API
        //Forcing commit before disconnecting
        $hooks = &load_class('Hooks');
        $hooks->_run_hook(array(
                            'class' => 'RightNow\Hooks\SqlMailCommit',
                            'function' => 'commit',
                            'filename' => 'SqlMailCommit.php',
                            'filepath' => 'Hooks'
                        ));
        //closing the open connection.
        Api::sql_disconnect();

        $response = @curl_exec($ch);
        fclose($tmpFile);
        //Open database connection for further use.
        Api::sql_open_db();

        $info = curl_getinfo($ch);
        $statusCode = $info['http_code'];
        $this->apiDuration = $info['total_time'];
        $timingArray = array(
                            array('key' => ($requestUrl . ' | ' . $methodType . ' | ' . 'Status code - ' . $statusCode), 'value' => $this->apiDuration)
                        );
        $response = array('response' => $response, 'statusCode' => $statusCode);
        Okcs::setTimingToCache('timingCacheKey', $timingArray);
        return $this->getResponseObject($response, 'is_array');
    }

}
