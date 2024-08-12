<?php
use RightNow\Connect\v1_2 as Connect,
    RightNow\Controllers,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsAjaxRequestTest extends CPTestCase {

    private $initialConfigValues = array();

    function __construct() {
        parent::__construct();
    }

    function setUp(){
        $this->initialConfigValues['OKCS_ENABLED'] = \Rnow::getConfig(OKCS_ENABLED);
        \Rnow::updateConfig('OKCS_ENABLED', 1, false);
        $this->initialConfigValues['OKCS_API_TIMEOUT'] = \Rnow::getConfig(OKCS_API_TIMEOUT);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, false);
        $this->initialConfigValues['OKCS_SRCH_API_URL'] = \Rnow::getConfig(OKCS_SRCH_API_URL);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', false);
        $this->initialConfigValues['OKCS_IM_API_URL'] = \Rnow::getConfig(OKCS_IM_API_URL);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', false);
        parent::setUp();
    }

    function tearDown(){
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, true);
        }
        parent::tearDown();
    }

    function testGetOkcsDataForBrowseArticles() {
        $filters = '{"channelRecordID":{"value":"SOLUTIONS","key":"channelRecordID","type":"channelRecordID"}, "limit":{"value":100,"key":"limit","type":"limit"},"pageSize":{"value":10,"key":"pageSize","type":"pageSize"}, "truncate":{"value":200,"key":"truncate","type":"truncate"}}';
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData', array('post' => 'filters='.$filters.'&answerListApiVersion=v1')));
        $this->assertIsA($response->articles, 'array');
        $article = $response->articles[0];
        $this->assertEqual($article->contentType->referenceKey, 'SOLUTIONS');
        $this->assertEqual($article->locale->recordId, 'en_US');
        $this->assertNotNull($article->title);
        $this->assertNotNull($article->answerId);
        $this->assertNotNull($article->documentId);
    }

    function testGetOkcsDataForBrowseArticlesTruncate() {
        $filters = '{"channelRecordID":{"value":"SOLUTIONS","key":"channelRecordID","type":"channelRecordID"}, "limit":{"value":100,"key":"limit","type":"limit"},"pageSize":{"value":10,"key":"pageSize","type":"pageSize"}, "truncate":{"value":10,"key":"truncate","type":"truncate"}}';
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData', array('post' => 'filters=' . $filters . '&answerListApiVersion=v1')));
        $this->assertIsA($response->articles, 'array');
        $article = $response->articles[1];
        $this->assertEqual($article->contentType->referenceKey, 'SOLUTIONS');
        $this->assertEqual($article->locale->recordId, 'en_US');
        $this->assertEqual($article->title, 'Download...');
    }

    function testContactDeflectionResponse() {
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData',
            array('post' => sprintf("priorTransactionID=%d&deflected=%s&okcsSearchSession=%s",
                    1,
                    true,
                    "asdf"
            ))
        ));
        $this->assertNull($response->error);
    }

    function testGetOkcsDataForChildCategories() {
        $response = json_decode($this->makeRequest(
            '/ci/OkcsAjaxRequest/getOkcsData',
            array('post' => 'categoryId=DEFECT')
        ));
        if ($this->assertIsA($response->items, 'array')) {
            $firstCategory = $response->items[0];
            $this->assertNotNull($firstCategory->recordId);
            $this->assertNotNull($firstCategory->referenceKey);
            $this->assertNotNull($firstCategory->externalType);
            $this->assertEqual($firstCategory->externalType, 'CATEGORY');
        }
    }

    function testGetOkcsDataForSubmitRating() {
        $response = $this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData',
            array('post' => 'surveyRecordID=08202025e4fff4501468a55022f007e82&answerRecordID=08202025e4fff4501468a55022f007e88&contentRecordID=08202025e4fff4501468a55022f007e88&localeRecordID=en_US')
        );
        $response = json_decode($response);
        $this->assertNull($response->error);
    }

    function testGetOkcsDataForSubmitSearchRating() {
        $response = $this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData', array('post' => 'rating=1&feedback=Search was helpful'));
        $response = json_decode($response);
        $this->assertNull($response->error);
    }

    function testBrowsePopularArticles() {
        $filters = '{"channelRecordID":{"value":"SOLUTIONS","key":"channelRecordID","type":"channelRecordID"}, "type":{"value":"popular","key":"type","type":"type"},"limit":{"value":100,"key":"limit","type":"limit"},"pageSize":{"value":10,"key":"pageSize","type":"pageSize"}, "truncate":{"value":200,"key":"truncate","type":"truncate"}}';
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData', array('post' => 'filters='.$filters.'&answerListApiVersion=v1')));
        $this->assertIsA($response->articles, 'array');
        $article = $response->articles[0];
        $this->assertEqual($article->contentType->referenceKey, 'SOLUTIONS');
        $this->assertEqual($article->locale->recordId, 'en_US');
        $this->assertNotNull($article->title);
        $this->assertNotNull($article->answerId);
        $this->assertNotNull($article->documentId);
    }

    function testCreateRecommendation() {
        $filters = '{"caseNumber":"324","comments":"Microsoft introduced an operating environment named Windows as a graphical operating system shell for MS-DOS in response to the growing interest in graphical user interfaces (GUIs).[4] Microsoft Windows came
        to dominate the worlds personal computer market with over market share, overtaking Mac OS, which had been introduced in 1984. However, since 2012, because of the massive growth of smartphones, Windows sells less than Android, which became the most
        popular operating system in 2014, when counting all of the computing platforms each operating system runs on; in 2014, the number of Windows devices sold were less than   of Android devices sold. However, comparisons across different markets are not
        fully relevant; and for personal computers, Windows is still the most popular operating system.Microsoft introduced an operating environment named Windows on November 20, 1985, as a graphical operating system shell for MS-DOS in response to the growing
        interest in graphical user interfaces (GUIs).[4] Microsoft Windows came to dominate the worlds personal computer market with over 90% market share, overtaking Mac OS, which had been introduced in 1984. However, since 2012, because of the massive growth
        of smartphones, Windows sells less than Android, which became the most popular operating system in 2014, when counting all of the computing platforms each operating system runs on; in 2014, the number of Windows devices sold were less than   of Android
        devices sold. However, comparisons across different markets are not fully relevant; and for personal computers, Windows is still the most popular operating system.Microsoft introduced an operating environment named Windows on November 20, 1985, as a
        graphical operating system shell for MS-DOS in response to the growing interest in graphical user interfaces (GUIs).[4] Microsoft Windows came to dominate the worlds personal computer market with over 90% market share, overtaking Mac OS, which had been
        introduced in 1984. However, since 2012, because of the massive growth of smartphones, Windows sells less than Android, which became the most popular operating system in 2014, when counting all of the computing platforms each operating system runs on;
        in 2014, the number of Windows devices sold were less than   of Android devices sold. However, comparisons across different markets are not fully relevant; and for personal computers, Windows is still the most popular operating system.Microsoft
        introduced an operating environment named Windows on November 20, 1985, as a graphical operating system shell for MS-DOS in response to the growing interest in graphical user interfaces (GUIs).[4] Microsoft Windows came to dominate the worlds personal
        computer market with over 90% market share, overtaking Mac OS, which had been introduced in 1984. However, since 2012, because of the massive growth of smartphones, Windows sells less than Android, which became the most popular operating system in
        2014, when counting all of the computing platforms each operating system runs on; in 2014, the number of Windows devices sold were less than  of Android devices sold. However, comparisons across different markets are not fully relevant; and for
        personal computers, Windows is still the most popular operating system.Microsoft introduced an operating environment named Windows on November 20, 1985, as a graphical operating system shell for MS-DOS in response to the growing interest in graphical
        user interfaces (GUIs).[4] Microsoft Windows came to dominate the worlds personal computer market with over 90% market share, overtaking Mac OS, which had been introduced in 1984. However, since 2012, because of the massive growth of smartphones,
        Windows sells less than Android, which became the most popular operating system in 2014, when counting all of the computing platforms each operating system runs on; in 2014, the number of Windows devices sold were less than of Android devices sold.
        However, comparisons across different markets are not fully relevant; and for personal computers, Windows is still the most popular operating system.","title":"windows","contentType":{"recordId":"01209602320f2da0153a80f7c76007f15","referenceKey":"COMPLEXCHANNEL","name":"ComplexChannel"},"priority":"MEDIUM"}';
        $response = $this->makeRequest('/ci/OkcsAjaxRequest/createRecommendation', array('post' => $filters));
        $response = json_decode($response);
        $this->assertEqual($response->success, Config::getMessage(THANK_YOU_TAKING_MAKE_RECOMMENDATION_LBL));
    }

    function testGetOkcsDataForIMContent() {
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getOkcsData', array('post' => 'doc_id=1000001')));
        $this->assertNotNull($response->id);
        $this->assertEqual($response->id, 1000001);
        $this->assertNotNull($response->contents);
        $this->assertNotNull($response->contents->title);
        $this->assertNotNull($response->contents->content);
        $this->assertNotNull($response->contents->locale);
        $this->assertEqual($response->contents->locale, 'en_US');
    }

    function testSendOkcsEmailAnswerLink() {
    $emailData = '{
            "sendTo" => "sender@abc.com",
            "name" => "SenderName",
            "from" => "from@abc.com",
            "answerID" => "1000000",
            "title" => "Sample title",
            "emailHeaderLabel" => "The following answer has been forwarded to you by",
            "emailSenderLabel" => "(Sender address has not been verified)",
            "summaryLabel" => "Summary",
            "answerViewLabel" => "you can view this answer here.",
            "emailAnswerToken" => "ZlVaQ0ZiNUF5U0UwQmVSZUlhOVZMSndsVzNReldDM2pBYzZjWHd4VUhzSmJSMXI4NGVXYzFIU3NCUVlPZ2pGbVoxQVd6QVZ0dHRIMnVzdXFQWDVrV2w1NzBic35Kal9VZ2doUVR_bE5DUHNxNUlwN2RCbThQYlRheHJMMDJ3SjgzU2trNldVUEhFSGhjRzJlcThkcVNHeEZGX0xrVmtPdHcycGRfM2pGWVhUZ0VFdXFqVDdfZDhzQSEh"
        }';
        $response = $this->makeRequest('/ci/OkcsAjaxRequest/sendOkcsEmailAnswerLink', array('post' => $emailData));
        $response = json_decode($response);
        $this->assertNotNull($response);
    }

    function testGetSuggestions() {
        $filters = '{"ssQuery":{"value":"Oracle","key":"ssQuery","type":"ssQuery"}, "suggestionCount":{"value":7,"key":"suggestionCount","type":"suggestionCount"}}';
        $response = json_decode($this->makeRequest(
            '/ci/OkcsAjaxRequest/getSuggestions',
            array('post' => $filters)
        ));

        $this->assertNotNull($response[0]->highlightedTitle);
        $this->assertTrue(Text::stringContains($response[0]->highlightedTitle, 'rn_KASuggestTitle'));
        $this->assertNotNull($response[0]->title);
        $this->assertNotNull($response[0]->answerId);
    }

    function testGetAllLocaleDescriptions() {        
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getAllLocaleDescriptions'));
        $this->assertNotNull($response);
        $this->assertSame('Čeština Česká republika', $response->cs_CZ);
        $this->assertSame('English United States', $response->en_US);
        $this->assertSame('Hebrew Israel', $response->he_IL);
        $this->assertSame('Italiano Italia', $response->it_IT);
    }

    function testGetAllTranslations() {
        $response = json_decode($this->makeRequest('/ci/OkcsAjaxRequest/getAllTranslations', array('post' => 'answerId=1000000')));
        $this->assertNotNull($response);
        foreach($response as $item) {
            $this->assertNotNull($item->answerId);
            $this->assertSame(1, count($item->answerId));
            $this->assertNotNull($item->localeRecordId);
            $this->assertSame(1, count($item->localeRecordId));
        }
    }
}
