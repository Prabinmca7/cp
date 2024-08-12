<?php

namespace RightNow\Models;
require_once CORE_FILES . 'compatibility/Internal/OkcsSamlAuth.php';
require_once CORE_FILES . 'compatibility/Internal/Sql/Okcs.php';

use RightNow\Connect\v1_4 as Connect,
    RightNow\Api,
    RightNow\ActionCapture,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Libraries\Search;

/**
 * Methods for retrieving agent accounts
 */
class Okcs extends Base {
    private $cache;
    private $session = "";
    private $transactionID = 0;
    private $priorTransactionID = 0;
    private $lastViewedPage = 1;
    private $okcsApi;
    private $apiVersion = 'v1';

    const BROWSE_CACHE_KEY = 'BROWSE_RESULT';
    const LANGUAGE_PREFERENCE_CACHE_KEY = 'LANGUAGE_PREFERENCE';
    const USER_CACHE_KEY = 'OKCS_USER';
    const INVALID_CACHE_KEY = 'invalidCacheKey';
    const USER_LOCALE_CACHE_KEY = 'USER_LOCALE';
    const SEARCH_ID = 'SEARCH_ID';
    const GUEST_USER = 'GUEST';
    const CHANNEL_SCHEMA_KEY = 'CHANNEL_SCHEMA';
    const OKCS_CONTENT_TYPES = 'OKCS_CONTENT_TYPES';
    const DEFAULT_CHANNEL_KEY = 'DEFAULT_CHANNEL';
    const OKCS_DOC_CACHE_KEY = 'OKCS_DOC';
    const OKCS_ADVANCED = 'okcsa-';
    const OKCS_CONTENT_TYPE = 'OKCS_CONTENT_TYPE';
    const OKCS_PROD_CATEG = 'OKCS_PROD_CATEG';
    const OKCS_PROD_CATEG_LIST = 'OKCS_PROD_CATEG_LIST';
    const OKCS_RATING = 'OKCS_RATING';
    const REPOSITORY_LOCALES = 'OKCS_REPOSITORY_LOCALES';
    const INVALID_OKCS_USER_KEY = 'INVALID_OKCS_USER_KEY';
    const ANSWER_KEY = 'ANSWER_KEY';
    const CONTENT_SCHEMA_KEY = 'CONTENT_SCHEMA_KEY';
    const OKCS_KM_ENDPOINT = 'ci/unitTest/OkcsKmApi/endpoint/';
    const OKCS_SRT_ENDPOINT = 'ci/unitTest/OkcsSrt/api/';
    const CACHE_TIME_IN_SECONDS = 600;
    const OKCS_BAD_REQUEST = 'HTTP 400';
    const OKCS_FORBIDDEN_REQUEST = 'HTTP 403';
    const OKCS_NOT_FOUND_REQUEST = 'HTTP 404';
    const OKCS_CONFLICT_REQUEST = 'HTTP 409';
    const OKCS_INTERNAL_SERVER_ERROR = 'HTTP 500';
    const OKCS_SERVICE_UNAVAILABLE = 'HTTP 503';
    const NOTOFICATION_CACHE_KEY = 'NOTIFICATION_RESULT';
    const OKCS_DEFAULT_QUERY_SOURCE = 'SearchResult';
    const OKCS_DUP_ADD_FAVORITE = 'OKDOM-USRFAV01';
    const OKCS_DUP_REMOVE_FAVORITE = 'OKDOM-USRFAV02';

    function __construct() {
        parent::__construct();
        require_once CORE_FILES . 'compatibility/Internal/OkcsApi.php';
        require_once CPCORE . 'Utils/Okcs.php';
        $this->okcsApi = new \RightNow\compatibility\Internal\OkcsApi();
    }

    /**
    * Gets a list of articles sorted by the requested filter. This method supports retrieval of draft answers based on the filter parameter 'status'.
    * @param array $filter Filter list to fetch Infomanager articles
    * @return array an array that contains internal articles.
    */
    public function getArticlesSortedBy(array $filter) {
        $localeCode = str_replace("-", "_", $this->getLocaleCode());
        $length = $filter['truncate'];
        $pageNumber = $filter['pageNumber'];
        $pageSize = $filter['pageSize'];
        $response = $this->okcsApi->getArticlesSortedBy($filter, $localeCode);
        if (isset($response->result) && isset($response->result->errors) && $response->result->errors !== null) {
            $response = $response['response'];
            return $this->getResponseObject($response);
        }
        else if (isset($response->items) && is_array($response->items) && count($response->items) > 0) {
            foreach ($response->items as $document) {
                $document->title = (trim($document->title) === '') ? Config::getMessage(NO_TTLE_LBL) : Text::escapeHtml($document->title);
                if(!$document->published) {
                    $draftUrlParameter = "/a_status/draft_" . rand(1, PHP_INT_MAX) . "_" . $document->answerId;
                    $encryptedUrlParameter = Api::ver_ske_encrypt_fast_urlsafe($draftUrlParameter);
                    $document->encryptedUrl = Api::encode_base64_urlsafe($encryptedUrlParameter);
                }
                $document->publishDate = $this->processIMDate($document->publishDate);
                $document->createDate = $this->processIMDate($document->createDate);
                $document->dateAdded = $this->processIMDate($document->dateAdded);
                $document->dateModified = $this->processIMDate($document->dateModified);
                $document->displayEndDate = $this->processIMDate($document->displayEndDate);
                $document->displayStartDate = $this->processIMDate($document->displayStartDate);
                $document->title = is_null($length) ? $document->title : Text::truncateText($document->title, $length);
                unset($document->links, $document->owner->links, $document->contentType->links, $document->locale->links, $document->lastModifier->links, $document->creator->links);
            }
        }
        if(isset($filter['categoryRecordID']) && $filter['categoryRecordID'] !== null && isset($filter['productRecordID']) && $filter['productRecordID'] !== null) {
            ActionCapture::instrument(self::OKCS_ADVANCED . 'productAndCategory', 'selection', 'info', array('Channel' => $filter['contentType'], 'Category' => $filter['category'], 'CategoryRecordID' => $filter['categoryRecordID']));
        }
        else if(isset($filter['productRecordID']) && $filter['productRecordID'] !== null) {
            ActionCapture::instrument(self::OKCS_ADVANCED . 'product', 'selection', 'info', array('Channel' => $filter['contentType'], 'Product' => $filter['category'], 'ProductRecordID' => $filter['productRecordID']));
        }
        else if(isset($filter['categoryRecordID']) && $filter['categoryRecordID'] !== null) {
            ActionCapture::instrument(self::OKCS_ADVANCED . 'category', 'selection', 'info', array('Channel' => $filter['contentType'], 'Category' => $filter['category'], 'CategoryRecordID' => $filter['categoryRecordID']));
        }
        Framework::setCache(self::BROWSE_CACHE_KEY, $response->hasMore, true);
        return $response;
    }

    /**
    * Gets a list of articles sorted by the requested filter
    * @param array $filter Filter list to fetch recommendations
    * @return array an array that contains recommendations list
    */
    public function getRecommendationsSortedBy(array $filter) {
        if(Framework::isLoggedIn() && !is_null($this->getLoggedInUser())) {
            $length = isset($filter['truncate']) ? $filter['truncate'] : null;
            $response = $this->okcsApi->getRecommendationsSortedBy($this->getUserRecordID(), $filter);
            if (is_array($response->items) && count($response->items) > 0) {
                foreach ($response->items as $recommendation) {
                    if(trim($recommendation->title) === '')
                        $recommendation->title = Config::getMessage(NO_TTLE_LBL);
                    $recommendation->title = (trim($recommendation->title) === '') ? Config::getMessage(NO_TTLE_LBL) : $recommendation->title;
                    $recommendation->dateAdded = $this->processIMDate($recommendation->dateAdded);
                    $recommendation->dateModified = $this->processIMDate($recommendation->dateModified);
                    $recommendation->title = is_null($length) ? $recommendation->title : Text::truncateText($recommendation->title, $length);
                    $recommendation->referenceNumber = $recommendation->caseNumber;
                    $recommendation->priority = ucwords(strtolower(str_replace('_', ' ', $recommendation->localizedPriority)));
                    $recommendation->status = ucwords(strtolower(str_replace('_', ' ', $recommendation->localizedStatus)));
                }
            }
            Framework::setCache(self::NOTOFICATION_CACHE_KEY, $response->hasMore, true);
            return $response;
        }
    }

    /**
    * This method returns Recommendations View
    * @param string $recordId Record Id of the recommendation to be retrieved
    * @return array Array Recommendations View Data
    */
    public function getRecommendationsView($recordId) {
        $response = new \stdClass();
        if(Framework::isLoggedIn() && !is_null($this->getLoggedInUser())) {
            $response = $this->okcsApi->getRecommendationsView($recordId);
        }
        if(!isset($response->errors)) {
            $response->priority = ucwords(strtolower(str_replace('_', ' ', $response->localizedPriority)));
            $response->status = ucwords(strtolower(str_replace('_', ' ', $response->localizedStatus)));
        }
        return $response;
    }

    /**
    * Method to get cached Recommendations.
    * @return object|null Recommendations article object containing Recommendations list
    */
    public function getSortNotifications() {
        return Framework::checkCache(self::NOTOFICATION_CACHE_KEY);
    }

    /**
    * Gets list of articles
    * @param int $pageNumber Page number
    * @param int $pageSize Size of the page
    * @return array an array that contains internal articles
    */
    public function getArticles($pageNumber, $pageSize) {
        $rowCount = 0;
        $articles = $this->okcsApi->getArticlesForSiteMap($pageNumber, $pageSize);
        $totalResults = count($articles->items);
        $results = array();
        foreach ($articles->items as $document) {
            $link = '/app/' . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/' . $document->answerId;
            $url = "<a href='{$link}'>{$document->answerId}</a>";
            $result = array($url, $rowSize = null, $rowTime = null, $document->title);
            $results['data'][$rowCount++] = $result;
        }
        $results['total_pages'] = ($pageSize > 0 && $totalResults > 0) ? ceil($totalResults / $pageSize) : 0;
        $results['page'] = $pageNumber;
        $results['total_num'] = $totalResults;
        return $results;
    }

    /**
     * Retrieves related answers associated to a specific answer ID.
     * Manual links take higher priority over Learned links
     * @param int $answerID Answer ID from which to get related answers
     * @param int $limit Number of related and learned link answers to return
     * @param string $linkType Type of Related Answer
     * @return array Results from query
     */
    public function getRelatedAnswers($answerID, $limit, $linkType) {
        $relatedAnswers = array();
        if($linkType === 'both') {
            $articles = $this->okcsApi->getRelatedAnswers($answerID, $limit);
            if(isset($articles->items) && is_array($articles->items) && count($articles->items) > 0) {
                foreach($articles->items as $articleList) {
                    $jsonDecodedBody = isset($articleList->body) ? json_decode($articleList->body) : null;
                    $recentArticleList = isset($jsonDecodedBody) && isset($jsonDecodedBody->items) ? $jsonDecodedBody->items : null;
                    if(!is_null($recentArticleList)) {
                        foreach($recentArticleList as $recentArticle) {
                            $recentArticle->Title = trim($recentArticle->title) === '' ? Config::getMessage(NO_TTLE_LBL) : $recentArticle->title;
                            $recentArticle->ID = $recentArticle->answerId;
                            array_push($relatedAnswers, $recentArticle);
                        }
                    }
                }
            }
        }else{
            $articles = $this->okcsApi->getManualOrLearnedLinks($answerID, $linkType);
            if(isset($articles->items) && is_array($articles->items) && count($articles->items) > 0) {
                foreach($articles->items as $recentArticle) {
                    $recentArticle->Title = trim($recentArticle->title) === '' ? Config::getMessage(NO_TTLE_LBL) : $recentArticle->title;
                    $recentArticle->ID = $recentArticle->answerId;
                    array_push($relatedAnswers, $recentArticle);
                }
            }
        }
        return array_slice($relatedAnswers, 0, $limit);
    }

    /**
    * Gets list of articles or count of articles based on page number
    * @param array &$hookData Data from the okcs_site_map_answers hook having keys:
    *   int 'pageNumber' PageNumber for which sitemap data is retrieved
    *   int 'sitemapPageLimit' Maximum number of urls on each sitemap page
    * @param int $maxSiteMapLinks Max number of Sitemap links that will be displayed on all pages combined
    * @param string $contentTypes Content Types
    * @param int $maxPerBatch Max number of API calls included in one batch
    */
    public function getArticlesForSiteMap(array &$hookData, $maxSiteMapLinks = 10000, $contentTypes = '*', $maxPerBatch = 1) {
        if(empty($hookData['answers'])) {
            if($hookData['pageNumber'] === 0) {
                $results = $this->getArticlesCountForSiteMapIndex($hookData, $maxSiteMapLinks, $contentTypes, $maxPerBatch);
            }
            else {
                $results = $this->getArticlesForSiteMapPages($hookData, $maxSiteMapLinks, $contentTypes, $maxPerBatch);
            }
            $hookData['answers'] = $results;
        }
    }

    /**
    * Gets a list of content types
    * @param array $contentTypeApiVersion Filter list to fetch channels
    * @return object|null The Channel Object containg channel list
    */
    public function getChannels($contentTypeApiVersion) {
        $contentTypes = Framework::checkCache(self::OKCS_CONTENT_TYPES);
        if((is_bool($contentTypes) && !$contentTypes) || empty($contentTypes)) {
            $contentTypes = $this->okcsApi->getChannels($contentTypeApiVersion);
            Framework::setCache(self::OKCS_CONTENT_TYPES, $contentTypes, true);
        }
        return $contentTypes;
    }

    /**
    * Gets details of seleted channel
    * @param string $channel Selected Channel
    * @return object|null The Channel Object containg channel details
    */
    public function getChannelDetails($channel) {
        if(is_null($channel) || empty($channel))
            return $this->getResponseObject(null, null, 'Invalid channel');

        $response = Framework::checkCache(self::CONTENT_SCHEMA_KEY . '_' . $channel);
        if (is_null($response)) {
            $response = $this->okcsApi->getIMContentSchema($channel, null, 'v1');
            Framework::setCache(self::CONTENT_SCHEMA_KEY . '_' . $channel, $response, true);
        }

        return isset($response->result->errors) && $response->result->errors !== null ? $response : $this->getResponseObject($response);
    }

    /**
    * Getter method for search session
    * @return object|null Search session
    */
    public function getSession() {
        return $this->session;
    }

    /**
    * Setter method to set the search session
    * @param string $searchSession Search session
    */
    public function setSession($searchSession) {
        $this->session = $searchSession;
    }

    /**
    * Getter method for search transactionID
    * @return int Search transactionID
    */
    public function getTransactionID() {
        return $this->transactionID;
    }

    /**
    * Setter method to set the transactionID
    * @param string $transactionID Search transactionID
    */
    public function setTransactionID($transactionID) {
        $this->transactionID = $transactionID;
    }

    /**
    * Getter method for search priorTransactionID
    * @return int Search priorTransactionID
    */
    public function getPriorTransactionID() {
        return $this->priorTransactionID;
    }

    /**
    * Setter method to set the prior transactionID
    * @param string $priorTransactionID Search priorTransactionID
    */
    public function setPriorTransactionID($priorTransactionID) {
        $this->priorTransactionID = $priorTransactionID;
    }

