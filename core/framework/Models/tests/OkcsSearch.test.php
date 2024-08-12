<?
use RightNow\Utils\Text;
 
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsSearchTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\OkcsSearch';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\OkcsSearch;
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
        parent::setUp();
    }

    function tearDown(){
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, true);
        }
        parent::tearDown();
    }

    function testSearchWithInValidQuery() {
         $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'locale' => array('value' => 'en-US'),
        ));
        $resultCount = count($result->result->searchResults->results[0]->resultItems);
        $this->assertSame(null, $result->result->searchResults->results[0]);
        $this->assertSame($resultCount, 0);
    }
    
    function testSearchForIntentResponse() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Intent'),
            'locale' => array('value' => 'en-US'),
        ));
        $resultCount = count($result->result->searchResults['results']->results[0]->resultItems);
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertTrue($resultCount > 0);
        $this->assertNotNull($firstResult->href);
        $this->assertSame($firstResult->type,'template');
        $this->assertStringContains($firstResult->href, '/a_id/');
    }
    
    function testSearchWithValidQuery() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Windows'),
            'locale' => array('value' => 'en-US'),
        ));
        $resultCount = count($result->result->searchResults['results']->results[0]->resultItems);
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertTrue(Text::stringContains($firstResult->href, '/a_id/1000001/loc/en_US/s/16777216_'));
        $this->assertTrue(Text::stringContains($firstResult->href, '/prTxnId/835929468#__highlight'));
        $this->assertNotNull($firstResult->href);
        $this->assertTrue($resultCount > 0);
        $this->assertTrue($resultCount <= 10);
    }
    
    function testSearchWithFacet() {
        $result = $this->model->search(array('query' => array('value' => 'Windows')));
        $searchState = $result->result->searchState;
        $filters = array(
            'facet' => array('value' => 'DOC_TYPES.DOCUMENT'),
            'loc' => array('value' => 'en-US'),
            'okcsSearchSession' => array('value' => $searchState['session']),
            'transactionID' => array('value' => $searchState['transactionID']),
            'priorTransactionID' => array('value' => $searchState['priorTransactionID']),
            'searchType' => array('value' => 'FACET')
        );
        
        $facetData = $this->model->search($filters);
        $this->assertNotNull($facetData->result->searchResults);
        
        $resultCount = count($facetData->result->searchResults['results']->results[0]->resultItems);
        $firstResult = $facetData->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertNotNull($firstResult->href);
        $this->assertTrue($resultCount > 0);
        $this->assertTrue($resultCount <= 10);
    }
    
   function testSearchWithPage() {
        $result = $this->model->search(array('query' => array('value' => 'Windows')));
        $searchState = $result->result->searchState;
        
        $filters = array(
            'loc' => array('value' => 'en-US'),
            'okcsSearchSession' => array('value' => $searchState['session']),
            'priorTransactionID' => array('value' => $searchState['priorTransactionID']),
            'page' => array('value' => '0'),
            'direction' => array('value' => 'forward')
        );
        
        $pageData = $this->model->search($filters);
        $this->assertNotNull($pageData->result->searchResults);
        
        $resultCount = count($pageData->result->searchResults['results']->results[0]->resultItems);
        $firstResult = $pageData->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertNotNull($firstResult->href);
        $this->assertTrue($resultCount > 0);
        $this->assertTrue($resultCount <= 10);
    }
    
    function testSearchWithChannel() {
        $result = $this->model->search(array('channelRecordID' => array('value' => 'SOLUTIONS')))->result;
        $this->assertTrue(count($result->category) > 0);
        $this->assertIsA($result->category, 'Array');
    }
    
    function testGetUrlDataForPDF() {
        $result = $this->model->search(array(
            'query' => array('value' => 'AppQA'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000030/Anchor.pdf');
        $result = $this->model->getUrlData($firstResult);
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $fileName = Text::getSubstringBefore($url, '#xml');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForRTF() {
        $result = $this->model->search(array(
            'query' => array('value' => 'AppQA1'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000041/Loan%20recovery%20amount%20for%20Car%20loan%20and%20clearing%20of%20car%20loan.rtf');
        $result = $this->model->getUrlData($firstResult);
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForTEXT() {
        $result = $this->model->search(array(
            'query' => array('value' => 'AppQA2'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000024/Anchor.txt');
        $result = $this->model->getUrlData($firstResult);
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForXML() {
        $result = $this->model->search(array(
            'query' => array('value' => 'AppQA3'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $result = $this->model->getUrlData($firstResult);
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForHTML() {
        $result = $this->model->search(array(
            'query' => array('value' => 'AppQA4'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000040/Anchor.html');
        $result = $this->model->getUrlData($firstResult);
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForDoc() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Test1'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000032/Loan%20recovery%20amount%20for%20Home%20loan.doc');
        $result = $this->model->getUrlData($firstResult);
       
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForExcel() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Test2'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $this->assertSame($firstResult->dataHref, '/ci/okcsFattach/getFile/1000036/Widget_tests.xlsx');
        $result = $this->model->getUrlData($firstResult);
       
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForODP() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Test3'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $result = $this->model->getUrlData($firstResult);
       
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForODS() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Test4'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $result = $this->model->getUrlData($firstResult);
       
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
    
    function testGetUrlDataForPPT() {
        $result = $this->model->search(array(
            'query' => array('value' => 'Test5'),
            'locale' => array('value' => 'en-US')
        ));
        $firstResult = $result->result->searchResults['results']->results[0]->resultItems[0];
        $result = $this->model->getUrlData($firstResult);
       
        $url = Text::getSubstringAfter($result['url'], '/file/');
        $url = $this->CI->model("Okcs")->decodeAndDecryptData($url);
        $this->assertTrue(Text::stringContains($url, "ATTACHMENT:"));
        $fileName = Text::getSubstringAfter($url, 'ATTACHMENT:');
        $this->assertFalse(Text::stringContains($fileName, "#"));
        $this->assertFalse(Text::stringContains($fileName, ":#"));
    }
}
