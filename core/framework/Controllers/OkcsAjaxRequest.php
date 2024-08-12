<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\ActionCapture,
    RightNow\Utils\Config,
    RightNow\Utils\Okcs,
    RightNow\Utils\Text,
    RightNow\Libraries\AbuseDetection;

/**
* Generic controller endpoint for standard OKCS widgets to make requests to retrieve data. Nearly all of the
* methods in this controller echo out their data in JSON so that it can be received by the calling JavaScript.
*/
final class OkcsAjaxRequest extends Base
{
    public function __construct()
    {
        parent::__construct();
        require_once CPCORE . 'Utils/Okcs.php';
    }

    /**
    * Method to fetch data through OKCS APIs
    * @internal
    */
    public function getOkcsData() {
        $filters = json_decode($this->input->post('filters'), true);
        $postAction = $this->input->post('action');
        if (strlen($this->input->post('doc_id')) !== 0) {
            $this->getIMContent();
        }
        else if ($this->input->post('getMoreAnswerNotif') === 'getMoreAnswerNotif') {
            $this->getMoreAnswerNotif();
        }
        else if ($this->input->post('getMoreContentTypeNotif') === 'getMoreContentTypeNotif') {
            $this->getMoreContentTypeNotif();
        }
        else if (strlen($this->input->post('clickThruLink')) !== 0) {
            $this->clickThru();
        }
        else if(isset($filters['isRecommendations']['value']) && $filters['isRecommendations']['value'] !== null) {
            $this->browseRecommendations();
        }
        else if(isset($filters['channelRecordID']['value']) || isset($filters['currentSelectedID']['value']) || strlen($this->input->post('answerListApiVersion')) !== 0) {
            $this->browseArticles();
        }
        else if($postAction === 'SubscriptionSchedule') {
            $this->setSubscriptionSchedule();
        }
        else if (strlen($this->input->post('deflected')) !== 0) {
            $this->getContactDeflectionResponse();
        }
        else if (strlen($this->input->post('categoryId')) !== 0) {
            $this->getChildCategories();
        }
        else if (strlen($this->input->post('getMoreProdCatFlag')) !== 0) {
            $this->getMoreProdCat();
        }
        else if (strlen($this->input->post('surveyRecordID')) !== 0) {
            $this->submitRating();
        }
        else if (strlen($this->input->post('rating')) !== 0) {
            $this->submitSearchRating();
        }
        else if ($postAction === 'Unsubscribe') {
            $this->unsubscribeAnswer();
        }
        else if ($postAction === 'Subscribe') {
            $this->addSubscription();
        }
        else if ($postAction === 'AddFavorite') {
            $this->addFavorite();
        }
        else if ($postAction === 'RemoveFavorite') {
            $this->removeFavorite();
        }
        else if ($postAction === 'OkcsRecentAnswers') {
            $this->getOkcsRecentAnswers();
        }
        else if ($postAction === 'OkcsRelatedAnswers') {
            $this->getOkcsRelatedAnswers();
        }
        else if ($this->input->post('noOfSuggestions') !== 0) {
            $this->getUpdatedRecentSearches();
        }
    }

    /**
    * Method to add subscription for the IM Answer content.
    */
    public function addSubscription() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $subscriptionData = array(
            'answerID' => $this->input->post('answerID'),
            'documentID' => $this->input->post('docId'),
            'versionID' => $this->input->post('versionID')
        );