    /**
    * Gets the content of a document in Info Manager
    * @param array $contentData Content data to fetch content details
    * @return object Info Manager Content Object
    */
    public function getIMContent(array $contentData) {
        $contentData['status'] = isset($contentData['status']) ? $contentData['status'] : null;
        if(!isset($contentData['status']) && !is_null(Url::getParameter('answer_data'))) {
            if(Text::stringContains($this->decodeAndDecryptData(Url::getParameter('answer_data')), 'a_status/draft_'))
                $contentData['status'] = 'LATESTVALID';
        }
        if(empty($contentData['searchSession'])) {
            if(Url::getParameter('draft') !== null) {
                $contentData['status'] = 'LATESTVALID';
            }
            return $this->getArticle($contentData['docID'], isset($contentData['status']) ? $contentData['status'] : null, $contentData['answerViewApiVersion'], !$contentData['isAttachmentView']);
        }

        $response = $this->okcsApi->getHighlightContent($contentData);
        if(isset($response->errors)) {
            return $contentData['answerType'] !== 'HTML' ? $this->getArticle($contentData['docID'], $contentData['status'], $contentData['answerViewApiVersion'], false) : Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG);
        }
        else if($contentData['answerType'] === 'HTML') {
            return array('url' => $response->url, 'html' => $response->HttpPassThrough);
        }
        else if($contentData['answerType'] !== 'HTML' && !isset($response->HttpPassThrough)) {
            return $this->getArticle($contentData['docID'], $contentData['status'], $contentData['answerViewApiVersion'], false);
        }

