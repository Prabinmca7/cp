<?php
use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Config,
    RightNow\Api,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class OkcsModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Okcs';
    public $contentType;
    public $documentID;
    private $initialConfigValues = array();
    
    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Okcs();
        $this->CI = get_instance();
    }
    
    function setUp(){
        $this->initialConfigValues['OKCS_ENABLED'] = \Rnow::getConfig(OKCS_ENABLED);
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $this->initialConfigValues['OKCS_API_TIMEOUT'] = \Rnow::getConfig(OKCS_API_TIMEOUT);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        $this->initialConfigValues['OKCS_SRCH_API_URL'] = \Rnow::getConfig(OKCS_SRCH_API_URL);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        $this->initialConfigValues['OKCS_IM_API_URL'] = \Rnow::getConfig(OKCS_IM_API_URL);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->initialConfigValues['TZ_INTERFACE'] = \Rnow::getConfig(TZ_INTERFACE);
        \Rnow::updateConfig('TZ_INTERFACE', \Rnow::getConfig(TZ_INTERFACE), true);
        $this->initialConfigValues['DTF_SHORT_DATE'] = \Rnow::getConfig(DTF_SHORT_DATE);
        \Rnow::updateConfig('DTF_SHORT_DATE', \Rnow::getConfig(DTF_SHORT_DATE), true);
        parent::setUp();
    }
    
    function tearDown(){
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, true);
        }
        parent::tearDown();
    }
    
    function getMethodInvoker($methodName) {
        return RightNow\UnitTest\Helper::getMethodInvoker('RightNow\Models\Okcs', $methodName);
    }

    function testGetRelatedAnswers() {
        // Verifying results are restricted by count
        $response = $this->model->getRelatedAnswers('48', '5');
        $this->assertSame(5, count($response));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->getRelatedAnswers('48', '10');
        $this->assertSame(9, count($response));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // No results
        $response = $this->model->getRelatedAnswers('49', '10');
        $this->assertSame(0, count($response));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetArticlesSortedWithPopularType() {
        $filter = array(
            'type' => 'popular',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->verifyAnswerListArrayContents($results);
    } 

    function testGetArticlesSortedTitle() {
        $filter = array(
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'category' => '',
            'truncate' => 200
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        // Make sure these defects 151222-000076, 160204-000115 are taken care of if this test is being modified
        $this->assertSame("Test&#039;s Special characters -- !@#$%*() &amp;lt;b&amp;gt; html &amp;lt;/b&amp;gt; &lt;b&gt; bold text &lt;/b&gt;", $results[6]->title);
    }

    function testGetArticlesSortedWithRecentType() {
        $filter = array(
            'type' => 'recent',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));

        $this->verifyAnswerListArrayContents($results);
    }

    function testGetArticlesSortedWithDefaultType() {
        $filter = array(
            'type' => 'XXX',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->verifyAnswerListArrayContents($results);
        
        $filter = array(
            'type' => '1234',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->verifyAnswerListArrayContents($results);
        
        $filter = array(
            'type' => 'Alpha1234',
            'limit' => 0,
            'answerListApiVersion' => 'v1',
            'pageNumber' => 0,
            'contentType' => '',
            'category' => '',
            'truncate' => 20
        );
        $response = $this->model->getArticlesSortedBy($filter);
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->verifyAnswerListArrayContents($results);
    }

    function verifyAnswerListArrayContents($results) {
        foreach ($results as $item) {
            $this->assertSame(1, count($item->contentType->referenceKey));
            $this->assertNotNull($item->contentType->referenceKey);
            $this->assertSame(1, count($item->recordId));
            $this->assertNotNull($item->recordId);
            $this->assertSame(1, count($item->documentId));
            $this->assertNotNull($item->documentId);
            $this->assertSame(1, count($item->title));
            $this->assertNotNull($item->title);
            $this->assertSame(1, count($item->version));
            $this->assertNotNull($item->version);
            $this->assertSame(1, count($item->answerId));
            $this->assertNotNull($item->answerId);
            $this->assertSame(1, count($item->publishDate));
            $this->assertNotNull($item->publishDate);
            $this->assertSame(1, count($item->createDate));
            $this->assertNotNull($item->createDate);
            $this->assertSame(1, count($item->dateAdded));
            $this->assertNotNull($item->dateAdded);
            $this->assertSame(1, count($item->dateModified));
            $this->assertNotNull($item->dateModified);
            $this->assertSame(1, count($item->displayEndDate));
            $this->assertNotNull($item->displayEndDate);
            $this->assertSame(1, count($item->displayStartDate));
            $this->assertNotNull($item->displayStartDate);
        }
    }

    function testGetChannels() {
        $response = $this->model->getChannels('v1');
        $results = $response->items;
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));

        foreach ($results as $item) {
            $this->assertSame(1, count($item->referenceKey));
            $this->assertNotNull($item->referenceKey);
            $this->assertSame(1, count($item->name));
            $this->assertNotNull($item->name);
            $this->assertSame(1, count($item->recordId));
            $this->assertNotNull($item->recordId);
        }
    }

    function testGetChannelDetailsWithValidChannel()
    {
        $response = $this->model->getChannelDetails($this->getChannel());
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $result = $response->result;
        $this->assertNotNull($result);

        $this->assertNotNull($result);
        $this->assertSame(1, count($result->referenceKey));
        $this->assertNotNull($result->referenceKey);
        $this->assertSame(1, count($result->name));
        $this->assertNotNull($result->name);
        $this->assertSame(1, count($result->recordId));
        $this->assertNotNull($result->recordId);
        $this->assertSame(1, count($result->contentSchema));
        $this->assertNotNull($result->contentSchema);
        $this->assertSame(1, count($result->dateAdded));
        $this->assertNotNull($result->dateAdded);
        $this->assertSame(1, count($result->dateModified));
        $this->assertNotNull($result->dateModified);
    }

    function testGetChannelDetailsWithInvalidChannel() {
        $response = $this->model->getChannelDetails('XXX');
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getChannelDetails('');
        $this->assertResponseObject($response, 'is_null', 1, 0);

        $response = $this->model->getChannelDetails(null);
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getChannelDetails(1234);
        $results = $response->result->items;
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getChannelDetails('Alpha1234');
        $results = $response->result->items;
        $this->assertResponseObject($response, 'is_null', 1, 0);
    }

    function verifyImArrayContents($ImArray) {
        if(!is_null($ImArray)) {
            foreach ($ImArray as $categoryItem) {
                $this->assertSame(1, count($categoryItem->recordId));
                $this->assertSame(1, count($categoryItem->referenceKey));
            }
        }
    }

    function testGetIMContentWithValidData() {
        $response = $this->model->getIMContent(array( 'docID' => 1000001, null, 'answerViewApiVersion' => 'v1'));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $categoryArray = $response->categories;
        $viewArray = $response->views;
        $this->assertTrue(is_array($categoryArray));
        $this->assertTrue(is_array($viewArray));
        
        $this->verifyImArrayContents($categoryArray);
        $this->verifyImArrayContents($viewArray);

        $this->assertNotNull($response->recordId);
        $this->assertNotNull($response->versionId);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->answerId);
        $this->assertNotNull($response->contentType->recordId);
        $this->assertNotNull($response->contentType->referenceKey);
        $this->assertNotNull($response->contentType->name);
        $this->assertNotNull($response->locale->recordId);
        $this->assertNotNull($response->priority);
        $this->assertNotNull($response->createDate);
        $this->assertNotNull($response->dateAdded);
        $this->assertNotNull($response->dateModified);
        $this->assertNotNull($response->displayStartDate);
        $this->assertNotNull($response->displayEndDate);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->publishDate);
        $this->assertNotNull($response->checkedOut);
        $this->assertNotNull($response->publishedVersion);
        $this->assertNotNull($response->xml);
    }

    function testGetIMContentWithValidDataDraft() {
        $this->addUrlParameters(array('answer_data' => Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe('a_status/draft_14254_1000041'))));
        $response = $this->model->getIMContent(array( 'docID' => 1000041, null, 'answerViewApiVersion' => 'v1'));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $categoryArray = $response->categories;
        $viewArray = $response->views;
        $this->assertTrue(is_array($categoryArray));
        $this->assertTrue(is_array($viewArray));
        $this->assertFalse($response->published);
        $this->verifyImArrayContents($categoryArray);
        $this->verifyImArrayContents($viewArray);

        $this->assertNotNull($response->recordId);
        $this->assertNotNull($response->versionId);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->answerId);
        $this->assertNotNull($response->contentType->recordId);
        $this->assertNotNull($response->contentType->referenceKey);
        $this->assertNotNull($response->contentType->name);
        $this->assertNotNull($response->locale->recordId);
        $this->assertNotNull($response->priority);
        $this->assertNotNull($response->createDate);
        $this->assertNotNull($response->dateAdded);
        $this->assertNotNull($response->dateModified);
        $this->assertNotNull($response->displayStartDate);
        $this->assertNotNull($response->displayEndDate);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->checkedOut);
        $this->assertNotNull($response->publishedVersion);
        $this->assertNotNull($response->xml);
        $this->restoreUrlParameters();
    }

    function testGetIMContentForSearchClickThruFlow() {
        // This tests the flow when IM content needs to be fetched on click of search result. The getHighlightContent call will fail and invoke the subsequent IM call
        $response = $this->model->getIMContent(array( 'docID' => 1000001, null, 'answerViewApiVersion' => 'v1','searchSession' => '51364bc714972-165d-43db-b689-c1ca269ba48b'));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $categoryArray = $response->categories;
        $viewArray = $response->views;
        $this->assertTrue(is_array($categoryArray));
        $this->assertTrue(is_array($viewArray));
        
        $this->verifyImArrayContents($categoryArray);
        $this->verifyImArrayContents($viewArray);

        $this->assertNotNull($response->recordId);
        $this->assertNotNull($response->versionId);
        $this->assertNotNull($response->documentId);
        $this->assertNotNull($response->title);
        $this->assertNotNull($response->version);
        $this->assertNotNull($response->answerId);
        $this->assertNotNull($response->contentType->recordId);
        $this->assertNotNull($response->contentType->referenceKey);
        $this->assertNotNull($response->contentType->name);
        $this->assertNotNull($response->locale->recordId);
        $this->assertNotNull($response->priority);
        $this->assertNotNull($response->createDate);
        $this->assertNotNull($response->dateAdded);
        $this->assertNotNull($response->dateModified);
        $this->assertNotNull($response->displayStartDate);
        $this->assertNotNull($response->displayEndDate);
        $this->assertNotNull($response->published);
        $this->assertNotNull($response->publishDate);
        $this->assertNotNull($response->checkedOut);
        $this->assertNotNull($response->publishedVersion);
        $this->assertNotNull($response->xml);
    }

     function testGetIMContentWithInvalidData() {
        $response = $this->model->getIMContent(array( 'docID' => 'XXX', null, 'answerViewApiVersion' => 'v1'));
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getIMContent(array( 'docID' => '1234',  null, 'answerViewApiVersion' => 'v1'));
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getIMContent(array( 'docID' => 'Alpha1234',  null, 'answerViewApiVersion' => 'v1'));
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getIMContent(array( 'docID' => '',  null, 'answerViewApiVersion' => 'v1'));
        $this->assertResponseObject($response, 'is_null', 1, 0);
        
        $response = $this->model->getIMContent(array( 'docID' => null,  null, 'answerViewApiVersion' => 'v1'));
        $this->assertResponseObject($response, 'is_null', 1, 0);
    }

    function testGetIMContentSchema() {
        $response = $this->model->getIMContentSchema($this->getChannel(), 'en_US', 'v1');
        $contentSchemaArray = $response['contentSchema'];
        $metaSchemaArray = $response['metaSchema'];
        
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
        if(!is_null($metaSchemaArray)){
            $this->assertTrue(is_array($metaSchemaArray));
            foreach ($metaSchemaArray as $metaSchemaItem) {
                $this->assertNotNull($metaSchemaItem->recordId);
                $this->assertNotNull($metaSchemaItem->name);
                $this->assertNotNull($metaSchemaItem->dateAdded);
                $this->assertNotNull($metaSchemaItem->dateModified);
                $this->assertNotNull($metaSchemaItem->referenceKey);
            }
        }
    }

    function testGetIMContentSchemaForCachedResponse() {
        $response = $this->model->getIMContentSchema('FACET_TESTING', 'en_US', 'v1');
        $contentSchemaArray = $response['contentSchema'];
        $metaSchemaArray = $response['metaSchema'];

        if(!is_null($contentSchemaArray)) {
            $this->assertTrue(is_array($contentSchemaArray));
            foreach ($contentSchemaArray as $contentSchemaItem) {
                $this->assertStringContains('//FACET_TESTING/' . $contentSchemaItem->referenceKey, $contentSchemaItem->xpath);
            }
        }
        if(!is_null($metaSchemaArray)){
            $this->assertTrue(is_array($metaSchemaArray));
            foreach ($metaSchemaArray as $metaSchemaItem) {
                $this->assertStringContains('/FACET_TESTING/' . $metaSchemaItem->referenceKey, $metaSchemaItem->xpath);
            }
        }
    }

   function testProcessIMContentWithValidData() {
        $response = $this->model->processIMContent('1000001');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $this->assertSame(1, count($response['title']));
        $this->assertSame(1, count($response['docID']));
        $this->assertSame(1, count($response['answerID']));
        $this->assertSame(1, count($response['version']));
        $this->assertSame(1, count($response['published']));
        $this->assertSame(1, count($response['publishedDate']));
        $this->assertSame(1, count($response['content']));
        $this->assertSame(1, count($response['locale']));
        $this->assertSame(1, count($response['contentType']->recordId));
        $this->assertSame(1, count($response['contentType']->referenceKey));
        $this->assertSame(1, count($response['contentType']->name));
    }

    function testProcessIMContentWithInvalidData() {
        $response = $this->model->processIMContent('XXX');

        $this->assertNull($response['title']);
        $this->assertNull($response['docID']);
        $this->assertNull($response['answerID']);
        $this->assertNull($response['version']);
        $this->assertNull($response['published']);
        $this->assertNull($response['content']);
        $this->assertNull($response['locale']);
        $this->assertNull($response['contentType']->recordId);
        $this->assertNull($response['contentType']->referenceKey);
        $this->assertNull($response['contentType']->name);
    }

    function testProcessNullDate() {
        $response = $this->model->processIMDate(null);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNull($response);
    }

    function testProcessIMDate() {
        $response = $this->model->processIMDate("2015-05-11T05:36:08MDT");
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNotNull($response);
        $this->assertSame("05/11/2015", $response);
    }
    
    function testProcessIMDateCommonTimestamp() {
        $response = $this->model->processIMDate("2015-12-16 07:00:00 Etc/GMT");
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNotNull($response);
        $this->assertSame("12/16/2015", $response);
    }
    
    function testProcessIMDateSOAPTimestamp() {
        $response = $this->model->processIMDate("2015-05-11T05:36:08-0700");
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNotNull($response);
        $this->assertSame("05/11/2015", $response);
    }
    
    function testProcessIMDateForDifferentLocales() {
        \Rnow::updateConfig('TZ_INTERFACE', 'Europe/Prague', true);
        \Rnow::updateConfig('DTF_SHORT_DATE', "%d. %m. %Y", true);
        $response = $this->model->processIMDate("2016-02-17T03:30:00-0700");
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNotNull($response);
        $this->assertSame("17. 02. 2016", $response);
        \Rnow::updateConfig('TZ_INTERFACE', 'Europe/Berlin', true);
        \Rnow::updateConfig('DTF_SHORT_DATE', "%d/%m/%Y", true);
        $response = $this->model->processIMDate("2016-02-17T03:30:00-0700");
        $this->assertNotNull($response);
        $this->assertSame("17/02/2016", $response);
        \Rnow::updateConfig('TZ_INTERFACE', 'Europe/Copenhagen', true);
        \Rnow::updateConfig('DTF_SHORT_DATE', "%d-%m-%Y", true);
        $response = $this->model->processIMDate("2016-02-17T03:30:00-0700");
        $this->assertNotNull($response);
        $this->assertSame("17-02-2016", $response);
        \Rnow::updateConfig('TZ_INTERFACE', 'Europe/Paris', true);
        \Rnow::updateConfig('DTF_SHORT_DATE', "%m/%d/%Y", true);
        $response = $this->model->processIMDate("2016-02-17T03:30:00-0700");
        $this->assertNotNull($response);
        $this->assertSame("02/17/2016", $response);
     }

    function testGetSupportedLanguages() {
        $response = $this->model->getSupportedLanguages('v1');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $results = $response->items;
        $this->assertTrue(is_array($results));
        foreach ($results as $item) {
            $this->assertSame(1, count($item->active));
            $this->assertNotNull($item->active);
            $this->assertSame(1, count($item->encoding));
            $this->assertNotNull($item->encoding);
            $this->assertSame(1, count($item->localeCode));
            $this->assertNotNull($item->localeCode);
            $this->assertSame(1, count($item->localeDesc));
            $this->assertNotNull($item->localeDesc);
            $this->assertSame(1, count($item->localeValue));
            $this->assertNotNull($item->localeValue);
            $this->assertSame(1, count($item->timeFormat));
            $this->assertNotNull($item->timeFormat);
            $this->assertSame(1, count($item->timeFormatDisplay));
            $this->assertNotNull($item->timeFormatDisplay);
            $this->assertSame(1, count($item->recordId));
            $this->assertNotNull($item->recordId);
        }
    }

    function testGetUserLocale() {
        $response = $this->model->getUserLocale();
        $this->assertNotNull($response);
        $this->assertIdentical($response, 'en-US');
    }

    function verifySearchResultArray($resultItems) {
        $this->assertNotNull($resultItems);
        foreach ($resultItems as $item) {
            $this->assertNotNull($item->fileType);
            $this->assertNotNull($item->answerId);
            $this->assertNotNull($item->docId);
            $this->assertNotNull($item->score);
            $this->assertNotNull($item->clickThroughLink);
            $this->assertNotNull($item->similarResponseLink);
        }
    }

    function testGetAnswersForSelectedFacetWithValidData() {
        $response = $this->model->getSearchResult(array('query' => 'Windows')); 
        $result = $response->result;
        $facetFilter = array(
            'session' => $result['searchState']['session'],
            'transactionID' => $result['searchState']['transactionID'] + 1,
            'priorTransactionID' => $result['searchState']['transactionID'],
            'facet' => 'DOC_TYPES.CMS-XML',
            'resultLocale' => 'en-US'
        );
        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter);
        $this->assertIsA($facetResponse, 'RightNow\Libraries\ResponseObject');
        $response = $facetResponse->result;
        $searchResult = $response['searchResults']['results'];
        $resultItems = $searchResult->results[0]->resultItems;
        $this->verifySearchResultArray($resultItems);
    }

   function testGetAnswersForSelectedFacetWithInvalidData() {
        $result = $this->model->getSearchResult(array('query' => 'XXX'))->result;

        $facetFilter = array(
            'session' => $result['searchState']['session'],
            'transactionID' => 2,
            'priorTransactionID' => 1,
            'facet' => 'XXX',
            'resultLocale' => 'en-US'
        );

        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter)->result;
        $this->assertInvalidFacetResponse($facetResponse, 'XXXX');
        
        $facetFilter['transactionID'] = 3;
        $facetFilter['priorTransactionID'] = 2;
        $facetFilter['facet'] = 1234;
        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter)->result;
        $this->assertInvalidFacetResponse($facetResponse, 1234);
        
        $facetFilter['transactionID'] = 4;
        $facetFilter['priorTransactionID'] = 3;
        $facetFilter['facet'] = 'Alpha1234';
        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter)->result;
        $this->assertInvalidFacetResponse($facetResponse, 'Alpha1234');
        
        $facetFilter['transactionID'] = 5;
        $facetFilter['priorTransactionID'] = 4;
        $facetFilter['facet'] = '';
        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter);
        $this->assertNotNull($facetResponse->errors);
        
        $facetFilter['transactionID'] = 6;
        $facetFilter['priorTransactionID'] = 5;
        $facetFilter['facet'] = null;
        $facetResponse = $this->model->getAnswersForSelectedFacet($facetFilter);
        $this->assertNotNull($facetResponse->errors);
    }

    function assertInvalidFacetResponse($response, $selectedFacet){
        $facetItem = $response["searchResults"]["results"]->facets[0]->children[0];
        $this->assertNotIdentical($facetItem->id, $selectedFacet);
    }

    function testGetSearchPageWithValidData() {
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $result = $response->result;
        $pageFilter = array(
            'session' => $result['searchState']['session'],
            'priorTransactionID' => 1,
            'page' => 0,
            'type' => 'forward',
            'resultLocale' => 'en-US'
        );
        
        $searchPageResponse = $this->model->getSearchPage($pageFilter);
        
        $response1 = $searchPageResponse->result;
        $searchResult = $response1['searchResults']['results'];
        $resultItems = $searchResult->results[0]->resultItems;
        $this->verifySearchResultArray($resultItems);
    }

    function testGetSearchPageWithInvalidData() {
        $response = $this->model->getSearchResult(array('query' => 'XXX'));
        $result = $response->result;
        $pageFilter = array(
            'session' => $result['searchState']['session'],
            'priorTransactionID' => 22,
            'page' => 20,
            'type' => 'forward',
            'resultLocale' => 'en-US'
        );
        $searchPageResponse = $this->model->getSearchPage($pageFilter);
        $response1 = $searchPageResponse->result;
        $this->assertIsA($searchPageResponse, 'RightNow\Libraries\ResponseObject');
        $searchResult = $response1['searchResults']['results'];
        $resultItems = $searchResult->results[0]->resultItems;
        $facetItems = $searchResult->facets;
        $this->assertSame(0, count($resultItems));
        $this->assertSame(0, count($facetItems));
    }

    function testGetSearchResultDataWithValidData() {
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $result = $response->result;
        $searchResult = $result['searchResults']['results'];
        $resultItems = $searchResult->results[0]->resultItems;
        $facetItems = $searchResult->facets;
        $this->verifySearchResultArray($resultItems);
        $this->assertNotNull($facetItems);
        foreach ($facetItems as $item) {
            $this->assertSame(1, count($item->id));
            $this->assertSame(1, count($item->desc));
            $this->assertIsA($item->children,'Array');
        }
    }

    function testGetSearchResultWithHtmlEncodedData() {
        $response = $this->model->getSearchResult(array('query' => '&quot;Silver and White&quot;'));
        $result = $response->result;
        $searchResult = $result['searchResults']['results'];
        $resultItems = $searchResult->results[0]->resultItems;
        $facetItems = $searchResult->facets;
        $this->verifySearchResultArray($resultItems);
        $this->assertSame(1, count($resultItems));
    }

    function testGetDocumentRatingWithInvalidID() {
        $response = $this->model->getDocumentRating('', 'v1');
        $this->assertResponseObject($response, 'is_null', 1, 0);

        $response = $this->model->getDocumentRating(null, 'v1');
        $this->assertResponseObject($response, 'is_null', 1, 0);

        $response = $this->model->getDocumentRating("abc123", 'v1');
        $this->assertResponseObject($response, 'is_null');

        $response = $this->model->getDocumentRating(456334, 'v1');
        $this->assertResponseObject($response, 'is_null');
    }

    function testGetDocumentRatingWithValidID() {
        $this->logIn('test');
        $getDocumentRating = $this->getMethod('getDocumentRating');
        $response = $getDocumentRating('1000001', 'v1');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertNotNull($response->result['surveyRecordID']);
        $this->assertSame(1, count($response->result['surveyRecordID']));
        $this->assertNotNull($response->result['questions']);
        $this->assertSame(1, count($response->result['questions']));
    }

    function testGetDocumentRatingWithCustomType() {
        $this->logIn('test');
        $response = $this->model->getDocumentRating('1000001', 'v1');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testSubmitDocumentRating() {
        $response = $this->model->submitRating(array('surveyRecordID' => '08202025e4fff4501468a55022f007e82', 'answerRecordID' => '08202025e4fff4501468a55022f007e88', 'contentRecordID' => '08202025e4fff4501468a55022f007c31', 'localeRecordID' => 'en_US'));
        $this->assertResponseObject($response, 'is_bool');
        $this->assertIdentical($response->result, true);
    }

    function testSubmitSearchRating() {
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $result = $response->result;
        $response = $this->model->submitSearchRating('1', 'Search was helpful', $result['searchState']['priorTransactionId'], $result['searchState']['session']);
        $this->assertTrue($response->result);
        $this->assertSame(0, count($response->errors));
    }

    function testGetSearchResultWithInvalidData() {
        $getSearchResult = $this->getMethod('getSearchResult');
        $filters = array(
            'query' => 'qwertv',
            'locale' => 'en-US',
            'session' => '',
            'transactionID' => 1
        );
        $response = $getSearchResult($filters);
        $result = $response->result;
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response));
        $this->assertSame(0, count($response->result->error));
        $searchResult = $result['searchResults']['results'];
        $resultItems = $searchResult->items[0]->resultItems;
        $facetItems = $searchResult->facets;
        $this->assertSame(0, count($resultItems));
        $this->assertSame(0, count($facetItems));
    }

    function testRetrieveSmartAssistantRequest() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Windows',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), \RightNow\Utils\Config::getConfig(SA_NL_MAX_SUGGESTIONS));

        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $href = $answer['href'];
        $getUrlParameter = $this->getMethod('getUrlParameter');
        $keyValue = $getUrlParameter($href, 'loc');
        $this->assertSame('en_US', $keyValue);
        $this->assertNull($response->errors);
        $this->assertNotNull($answer['href']);
    }
    
    function testRetrieveSmartAssistantRequestWithProductOnly() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Windows',
                    'required' => true
                ),
                'Incident.Product' => (object) array(
                    'value' => 'windows',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Product');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), \RightNow\Utils\Config::getConfig(SA_NL_MAX_SUGGESTIONS));

        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $href = $answer['href'];
        $getUrlParameter = $this->getMethod('getUrlParameter');
        $keyValue = $getUrlParameter($href, 'loc');
        $this->assertSame('en_US', $keyValue);
        $this->assertNull($response->errors);
        $this->assertNotNull($answer['href']);
    }

    function getUrlParameterValue($url, $parameter){
        $parameterList = explode('/', $url);
        for($i = 0; $i < count($parameterList); $i = $i + 2) {
            if($parameterList[$i] === $parameter) {
                return (int)$parameterList[$i+1];
            }
        }
    }

    function testGetUser(){
        $getUser = $this->getMethod('getUser');
        $user = $getUser();
        $this->assertNotNull($user);
        $this->assertSame('guest', $user);
    }

    function testDecodeAndDecryptData() {
        $answerData = Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe('title/test'));
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $data = $decodeAndDecryptData($answerData);
        $this->assertNotNull($data);
        $this->assertSame($data, 'title/test');
    }

    function testEncryptAndEncodeData() {
        $encryptAndEncodeData = $this->getMethod('encryptAndEncodeData');
        $data = $encryptAndEncodeData('title/test');
        $this->assertNotNull($data);
    }

    function testGetArticleDetails(){
        $getArticleDetails = $this->getMethod('getArticleDetails');
        $response = $getArticleDetails('1000001', 'v1');
        $this->assertNotNull($response);
        $this->assertNotNull($response['title']);
        $this->assertNotNull($response['docID']);
        $this->assertNotNull($response['version']);
        $this->assertNotNull($response['published']);
    }

    function testCacheData(){
        $cacheData = $this->getMethod('cacheData');
        $getCacheData = $this->getMethod('getCacheData');
        $response = $cacheData('login', 'testUser');
        $response = (string)$getCacheData('login');
        $this->assertIdentical($response, 'testUser');
    }

    function testGetAnswerViewData(){
        $getAnswerViewData = $this->getMethod('getAnswerViewData');
        $response = $getAnswerViewData('1000001', null, null, null, 'v1', true);
        $this->assertNotNull($response);
        $this->assertIsA($response, 'Array');
    }

    function getChannel() {
        if(!is_null($this->contentType) && !empty($this->contentType)) {
            $contentType = $this->contentType;
        } 
        else {
            $response = $this->model->getChannels('v1');
            if(count($response->items) > 0)
                $contentType = $response->items[2]->referenceKey;
            $this->contentType = $contentType;
        }
        return $contentType;
    }

    function getDocument() {
        if(!is_null($this->documentID) && !empty($this->documentID)) {
            $documentID = $this->documentID;
        } 
        else {
            $getArticlesSortedBy = $this->getMethod('getArticlesSortedBy');
            $response = $getArticlesSortedBy(array('answerListApiVersion' => 'v1'));
            if(count($response->items) > 0)
                $documentID = $response->items[0]->answerId;
            $this->documentID = $documentID;
        }
        return $documentID;
    }

    function testGetUserLanguagePreferences() {
        $this->logIn();
        $getUserLanguagePreferences = $this->getMethod('getUserLanguagePreferences');
        $response = $getUserLanguagePreferences();
        $this->assertIdentical($response->key, 'search_language');
        $this->assertNotNull($response->value);
    }

    function testGetChannelCategories() {
        $categoryValues = $this->model->getChannelCategories('', 'v1');
        // HTML characters are being escaped at model layer
        $this->assertSame("Product 2&#039;s Special characters -- !@#$%*() &amp;lt;b&amp;gt; html &amp;lt;/b&amp;gt; &lt;b&gt; bold text &lt;/b&gt;", $categoryValues->items[2]->name);
    }

    function testGetArticles() {
        $results = $this->model->getArticles(0, 1);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_array($results));
        $this->assertTrue(is_array($results['data']));
    }

    function testClearcache() {
        $cacheData = $this->getMethod('cacheData');
        $cacheID = $cacheData('lang', 'en_US');
        $clearcache = $this->getMethod('clearcache');
        $data = $clearcache();
        $this->assertSame(200, $data->result['statusCode']);
    }

    function testGetHighlightedHTML(){
        $urlData = array('highlightedLink' => 'xyzsdf', 'searchSession' => 'sertdf', 'txn' => '1', 'answerId' => '1000001', 'locale' => 'en-US', 'prTxnId' => '1234', 'transactionID' => '1235');
        $getHighlightedHTML = $this->getMethod('getHighlightedHTML');
        $response = $getHighlightedHTML($urlData);
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
        $this->assertNull($response['html']);
        $this->assertSame('IM:ANSWER_HTML_01:8890A5F088E941B39B20DE335D2B9629:en_US:published:AN636:1000637:1.0', $response['url']);
    }

    function testGetSubscriptionList(){
        $this->logIn();
        $getSubscriptionList = $this->getMethod('getSubscriptionList');
        $response = $getSubscriptionList();
        $this->assertNotNull($response);
    }

    function testUnsubscribeAnswerWithWrongID(){
        $this->logIn();
        $unsubscribeAnswer = $this->getMethod('unsubscribeAnswer');
        $response = $unsubscribeAnswer('XYZ');
        $this->assertNotNull($response);
    }

    function testSortNotifications(){
        $this->logIn();
        $sortNotifications = $this->getMethod('sortNotifications');
        $response = $sortNotifications('documentId', 'desc');
        $this->assertNotNull($response);
    }

   function testUpdateRecentSearchesArrayFromSession() {
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame('Windows', $sessionRecentSearches[0]);
    }

    function testUpdateRecentSearchesArrayOrder() {
        $response = $this->model->getSearchResult(array('query' => 'AppQA'));
        $response = $this->model->getSearchResult(array('query' => 'Top'));
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame('Windows', $sessionRecentSearches[0]);
    }

    function testUpdateRecentSearchesArrayDuplicates() {
        $response = $this->model->getSearchResult(array('query' => 'This\'s is a ;: Test$`~#<> content'));
        $response = $this->model->getSearchResult(array('query' => 'AppQA'));
        $response = $this->model->getSearchResult(array('query' => 'Top'));
        $response = $this->model->getSearchResult(array('query' => 'This\'s is a ;: Test$`~#<> content'));
        $response = $this->model->getSearchResult(array('query' => 'AppQA'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame(1, count(array_search('AppQA', $sessionRecentSearches)));
        $this->assertSame(1, count(array_search('This\'s is a ;: Test$`~#<> content', $sessionRecentSearches)));
        $this->assertSame('AppQA', $sessionRecentSearches[0]);
    }

    function testUpdateRecentSearchesWithInvalidSearch() {
        $response = $this->model->getSearchResult(array('query' => '中國未編碼'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame('中國未編碼', $sessionRecentSearches[0]);
    }

    function testRetrieveSmartAssistantRequestForTEXT() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'AppQA',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $answer = $suggestion['list'][0];      
        $this->assertNotNull($answer);
        
        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $url = Text::getSubstringAfter($answer['href'], '/file/');
        $href = $decodeAndDecryptData($url);
        $this->assertNotNull($href );
        $this->assertTrue(Text::stringContains($href , "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($href , 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }

    function testRetrieveSmartAssistantRequestForExtAttachment() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Ruby',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Ruby')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $this->assertSame('Ruby Course', $suggestion['list'][0]['title']);
        $this->assertSame('Linux - Wikipedia, the free encyclopedia', $suggestion['list'][1]['title']);
        $this->assertSame('No Title', $suggestion['list'][2]['title']);
        $this->assertSame('Search Highlighting in Acrobat Reader', $suggestion['list'][3]['title']);
        $this->assertSame('car.xml', $suggestion['list'][4]['title']);
    }

    function testRetrieveSmartAssistantRequestForTitle() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Title',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Title')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $this->assertSame('Proof Submission document in Windows', $suggestion['list'][0]['title']);
        $this->assertSame('windows for facets', $suggestion['list'][1]['title']);
        $this->assertSame('electric battery', $suggestion['list'][2]['title']);
    }

    function testRetrieveSmartAssistantRequestForPDF() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Top',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $answer = $suggestion['list'][0];      
        $this->assertNotNull($answer);
        
        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $url = Text::getSubstringAfter($answer['href'], '/file/');
        $href = $decodeAndDecryptData($url);
        $this->assertNotNull($href );
        $this->assertTrue(Text::stringContains($href , "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($href , 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }

    function testRetrieveSmartAssistantRequestForHTML() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Anchor',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $answer = $suggestion['list'][0];      
        $this->assertNotNull($answer);
        
        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $url = Text::getSubstringAfter($answer['href'], '/file/');
        $href = $decodeAndDecryptData($url);
        $this->assertNotNull($href );
        $this->assertTrue(Text::stringContains($href , "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($href , 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }

    function testRetrieveSmartAssistantRequestForXML() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'Loan',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData);
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);
        $answer = $suggestion['list'][0];      
        $this->assertNotNull($answer);
        
        $highlightLink = $answer['highlightLink'];
        $highlightLinkData = $decodeAndDecryptData($highlightLink);
        $transactionID = $this->getUrlParameterValue($highlightLinkData, 'txn');
        $url = Text::getSubstringAfter($answer['href'], '/file/');
        $href = $decodeAndDecryptData($url);
        $this->assertNotNull($href );
        $this->assertTrue(Text::stringContains($href , "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($href , 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }

    function testUpdateRecentSearchesArray() {
        $response = $this->model->getSearchResult(array('query' => 'Windows'));
        $response = $this->model->getSearchResult(array('query' => 'isn\'t it a <b>nice day<\/b>'));
        $response = $this->model->getSearchResult(array('query' => '#®™ €phone(0001)-[701_{9898}_@=+*1800%^]$'));
        $response = $this->model->getSearchResult(array('query' => 'dies ist Windows-Dokument zu Testzwecken'));
        $response = $this->model->getSearchResult(array('query' => 'זה מסמך חלונות למטרה בדיקה'));
        $response = $this->model->getSearchResult(array('query' => 'ceci est le document de fenêtres des fins de test'));
        $response = $this->model->getSearchResult(array('query' => '這是由於Windows文檔測試的目的'));
        $response = $this->model->getSearchResult(array('query' => 'هذا هو وثيقة ويندوز لغرض الاختبار'));
        $response = $this->model->getSearchResult(array('query' => 'це вікна документа для цілей тестування'));
        $response = $this->model->getSearchResult(array('query' => 'นี้คือเอกสารหน้าต่างเพื่อจุดประสงค์ในการทดสอบ'));
        $response = $this->model->getSearchResult(array('query' => 'window\"s & iphone'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(10);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame(10, count($sessionRecentSearches));
        $this->assertSame('window\"s & iphone', $sessionRecentSearches[0]);
        $this->assertSame('นี้คือเอกสารหน้าต่างเพื่อจุดประสงค์ในการทดสอบ', $sessionRecentSearches[1]);
        $this->assertSame('це вікна документа для цілей тестування', $sessionRecentSearches[2]);
        $this->assertSame('هذا هو وثيقة ويندوز لغرض الاختبار', $sessionRecentSearches[3]);
        $this->assertSame('這是由於Windows文檔測試的目的', $sessionRecentSearches[4]);
        $this->assertSame('ceci est le document de fenêtres des fins de test', $sessionRecentSearches[5]);
        $this->assertSame('זה מסמך חלונות למטרה בדיקה', $sessionRecentSearches[6]);
        $this->assertSame('dies ist Windows-Dokument zu Testzwecken', $sessionRecentSearches[7]);
        $this->assertSame('#®™ €phone(0001)-[701_{9898}_@=+*1800%^]$', $sessionRecentSearches[8]);
        $this->assertSame('isn\'t it a <b>nice day<\/b>', $sessionRecentSearches[9]);
        $this->assertNotIdentical(11, count($sessionRecentSearches));
    }

    function testUpdateRecentSearchesArrayWithNumberOfSuggestions() {
        $response = $this->model->getSearchResult(array('query' => 'dies ist Windows-Dokument zu Testzwecken'));
        $response = $this->model->getSearchResult(array('query' => 'זה מסמך חלונות למטרה בדיקה'));
        $response = $this->model->getSearchResult(array('query' => 'ceci est le document de fenêtres des fins de test'));
        $response = $this->model->getSearchResult(array('query' => '這是由於Windows文檔測試的目的'));
        $response = $this->model->getSearchResult(array('query' => 'هذا هو وثيقة ويندوز لغرض الاختبار'));
        $response = $this->model->getSearchResult(array('query' => 'це вікна документа для цілей тестування'));
        $response = $this->model->getSearchResult(array('query' => 'นี้คือเอกสารหน้าต่างเพื่อจุดประสงค์ในการทดสอบ'));
        $response = $this->model->getSearchResult(array('query' => 'this is latest search'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame(5, count($sessionRecentSearches));
        $this->assertSame('this is latest search', $sessionRecentSearches[0]);
        $this->assertSame('นี้คือเอกสารหน้าต่างเพื่อจุดประสงค์ในการทดสอบ', $sessionRecentSearches[1]);
        $this->assertSame('це вікна документа для цілей тестування', $sessionRecentSearches[2]);
        $this->assertSame('هذا هو وثيقة ويندوز لغرض الاختبار', $sessionRecentSearches[3]);
        $this->assertSame('這是由於Windows文檔測試的目的', $sessionRecentSearches[4]);
        $this->assertNotIdentical(6, count($sessionRecentSearches));
    }

    function testGetErrorPageDetails() {
        $fetchErrorPageDetail = $this->getMethod('fetchErrorPageDetails');

        $this->assertSame('/app/error/error_id/1', $fetchErrorPageDetail('HTTP 400'));
        $this->assertSame('/app/error/error_id/4', $fetchErrorPageDetail('HTTP 403'));
        $this->assertSame('/app/error/error_id/1', $fetchErrorPageDetail('HTTP 404'));
        $this->assertSame('/app/error/error_id/1', $fetchErrorPageDetail('HTTP 409'));
        $this->assertSame('/app/error/error_id/1', $fetchErrorPageDetail('HTTP 500'));
        $this->assertSame('/app/error/error_id/1', $fetchErrorPageDetail('HTTP 503'));
    }
    
    function testUpdateRecentSearchesArrayWithMultipleSpacesQuery() {
        $response = $this->model->getSearchResult(array('query' => 'beatles test'));
        $response = $this->model->getSearchResult(array('query' => 'beatles                                                                 test'));
        $response = $this->model->getSearchResult(array('query' => '    beatles        test    '));
        $response = $this->model->getSearchResult(array('query' => '    beatles        test    with     multiple   spaces  '));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame('beatles test with multiple spaces', $sessionRecentSearches[0]);
        $this->assertSame('beatles test', $sessionRecentSearches[1]);
        $this->assertNotIdentical('beatles test', $sessionRecentSearches[2]);
    }
    
    function testUpdateRecentSearchesArrayWithCaseSensitive() {
        $response = $this->model->getSearchResult(array('query' => 'GOOGLE SEARCH'));
        $response = $this->model->getSearchResult(array('query' => 'Google search'));
        $getUpdatedRecentSearches = $this->getMethod('getUpdatedRecentSearches');
        $sessionRecentSearches = $getUpdatedRecentSearches(5);
        $this->assertNotNull($sessionRecentSearches);
        $this->assertSame('Google search', $sessionRecentSearches[0]);
        $this->assertNotIdentical('GOOGLE SEARCH', $sessionRecentSearches[0]);
        $this->assertNotIdentical('GOOGLE SEARCH', $sessionRecentSearches[1]);
        $this->assertSame(1, count(array_search("Google search",$sessionRecentSearches)));
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

    function testGetArticlesForSiteMapIndex() {
        $hookData = array('pageNumber' => 0, 'sitemapPageLimit' => 50000);
        $response = $this->model->getArticlesForSiteMap($hookData);
        $this->assertSame(65, $hookData['answers']['total_num']);
    }

    function testGetArticlesForSiteMapIndexWrongChannel() {
        $hookData = array('pageNumber' => 0, 'sitemapPageLimit' => 50000);
        $ContentTypes = '("CATEGORY_TEST", "CATEGORY_TEST.1" , "DEFEEECT","FACET_TESTING")';
        $response = $this->model->getArticlesForSiteMap($hookData, 10000, $ContentTypes, 3);
        $this->assertSame(15, $hookData['answers']['total_num']);
    }

    function testGetArticlesForSiteMapIndexEmptyChannel() {
        $hookData = array('pageNumber' => 0, 'sitemapPageLimit' => 50000);
        $ContentTypes = '';
        $response = $this->model->getArticlesForSiteMap($hookData, 10000, $ContentTypes, 3);
        $this->assertSame(65, $hookData['answers']['total_num']);
    }

    function testGetArticlesForSiteMapIndexSingleChannel() {
        $hookData = array('pageNumber' => 0, 'sitemapPageLimit' => 50000);
        $ContentTypes = '("CATEGORY_TEST")';
        $response = $this->model->getArticlesForSiteMap($hookData, 10000, $ContentTypes, 3);
        $this->assertSame(5, $hookData['answers']['total_num']);
    }

    function testGetArticlesForSiteMap() {
        $hookData = array('pageNumber' => 1, 'sitemapPageLimit' => 600);
        $response = $this->model->getArticlesForSiteMap($hookData);
        $this->assertSame(600, $hookData['answers']['total_num']);
    }

    function testRetrieveSAResultsWithProductOnlyForGuest() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'GuestProduct',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Category');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with product1 for guest user');
    }

    function testRetrieveSAResultsWithProductAndLoggedInUser() {
        $this->logIn('test');
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'UserProduct',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Category');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with product1 for test user');
    }

    function testRetrieveSAResultsWithCategoryOnlyForGuest() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'GuestCategory',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Category');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with Category1 for guest user');
    }

    function testRetrieveSAResultsWithCategoryAndLoggedInUser() {
        $this->logIn('test');
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'UserCategory',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'Windows 8')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Category');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with Category1 for test user');
    }

    function testRetrieveSAResultsWithNoFilterAndGuest() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'GuestNone',
                    'required' => true
                ),
                'Incident.Product' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'windows')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'None');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is not associated with any product and category for guest user');
    }

    function testSAResultsWithNoFilterAndLoggedInUser() {
        $this->logIn('test');
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'UserNone',
                    'required' => true
                ),
                'Incident.Product' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'windows')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'None');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is not associated with any product and category for test user');
    }

    function testSAResultsWithProdAndCatForGuest() {
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'GuestProductCategory',
                    'required' => true
                ),
                'Incident.Product' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'windows')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'None');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with product1 and category1 for guest user');
    }

    function testSAResultsWithProdAndCatForUser() {
        $this->logIn('test');
        $hookData = array(
            'formData' => array(
                'Contact.Emails.PRIMARY.Address' => (object) array(
                    'value' => 'test@test.com',
                    'required' => true
                ),
                'Incident.Subject' => (object) array(
                    'value' => 'UserProductCategory',
                    'required' => true
                ),
                'Incident.Product' => (object) array(
                    'value' => 'product1',
                    'required' => true
                ),
                'Incident.Category' => (object) array(
                    'value' => 'category1',
                    'required' => true
                ),
                'Incident.Threads' => (object) array('value' => 'windows')
            ),
            'token' => '',
            'canEscalate' => true,
            'suggestions' => null,
            'transactionID' => 1,
            'priorTransactionID' => 1,
            'okcsSearchSession' => ''
        );
        $decodeAndDecryptData = $this->getMethod('decodeAndDecryptData');
        $response = $this->model->retrieveSmartAssistantRequest($hookData, 'Product');
        $suggestion = $hookData['suggestions'][0];
        $this->assertNotNull($suggestion);

        $answer = $suggestion['list'][0];
        $this->assertNotNull($answer);
        $this->assertIdentical(count($suggestion['list']), 1);
        $this->assertIdentical($suggestion['list'][0]['title'], 'This answer is associated with product1 and category1 for test user');
    }
    
    function testGetRecommendationsView() {
        $this->logIn();
        $getRecommendationsView = $this->getMethod('getRecommendationsView');
        $response = $this->model->getRecommendationsView('DFC5E25E4E3D45F5BB12BC9E5CD88FBE');
        $this->assertNotNull($response);
        $this->assertIdentical($response->status, 'Rejected Other');
        $response = $this->model->getRecommendationsView('52CBAF3CD860453989A8D1FAA45BFBDF');
        $this->assertNotNull($response);
        $this->assertIdentical($response->status, 'New');
    }

    function testEmailOkcsAnswerLink() {
        $emailData = array(
            'sendTo' => 'sender@abc.com',
            'name' => 'SenderName',
            'from' => 'from@abc.com',
            'answerID' => '1000000',
            'title' => 'Sample title',
            'emailHeaderLabel' => 'The following answer has been forwarded to you by',
            'emailSenderLabel' => '(Sender address has not been verified)',
            'summaryLabel' => 'Summary',
            'answerViewLabel' => 'you can view this answer here.',
            'emailAnswerToken' => 'ZlVaQ0ZiNUF5U0UwQmVSZUlhOVZMSndsVzNReldDM2pBYzZjWHd4VUhzSmJSMXI4NGVXYzFIU3NCUVlPZ2pGbVoxQVd6QVZ0dHRIMnVzdXFQWDVrV2w1NzBic35Kal9VZ2doUVR_bE5DUHNxNUlwN2RCbThQYlRheHJMMDJ3SjgzU2trNldVUEhFSGhjRzJlcThkcVNHeEZGX0xrVmtPdHcycGRfM2pGWVhUZ0VFdXFqVDdfZDhzQSEh'
        );
        
        $response = $this->model->emailOkcsAnswerLink($emailData);
        $this->assertSame(true, $response->result);
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
    }
    function testAggregateRating(){
        $getAggregateRating = $this->getMethod('getAggregateRating');
        $response = $getAggregateRating('04003706c11401e015ab1a43806007f9a');
        $this->assertNotNull($response);
    }
    function testGetProductCategoryDetails(){
        $getProductCategoryDetails = $this->getMethod('getProductCategoryDetails');
        $response = $getProductCategoryDetails('RN_PRODUCT_1');
        $this->assertNotNull($response);
        $this->assertSame('PRODUCT', $response->externalType);
        $this->assertSame('Iphone', $response->name);
        $this->assertSame('213F3C6D6AA3466891A27763349D8F1F', $response->recordId);
        $this->assertSame(true, $response->hasChildren);

        $response = $getProductCategoryDetails('RN_CATEGORY_1');
        $this->assertNotNull($response);
        $this->assertSame('CATEGORY', $response->externalType);
        $this->assertSame('OPERATING_SYSTEM', $response->name);
        $this->assertSame('EDE35B79C8CC46F4A0FB9187D05FC659', $response->recordId);
        $this->assertSame(false, $response->hasChildren);

        // For Special characters in name -- << `~!#&lt;b&gt;%^$@&amp;*()_{}&lt;/b&gt; >>
        $response = $getProductCategoryDetails('RN_PRODUCT_2');
        $this->assertNotNull($response);
        $this->assertSame('PRODUCT', $response->externalType);
        $this->assertSame('Spl(P1.5 `~!#&amp;lt;b&amp;gt;%^$@&amp;amp;*()_{}&amp;lt;/b&amp;gt;)', $response->name);
        $this->assertSame('213F3C6D6AA3466891A27763349D8F1E', $response->recordId);
        $this->assertSame(true, $response->hasChildren);

        $response = $getProductCategoryDetails('RN_CATEGORY_2');
        $this->assertNotNull($response);
        $this->assertSame('CATEGORY', $response->externalType);
        $this->assertSame('Spl(C1.5 `~!#&amp;lt;b&amp;gt;%^$@&amp;amp;*()_{}&amp;lt;/b&amp;gt;)', $response->name);
        $this->assertSame('EDE35B79C8CC46F4A0FB9187D05FC659', $response->recordId);
        $this->assertSame(false, $response->hasChildren);
    }

    function testAllTranslations(){
        $getAllTranslations = $this->getMethod('getAllTranslations');
        $response = $getAllTranslations('1000044');
        $this->assertNotNull($response);
        foreach($response as $item) {
            $this->assertNotNull($item['answerId']);
            $this->assertSame(1, count($item['answerId']));
            $this->assertNotNull($item['localeRecordId']);
            $this->assertSame(1, count($item['localeRecordId']));
        }
    }

    function testAllLocaleDescriptions(){
        $getAllLocaleDescriptions = $this->getMethod('getAllLocaleDescriptions');
        $response = $getAllLocaleDescriptions('v1');
        $this->assertNotNull($response);
        $this->assertSame('Cetina Ceskᠲepublika', $response['cs_CZ']);
        $this->assertSame('English United States', $response['en_US']);
        $this->assertSame('Hebrew Israel', $response['he_IL']);
        $this->assertSame('Italiano Italia', $response['it_IT']);
    }

    function testGetFavoriteList() {
        $this->logIn();
        $getFavoriteList = $this->getMethod('getFavoriteList');
        $response = $getFavoriteList();
        $this->assertSame('favorite_document', $response->key);
        $this->assertNotNull($response->value);
    }

    function testGetSuggestions() {
        $suggestionList = $this->model->getSuggestions('oracle', '10')->items;
        $this->assertNotNull($suggestionList);
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithProductOnly() {
        $additionalParameters = array();
        $additionalParameters['productCategory'] = 'RN_PRODUCT_8';
        $additionalParameters['matchAllCategories'] = 'true';
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('oracle', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(3, count($suggestionList));
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithCategoryOnly() {
        $additionalParameters = array();
        $additionalParameters['productCategory'] = 'RN_CATEGORY_12';
        $additionalParameters['matchAllCategories'] = 'true';
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('oracle', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(4, count($suggestionList));
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithProdCategCombo() {
        $additionalParameters = array();
        $additionalParameters['productCategory'] = 'RN_PRODUCT_8,RN_CATEGORY_18';
        $additionalParameters['matchAllCategories'] = 'true';
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('oracle', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(1, count($suggestionList));
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithMatchAllCategsFalse() {
        $additionalParameters = array();
        $additionalParameters['productCategory'] = 'RN_PRODUCT_1,RN_PRODUCT_2';
        $additionalParameters['matchAllCategories'] = 'false';
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('sample', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(5, count($suggestionList)); // five suggestions retrieved
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithMatchAllCategsTrue() {
        $additionalParameters = array();
        $additionalParameters['productCategory'] = 'RN_PRODUCT_1,RN_PRODUCT_2';
        $additionalParameters['matchAllCategories'] = 'true';
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('sample', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(1, count($suggestionList)); // ONLY one matching suggestion retrieved
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithApplyCtIndexStatusTrue() {
        $additionalParameters = array();
        $additionalParameters['applyCtIndexStatus'] = 'true';
        $suggestionList = $this->model->getSuggestions('solu', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(1, count($suggestionList)); // ONLY one matching suggestion is retrieved
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithApplyCtIndexStatusFalse() {
        $additionalParameters = array();
        $additionalParameters['applyCtIndexStatus'] = 'false';
        $suggestionList = $this->model->getSuggestions('solu', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(2, count($suggestionList)); // TWO matching suggestions are retrieved
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }

    function testGetSuggestionsWithApplyCtIndexStatusNull() {// defaults the applyCtIndexStatus flag in model layer to true before API invocation
        $additionalParameters = array();
        $suggestionList = $this->model->getSuggestions('solu', '10', $additionalParameters)->items;
        $this->assertNotNull($suggestionList);
        $this->assertSame(1, count($suggestionList)); // ONLY one matching suggestion is retrieved -- same as true case
        foreach ($suggestionList as $suggestion) {
            $this->assertNotNull($suggestion->highlightedTitle);
            $this->assertNotNull($suggestion->title);
            $this->assertNotNull($suggestion->answerId);
        }
    }
}