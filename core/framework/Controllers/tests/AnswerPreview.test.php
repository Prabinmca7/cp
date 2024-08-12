<?php

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class AnswerPreviewTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\AnswerPreview';

	function __construct() {
		$this->answerID = 1;
        //will need to be changed to an actual versionID
        $this->versionID = 1;
	}

    function testFullAnswerData() {
        $invoke = $this->getMethod('_fullAnswerData');

        // Invalid answerID
        $result = $invoke(1234567890, array(1, 3));
        $this->assertTrue(array_key_exists('error', $result));

        // Invalid access levels
        $result = $invoke($this->answerID, array(0));
        $this->assertTrue(array_key_exists('error', $result));

        $result = $invoke($this->answerID, array(1, 3));
        $this->assertIsA($result, 'array');
        $this->assertIsA($result['answer'], 'RightNow\Connect\Knowledge\v1\AnswerContent');

        // We need an ANSWER_TYPE_ATTACHMENT added to our dev site data
        //$result = $invoke($fileAttachmentTypeAnswerID, array(1, 3));
        //$this->assertEqual('http://scott.ruby.rightnowtech.com/cgi-bin/scott.cfg/php/admin/fattach_get.php?p_sid=&p_tbl=9&p_id=1&p_created=', $result['location']);
    }

    function testFullAnswerVersionData() {
        $invoke = $this->getMethod('_fullAnswerVersionData');

        // Invalid versionID
        $result = $invoke(1234567890, array(1, 3));
        $this->assertTrue(array_key_exists('error', $result));

        // Invalid access levels
        $result = $invoke($this->versionID, array(0));
        $this->assertTrue(array_key_exists('error', $result));

        $result = $invoke($this->versionID, array(1, 3));
        $this->assertIsA($result, 'array');
        $this->assertIsA($result['answer'], 'RightNow\Connect\Knowledge\v1\AnswerContent');
    }

    function testQuickAnswerData() {
        $invoke = $this->getMethod('_quickAnswerData');
        $summary = "Answer summary";
        $description = "Answer description";
        $solution = "Answer solution";
        $result = $invoke($summary, $description, $solution);
        $this->assertEqual($summary, $result['summary']);
        $this->assertEqual($description, $result['description']);
        $this->assertEqual($solution, $result['solution']);
        $this->assertTrue(array_key_exists('baseurl', $result));
    }

    function testFullWithUrlRedirect() {
        $headers = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/callFull', array('justHeaders' => true));
        $this->assertStringContains($headers, 'Location: http://en.wikipedia.org/wiki/Sweet_track');
        $this->assertStringContains($headers, '302 Moved Temporarily');
    }

    function callFull() {
        $invoke = $this->getMethod('full');
        $invoke(50);
    }
}
