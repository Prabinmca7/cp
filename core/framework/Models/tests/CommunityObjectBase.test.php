<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Text,
    RightNow\UnitTest\Fixture as Fixture,
    RightNow\Utils\Config,
    RightNow\Models\CommunityObjectBase;

TestHelper::loadTestedFile(__FILE__);

class CommunityObjectBaseTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\CommunityObjectBase';

    function __construct() {
        parent::__construct();
        $this->model = new FakeSocialObjectBaseModelInstance();
        $this->testingClass = 'FakeSocialObjectBaseModelInstance';
        $this->fixtureInstance = new Fixture();
    }

    function testGetSocialUser(){
        // default behavior
        $this->logIn('useractive1');
        $result = $this->model->getSocialUser();
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($result->result, 'RightNow\Connect\v1_4\CommunityUser');
        $this->assertTrue(count($result->errors) === 0);

        $this->logOut();

        // not logged in - should return response object with proper error attributes
        $result = $this->model->getSocialUser();
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertTrue(count($result->errors) > 0);
        $this->assertIdentical($result->errors[0]->externalMessage, Config::getMessage(USER_IS_NOT_LOGGED_IN_LBL));
        $this->assertIdentical($result->errors[0]->errorCode, CommunityObjectBase::USER_NOT_LOGGED_IN_ERROR_CODE);

        // logged in, no social user association - should return response object with proper error attributes
        $this->logIn('eturner@rightnow.com.invalid');
        $result = $this->model->getSocialUser();
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertTrue(count($result->errors) > 0);
        $this->assertIdentical($result->errors[0]->externalMessage, Config::getMessage(USER_DOES_NOT_HAVE_A_DISPLAY_NAME_LBL));
        $this->assertIdentical($result->errors[0]->errorCode, CommunityObjectBase::USER_HAS_NO_SOCIAL_USER_CODE);
        $this->logOut();

        // modify useractive2 for next test
        $this->logIn('useractive2');
        $userToModify = $this->CI->model('CommunityUser')->get($this->CI->session->getProfileData('socialUserID'))->result;
        $userToModifyOldDisplayName = $userToModify->DisplayName;
        $userToModify->DisplayName = '    '; // getSocialUser calls trim on this value
        $userToModify->save();

        // logged in, social user association, but blank display name - should return response object with proper error attribute
        $result = $this->model->getSocialUser();
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject')  ;
        $this->assertNull($result->result);
        $this->assertTrue(count($result->errors) > 0);
        $this->assertIdentical($result->errors[0]->externalMessage, Config::getMessage(USER_DOES_NOT_HAVE_A_DISPLAY_NAME_LBL));
        $this->assertIdentical($result->errors[0]->errorCode, CommunityObjectBase::USER_HAS_BLANK_SOCIAL_USER_CODE);

        // restore user name
        $userToModify->DisplayName = $userToModifyOldDisplayName;
        $userToModify->save();

        $this->logOut();
    }

    function testCheckAuthorAndObjectPermission(){
        $this->logIn('useractive1');

        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
        ))->result;

        $result = $this->model->checkAuthorAndObjectPermission($question, PERM_SOCIALQUESTION_UPDATE_AUTHORBESTANSWER, PERM_SOCIALQUESTION_UPDATE_MODERATORBESTANSWER);
        $this->assertTrue($result);

        $this->logIn('useractive2');
        $result = $this->model->checkAuthorAndObjectPermission($question, PERM_SOCIALQUESTION_UPDATE_AUTHORBESTANSWER, PERM_SOCIALQUESTION_UPDATE_MODERATORBESTANSWER);
        $this->assertFalse($result);

        $this->logIn('modactive1');
        $result = $this->model->checkAuthorAndObjectPermission($question, PERM_SOCIALQUESTION_UPDATE_AUTHORBESTANSWER, PERM_SOCIALQUESTION_UPDATE_MODERATORBESTANSWER);
        $this->assertTrue($result);

        $this->logOut();
        $result = $this->model->checkAuthorAndObjectPermission($question, PERM_SOCIALQUESTION_UPDATE_AUTHORBESTANSWER, PERM_SOCIALQUESTION_UPDATE_MODERATORBESTANSWER);
        $this->assertFalse($result);
    }

    function testGetQuestionSelectROQL() {
        list($class, $method, $bestAnswerFieldsAttr) = $this->reflect('method:getQuestionSelectROQL', 'bestAnswerFields');
        $instance = $class->newInstanceArgs(array());

        $result = $method->invokeArgs($instance, array('wheres'));
        $this->assertTrue(Text::endsWith($result, 'WHERE wheres '));
        $this->assertTrue(Text::stringContains($result, $bestAnswerFieldsAttr->getValue($instance)));

        $result = $method->invokeArgs($instance, array('wheres', 'limits'));
        $this->assertTrue(Text::endsWith($result, 'WHERE wheres LIMIT limits'));
        $this->assertTrue(Text::stringContains($result, $bestAnswerFieldsAttr->getValue($instance)));

        $result = $method->invokeArgs($instance, array('wheres', null, false));
        $this->assertTrue(Text::endsWith($result, 'WHERE wheres '));
        $this->assertFalse(Text::stringContains($result, $bestAnswerFieldsAttr->getValue($instance)));

        $result = $method->invokeArgs($instance, array('wheres', 'limits', false));
        $this->assertTrue(Text::endsWith($result, 'WHERE wheres LIMIT limits'));
        $this->assertFalse(Text::stringContains($result, $bestAnswerFieldsAttr->getValue($instance)));
    }

    function testGetCommentSelectROQL() {
        $method = $this->getMethod('getCommentSelectROQL');

        $result = $method('wheres');
        $this->assertPattern('/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\)\) AND wheres $/', $result);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentSelectROQL/');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentSelectROQL/modactive1');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentSelectROQL/useractive1');
        $this->assertEqual('', $output);
    }

    function getCommentSelectROQL() {
        $method = $this->getMethod('getCommentSelectROQL');
        $user = Text::getSubstringAfter(get_instance()->uri->uri_string(), 'getCommentSelectROQL/');
        if ($user)
            $this->logIn($user);

        $result = $method('wheres', 'limits');
        if (!$user)
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\)\) AND wheres LIMIT limits$/';
        // if we had a question to pass in, modactive1 would have a different result
        else if ($user === 'modactive1')
            //$patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+\) OR \(c.StatusWithType.StatusType = 29 AND c.CreatedByCommunityUser.ID = \d+\)\) AND wheres LIMIT limits$/';
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\\) OR \(c.StatusWithType.StatusType = 29 AND c.CreatedByCommunityUser.ID = \d+\)\) AND wheres LIMIT limits$/';
        else
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\) OR \(c.StatusWithType.StatusType = 29 AND c.CreatedByCommunityUser.ID = \d+\)\) AND wheres LIMIT limits$/';

        $this->assertPattern($patternToCheck, $result);
        $question = Connect\CommunityQuestion::fetch(2055);
        $result = $method('wheres', 'limits', $question);
        if (!$user)
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\)\) AND wheres LIMIT limits$/';
        else if ($user === 'modactive1')
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+\) OR \(c.StatusWithType.StatusType = 29 AND c.CreatedByCommunityUser.ID = \d+\)\) AND wheres LIMIT limits$/';
        else
            $patternToCheck = '/WHERE \(c\.StatusWithType\.StatusType NOT IN \(\d+,\d+\) OR \(c.StatusWithType.StatusType = 29 AND c.CreatedByCommunityUser.ID = \d+\)\) AND wheres LIMIT limits$/';

        if ($user)
            $this->logOut();
    }

    function testBuildSelectROQL() {
        $method = $this->getMethod('buildSelectROQL');

        $result = $method('', '', '');
        $this->assertSame('SELECT  FROM  WHERE  ', $result);
        $result = $method('*', 'CommunityUser', 'ID < 10', 10);
        $this->assertSame('SELECT * FROM CommunityUser WHERE ID < 10 LIMIT 10', $result);
        $result = $method('*', 'CommunityUser', array('ID < 10', 'ID > 1'));
        $this->assertSame('SELECT * FROM CommunityUser WHERE ID < 10 AND ID > 1 ', $result);
        $result = $method('*', 'CommunityUser', array(false, 'ID < 10', null, 'ID > 1', ''));
        $this->assertSame('SELECT * FROM CommunityUser WHERE ID < 10 AND ID > 1 ', $result);
    }

    function testGetCommentStatusTypeFilters() {
        $method = $this->getMethod('getCommentStatusTypeFilters');

        $result = $method();
        $this->assertIdentical("(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . "," . STATUS_TYPE_SSS_COMMENT_PENDING . "))", $result);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentStatusTypeFilters/useractive1');
        $this->assertEqual('', $output);

        $output = TestHelper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
                . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentStatusTypeFilters/modactive1');
        $this->assertEqual('', $output);
    }

    function getCommentStatusTypeFilters() {
        $method = $this->getMethod('getCommentStatusTypeFilters');
        $user = Text::getSubstringAfter(get_instance()->uri->uri_string(), 'getCommentStatusTypeFilters/');
        $this->logIn($user);

        if ($user === 'modactive1')
            // if we had a question to pass in, modactive1 would have a different result
            //$expectedResult = "(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . ") OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $this->CI->Model('CommunityUser')->get()->result->ID . "))";
            $expectedResult = "(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . "," . STATUS_TYPE_SSS_COMMENT_PENDING . ") OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $this->CI->Model('CommunityUser')->get()->result->ID . "))";
        else
            $expectedResult = "(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . "," . STATUS_TYPE_SSS_COMMENT_PENDING . ") OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $this->CI->Model('CommunityUser')->get()->result->ID . "))";

        $result = $method();
        $this->assertIdentical($expectedResult, $result);

        if ($user === 'modactive1')
            $expectedResult = "(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . ") OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $this->CI->Model('CommunityUser')->get()->result->ID . "))";
        else
            $expectedResult = "(c.StatusWithType.StatusType NOT IN (" . STATUS_TYPE_SSS_COMMENT_DELETED . "," . STATUS_TYPE_SSS_COMMENT_PENDING . ") OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $this->CI->Model('CommunityUser')->get()->result->ID . "))";

        $question = Connect\CommunityQuestion::fetch(2055);
        $result = $method($question);
        $this->assertIdentical($expectedResult, $result);

        $question = $this->assertResponseObject($this->CI->Model('CommunityQuestion')->create(array(
            'CommunityQuestion.Product' => (object) array('value' => 1),
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $result = $method($question);
        $this->assertIdentical($expectedResult, $result);

        $this->logOut();
    }

    function testGetSocialObjectCountsByStatusType() {
        $method = $this->getMethod('getSocialObjectCountsByStatusType');
        $this->logIn('usermoderator');

        //Social Question tests
        $result = $method('CommunityQuestion')->result;
        $this->assertNull($result[STATUS_TYPE_SSS_QUESTION_DELETED], "Deleted questions count should not exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_QUESTION_ACTIVE], "Active questions count doesn't  exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_QUESTION_SUSPENDED], "Susupended questions count doesn't exist");

        //Social Comment tests
        $result = $method('CommunityComment')->result;
        $this->assertNull($result[STATUS_TYPE_SSS_COMMENT_DELETED], "Deleted comments count should not exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_COMMENT_ACTIVE], "Active comments count doesn't  exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_COMMENT_SUSPENDED], "Susupended comments count doesn't exist");

        //Ensure that the method doesn't count comments whose parent social questions have a deleted status
        //Get initial active question and comment counts
        $questionCounts = $method('CommunityQuestion')->result;
        $initialQuestionCount = $questionCounts[STATUS_TYPE_SSS_QUESTION_ACTIVE];
        $commentCounts = $method('CommunityComment')->result;
        $initialCommentCount = $commentCounts[STATUS_TYPE_SSS_COMMENT_ACTIVE];
        //Create a deleted question with active comments
        $this->fixtureInstance->make('QuestionDeletedWithCommentActive');
        //Get final count for active questions and comments
        $questionCounts = $method('CommunityQuestion')->result;
        $finalQuestionCount = $questionCounts[STATUS_TYPE_SSS_QUESTION_ACTIVE];
        $commentCounts = $method('CommunityComment')->result;
        $finalCommentCount = $commentCounts[STATUS_TYPE_SSS_COMMENT_ACTIVE];
        //Deleted question shouldn't affect the question count
        $this->assertEqual($finalQuestionCount, $initialQuestionCount);
        //The comment associated with deleted question should also not be considered, hence the count shouldn't change
        $this->assertEqual($finalCommentCount, $initialCommentCount);
        $this->logOut();
        $this->fixtureInstance->destroy();

        //Social User tests
        $result = $method('CommunityUser')->result;
        $this->assertNull($result[STATUS_TYPE_SSS_USER_DELETED], "Deleted users count should not exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_USER_ACTIVE], "Active users count doesn't  exist");
        $this->assertNotNull($result[STATUS_TYPE_SSS_USER_SUSPENDED], "Susupended users count doesn't exist");

        //Count Tests for different date
        $recentCount = $method('CommunityQuestion', 'hour', -24)->result;
        $oneYearCount = $method('CommunityQuestion', 'day', -365)->result;
        $this->assertNotEqual($recentCount, $oneYearCount, 'Count should be different');

        //Invalid date range (future date)
        $recentCount = $method('CommunityComment', 'hour', 24)->result;
        $this->assertTrue(empty($recentCount), 'Count for future date should be empty');
        $oneYearCount = $method('CommunityComment', 'day', -365)->result;
        $this->assertNotEqual($recentCount, $oneYearCount, 'Count should be different');
    }

    function testGetSocialObjectMetadataMapping() {

        $this->logIn('usermoderator');

        //test - metadata about all social object is returned when no argument is passed
        $response = $this->model->getSocialObjectMetadataMapping()->result;
        $this->assertSame(true, isset($response['CommunityUser']));
        $this->assertSame(true, isset($response['CommunityQuestion']));
        $this->assertSame(true, isset($response['CommunityComment']));

        //test - metadata about one social object is returned when object name is passed
        $response = $this->model->getSocialObjectMetadataMapping('CommunityQuestion')->result;
        $this->assertSame(true, isset($response));
        $this->assertSame(true, isset($response['allowed_actions']));
        $this->assertSame(false, isset($response['CommunityComment']));
        $this->assertSame(false, isset($response['CommunityUser']));

        //test - for Social Comment
        $response = $this->model->getSocialObjectMetadataMapping('CommunityComment', 'allowed_actions')->result;
        $this->assertSame(1, count($response['suspend']));
        $this->assertNull($response['move'], "Move action is set for Comment");

        //tes -  for Social Question
        $response = $this->model->getSocialObjectMetadataMapping('CommunityQuestion', 'allowed_actions')->result;
        $this->assertNotNull($response['move'], "Move action is not set for Question");
        $this->assertNotNull($response['reset_flags'], "Reset flag action is not set for Question");

        //test -  for Social User
        $response = $this->model->getSocialObjectMetadataMapping('CommunityUser', 'allowed_actions')->result;
        $this->assertNull($response['move'], "Move action is set for User");
        $this->assertNull($response['reset_flags'], "Reset flag action is set for User");
        $this->assertNotNull($response['archive'], "Archive action is not set for User");

        //test - Social permission
        $response = $this->model->getSocialObjectMetadataMapping('CommunityUser', 'allowed_actions')->result;
        $this->assertNull($response['delete'], "Delete action is set for User");

        $response = $this->model->getSocialObjectMetadataMapping('CommunityComment', 'allowed_actions')->result;
        $this->assertNotNull($response['suspend_user'], "Restore user action is not set for comment");
        $this->assertNotNull($response['restore_user'], "Suspend user action is not set for comment");

        $response = $this->model->getSocialObjectMetadataMapping('CommunityQuestion', 'allowed_actions')->result;
        $this->assertNotNull($response['suspend_user'], "Restore user action is not set for question");
        $this->assertNotNull($response['restore_user'], "Suspend user action is not set for question");
        $this->logOut();
    }

    function testGetRecentSocialObjectCountsByDateTime() {
        $this->logIn('slatest');

        //CommunityQuestion tests using 'day' as interval
        $countDate = $this->model->getRecentSocialObjectCountsByDateTime('CommunityQuestion')->result;
        $this->assertEqual(7, count($countDate), 'Default date for last 7 day is incorrect');
        end($countDate);
        // account for time zone issues which could cause the day to advance late in the evening
        if (date('Y-m-d') === key($countDate))
            $this->assertEqual(date('Y-m-d'), key($countDate), 'Start date is not correct');
        else
            $this->assertEqual(date('Y-m-d', strtotime('+1 day')), key($countDate), 'Start date is not correct');

        //CommunityComment tests using 'month' as interval
        $countDate = $this->model->getRecentSocialObjectCountsByDateTime('CommunityComment', 'month', -4)->result;
        $this->assertEqual(4, count($countDate), '4 element should exist');

        //SocialUsert tests using invalid interval
        $countDate = $this->model->getRecentSocialObjectCountsByDateTime('CommunityUser', 'invalid', -6);
        $this->assertNull($countDate->result, 'Should be null');
        $this->assertNotNull($countDate->error, 'Should not be null');

        //SocialUsert tests using valid intervals
        $countDate = $this->model->getRecentSocialObjectCountsByDateTime('CommunityUser', 'month', -12)->result;
        $this->assertEqual(12, count($countDate), '12 element should exist');

        $countDate = $this->model->getRecentSocialObjectCountsByDateTime('CommunityUser', 'month', -8)->result;
        $this->assertEqual(8, count($countDate), '8 element should exist');
        end($countDate);
        // account for possible rollover into next month
        $this->assertTrue(date('Y-m').'-01' === key($countDate) || date('Y-m', time() + (24 * 60 * 60)).'-01' === key($countDate), 'End month is not correct');

        $this->logOut();
    }

    function testGetStatusTypeFromStatus() {
        $this->logIn('slatest');

        //Question status type tests
        $this->assertEqual(STATUS_TYPE_SSS_QUESTION_SUSPENDED, $this->CI->model('CommunityQuestion')->getStatusTypeFromStatus(30), 'Incorrect question suspend status type');//suspend
        $this->assertEqual(STATUS_TYPE_SSS_QUESTION_ACTIVE, $this->CI->model('CommunityQuestion')->getStatusTypeFromStatus(29), 'Incorrect question active status type');//active
        $this->assertEqual(STATUS_TYPE_SSS_QUESTION_DELETED, $this->CI->model('CommunityQuestion')->getStatusTypeFromStatus(31), 'Incorrect question deleted status type');//deleted
        $this->assertEqual(null,  $this->CI->model('CommunityQuestion')->getStatusTypeFromStatus('move'), 'Status type should be null for move');//move

        //Comment status type tests
        $this->assertEqual(STATUS_TYPE_SSS_COMMENT_SUSPENDED, $this->CI->model('CommunityComment')->getStatusTypeFromStatus(34), 'Incorrect comment suspend status type');//suspend
        $this->assertEqual(STATUS_TYPE_SSS_COMMENT_ACTIVE, $this->CI->model('CommunityComment')->getStatusTypeFromStatus(33), 'Incorrect comment active status type');//active
        $this->assertEqual(STATUS_TYPE_SSS_COMMENT_DELETED, $this->CI->model('CommunityComment')->getStatusTypeFromStatus(35), 'Incorrect comment deleted status type');//deleted
        $this->assertEqual(null,  $this->CI->model('CommunityComment')->getStatusTypeFromStatus('reset_falgs'), 'Status type should be null for reset_flags');//reset_falgs

        //User status type tests
        $this->assertEqual(STATUS_TYPE_SSS_USER_SUSPENDED, $this->CI->model('CommunityUser')->getStatusTypeFromStatus(39), 'Incorrect user suspend status type');//suspend
        $this->assertEqual(STATUS_TYPE_SSS_USER_ACTIVE, $this->CI->model('CommunityUser')->getStatusTypeFromStatus(38), 'Incorrect user active status type');//active
        $this->assertEqual(STATUS_TYPE_SSS_USER_DELETED, $this->CI->model('CommunityUser')->getStatusTypeFromStatus(40), 'Incorrect user active status type');//deleted
        $this->assertEqual(STATUS_TYPE_SSS_USER_ARCHIVE, $this->CI->model('CommunityUser')->getStatusTypeFromStatus(41), 'Incorrect user archived status type');//archive

        $this->logOut();
    }
}

class FakeSocialObjectBaseModelInstance extends \RightNow\Models\CommunityObjectBase{
    public function checkAuthorAndObjectPermission(Connect\RNObject $rnObject, $authorPermissionID, $alternatePermissionID){
        return parent::checkAuthorAndObjectPermission($rnObject, $authorPermissionID, $alternatePermissionID);
    }
}