        $response = $this->model('Okcs')->addSubscription($subscriptionData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'addSubscription | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if(isset($response->result) && ($response->result === 'OKDOM-USER0019' || $response->result === 'OKDOM-USER0020')) {
            $errorCode = $response->result;
            $response = array();
            $response['result'] = $errorCode;
            $response['failure'] = Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG);
            $this->_renderJSON($response);
        }
        else if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to send subscription schedule.
    */
    public function setSubscriptionSchedule() {
        $response = $this->model('Okcs')->setSubscriptionSchedule($this->input->post('scheduleValue'));
        $this->_renderJSON($response);
    }

    /**
    * Method to add content type subscription.
    */
    public function addContentTypeSubscription() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $subscriptionData = array(
            'name' => $this->input->post('name'),
            'ctRecordId' => $this->input->post('ctRecordId'),
            'productRecordId' => $this->input->post('productRecordId'),
            'categoryRecordId' => $this->input->post('categoryRecordId')
        );
        $response = $this->model('Okcs')->addContentTypeSubscription($subscriptionData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'addContentTypeSubscription | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        if(isset($response->result) && ($response->result === 'OKDOM-USER0019' || $response->result === 'OKDOM-USER0020')) {
            $errorCode = $response->result;
            $response = array();
            $response['result'] = $errorCode;
            $this->_renderJSON($response);
        }
        else if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
        }
        else {
            $subscription = $items = array();
            $subscriptionID = isset($response->recordId) ? $response->recordId : null;
            $dateAdded = isset($response->dateAdded) ? $this->model('Okcs')->processIMDate($response->dateAdded) : null;
            $item = array(
                'name'             => isset($response->name) ? $response->name : null,
                'expires'          => sprintf(Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $dateAdded),
                'subscriptionID'   => $subscriptionID,
                'categories'       => isset($response->categories) ? $response->categories : null,
                'subscriptionType' => isset($response->subscriptionType) ? $response->subscriptionType : null,
                'contentType'      => isset($response->contentType) ? $response->contentType : null
            );
            array_push($items, $item);               
            $subscription['items'] = $items;
            $this->_renderJSON($subscription);
        }
    }
    
    /**
    * Method to create recommended content
    */
    public function createRecommendation() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        if(!$this->checkForValidFormToken($this->input->post('r_tok'))) {
            $this->_renderJSON(array('error' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG)));
            return;
        }
        $recommendationData = array(
            'contentTypeRecordId' => $this->input->post('contentTypeRecordId'),
            'contentTypeReferenceKey' => $this->input->post('contentTypeReferenceKey'),
            'contentTypeName' => $this->input->post('contentTypeName'),
            'caseNumber' => $this->input->post('caseNumber'),
            'comments' => nl2br($this->input->post('comments')),
            'versionID' => $this->input->post('versionID'),
            'title' => $this->input->post('title'),
            'priority' => $this->input->post('priority'),
            'contentRecordId' => $this->input->post('contentRecordId'),
            'answerId' => $this->input->post('answerId'),
            'documentId' => $this->input->post('documentId'),
            'isRecommendChange' => $this->input->post('isRecommendChange')
        );

        $response = $this->model('Okcs')->createRecommendation($recommendationData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'createRecommendation | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
        }
        else {
            $this->_renderJSON(array('success' => Config::getMessage(THANK_YOU_TAKING_MAKE_RECOMMENDATION_LBL)));
        }
    }

    /**
     * Method to create recommend with attachment
     */
    public function createRecommendationWithAttachment() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        if(!$this->checkForValidFormToken($this->input->post('r_tok'))) {
            $this->_renderJSON(array('error' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG)));
            return;
        }

        $recommendationData = array(
            'contentTypeRecordId' => $this->input->post('contentTypeRecordId'),
            'contentTypeReferenceKey' => $this->input->post('contentTypeReferenceKey'),
            'contentTypeName' => $this->input->post('contentTypeName'),
            'caseNumber' => $this->input->post('caseNumber'),
            'comments' => nl2br($this->input->post('comments')),
            'versionID' => $this->input->post('versionID'),
            'title' => $this->input->post('title'),
            'priority' => $this->input->post('priority'),
            'contentRecordId' => $this->input->post('contentRecordId'),
            'answerId' => $this->input->post('answerId'),
            'documentId' => $this->input->post('documentId'),
            'isRecommendChange' => $this->input->post('isRecommendChange'),
        );

        if (isset($_FILES['file'])){
            $file = $_FILES['file'];
            $recommendationData['file'] = $file;
        }

        $response = $this->model('Okcs')->createRecommendation($recommendationData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'createRecommendation | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
        }
        else {
            $this->_renderJSON(array('success' => Config::getMessage(THANK_YOU_TAKING_MAKE_RECOMMENDATION_LBL)));
        }
    }

    /**
    * Method to unsubscribe an answer.
    */
    public function unsubscribeAnswer() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $response = $this->model('Okcs')->unsubscribeAnswer($this->input->post('subscriptionID'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'unsubscribeAnswer | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if(isset($response->result) && $response->result === 'OKDOM-USER0014') {
            $errorCode = $response->result;
            $response = array();
            $response['result'] = $errorCode;
            $this->_renderJSON($response);
        }
        else if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response->result = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch content types
    */
    public function getContentType() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }

        $contentTypeList = trim($this->input->post('contentTypeList'));
        $userPreferredList = explode(",", $contentTypeList);
        $allContentTypes = $this->model('Okcs')->getChannels('v1');

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getContentType | OkcsAjaxRequestController');
            $allContentTypes->ajaxTimings = $timingArray;
        }

        if ($allContentTypes->error !== null) {
            $response = $this->getResponseObject($allContentTypes);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
            return;
        }

        if(empty($contentTypeList)) {
            $validContentTypes = array();
            foreach ($allContentTypes->items as $item) {
                if($item->allowRecommendations) {
                    array_push($validContentTypes, $item);
                }
            }
            $this->_renderJSON($validContentTypes);
        }
        else if ($allContentTypes->items !== null) {
            $validUserPreferredList = array();
            $invalidChannel = array();
            if(is_array($userPreferredList)){
                for($i = 0; $i < count($userPreferredList); $i++) {
                    $isValidChannel = false;
                    foreach ($allContentTypes->items as $item) {
                        if(strtoupper($item->referenceKey) === strtoupper(trim($userPreferredList[$i]))) {
                            $isValidChannel = true;
                            if($item->allowRecommendations) {
                                array_push($validUserPreferredList, $item);
                            }
                            break;
                        }
                    }
                    if(!$isValidChannel)
                        array_push($invalidChannel, $userPreferredList[$i]);
                }
            }
            if(count($invalidChannel) == 0) {
                $this->_renderJSON($validUserPreferredList);
            }
            else {
                $this->_renderJSON(array('failure' => sprintf(Config::getMessage(PCT_S_NOT_FND_FOR_THE_CONTENT_TYPE_LBL), implode(", ", $invalidChannel))));
            }
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to get updated recent searches information.
    */
    public function getUpdatedRecentSearches() {
        $response = $this->model('Okcs')->getUpdatedRecentSearches($this->input->post('noOfSuggestions'));
        $this->_renderJSON($response);
    }

    /**
    * Method to get suggested articles for the associated query
    */
    public function getSuggestions() {
        $applyCtIndexStatus = ($_POST['applyCtIndexStatus'] === null) ? 'true' : $this->input->post('applyCtIndexStatus');
        $productCategory = $this->input->post('productCategory');
        $additionalParameters = array();
        if($productCategory) {
            $additionalParameters['productCategory'] = $productCategory;
            $additionalParameters['matchAllCategories'] = $this->input->post('matchAllCategories');
        }
        $additionalParameters['applyCtIndexStatus'] = $applyCtIndexStatus;
        $response = $this->model('Okcs')->getSuggestions($this->input->post('ssQuery'), $this->input->post('suggestionCount'), $additionalParameters);
        $this->_renderJSON(isset($response->items) ? $response->items : '');
    }

    /**
    * Method to sort subscriptions based on the docId
    */
    public function sortNotifications() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $subscriptionList = $this->model('Okcs')->sortNotifications($this->input->post('sortColumn'), $this->input->post('direction'));
        if(IS_DEVELOPMENT){
            $subscriptionList->ajaxTimings = $this->calculateTimeDifference($startTime, 'sortNotifications | OkcsAjaxRequestController');
        }
        if($subscriptionList->errors) {
            $list = $this->getResponseObject($subscriptionList);
            $list['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        else {
            $titleLength = $this->input->post('titleLength') ?: 0;
            $maxRecords = $this->input->post('maxRecords');
            $list = array();
            if ($subscriptionList && !$subscriptionList->error && isset($subscriptionList->items) && is_array($subscriptionList->items) && count($subscriptionList->items) > 0) {
                if($maxRecords > 0) {
                    $subscriptions = array_slice($subscriptionList->items, 0, $maxRecords);
                    foreach ($subscriptions as $document) {
                        if($document->subscriptionType === 'SUBSCRIPTIONTYPE_CONTENT' && $document->content !== null) {
                            $subscriptionID = $document->recordId;
                            $dateAdded = $this->model('Okcs')->processIMDate($document->dateAdded);
                            $document = $document->content;
                            $document->title = Text::escapeHtml($document->title);
                            $item = array(
                                'documentId'        => $document->documentId,
                                'answerId'          => $document->answerId,
                                'title'             => $titleLength === 0 ? $document->title : Text::truncateText($document->title, $titleLength),
                                'expires'           => $dateAdded,
                                'subscriptionID'    => $subscriptionID
                            );
                            array_push($list, $item);
                        }
                    }
                }
            }
        }
        $this->_renderJSON($list);
    }

    /**
    * Method to call clickthru OKCS API.
    */
    private function clickThru() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $clickThruLink = $this->model('Okcs')->decodeAndDecryptData($this->input->post('clickThruLink'));
        $clickThroughInput = array(
           'answerId' => $this->getUrlParameter($clickThruLink, 'answerId'),
           'searchSession' => $this->getUrlParameter($clickThruLink, 'searchSession'),
           'prTxnId' => $this->getUrlParameter($clickThruLink, 'priorTransactionId'),
           'txnId' => $this->getUrlParameter($clickThruLink, 'txn') ,
           'ansType' => $this->input->post('answerType'));
        $result = $this->model('Okcs')->getHighlightedHTML($clickThroughInput);

        if(IS_DEVELOPMENT){
            $stopTime = microtime(true);
            $duration = $stopTime - $startTime;
            $timingArray = Okcs::getCachedTimings('timingCacheKey');
            array_push($timingArray, array('key' => 'clickThru | OkcsAjaxRequestController', 'value' => $duration));

            $result['ajaxTimings'] = $timingArray;
        }
        echo json_encode($result);
    }

    /**
    * Method to get Recommendations View
    */
    public function recommendationsView() {
        $recommendationsView = $this->model('Okcs')->getRecommendationsView($this->input->post('recordId'));
        if(isset($recommendationsView->errors) && $recommendationsView->errors) {
            $recommendationsView = $this->getResponseObject($recommendationsView);
            $recommendationsView['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        else {
            $recommendationsView->dateAdded = $this->model('Okcs')->processIMDate($recommendationsView->dateAdded);
        }
        $this->_renderJSON($recommendationsView);
    }

    /**
    * Method to get recently viewed OKCS answers
    */
    public function getOkcsRecentAnswers() {
        $widgetContentCount = $this->input->post('contentCount');
        $response = $this->model('Okcs')->getOkcsRecentAnswers($widgetContentCount);
        $this->_renderJSON($response);
    }

    /**
    * Method to get related OKCS answers
    */
    public function getOkcsRelatedAnswers() {
        $widgetContentCount = $this->input->post('contentCount');
        $answerId = $this->input->post('answerId');
        $displayLinkType = $this->input->post('displayLinkType');
        $response = $this->model('Okcs')->getRelatedAnswers($answerId, $widgetContentCount, $displayLinkType);
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch all articles from OKCS API.
    */
    public function fetchForInternalPagination() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $filter = array(
            'type'                  => $this->input->post('type'),
            'limit'                 => $this->input->post('limit'),
            'contentType'           => $this->input->post('contentType'),
            'category'              => $this->input->post('category'),
            'pageNumber'            => $this->input->post('pageNumber'),
            'pageSize'              => $this->input->post('pageSize'),
            'truncate'              => $this->input->post('truncate'),
            'sortColumnId'          => $this->input->post('sortColumnId'),
            'sortDirection'         => $this->input->post('sortDirection'),
            'status'                => $this->input->post('showDraft'),
            'answerListApiVersion'  => $this->input->post('answerListApiVersion')
        );
        
        $response = $articleResult = $this->model('Okcs')->getArticlesSortedBy($filter);
        $filters = array();

        $columnID = isset($filters['sortColumn']['value']) ? $filters['sortColumn']['value'] : "publishDate";
        $sortDirection = isset($filters['sortDirection']['value']) ? $filters['sortDirection']['value'] : "DESC";
        $contentType = isset($filters['channelRecordID']['value']) ? $filters['channelRecordID']['value'] : '';
        $browsePage = isset($filters['browsePage']['value']) ? $filters['browsePage']['value'] : 0;
        $response = array(
            'error'           => (isset($articleResult->errors) && $articleResult->errors) ? $articleResult->error->errorCode . ': ' .
                                 $articleResult->error->externalMessage : null,
            'articles'        => $articleResult->items,
            'filters'         => '',
            'columnID'        => $columnID,
            'sortDirection'   => $sortDirection,
            'selectedChannel' => $contentType,
            'hasMore'         => $articleResult->hasMore,
            'currentPage'     => $browsePage,
            'isRecommendationAllowed' => isset($filters['isRecommendationAllowed']['value']) ? $filters['isRecommendationAllowed']['value'] : false
        );

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'fetchForInternalPagination | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to call browseArticles OKCS API.
    */
    private function browseArticles() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $filters = json_decode($this->input->post('filters'), true);
        $contentType = $filters['channelRecordID']['value'] !== null ? $filters['channelRecordID']['value'] : '';
        $currentSelectedID = isset($filters['currentSelectedID']) && isset($filters['currentSelectedID']['value']) ? $filters['currentSelectedID']['value'] : null;
        $productRecordID = isset($filters['productRecordID']) && isset($filters['productRecordID']['value']) ? $filters['productRecordID']['value'] : null;
        $categoryRecordID = isset($filters['categoryRecordID']) && isset($filters['categoryRecordID']['value']) ? $filters['categoryRecordID']['value'] : null;
        $isProductSelected = isset($filters['isProductSelected']) && isset($filters['isProductSelected']['value']) ? $filters['isProductSelected']['value'] : null;
        $isCategorySelected = isset($filters['isCategorySelected']) && isset($filters['isCategorySelected']['value']) ? $filters['isCategorySelected']['value'] : null;
        $categoryFetchFlag = $isProductSelected !== null || $isCategorySelected !== null ? false : true;
        $browsePage = isset($filters['browsePage']) && isset($filters['browsePage']['value']) && $filters['browsePage']['value'] !== null ? $filters['browsePage']['value'] : 0;
        $pageSize = $filters['pageSize']['value'] !== null ? $filters['pageSize']['value'] : 10;
        $limit = isset($filters['limit']) && isset($filters['limit']['value']) ? $filters['limit']['value'] : null;
        $columnID = isset($filters['sortColumn']) && isset($filters['sortColumn']['value']) && $filters['sortColumn']['value'] !== null ? $filters['sortColumn']['value'] : "publishDate";
        $sortDirection = isset($filters['sortDirection']['value']) ? $filters['sortDirection']['value'] : "DESC";
        $answerListApiVersion = $this->input->post('answerListApiVersion');
        $productCategoryApiVersion = $this->input->post('productCategoryApiVersion');

        if($productRecordID === null)
            $isProductSelected = null;

        if($categoryRecordID === null)
            $isCategorySelected = null;

        $isSelected = $currentSelectedID === $productRecordID ? $isProductSelected : $isCategorySelected;
        $category = null;
        if($isProductSelected)
            $category = $productRecordID;

        if ($isCategorySelected) {
            if($category !== null) {
                $category .= ':' . $categoryRecordID;
            }
            else {
                $category = $categoryRecordID;
            }
        }

        $filter = array(
            'type'             => $filters['type']['value'],
            'status'           => $filters['a_status']['value'],
            'truncate'         => $filters['truncate']['value'],
            'limit'            => $limit,
            'contentType'      => $contentType,
            'category'         => isset($category) ? $category : null,
            'pageNumber'       => $browsePage,
            'pageSize'         => $pageSize,
            'sortColumnId'     => $columnID,
            'sortDirection'    => $sortDirection,
            'categoryRecordID' => $categoryRecordID,
            'productRecordID'  => $productRecordID,
            'answerListApiVersion' => $answerListApiVersion,
            'productCategoryAnsList' => isset($filters['productCategoryAnsList']) && isset($filters['productCategoryAnsList']['value']) ? $filters['productCategoryAnsList']['value'] : null,
            'contentTypeAnsList' => isset($filters['contentTypeAnsList']) && isset($filters['contentTypeAnsList']['value']) ? $filters['contentTypeAnsList']['value'] : null
        );
        $articleResult = $this->model('Okcs')->getArticlesSortedBy($filter);
        $response = array(
            'error'           => isset($articleResult->errors) ? $articleResult->error->errorCode . ': ' .
                                 $articleResult->error->externalMessage : null,
            'articles'        => $articleResult->items,
            'filters'         => '',
            'columnID'        => $columnID,
            'sortDirection'   => $sortDirection,
            'selectedChannel' => $contentType,
            'hasMore'         => $articleResult->hasMore,
            'currentPage'     => $browsePage,
            'isRecommendationAllowed' => $filters['isRecommendationAllowed']['value']
        );

        if (isset($category) && strlen($category) === 0 && strlen($currentSelectedID) === 0 && $categoryFetchFlag){
            $response["category"] = $this->model('Okcs')->getChannelCategories($contentType, $productCategoryApiVersion);
        }
        else {
            $response["categoryRecordID"] = $currentSelectedID;
        }

        if($isSelected)
            $response["isCategorySelected"] = $isSelected;

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'browseArticles | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
     * Method to call getRecommendationsSortedBy OKCS API.
     * Retrieves recommendations based on the specified filter parameters for a logged-in user.
     */
    private function browseRecommendations() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $filters = json_decode($this->input->post('filters'), true);
        $browsePage = isset($filters['browsePage']['value']) ? $filters['browsePage']['value'] : 0;
        $pageSize = isset($filters['pageSize']['value']) ? $filters['pageSize']['value'] : 10;
        $offSet = (isset($filters['pageSize']['value']) && isset($filters['browsePage']['value'])) ? $filters['pageSize']['value'] * ($filters['browsePage']['value'] - 1) : 0;
        $sortColumn = $filters['sortColumn']['value'] !== null ? $filters['sortColumn']['value'] : "dateAdded";
        $sortDirection = $filters['sortDirection']['value'] !== null ? $filters['sortDirection']['value'] : "DESC";
        $manageRecommendationsApiVersion = $this->input->post('manageRecommendationsApiVersion');
        $filter = array(
            'type'             => '',
            'offSet'           => $offSet,
            'pageNumber'       => $browsePage,
            'pageSize'         => $pageSize,
            'sortColumnId'     => $sortColumn,
            'sortDirection'    => $sortDirection,
            'manageRecommendationsApiVersion' => $manageRecommendationsApiVersion
        );
        $recommendationsResult = $this->model('Okcs')->getRecommendationsSortedBy($filter);
        if(isset($recommendationsResult->errors) && $recommendationsResult->errors) {
            $response = $this->getResponseObject($recommendationsResult);
        }
        else {
            $response = array(
                'recommendations' => $recommendationsResult->items,
                'filters'         => '',
                'columnID'        => $sortColumn,
                'sortDirection'   => $sortDirection,
                'hasMore'         => $recommendationsResult->hasMore,
                'currentPage'     => $browsePage
            );
        }
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'browseRecommendations | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch details of an OKCS IM content
    */
    private function getIMContent() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $docID = $this->input->post('doc_id');
        $highlightedLink = $this->model('Okcs')->decodeAndDecryptData($this->input->post('highlightedLink'));
        $searchData = array('answerId' => $this->getUrlParameter($highlightedLink, 'answerId'), 'searchSession' => $this->getUrlParameter($highlightedLink, 'searchSession'), 'prTxnId' => $this->getUrlParameter($highlightedLink, 'priorTransactionId'), 'txnId' => $this->getUrlParameter($highlightedLink, 'txn'));
        $answerType = $this->input->post('answerType');
        $isAggRat = $this->input->post('isAggRat');
        
        //If highlighting is enabled
        if ($answerType === 'CMS-XML') {
            if (strlen($highlightedLink) !== 0) {
                $response = $this->model('Okcs')->getAnswerViewData($docID, null, $searchData, '', 'v1');
            }
            else {
                $response = $this->model('Okcs')->getAnswerViewData($docID);
            }
        }
        else {
            if (strlen($highlightedLink) !== 0) {
                $response = $this->model('Okcs')->processIMContent($docID, 'v1', $searchData, $answerType);
            } 
            else {
                $response = $this->model('Okcs')->processIMContent($docID);
            }
        }
        if($isAggRat){
            $aggregateRating = $this->model('Okcs')->getAggregateRating($response['contentRecordID']);
            $response['questionsCount'] = isset($aggregateRating->questions[0]) && is_array($aggregateRating->questions[0]) ? count($aggregateRating->questions[0]) : 0;
            $response['aggregateRating'] = isset($aggregateRating->questions[0]->averageResponse) ? $aggregateRating->questions[0]->averageResponse : null;
            $response['answersCount'] = isset($aggregateRating->questions[0]->answers) && is_array($aggregateRating->questions[0]->answers) ? count($aggregateRating->questions[0]->answers) : 0;
        }
        if ($answerType !== 'HTML' && isset($response['content'])) {
            $contentTypeSchema = $this->model('Okcs')->getIMContentSchema($response['contentType']->referenceKey, $response['locale']->recordID, 'v1');
            if ($contentTypeSchema->error === null) {
                $okcs = new \RightNow\Utils\Okcs();
                $channelData = $okcs->getAnswerView($response['content'], $contentTypeSchema['contentSchema'], "CHANNEL", $response['resourcePath']);
                $response['content'] = $channelData;
                if($contentTypeSchema['metaSchema'] !== null) {
                    $metaData = $okcs->getAnswerView($response['metaContent'], $contentTypeSchema['metaSchema'], "META", $response['resourcePath']);
                    $response['metaContent'] = $metaData;
                }
            }
            else {
                return false;
            }
        }
        $timingArray = null;
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getIMContent | OkcsAjaxRequestController');
        }
        $this->_renderJSON(array(
            'error' => isset($response->errors) && $response->errors ? (string) $response->error : null,
            'id' => $docID,
            'contents' => $response,
            'ajaxTimings' => $timingArray
        ));
    }

    /**
    * Method to call getContactDeflectionResponse OKCS API.
    */
    private function getContactDeflectionResponse() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getContactDeflectionResponse($this->input->post('priorTransactionID'), $this->input->post('deflected'), $this->input->post('okcsSearchSession'));
        if(IS_DEVELOPMENT){
            $response->ajaxTimings = $this->calculateTimeDifference($startTime, 'getContactDeflectionResponse | OkcsAjaxRequestController');
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to call getChannelCategories OKCS API to pull more categories for the same channel based  on externalType. This method should not be timed as it will cache results that it gets from API.
    */
    public function getChannelCategories() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response["categories"] = $this->model('Okcs')->getChannelCategories($this->input->post('channelRecordID'), $this->input->post('productCategoryApiVersion'), $this->input->post('offset'), $this->input->post('type'));
        if(isset($response->items))
            $this->_renderJSON($response);
        else
            $this->_renderJSON($this->getResponseObject($response));
    }
    
    /**
    * Method to call getChannelCategories OKCS API to pull more categories for the same channel. This method should not be timed as it will cache results that it gets from API.
    */
    private function getMoreProdCat() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getChannelCategories($this->input->post('contentType'), $this->input->post('productCategoryApiVersion'), $this->input->post('offset'));
        if($response->items !== null)
            $this->_renderJSON($response);
        else
            $this->_renderJSON($this->getResponseObject($response));
    }

    /**
    * Method to call getChildCategories OKCS API to pull children of a parent category.
    */
    private function getChildCategories() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getChildCategories($this->input->post('categoryId'), $this->input->post('limit'), $this->input->post('offset'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getChildCategories | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        if($response->items !== null)
            $this->_renderJSON($response);
        else
            $this->_renderJSON($this->getResponseObject($response));
    }

    /**
    * Method to submit Info Manager document rating.
    */
    private function submitRating() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $ratingData = array(
            'answerID' => $this->input->post('answerID'),
            'surveyRecordID' => $this->input->post('surveyRecordID'),
            'answerRecordID' => $this->input->post('answerRecordID'),
            'contentRecordID' => $this->input->post('contentRecordID'),
            'localeRecordID' => $this->input->post('localeRecordID'),
            'ratingPercentage' => $this->input->post('ratingPercentage'),
            'answerComment' => $this->input->post('answerComment')
        );
        $response = $this->model('Okcs')->submitRating($ratingData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'submitRating | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to submit search rating.
    */
    private function submitSearchRating() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->submitSearchRating($this->input->post('rating'), $this->input->post('feedback'), $this->input->post('priorTransactionID'), $this->input->post('okcsSearchSession'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'submitSearchRating | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to record search answer clickthru.
    */
    public function recordClickThru() {
        $clickThruLink = $this->input->post('clickThruLink');
        $clickThruLink = Text::getSubstringBefore($clickThruLink, '#');
        if (Text::stringContains($clickThruLink, '/ci/okcsFile')) {
            $clickThruLink = Text::getSubstringAfter($clickThruLink, '/get/');
            $clickThruData = explode('/', $clickThruLink);
            $answerId = $clickThruData[0];
            $searchSession = $clickThruData[1];
            $prTxnId = $clickThruData[2];
            $ansType = $clickThruData[3];
            $txnId = $clickThruData[4];
        } else if (Text::stringContains($clickThruLink, '/ci/okcsFattach')) {
            $clickThruLink = Text::getSubstringAfter($clickThruLink, '/file/');
            $clickThruData = explode('/', $clickThruLink);
            $ansType = $clickThruData[1];
            $searchSession = $clickThruData[2];
            $txnId = $prTxnId = $clickThruData[3];
            $answerId = $clickThruData[4];
        } else {
            $answerData = $this->getUrlParameter($clickThruLink, 's');
            $answerId = Text::getSubstringBefore($answerData, '_');
            $searchSession = Text::getSubstringAfter($answerData, '_');
            $prTxnId = $this->getUrlParameter($clickThruLink, 'prTxnId');
            $txnId = $this->getUrlParameter($clickThruLink, 'txnId');
            $ansType = null;
            $accessType = $this->getUrlParameter($clickThruLink, 'accessType');
        }
        $clickThroughInput = array(
           'answerId' => $answerId,
           'searchSession' => $searchSession,
           'prTxnId' => $prTxnId,
           'txnId' => $txnId,
           'ansType' => $ansType
        );
        if(!is_null($accessType)){
            $clickThroughInput['accessType'] = $accessType;
        }
        $result = $this->model('Okcs')->getHighlightedHTML($clickThroughInput);
        $this->_renderJSON($result);
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
    * Method to calculate time difference.
    * @param string $startTime StartTime
    * @param string $value TimingArrayKey
    * @return array  timing array
    */
    private function calculateTimeDifference($startTime, $value) {
        $stopTime = microtime(true);
        $duration = $stopTime - $startTime;
        $timingArray = Okcs::getCachedTimings('timingCacheKey');
        if($timingArray === null) {
            Okcs::setTimingToCache('timingCacheKey', array(array('key' => $value, 'value' => $duration)));
            $timingArray = Okcs::getCachedTimings('timingCacheKey');
        }
        else
            array_push($timingArray, array('key' => $value, 'value' => $duration));

        return $timingArray;
    }

    /**
    * Method to get formatted Response Object to display errors in AJAX responses
    * @param object $response Response object from the model layer which needs to be formatted
    * @return array Formatted error response
    */
    private function getResponseObject($response){
        if(isset($response->errors) && $response->errors){
            $responseObject = array();
            if($response->ajaxTimings){
                $responseObject['ajaxTimings'] = $response->ajaxTimings;
            }
            $responseObject['isResponseObject'] = true;
            $responseObject['result'] = false;
            $responseObject['errors'] = array();
            foreach($response->errors as $error){
                $externalMessage = $this->model('Okcs')->formatErrorMessage($error);
                $displayToUser = IS_DEVELOPMENT ? true : false;
                array_push($responseObject['errors'], array('externalMessage' => $externalMessage, 'displayToUser' => $displayToUser));
            }
            return $responseObject;
        }
        return $response;
    }

    /**
    * Method to send Email for an OKCS answer
    */
    public function sendOkcsEmailAnswerLink() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        // Populate all email related labels into an array to be sent to the model layer
        $emailData = array(
            'sendTo' => $this->input->post('to'),
            'name' => $this->input->post('name'),
            'from' => $this->input->post('from'),
            'answerID' => $this->input->post('aId'),
            'title' => $this->input->post('title'),
            'emailHeaderLabel' => $this->input->post('emailHeader'),
            'emailSenderLabel' => $this->input->post('emailSender'),
            'summaryLabel' => $this->input->post('summaryLabel'),
            'answerViewLabel' => $this->input->post('answerViewLabel'),
            'emailAnswerToken' => $this->input->post('emailAnswerToken'),
            'databaseEmailTemplate' => $this->input->post('databaseEmailTemplate')
        );
        $response = $this->model('Okcs')->emailOkcsAnswerLink($emailData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'sendOkcsEmailAnswerLink | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        $this->_renderJSON($response);
    }
    
    /**
    * Method to get more Answer Notifications based on offset.
    */
    public function getMoreAnswerNotif() {
        $response = $this->model('Okcs')->getPaginatedSubscriptionList($this->input->post('offset'));
        $this->_renderJSON($response);
    }

    /**
    * Method to get more Content Type Notifications based on offset.
    */
    public function getMoreContentTypeNotif() {
        $subscriptionList = $this->model('Okcs')->getContentTypeSubscriptionList($this->input->post('offset'));
        $response = array();
        $items = array();
        if (!is_null($subscriptionList) && !isset($subscriptionList->error) && isset($subscriptionList->items) && is_array($subscriptionList->items) && count($subscriptionList->items) > 0) {
            foreach ($subscriptionList->items as $subscription) {
                if($subscription->subscriptionType === 'SUBSCRIPTIONTYPE_CHANNEL') {
                    $subscriptionID = $subscription->recordId;
                    $dateAdded = $this->model('Okcs')->processIMDate($subscription->dateAdded);
                    $item = array(
                        'name'             => $subscription->name,
                        'expires'           => sprintf(Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $dateAdded),
                        'subscriptionID'    => $subscriptionID
                    );
                    array_push($items, $item);
                }
            }
            $response['items'] = $items;
            $response['hasMore'] = $subscriptionList->hasMore;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch all translations for a content based on answerId.
    */
    public function getAllTranslations() {
        $response = $this->model('Okcs')->getAllTranslations($this->input->post('answerId'));
        $this->_renderJSON($response);
    }
    
    /**
    * Method to fetch all locale descriptions associated with the interface.
    */
    public function getAllLocaleDescriptions() {
        $response = $this->model('Okcs')->getAllLocaleDescriptions('v1');
        $this->_renderJSON($response);
    }

    /**
    * Method to add an answer as a favorite.
    */
    public function addFavorite() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $response = $this->model('Okcs')->addFavorite($this->input->post('answerID'));

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'addFavorite | OkcsAjaxRequestController');
            if(isset($response) && $response !== null){
                $response->ajaxTimings = $timingArray;
            }
        }
        // Error scenario for maxLength exceeded
        if(isset($response) && isset($response->result) && ($response->result === 'OK-GEN0004')) {
            $errorCode = $response->result;
            $response = array();
            $response['result'] = $errorCode;
            $this->_renderJSON($response);
        }
        // Error scenario for adding same answer as favorite more than once
        if(isset($response) && isset($response->errors) && $response->errors && $response->errors[0]->errorCode === 'OKDOM-USRFAV01') {
            $errorObject = $response->errors;
            $response = array();
            $response['result'] = $errorObject;
        }
        else if(isset($response) && isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to remove an answer from the favorite list.
    */
    public function removeFavorite() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $response = $this->model('Okcs')->removeFavorite($this->input->post('answerID'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'removeFavorite | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        // Error scenario for deleting same answer as favorite more than once
        if(isset($response->errors) && $response->errors && $response->errors[0]->errorCode === 'OKDOM-USRFAV02') {
            $errorObject = $response->errors;
            $response = array();
            $response['result'] = $errorObject;
        }
        else if(isset($response->errors) && $response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to get paginated favorite answer details.
    */
    public function getFavoriteAnswers() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $favString = $this->input->post('getMoreFavAnswers');
        $titleLength = $this->input->post('titleLength');
        if($favString === 'getMoreFavTabAnswers') {
            $favIds = $this->input->post('favIds');
            $favoriteIdArr = array_unique(explode(",", $favIds));
        }
        else {
            $offset = $this->input->post('offset');
            $userFavoritesList = $this->input->post('userFavoritesList');
            $favoritesList = array_unique(explode(",", $userFavoritesList));
            $favoriteIdArr = count($favoritesList) > $offset + 20 ? array_splice($favoritesList, $offset, 20) : array_splice($favoritesList, $offset, count($favoritesList) - $offset);
            $favIds = implode(",", $favoriteIdArr);
        }
        $response = $this->model('Okcs')->getDetailsForAnswerId($favoriteIdArr);
        if($response === null)
            $response = $this->getResponseObject($response);
            $response['favIds'] = $favIds;

        foreach ($favoriteIdArr as $favoriteId) {
            $origTitle = $response[$favoriteId]['title'];
            $truncatedTitle = !($titleLength) ? $origTitle : Text::truncateText($origTitle, $titleLength);
            $response[$favoriteId]['title'] = $truncatedTitle;
        }
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getFavoriteAnswers | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
     * Verifies the validity of the input token
     * @param string $formToken Form Token generated i nthe widget controller
     * @return Boolean Whether the form token exists and is valid
     */
    private function checkForValidFormToken($formToken) {
        return count($_POST) && $formToken && Framework::isValidSecurityToken($formToken, 0);
    }
}
