<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Api,
    RightNow\Internal\OkcsApi,
    RightNow\Utils\Config,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Text;

class InternalOkcsApiTest extends CPTestCase
{
    public $testingClass = 'RightNow\compatibility\Internal\OkcsApi';
    function __construct() {
        parent::__construct();
        $this->api = new RightNow\compatibility\Internal\OkcsApi();
        $this->CI = get_instance();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
    }

    function __destruct() {
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    function testGetRelatedAnswers(){
        $getRelatedAnswers = $this->getMethod('getRelatedAnswers');
        $response = $getRelatedAnswers('48');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $results = $response->items;
        $this->assertTrue(is_array(json_decode($results[0]->body)->items));
    }

    function testGetArticlesSortedBy() {
        $filter = array(
            'type' => 'popular',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $getArticlesSortedBy = $this->getMethod('getArticlesSortedBy');
        $response = $getArticlesSortedBy($filter, 'en_US');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
    }

    function testGetRecommendationsSortedBy() {
        $filter = array(
            'pageNumber' => 1,
            'pageSize' => 10,
            'manageRecommendationsApiVersion' => 'v1',
            'truncate' => 200
        );
        $getRecommendationsSortedBy = $this->getMethod('getRecommendationsSortedBy');
        $response = $getRecommendationsSortedBy('01203607b751e7d0150b87ec0c8007e7d', $filter, 'en_US');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
    }

    function testGetChannels() {
        $response = $this->api->getChannels('v1');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->assertSame(13, count($results));

        foreach ($results as $item) {
            $this->assertNotNull($item->referenceKey);
            $this->assertIsA($item->referenceKey, 'string');
            $this->assertNotNull($item->name);
            $this->assertIsA($item->recordId, 'string');
            $this->assertNotNull($item->recordId);
            $this->assertIsA($item->recordId, 'string');
        }
    }

    function testGetDocumentRating(){
        $getChannelDetails = $this->getMethod('getDocumentRating');
        $response = $this->api->getChannelDetails('02014703cab4f06014c6f65dcf2007fac','v1');
    }

    function testGetIMContentSchema() {
        $contentTypeId = $this->api->getChannels('v1')->items[0]->recordId;
        $response = $this->api->getIMContentSchema($contentTypeId, 'en_US','v1');
        $contentSchemaArray = $response->contentSchema->schemaAttributes;

        if(!is_null($contentSchemaArray)) {
            $this->assertTrue(is_array($contentSchemaArray));
            foreach ($contentSchemaArray as $contentSchemaItem) {
                $this->assertNotNull($contentSchemaItem->recordId);
                $this->assertNotNull($contentSchemaItem->name);
                $this->assertNotNull($contentSchemaItem->dateAdded);
                $this->assertNotNull($contentSchemaItem->dateModified);
                $this->assertNotNull($contentSchemaItem->referenceKey);
            }
        }
    }
    
    function testGetSupportedLanguages() {
        $getSupportedLanguages = $this->getMethod('getSupportedLanguages');
        $response = $this->api->getSupportedLanguages('v1');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $results = $response->items;
        $this->assertTrue(is_array($results));
        $this->assertSame(4, count($results));
        foreach ($results as $item) {
            $this->assertNotNull($item->active);
            $this->assertNotNull($item->localeCode);
            $this->assertNotNull($item->localeDesc);
            $this->assertNotNull($item->localeValue);
        }
    }

    function testGetUserLocale() {
        $getUserLocale = $this->getMethod('getUserLocale');
        $response = $getUserLocale('slatest')->defaultLocale->recordId;
        $this->assertNotNull($response);
        $this->assertIdentical($response, 'en_US');
    }

    function testGetSearchResultDataWithValidData() {
        $getSearchResultData = $this->getMethod('getSearchResult');
        $filter = array(
            'searchText' => 'Windows',
            'searchSession' => '',
            'transactionId' => 1,
            'localeCode' => 'en-US',
            'resultLocale' => 'en-US',
            'requestType' => 'SEARCH'
        );
        $response = $getSearchResultData($filter);
        $facetItems = $response->results->facets;
        $this->assertSame(4, count($facetItems));
        if(!is_null($facetItems)) {
            foreach ($facetItems as $item) {
                $this->assertSame(1, count($item->id));
                $this->assertSame(1, count($item->desc));
                $this->assertIsA($item->children,'Array');
            }
        }
    }
    
    function testGetAnswersForSelectedFacetWithValidData() {
        $getSearchResultData = $this->getMethod('getSearchResult');
        $filter = array(
            'searchText' => 'Windows',
            'searchSession' => '',
            'transactionId' => 1,
            'localeCode' => 'en-US',
            'resultLocale' => 'en-US',
            'requestType' => 'SEARCH'
        );
        $response = $getSearchResultData($filter);
        $result = $response->results;
        $facetFilter = array(
            'session' => $response->session,
            'transactionID' => $response->transactionId + 1,
            'priorTransactionID' => $response->transactionId,
            'facet' => 'DOC_TYPES.CMS-XML',
            'resultLocale' => 'en-US'
        );
        $getAnswersForSelectedFacet = $this->getMethod('getAnswersForSelectedFacet');
        $facetResponse = $getAnswersForSelectedFacet($facetFilter);
        $response = $facetResponse->results;
        $resultItems = $response->results[0]->resultItems;
        $this->verifySearchResultArray($resultItems);
    }
    
    function testGetSearchPage() {
        $getSearchResultData = $this->getMethod('getSearchResult');
        $filter = array(
            'searchText' => 'Windows',
            'searchSession' => '',
            'transactionId' => 1,
            'localeCode' => 'en-US',
            'resultLocale' => 'en-US',
            'requestType' => 'SEARCH'
        );
        $response = $getSearchResultData($filter);
        $result = $response->results;
        $pageFilter = array(
            'session' => $response->session,
            'priorTransactionID' => 1,
            'page' => 0,
            'pageDirection' => 'next',
            'resultLocale' => 'en-US'
        );
        $getSearchPage = $this->getMethod('getSearchPage');
        $searchPageResponse = $getSearchPage($pageFilter);
        $paginationResponse = $searchPageResponse->results;
        $resultItems = $paginationResponse->results[0]->resultItems;
        $this->verifySearchResultArray($resultItems);
    }
    
    function testGetUserRecordId() {
        $getUserRecordId = $this->getMethod('getUserRecordId');
        $response = $getUserRecordId('slatest');
        $this->assertNotNull($response->recordId);
    }
    
    function testGetUserLanguagePreferences() {
        $getUserLanguagePreferences = $this->getMethod('getUserLanguagePreferences');
        $getUserRecordId = $this->getMethod('getUserRecordId');
        $response = $getUserLanguagePreferences($getUserRecordId('test')->recordId);
        $this->assertNull($response->error);
    }
    
    function testGetChannelCategories() {
        $getChannelCategories = $this->getMethod('getChannelCategories');
        $category = $getChannelCategories(null, 'v1')->items[0];
        $this->assertNotNull($category->referenceKey);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->externalType);
    }
    
    function testSubmitSearchRating() {
        $getSearchResultData = $this->getMethod('getSearchResult');
        $filter = array(
            'searchText' => 'windows',
            'searchSession' => '',
            'transactionId' => 1,
            'localeCode' => 'en-US',
            'resultLocale' => 'en-US',
            'requestType' => 'SEARCH'
        );
        $response = $getSearchResultData($filter);
        $submitSearchRating = $this->getMethod('submitSearchRating');
        $response = $submitSearchRating('1', 'Search%20dwas%20dhelpful', '1', $response->session);
        $this->assertNull($response);
        $this->assertSame(0, count($response->errors));
    }
    
    function testGetArticle(){
        $getArticle = $this->getMethod('getArticle');
        $response = $getArticle($this->getDocument(), 'PUBLISHED', 'v1');
        $this->assertNotNull($response);
        
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->xml);
        $this->assertNotNull($response->locale);
    }

