<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Sql\Report as Sql;

class ReportSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\Report';

    function testViewUtilsInclusionAndConstant(){
        $includedFiles = get_included_files();
        $foundViewUtils = false;
        foreach($includedFiles as $file){
            if(strpos($file, 'view_utils.phph') !== false){
                $foundViewUtils = true;
            }
        }
        $this->assertTrue($foundViewUtils);
        $this->assertIdentical('answers.special_settings', Sql::ANSWERS_SPECIAL_SETTINGS_FILTER_NAME);
    }

    function testGetSimilarSearches()
    {
        $method = $this->getMethod('getSimilarSearches');
        $response = $method('iPhone');
        $this->assertTrue(is_array($response));
    }

    function testGetTopicWords()
    {
        $method = $this->getMethod('getTopicWords');

        $response = $method('');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 1);
        $this->assertIsA($response[0], 'array');
        $this->assertTrue(array_key_exists('url', $response[0]));
        $this->assertTrue(array_key_exists('title', $response[0]));
        $this->assertTrue(array_key_exists('text', $response[0]));
        $this->assertTrue(array_key_exists('icon', $response[0]));

        $response = $method('iPhone');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 3);
        $this->assertIsA($response[0], 'array');
        $this->assertIsA($response[1], 'array');
        $this->assertIsA($response[2], 'array');
        $this->assertTrue(array_key_exists('url', $response[0]));
        $this->assertTrue(array_key_exists('title', $response[0]));
        $this->assertTrue(array_key_exists('text', $response[0]));
        $this->assertTrue(array_key_exists('icon', $response[0]));

        $response = $method('iPhone nonPriorityWord');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 3);

        $response = $method('nonPriorityWord iPhone nonPriorityWord2');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 3);

        $response = $method('android');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 1);

        $response = $method('iPhone android');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 3);

        $response = $method('nonPriorityWord android iPhone nonPriorityWord2');
        $this->assertIsA($response, 'array');
        $this->assertTrue(count($response) === 3);
    }

    function testGetSuggestedSearch(){
       \Rnow::updateConfig("SEARCH_SUGGESTIONS_DISPLAY", 3);

       $response = json_decode($this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Report/getSuggestedSearch/iPhone/" . HM_PRODUCTS), true);
       $this->assertIsA($response, 'array');
       $this->assertIdentical(3, count($response));
       $this->assertIsA($response[0], 'array');
       $this->assertIsA($response[1], 'array');
       $this->assertIsA($response[2], 'array');
       $this->assertTrue(array_key_exists('label', $response[0]));
       $this->assertTrue(array_key_exists('id', $response[0]));

       $response = json_decode($this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Report/getSuggestedSearch/Android/" . HM_PRODUCTS), true);
       $this->assertIsA($response, 'array');
       $this->assertIdentical(1, count($response));
       $this->assertIsA($response[0], 'array');
       $this->assertTrue(array_key_exists('label', $response[0]));
       $this->assertTrue(array_key_exists('id', $response[0]));

       $response = json_decode($this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Report/getSuggestedSearch/" . HM_PRODUCTS), true);
       $this->assertNull($response);

       $response = json_decode($this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Report/getSuggestedSearch/asdf/" . HM_PRODUCTS), true);
       $this->assertNull($response);

       $response = json_decode($this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Report/getSuggestedSearch/Service/" . HM_CATEGORIES), true);
       $this->assertIsA($response, 'array');
       $this->assertIdentical(1, count($response));
       $this->assertIsA($response[0], 'array');
       $this->assertTrue(array_key_exists('label', $response[0]));
       $this->assertTrue(array_key_exists('id', $response[0]));

       \Rnow::updateConfig("SEARCH_SUGGESTIONS_DISPLAY", 0);
   }

    function testLoadHTDigLibrary(){
        $method = $this->getMethod('loadHTDigLibrary');
        $method();
        $includedFiles = get_included_files();
        $foundHTDig = false;
        foreach($includedFiles as $file){
            if(strpos($file, 'htdig.phph') !== false){
                $foundHTDig = true;
            }
        }
        $this->assertTrue($foundHTDig);
    }
}
