<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text;

class TestRecentlyViewedContent extends WidgetTestCase {
    public $testingWidget = "standard/discussion/RecentlyViewedContent";

    function __construct() {
        parent::__construct();
    }

    function getData($attributes = array()) {
        $instance = $this->createWidgetInstance($attributes);
        return $this->getWidgetData();
    }

    function setUp() {
        $this->testDataWithInvalidParams = array(
                array('a_id' => 52),
                array('a_id' => 54),
                array('qid' => 554),
                array('invalidParam' => 21),
                array('a_id' => 59),
                array('qid' => 537),
                array('invalidParam' => 21),
                array('a_id' => 57),
                array('a_id' => 59),);

        $this->testDataWithoutInvalidParams = array(
                array('a_id' => 52),
                array('a_id' => 54),
                array('qid' => 554),
                array('a_id' => 59),
                array('qid' => 537),
                array('a_id' => 57),
                array('a_id' => 59),);

        $this->testDataWithoutDuplicateEntries = array(
                array('a_id' => 52),
                array('a_id' => 54),
                array('qid' => 554),
                array('invalidParam' => 21),
                array('a_id' => 59),
                array('qid' => 537),
                array('a_id' => 57),);

        $this->expectedFinalTestData = array(
                array('a_id' => 57),
                array('qid' => 537),
                array('a_id' => 59),
                array('qid' => 554),);

        $this->fakeSession = new \RightNow\Libraries\SessionData(array('u' => $this->testDataWithInvalidParams));
    }

    function tearDown() {
        $this->restoreUrlParameters();
    }

    function testFindUrlParamsInPage() {
        $this->getData();
        $findUrlParamsInPage = $this->getWidgetMethod('findUrlParamsInPage');

        //Valid content_type valid ID
        $urlParam = array('a_id' => 52);
        $this->addUrlParameters($urlParam);
        $this->assertEqual($findUrlParamsInPage(), $urlParam);
        $this->restoreUrlParameters();

        //Invalid content_type
        $urlParam = array('invalidParam' => 52);
        $this->addUrlParameters($urlParam);
        $this->assertNull($findUrlParamsInPage());
        $this->restoreUrlParameters();

        //Invalid id
        $urlParam = array('qid');
        $this->addUrlParameters($urlParam);
        $this->assertNull($findUrlParamsInPage());
        $this->restoreUrlParameters();

        $this->getData(array('content_type' => 'questions'));
        $findUrlParamsInPage = $this->getWidgetMethod('findUrlParamsInPage');

        //Valid content_type valid ID
        $urlParam = array('qid' => 554);
        $this->addUrlParameters($urlParam);
        $this->assertEqual($findUrlParamsInPage(), $urlParam);
        $this->restoreUrlParameters();

        //Invalid content_type
        $urlParam = array('a_id' => 52);
        $this->addUrlParameters($urlParam);
        $this->assertNull($findUrlParamsInPage());
        $this->restoreUrlParameters();
    }