    function testGetArticleWithNoRecordContentViewEvent(){
        $getArticle = $this->getMethod('getArticle');
        // This test case verifies the flow for fetching the document when recordContentViewEvent is set to false.
        // The database entry not being populated needs to be verified manually.
        $response = $getArticle($this->getDocument(), 'PUBLISHED', 'v1', false, false);
        $this->assertNotNull($response);
        
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->xml);
        $this->assertNotNull($response->locale);
    }

    function getDocument() {
        if(!is_null($this->documentId) && !empty($this->documentId)) {
            $documentId = $this->documentId;
        } 
        else {
            $filter = array(
            'type' => 'popular',
            'limit' => 0,
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20,
            'answerListApiVersion' => 'v1'
            );
            $getArticlesSortedBy = $this->getMethod('getArticlesSortedBy');
            $response = $getArticlesSortedBy($filter, 'en_US');
            if(count($response->items) > 0)
                $documentId = $response->items[0]->answerId;
            $this->documentId = $documentId;
        }
        return $documentId;
    }

    function verifySearchResultArray($resultItems) {
        $this->assertTrue(count($resultItems) > 0);
        if(!is_null($resultItems)) {
            foreach ($resultItems as $item) {
                $this->assertNotNull($item->fileType);
                $this->assertNotNull($item->answerId);
                $this->assertNotNull($item->docId);
                $this->assertNotNull($item->title);
                $this->assertNotNull($item->clickThroughLink);
                $this->assertNotNull($item->similarResponseLink);
                $this->assertNotNull($item->highlightedLink);
            }
        }
    }

    function testGetIntegrationUserToken(){
        $getIntegrationUserToken = $this->getMethod('getIntegrationUserToken');
        $response = $getIntegrationUserToken('iu_customer_portal', 'password');
        $this->assertNotNull($response);
    }

    function testGetAttachment(){
        $getAttachment = $this->getMethod('getAttachment');
        $data = $getAttachment('http://slc01fjo.us.oracle.com:8226/InfoManager/file/resources//okcs__ok152b1__t2/content/draft/082020274001257014a60bef54d007f8f/082020274001257014a60bef54d007f8e/pdf-test.pdf');
        $this->assertNotNull($data);
    }

    function testGetArticlesForSiteMap() {
        $getArticlesSortedBy = $this->getMethod('getArticlesForSiteMap');
        $response = $getArticlesSortedBy(1, 2);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
    }

    function testGetWithWrongKey() {
        $getFunction = $this->getMethod('get');
        $response = $getFunction('xyz');
        $this->assertNull($response);
    }

    function testGetWithNoFile() {
        $getFunction = $this->getMethod('get');
        $response = $getFunction('siteName');
        $this->assertNull($response);
    }

    function testSubscriptionList() {
        $getSubscriptionList = $this->getMethod('getSubscriptionList');
        $response = $getSubscriptionList('xyz');
        $this->assertNotNull($response);
        $this->assertSame('SUBSCRIPTIONTYPE_CONTENT', $response->items[2]->subscriptionType);
        $this->assertSame('6087E38D611D4DF78922EACF3B817A12', $response->items[2]->recordId);
    }

    function testContentTypeSubscriptionList() {
        $getSubscriptionList = $this->getMethod('getSubscriptionList');
        $response = $getSubscriptionList('xyz');
        $this->assertNotNull($response);
        $this->assertSame('SUBSCRIPTIONTYPE_CHANNEL', $response->items[0]->subscriptionType);
        $this->assertSame('2DA44EEF8A004B80BB4DCF5903520649', $response->items[0]->recordId);
    }

    function unsubscribeInvalidAnswer() {
        $unsubscribeAnswer = $this->getMethod('unsubscribeAnswer');
        $response = $unsubscribeAnswer('xyz', 'abc');
        $this->assertNotNull($response->errors);
    }

    function testGetSortedSubscriptionList() {
        $getSortedSubscriptionList = $this->getMethod('getSortedSubscriptionList');
        $response = $getSortedSubscriptionList('xyz', 'documentId', 'desc');
        $this->assertNotNull($response);
        $this->assertSame('6087E38D611D4DF78922EACF3B817A12', $response->items[2]->recordId);
    }

    function getErrorResponse($errorCode) {
        $getBaseUrl= $this->getMethod('getBaseUrl');
        $url = $getBaseUrl(). 'v1/content?q=contentType.referenceKey+eq+"' . $errorCode . '"+and+filterMode.contentState+eq+"PUBLISHED"&orderBy=publishDate:DESC&offset=0&limit=10';
        $getMakeRequest = $this->getMethod('makeRequest');
        return $getMakeRequest($url);
    }

    function testMakeRequestFor0ErrorCode() {
        $errorCode = '408';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 408', $response->errors[0]->errorCode);
        $this->assertSame('API Request Timed Out', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor400ErrorCode() {
        $errorCode = '400';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 400', $response->errors[0]->errorCode);
        $this->assertSame('Malformed URL', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor403ErrorCode() {
        $errorCode = '403';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 403', $response->errors[0]->errorCode);
        $this->assertSame('Invalid credentials', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor404ErrorCode() {
        $errorCode = '404';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 404', $response->errors[0]->errorCode);
        $this->assertSame('Resource Not Found', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor405ErrorCode() {
        $errorCode = '405';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 405', $response->errors[0]->errorCode);
        $this->assertSame('Method not allowed', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor409ErrorCode() {
        $errorCode = '409';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 409', $response->errors[0]->errorCode);
        $this->assertSame('Conflict', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor500ErrorCode() {
        $errorCode = '500';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 500', $response->errors[0]->errorCode);
        $this->assertSame('API Unavailable', $response->errors[0]->externalMessage);
    }

    function testMakeRequestFor503ErrorCode() {
        $errorCode = '503';
        $response = $this->getErrorResponse($errorCode);
        $this->assertSame('HTTP 503', $response->errors[0]->errorCode);
        $this->assertSame('Service Temporarily Unavailable', $response->errors[0]->externalMessage);
    }

    function testAddPostDataEndPoint(){
        $addPostData = $this->getMethod('addPostData');
        $postData = array("requests" => array());
        $response = $addPostData('FACET', 'DOC_TYPES.CMS-XML', $postData, array());
        $this->assertTrue(Text::stringContains($response['postData']['requests'][0]['relativeUrl'], 'v1/search/navigation'));
        $this->assertNotNull($response);
    }

    function testGetContactDeflectionResponse() {
        $getContactDeflectionResponse = $this->getMethod('getContactDeflectionResponse');
        $contactDeflectionData = array(
            'localeCode' => 'en-US',
            'resultLocale' => '',
            'transactionID' => 0,
            'priorTransactionID' => 457095166,
            'searchSession' => '45245Lh1byHzm',
            'deflected' => true
            );
        $response = $this->api->getContactDeflectionResponse($contactDeflectionData);
        $this->assertNull($response);
        $this->assertSame(0, count($response->errors));
    }
    
    function testContentRecommendationWithoutPriority() {
        $createRecommendation = $this->getMethod('createRecommendation');
        $recommendationData = array(
            'contentTypeRecordId' => '08202025e3d5766014b7d157242007ed0',
            'contentTypeReferenceKey' => 'DEFECT',
            'contentTypeName' => 'Defect',
            'caseNumber' => '',
            'comments' => 'Test Content',
            'title' => 'Test Title',
            'priority' => 'None'
        );
        $response = $createRecommendation($recommendationData);
        $this->assertNotNull($response);
    }

    function testGetArticlesForSiteMapPage() {
        $getArticlesForSiteMapBatch = $this->getMethod('getArticlesForSiteMapBatch');
        $siteMapFilter = array(
            'pageNumber' => 1,
            'pageSize' => 50000,
            'contentStatus' => 'PUBLISHED',
            'priorTransactionID' => '*',
            'maxSiteMapLinks' => 600,
            'maxPerBatch' => 3,
            'offSet' => 0,
            'pageMax' => 600
            );
        $response = $this->api->getArticlesForSiteMapBatch($siteMapFilter);
        $this->assertNotNull($response);
        $articles = json_decode($response->items[0]->body);
        $this->assertSame(600, count($articles->items));
    }

    function testGetDraftArticle() {
        $filter = array(
            'type' => 'popular',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'status' => 'DRAFT',
            'truncate' => 20
        );
        $getArticlesSortedBy = $this->getMethod('getArticlesSortedBy');
        $response = $getArticlesSortedBy($filter, 'en_US');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
    }

    function testGetArticlesForSiteMapIndex() {
        $getArticlesForSiteMapBatch = $this->getMethod('getArticlesForSiteMapBatch');
        $contentTypes = array('"CATEGORY_TEST" ', ' "CATEGORY_TEST.1"', '"DEFECT"', '"FACET_TESTING" ');
        $siteMapFilter = array(
            'pageNumber' => 0,
            'pageSize' => 50000,
            'contentTypes' => $contentTypes,
            'sitemapIndexLimit' => 600,
            'offSet' => 0,
            'endValue' => 3
            );
        $response = $this->api->getArticlesForSiteMapBatch($siteMapFilter);
        $this->assertNotNull($response);
        $this->assertSame(5, json_decode($response->items[0]->body)->publicDocumentCount);
        $this->assertSame(4, count($response->items));
    }

    function testArticleWithInvalidStatus() {
        $filter = array(
            'type' => 'popular',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'status' => 'xyz',
            'truncate' => 20
        );
        $getArticlesSortedBy = $this->getMethod('getArticlesSortedBy');
        $response = $getArticlesSortedBy($filter, 'en_US');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
    }
    
    function testGetApiResponseWithoutMethodName() {
        $getApiResponse = $this->getMethod('getApiResponse');
        $response = $getApiResponse();
        $this->assertSame(1, count($response->errors));
        $this->assertSame('Method name is required', $response->errors[0]->externalMessage);
    }
    
    function testGetApiResponseWithInvalidMethodName() {
        $getApiResponse = $this->getMethod('getApiResponse');
        $dataArray = array('methodType' => 'PUT');
        $response = $getApiResponse('NotAMethodName', $dataArray);
        $this->assertSame(1, count($response->errors));
        $this->assertSame('Invalid method name', $response->errors[0]->externalMessage);
    }
    
    function testGetApiResponseWithInvalidMethodType() {
        $getApiResponse = $this->getMethod('getApiResponse');
        $dataArray = array('methodType' => 'PUT');
        $response = $getApiResponse('contentLearnedLinksForAnswerId', $dataArray);
        $this->assertSame(1, count($response->errors));
        $this->assertSame('Invalid method type', $response->errors[0]->externalMessage);
    }
    
    function testGetApiResponseWithoutPathParameters() {
        $getApiResponse = $this->getMethod('getApiResponse');
        $dataArray = array('methodType' => 'GET');
        $response = $getApiResponse('contentLearnedLinksForAnswerId', $dataArray);
        $this->assertSame(1, count($response->errors));
        $this->assertSame('Path parameter is not provided : answerId', $response->errors[0]->externalMessage);
    }
    
    function testGetApiResponseWithValidData() {
        $getApiResponse = $this->getMethod('getApiResponse');
        $dataArray = array('methodType' => 'GET', 'answerId' => 1000001);
        $response = $getApiResponse('contentForAnswerId', $dataArray);
        $this->assertSame(0, count($response->errors));
        $this->assertNotNull($response);
        $this->assertSame($dataArray['answerId'], $response->answerId);
    }
    
    function testGetApiResponseWithValidDataForSearchApi() {
        $createRequestHeader = $this->getMethod('createRequestHeader');
        $getHost = $this->getMethod('getHost');
        $getApiResponse = $this->getMethod('getApiResponse');
        $dataArray = array('methodType' => 'POST', 'queryString' => 'question=Windows&requestLocale=');
        $postData = array(
            'searchText' => 'Windows',
            'session' => '',
            'transactionId' => null,
            'locale' => 'en-US',
            'resultLocales' => 'en-US',
            'querySource' => null,
            'requestSource' => 'IM',
            'clientInfo' => array(
                'referrer' => '',
                'host' => $getHost(),
                'requestHeaders' => $createRequestHeader(),
                'requestParameters' => array( array( 'key' => 'question_box', 'value' => 'Windows' ) )
            )
        );
        $response = $getApiResponse('searchResult', $dataArray, $postData);
        $facetItems = $response->results->facets;
        $this->assertNotNull($response);
        $this->assertSame(4, count($facetItems));
    }

    function testContentRecommendation() {
       $this->logIn();
       $createRecommendation = $this->getMethod('createRecommendation');
       $recommendationData = array(
           'contentTypeRecordId' => '08202025e3d5766014b7d157242007ed0',
           'contentTypeReferenceKey' => 'DEFECT',
           'contentTypeName' => 'Defect',
           'caseNumber' => '',
           'comments' => 'Test Content',
           'title' => 'Test Title',
           'priority' => 'LOW'
       );
       $response = $createRecommendation($recommendationData);
       $this->assertNotNull($response);
   }

    function testSetUserLanguagePreference() {
        $setUserLanguagePreferences = $this->getMethod('setUserLanguagePreference');
        $getUserRecordId = $this->getMethod('getUserRecordId');
        $response = $setUserLanguagePreferences('cs-CZ,en-US,zh-HK', null, $getUserRecordId('test')->recordId);
        $this->assertNull($response->error);
    }

    function testAttachmentDownload(){
        $getAttachment = $this->getMethod('getAttachment');
        $data = $getAttachment('http://slc01fjo.us.oracle.com:8226/InfoManager/file/resources//okcs__ok152b1__t2/content/draft/082020274001257014a60bef54d007f8f/082020274001257014a60bef54d007f8e/pdf-test.pdf');
        $this->assertNotNull($data);
    }

    function testAggregateRating(){
        $getAggregateRating = $this->getMethod('getAggregateRating');
        $response = $getAggregateRating('04003706c11401e015ab1a43806007f9a');
        $this->assertNotNull($response);
    }

    function testAllTranslations(){
        $getAllTranslations = $this->getMethod('getAllTranslations');
        $response = $getAllTranslations('1000000');
        $this->assertNotNull($response);
    }

    function testGetFavoriteList() {
        $getFavoriteList = $this->getMethod('getFavoriteList');
        $getUserRecordId = $this->getMethod('getUserRecordId');
        $response = $getFavoriteList($getUserRecordId('test')->recordId);
        foreach ($response->items as $item) {
            $this->assertSame('favorite_document', $item->key);
            $this->assertNotNull($item->value);
        }
    }

    function testGetArticlePreview() {
        $answerPreviewDetails = array(
            'answerId' => '1000002',
            'apiVersion' => 'v1',
            'integrationToken' => 'ZGF5MTAxXzIxNTAwX3NxbF8zMmg6VkFXQUplQy9xTUFqc01GTFN3b0xXTHovdlhYcTNCMSt0ZHV1K2JxT3Zkc0UzUWFyRlVxeGJEVEFubWxaSHpBT1NWZzQ4a0RVUjc5OFFPS0tBWnFLSlFtSEF4N1FsRVpZR0FwclNYTWI0YXhZS0g0K2pJcUhoZEV0dThnSVhQbFVFdkkzWHVROXlyRUtMVyszcE1zZEpHREZrMytITDYvYk02V2gvUW5SRnFmeGJxN29WdEVIZVVsQW0vUHpDNGEz',
            'answerVersionId' => '09006125b58b1a01769a5d493c007fcd');
        $getArticlePreview = $this->getMethod('getArticlePreview');
        $response = $getArticlePreview($answerPreviewDetails);

        $this->assertNotNull($response);
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->xml);
        $this->assertNotNull($response->locale);
    }

    function testGetAnswerForDocId() {
        $getAnswerForDocId = $this->getMethod('getAnswerForDocId');

        // test method with only two docIds -- GET call test
        $docIdQuery = "'FA7','FA1'";
        $response = $getAnswerForDocId($docIdQuery);
        $this->assertNotNull($response);
        $this->assertNotNull($response->items[0]->answerId);
        $this->assertNotNull($response->items[1]->answerId);

        // test method with AnswerLimit > 1000 -- POST call test
        $docIdQuery = "'FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7','FA7'";
        $response = $getAnswerForDocId($docIdQuery);
        $this->assertNotNull($response);
        $this->assertNotNull($response->items[0]->answerId);
        $this->assertNotNull($response->items[1]->answerId);
        $this->assertNotNull($response->items[2]->answerId);
    }
}