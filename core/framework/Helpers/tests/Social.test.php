<?php

use RightNow\Helpers\SocialHelper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialHelperTest extends CPTestCase {
    public $testingClass = 'RightNow\Helpers\SocialHelper';

    function testGetDefaultAvatar() {
        $method = $this->getMethod('getDefaultAvatar');
        $avatarInfo = $method('Jim');
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('J', $avatarInfo['text']);
        $this->assertSame(3, $avatarInfo['color']);

        $avatarInfo = $method('Jimmy');
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('J', $avatarInfo['text']);
        $this->assertSame(0, $avatarInfo['color']);
    }

    function testGetDefaultAvatarReturnsQuestionMarkIfDisplayNameIsBlank() {
        $method = $this->getMethod('getDefaultAvatar');
        $avatarInfo = $method(null);
        $this->assertSame('?', $avatarInfo['text']);
        $avatarInfo = $method('');
        $this->assertSame('?', $avatarInfo['text']);
        $avatarInfo = $method('', false);
        $this->assertSame('?', $avatarInfo['text']);
    }

    function testGetDefaultAvatarReturnsBangIfInactive() {
        $getDefaultAvatar = $this->getMethod('getDefaultAvatar');
        $avatarInfo = $getDefaultAvatar('Bill', false);
        $this->assertSame('!', $avatarInfo['text']);
    }

    function testGetDefaultAvatarReturnsFisrtAlphanumeric() {
        $method = $this->getMethod('getDefaultAvatar');
        $avatarInfo = $method('>Jim');//html entity
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('J', $avatarInfo['text']);
        $avatarInfo = $method('&gt;Jim'); //html entity encoded
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('J', $avatarInfo['text']);
        $avatarInfo = $method('&*^$%^##$Jim#&*^#*&^#&*^AB'); //special characters and alphanumeric
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('J', $avatarInfo['text']);
        $avatarInfo = $method('<&^^&'); //Only special characters
        $this->assertIsA($avatarInfo, 'array');
        $this->assertSame('<', $avatarInfo['text']);
    }

    function testUserProfileUrl() {
        $method = $this->getMethod('userProfileUrl');
        $publicProfileUrl = '/app/public_profile/user';
        $sessionString = \RightNow\Utils\Url::sessionParameter();

        $this->assertEqual("$publicProfileUrl/1/$sessionString", $method(1));
        $this->assertEqual("$publicProfileUrl//$sessionString", $method(null));
    }

    function testGetStatusLabels () {
        $method = $this->getMethod('getStatusLabels');
        $statusInfo = $method('CommunityQuestion');
        $this->assertIsA($statusInfo, 'array', 'Question Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'Question Should have at least one status');
        $status = get_instance()->model('CommunityQuestion')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_QUESTION_DELETED])]), 'Question Deleted status should not be in array');

        $statusInfo = $method('CommunityComment');
        $this->assertIsA($statusInfo, 'array', 'Comment Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'Comment Should have at least one status');
        $status = get_instance()->model('CommunityComment')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_COMMENT_DELETED])]), 'Comment Deleted status should not be in array');

        $statusInfo = $method('CommunityUser');
        $this->assertIsA($statusInfo, 'array', 'User Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'User Should have at least one status');
        $status = get_instance()->model('CommunityUser')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_USER_DELETED])]), 'User Deleted status should not be in array');

        $statusInfo = $method('CommunityQuestion', array(STATUS_TYPE_SSS_QUESTION_PENDING));
        $this->assertIsA($statusInfo, 'array', 'Question Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'Question Should have at least one status');
        $status = get_instance()->model('CommunityQuestion')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_QUESTION_PENDING])]), 'Question Pending status should not be in array');

        $statusInfo = $method('CommunityComment', array(STATUS_TYPE_SSS_COMMENT_PENDING));
        $this->assertIsA($statusInfo, 'array', 'Comment Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'Comment Should have at least one status');
        $status = get_instance()->model('CommunityComment')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_COMMENT_PENDING])]), 'Comment Pending status should not be in array');

        $statusInfo = $method('CommunityUser', array(STATUS_TYPE_SSS_USER_PENDING));
        $this->assertIsA($statusInfo, 'array', 'User Statuses should be an array');
        $this->assertTrue(count($statusInfo) > 0, 'User Should have at least one status');
        $status = get_instance()->model('CommunityUser')->getMappedSocialObjectStatuses()->result;
        $this->assertNull(($statusInfo[key($status[STATUS_TYPE_SSS_USER_PENDING])]), 'User Pending status should not be in array');
    }

    function testGetFlagTypeLabels () {
        $method = $this->getMethod('getFlagTypeLabels');
        $flagInfo = $method('CommunityQuestion');
        $this->assertIsA($flagInfo, 'array', 'Question Flags should be an array');
        $this->assertTrue(count($flagInfo) > 0, 'Question should have at least one flag defined');

        $flagInfo = $method('CommunityComment');
        $this->assertIsA($flagInfo, 'array', 'Comment Flags should be an array');
        $this->assertTrue(count($flagInfo) > 0, 'Comment should have at least one flag defined');
    }

    function testFormatListAttribute () {
        $method = $this->getMethod('formatListAttribute');
        $dateFilterInfo = $method('5 > All, 1 > Last 24 hours, 2 > Last 7 days, 3 > Last 30 days, 4 > Last 365 days');
        $this->assertIsA($dateFilterInfo, 'array', 'Date filters should be an array');
        $this->assertTrue(count($dateFilterInfo) > 0, 'Date filters should have at least one element');
        $this->assertTrue(key($dateFilterInfo) === 5, 'First key should be 5');

        $dateFilterInfo = $method('5> All, something');
        $this->assertIsA($dateFilterInfo, 'array', 'Date filters should be an array');
        $this->assertTrue(count($dateFilterInfo) === 0, 'Date filters should be empty');
    }

    function testDefaultAvatarArgs() {
        $method = $this->getMethod('defaultAvatarArgs');

        // Test default args for an active user
        $userName = 'useractive1';
        $this->logIn($userName);
        $user = get_instance()->model('CommunityUser')->get()->result;
        $args = $method($user);
        $this->assertEqual($userName, $args['displayName']);
        $this->assertEqual('U', $args['defaultAvatar']['text']);
        $this->assertTrue($args['isActive']);
        $this->assertEqual('rn_Medium', $args['className']);
        $this->assertEqual('48', $args['size']);
        $this->assertEqual(false, $args['hideDisplayName']);

        // Test overrides
        $args = $method($user, array('title' => null, 'newKey' => 'new'));
        $this->assertNull($args['title']);
        $this->assertEqual('new', $args['newKey']);
        $this->assertEqual('48', $args['size']);

        // Avatar size is honored.
        $args = $method($user, array('size' => 'xlarge'));
        $this->assertEqual('160', $args['size']);
        $this->assertEqual('rn_XLarge', $args['className']);

        // Avatar size defaults to 'medium' for any invalid size.
        $args = $method($user, array('size' => 'hopeless'));
        $this->assertEqual('48', $args['size']);
        $this->assertEqual('rn_Medium', $args['className']);

        // suspended user
        $this->logIn('usersuspended');
        $user = get_instance()->model('CommunityUser')->get()->result;
        $args = $method($user);
        $this->assertEqual('[inactive]', $args['displayName']);
        $this->assertEqual('!', $args['defaultAvatar']['text']);
        $this->assertFalse($args['isActive']);

        $this->logOut();
    }

    function testDefaultAvatarArgsReturnsFileNotExists() {
        $defaultAvatarArgs = $this->getMethod('defaultAvatarArgs');
        $emptyTabularUser = (object) array(
            'ID' => 10,
            'DisplayName' => 'test',
            'AvatarURL' => 'https://' . $_SERVER['SERVER_NAME'] . '/euf/assets/images/avatar_library/display/everyone/image.jpg'
        );
        $args = $defaultAvatarArgs($emptyTabularUser);
        $this->assertIdentical(false, $args['fileExists']);
    }

    function testDefaultAvatarArgsWithNoUser() {
        $defaultAvatarArgs = $this->getMethod('defaultAvatarArgs');

        $args = $defaultAvatarArgs(null);
        $this->assertIdentical('[unknown]', $args['displayName']);
        $this->assertIdentical('unknown', $args['title']);
        $this->assertIdentical('?', $args['defaultAvatar']['text']);
    }

    function testDefaultAvatarArgsWithEmptyObject() {
        $defaultAvatarArgs = $this->getMethod('defaultAvatarArgs');

        $emptyTabularUser = (object) array(
            'ID' => null,
            'DisplayName' => null,
        );
        $args = $defaultAvatarArgs($emptyTabularUser);
        $this->assertIdentical('[unknown]', $args['displayName']);
        $this->assertIdentical('unknown', $args['title']);
        $this->assertIdentical('?', $args['defaultAvatar']['text']);
    }

    function testUserIsSuspendedOrDeleted() {
        $method = $this->getMethod('userIsSuspendedOrDeleted');

        // decorated users
        $this->logIn('useractive1');
        $user = get_instance()->model('CommunityUser')->get()->result;
        $this->assertFalse($method($user));

        $this->logIn('usersuspended');
        $user = get_instance()->model('CommunityUser')->get()->result;
        $this->assertTrue($method($user));

        $this->logIn('userdeleted');
        $user = get_instance()->model('CommunityUser')->get()->result;
        $this->assertTrue($method($user));

        // Undecorated users
        $user = (object) array('StatusWithType' => (object) array('StatusType' => (object) array('ID' => STATUS_TYPE_SSS_USER_SUSPENDED)));
        $this->assertTrue($method($user));

        $user = (object) array('StatusWithType' => (object) array('StatusType' => (object) array('ID' => STATUS_TYPE_SSS_USER_DELETED)));
        $this->assertTrue($method($user));

        $user = (object) array('StatusWithType' => (object) array('StatusType' => (object) array('ID' => STATUS_TYPE_SSS_USER_ACTIVE)));
        $this->assertFalse($method($user));

        $this->logOut();
    }

    function testFormatBody() {
        $method = $this->getMethod('formatBody');

        $html = 'this is <b>bold</b>';
        $object = (object) array(
            'Body' => $html,
            'BodyContentType' => (object) array(
                'LookupName' => 'text/html'
            )
        );
        $this->assertEqual($html, $method($object));

        $object->BodyContentType->LookupName = 'text';
        $this->assertEqual('this is &lt;b&gt;bold&lt;/b&gt;', $method($object));

        $object->Body = 'this is **bold**';
        $object->BodyContentType->LookupName = 'text/x-markdown';
        $result = $method($object);
        $this->assertEqual('<p>this is <strong>bold</strong></p>', trim($method($object)));
    }

    function testMapFlagTypeAttribute () {
        $method = $this->getMethod('mapFlagTypeAttribute');
        $flagIDs = $method(array(null));
        $this->assertEqual(0, count($flagIDs));
        $flagIDs = $method(array());
        $this->assertEqual(0, count($flagIDs));
        $flagIDs = $method(array("invalid"));
        $this->assertEqual(0, count($flagIDs));
        $flagIDs = $method(array("Inappropriate", "spam"));
        $this->assertEqual(2, count($flagIDs));
        $flagIDs = $method(array());
        $this->assertTrue(empty($flagIDs));
    }

    function testParseReportDefaultFilterValue() {
        $method = $this->getMethod('parseReportDefaultFilterValue');
        $parsingOptions = array("allowedOptions" => array("last_90_days" => "last_90_days", "last_365_days" => "last_365_days", "last_7_days" => "last_7_days"), "dateFormat" => 'm/d/Y');
        $this->assertEqual("last_365_days", $method("DATE_ADD(SYSDATE(), -365, DAYS, 1)|", 4, $parsingOptions));
        $this->assertEqual("last_7_days", $method("DATE_ADD(SYSDATE(), -7, DAYS, 1)|", 4, $parsingOptions));
        $this->assertEqual(null, $method("invalid", 4, $parsingOptions));
        $this->assertEqual(null, $method("DATE_ADD(SYSDATE(), -65, DAYS, 1)|", 4, $parsingOptions));
        $dateRange = RightNow\Utils\Text::validateDateRange("01/01/2010|12/30/2011", 'm/d/Y', "|", true);
        $this->assertEqual("01/01/2010|12/31/2011", $method($dateRange, 4, $parsingOptions));
        $this->assertTrue(count(array_intersect(array("29", "30", "32"), $method("29;30;32", 1))) === 3);
        $this->assertEqual(array(""), $method("~any~", 1));
        $parsingOptions = array("filterName" => "p");
        $this->assertEqual(array(1, 4, 160), $method(160, 1, $parsingOptions));
        $parsingOptions = array("filterName" => "c");
        $this->assertEqual(array(71, 77), $method(77, 1, $parsingOptions));
    }

    function testGetModerationDateRangeValidationFunctions(){
        $method = $this->getMethod('getModerationDateRangeValidationFunctions');
        $validationFunctions =  $method("90 days");
        $this->assertNotNull($validationFunctions["questions.updated"]);
        $this->assertNotNull($validationFunctions["comments.updated"]);
        $result = $validationFunctions["questions.updated"]("01/01/2003|08/01/2004");
        $this->assertTrue(empty($result));
        $result = $validationFunctions["questions.updated"]("01/01/2003|02/28/2003");
        $this->assertTrue(!empty($result));
        $result = $validationFunctions["questions.updated"]("last_24_hours");
        $this->assertEqual("last_24_hours", $result);
        $result = $validationFunctions["questions.updated"]("last_30_days");
        $this->assertEqual("last_30_days", $result);
    }

    function testValidateModerationMaxDateRangeInterval(){
        $method = $this->getMethod('validateModerationMaxDateRangeInterval');
        $this->assertNotNull($method("90 days"));
        $this->assertNotNull($method("90 day"));
        $this->assertNotNull($method("90 years"));
        $this->assertNotNull($method("90 year"));
        $this->assertNotNull($method("90 month"));
        $this->assertNotNull($method("90 months"));
        $this->assertNull($method("invalid"));
        $this->assertNull($method(""));
        $this->assertNull($method(null));
        $this->assertNull($method("-2 days"));
    }

    function testGetExcludedStatuses(){
        $method = $this->getMethod('getExcludedStatuses');
        $questionExcludedStatuses = $method('CommunityQuestion');
        $commentExcludedStatuses = $method('CommunityComment');

        $this->assertEqual(count($method()), 3);
        $this->assertEqual(count($questionExcludedStatuses), 3);
        $this->assertEqual(count($commentExcludedStatuses), 3);
        $expectedQuestionExcludedStatuses = array(STATUS_TYPE_SSS_QUESTION_SUSPENDED, STATUS_TYPE_SSS_QUESTION_DELETED, STATUS_TYPE_SSS_QUESTION_PENDING);
        $this->assertEqual(0, count(array_diff($expectedQuestionExcludedStatuses, $questionExcludedStatuses)));
        $expectedCommentExcludedStatuses = array(STATUS_TYPE_SSS_COMMENT_SUSPENDED, STATUS_TYPE_SSS_COMMENT_DELETED, STATUS_TYPE_SSS_COMMENT_PENDING);
        $this->assertEqual(0, count(array_diff($expectedCommentExcludedStatuses, $commentExcludedStatuses)));
        $this->assertNull($method("Invalid"), 3);
    }

    function testFilterValidRoleSetIDs() {
        $method = $this->getMethod('filterValidRoleSetIDs');
        $result = $method("1|Posted by a moderator");
        $this->assertEqual(array("1" => "Posted by a moderator"), $result);
        $result = $method(",0,5,1|Posted by a moderator");
        $this->assertEqual(array("5" => "Posted by a moderator", "1" => "Posted by a moderator"), $result);
        $result = $method("1,7,-1|Posted by a moderator; 3|Posted by an employee");
        $this->assertEqual(array("1" => "Posted by a moderator", "7" => "Posted by a moderator", "3" => "Posted by an employee"), $result);
        $result = $method("1|Posted by a moderator; 7,,,1,3|Posted by an employee");
        $this->assertEqual(array("1" => "Posted by a moderator" ,"7" => "Posted by an employee", "3" => "Posted by an employee"), $result);
    }

    function testHighlightAuthorContent() {
        // Highlighting should be done for content posted by a moderator with role set ID 5 if specified
        list($fixtureInstance, $moderatorQuestion) = $this->getFixtures(array('QuestionActiveModActive'));
        $highlightAuthorContent = $this->getMethod('highlightAuthorContent');
        $rolesetToHighlight = $highlightAuthorContent($moderatorQuestion->CreatedByCommunityUser->ID, array("5" => "Posted by a moderator"));

        $this->assertEqual(5, $rolesetToHighlight);
        $fixtureInstance->destroy();

        // Highlighting should not be done, i.e return -1, when content is posted by an author whose role set ID isn't specified
        list($fixtureInstance, $userQuestion) = $this->getFixtures(array('QuestionActiveUserActive'));
        $highlightAuthorContent = $this->getMethod('highlightAuthorContent');
        $rolesetToHighlight = $highlightAuthorContent($userQuestion->CreatedByCommunityUser->ID, array("5" => "Posted by a moderator"));

        $this->assertEqual(-1, $rolesetToHighlight);
        $fixtureInstance->destroy();
    }
}