    function testGetContentFromIDs() {
        $this->getData();
        $getContentFromIDsMethod = $this->getWidgetMethod('getContentFromIDs');

        //Valid content_type, valid IDs
        $results = $getContentFromIDsMethod(array(
            array('a_id' => 55),
            array('qid' => 2038),
            array('qid' => 2037),
            array('a_id' => 57),
        ));

        foreach($results as &$result) {
            if(Text::stringContains($result['url'], 'session/')) {
                $result['url'] = Text::getSubstringBefore($result['url'], 'session/');
            }
            $result['url'] = trim($result['url']);
            if(!Text::endsWith($result['url'], '/')) {
                $result['url'] = $result['url'] . '/';
            }
        }

        $this->assertEqual($results, array(
            array(
                'type' => 'AnswerContent',
                'url'  => '/app/answers/detail/a_id/55/',
                'text' => 'iPhone not recognized in iTunes for Windows'
            ),
            array(
                'type' => 'CommunityQuestion',
                'url'  => '/app/social/questions/detail/qid/2038/',
                'text' => '[ACTIVE] [AUTHOR BA] [MOD BA] Question by...'
            ),
            array(
                'type' => 'CommunityQuestion',
                'url'  => '/app/social/questions/detail/qid/2037/',
                'text' => '[ACTIVE] [AUTHOR BA] [MOD BA] Question by...'
            ),
            array(
                'type' => 'AnswerContent',
                'url'  => '/app/answers/detail/a_id/57/',
                'text' => 'iPhone: Charging the battery'
            ),
        ));

        $results = $getContentFromIDsMethod(array());
        $this->assertEqual($results, array());

        //Valid content_type, invalid ID,
        $results = $getContentFromIDsMethod(array(
            array('a_id' => 119098675309),
            array('qid'  => 867134455309),
            array('qid'  => 180089089087),
            array('a_id' => 500000776871),
        ));
        $this->assertEqual($results, array());

        //Valid content_type, 2 valid asnwer IDs and 2 invalid question IDs.
        $results = $getContentFromIDsMethod(array(
            array('a_id' => 55),
            array('a_id' => 57),
            array('qid' => 180089089087),
            array('qid' => 867134455309),
        ));
        $this->assertEqual(count($results), 2);

         //Valid content_type, 3 invalid asnwer IDs and 1 valid question ID.
        $results = $getContentFromIDsMethod(array(
            array('a_id' => 180089089087),
            array('a_id' => 867134455309),
            array('a_id' => 119098675309),
            array('qid' => 2038),
        ));
        $this->assertEqual(count($results), 1);

        //Valid content_type, 1 invalid asnwer ID, 2 valid asnwer ID and 1 valid question ID.
        $results = $getContentFromIDsMethod(array(
            array('a_id' => 55),
            array('a_id' => 57),
            array('a_id' => 119098675309),
            array('qid' => 2038),
        ));
        $this->assertEqual(count($results), 3);

        //Invalid content_type
        $results = $getContentFromIDsMethod(array(
            array('( ͡° ͜ʖ ͡°)' => 119098675309)
        ));
        $this->assertEqual($results, array());
    }

    function testTruncateText() {
        $this->getData(array('truncate_size' => 999999));
        $truncateTextMethod = $this->getWidgetMethod('truncateText');

        $input = 'A kiss gifts an ambient answer. How does a covering accident trip outside the symptom? Why does every preliminary defense introduce the responsible evil? The economy recycles a nature. The internal rod dices a frog without the widest flavor.';
        $result = $truncateTextMethod($input);
        $this->assertEqual($result, $input);

        $this->getData(array('truncate_size' => 0));
        $truncateTextMethod = $this->getWidgetMethod('truncateText');

        $result = $truncateTextMethod($input);
        $this->assertEqual($result, $input);

        $this->getData(array('truncate_size' => 32));
        $truncateTextMethod = $this->getWidgetMethod('truncateText');

        $result = $truncateTextMethod($input);
        $this->assertEqual($result, 'A kiss gifts an ambient answer...');

        $input = 'Encode these! " ? <';
        $result = $truncateTextMethod($input);
        $this->assertEqual($result, 'Encode these! &quot; ? &lt;');
    }

    function testRemoveUnusedUrlParams() {
        $this->getData();
        $removeUnusedUrlParams = $this->getWidgetMethod('removeUnusedUrlParams');

        $result = $removeUnusedUrlParams($this->testDataWithInvalidParams);
        $this->assertEqual($result, $this->testDataWithoutInvalidParams);

    }

    function testGetArrayOfUniqueEntries() {
        $this->getData();
        $getArrayOfUniqueEntries = $this->getWidgetMethod('getArrayOfUniqueEntries');

        $result = $getArrayOfUniqueEntries($this->testDataWithInvalidParams);
        $this->assertEqual($result, $this->testDataWithoutDuplicateEntries);
    }
}