        return json_decode($response->HttpPassThrough);
    }

    /**
    * Gets the contentSchema of a document
    * @param string $contentTypeId Record ID of contentType
    * @param string $locale Locale of contentType schema
    * @param string $answerViewApiVersion Answer view api version
    * @param boolean $isGuestUser True if guest user
    * @return object Content schema object
    */
    public function getIMContentSchema($contentTypeId, $locale, $answerViewApiVersion, $isGuestUser = false) {
        $response = Framework::checkCache(self::CONTENT_SCHEMA_KEY . '_' . $contentTypeId);
        if (is_null($response)) {
            $response = $this->okcsApi->getIMContentSchema($contentTypeId, $locale, $answerViewApiVersion, $isGuestUser);
            Framework::setCache(self::CONTENT_SCHEMA_KEY . '_' . $contentTypeId, $response, true);
        }
        return array(
            'contentSchema' => $response->contentSchema->schemaAttributes,
            'metaSchema' => isset($response->metaDataSchema->schemaAttributes) ? $response->metaDataSchema->schemaAttributes : null,
            'allowRecommendations' => $response->allowRecommendations
        );
    }

    /**
    * Prepares the answer content for display
    * @param string $docID Document ID
    * @param string $answerViewApiVersion Answer view api version
    * @param array $highlightedLinkData Details for highlight link
    * @param string $answerType Type of the selected answer
    * @param string $locale Locale of the selected answer
    * @param string $isAttachmentView Flag to determine if content view request is made for attachment or not
    * @return array Details of the content
    */
    public function processIMContent($docID, $answerViewApiVersion = 'v1', $highlightedLinkData = array(), $answerType = null, $locale = null, $isAttachmentView = false) {
        $searchSession = null;
        $transactionID = 0;
        $answerId = null;
        $priorTransactionId = null;
        if(isset($highlightedLinkData['searchSession'])) {
            $searchSession = $highlightedLinkData['searchSession'];
            $transactionID = $highlightedLinkData['txnId'];
            $answerId = $highlightedLinkData['answerId'];
            $priorTransactionId = $highlightedLinkData['prTxnId'];
        }
        if(empty($answerStatus)) $answerStatus = $highlightedLinkData;

        if(is_null($locale))
            $locale = $this->getLocaleCode();

        $accessType = !is_null(Url::getParameter('accessType')) ? Url::getParameter('accessType') : null;

        $contentData = array(
            'docID' => $docID,
            'searchSession' => $searchSession,
            'answerId' => $answerId,
            'transactionID' => $transactionID,
            'answerType' => $answerType,
            'locale' => $locale,
            'priorTransactionId' => $priorTransactionId,
            'answerViewApiVersion' => $answerViewApiVersion,
            'isAttachmentView' => $isAttachmentView,
            'accessType' => $accessType
        );
        $imContent = $this->getIMContent($contentData);
        if($answerType === 'HTML') {
            Framework::setCache(self::OKCS_DOC_CACHE_KEY . '_' . $docID, $imContent, true);
            return $imContent;
        }
        $date = $imContent->published ? $this->processIMDate($imContent->publishDate) : $this->processIMDate($imContent->dateModified);
        $version = $imContent->published ? $imContent->publishedVersion : $imContent->version;
        $imContentData = array(
            'title' => $imContent->title,
            'docID' => $imContent->documentId,
            'recordID' => $imContent->recordId,
            'owner' => $imContent->owner,
            'answerID' => $imContent->answerId,
            'version' => $version,
            'published' => $imContent->published,
            'publishedDate' => $date,
            'content' => $imContent->xml,
            'metaContent' => isset($imContent->metaDataXml) ? $imContent->metaDataXml : null,
            'contentType' => $imContent->contentType,
            'resourcePath' => $imContent->resourcePath,
            'locale' => isset($imContent->locale->recordId) ? $imContent->locale->recordId : null,
            'error' => isset($imContent->error) ? $imContent->error : null,
            'categories' => $imContent->categories,
            'versionID' => $imContent->versionId,
            'lastModifier' => isset($imContent->lastModifier->name) ? $imContent->lastModifier->name : null,
            'lastModifiedDate' => $this->processIMDate($imContent->lastModifiedDate),
            'answerId' => $imContent->answerId,
            'creator' => isset($imContent->creator->name) ? $imContent->creator->name : null,
            'userGroups' => $imContent->userGroups
        );
        Framework::setCache(self::OKCS_DOC_CACHE_KEY . '_' . $docID, $imContentData, true);
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'view', isset($imContent->answerId) ? $imContent->answerId : null);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => isset($imContent->answerId) ? $imContent->answerId : null, 'Channel' => isset($imContent->contentType->referenceKey) ? $imContent->contentType->referenceKey : null));
        return $imContentData;
    }

    /**
    * Method to fetch document Rating
    * @param string $docID Document ID
    * @param string $documentRatingApiVersion Document rating api version
    * @param boolean $displayAnonymous This is a boolean flag , when set to true the widget will be displayed to guest users
    * @return array Details of the document rating
    */
    public function getDocumentRating($docID, $documentRatingApiVersion, $displayAnonymous = false) {
        $response = null;
        if(is_null($docID) || empty($docID)){
            Api::phpoutlog("getDocumentRating - Error: DocumentID - " . $docID . " is null or empty");
            return $this->getResponseObject(null, null, 'DocumentID is null or empty');
        }
        if(Framework::isLoggedIn() || $displayAnonymous ) {
            $imContent = Framework::checkCache(self::OKCS_DOC_CACHE_KEY . '_' . $docID); 
            $isDataCached = true;
            if((is_bool($imContent) && !$imContent) || empty($imContent)) {
                $imContent = $this->getIMContent(array('docID' => $docID, 'answerViewApiVersion' => $documentRatingApiVersion));
                $isDataCached = false;
            }

            list($contentOwner, $contentType, $contentRecordID, $contentLocale, $error) = $isDataCached ? array(isset($imContent['owner']->recordId) ? $imContent['owner']->recordId : null, isset($imContent['contentType']->referenceKey) ? $imContent['contentType']->referenceKey : null, isset($imContent['recordID']) ? $imContent['recordID'] : null, isset($imContent['locale']->recordId) ? $imContent['locale']->recordId : null, $imContent['error']) : array(isset($imContent->owner->recordId) ? $imContent->owner->recordId : null, isset($imContent->contentType->referenceKey) ? $imContent->contentType->referenceKey : null, isset($imContent->recordId) ? $imContent->recordId : null, isset($imContent->locale->recordId) ? $imContent->locale->recordId : null, $imContent->error);

            if($error !== null) {
                Api::phpoutlog("getDocumentRating - Error: " . $error);
                return $imContent;
            }
            if($contentOwner !== $this->getUserRecordID()) {
                $contentTypeData = $this->getChannelDetails($contentType)->result;
                $ratingID = isset($contentTypeData->rating->recordId) ? $contentTypeData->rating->recordId : null;
                $response = $this->getMemCacheData(self::OKCS_RATING . '_' . $ratingID);
                if(!$response) {
                    $response = $this->okcsApi->getDocumentRating($ratingID, $documentRatingApiVersion);
                    $this->getMemCache()->set(self::OKCS_RATING . '_' . $ratingID, $response);
                }
                $response = array('surveyRecordID' => $response->recordId, 'questions' => $response->ratingType !== 0 ? $response->questions : null, 'contentID' => $contentRecordID, 'locale' => $contentLocale);

                return $this->getResponseObject($response, 'is_array');
            }
        }
        return $this->getResponseObject(isset($response) ? response : null, 'is_null');
    }

    /**
    * Formats the milliseconds to date
    * @param float $date Date in millisecond
    * @return date Formatted date
    */
    public function processIMDate($date) {
        if($date) {
            $date = \RightNow\Utils\Okcs::formatOkcsDate($date, 'DATE');
            return $date;
        }
    }

    /**
    * Gets the list of supported languages
    * @param string $supportedLanguageApiVersion Supported language api version
    * @return object The language object of all the languages supported
    */
    public function getSupportedLanguages($supportedLanguageApiVersion) {
        $locales = $this->getMemCacheData(self::REPOSITORY_LOCALES);
        if(!$locales) {
            $response = $this->okcsApi->getSupportedLanguages($supportedLanguageApiVersion);
            if($response->result->errors !== null) {
                return $this->getResponseObject($response);
            }
            $this->getMemCache()->set(self::REPOSITORY_LOCALES, $response);
            $locales = $response;
        }
        return $locales;
    }
    
    /**
    * Gets the list of supported languages with locale descriptions
    * @param string $supportedLanguageApiVersion Supported language api version
    * @return array Locale descriptions of all the languages supported
    */
    public function getAllLocaleDescriptions($supportedLanguageApiVersion) {
        $response = null;
        if(!$locales) {
            $response = $this->getSupportedLanguages($supportedLanguageApiVersion);
            if ($response->result->errors !== null) {
                $response = $response['response'];
                return $this->getResponseObject($response);
            }
            else if (isset($response->items) && is_array($response->items) && count($response->items) > 0) {
                $localeDescriptionItems = array();
                foreach ($response->items as $locale) {
                        $localeDesc = $locale->localeDesc;
                        $localeCode = $locale->localeCode;
                        $trimmingFactor = ('(' . str_replace("_", "-", $localeCode) . ')');
                        $localeDesc = str_replace($trimmingFactor, "", $localeDesc);
                        $localeDescriptionItems[$localeCode] = $localeDesc;
                }
                $response = $localeDescriptionItems;
            }
        }
        return $response;
    }

    /**
    * Gets the preferred languages of a user
    * @return object The user locale object
    */
    public function getUserLocale() {
        $userLocale = null;
        $user = $this->getUser();
        if (Framework::isLoggedIn()) {
            $userLocale = $this->getCacheData(self::USER_LOCALE_CACHE_KEY);
            if($userLocale === null && !$this->cacheKeyExists(self::USER_LOCALE_CACHE_KEY)) {
                $response = $this->okcsApi->getUserLocale($user);
                if($response->errors === null){
                    $userLocale = $response->defaultLocale->recordId;
                    $this->cacheData(self::USER_LOCALE_CACHE_KEY, $userLocale);
                }
            }
        }
        return $userLocale === null ? \RightNow\Utils\Okcs::getInterfaceLocale() : $userLocale;
    }

    /**
    * This method is used to store data into the okcs cache
    * @param string $key Cache key
    * @param string $value Key value
    */
    public function cacheData($key, $value) {
        $userCacheKey = $this->CI->session->getSessionData('userCacheKey');
        $isLoggedInUser = Framework::isLoggedIn();
        $keySuffix = $isLoggedInUser ? $this->getLoggedInUser() : self::GUEST_USER;
        if(!$userCacheKey || ($isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $this->getLoggedInUser())) ||
            (!$isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $keySuffix))){
            $cacheKey = $this->okcsApi->cacheUserData(json_encode(array($key => $value)), 'POST');
            if($cacheKey === self::INVALID_CACHE_KEY)
                $this->CI->session->setSessionData(array('userCacheKey' => false));
            else
                $this->CI->session->setSessionData(array('userCacheKey' => $cacheKey . '_' . $keySuffix));
        }
        else {
            $userCacheKey = Text::getSubstringBefore($userCacheKey, '_' . $keySuffix);
            $cacheData = (array)json_decode($this->okcsApi->getCacheData($userCacheKey));
            if($key === self::SEARCH_ID && !is_null($cacheData[$key])) {
                $cacheData[$key] .= ',' . $value;
            }
            else {
                $cacheData[$key] = $value;
            }
            $cacheKey = $this->okcsApi->cacheUserData(json_encode($cacheData), 'PUT', $userCacheKey);
            if($cacheKey === self::INVALID_CACHE_KEY) {
                $cacheKey = $this->okcsApi->cacheUserData(json_encode(array($key => $value)), 'POST');
                if($cacheKey === self::INVALID_CACHE_KEY)
                    $this->CI->session->setSessionData(array('userCacheKey' => false));
                else
                    $this->CI->session->setSessionData(array('userCacheKey' => $cacheKey . '_' . $keySuffix));
            }
        }
    }

    /**
    * This method is used to clear cache on logout
    * @return object|null Deleted cache object
    */
    public function clearCache() {
        $keySuffix = Framework::isLoggedIn() ? $this->getLoggedInUser() : self::GUEST_USER;
        $userCacheKey = $this->CI->session->getSessionData('userCacheKey');
        $userCacheKey = Text::getSubstringBefore($userCacheKey, '_' . $keySuffix);
        $cacheData = (array)json_decode($this->okcsApi->getCacheData($userCacheKey));
        $batchData = array();
        if(!is_null($cacheData) && !empty($cacheData) && !is_null($cacheData[self::SEARCH_ID])) {
            $cacheList = explode(',', $cacheData[self::SEARCH_ID]);
            $cacheListCount = count($cacheList);
            if($cacheListCount > 0) {
                for($count = 0; $count < $cacheListCount; $count++) {
                    $data = array('id' => $count + 1, 'method' => 'DELETE', 'relativeUrl' => "{$this->apiVersion}/keyValueCache/{$cacheList[$count]}", 'bodyClassName' => null, 'body' => null);
                    array_push($batchData, $data);
                }
            }
        }
        $data = array('id' => $count + 1, 'method' => 'DELETE', 'relativeUrl' => "{$this->apiVersion}/keyValueCache/{$userCacheKey}", 'bodyClassName' => null, 'body' => null);
        array_push($batchData, $data);
        $postData = array('asynchronous' => false, 'requests' => $batchData);
        $this->CI->session->setSessionData(array('userCacheKey' => false));
        return $this->okcsApi->cacheUserData(json_encode($postData), 'DELETE');
    }

    /**
    * This method returns cacheKey value
    * @param string $key Cache key
    * @return false|object cacheKey value
    */
    function getCacheData($key) {
        $userCacheKey = $this->CI->session->getSessionData('userCacheKey');
        $isLoggedInUser = Framework::isLoggedIn();
        $keySuffix = $isLoggedInUser ? $this->getLoggedInUser() : self::GUEST_USER;
        if(!$userCacheKey || ($isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $this->getLoggedInUser())) ||
            (!$isLoggedInUser && !Text::endsWith($userCacheKey, '_' . $keySuffix)))
            return false;
        $userCacheKey = Text::getSubstringBefore($userCacheKey, '_' . $keySuffix);
        $cacheData = json_decode($this->okcsApi->getCacheData($userCacheKey));
        return isset($cacheData->$key) ? $cacheData->$key : null;
    }

    /**
    * This method checks if cacheKey exists in the cache
    * @param string $key Cache key
    * @return boolean True if cache key exists
    */
    function cacheKeyExists($key) {
        $isLoggedInUser = Framework::isLoggedIn();
        $userCacheKey = $this->CI->session->getSessionData('userCacheKey');
        $keySuffix = $isLoggedInUser ? $this->getLoggedInUser() : self::GUEST_USER;
        $userCacheKey = Text::getSubstringBefore($userCacheKey, '_' . $keySuffix);
        $cacheData = $this->okcsApi->getCacheData($userCacheKey);
        if($cacheData !== null){
            $cacheArr = (array)(json_decode($cacheData));
            return array_key_exists($key, $cacheArr);
        }
        return false;
    }

    /**
    * Gets answers for a question and then cache them.
    * @param array $filters Search filter list
    * @return array Search result
    */
    public function getSearchResult(array $filters) {
        $locale = null;
        $searchText = urlencode(html_entity_decode($filters['query'], ENT_QUOTES));
        $selectedLocale = $filters['locale'];
        $searchSession = $filters['session'];
        $transactionID = $filters['transactionID'];
        $priorTransactionID = isset($filters['priorTransactionID']) ? $filters['priorTransactionID'] : null;
        $collectFacet = isset($filters['collectFacet']) ? $filters['collectFacet'] : null;
        $facets = isset($filters['facets']) ? $filters['facets'] : null;
        $resultCount = isset($filters['resultCount']) ? $filters['resultCount'] : null;
        $localeCode = $this->getLocaleCode();
        $requestLocale = str_replace("-", "_", $localeCode);
        $querySource = isset($filters['querySource']) ? $filters['querySource'] : null;
        if(strlen($searchText) !== 0) {
            $this->CI->model("Okcs")->updateRecentSearchesArray($filters['query']);
        }
        // check for SADialog widget search request
        if ($selectedLocale !== 'saDefaultLocale') {
            $requestType = 'SEARCH';
            $user = $this->getUser();
            if(!is_null($selectedLocale)) {
                $cachedLanguage = $this->getCacheData(self::LANGUAGE_PREFERENCE_CACHE_KEY);
                // to set user language preference in the database if user has logged in.
                if(Framework::isLoggedIn() && $cachedLanguage !== $selectedLocale)
                    $this->setUserLanguagePreference($selectedLocale, true);
                $this->cacheData(self::LANGUAGE_PREFERENCE_CACHE_KEY, $selectedLocale);
            }
        }
        else {
            $searchSession = $this->getContactSearchSession();
            $requestType = 'SMART_ASSISTANT';
            $selectedLocale = $localeCode;
        }

        $resultLocale = (!is_null($selectedLocale) && strlen($selectedLocale) !== 0) ? $selectedLocale : $localeCode;
        $querySource = $this->getSourceQuery($querySource);

        if($transactionID === 0 || is_null($transactionID) || empty($transactionID)) {
            $transactionID = rand(1, PHP_INT_MAX);
            $this->setTransactionID($transactionID);
        }

        $searchFilter = array(
            'searchText' => $searchText,
            'searchSession' => $searchSession,
            'transactionID' => $transactionID,
            'localeCode' => $localeCode,
            'resultLocale' => $resultLocale,
            'requestLocale' => $requestLocale,
            'querySource' => $querySource,
            'requestType' => $requestType,
            'collectFacet' => $collectFacet,
            'resultCount' => $resultCount,
            'facets' => $facets
        );
        if($collectFacet){
            $searchFilter['facetPriorTransactionID'] = $priorTransactionID;
        }

        if (isset($filters['accessType']) && !is_null($filters['accessType']) && !empty($filters['accessType'])) {
            $searchFilter['accessType'] = $filters['accessType'];
        }

        $results = $this->okcsApi->getSearchResult($searchFilter);
        if (isset($results->errors))
            return $results;

        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'keywords', $searchText);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'keywords', 'info', array('searchText' => $searchText, 'Facets' => isset($searchFilter->facets) ? $searchFilter->facets : null, 'RequestType' => 'AskQuestion'));
        $searchSession = isset($results->session) ? $results->session : null;
        if ($requestType === 'SMART_ASSISTANT')
            $searchSession .= '_SMART_ASSISTANT';

        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $this->setTransactionID($results->transactionId);
        $this->setPriorTransactionID($results->priorTransactionId);
        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $query = $results->query;
        $results = $results->results;
        $page = isset($results->results[0]->pageNumber) ? (int)($results->results[0]->pageNumber) : 0;
        $pageMore = isset($results->results[0]->pageMore) ? (int)($results->results[0]->pageMore) : 0;

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => isset($results->facets) ? $results->facets : null,
            'selectedLocale' => $locale,
            'query' => $query
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        return $this->getResponseObject($response, 'is_array');
    }

    /**
    * Gets answers for a question employing multiple filtering.
    * @param array $filters Search filter list
    * @return array Search result
    */
    public function getMultiFacetSearchResult(array $filters) {
        $requestType = null;
        $searchText = urlencode(html_entity_decode($filters['query'], ENT_QUOTES));
        $selectedLocale = $filters['locale'];
        $searchSession = $filters['session'];
        $transactionID = $filters['transactionID'];
        $priorTransactionID = $filters['priorTransactionID'];
        $collectFacet = $filters['collectFacet'];
        $facets = $filters['facets'];
        $resultCount = $filters['resultCount'];
        $localeCode = $this->getLocaleCode();
        $requestLocale = str_replace("-", "_", $localeCode);
        $accessType = $filters['accessType'];
        $querySource = $filters['querySource'];

        $resultLocale = (!is_null($selectedLocale) && strlen($selectedLocale) !== 0) ? $selectedLocale : $localeCode;
        $querySource = $this->getSourceQuery($querySource);

        if($transactionID === 0 || is_null($transactionID) || empty($transactionID)) {
            $transactionID = rand(1, PHP_INT_MAX);
            $this->setTransactionID($transactionID);
        }

        $searchFilter = array(
            'searchText' => $searchText,
            'searchSession' => $searchSession,
            'transactionID' => $transactionID,
            'localeCode' => $localeCode,
            'resultLocale' => $resultLocale,
            'requestLocale' => $requestLocale,
            'querySource' => $querySource,
            'requestType' => $requestType,
            'collectFacet' => $collectFacet,
            'resultCount' => $resultCount,
            'facets' => $facets,
            'accessType' => $accessType
        );
        if($collectFacet){
            $searchFilter['facetPriorTransactionID'] = $priorTransactionID;
        }

        $results = $this->okcsApi->getMultiFacetSearchResult($searchFilter);

        if (isset($results->errors))
            return $results;

        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'keywords', $searchText);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'keywords', 'info', array('searchText' => $searchText, 'Facets' => isset($searchFilter->facets) ? $searchFilter->facets : null, 'RequestType' => 'AskQuestion'));
        $searchSession = isset($results->session) ? $results->session : null;

        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $this->setTransactionID($results->transactionId);
        $this->setPriorTransactionID($results->priorTransactionId);
        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $query = $results->query;
        $results = $results->results;
        $page = (int)($results->results[0]->pageNumber);
        $pageMore = (int)($results->results[0]->pageMore);

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => isset($results->facets) ? $results->facets : null,
            'selectedLocale' => $locale,
            'query' => $query
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        return $this->getResponseObject($response, 'is_array');
    }

    /**
    * Gets answers to display answers on the new tab/window.
    * @param array $filter Filters to fetch search results
    * @return array Search result
    */
    public function getSearchResultForNewTab(array $filter) {
        $localeCode = $this->getLocaleCode();
        $requestLocale = str_replace("-", "_", $localeCode);
        $resultLocale = (!is_null($selectedLocale) && strlen($selectedLocale) !== 0) ? $selectedLocale : $localeCode;

        $transactionID = rand(1, PHP_INT_MAX);
        $this->setTransactionID($transactionID);
        $sessionID = '';
        $searchSession = $this->getSearchSession($sessionID, $locale, $requestLocales, $transactionID);

        $filter['querySource'] = $this->getSourceQuery($filter['querySource']);
        $searchFilter = array(
            'kw' => urlencode($filter['kw']),
            'facet' => $filter['facet'],
            'page' => $filter['page'],
            'loc' => $filter['loc'],
            'localeCode' => $localeCode,
            'requestLocale' => $requestLocale,
            'resultLocale' => $resultLocale,
            'resultCount' => $filter['resultCount'],
            'searchSession' => $searchSession,
            'transactionID' => $transactionID,
            'querySource' => $filter['querySource']
        );
        $results = $this->okcsApi->getSearchResultForNewTab($searchFilter);
        if (isset($results->errors))
            return $results;

        $searchSession = isset($results->session) ? $results->session . '_SEARCH' : null;
        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $this->setTransactionID($results->transactionId);
        $this->setPriorTransactionID($results->priorTransactionId);

        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $results = $results->results;
        $page = (int)($results->results[0]->pageNumber) + 1;
        $pageMore = (int)($results->results[0]->pageMore);

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => isset($results->facets) ? $results->facets : null,
            'selectedLocale' => $locale
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        return $this->getResponseObject($response, 'is_array');
    }

    /**
    * This method returns search session to perform search on ask question tab
    * @return string Search session
    */
    private function getContactSearchSession() {
        $results = $this->okcsApi->getContactSearchSession(str_replace("_", "-", $this->getLocaleCode()));
        return $results->results && isset($results->result->error) && $results->result->error ? $results : (isset($results->session) ? $results->session : null);
    }

    /**
    * This method returns search session to perform search on ask question tab
    * @param string $sessionID New generated sessionID
    * @param string $locale Locale of the selected answer
    * @param string $requestLocales Locale of the search request
    * @param string $transactionID Search transactionID
    * @return string Search session
    */
    private function getSearchSession($sessionID, $locale, $requestLocales, $transactionID) {
        $results = $this->okcsApi->getSearchSession($sessionID, $locale, $requestLocales, $transactionID);
        return $results->results && $results->result->error ? $results : (isset($results->session) ? $results->session : null);
    }

    /**
    * This method refines last performed search result based on the seleted facet.
    * @param array $searchFilter List of search filters
    * @return array Search result response
    */
    public function getAnswersForSelectedFacet(array $searchFilter) {
        if(is_null($searchFilter['facet']) || empty($searchFilter['facet']))
            return $this->getResponseObject(null, null, 'Invalid facet');
        $localeCode = str_replace("_", "-", $this->getLocaleCode());
        $resultLocale = (isset($searchFilter['resultLocale']) && (strlen($searchFilter['resultLocale']) !== 0)) ? $searchFilter['resultLocale'] : $localeCode;
        $searchFilter['localeCode'] = $localeCode;
        $searchFilter['resultLocale'] = $resultLocale;

        $searchFilter['querySource'] = $this->getSourceQuery($searchFilter['querySource']);
        $results = $this->okcsApi->getAnswersForSelectedFacet($searchFilter);
        if (isset($results->errors))
            return $results;

        $searchSession = isset($results->session) ? $results->session. '_SEARCH' : null;
        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $this->setTransactionID($results->transactionId);
        $this->setPriorTransactionID($results->priorTransactionId);

        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $results = $results->results;
        $facets = isset($results->results->facets) ? $results->results->facets : null;
        $page = (int)($results->results[0]->pageNumber);
        $pageMore = (int)($results->results[0]->pageMore);

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => $facets
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'facet', $facets);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'facet', 'info', array('SearchText' => isset($searchFilter->searchText) ? $searchFilter->searchText : null, 'Facets' => $facets, 'PageNo' => isset($searchData->page) ? $searchData->page : null, 'RequestType' => 'AskQuestion'));
        return $this->getResponseObject($response);
    }

    /**
    * Gets answer data for a requestedPage.
    * @param array $searchFilter List of search filters
    * @return array Search result for the requested page
    */
    public function getSearchPage(array $searchFilter) {
        $localeCode = null;
        $searchFilter['localeCode'] = str_replace("_", "-", $this->getLocaleCode());
        $searchFilter['pageDirection'] = ($searchFilter['type'] === 'forward') ? 'next' : 'previous';
        if($searchFilter['type'] === 'current'){
            $searchFilter['pageDirection'] = 'current';
        }

        $searchFilter['querySource'] = $this->getSourceQuery($searchFilter['querySource']);
        $results = $this->okcsApi->getSearchPage($searchFilter);

        if (isset($results->errors))
            return $results;

        $searchSession = isset($results->session) ? $results->session . '_SEARCH' : null;
        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $results = $results->results;
        $page = (int)($results->results[0]->pageNumber);
        $pageMore = (int)($results->results[0]->pageMore);

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => isset($results->facets) ? $results->facets : null
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'paging', $searchFilter['page']);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'paging', 'info', array('SearchText' => isset($searchFilter->searchText) ? $searchFilter->searchText : '', 'Facets' => isset($searchData->facet) ? $searchData->facet : '', 'PageNo' => $searchFilter['page'], 'RequestType' => 'AskQuestion'));
        return $this->getResponseObject($response, null);
    }

    /**
    * Fetches answer data with a particular limit.
    * @param array $searchFilter List of search filters
    * @return array Search result for the requested limit
    */
    public function performSearchWithResultCount(array $searchFilter) {
        $searchFilter['localeCode'] = str_replace("_", "-", $this->getLocaleCode());
        $searchFilter['resultLocale'] = (isset($searchFilter['resultLocale']) && strlen($searchFilter['resultLocale']) !== 0) ? $searchFilter['resultLocale'] : $localeCode;

        $searchFilter['querySource'] = $this->getSourceQuery($searchFilter['querySource']);
        $results = $this->okcsApi->performSearchWithResultCount($searchFilter);

        if (isset($results->errors))
            return $results;

        $searchSession = isset($results->session) ? $results->session . '_SEARCH' : null;
        $this->setSession(Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($searchSession)));
        $searchState = array(
            'session' => $this->getSession(),
            'transactionID' => $results->transactionId,
            'priorTransactionID' => $results->priorTransactionId
        );

        $results = $results->results;
        $page = (int)($results->results[0]->pageNumber);
        $pageMore = (int)($results->results[0]->pageMore);

        $searchData = array(
            'page' => $page,
            'pageMore' => $pageMore,
            'results' => $results,
            'facet' => isset($results->facets) ? $results->facets : null
        );
        $response = array('searchState' => $searchState, 'searchResults' => $searchData);
        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'paging', $searchFilter['page']);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'paging', 'info', array('SearchText' => $searchFilter->searchText, 'Facets' => $searchData->facet, 'PageNo' => $searchFilter['page'], 'RequestType' => 'AskQuestion'));
        return $this->getResponseObject($response, null);
    }

    /**
    * Method to get cached Info Manager article details.
    * @param string $answerID Answer ID
    * @param string $answerViewApiVersion Answer view api version
    * @param array $highlightedLinkData Highlighted Link data array
    * @param string $answerType Type of the selected answer
    * @param string $locale Locale of InfoManager article
    * @return object|null InfoManager article object
    */
    public function getArticleDetails($answerID, $answerViewApiVersion, array $highlightedLinkData = array(), $answerType = null, $locale = null) {
        $articleDetails = null;
        if(is_null($locale)) {
            $locale = str_replace("_", "-", $this->getLocaleCode());
        }
        if (!is_null($answerID) || (!empty($highlightedLinkData) && $answerType === 'HTML')) {
            $articleDetails = $this->getAnswerViewData($answerID, Url::getParameter('loc'), $highlightedLinkData, Url::getParameter('answer_data'), $answerViewApiVersion);
        }
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'view', $answerID);
        if(is_null($answerID)) {
            ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => $answerID, 'Message' => 'AnswerID is null'));
        }
        else if(!is_null($articleDetails)) {
            ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => $answerID, 'Message' => 'Answer Details fetched from cache'));
        }
        else if($answerType !== 'HTML') {
            if(!empty($highlightedLinkData))
                ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => $answerID, 'Message' => 'Highlighted link does not correspond to HTML content'));
        }
        return $articleDetails;
    }

    /**
    * This Method returns decoded and decrypted data
    * @param string $answerData Answer data
    * @return string Decoded and decrypted answer data
    */
    public function decodeAndDecryptData($answerData) {
        // @codingStandardsIgnoreStart
        $decodedData = Api::decode_base64_urlsafe($answerData);
        return Api::ver_ske_decrypt($decodedData);
        // @codingStandardsIgnoreEnd
    }
    /**
    * This Method returns encrypted and encoded Data
    * @param string $data Data
    * @return string Encrypted and encoded Data
    */
    public function encryptAndEncodeData($data) {
        return Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($data));
    }

    /**
    * Method to get cached Info Manager articles.
    * @return object|null InfoManager article object containg article list
    */
    public function getIMArticles() {
        return Framework::checkCache(self::BROWSE_CACHE_KEY);
    }

    /**
    * Method to retrieve user recordID.
    * @return object User record object
    */
    public function getUserRecordID() {
        $user = $this->CI->model('Contact')->get()->result->Login;
        $userRecordID = $this->getCacheData(self::USER_CACHE_KEY);
        if(!$userRecordID) {
            $response = $this->okcsApi->getUserRecordID($user);
            if (isset($response->result->errors) && $response->result->errors !== null)
                return $response;
            $userRecordID = $response->recordId;
            $this->cacheData(self::USER_CACHE_KEY, $userRecordID);
        }
        return $userRecordID;
    }

    /**
    * Method to retrieve user preferences.
    * @return object User preferred languages object
    */
    public function getUserLanguagePreferences() {
        $user = $this->getUser();
        $preferredLanguages = $this->getCacheData(self::LANGUAGE_PREFERENCE_CACHE_KEY);
        if((!$preferredLanguages || $preferredLanguages === null) && !$this->cacheKeyExists(self::LANGUAGE_PREFERENCE_CACHE_KEY) && Framework::isLoggedIn()) {
            $userRecordID = $this->getUserRecordID();
            if($userRecordID) {
                $response = $this->okcsApi->getUserLanguagePreferences($userRecordID);
                $preferredLanguages = isset($response->errors) ? $response : (is_array($response->items) && count($response->items) === 0 ? null : $response->items[0]);
                $this->cacheData(self::LANGUAGE_PREFERENCE_CACHE_KEY, $preferredLanguages);
                return $preferredLanguages;
            }
            return null;
        }

        return !$preferredLanguages ? null : $preferredLanguages;
    }

    /**
    * Method to format error message
    * @param string $error Error message to be formatted
    * @return string Formatted error message
    */
    public function formatErrorMessage($error) {
        $errorMessage = $error->__toString();
        if (!is_null($error->getErrorCode()))
            $errorMessage = $error->getErrorCode() . ': ' . $errorMessage . ' - ' . $error->getSource();
        return $errorMessage;
    }

    /**
    * Method to get contact deflection response
    * @param int $priorTransactionID Prior transactionID
    * @param boolean $deflected If true means case was deflected otherwise question was not answered
    * @param string $session Search session
    * @return object Api response object
    */
    public function getContactDeflectionResponse($priorTransactionID, $deflected, $session) {
        $contactDeflectionData = array(
            'localeCode' => str_replace("_", "-", $this->getLocaleCode()),
            'transactionID' => $this->getTransactionID(),
            'priorTransactionID' => $priorTransactionID,
            'searchSession' => Text::getSubstringBefore($this->decodeAndDecryptData($session), '_SMART_ASSISTANT'),
            'deflected' => $deflected
        );
        $response = $this->okcsApi->getContactDeflectionResponse($contactDeflectionData);
        $deflected = $deflected === 'true';
        ActionCapture::record(self::OKCS_ADVANCED . 'incident', $deflected ? 'deflect' : 'doNotCreateState');
        ActionCapture::instrument(self::OKCS_ADVANCED . 'incident', $deflected ? 'deflect' : 'Filed', 'info');
        if(!$deflected){
            ActionCapture::record(self::OKCS_ADVANCED . 'incident', 'notDeflected');
            ActionCapture::instrument(self::OKCS_ADVANCED . 'incident', 'notDeflected', 'info');
        }
        return isset($response->result->errors) ? $this->getResponseObject($response) : $this->getResponseObject(true, 'is_bool');
    }

    /**
    * Sets the default channel
    * @param string $channel Default channel
    */
    public function setDefaultChannel($channel) {
        Framework::setCache(self::DEFAULT_CHANNEL_KEY, $channel, true);
    }

    /**
    * Method to get default channel
    * @return string Default Channel
    */
    public function getDefaultChannel() {
        return Framework::checkCache(self::DEFAULT_CHANNEL_KEY);
    }

    /**
    * Method to get categories corresponding to a praticular channel
    * @param string $channelReferenceKey Channel Reference Key
    * @param string $productCategoryApiVersion Product category api version
    * @param int $offset Offset in category list
    * @param string $externalType Category external type
    * @return object Api response object
    */
    public function getChannelCategories($channelReferenceKey, $productCategoryApiVersion = 'v1', $offset = 0, $externalType = null) {
        $locale = \RightNow\Utils\Okcs::getInterfaceLocale();
        $cacheKey = self::OKCS_PROD_CATEG . '_' . $channelReferenceKey . '_' . $externalType . '_' . $offset . '_' . $locale;
        $categoryValues = $this->getMemCacheData($cacheKey);
        if(!$categoryValues) {
            $categoryValues = $this->okcsApi->getChannelCategories($channelReferenceKey, $productCategoryApiVersion, $offset, $externalType);
            $this->getMemCache()->set($cacheKey, $categoryValues);
        }
        if (is_array($categoryValues->items)) {
            foreach($categoryValues->items as $category) {
                $category->name = Text::escapeHtml($category->name);
            }
        }
        return isset($categoryValues->result) && isset($categoryValues->result->error) && $categoryValues->result->errors !== null ? $categoryValues['response'] : $categoryValues;
    }

    /**
    * Method to get children corresponding to a perticular category
    * @param string $categoryID CategoryID
    * @param string $childCategoryLimit No. of categories to be fetched
    * @param string $offset Offset in category list
    * @return object Api response object
    */
    public function getChildCategories($categoryID, $childCategoryLimit, $offset) {
        $locale = \RightNow\Utils\Okcs::getInterfaceLocale();
        $cacheKey = self::OKCS_PROD_CATEG . '_' . $categoryID . '_' . $locale . '_' . $offset;
        $childCategoryValues = $this->getMemCacheData($cacheKey);
        if(!$childCategoryValues) {
            $childCategoryValues = $this->okcsApi->getChildCategories($categoryID, $childCategoryLimit, $offset);
            $this->getMemCache()->set($cacheKey, $childCategoryValues);
        }
        if (is_array($childCategoryValues->items)) {
            foreach($childCategoryValues->items as $category) {
                $category->name = Text::escapeHtml($category->name);
            }
        }
        return $childCategoryValues;
    }

    /**
    * Method to get product/category details corresponding to an identifier
    * @param string $categoryID CategoryID
    * @param string $productCategoryApiVersion Product category api version
    * @return object Api response object
    */
    public function getProductCategoryDetails($categoryID, $productCategoryApiVersion = 'v1') {
        $locale = Text::getLanguageCode();
        $cacheKey = self::OKCS_PROD_CATEG . '_' . $categoryID . '_' . $locale;
        $categoryValue = $this->getCacheData($cacheKey);
        if(!$categoryValue || $categoryValue === null && !$this->cacheKeyExists($cacheKey)) {
            $categoryValue = $this->okcsApi->getProductCategoryDetails($categoryID, $productCategoryApiVersion);
            if($categoryValue->errors === null){
                $categoryValue->name = Text::escapeHtml($categoryValue->name);
                $this->cacheData($cacheKey, $categoryValue);
            }
        }

        return $categoryValue;
    }

    /**
    * Method to get product/category details corresponding to list of categories
    * @param array $categoryList Array of product/category reference keys
    * @param string $productCategoryApiVersion Product category api version
    * @return object Api response object
    */
    public function getProductCategoryListDetails($categoryList, $productCategoryApiVersion = 'v1') {
        $prodCategList = Framework::checkCache(self::OKCS_PROD_CATEG_LIST);
        if (!is_null($prodCategList))
            return $prodCategList;
        $prodCategList = $this->okcsApi->getProductCategoryListDetails($categoryList, $productCategoryApiVersion);
        Framework::setCache(self::OKCS_PROD_CATEG_LIST, $prodCategList, true);
        return $prodCategList;
    }

    /**
    * This method returns answer preview details
    * @param string $docID AnswerID
    * @param string $answerViewApiVersion Answer view api version
    * @return array Answer details
    */
    function getAnswerPreviewDetails($docID, $answerViewApiVersion) {
        $imContent = $this->okcsApi->getArticle($docID, null, $answerViewApiVersion, $isGuestUser = true);
        $date = $imContent->published ? $this->processIMDate($imContent->publishDate) : '';
        $imContentData = array(
            'title' => $imContent->title,
            'docID' => $imContent->documentId,
            'answerID' => $imContent->answerId,
            'version' => $imContent->publishedVersion,
            'published' => $imContent->published,
            'publishedDate' => $date,
            'content' => $imContent->xml,
            'metaContent' => $imContent->metaDataXml,
            'contentType' => $imContent->contentType,
            'resourcePath' => $imContent->resourcePath,
            'locale' => $imContent->locale->recordId,
            'error' => $imContent->error,
            'categories' => $imContent->categories,
            'versionID' => $imContent->versionID
        );
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'view', $imContent->answerID);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => $imContent->answerID, 'Channel' => isset($imContent->contentType->referenceKey) ? $imContent->contentType->referenceKey : null));
        return $imContentData;
    }

    /**
    * This method add/promotes the requested Document as first search result
    * @param string $results Search results response
    * @param string $docID DocumentID
    * @return object Search results response
    */
    public function getDocByIdFromSearchOrIm($results, $docID) {
        $searchResultContainsDoc = false;
        if(isset($results->result['searchResults']['results']->results[0]->resultItems)){
            foreach($results->result['searchResults']['results']->results[0]->resultItems as $key => $value){
                if ($value->fileType === 'CMS-XML' && stripos($value->link, ':' . $docID . ':') !== false){
                    $newValue = $value;
                    unset($results->result['searchResults']['results']->results[0]->resultItems[$key]);
                    array_unshift($results->result['searchResults']['results']->results[0]->resultItems, $newValue);
                    $searchResultContainsDoc = true;
                }
            }
        }
        if($searchResultContainsDoc){
            return $results;
        }
        $resultLocale = $results->resultLocales;
        if($resultLocale !== null) {
            $resultLocaleArray = explode(',', $resultLocale);
            $resultLocale = $resultLocaleArray[0];
            $searchDocumentDetails = $this->okcsApi->getDocumentByDocIdLocale($docID, $resultLocale);
        }
        else {
            $searchDocumentDetails = $this->okcsApi->getDocumentByDocId($docID);
        }
        if(isset($searchDocumentDetails->errors)){
            return null;
        }
        $searchDocumentDetailsData = array(
            'type' => 'unstructured',
            'fileType' => 'CMS-XML',
            'answerID' => $searchDocumentDetails->answerId,
            'docId' => $docID,
            'score' => 1,
            'title' => $searchDocumentDetails->title,
            'clickThroughLink' => '/a_id/' . $searchDocumentDetails->answerId,
            'similarResponseLink' => '',
            'highlightedLink' => '',
            'clickThroughUrl' => '',
            'href' => '/a_id/' . $searchDocumentDetails->answerId,
            'dataHref' => '/a_id/' . $searchDocumentDetails->answerId
        );
        if(!is_null($searchDocumentDetailsData) && isset($results->result['searchResults']['results']->results[0]->resultItems)){
            array_unshift($results->result['searchResults']['results']->results[0]->resultItems, (object) $searchDocumentDetailsData);
        }
        else{
            if(isset($results->result['searchResults']['results']->results[0]->resultItems[0]))
                $results->result['searchResults']['results']->results[0]->resultItems[0] = (object) $searchDocumentDetailsData;
        }
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'view', $searchDocumentDetails->answerId);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'view', 'info', array('AnswerID' => $searchDocumentDetails->answerId));
        return $results;
    }

    /**
    * This method returns answer data to display on the answer view
    * @param string $docID AnswerID
    * @param string $locale Locale of the answer
    * @param array $searchAnswerData Search answer data
    * @param string $answerData Cached meta data
    * @param string $answerViewApiVersion Answer view api version
    * @param string $isAttachmentView Flag to determine if content view request is made for attachment or not
    * @param boolean $isGuestUser True if guest user
    * @return array Answer details
    */
    public function getAnswerViewData($docID, $locale, $searchAnswerData, $answerData, $answerViewApiVersion, $isAttachmentView = false, $isGuestUser = false) {
        $highlightedLink = null;
        $answerType = null;
        $answerViewApiVersion = $answerViewApiVersion ?: 'v1';
        if (is_null($docID) && is_null($searchAnswerData)) {
            Framework::setLocationHeader("/app/error/error_id/1");
            return;
        }
        $answerViewType = $this->CI->session->getSessionData('answerViewType');
        $answer = Framework::checkCache(self::ANSWER_KEY);
        $answerType = isset($answerType) ? $answerType : null;
        if (!is_null($answer))
            return $answer;

        if (!is_null($docID)) {
            if($isGuestUser) {
                $imContent = $this->getAnswerPreviewDetails($docID, $answerViewApiVersion);
            }
            else {
                $imContent = $this->processIMContent($docID, $answerViewApiVersion, $searchAnswerData, $answerType, $locale, $isAttachmentView);
            }

            if ($errorPage = $this->fetchErrorPageDetails(isset($imContent['error']->errorCode) ? $imContent['error']->errorCode : null)) {
                if (!Framework::isLoggedIn() && Text::stringContains($imContent['error']->errorCode, '403')) {
                    if(Config::getConfig(PTA_ENABLED) && Config::getConfig(PTA_EXTERNAL_LOGIN_URL)) {
                        $loginPage = Url::replaceExternalLoginVariables(0, $_SERVER['REQUEST_URI']);
                    }
                    else if($internalLogin = Config::getConfig(CP_LOGIN_URL)) {
                        $loginPage = "/app/$internalLogin/redirect/" . rawurlencode(rawurlencode($_SERVER['REQUEST_URI'])) . Url::sessionParameter();
                        //Append encrypted session cookie in URL, which will be validated during PTA login
                        if (is_object($this->session)) {
                            $ptaSessionId = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('p_ptaid' => $this->session->getSessionData('sessionID')))));
                            $loginPage .= ((strrchr($loginPage, '?')) ? '&' : '?') . "p_ptaid=$ptaSessionId";
                        }
                    }
                    else {
                        show_404();
                    }
                    $preHookData = array('data' => $loginPage);
                    \RightNow\Libraries\Hooks::callHook('pre_login_redirect', $preHookData);
                    $url = $preHookData['data'];
                    $parsedUrl = parse_url($url);
                    if (!$parsedUrl['host']) {
                        $url = Url::getShortEufBaseUrl('sameAsRequest', $url);
                    }
                    if ($expires !== null) {
                        header("Expires: $expires");
                    }
                    Framework::setLocationHeader($url);
                    return;
                }
                else {
                    Framework::setLocationHeader($errorPage);
                    return;
                }
            }

            if($answerType !== 'HTML') {
                if (!is_null($imContent['content'])) {
                    $resourcePath = $imContent['resourcePath'];
                    $contentTypeSchema = $this->getIMContentSchema($imContent['contentType']->referenceKey, isset($imContent['locale']->recordId) ? $imContent['locale']->recordId : null, $answerViewApiVersion, $isGuestUser);
                    if (!isset($contentTypeSchema->error)) {
                        $content = $schmAttrArr = $schemaAttrList = array();
                        $okcs = new \RightNow\Utils\Okcs();
                        $contentData = $okcs->getAnswerView($imContent['content'], $contentTypeSchema['contentSchema'], "CHANNEL", $resourcePath);
                        $contentXpathCount = $contentData['xpathCount'];
                        $contentSchema = $this->getSchemaAtributes($contentTypeSchema['contentSchema'], $schmAttrArr, $schemaAttrList, $contentXpathCount, 1);
                        $sortedContedData = $this->sortContentData($contentData, $schemaAttrList);
                        array_push($content, array('content' => $sortedContedData, 'contentSchema' => $contentSchema, 'type' => 'CHANNEL'));
                        if(!is_null($imContent['metaContent']) && !empty($imContent['metaContent'])) {
                            $schmAttrArr = $schemaAttrList = array();
                            $metaData = $okcs->getAnswerView($imContent['metaContent'], $contentTypeSchema['metaSchema'], "META", $resourcePath);
                            $contentXpathCount = $metaData['xpathCount'];
                            $metaSchema = $this->getSchemaAtributes($contentTypeSchema['metaSchema'], $schmAttrArr, $schemaAttrList, $contentXpathCount, 1);
                            $metaContedData = $this->sortContentData($metaData, $schemaAttrList);
                            array_push($content, array('content' => $metaContedData, 'contentSchema' => $metaSchema, 'type' => 'META'));
                        }
                        $answer = array(
                            'title' => $imContent['title'],
                            'docType' => $answerType,
                            'docID' => $imContent['docID'],
                            'version' => $imContent['version'],
                            'published' => $imContent['published'] ? Config::getMessage(PUBLISHED_LBL) : Config::getMessage(DRAFT_LBL),
                            'publishedDate' => $imContent['publishedDate'],
                            'categories' => $imContent['categories'],
                            'data' => $content,
                            'versionID' => $imContent['versionID'],
                            'contentRecordID' => $imContent['recordID'],
                            'contentTypeReferenceKey' => $imContent['contentType']->referenceKey,
                            'lastModifier' => $imContent['lastModifier'],
                            'locale' => $imContent['locale'],
                            'owner' => $imContent['owner']->name,
                            'lastModifiedDate' => $imContent['lastModifiedDate'],
                            'answerId' => $imContent['answerId'],
                            'aggregateRating' => isset($imContent['aggregateRating']) ? $imContent['aggregateRating'] : null,
                            'creator' => $imContent['creator'],
                            'userGroup' => $imContent['userGroups'],
                            'allowRecommendations' => $contentTypeSchema['allowRecommendations']
                        );
                        if(trim($imContent['title']) === '') {
                            $answer['title'] = Config::getMessage(NO_TTLE_LBL);
                        }
                        if ($imContent['locale'] != str_replace("-", "_", $this->getLocaleCode())) {
                            $contentLocales = $this->CI->session->getSessionData('contentLocales');
                            if (!$contentLocales || empty($contentLocales)) {
                                $contentLocales = array();
                            }
                            if (!in_array($imContent['locale'], $contentLocales)) {
                                array_push($contentLocales, $imContent['locale']);
                                $this->CI->session->setSessionData(array('contentLocales' => $contentLocales));
                            }
                        }
                    }
                }
                else {
                    return $imContent;
                }
            }
            else {
                $answer = array('content' => $imContent);
            }
            if (is_null($answer)) {
                Framework::setLocationHeader("/app/error/error_id/1");
                return;
            }
        }
        else if(is_null($docID) && !Text::stringContains($highlightedLink, 'priorTransactionId=')) {
            $answer = array('docUrl' => !$highlightedLink ? urldecode(Text::getSubstringAfter($answerData, '/url/')) : $highlightedLink);
            if (is_null($answer['docUrl']) || $answer['docUrl'] === '') {
                Framework::setLocationHeader("/app/error/error_id/1");
                return;
            }
        }
        else {
            Framework::setLocationHeader("/app/error/error_id/1");
            return;
        }
        if(!is_null($answerViewType) && $answerViewType === "okcsAnswerPreview") {
            $answer['answerPreview'] = true;
        }
        Framework::setCache(self::ANSWER_KEY, $answer, true);
        return $answer;
    }

    /**
    * This method returns array schema attributes
    * @param array $contentSchema Content Schema
    * @param array &$schmAttrArr Schema
    * @param array &$schemaAttrList Schema Attribute
    * @param array $contentXpathCount Schema Xpath count
    * @param int $depth Schema Xpath Depth
    * @return array Schema attributes
    */
    function getSchemaAtributes($contentSchema, &$schmAttrArr, &$schemaAttrList, $contentXpathCount, $depth) {
        foreach((array)$contentSchema as $contentAttr){
            array_push($schmAttrArr, $contentAttr->xpath);
            $attrXpath = str_replace('//', '', $contentAttr->xpath);
            if(!isset($schemaAttrList[$depth])){
                $schemaAttrList[$depth] = array();
            }
            for($i = 0; $i < (isset($contentXpathCount[$attrXpath]) ? $contentXpathCount[$attrXpath] : 0); $i++){
                $i == 0 ? array_push($schemaAttrList[$depth], $attrXpath) : array_push($schemaAttrList[$depth], $attrXpath . '-' . $i);
            }
            if($contentAttr->children > 0)
                $this->getSchemaAtributes($contentAttr->children, $schmAttrArr, $schemaAttrList, $contentXpathCount, $nextIndex = $depth + 1);
        }
        return $schmAttrArr;
    }
    /**
    * This method returns content data array sorted by attributes
    * @param array $contentData Answer Content
    * @param array $schemaAttrList Schema Attribute
    * @return array Sorted Content
    */
    function sortContentData($contentData, $schemaAttrList){
        $sortedContent = array();
        $schemaPath = $schemaAttrList[1];
        $contentXpaths = array_keys($contentData);
        $xpathIndexes = array_flip($contentXpaths);
        for($i = 0; $i < count($schemaPath); $i++){
            $xpath = str_replace('//', '', $schemaPath[$i]);
            $sortedContent[$xpath] = $contentData[$xpath];
        }
        for($j = 2; $j <= count($schemaAttrList); $j++){
            $schemaPath = $schemaAttrList[$j];
            for($k = count($schemaPath); $k >= 0; $k--){
                $xpath = str_replace('//', '', $schemaPath[$k]);
                $ind = 1;
                while($contentData[$contentXpaths[$xpathIndexes[$xpath] - $ind]]['depth'] >= ($j - 1)){
                    if($contentData[$contentXpaths[$xpathIndexes[$xpath] - $ind]]['depth'] == ($j - 1)){
                        $prevLevelPath = $contentXpaths[$xpathIndexes[$xpath] - $ind];
                        $sortedContent = $this->sortContentAttribute($sortedContent, $xpath, $contentData[$xpath], array_search($prevLevelPath, array_keys($sortedContent)) + 2);
                        break;
                    }
                    $ind++;
                }
            }
        }
        return $sortedContent;
    }
    /**
    * This method returns content data array sorted by attributes
    * @param array $contentData Content Data Array
    * @param string $xpath Schema Xpath
    * @param array $data Content Data
    * @param int $position Content Data position
    * @return array Sorted Content
    */
    function sortContentAttribute($contentData, $xpath, $data, $position) {
        $sortedContentData = array_slice($contentData, 0, $position - 1, true) + array($xpath => $data) + array_slice($contentData, $position - 1, count($contentData), true);
        return $sortedContentData;
    }

    /**
    * Method to update article rating
    * @param array $ratingData Rating data array
    * @return object|bool True if the rating was submitted successfully or Api response object
    */
    public function submitRating($ratingData) {
        $surveyRecordID = $ratingData['surveyRecordID'];
        $answerRecordID = $ratingData['answerRecordID'];
        $contentRecordID = $ratingData['contentRecordID'];
        $localeRecordID = $ratingData['localeRecordID'];
        if(!$localeRecordID) {
            $localeRecordID = str_replace("-", "_", $this->getLocaleCode());
        }
        else{
            $localeRecordID = str_replace("-", "_", $localeRecordID);
        } 
        $ratingPercentage = $ratingData['ratingPercentage'];
        $answerComment = $ratingData['answerComment'];
        $response = $this->okcsApi->submitRating($surveyRecordID, $answerRecordID, $contentRecordID, $localeRecordID, $answerComment);
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'rate', $ratingData['answerID']);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'rate', 'info', array('answerID' => $ratingData['answerID']));
        ActionCapture::record(self::OKCS_ADVANCED . 'answer', 'rated', $ratingPercentage);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'answer', 'rated', 'info', array('ratingPercentage' => $ratingPercentage));
        if(isset($response->errors) && $response->errors !== null) {
            return $response;
        }
        return $this->getResponseObject(true, 'is_bool');
    }

    /**
    * Method to update search rating
    * @param int $rating User rating
    * @param string $feedback User feedback
    * @param int $priorTransactionID Prior TransactionID
    * @param string $searchSession Search Session
    * @return object|bool True if the rating was submitted successfully or Api response object
    */
    public function submitSearchRating($rating, $feedback, $priorTransactionID, $searchSession) {
        $searchSession = $this->decodeAndDecryptData($searchSession);
        $response = $this->okcsApi->submitSearchRating($rating, $feedback, $priorTransactionID, $searchSession);
        ActionCapture::record(self::OKCS_ADVANCED . 'search', 'rated', $rating);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'search', 'rated', 'info', array('Rating' => $rating));
        ActionCapture::record(self::OKCS_ADVANCED . 'searchFeedback', 'submit', $feedback);
        ActionCapture::instrument(self::OKCS_ADVANCED . 'searchFeedback', 'submit', 'info', array('feedback' => $feedback));
        return $response->result->errors !== null ? $this->getResponseObject($response) : $this->getResponseObject(true, 'is_bool');
    }

    /**
    * Gets OKCS search result for SADialog when OKCS_ENABLED is active
    * @param array &$hookData Data from the pre_retrieve_smart_assistant_answers hook having keys:
    *   array 'formData' Form fields
    *   string 'token' value of KnowledgeApiSessionToken
    *   boolean 'canEscalate' value of 'canEscalate' property of SmartAssistant
    *   array 'suggestions' OKCS search results for SmartAssistantDialog widget
    *   int 'transactionID' OKCS search transaction ID
    *   int 'priotTansactionID' OKCS search prior transaction ID
    *   string 'okcsSearchSession' OKCS search session
    * @param string $filterBy Possible values are 'ProductAndCategory', 'Product', 'Category' and 'None'
    * @return string|null Error message, if any
    */
    public function retrieveSmartAssistantRequest(&$hookData, $filterBy = 'ProductAndCategory') {
        if(empty($hookData['suggestions'])) {
            $session = null;
            $facets = null;
            $formData = $hookData['formData'];
            $searchQuery = array_key_exists('Incident.Subject', $formData) ? $formData['Incident.Subject'] : $formData['Socialquestion.Subject'];
            $transactionID = $this->CI->model("Okcs")->getTransactionID();
            $priorTransactionID = $this->CI->model("Okcs")->getPriorTransactionID();

            $filters = array(
                'query' => $searchQuery->value,
                'locale' => 'saDefaultLocale',  // pass this value for OKCS model getSearchResult in case of SmartAssistant request
                'session' => $session,
                'transactionID' => $transactionID
            );

            if(($filterBy === 'ProductAndCategory' || $filterBy === 'Product') && !is_null($formData['Incident.Product']->value) && !empty($formData['Incident.Product']->value)) {
                $hierarchy = $this->CI->model('Prodcat')->getFormattedChain('Product', $formData['Incident.Product']->value, true)->result;
                $facets = 'CMS-PRODUCT.' . $this->CI->model("Okcs")->getProdCatHierarchy($hierarchy);
            }

            if(($filterBy === 'ProductAndCategory' || $filterBy === 'Category') && !is_null($formData['Incident.Category']->value) && !empty($formData['Incident.Category']->value)) {
                $hierarchy = $this->CI->model('Prodcat')->getFormattedChain('Category', $formData['Incident.Category']->value, true)->result;
                $facets .= ',CMS-CATEGORY_REF.' . $this->CI->model("Okcs")->getProdCatHierarchy($hierarchy);
            }

            if(!is_null($facets) && !empty($facets))
                $filters['facets'] = $facets;

            $resultObject = $this->CI->model("Okcs")->getSearchResult($filters);
            $hookData['apiInvoked'] = true;

            if (count($resultObject->errors))
                return $resultObject->errors[0]->externalMessage;

            $hookData['priorTransactionID'] = $resultObject->result['searchState']['priorTransactionID'];
            $hookData['transactionID'] = $resultObject->result['searchState']['transactionID'];
            $searchSession = $this->decodeAndDecryptData($resultObject->result['searchState']['session']);
            $searchSession = Text::getSubstringBefore($searchSession, '_SMART_ASSISTANT');
            $hookData['okcsSearchSession'] = $resultObject->result['searchState']['session'];
            $fullResults = $resultObject->result['searchResults'];
            $results = isset($fullResults['results']->results[0]->resultItems) ? $fullResults['results']->results[0]->resultItems : null;

            // populate data to match the expected data structure for suggestions in SADialog
            $list = array();
            if (!empty($results)) {
                $searchStateUrlData = "/priorTransactionID/{$hookData['priorTransactionID']}/searchSession/{$searchSession}";
                $answerLinks = array();
                $numberOfResults = count($results) < Config::getConfig(SA_NL_MAX_SUGGESTIONS) ? count($results) : Config::getConfig(SA_NL_MAX_SUGGESTIONS);
                for ($i = 0; $i < $numberOfResults; $i++) {
                    $hrefUrlData = '';
                    $docID = '';
                    $answerLocale = '';
                    $answerStatus = '';
                    $attachmentLink = '';
                    $highlightContentFlag = false;
                    $anchor = '';

                    $linkUrl = (is_null($results[$i]->title) && is_null($results[$i]->title->url)) || ($results[$i]->type === 'template') ? $results[$i]->link : $results[$i]->title->url;
                    $data = $this->CI->model("OkcsSearch")->getValidatedLinkUrl($linkUrl, true);
                    if(Text::stringContains($results[$i]->clickThroughLink, 'turl=') && $results[$i]->fileType !== 'XML' && $results[$i]->fileType !== 'HTML'){
                        $linkUrl = (Text::getSubstringAfter($results[$i]->clickThroughLink, 'turl='));
                        $data = $this->CI->model("OkcsSearch")->getValidatedLinkUrl($linkUrl, false);
                    }
                    $linkUrl = $data['linkUrl'];
                    $anchor = $data['anchor'];
                    $title = Text::escapeHtml($results[$i]->title->snippets[0]->text);
                    if(trim($title) === '')
                        $title = Config::getMessage(NO_TTLE_LBL);

                    if (Text::stringContains($linkUrl, 'IM:') !== false) {
                        $articleData = explode(':', $linkUrl);
                        $answerLocale = $articleData[3];
                        $docID = $articleData[6];
                        $clickThrough = '/transactionID/' . ($hookData['transactionID'] + 1) . $searchStateUrlData . '/clickThrough/' . $linkUrl;
                        if(Text::stringContains($linkUrl, ':#') !== false) {
                            $answerStatus = $articleData[4];
                            $answerID = strtoupper($answerStatus) === 'PUBLISHED' ? $docID : $docID . "_d";
                            $attachment = Text::getSubstringAfter($linkUrl, ':#');
                            if(Text::stringContains($results[$i]->highlightedLink, '#xml=')) {
                                $attachment .= '#xml=' . Text::getSubstringAfter($results[$i]->highlightedLink, '#xml=');
                            }
                            $attachmentLink = $answerID . "/file/" . Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe('ATTACHMENT:' . $attachment));
                        }
                        if($results[$i]->highlightedLink) {
                            $hrefUrlData = "searchSession/{$searchSession}/txn/".(isset($searchState['transactionID']) ? $searchState['transactionID'] : null)."/highlightedLink/";
                            $highlightContentFlag = true;
                        }
                        else {
                            $answerStatus = $articleData[4];
                        }
                        $item = array('ID' => $docID, 'title' => $title);
                    }
                    else {
                        $clickThrough = "/answerId/{$results[$i]->answerId}/txn/{$hookData['transactionID']}/searchSession/{$searchSession}/priorTransactionId/{$hookData['priorTransactionID']}";
                        if($results[$i]->fileType === 'HTML' && $results[$i]->highlightedLink) {
                            $hrefUrlData = "type/{$results[$i]->fileType}/searchSession/{$searchSession}/txn/{$searchState['transactionID']}/highlightedLink/";
                            $highlightContentFlag = true;
                        }
                        else {
                            $hrefUrlData = 'url/' . $linkUrl;
                            if($results[$i]->highlightedLink)
                                $linkUrl = $results[$i]->highlightedLink;
                        }
                        $item = array('title' => urldecode(empty($title) ? $linkUrl : $title), 'url' => $linkUrl);
                    }
                    $iqAction = null;
                    $item['docID'] = $results[$i]->docId;
                    $item['answerID'] = $results[$i]->answerId;
                    $item['type'] = $results[$i]->type === 'template' ? 'CMS-XML' : $results[$i]->fileType;
                    $item['clickThrough'] = Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($clickThrough));
                    $item['iqAction'] = $iqAction;

                    if ($results[$i]->highlightedLink && $highlightContentFlag) {
                        $query = parse_url($results[$i]->highlightedLink, PHP_URL_QUERY);
                        parse_str($query, $params);
                        $highlightInfo = $params['highlight_info'];
                        $trackedURL = $params['turl'];
                        $highlightData = "/answerId/{$results[$i]->answerId}/txn/{$hookData['transactionID']}/searchSession/{$searchSession}/priorTransactionId/{$hookData['priorTransactionID']}";
                        $urlData = "priorTransactionId={$hookData['priorTransactionID']}&answerId={$params['answer_id']}&highlightInfo={$highlightInfo}&trackedURL={$trackedURL}";
                        $hrefUrlData .= $urlData;
                        $item['highlightLink'] = Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($highlightData . '/highlightedLink/' . $urlData));
                    }

                    $hrefUrlData = (!empty($answerStatus)) ? "title/{$title}/a_status/{$answerStatus}/{$hrefUrlData}" : "title/{$title}/{$hrefUrlData}";
                    if($docID !== '') {
                        $href = '/a_id/' . $docID;
                        $item['imDocID'] = $docID;
                    }
                    $clickThruData = array(
                        'trackedURL' => isset($results[$i]->clickThroughUrl) ? $results[$i]->clickThroughUrl : null,
                        'answerID' => $results[$i]->answerId,
                        'docID' => $results[$i]->docId
                    );
                    if($attachmentLink !== '') {
                        $item['docID'] = $docID;
                        $item['url'] = $item['href'] = $attachmentLink;
                        $answerLinks[$docID] = array('UrlData' => $attachmentLink, 'clickThruData' => $clickThruData);
                    }
                    else {
                        if($answerLocale !== '')
                            $href .= "/loc/{$answerLocale}";
                        $item['href'] = $href . '/s/' . $results[$i]->answerId;
                        $answerLinks[$results[$i]->answerId] = array(
                            'UrlData' => Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($hrefUrlData)),
                            'clickThruData' => $clickThruData
                        );
                    }
                    $href = '';
                    array_push($list, $item);
                }
            }
            $answerLinks['user'] = $this->getUser();

            if (count($list) > 0) {
                foreach ($list as &$answer) {
                    if(!Text::stringContains($answer['href'], '/s/')) {
                        $answer['url'] = $answer['href'] = '/ci/okcsFattach/get/' . $answer['url'] . '/' . $answer['type'] . '/' . $filters['sessionKey'] . '/'  . $this->getUrlParameter($answer->clickThroughLink, 'prior_transaction_id') . '/' . $answer['answerID'] . '#__highlight';
                    }
                    else if(isset($answer->fileType) && $answer->fileType === 'CMS-XML') {
                        $answer['href'] .= '_' . $filters['sessionKey'] . '/prTxnId/' . $this->getUrlParameter($answer->clickThroughLink, 'prior_transaction_id') .'#__highlight';
                    }
                }
            }
            $hookData['suggestions'] = array();
            ActionCapture::record(self::OKCS_ADVANCED . 'smartAssistant', 'resultView');
            ActionCapture::instrument(self::OKCS_ADVANCED . 'smartAssistant', 'resultView', 'info');
            $this->mergeSaAnswersDisc($hookData, $list, $searchQuery->value);
        }
    }

    /**
    * This method returns hierarchy of the selected product or category.
    * @param array $prodCatList Array of ExternalIDs of the selected product and cetegories
    * @return string Product/Category hierarchy
    */
    public function getProdCatHierarchy(array $prodCatList) {
        $prodCatHierarchy = null;
        $batchData = array();
        $listCount = count($prodCatList);
        if($listCount > 0) {
            for($counter = 0; $counter < $listCount; $counter++) {
                $categoryExternalID = $prodCatList[$counter];
                $data = array('id' => $counter + 1, 'method' => 'GET', 'relativeUrl' => "{$this->apiVersion}/categories?q=externalId eq {$categoryExternalID}&mode=KEY", 'bodyClassName' => null, 'body' => null);
                array_push($batchData, $data);
            }
            $postData = array('asynchronous' => false, 'requests' => $batchData);
            $results = $this->okcsApi->getProdCatHierarchy($postData)->items;
            for($counter = 0; $counter < $listCount; $counter++) {
                $prodCat = json_decode($results[$counter]->body)->items;
                $prodCat = isset($prodCat[0]->referenceKey) ? $prodCat[0]->referenceKey : null;
                if($prodCat !== null && $prodCat !== '')
                    $prodCatHierarchy = empty($prodCatHierarchy) ? $prodCat : $prodCatHierarchy . '.' . $prodCat;
            }
        }
        return $prodCatHierarchy;
    }

    /**
    * This method returns highlighted html
    * @param array $urlData Details for highlight link
    * @return array Array of html and externalUrl
    */
    public function getHighlightedHTML($urlData) {
        $accessType = isset($urlData['accessType']) ? $urlData['accessType'] : null;
        $searchData = array(
            'answerType' => $urlData['ansType'],
            'searchSession' => $urlData['searchSession'],
            'transactionID' => $urlData['txnId'],
            'priorTransactionId' => $urlData['prTxnId'],
            'answerId' => $urlData['answerId'],
            'accessType' => $accessType
        );
        $response = $this->okcsApi->getHighlightContent($searchData);
        return array('url' => $response->url, 'html' => $response->HttpPassThrough);
    }

    /**
    * This method returns list of subscribed answers
    * @param string $answerId Answer Id for which to get subscription details
    * @return array|null Array of subscribed answers. This would be null if user is not logged in.
    */
    public function getSubscriptionList($answerId = null) {
        $response = null;
        $user = $this->getLoggedInUser();
        $response = null;
        if(Framework::isLoggedIn() && !is_null($user)) {
            $userID = $this->getUserRecordID();
            $response = $this->okcsApi->getSubscriptionList($userID, $answerId);
            $response = isset($response->result->errors) && $response->result->errors !== null ? $this->getResponseObject($response) : $response;
        }
        return $response;
    }

    /**
    * This method returns sorted list of subscriptions
    * @param string $sortColumn Column for sorting
    * @param string $direction Sorting order
    * @return array Array of subscribed answers
    */
    public function sortNotifications($sortColumn, $direction) {
        $response = null;
        if(Framework::isLoggedIn()) {
            $response = $this->okcsApi->getSortedSubscriptionList($this->getUserRecordID(), $sortColumn, $direction);
        }
        return $response;
    }

    /**
    * This method returns contentType based list of subscriptions
    * @param string $offset Offset in the notification list
    * @param string $limit Limit of the notification list
    * @return array Array of subscription details
    */
    public function getContentTypeSubscriptionList($offset=0, $limit=20) {
        $response = null;
        if(Framework::isLoggedIn()) {
            $response = $this->okcsApi->getContentTypeSubscriptionList($this->getUserRecordID(), $offset, $limit);
        }
        return $response;
    }

    /**
    * This method returns subscription details
    * @param string $subscriptionID SubscriptionID
    * @return array Array of subscription details
    */
    public function unsubscribeAnswer($subscriptionID) {
        $user = $this->getLoggedInUser();
        if(!is_null($user)){
            $response = $this->okcsApi->unsubscribeAnswer($this->getUserRecordID(), $subscriptionID);
            return isset($response->errors) && $response->errors !== null ? $response : $this->getResponseObject(true, 'is_bool');
        }
    }

    /**
    * Method to add subscription for logged in user
    * @param array $subscriptionData Subscription data
    * @return object Subscription Info Object
    */
    public function addSubscription($subscriptionData) {
        $response = null;
        $user = $this->getLoggedInUser();

        if(Framework::isLoggedIn() && !is_null($this->getUserRecordID())){
            $response = $this->okcsApi->addSubscription($this->getUserRecordID(), $subscriptionData['answerID'], $subscriptionData['versionID'], $subscriptionData['documentID']);
        }
        return $response;
    }

    /**
    * Method to add content type subscription for logged in user
    * @param array $subscriptionData Subscription data
    * @return object Subscription Info Object
    */
    public function addContentTypeSubscription($subscriptionData) {
        $response = null;
        $user = $this->getLoggedInUser();

        if(Framework::isLoggedIn() && !is_null($this->getUserRecordID())){
            $response = $this->okcsApi->addContentTypeSubscription($this->getUserRecordID(), $subscriptionData);
        }
        return $response;
    }
    
    /**
    * Method to create recommendation in Info Manager
    * @param array $recommendationData Recommendation data
    * @return object Recommendation Object
    */
    public function createRecommendation(array $recommendationData) {
        $response = $this->okcsApi->createRecommendation($recommendationData);
        return $response;
    }

    /**
    * This method returns the updated recent searches information
    * @param int $numberOfSuggestions Number of suggestions
    * @return array An array of recent searches
    */
    public function getUpdatedRecentSearches($numberOfSuggestions) {
        $recentSearchSuggestions = array();
        $updatedSuggestions = $this->CI->session->getSessionData('recentSearches');
        if(!empty($updatedSuggestions)) {
            foreach($updatedSuggestions as $recentSearchValue) {
                $decryptedQuery = Api::ver_ske_decrypt($recentSearchValue);
                array_push($recentSearchSuggestions, $decryptedQuery);
            }
        }
        $recentSearchSuggestions = array_reverse($recentSearchSuggestions);
        $recentSearchSuggestions = array_slice($recentSearchSuggestions, 0, $numberOfSuggestions);
        return $recentSearchSuggestions;
    }

    /**
    * Gets the internal article from Info Manager
    * @param string $answerID AnswerID in Info Manager
    * @param string $status Status of the article
    * @param string $answerViewApiVersion Answer view api version
    * @param boolean $ignoreContentViewEvent Flag to record entry in okcs_stg_content_view table
    * @return object Info Manager Content Object
    */
    public function getArticle($answerID, $status, $answerViewApiVersion = 'v1', $ignoreContentViewEvent = true) {
        if(!is_numeric($answerID))
            return $this->getResponseObject(null, null, 'Invalid answer Id');
        $response = $this->okcsApi->getArticle($answerID, $status, $answerViewApiVersion, false, $ignoreContentViewEvent);
        return $response;
    }

    /**
    * This method returns all the answer id's for the doc id's
    * @param string $docIdQuery Doc id's of Info Manager articles
    * @return array An array of answer id's for the specified doc id's
    */
    public function getAnswerIds($docIdQuery) {
        $answerIdLst = array();
        $response = $this->okcsApi->getAnswerForDocId($docIdQuery);
        if(isset($response->items) && is_array($response->items) && count($response->items) > 0) {
            foreach ($response->items as $contentSet) {
                $answerIdLst["'" . $contentSet->documentId . "'"] = $contentSet->answerId;
            }
        }
        return $answerIdLst;
    }

    /**
    * This method returns key value from the Url.
    * Sample url format /key1/value1/key2/value2
    * @param string $url Url
    * @param string $key Url parameter key
    * @return string Url parameter value
    */
    private function getUrlParameter($url, $key) {
        if (preg_match("/\/$key\/([^\/]*)(\/|$)/", $url, $matches)) return $matches[1];
    }

    /**
    * Method to set user language preferences.
    * @param string $userLanguages Selected languages
    * @param string $retryFlag To decide if we need to retry api in case of 403
    */
    private function setUserLanguagePreference($userLanguages, $retryFlag) {
        $userRecordID = $this->getUserRecordID();
        if($userRecordID !== null) {
            $preferredLanguage = $this->getUserLanguagePreferences();
            if(!isset($preferredLanguage->errors)) {
                $response = $this->okcsApi->setUserLanguagePreference($userLanguages, $preferredLanguage, $userRecordID);
                if(isset($response->errors) && $response->errors && $response->errors[0]->errorCode === 'HTTP 409' && $retryFlag === true)
                   $this->setUserLanguagePreference($userLanguages, true);
            }
        }
    }

    /**
    * Method returns mem cache instance.
    * @return object Cache instance
    */
    private function getMemCache() {
        return ($this->cache === null) ? new \RightNow\Libraries\Cache\Memcache(self::CACHE_TIME_IN_SECONDS) : $this->cache;
    }

    /**
    * Method returns mem cache key value
    * @param string $key Key
    * @return object|null Cached key value
    */
    private function getMemCacheData($key) {
        if(Text::stringContains(Config::getConfig(OKCS_IM_API_URL), self::OKCS_KM_ENDPOINT) || Text::stringContains(Config::getConfig(OKCS_SRCH_API_URL), self::OKCS_SRT_ENDPOINT)) {
            return null;
        }
        return $this->getMemCache()->get($key);
    }

    /**
    * Method to get anonymousUser
    * @return string Anonymous User
    */
    private function getAnonymousUser() {
        $iniArray = @parse_ini_file(APPPATH . 'config/okcs.ini');
        $user = strtolower(self::GUEST_USER);
        if(!(is_bool($iniArray) && !$iniArray))
            $user = is_null($iniArray['GUEST_USER']) ? $user : $iniArray['GUEST_USER'];
        return $user;
    }

    /**
    * Method to get default locale code
    * @return string Locale code
    */
    private function getLocaleCode() {
        return \RightNow\Utils\Okcs::getInterfaceLocale();
    }

    /**
    * Sets the desired value for Query source parameter
    * @param string $querySource Filter parameter query source
    * @return string Search source query
    */
    private function getSourceQuery($querySource) {
        if(empty($querySource)){
            if(isset($_SERVER['HTTP_REFERER'])) {
                $querySource = 'quicksearch';
                if(strpos($_SERVER['HTTP_REFERER'], 'home') !== false)
                    $querySource = 'home';
            }
            else {
                return self::OKCS_DEFAULT_QUERY_SOURCE;
            }
        }
        return $querySource;
    }

    /**
    * This method returns logged in or guest user
    * @return string Guest or logged in user id
    */
    private function getUser() {
        return Framework::isLoggedIn() ? $this->CI->model('Contact')->get()->result->Login : $this->getAnonymousUser();
    }

    /**
    * This method returns logged in or guest user
    * @return string Guest or logged in user id
    */
    private function getLoggedInUser() {
        if(Framework::isLoggedIn()){
            return $this->CI->model('Contact')->get()->result->Login;
        }else{
            return null;
        }
    }

    /**
    * This method updates the recent searches session variable with latest searched values
    * @param string $query Search query
    */
    private function updateRecentSearchesArray($query) {
        $maxNumberOfSuggestions = 10;
        $decryptedArray = array();
        $sessionRecentSearches = $this->CI->session->getSessionData('recentSearches');
        if(!is_null($query) && strlen($query) !== 0) {
            $query = Api::ver_ske_encrypt_urlsafe($this->removeMultipleWhiteSpace($query));
            if(empty($sessionRecentSearches)) {
                $sessionRecentSearches = array();
                array_push($sessionRecentSearches, $query);
            }
            else {
                for($k = 0; $k < count($sessionRecentSearches); $k++) {
                    if(strtoupper(Text::unescapeHtml(Api::ver_ske_decrypt($query))) === strtoupper(Text::unescapeHtml(Api::ver_ske_decrypt($sessionRecentSearches[$k]))))
                        unset($sessionRecentSearches[$k]);
                }
                if(count($sessionRecentSearches) === $maxNumberOfSuggestions) {
                    unset($sessionRecentSearches[0]);
                }
                array_push($sessionRecentSearches, $query);
            }
        }
        $sessionRecentSearches = array_slice($sessionRecentSearches, 0, $maxNumberOfSuggestions);
        $this->CI->session->setSessionData(array('recentSearches' => $sessionRecentSearches));
    }

    /**
    * This method returns the url re-direction string
    * to be populated for various error scenarios
    * @param string $errorCode Error code
    * @return string|null Error URL value
    */
    private function fetchErrorPageDetails($errorCode) {
        switch ($errorCode) {
            case self::OKCS_BAD_REQUEST:
            case self::OKCS_NOT_FOUND_REQUEST:
            case self::OKCS_CONFLICT_REQUEST:
            case self::OKCS_INTERNAL_SERVER_ERROR:
            case self::OKCS_SERVICE_UNAVAILABLE:
                return "/app/error/error_id/1";
            case self::OKCS_FORBIDDEN_REQUEST:
                return "/app/error/error_id/4";
        }
    }

    /**
    * This method helps in removing multiple spaces between a string
    * @param string $query Search query
    * @return string Search query with removed spaces
    */
    private function removeMultipleWhiteSpace($query) {
        $query = trim(preg_replace('/\s+/', ' ', $query));
        return $query;
    }

    /**
    * Gets count of articles for sitemap index
    * @param array $hookData Data from the okcs_site_map_answers hook having keys:
    *   int 'pageNumber' PageNumber for which sitemap data is retrieved
    *   int 'sitemapPageLimit' Maximum number of urls on each sitemap page
    * @param int $maxSiteMapLinks Max number of Sitemap links that will be displayed on all pages combined
    * @param string $contentTypes Content Types
    * @param int $maxPerBatch Max number of API calls included in one batch
    * @return array Array of internal articles
    */
    private function getArticlesCountForSiteMapIndex(array $hookData, $maxSiteMapLinks, $contentTypes, $maxPerBatch) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($maxPerBatch || true);
        $pageNumber = $hookData['pageNumber'];
        $pageSize = $hookData['sitemapPageLimit'];
        $channelsArray = array();
        $results = array();
        if($contentTypes === '*' || empty($contentTypes)){
            $channelsList = $this->getChannels($this->apiVersion);
            if(is_null($channelsList->errors)) {
                foreach ($channelsList->items as $channels) {
                    array_push($channelsArray, $channels->referenceKey);
                }
            }
        }
        else {
            $contentTypes = trim($contentTypes, '()');
            $channelsArray = explode(',', $contentTypes);
        }
        $siteMapFilter = array(
            'pageNumber' => $pageNumber,
            'contentStatus' => 'PUBLISHED',
            'contentTypes' => $channelsArray
            );
        $response = $this->okcsApi->getArticlesForSiteMapBatch($siteMapFilter);
        $rowCount = 0;
        if(isset($response->items) && is_array($response->items) && count($response->items) > 0) {
            foreach ($response->items as $contentSet) {
                $contentSetCount = json_decode($contentSet->body);
                $rowCount += $contentSetCount->publicDocumentCount;
            }
        }
        $result = array($url, $rowSize = null, $rowTime = null, $document->title);
        // Setting the rowCount to maxSiteMapLinks when rowCount is greater
        $rowCount = $rowCount > $maxSiteMapLinks ? $maxSiteMapLinks : $rowCount;
        $results['data'][$rowCount] = $result;
        $results['total_pages'] = ($pageSize > 0 && $rowCount > 0) ? ceil($rowCount / $pageSize) : 0;
        $results['page'] = $pageNumber;
        $results['total_num'] = $rowCount;
        return $results;
    }

    /**
    * Gets list of articles for a specified page
    * @param array $hookData Data from the okcs_site_map_answers hook having keys:
    *   int 'pageNumber' PageNumber for which sitemap data is retrieved
    *   int 'sitemapPageLimit' Maximum number of urls on each sitemap page
    * @param int $maxSiteMapLinks Max number of Sitemap links that will be displayed on all pages combined
    * @param string $contentTypes Content Types
    * @param int $maxPerBatch Max number of API calls included in one batch
    * @return array Array of internal articles
    */
    private function getArticlesForSiteMapPages(array $hookData, $maxSiteMapLinks, $contentTypes, $maxPerBatch) {
        $pageNumber = $hookData['pageNumber'];
        $pageSize = $hookData['sitemapPageLimit'];
        $rowCount = $offSet = 0; $results = array();
        $initialOffSet = $offSet = $pageSize * ($pageNumber - 1);
        $pageMax = $pageSize * $pageNumber;
        // Retrieve only till maxSiteMapLinks when pageMax is greater
        $pageMax = $pageMax > $maxSiteMapLinks ? $maxSiteMapLinks : $pageMax;
        while($offSet < $pageMax){
            $siteMapFilter = array(
                'pageNumber' => $pageNumber,
                'pageSize' => $pageSize,
                'contentStatus' => 'PUBLISHED',
                'contentTypes' => $contentTypes,
                'offSet' => $offSet,
                'maxPerBatch' => $maxPerBatch,
                'maxSiteMapLinks' => $maxSiteMapLinks,
                'pageMax' => $pageMax
                );
            $articleList = $this->okcsApi->getArticlesForSiteMapBatch($siteMapFilter)->items;
            if(count($articleList) > 0) {
                foreach ($articleList as $articleSet) {
                    $articles = json_decode($articleSet->body);
                    foreach ($articles->items as $key => $document) {
                        if($document->answerId) {
                            $link = '/app/' . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/' . $document->answerId;
                            $url = "<a href='{$link}'>{$document->answerId}</a>";
                            $result = array($url, $rowSize = null, $rowTime = null, $document->title);
                            $results['data'][$rowCount] = $result;
                            $rowCount++;
                        }
                        // To restrict additional API calls when there are no more answers
                        if(!$articles->hasMore){
                            $offSet = $pageMax;
                        }
                    }
                }
            }
            if($offSet !== $pageMax) {
                $offSet = $initialOffSet + $rowCount;
            }
        }
        $totalResults = $rowCount;
        $results['total_pages'] = ($pageSize > 0 && $totalResults > 0) ? ceil($totalResults / $pageSize) : 0;
        $results['page'] = $pageNumber;
        $results['total_num'] = $totalResults;
        return $results;
    }
    
    /**
    * Gets list of sender email addresses which are allowed to send email. 
    * @return string emailid's seperated by comma.
    */
    public function getAllowedSenderEmailList() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_OKCS_SEND_EMAIL_ALLOWED_LIST')->Value;
        }
        catch (\Exception $err ) {
            return null;
        }
    }

    /**
    * This method is used to check if the sender email validation is required or not. 
    * @return string either 1 or 0.
    */
    public function isSenderEmailSecurityRequired() {
        try {
            return Connect\Configuration::fetch('CUSTOM_CFG_OKCS_LOGIN_REQUIRED_TO_EMAIL')->Value;
        }
        catch (\Exception $err ) {
            return "0";
        }
    }    

    /**
     * Sends an email regarding an OKCS answer to the specified email.
     * @param array $emailData Email data
     * @return bool Whether the email was successfully sent or not
     */
    public function emailOkcsAnswerLink($emailData) {
        $serverUrl = (Config::getConfig(SEC_END_USER_HTTPS) ? 'https://' : 'http://') . Config::getConfig(OE_WEB_SERVER);
        $answerDetailConfig = Config::getConfig(CP_ANSWERS_DETAIL_URL);
        $answerUrl = $serverUrl . '/app/' . $answerDetailConfig . '/a_id/' . $emailData['answerID'];
        $from = $emailData['from'];

        if($this->isSenderEmailSecurityRequired() && $this->isSenderEmailSecurityRequired() == '1' && $this->getAllowedSenderEmailList() !== null && $this->getAllowedSenderEmailList() !== ''){
            $allowedSenderEmails = explode(",", $this->getAllowedSenderEmailList());
            $isValidSender = false;
            foreach($allowedSenderEmails as $emailAdd){
                $emailAdd = trim($emailAdd);
    
                if($from === $emailAdd){
                    $isValidSender = true;
                    break;
                }
            }
    
            if(!$isValidSender){
                return $this->getResponseObject(false, 'is_bool', 'sender email is invalid.')->error;
            }
        }

        // Check if token is compliant with the one generated in controller of the widget
        if(!Framework::isValidSecurityToken($emailData['emailAnswerToken'], 146)) {
            // If form token is invalid report success anyway
            return $this->getResponseObject(true, 'is_bool');
        }

        // Get subject and validate name
        $title = $emailData['title'];
        $name = trim($emailData['name']);
        if ($title === null || $name === '') {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(THERE_WAS_ERROR_EMAIL_WAS_NOT_SENT_LBL));
        }
        $name = Text::escapeHtml($name);

        $emailAddressError = function($email) {
            return (!Text::isValidEmailAddress($email) || Text::stringContains($email, ';') || Text::stringContains($email, ',') || Text::stringContains($email, ' '));
        };
        $sendTo = trim($emailData['sendTo']);
        if ($emailAddressError($sendTo)) {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(RECIPIENT_EMAIL_ADDRESS_INCORRECT_LBL));
        }
        if ($emailAddressError($from)) {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(SENDER_EMAIL_ADDRESS_INCORRECT_LBL));
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }

        try {
            $email = new Connect\Email();
            $email->Address = $from;
            $email->AddressType->ID = 0;

            $mailMessage = new Connect\MailMessage();
            $mailMessage->To->EmailAddresses = array($emailData['sendTo']);
            $mailMessage->FriendlyFromAddress = $from;
            $mailMessage->ReplyToAddress = $from;

            if($emailData['databaseEmailTemplate'] === 'true') {
                if(\RightNow\Internal\Utils\Version::compareVersionNumbers(CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION, '3.7.6') >= 0) {
                    $emailBody = $this->okcsApi->getOkcsSendEmailTemplate();
                }
                else {
                    $emailBody = \RightNow\Internal\Sql\Okcs::getOkcsSendEmailTemplate();
                }
                $emailBody = str_replace("<SERVER_URL>", $serverUrl, $emailBody);
                $emailBody = str_replace("<SENDER_NAME>", $name, $emailBody);
                $emailBody = str_replace("<SENDER_EMAIL>", $from, $emailBody);
                $emailBody = str_replace("<MID>", $title, $emailBody);
                $emailBody = str_replace("<ANSWER_URL>", $answerUrl, $emailBody);
            }
            else {
                $emailBody = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"><html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:rn=\"http://schemas.rightnow.com/crm/document\"><head><title></title><style type=\"text/css\" xml:space=\"preserve\">/*<![CDATA[*/P.MsoNormal {MARGIN: 0px}LI.MsoNormal {MARGIN: 0px}DIV.MsoNormal {MARGIN: 0px}P.MsoListParagraph {MARGIN-LEFT: 48px}LI.MsoListParagraph {MARGIN-LEFT: 48px}DIV.MsoListParagraph {MARGIN-LEFT: 48px}/*]]>*/</style></head><body style=\"FONT-FAMILY: Segoe UI, Verdana, sans-serif\"><table style=\"FONT-FAMILY: Segoe UI, Verdana, sans-serif\" border=\"1\" =cellspacing=\"0\" cellpadding=\"0\" width=\"610\" align=\"center\"><tbody><tr><td bgcolor=\"#F5F5F5\"><table style=\"FONT-FAMILY: Segoe UI, Verdana, sans-serif\" cellpadding=3D=\"5\" width=\"600\" align=\"center\"><tbody><tr><td bgcolor=\"#FFFFFF\"><table><tbody><tr><td valign=\"top\"><img alt=\"Image\" src=\"$serverUrl/euf/assets/images/message_template.gif\" /></td><td valign=\"top\" width=\"500\"><table style=\"FONT-FAMILY: Segoe UI, Verdana, sans-serif\" cellpadding=3D=\"1\" width=\"100%\"><tbody><tr><td><p style=\"LINE-HEIGHT: 18px; MARGIN: 0px; FONT-SIZE: 12pt\">" . $emailData['emailHeaderLabel'] . "<br />" . $name . "<span style=\"FONT-SIZE: 10pt\"> - <a href=\"mailto:"  . $from . "\">" . $from . " </a><br />" . $emailData['emailSenderLabel'] . " </span></p></td></tr><tr><td style=\"BORDER-TOP: #d0d0d0 1px solid\"><h1 style=\"MARGIN: 10px 0px 5px; COLOR: #848484; FONT-SIZE: 11pt; FONT-=WEIGHT: bold\">" . $emailData['summaryLabel'] . "</h1></td></tr><tr><td><p style=\"MARGIN: 0px 0px 0px 10px\">" . $title . "</p></td></tr><tr><td><p style=\"MARGIN: 20px 0px 0px 10px\"><a href=\"$answerUrl\"> " . $emailData['answerViewLabel'] . "</a></p></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></body></html>";
            }

            $mailMessage->subject = $title;
            $mailMessage->Body->Html = $emailBody;
            $mailMessage->Options->IncludeOECustomHeaders = false;
            $mailMessage->Options->HonorGlobalSuppressionList = false;
            $mailMessage->Options->HonorMarketingOptIn = false;
            $emailSent = $mailMessage->send();
            \RightNow\ActionCapture::record('okcsAnswer', 'email', $emailData['answerID']);

            if ($emailSent == null) {
                return $this->getResponseObject(true, 'is_bool');
            }
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(SORRY_WERENT_ABLE_SEND_EMAIL_PLS_MSG));    
        }
        catch ( Exception $err ) {
            echo "<br><b>Exception</b>: line " . __LINE__ . ": " . $err->getMessage() . "</br>";
        }
    }

    /**
    * This method returns the recently viewed answer id's from session
    * @return array An array of recently viewed answer id's
    */
    public function getRecentlyViewedAnswers() {
        return $this->CI->session->getSessionData('okcsAnswersViewed');
    }

    /**
    * This method returns the recently viewed question id's from session
    * @return array An array of recently viewed question id's
    */
    public function getRecentlyViewedQuestions() {
        return $this->CI->session->getSessionData('discQuestionsViewed');
    }

    /**
    * This method sets the recently viewed answer id to session
    * @param int $answerId Okcs Answer Id
    */
    public function setRecentlyViewedAnswers($answerId) {
        $okcsRecentAnswers = $this->CI->session->getSessionData('okcsAnswersViewed');
        if(!$okcsRecentAnswers || empty($okcsRecentAnswers)) {
            $okcsRecentAnswers = array();
        }
        if(in_array($answerId, $okcsRecentAnswers)) {
            array_splice($okcsRecentAnswers, array_search($answerId, $okcsRecentAnswers), 1);
        }
        array_push($okcsRecentAnswers, $answerId);
        $this->CI->session->setSessionData(array('okcsAnswersViewed' => $okcsRecentAnswers));
    }

    /**
    * This method sets the recently viewed discussion question id to session
    * @param int $questionId Question Id
    */
    public function setRecentlyViewedQuestions($questionId) {
        $recentQuestions = $this->CI->session->getSessionData('discQuestionsViewed');
        if(!$recentQuestions || empty($recentQuestions)) {
            $recentQuestions = array();
        }
        if (in_array($questionId, $recentQuestions)) {
            array_splice($recentQuestions, array_search($questionId, $recentQuestions), 1);
        }
        array_push($recentQuestions, $questionId);
        $this->CI->session->setSessionData(array('discQuestionsViewed' => $recentQuestions));
    }

    /**
    * This method returns recently viewed OKCS answers for answer id's in session
    * @param int $widgetContentCount Number of recent answers to be fetched
    * @return array An array of recently viewed answers
    */
    public function getOkcsRecentAnswers($widgetContentCount) {
        $okcsRecentAnswers = $this->CI->session->getSessionData('okcsAnswersViewed');
        $localeCode = str_replace("-", "_", $this->getLocaleCode());
        $okcsAnswers = array_reverse($okcsRecentAnswers);
        $okcsAnswerIdList = $widgetContentCount == 0 ? $okcsAnswers : array_slice($okcsAnswers, 0, $widgetContentCount + 1);
        $articles = $this->okcsApi->getAnswers($okcsAnswerIdList, $localeCode);
        
        $recentAnswers = array();
        if(isset($articles->items) && is_array($articles->items) && count($articles->items) > 0) {
            foreach($articles->items as $articleList) {
                if(!is_null($articleList)) {
                    array_push($recentAnswers, array("answerId" => $articleList->answerId, "title" => $articleList->title));
                }
            }
        }
        $recentlyViewedAnswers = array();
        if(count($recentAnswers) > 0) {
            for($i = 0; $i < count($okcsAnswerIdList); $i++) {
                foreach($recentAnswers as $answerKey => $answerVal) {
                    if($answerVal['answerId'] == $okcsAnswerIdList[$i]) {
                        array_push($recentlyViewedAnswers, $answerVal);
                        unset($recentAnswers[$answerKey]);
                        break;
                    }
                }
            }
        }
        return $recentlyViewedAnswers;
    }

    /**
    * This method returns social discussions for the incident subject
    * @param string $incSubject Incident subject to search for
    * @return array An array of social discussions
    */
    public function getSocialDiscussions($incSubject) {
        $filters = array('limit' => array('value' => 100), 'query' => array('value' => $incSubject));
        $search = Search::getInstance('SocialSearch');
        $results = $search->addFilters($filters)->executeSearch();
        $socialResultsList = array();
        foreach($results->results as $result) {
            if($result->SocialSearch->bestAnswerCount > 0) {
                array_push($socialResultsList, array("ID" => $result->SocialSearch->id, "title" => $result->text));
            }
        }
        return($socialResultsList);
    }

    /**
    * This method merges KA answers and social discussions
    * @param array &$hookData Data from the pre_retrieve_smart_assistant_answers hook
    * @param array $ansList An array of KA answers
    * @param string $incSubject Incident subject to search for
    */
    public function mergeSaAnswersDisc(&$hookData, $ansList, $incSubject) {
        if(Config::getConfig(KFAPI_SSS_ENABLED)){
            $discList = $this->getSocialDiscussions($incSubject);
            $maxSuggestions = Config::getConfig(SA_NL_MAX_SUGGESTIONS) > 10 ? 10 : Config::getConfig(SA_NL_MAX_SUGGESTIONS);
            $ansToDiscRatio = explode(':', Config::getConfig(SA_RESULTS_COUNT_RATIO));
            $answerCount = $discCount = 0;
            if(is_array($ansToDiscRatio) && count($ansToDiscRatio) > 0) {
                foreach($ansToDiscRatio as $saType) {
                    if(!is_null($saType) && substr($saType, 0, 1) !== '-') {
                        switch(substr($saType, strlen($saType) - 1, 1)) {
                            case 'A':
                            case 'a': $answerCount = substr($saType, 0, strlen($saType) - 1);
                                break;
                            case 'S':
                            case 's': $discCount = substr($saType, 0, strlen($saType) - 1);
                                break;
                        }
                    }
                }
                if($answerCount > 0 && $answerCount < 100) {
                    $answerCount = ceil(($answerCount / 100) * $maxSuggestions);
                    $answerCount = is_array($ansList) && ($answerCount >= count($ansList)) ? count($ansList) : $answerCount;
                    $discCount = $maxSuggestions - $answerCount;
                }
                else if($discCount > 0 && $answerCount < 100) {
                    $discCount = ceil(($discCount / 100) * $maxSuggestions);
                    $answerCount = $maxSuggestions - $discCount;
                }
                else {
                    $answerCount = $maxSuggestions;
                }

                if(is_array($ansList) && ($answerCount > count($ansList))) {
                    $discCount = $maxSuggestions - count($ansList);
                }
                else if(is_array($discList) && ($discCount > count($discList))) {
                    $answerCount = $maxSuggestions - count($discList);
                }

                array_push($hookData['suggestions'], array('type' => 'AnswerSummary', 'list' => array_slice($ansList, 0, $answerCount)));
                array_push($hookData['suggestions'], array('type' => 'QuestionSummary', 'list' => array_slice($discList, 0, $discCount)));
            }
        }
        else {
            array_push($hookData['suggestions'], array('type' => 'AnswerSummary', 'list' => $ansList));
        }
    }
    /**
    * This method returns list of subscribed answers based on offset
    * @param string $offset Offset in the notification list
    * @return array|null Array of subscribed answers. This would be null if user is not logged in.
    */
    public function getPaginatedSubscriptionList($offset = 0) {
        $user = $this->getLoggedInUser();
        if(Framework::isLoggedIn() && !is_null($user)) {
            $userID = $this->getUserRecordID();
            $response = $this->okcsApi->getPaginatedSubscriptionList($userID, $offset);
            $response = isset($response->result->errors) ? $this->getResponseObject($response) : $response;
        }
        $subscriptionList = $response;
        $list = array();
        $items = array();
        if ($subscriptionList !== null && !isset($subscriptionList->error) && count($subscriptionList->items) > 0) {
            foreach ($subscriptionList->items as $document) {
                if($document->subscriptionType === 'SUBSCRIPTIONTYPE_CONTENT') {
                    $subscriptionID = $document->recordId;
                    $dateAdded = $this->CI->model('Okcs')->processIMDate($document->dateAdded);
                    $document = $document->content;
                    $document->title = Text::escapeHtml($document->title);
                    $item = array(
                        'documentId'        => Config::getMessage(DOC_ID_LBL) . ' - ' . $document->documentId,
                        'answerId'          => $document->answerId,
                        'title'             => $document->title,
                        'expires'           => sprintf(Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $dateAdded),
                        'subscriptionID'    => $subscriptionID,
                        'expiresTime'       => $expiresTime ? $expiresTime  : Config::getMessage(NO_EXPIRATION_DATE_LBL),
                        'subscriptionID'    => $subscriptionID
                    );
                    array_push($items, $item);
                }
            }
            $list['items'] = $items;
            $list['hasMore'] = $subscriptionList->hasMore;
        }
        return $list;
    }
    
    /**
    * Retrieve the aggregate rating for a recordId
    * @param string $recordId Okcs Record Id
    * @return string The aggregate rating for the record
    */
    public function getAggregateRating($recordId) {
        return $this->okcsApi->getAggregateRating($recordId);
    }

    /**
    * Method to fetch suggestions for the specified query
    * @param string $query Query to retrieve suggestions
    * @param string $limit No. of suggestions to be fetched
    * @param array $additionalParameters Additional optional parameters for filtering suggestions
    * @return array Results from query
    */
    public function getSuggestions($query, $limit, $additionalParameters = array()) {
        if($additionalParameters['applyCtIndexStatus'] === null)
            $additionalParameters['applyCtIndexStatus'] = 'true';
        $response = $this->okcsApi->getSuggestions($query, $limit, $additionalParameters);
        if((isset($response->errors) && $response->errors) || !isset($response->items))
            $response->items = array();
        return $response;
    }

    /**
    * Method to fetch all translations for a particular answerId
    * @param string $answerId Answer Id
    * @return array Results from query
    */
    public function getAllTranslations($answerId) {
        $response = $this->okcsApi->getAllTranslations($answerId);
        if ($response->result->errors !== null) {
            $response = $response['response'];
            return $this->getResponseObject($response);
        }
        else if (isset($response->items) && is_array($response->items) && count($response->items) > 0) {
            $translationItems = array();
            foreach ($response->items as $document) {
                    $item = array(
                        'answerId' => $document->answerId,
                        'localeRecordId' => $document->locale->recordId
                    );
                array_push($translationItems, $item);
            }
            $response = $translationItems;
        }
        return $response;
    }

    /**
    * Method to add an answer as a favorite for logged in user
    * @param string $answerId Answer Id
    * @return object Favorites Info Object | Error objects, if any
    */
    public function addFavorite($answerId) {
        $response = null;
        $user = $this->getLoggedInUser();
        if(Framework::isLoggedIn() && !is_null($this->getUserRecordID())){
            $favoriteDetails = $this->getFavoriteList();
            $favoriteIds = $favoriteDetails->value;
            $favoriteIdArr = array_unique(explode(",", $favoriteIds));
            foreach($favoriteIdArr as $key => $favId) {
                if($favId === $answerId) {
                    return $this->getResponseObject(null, null, array(
                        array(
                            'externalMessage' => Config::getMessage(FAVORITE_ALREADY_ADDED_MSG),
                            'errorCode' => self::OKCS_DUP_ADD_FAVORITE,
                            'extraDetails' => $favoriteDetails->recordId
                        ),
                    ));
                }
            }
            $favoriteIdArr[0] === '' ? ($favoriteIdArr[0] = $answerId) : (array_unshift($favoriteIdArr, $answerId));
            $favoriteIdsToUpdate = implode(",", $favoriteIdArr);
            $favoriteArray = array(
                'userRecordID' => $this->getUserRecordID(),
                'favoriteDetails' => $favoriteDetails,
                'answerId' => $answerId,
                'favoriteIdsToUpdate' => $favoriteIdsToUpdate
            );
            $response = $this->okcsApi->addOrRemoveFavorite($favoriteArray);
        }
        return $response;
    }

    /**
    * Method to remove an answer as a favorite for logged in user
    * @param string $answerId Answer Id
    * @return object Favorites Info Object | Error objects, if any
    */
    public function removeFavorite($answerId) {
        $response = null;
        $user = $this->getLoggedInUser();
        if(Framework::isLoggedIn() && !is_null($this->getUserRecordID())){
            $favoriteDetails = $this->getFavoriteList();
            $favoriteIds = $favoriteDetails->value;
            $favoriteIdArr = array_unique(explode(",", $favoriteIds));
            $isFavDeleted = false;
            foreach($favoriteIdArr as $key => $favId) {
                if($favId === $answerId) {
                    $isFavDeleted = true;
                    unset($favoriteIdArr[$key]);
                    break;
                }
            }
            //Negative scenario - on trying to delete same answer as favorite more than once
            if(!$isFavDeleted) {
                return $this->getResponseObject(null, null, array(
                    array(
                        'externalMessage' => Config::getMessage(FAVORITE_ALREADY_DELETED_MSG),
                        'errorCode' => self::OKCS_DUP_REMOVE_FAVORITE
                    ),
                ));
            }
            $favoriteIdsToUpdate = implode(",", $favoriteIdArr);
            $favoriteArray = array(
                'userRecordID' => $this->getUserRecordID(),
                'favoriteDetails' => $favoriteDetails,
                'answerId' => $answerId,
                'favoriteIdsToUpdate' => $favoriteIdsToUpdate
            );
            $response = $this->okcsApi->addOrRemoveFavorite($favoriteArray);
        }
        return $response;
    }

    /**
    * This method returns list of favorite answerIds
    * @return array|null Array of favorite answerIds. This would be null if user is not logged in.
    */
    public function getFavoriteList() {
        $user = $this->getLoggedInUser();
        $response = null;
        if(Framework::isLoggedIn() && !is_null($user)) {
            $userID = $this->getUserRecordID();
            $response = $this->okcsApi->getFavoriteList($userID);
            $response = isset($response->errors) || (isset($response->result->errors) && $response->result->errors !== null) ? $this->getResponseObject($response) : (isset($response->items[0]) ? $response->items[0] : null);
        }
        return $response;
    }

    /**
    * This method returns OKCS answers for answer id's across locales
    * @param String $answerIdList Comma separated answerIds
    * @return array An array of answers
    */
    public function getDetailsForAnswerId($answerIdList) {
        $articles = $this->okcsApi->getAnswersForAnswerId($answerIdList);
        $answerDetailArr = array();
        if(isset($articles->items) && is_array($articles->items) && count($articles->items) > 0) {
            foreach($articles->items as $articleList) {
                if(!is_null($articleList)) {
                    $answerDetailArr[$articleList->answerId] = array("documentId" => $articleList->documentId, "title" => $articleList->title);
                }
            }
        }
        return $answerDetailArr;
    }

    /**
    * This method returns KA API response for custom API requests
    * @param string $url KA REST API URL
    * @param string $methodType Type of http method e.g. 'GET' or 'POST'.
    * @param string $postData Post data JSON string
    * @return string JSON string returned by KA REST API
    */
    public function makeApiRequest($url, $methodType = 'GET', $postData = '') {
        return $this->okcsApi->makeApiRequest($url, $methodType, json_decode($postData));
    }

    /**
    * Method to retrieve user subscriptionSchedule.
    * @return object schedule value
    */
    public function getUserSubscriptionSchedule() {
        $user = $this->getLoggedInUser();
        if(Framework::isLoggedIn() && !is_null($user)) {
            $response = $this->okcsApi->getUserRecordID($user);
            if (isset($response->result->errors))
                return $response;
            $subscriptionSchedule = $response->subscriptionSchedule;
        }
        return $subscriptionSchedule;
    }

    /**
    * This method saves subscription schedule value
    * @param string $scheduleValue Okcs schedule value
    * @return string returned by KA REST API
    */
    public function setSubscriptionSchedule($scheduleValue){
        $response = null;
        $user = $this->getLoggedInUser();
        if(Framework::isLoggedIn() && !is_null($user)) {
            $userID = $this->getUserRecordID();
            $response = $this->okcsApi->setSubscriptionSchedule($userID, $scheduleValue);
            if ($response === null) {
                $response = "Success";
            }
        }
        return $response;
    }

}
