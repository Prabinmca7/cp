<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as Connect;

class FileAttachmentSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\FileAttachment';

    function testGet() {
        $method = $this->getMethod('get');
        $result = $method('', '');
        $this->assertFalse($result);
        $result = $method('fosfsdfo', 'basdfr');
        $this->assertFalse($result);
        $result = $method('!=!=', 'sdf');
        $this->assertFalse($result);
        $result = $method(30);
        $this->assertIsA($result, 'array');
        $this->assertTrue(array_key_exists('id', $result));
        $this->assertTrue(array_key_exists('created', $result));
        $this->assertTrue(array_key_exists('type', $result));
        $this->assertTrue(array_key_exists('size', $result));
        $this->assertTrue(array_key_exists('table', $result));
        $this->assertTrue(array_key_exists('contentType', $result));
        $this->assertTrue(array_key_exists('userFileName', $result));
        $this->assertTrue(array_key_exists('localFileName', $result));
    }

    function testGetIDFromCreatedTime() {
        $method = $this->getMethod('getIDFromCreatedTime');
        $result = $method(0, 0, 0);
        $this->assertIsA($result, 'boolean');
    }

    function testIsFileAttachmentsAnswerAccessible() {
        $method = $this->getMethod('isFileAttachmentsAnswerAccessible');

        // perform some tests while logged in
        $cookies = Helper::logInUser();
        $this->assertTrue($method(1));

        // The only access level 'slatest' has is 'Special', which is enduser visible. Therefore, we cannot validate
        // that a logged in user with the appropriate SLA can view an answer with a non-visible access level. Currently,
        // all that we can determine is that a logged in user without the correct SLA cannot view an answer with a
        // non-visible access level.
        $answer = Connect\Answer::fetch(2);
        $this->setAnswerAccessLevel($answer, 4); // set to non-visible access level: 4 (Platinum)
        $this->assertFalse($method($answer->ID));

        // perform same tests without being logged in
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
        $this->assertTrue($method(1));
        // answer with non-visible access level should not be accessible while not logged in
        $this->assertFalse($method($answer->ID));

        // set access level back to 1 (Everyone)
        $answer = Connect\Answer::fetch(2);
        $this->setAnswerAccessLevel($answer, 1);
        $this->assertTrue($method($answer->ID));

        // @@@ QA 190917-000132 - unit test for attachments when access level is Everyone but answer is Private
        $answer = Connect\Answer::fetch(2);
        $this->setAnswerStatusType($answer, STATUS_TYPE_PRIVATE);
        $this->assertFalse($method($answer->ID));
        // cleanup
        $this->setAnswerStatusType($answer, STATUS_TYPE_PUBLIC);

    }

    // @@@ QA 130610-000129 – Unit testcases for isMetaAnswerAccessible
    function testIsMetaAnswerAccessible() {
        $method = $this->getMethod('isMetaAnswerAccessible');

        // perform some tests while logged in
        $cookies = Helper::logInUser();
        $m_id1 = sql_get_int("SELECT m_id FROM answers WHERE a_id = 1");
        $this->assertTrue($method($m_id1));

        // The only access level 'slatest' has is 'Special', which is enduser visible. Therefore, we cannot validate
        // that a logged in user with the appropriate SLA can view an sibling attachment  with a non-visible access level. Currently,
        // all that we can determine is that a logged in user without the correct SLA cannot view an sibling attachment   with a
        // non-visible access level.
        $answer = Connect\Answer::fetch(2);
        $m_id2 = sql_get_int("SELECT m_id FROM answers WHERE a_id = 2");
        $this->setAnswerAccessLevel($answer, 4); // set to non-visible access level: 4 (Platinum)
        $this->assertFalse($method($m_id2));

        // perform same tests without being logged in
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
        $this->assertTrue($method($m_id1));
        // answer with non-visible access level should not be accessible while not logged in
        $this->assertFalse($method($m_id2));

        // set access level back to 1 (Everyone)
        $answer = Connect\Answer::fetch(2);
        $this->setAnswerAccessLevel($answer, 1);
        $this->assertTrue($method($m_id2));
    }

    private function setAnswerAccessLevel(&$answer, $accessID) {
        $answer->AccessLevels->offsetUnset(0);
        $answer->AccessLevels[0] = new Connect\AccessLevel();
        $answer->AccessLevels[0]->ID = $accessID;
        $answer->save();
        Connect\ConnectAPI::commit();
    }

    /**
     * Known statuses are STATUS_TYPE_PUBLIC (4) and STATUS_TYPE_PRIVATE (5)
     */
    private function setAnswerStatusType(&$answer, $status) {
        $answer->StatusWithType->Status->ID = $status;
        $answer->save();
        Connect\ConnectAPI::commit();
    }
}
