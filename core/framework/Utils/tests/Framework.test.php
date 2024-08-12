<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper,
    RightNow\Api;

Helper::loadTestedFile(__FILE__);

class FrameworkTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\Framework';
    private $fixtureInstance;

    function __construct() {
        parent::__construct();
        $this->reflectionClass = new ReflectionClass('RightNow\Utils\Framework');
        $this->urlRequest = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/';
    }

    function setUpBeforeClass() {
        $this->fixtureInstance = new \RightNow\UnitTest\Fixture();
    }

    function tearDownAfterClass() {
        $this->fixtureInstance->destroy();
    }

    function setStaticProperty($propertyName, $propertyValue){
        $property = $this->reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($propertyValue);
    }

    function getStaticProperty($propertyName){
        $property = $this->reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue();
    }

    function setUpVersionInsertTests() {
        $this->eraseTempDir();
        $this->writeTempDir();
        $testCPCORE = $this->getTestDir();

        // FRAMEWORK
        $this->frameworkVersion = Framework::getFrameworkVersion();
        $testFrameworkPath = $this->testFrameworkPath = "$testCPCORE/framework";
        FileSystem::mkdirOrThrowExceptionOnFailure($testFrameworkPath, true);
        $this->frameworkContents = FileSystem::listDirectory(CPCORE, false, false, array('not equals', 'changelog.yml'), array('getType'));
        foreach ($this->frameworkContents as $pair) {
            list($fileOrDir, $type) = $pair;
            $target = "$testFrameworkPath/$fileOrDir";
            if ($type === 'dir') {
                FileSystem::mkdirOrThrowExceptionOnFailure($target);
            }
            else if ($type === 'file') {
                FileSystem::filePutContentsOrThrowExceptionOnFailure($target, '');
            }
        }

        // WIDGETS
        $source = dirname(CPCORE) . '/widgets';
        $target = $testCPCORE . '/widgets';
        FileSystem::copyDirectory($source, $target);
        $this->testWidgetPaths = array();
        foreach (FileSystem::getListOfWidgetManifests("$target/standard") as $path) {
            $this->testWidgetPaths[] = "$target/standard/" . dirname($path);
        }
    }

    function testGetFrameworkVersion() {
        $this->assertIdentical(CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION, Framework::getFrameworkVersion());
    }

    function testGetSupportedPhpVersions() {
        $data =  FrameworkInternal::getSupportedPhpVersions();
        $expected = array('3.11' => array(50600=>'5.6', 80100=>'8.1'));
        $defaultExpected = array(80100=>'8.1');
        
        if (array_key_exists(CP_FRAMEWORK_VERSION, $expected)) {
            $this->assertIdentical($expected[CP_FRAMEWORK_VERSION], $data);
        }
        else {
            $this->assertIdentical($defaultExpected, $data);
        }
    }

    function testCheckCache() {
        $this->setStaticProperty('processCache', array());
        $this->assertNull(Framework::checkCache('asdf'));
        $this->assertNull(Framework::checkCache(1));

        $this->setStaticProperty('processCache', array(
            'asdf' => array('fdsa', false),
            1 => array('one', false),
            'nullValue' => array(null, false),
            'falseValue' => array(false, false),
            'emptyValue' => array('', false),
            'serializedObject' => array(serialize(new \RightNow\Libraries\Widget\Attribute()), true))
        );

        $this->assertIdentical('fdsa', Framework::checkCache('asdf'));
        $this->assertIdentical('one', Framework::checkCache(1));
        $this->assertNull(Framework::checkCache('nullValue'));
        $this->assertFalse(Framework::checkCache('falseValue'));
        $this->assertIdentical('', Framework::checkCache('emptyValue'));
        $this->assertTrue(is_object(Framework::checkCache('serializedObject')));
        $this->assertIsA(Framework::checkCache('serializedObject'), '\RightNow\Libraries\Widget\Attribute');
        $this->setStaticProperty('processCache', array());
    }

    function testSetCache() {
        $this->setStaticProperty('processCache', array());

        Framework::setCache('key', 'value');
        $this->assertIdentical(array('key' => array('value', false)), $this->getStaticProperty('processCache'));

        $this->setStaticProperty('processCache', array());
        Framework::setCache('key', 'value');
        Framework::setCache('key', 'value');
        $this->assertIdentical(array('key' => array('value', false)), $this->getStaticProperty('processCache'));

        $this->setStaticProperty('processCache', array());
        Framework::setCache('key', 'value', false);
        $this->assertIdentical(array('key' => array('value', false)), $this->getStaticProperty('processCache'));
        Framework::setCache('key', 'value', true);
        $this->assertIdentical(array('key' => array(serialize('value'), true)), $this->getStaticProperty('processCache'));

        $this->setStaticProperty('processCache', array());
        try{
            Framework::setCache('key', array(new \RightNow\Libraries\Widget\Attribute()));
            $this->fail('The setCache method should throw an exception when we attempt to cache an object and dont tell it to serialize it.');
        }
        catch(\Exception $e){
            //We expect a failure since we're non hosted and we didn't tell setCache to serialize our object
        }
        $objectInstance = new \RightNow\Libraries\Widget\Attribute();
        Framework::setCache('key', $objectInstance, true);
        $this->assertIdentical(array('key' => array(serialize($objectInstance), true)), $this->getStaticProperty('processCache'));
        $this->setStaticProperty('processCache', array());
    }

    function testRemoveCache() {
        $this->setStaticProperty('processCache', array());

        Framework::setCache('key', 'value');
        $this->assertIdentical(array('key' => array('value', false)), $this->getStaticProperty('processCache'));

        Framework::removeCache('key');
        $this->assertFalse(array_key_exists('key', $this->getStaticProperty('processCache')));

        $this->setStaticProperty('processCache', array());
    }

    function testInArrayCaseInsensitive() {
        $this->assertTrue(Framework::inArrayCaseInsensitive(array('one'), 'One'));
        $this->assertTrue(Framework::inArrayCaseInsensitive(array('One', 'one'), 'One'));
        $this->assertTrue(Framework::inArrayCaseInsensitive(array('oNe', 'One', 'one'), 'One'));
        $this->assertTrue(Framework::inArrayCaseInsensitive(array(1, 'oNe', 'One', 'one'), 1));
        $this->assertTrue(Framework::inArrayCaseInsensitive(array(1, 'oNe', 'One', 'one'), 1));

        $this->assertFalse(Framework::inArrayCaseInsensitive(array(1, 'oNe', 'One', 'one'), 10));
        $this->assertFalse(Framework::inArrayCaseInsensitive(array(), 'one'));
        $this->assertFalse(Framework::inArrayCaseInsensitive(array(1, 2, 3), 'one'));
    }

    function testIsLoggedIn() {
        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $CI->session = null;
        try{
            Framework::isLoggedIn();
            $this->fail('The isLoggedIn function should throw an exception when the session class has yet to be initialized');
        }
        catch(\Exception $e){}

        $CI->session = array();
        try{
            Framework::isLoggedIn();
            $this->fail('The isLoggedIn function should throw an exception when the session class has yet to be initialized');
        }
        catch(\Exception $e){}

        $CI->session = new FakeSessionObject();
        $CI->session->setLoggedInResult(false);
        $this->assertFalse(Framework::isLoggedIn());

        $CI->session = new FakeSessionObject();
        $CI->session->setLoggedInResult(true);
        $this->assertTrue(Framework::isLoggedIn());

        $CI->session = $existingSessionInstance;
    }
    
    function testEnsurePasswordIsNotExpired(){
    	$CI = get_instance();
    	$existingSessionInstance = $CI->session;
    	
    	$CI->session = new FakeSessionObject();
    	$CI->session->setLoggedInResult(true);
    	
    	//Password is not expired
    	$CI->session->setProfileData('contactID', 1);
    	Api::test_sql_exec_direct("UPDATE _contacts SET password_exp = DATE( DATE_ADD( NOW() , INTERVAL 10 DAY ) ) WHERE c_id = 1");
    	Connect\ConnectAPI::commit();
    	$this->assertTrue(Framework::ensurePasswordIsNotExpired());
    	 
    	//Expired password
    	$CI->session->setProfileData('contactID', 2);
    	Api::test_sql_exec_direct("UPDATE _contacts SET password_exp = '2023-01-01 00:00:00' WHERE c_id = 2");
    	Connect\ConnectAPI::commit();
    	$this->assertFalse(Framework::ensurePasswordIsNotExpired());
    	
        $CI->session->setSessionData('ptaUsed', 1);
        $this->assertTrue(Framework::ensurePasswordIsNotExpired());
        
        $CI->session->setProfileData('ptaUsed', 0);
        $CI->session->setSessionData('ptaUsed', 0);
        $CI->session->setProfileData('samlLoginUsed', 1);
        $this->assertTrue(Framework::ensurePasswordIsNotExpired());
        
        $CI->session->setProfileData('ptaUsed', 0);
        $CI->session->setSessionData('ptaUsed', 0);
        $CI->session->setProfileData('samlLoginUsed', 0);
        $CI->session->setProfileData('openLoginUsed', 1);
        $this->assertTrue(Framework::ensurePasswordIsNotExpired());
        
    	$CI->session = $existingSessionInstance;
    }

    function testIsSocialModerator() {
        // normal social user
        $this->logIn('useractive1');
        $this->assertFalse(Framework::isSocialModerator());
        $this->logOut();

        // social moderator
        $this->logIn('userprodonly');
        $this->assertTrue(Framework::isSocialModerator());
        $this->logOut();
    }

    function testIsSocialUserModerator() {
        // normal social user
        $this->logIn('useractive1');
        $this->assertFalse(Framework::isSocialUserModerator());
        $this->logOut();

        // social content moderator
        $this->logIn('contentmoderator');
        $this->assertFalse(Framework::isSocialUserModerator());
        $this->logOut();

        // social moderator who has permission to moderate users(only update status)
        $this->logIn('userprodonly');
        $this->assertTrue(Framework::isSocialUserModerator());
        $this->logOut();

        // social moderator who has permission to moderate users (both update status and delete)
        $this->logIn('userprodandcat');
        $this->assertTrue(Framework::isSocialUserModerator());
        $this->logOut();

    }

    function testIsActiveSocialUser() {
        list($fixtureInstance,
            $useractive,
            $userarchived,
            $usersuspended,
            $userdeleted) = $this->getFixtures(array(
                'UserActive1',
                'UserArchive',
                'UserSuspended',
                'UserDeleted',
        ));

        foreach(array($useractive, $userarchived, $usersuspended, $userdeleted) as $user) {
            $this->logIn($user->Login);
            if ($user->DisplayName === 'Active User1') {
                $this->assertTrue(Framework::isActiveSocialUser());
            }
            else {
                $this->assertFalse(Framework::isActiveSocialUser());
            }
            $this->logOut();
        }

        $fixtureInstance->destroy();
    }

    function testGetSocialUser() {
        list($fixtureInstance,
            $useractive,
            $usersuspended,
            $usermoderator) = $this->getFixtures(array(
                'UserActive1',
                'UserSuspended',
                'UserModActive',
        ));

        $this->logIn($useractive->Login);
        $this->assertConnectObject(Framework::getSocialUser(), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE, PERM_SOCIALQUESTION_READ), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE, array(PERM_SOCIALQUESTION_READ)), 'CommunityUser');
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_PENDING));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_DELETED));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_SUSPENDED));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE, PERM_VIEWSOCIALMODERATORDASHBOARD));
        $this->logOut();

        $this->logIn($usersuspended->Login);
        $this->assertConnectObject(Framework::getSocialUser(), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_SUSPENDED), 'CommunityUser');
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_DELETED));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_PENDING));
        $this->logOut();

        $this->logIn($usermoderator->Login);
        $this->assertConnectObject(Framework::getSocialUser(), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE), 'CommunityUser');
        $this->assertConnectObject(Framework::getSocialUser(STATUS_TYPE_SSS_USER_ACTIVE, PERM_VIEWSOCIALMODERATORDASHBOARD), 'CommunityUser');
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_SUSPENDED));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_DELETED));
        $this->assertNull(Framework::getSocialUser(STATUS_TYPE_SSS_USER_PENDING));
        $this->logOut();

        $fixtureInstance->destroy();
    }

    function testIsSocialUser(){
        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $CI->session = null;
        try{
            Framework::isSocialUser();
            $this->fail('The isSocialUser function should throw an exception when the session class has yet to be initialized');
        }
        catch(\Exception $e){}

        $CI->session = array();
        try{
            Framework::isLoggedIn();
            $this->fail('The isSocialUser function should throw an exception when the session class has yet to be initialized');
        }
        catch(\Exception $e){}

        $CI->session = new FakeSessionObject();
        $CI->session->setLoggedInResult(false);
        $this->assertFalse(Framework::isSocialUser());

        $CI->session = new FakeSessionObject();
        $CI->session->setLoggedInResult(true);
        $this->assertFalse(Framework::isSocialUser());

        $CI->session->setProfileData('socialUserID', 0);
        $this->assertFalse(Framework::isSocialUser());

        $CI->session->setProfileData('socialUserID', -1);
        $this->assertFalse(Framework::isSocialUser());

        $CI->session->setProfileData('socialUserID', 1);
        $this->assertTrue(Framework::isSocialUser());

        $CI->session = $existingSessionInstance;
    }

    function testIsPta() {
        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $CI->session = new FakeSessionObject();
        $this->assertFalse(Framework::isPta());
        $CI->session->setSessionData('ptaUsed', false);
        $this->assertFalse(Framework::isPta());
        $CI->session->setSessionData('ptaUsed', 0);
        $this->assertFalse(Framework::isPta());
        $CI->session->setSessionData('ptaUsed', "");
        $this->assertFalse(Framework::isPta());

        $CI->session->setSessionData('ptaUsed', true);
        $this->assertTrue(Framework::isPta());
        $CI->session->setSessionData('ptaUsed', 1);
        $this->assertTrue(Framework::isPta());
        $CI->session->setSessionData('ptaUsed', "yep");
        $this->assertTrue(Framework::isPta());

        $CI->session = $existingSessionInstance;
    }

    function testIsOpenLogin() {
        $this->assertFalse(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => false));
        $this->assertFalse(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => 0));
        $this->assertFalse(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => ""));
        $this->assertFalse(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => array('provider' => 'twitter')));
        $this->assertTrue(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => 1));
        $this->assertTrue(Framework::isOpenLogin());

        $this->logIn($login, array('openLoginUsed' => "yep"));
        $this->assertTrue(Framework::isOpenLogin());

        $this->logOut();
    }
    
    function testIsSAMLLogin() {
        $this->assertFalse(Framework::isSAMLLogin());
        
        $this->logIn($login, array('samlLoginUsed' => false));
        $this->assertFalse(Framework::isSAMLLogin());
        
        $this->logIn($login, array('samlLoginUsed' => 0));
    	$this->assertFalse(Framework::isSAMLLogin());
    
    	$this->logIn($login, array('samlLoginUsed' => ""));
    	$this->assertFalse(Framework::isSAMLLogin());
        
    	$this->logIn($login, array('samlLoginUsed' => 1));
    	$this->assertTrue(Framework::isSAMLLogin());
    	
    	$this->logIn($login, array('samlLoginUsed' => true));
    	$this->assertTrue(Framework::isSAMLLogin());
    
    	$this->logIn($login, array('samlLoginUsed' => "yep"));
    	$this->assertTrue(Framework::isSAMLLogin());
        
        $this->logOut();
    }
    

    function testIsOpenLoginDoesNotReadFromSessionData() {
        $CI = get_instance();
        $existingSessionInstance = $CI->session;
        $CI->session = new FakeSessionObject();
        $CI->session->setSessionData('openLoginUsed', true);
        $this->assertFalse(Framework::isOpenLogin());
        $CI->session = $existingSessionInstance;
    }

    function testCheckForTemporaryLoginCookie() {
        $_COOKIE['cp_login_start'] = null;
        $this->assertFalse(Framework::checkForTemporaryLoginCookie());

        $_COOKIE['cp_login_start'] = false;
        $this->assertFalse(Framework::checkForTemporaryLoginCookie());
        $_COOKIE['cp_login_start'] = null;
        $this->assertFalse(Framework::checkForTemporaryLoginCookie());
        $_COOKIE['cp_login_start'] = "";
        $this->assertFalse(Framework::checkForTemporaryLoginCookie());
        $_COOKIE['cp_login_start'] = 0;
        $this->assertFalse(Framework::checkForTemporaryLoginCookie());

        $output = $this->makeRequest('Framework::checkForTemporaryLoginCookie', array('cookie' => 'cp_login_start=true'));
        $this->assertIdentical('', $output);
        $output = $this->makeRequest('Framework::checkForTemporaryLoginCookie', array('cookie' => 'cp_login_start=1'));
        $this->assertIdentical('', $output);
        $output = $this->makeRequest('Framework::checkForTemporaryLoginCookie', array('cookie' => 'cp_login_start="yep"'));
        $this->assertIdentical('', $output);

        unset($_COOKIE['cp_login_start']);
    }

    function testSetTemporaryLoginCookie(){
        $output = $this->makeRequest($this->urlRequest . 'setTemporaryLoginCookie', array('justHeaders' => true));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($output, "Set-Cookie: cp_login_start=1; path=/; httponly"));
    }

    function setTemporaryLoginCookie(){
        Framework::setTemporaryLoginCookie();
    }

    function checkForTemporaryLoginCookie() {
        $this->assertTrue(Framework::checkForTemporaryLoginCookie());
    }

    function testCreateToken() {
        $token = Framework::createToken(12);
        $this->assertFalse(Framework::isValidSecurityToken($token, 21));
        $this->assertFalse(Framework::isValidSecurityToken($token, 0));
        $this->assertTrue(Framework::isValidSecurityToken($token, 12));

        $token = Framework::createToken("asdf");
        $this->assertFalse(Framework::isValidSecurityToken($token, 21));
        $this->assertTrue(Framework::isValidSecurityToken($token, 'asdf'));
        $this->assertTrue(Framework::isValidSecurityToken($token, 0));
    }

    function testAdminPageCsrfToken(){
        $token = Framework::createAdminPageCsrfToken(1, 0);
        $this->assertNull($token);
        $token = Framework::createAdminPageCsrfToken(1, null);
        $this->assertNull($token);
        $token = Framework::createAdminPageCsrfToken(0, 1);
        $this->assertTrue(Framework::testCsrfToken($token, 1, 0, true));
    }

    function testCreateTokenWithExpiration() {
        $token = Framework::createTokenWithExpiration(12);
        $this->assertFalse(Framework::isValidSecurityToken($token, 21));
        $this->assertFalse(Framework::isValidSecurityToken($token, 0));
        $this->assertTrue(Framework::isValidSecurityToken($token, 12));

        $token = Framework::createTokenWithExpiration("asdf");
        $this->assertFalse(Framework::isValidSecurityToken($token, 21));

	$token = Framework::createTokenWithExpiration(0, false);
        $this->assertTrue(Framework::isValidSecurityToken($token, 0));
    }

    function testCreateCsrfToken() {
        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $CI->session = new FakeSessionObject();

        // anonymous users get unique tokens based on sessionID
        $CI->session->setSessionData('sessionID', 'asdf');
        $token1 = Framework::createCsrfToken(0, 0);
        $this->assertTrue(Framework::testCsrfToken($token1, 0));
        $CI->session->setSessionData('sessionID', 'qwer');
        $token2 = Framework::createCsrfToken(0, 0);
        $this->assertTrue(Framework::testCsrfToken($token2, 0));
        // verify the previous token is no longer valid
        $this->assertFalse(Framework::testCsrfToken($token1, 0));
        $this->validCsrfTokens($token1, $token2, false);

        // an authenticated user (passing in 50 as contactID) gets the different token based on sessionID
        $CI->session->setSessionData('sessionID', 'asdf');
        $token1 = Framework::createCsrfToken(0, 0, 50);
        $CI->session->setSessionData('sessionID', 'qwer');
        $token2 = Framework::createCsrfToken(0, 0, 50);
        $this->assertFalse(Framework::testCsrfToken($token1, 0, 50));
        $this->assertTrue(Framework::testCsrfToken($token2, 0, 50));
        $this->assertFalse(Framework::testCsrfToken($token1, 0));
        $this->assertFalse(Framework::testCsrfToken($token2, 0));
        $this->validCsrfTokens($token1, $token2, false);

        $CI->session = $existingSessionInstance;
    }

    /** @@@191204-000121
     * Ensure we can both create and validate CSRF tokens >256 chars in length
     * */
    function testLargeCsrfTokens() {
        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $CI->session = new FakeSessionObject();

        $longString = "DgN08IIfeSH02KimRZCltPRk0vRwJe9sdohZF9ZNnN3JooDf0jOTOgTcEQg95or7QRLCKfKTAa1ugsVISOArUI55222YBx03Kl4MYDkGJvUcPvsWQsLA5sowUASwtIXslotiLX9BnSCIRpOj2kXL7cW0Xl8ur7jdrlx2QYA88AK5VJB9vNtO87qeD27CRUrZtbsLFFvp";
        $CI->session->setSessionData('sessionID', $longString);
        $token = Framework::createCsrfToken(0, 0);
        $this->assertTrue(strlen($token) > 256);
        $this->assertTrue(Framework::testCsrfToken($token, 0));

        $CI->session = $existingSessionInstance;
    }

    function validCsrfTokens($token1, $token2, $shouldMatch) {
        $decodedToken = \RightNow\Api::decode_base64_urlsafe($token1);
        $token1Parts = explode('|', \RightNow\Api::ver_ske_decrypt($decodedToken));
        $decodedToken = \RightNow\Api::decode_base64_urlsafe($token2);
        $token2Parts = explode('|', \RightNow\Api::ver_ske_decrypt($decodedToken));

        $assertion = $shouldMatch ? 'assertIdentical' : 'assertNotIdentical';

        // hexTime value is ignored and everything else should match except possibly the contactID
        // id
        $this->assertIdentical($token1Parts[0], $token2Parts[0]);
        // doesTokenExpire
        $this->assertIdentical($token1Parts[2], $token2Parts[2]);
        // interfaceName
        $this->assertIdentical($token1Parts[3], $token2Parts[3]);
        // contactID
        $this->$assertion($token1Parts[4], $token2Parts[4]);
        // isChallengeRequired
        $this->assertIdentical($token1Parts[5], $token2Parts[5]);
    }

    function testCreatePostToken() {
        $constraints = json_encode(array('Incident.Subject' => array('required' => true)));
        $action = 'app/ask';
        $handler = 'postRequest/sendForm';
        $token = Framework::createPostToken($constraints, $action, $handler);

        $this->assertTrue(Framework::isValidPostToken($token, $constraints, $action, $handler));
        $this->assertTrue(Framework::isValidPostToken($token, $constraints, $action . '/session/abc', $handler));
    }

    function testIsValidPostToken() {
        $constraints = json_encode(array('Incident.Subject' => array('required' => true)));
        $action = 'app/ask';
        $handler = 'postRequest/sendForm';
        $token = Framework::createPostToken($constraints, $action, $handler);

        $this->assertTrue(Framework::isValidPostToken($token, $constraints, $action, $handler));
        $this->assertFalse(Framework::isValidPostToken($token, $constraints, $action, $handler . 'a'));
        $this->assertFalse(Framework::isValidPostToken($token, $constraints, $action . 'a', $handler));
        $this->assertFalse(Framework::isValidPostToken($token, json_encode(array()), $action, $handler));
    }

    function testIsValidSecurityToken() {
        $this->assertFalse(Framework::isValidSecurityToken('asdf', 19));

        $CI = get_instance();
        $existingSessionInstance = $CI->session;

        $nonLoggedInToken = Framework::createToken(1);
        $CI->session = new FakeSessionObject();
        $CI->session->setLoggedInResult(true);
        $CI->session->setProfileData('contactID', 8);

        $this->assertIdentical($nonLoggedInToken, Framework::createToken(1));
        $contactIDEightToken = Framework::createToken(11);
        $CI->session->setProfileData('contactID', 10);
        $this->assertFalse(Framework::isValidSecurityToken($contactIDEightToken, 11));
        $contactIDTenToken = Framework::createToken(111);
        $this->assertTrue(Framework::isValidSecurityToken($contactIDTenToken, 111));

        $CI->session = $existingSessionInstance;
    }

    function testLocationToken() {
        $token = $tokenSecure = Framework::createLocationToken('development');
        $this->assertIsA($token, 'string');
        $this->assertTrue(strlen($token) > 0);
        $this->assertFalse(Framework::testLocationToken(1, 'development'));
        $this->assertFalse(Framework::testLocationToken('', 'development'));
        $this->assertFalse(Framework::testLocationToken('asdf', 'development'));
        $this->assertTrue(Framework::testLocationToken($token, 'development'));
        $this->assertFalse(Framework::testLocationToken($token, null));
        $this->assertFalse(Framework::testLocationToken($token, ''));
        $this->assertFalse(Framework::testLocationToken($token, 'developments'));

        $token = Framework::createLocationToken(null);
        $this->assertIsA($token, 'string');
        $this->assertTrue(strlen($token) > 0);
        $this->assertFalse(Framework::testLocationToken($token, null));
        $this->assertFalse(Framework::testLocationToken($token, ''));

        $token = Framework::createLocationToken('');
        $this->assertIsA($token, 'string');
        $this->assertTrue(strlen($token) > 0);
        $this->assertFalse(Framework::testLocationToken($token, null));
        $this->assertFalse(Framework::testLocationToken($token, ''));

        $tokenInsecure = Framework::createLocationToken('development', true);
        $this->assertIsA($tokenInsecure, 'string');
        $this->assertTrue(strlen($tokenInsecure) > 0);
        // the insecure token will never be considered valid in the current framework
        $this->assertFalse(Framework::testLocationToken($tokenInsecure, 'development'));
        $this->assertFalse(Framework::testLocationToken($tokenInsecure, null));
        $this->assertFalse(Framework::testLocationToken($tokenInsecure, ''));
        $this->assertFalse(Framework::testLocationToken($tokenInsecure, 'developments'));

        // verify that encryption is different
        $decodedSecureToken = Api::decode_base64_urlsafe($tokenSecure);
        $decodedInsecureToken = Api::decode_base64_urlsafe($tokenInsecure);
        $dataSecure = explode('|', Api::ver_ske_decrypt($decodedSecureToken));
        $dataInsecure = explode('|', Api::pw_rev_decrypt($decodedInsecureToken));
        $dataSecure[1] = null;
        $dataInsecure[1] = null;
        $this->assertIdentical($dataSecure, $dataInsecure);
    }

    function testGetOptlist() {
        $this->assertIsA(Framework::getOptlist(12), 'array');
        $this->assertIsA(Framework::getOptlist(null), 'array');
        $optlist = Framework::getOptlist(12);
        $this->assertIsA($optlist, 'array');
        foreach($optlist as $key => $value){
            if(!is_int($key)){
                $this->fail("The getOptlist function is supposed to remove all non integer items from the array. This array has such keys - " . var_export($optlist));
            }
        }
    }

    function testLogMessage() {
        $logFile = \RightNow\Api::cfg_path() . '/log/cp' . getmypid() . '.tr';
        $this->assertIdentical(false, file_exists($logFile));
        Framework::logMessage('Hello this is a test', true);
        $this->assertIdentical(true, file_exists($logFile));
        $this->assertIdentical(true, strpos(file_get_contents($logFile), 'Hello this is a test') !== false);
        @unlink($logFile);

        $logFile = \RightNow\Api::cfg_path() . '/log/cp.log';
        @unlink($logFile);
        $this->assertIdentical(false, file_exists($logFile));
        Framework::logMessage('Hello this is a test');
        $this->assertIdentical(true, file_exists($logFile));
        $this->assertIdentical(true, strpos(file_get_contents($logFile), 'Hello this is a test') !== false);
        @unlink($logFile);
    }

    function testAddDevelopmentHeaderError() {
        $this->assertIdentical(null, $this->CI->developmentHeader);

        Framework::addDevelopmentHeaderError('add the header errror');

        require_once(CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php');
        $this->CI->developmentHeader = new \RightNow\Internal\Libraries\HeaderBuilder\Development(array(), array(), "");

        $this->assertIdentical(false, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the header errror'));

        Framework::addDevelopmentHeaderError('add the header errror');

        $this->assertIdentical(true, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the header errror') !== false);

        $this->CI->developmentHeader = null;
    }

    function testAddDevelopmentHeaderWarning() {
        $this->assertIdentical(null, $this->CI->developmentHeader);

        Framework::addDevelopmentHeaderWarning('add the header warning');

        require_once(CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php');
        $this->CI->developmentHeader = new \RightNow\Internal\Libraries\HeaderBuilder\Development(array(), array(), "");

        $this->assertIdentical(false, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the header warning'));

        Framework::addDevelopmentHeaderWarning('add the header warning');

        $this->assertIdentical(true, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the header warning') !== false);

        $this->CI->developmentHeader = null;
    }

    function testAddErrorToPageAndHeader() {
        $this->assertIdentical(null, $this->CI->developmentHeader);

        list($result, $content) = $this->returnResultAndContent(array('\RightNow\Utils\Framework', 'addErrorToPageAndHeader'), 'add the page and header error #1');
        $this->assertIdentical(null, $result);
        $this->assertIdentical('<div><b>add the page and header error #1</b></div>', $content);

        list($result, $content) = $this->returnResultAndContent(array('\RightNow\Utils\Framework', 'addErrorToPageAndHeader'), 'add the page and header error #2', true);
        $this->assertIdentical('<div><b>add the page and header error #2</b></div>', $result);
        $this->assertIdentical('', $content);

        require_once(CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php');
        $this->CI->developmentHeader = new \RightNow\Internal\Libraries\HeaderBuilder\Development(array(), array(), "");

        $this->assertIdentical(false, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the page and header error'));

        list($result, $content) = $this->returnResultAndContent(array('\RightNow\Utils\Framework', 'addErrorToPageAndHeader'), 'add the page and header error #1');
        $this->assertIdentical(null, $result);
        $this->assertIdentical('<div><b>add the page and header error #1</b></div>', $content);

        list($result, $content) = $this->returnResultAndContent(array('\RightNow\Utils\Framework', 'addErrorToPageAndHeader'), 'add the page and header error #2', true);
        $this->assertIdentical('<div><b>add the page and header error #2</b></div>', $result);
        $this->assertIdentical('', $content);

        $this->assertIdentical(true, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the page and header error #1') !== false);
        $this->assertIdentical(true, strpos($this->CI->developmentHeader->getDevelopmentHeaderHtml(), 'add the page and header error #2') !== false);

        $this->CI->developmentHeader = null;
    }

    function testEscapeForSql() {
        $this->assertIdentical("\\'", Framework::escapeForSql("'"));
        $this->assertIdentical("abc\\'def", Framework::escapeForSql("abc'def"));
        $this->assertIdentical("\\\\", Framework::escapeForSql("\\"));
        $this->assertIdentical("abc\\\\def", Framework::escapeForSql("abc\\def"));
        $this->assertIdentical("abc\\'\\\\\\'\\\\def", Framework::escapeForSql("abc'\\'\\def"));
    }

    function testRunSqlMailCommitHook() {
        Framework::runSqlMailCommitHook();

        $output = $this->makeRequest($this->urlRequest . 'getContactLogin');
        $this->assertIdentical('slatest', $output);

        $this->makeRequest($this->urlRequest . 'setContactLoginWithNoCommit');
        $output = $this->makeRequest($this->urlRequest . 'getContactLogin');
        $this->assertIdentical('slatest', $output);

        $this->makeRequest($this->urlRequest . 'setContactLoginWithCommit');
        $output = $this->makeRequest($this->urlRequest . 'getContactLogin');
        $this->assertIdentical('slatestchanged', $output);

        $output = $this->makeRequest($this->urlRequest . 'setContactLoginAfterCommitAndDBClose');
        $this->assertIdentical('1', $output);
        
        $output = $this->makeRequest($this->urlRequest . 'setContactLoginAfterCommitAndNoDBClose');
        $this->assertIdentical('', $output);

        $this->makeRequest($this->urlRequest . 'resetContactLoginWithCommit');
        $output = $this->makeRequest($this->urlRequest . 'getContactLogin');
        $this->assertIdentical('slatest', $output);
    }

    static function getContactLogin() {
        echo get_instance()->model('Contact')->get(1286)->result->Login;
    }

    static function setContactLoginWithNoCommit() {
        \RightNow\Api::test_sql_exec_direct("UPDATE _contacts SET login = 'slatestchanged' WHERE c_id = 1286");
        // force a fatal error so that process doesn't exit gracefully (even calling exit() is graceful)
        //  and commits are not done implicitly during process shutdown
        ThisClassDoesNotExist::SameWithThisFunction();
    }

    static function setContactLoginWithCommit() {
        \RightNow\Api::test_sql_exec_direct("UPDATE _contacts SET login = 'slatestchanged' WHERE c_id = 1286");
        Framework::runSqlMailCommitHook();
        ThisClassDoesNotExist::SameWithThisFunction();
    }
    
    static function setContactLoginAfterCommitAndNoDBClose() {
        \RightNow\Api::test_sql_exec_direct("UPDATE _contacts SET login = 'slatestchanged' WHERE c_id = 1286");
        RightNow\Hooks\SqlMailCommit::$disconnected = false;
        Framework::runSqlMailCommitHook();
        echo RightNow\Hooks\SqlMailCommit::$disconnected;
    }
    
    static function setContactLoginAfterCommitAndDBClose() {
        \RightNow\Api::test_sql_exec_direct("UPDATE _contacts SET login = 'slatestchanged' WHERE c_id = 1286");
        RightNow\Hooks\SqlMailCommit::$disconnected = false;
        Framework::runSqlMailCommitHook(true);
        echo RightNow\Hooks\SqlMailCommit::$disconnected;
    }    

    static function resetContactLoginWithCommit() {
        \RightNow\Api::test_sql_exec_direct("UPDATE _contacts SET login = 'slatest' WHERE c_id = 1286");
        Framework::runSqlMailCommitHook();
    }

    function testEvalCodeAndCaptureOutput() {
        $this->assertIdentical('asdf', Framework::evalCodeAndCaptureOutput("asdf"));
        $this->assertIdentical('hello', Framework::evalCodeAndCaptureOutput("<?echo 'hello';?>"));
        try{
            $response = Framework::evalCodeAndCaptureOutput("<?throw new \Exception('test exception');?>");
            $this->fail("Throwing an exception from evalCodeAndCaptureOutput should propagate out to the caller.");
        }
        catch(\Exception $e){
            $this->assertIdentical('test exception', $e->getMessage());
        }
    }

    function testEvalCodeAndCaptureOutputWithScope(){
        $this->assertIdentical('asdf', Framework::evalCodeAndCaptureOutputWithScope("asdf"));
        $this->assertIdentical('hello', Framework::evalCodeAndCaptureOutputWithScope("<?echo 'hello';?>"));
        try{
            $response = Framework::evalCodeAndCaptureOutputWithScope("<?throw new \Exception('test exception');?>");
            $this->fail("Throwing an exception from evalCodeAndCaptureOutput should propagate out to the caller.");
        }
        catch(\Exception $e){
            $this->assertIdentical('test exception', $e->getMessage());
        }

        $scope = (object)array('foo' => 'bar');
        $this->assertIdentical('bar', Framework::evalCodeAndCaptureOutputWithScope("<? print(\$this->foo); ?>", 'fake/path', $scope));

        $scope = new EvalScopingObject();
        $this->assertIdentical('public!', Framework::evalCodeAndCaptureOutputWithScope("<? print(\$this->publicProperty); ?>", 'fake/path', $scope));
        $this->assertIdentical('public!', Framework::evalCodeAndCaptureOutputWithScope("<? print(\$this->publicMethod()); ?>", 'fake/path', $scope));
    }

    function testHasClosedIncidentReopenDeadlinePassed() {
        $existingSessionInstance = $this->CI->session;
        $this->CI->session = new FakeSessionObject();
        $this->CI->session->setLoggedInResult(true);

        $this->CI->session->setProfileData('contactID', 655);

        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'i_id';
        $segments[] = '136';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter('i_id'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);

        $this->assertIdentical(true, Framework::hasClosedIncidentReopenDeadlinePassed(12));
        $this->assertIdentical(false, Framework::hasClosedIncidentReopenDeadlinePassed(1200000));

        //@@@ QA 130204-000128 Ensure hasClosedIncidentReopenDeadlinPassed is checking status type, not status ID
        \RightNow\Api::test_sql_exec_direct("UPDATE _incidents SET status_id = 1 WHERE i_id = 129");
        $this->CI->session->setProfileData('contactID', 118);

        $segments = $originalSegments;
        $segments[] = 'i_id';
        $segments[] = '129';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();

        $this->assertIdentical(true, Framework::hasClosedIncidentReopenDeadlinePassed(12));
        $this->assertIdentical(false, Framework::hasClosedIncidentReopenDeadlinePassed(1200000));

        $this->CI->session->setProfileData('contactID', 2);

        $segments = $originalSegments;
        $segments[] = 'i_id';
        $segments[] = '158';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();

        $this->assertIdentical(false, Framework::hasClosedIncidentReopenDeadlinePassed(12));

        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;

        $this->CI->session = $existingSessionInstance;
    }

    function testIncrementNumberOfSearchesPerformed() {
        $CI = get_instance();
        $existingSession = $CI->session;

        $CI->session = new FakeSessionObject();
        $CI->session->setSessionData('numberOfSearches', 0);
        $this->assertIdentical(0, $CI->session->getSessionData('numberOfSearches'));
        Framework::incrementNumberOfSearchesPerformed();
        $this->assertIdentical(1, $CI->session->getSessionData('numberOfSearches'));
        Framework::incrementNumberOfSearchesPerformed();
        $this->assertIdentical(2, $CI->session->getSessionData('numberOfSearches'));
        $CI->session->setSessionData('numberOfSearches', -1);
        Framework::incrementNumberOfSearchesPerformed();
        $this->assertIdentical(0, $CI->session->getSessionData('numberOfSearches'));

        $CI->session = $existingSession;
    }

    function testGetIcon() {
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_rightnow'><span class='rn_ScreenReaderOnly'>File Type rightnow</span></span>", Framework::getIcon('RNKLANS'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_rightnow'><span class='rn_ScreenReaderOnly'>File Type rightnow</span></span>", Framework::getIcon('rNkLaNs'));

        $this->assertIdentical("<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>", Framework::getIcon('http://www.google.com'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>", Framework::getIcon('https://www.google.com'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>", Framework::getIcon('HTtpS://www.google.com'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>", Framework::getIcon('httpstuffs'));

        $this->assertIdentical("<span class='rn_FileTypeIcon'></span>", Framework::getIcon('no_extension'));

        $this->assertIdentical("<span class='rn_FileTypeIcon rn_pdf'><span class='rn_ScreenReaderOnly'>File Type pdf</span></span>", Framework::getIcon('/foo/bar.pdf'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_doc'><span class='rn_ScreenReaderOnly'>File Type doc</span></span>", Framework::getIcon('/foo/bar.doc'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_abcdef'><span class='rn_ScreenReaderOnly'>File Type abcdef</span></span>", Framework::getIcon('/foo/bar.abcdef'));

        $this->assertIdentical("<span class='rn_FileTypeIcon rn_&lt;hack&gt;&amp;'><span class='rn_ScreenReaderOnly'>File Type &lt;hack&gt;&amp;</span></span>", Framework::getIcon('/foo/bar.<hack>&'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_ha&quot;ck'><span class='rn_ScreenReaderOnly'>File Type ha&quot;ck</span></span>", Framework::getIcon('/foo/bar.ha"ck'));
        $this->assertIdentical("<span class='rn_FileTypeIcon rn_ha&#039;ck'><span class='rn_ScreenReaderOnly'>File Type ha&#039;ck</span></span>", Framework::getIcon("/foo/bar.ha'ck"));
    }

    function testDestroyCookie() {
        $output = $this->makeRequest($this->urlRequest . 'destroyCookie1', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=deleted; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/\\n#", $output));

        $output = $this->makeRequest($this->urlRequest . 'destroyCookie2', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blahpath=deleted; expires=[a-zA-Z0-9:;, -]+; Max-Age=[0-9]+; path=/path\\n#", $output));
    }

    static function destroyCookie1() {
        Framework::destroyCookie('blah');
    }

    static function destroyCookie2() {
        Framework::destroyCookie('blahpath', '/path');
    }

    function testSetCPCookie() {
        $output = $this->makeRequest($this->urlRequest . 'setCPCookie1', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/; httponly; SameSite=Lax\\n#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie2', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=None; Secure\\n#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie3', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=Lax\\n#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie4', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=None; Secure\\n#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie5', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=Lax\\n#", $output));

        // verify Max-Age gets set
        $output = $this->makeRequest($this->urlRequest . 'setCPCookie6', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[1-9][0-9]+; path=/; httponly; SameSite=Lax\\n#", $output));
    }

    static function setCPCookie1() {
        Framework::setCPCookie('blah', 'bleet', time());
    }

    static function setCPCookie2() {
        Framework::setCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false, true);
    }

    static function setCPCookie3() {
        Framework::setCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false, false);
    }

    static function setCPCookie4() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', true, true);
        Framework::setCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false);
    }

    static function setCPCookie5() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', false, true);
        Framework::setCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false);
    }
    
    static function setCPCookie6() {
        Framework::setCPCookie('blah', 'bleet', time() + 1234);
    }

    function testSendCachedContentExpiresHeader() {
        $output = $this->makeRequest($this->urlRequest . 'sendCachedContentExpiresHeader1', array('justHeaders' => true));
        $this->assertFalse(\RightNow\Utils\Text::stringContains($output, 'Expires: '));

        $output = $this->makeRequest($this->urlRequest . 'sendCachedContentExpiresHeader2', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Expires: [a-zA-Z0-9:, -]+\\n#", $output));
    }

    static function sendCachedContentExpiresHeader1() {
    }

    static function sendCachedContentExpiresHeader2() {
        Framework::sendCachedContentExpiresHeader();
    }

    function testWriteContentWithLengthAndExit() {
        $output = $this->makeRequest($this->urlRequest . 'writeContentWithLengthAndExit1', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Content-Length: 4\\n#", $output));
        $this->assertIdentical(0, preg_match("#  Content-Type: mime-type\\n#", $output));
        $this->assertIdentical(1, preg_match("#Length: 4 \[text/html\]\\n#", $output));
        $output = $this->makeRequest($this->urlRequest . 'writeContentWithLengthAndExit1');
        $this->assertIdentical('blah', $output);

        $output = $this->makeRequest($this->urlRequest . 'writeContentWithLengthAndExit2', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#  Content-Length: 4\\n#", $output));
        $this->assertIdentical(1, preg_match("#  Content-Type: mime-type\\n#", $output));
        $this->assertIdentical(1, preg_match("#Length: 4 \[mime-type\]\\n#", $output));
        $output = $this->makeRequest($this->urlRequest . 'writeContentWithLengthAndExit2');
        $this->assertIdentical('blah', $output);
    }

    static function writeContentWithLengthAndExit1() {
        Framework::writeContentWithLengthAndExit('blah');
    }

    static function writeContentWithLengthAndExit2() {
        Framework::writeContentWithLengthAndExit('blah', 'mime-type');
    }

    function testGetErrorMessageFromCode() {
        $this->assertTrue(is_array(Framework::getErrorMessageFromCode('asdf')));
        $this->assertIdentical(Framework::getErrorMessageFromCode('asdf'), Framework::getErrorMessageFromCode('fdsa'));
        $this->assertIdentical(Framework::getErrorMessageFromCode(1), Framework::getErrorMessageFromCode('1'));
        $this->assertIdentical(Framework::getErrorMessageFromCode('sso9'), Framework::getErrorMessageFromCode('SSO9'));
    }

    function testGetCustomFieldList() {
        $this->assertTrue(is_array(Framework::getCustomFieldList(VTBL_CONTACTS, VIS_CF_ALL)));
        $this->assertTrue(is_array(Framework::getCustomFieldList(TBL_ANSWERS, VIS_CF_ALL)));

        $response = Framework::getCustomFieldList(VTBL_INCIDENTS, VIS_CF_ALL);
        $this->assertTrue(is_array($response));
        $firstCustomField = $response['cf_item0'];
        $this->assertTrue(is_int($firstCustomField['admin_required']));
        $this->assertTrue(is_int($firstCustomField['attr']));
        $this->assertTrue(is_int($firstCustomField['cf_id']));
        $this->assertTrue(is_string($firstCustomField['col_name']));
        $this->assertTrue(\RightNow\Utils\Text::beginsWith($firstCustomField['col_name'], 'c$'));
        $this->assertTrue(is_int($firstCustomField['data_type']));
        $this->assertTrue(is_int($firstCustomField['field_size']));
        $this->assertTrue(is_string($firstCustomField['lang_hint']));
        $this->assertTrue(is_string($firstCustomField['lang_name']));
        $this->assertTrue(is_int($firstCustomField['required']));
        $this->assertTrue(is_int($firstCustomField['visibility']));
    }

    function testInsertFrameworkVersionDirectory() {
        $this->setUpVersionInsertTests();
        Framework::insertFrameworkVersionDirectory($this->testFrameworkPath, null);
        $this->assertTrue(FileSystem::isReadableDirectory("{$this->testFrameworkPath}/{$this->frameworkVersion}"));
        foreach ($this->frameworkContents as $pair) {
            list($basename, $type) = $pair;
            $oldPath = "{$this->testFrameworkPath}/$basename";
            $newPath = "{$this->testFrameworkPath}/{$this->frameworkVersion}/$basename";
            if ($type === 'dir') {
                $this->assertFalse(FileSystem::isReadableDirectory($oldPath), "$oldPath still exists");
                $this->assertTrue(FileSystem::isReadableDirectory($newPath), "$newPath doesn't exist");
            }
            else if ($type === 'file') {
                $this->assertFalse(FileSystem::isReadableFile($oldPath), "$oldPath still exists");
                $this->assertTrue(FileSystem::isReadableFile($newPath), "$newPath doesn't exist");
            }
        }
    }

    function testInsertWidgetVersionDirectories() {
        $this->setUpVersionInsertTests();
        ob_start();
        Framework::insertWidgetVersionDirectories($this->testWidgetPaths);
        ob_end_clean();
        foreach ($this->testWidgetPaths as $path) {
            $contents = FileSystem::listDirectory($path, false, true);
            sort($contents);
            // Contents should be something like,
            // 0: 1.0.1
            // 1: 1.0.1/tests
            // 2..21: contents of 1.0.1 dir
            // 22: changelog.yml
            $this->assertIdentical(1, preg_match('/^(\d+\.\d+\.\d+)$/', $contents[0], $matches), sprintf("CONTENTS: %s  MATCHES: %s", var_export($contents[0], true), var_export($matches, true)));
            $version = $matches[0];
            $this->assertTrue(in_array("$version/info.yml", $contents), sprintf("%s not in %s", var_export("$version/info.yml", true), var_export($contents, true)));
        }
    }

    function testIsValidID() {
        $values = array(
            array(1, true),
            array('1', true),
            array('123', true),
            array(123, true),
            array('1.1', false),
            array('1e4', false),
            array(array(), false),
            array(false, false),
            array('false', false),
            array(null, false),
            array('null', false),
            array(0, false),
            array('0', false),
            array('', false),
            array(-1, false),
            array('-1', false),
        );
        foreach ($values as $pairs) {
            list($ID, $expected) = $pairs;
            $this->assertIdentical($expected, Framework::isValidID($ID), sprintf('"%s is not %s"', var_export($ID, true), var_export($expected, true)));
        }
    }

    function testCalculateJavaScriptHash() {
        $inDevelopmentSite = \RightNow\Utils\Widgets::getFullWidgetVersionDirectory('standard/input/FormInput') === 'standard/input/FormInput/';

        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('standard/input/FormInput/'))) . '.js', 'home.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/'))) . '.js', 'home.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/'))) . '.js', 'list.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('2.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/'))) . '.js', 'list.js') . "<br>\n";
        // echo "<br>\n";
        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('standard/input/FormInput/1.0.1'))) . '.js', 'home.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/1.0.1'))) . '.js', 'home.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('1.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/1.0.1'))) . '.js', 'list.js') . "<br>\n";
        // echo str_replace('.js', '.' . md5('2.' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '.' . json_encode(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget/1.0', 'standard/input/FormInput/1.0.1'))) . '.js', 'list.js') . "<br>\n";

        if($inDevelopmentSite) {
            $this->assertIdentical('home.9034eb147feabb422b79faddab6ac2f1.js', Framework::calculateJavaScriptHash(array('standard/input/FormInput'), 'home.js', 1));
            $this->assertIdentical('home.920ecdd60900d9d59e25155f40b0f490.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'home.js', 1));
            $this->assertIdentical('list.920ecdd60900d9d59e25155f40b0f490.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'list.js', 1));
            $this->assertIdentical('list.3a35f20e71907eb180de4c165af7afad.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'list.js', 2));
        }
        else {
            // hash includes version/folder 1.0.1 for standard widget
            $this->assertIdentical('home.78ce5cb93687da0e6d374c5279a45ab0.js', Framework::calculateJavaScriptHash(array('standard/input/FormInput'), 'home.js', 1));
            $this->assertIdentical('home.7f78a5c87bfa3c2ab9f8bed391409378.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'home.js', 1));
            $this->assertIdentical('list.7f78a5c87bfa3c2ab9f8bed391409378.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'list.js', 1));
            $this->assertIdentical('list.20540b54b52bd7281d30d7b4814451c3.js', Framework::calculateJavaScriptHash(array('/euf/core/js/min/widgetHelpers/EventProvider.js', 'custom/sample/SampleWidget', 'standard/input/FormInput'), 'list.js', 2));
        }
    }

    function testGetCustomField() {
        $this->assertNull(Framework::getCustomField('Contact', 'c$notACustomField'));

        try {
            Framework::getCustomField('Contacts', 'c$winner');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        // use assertIdentical vs assertTrue/False, since "False is the PHP definition of false, so that null, empty strings, zero and an empty array all count as false."
        $field = Framework::getCustomField('Contact', 'c$winner');
        $this->assertIdentical(false, $field['enduser_writable']);
        $this->assertIdentical(false, $field['enduser_visible']);

        $field = Framework::getCustomField('Contact', 'c$mktg_optin');
        $this->assertIdentical(true, $field['enduser_writable']);
        $this->assertIdentical(true, $field['enduser_visible']);

        $field = Framework::getCustomField('Contact', 'mktg_optin');
        $this->assertIdentical(true, $field['enduser_writable']);
        $this->assertIdentical(true, $field['enduser_visible']);
    }

    function testIsCustomFieldEnduserVisible() {
        $this->assertFalse(Framework::isCustomFieldEnduserVisible('Contact', 'c$winner'));
        $this->assertFalse(Framework::isCustomFieldEnduserVisible('Contact', 'winner'));
        $this->assertFalse(Framework::isCustomFieldEnduserVisible('SomeTable', 'mktg_optin'));
        $this->assertFalse(Framework::isCustomFieldEnduserVisible('Contact', 'some_field'));
        $this->assertTrue(Framework::isCustomFieldEnduserVisible('Contact', 'c$mktg_optin'));
        $this->assertTrue(Framework::isCustomFieldEnduserVisible('Contact', 'mktg_optin'));
    }

    function testIsCustomFieldEnduserWritable() {
        // Sadly, none of the default custom fields have read-only visibility, so this really
        // only tests that the field exists and is visible...
        $this->assertFalse(Framework::isCustomFieldEnduserWritable('Contact', 'c$winner'));
        $this->assertFalse(Framework::isCustomFieldEnduserWritable('Contact', 'winner'));
        $this->assertFalse(Framework::isCustomFieldEnduserWritable('SomeTable', 'mktg_optin'));
        $this->assertFalse(Framework::isCustomFieldEnduserWritable('Contact', 'some_field'));
        $this->assertTrue(Framework::isCustomFieldEnduserWritable('Contact', 'c$mktg_optin'));
        $this->assertTrue(Framework::isCustomFieldEnduserWritable('Contact', 'mktg_optin'));
    }

    function testDoesArrayContainAnObject() {
        $this->assertFalse(Framework::doesArrayContainAnObject(array()));
        $this->assertFalse(Framework::doesArrayContainAnObject(array(1, 2, 3)));
        $this->assertFalse(Framework::doesArrayContainAnObject(array('banana')));
        $this->assertTrue(Framework::doesArrayContainAnObject(array( (object) array())));
        $this->assertTrue(Framework::doesArrayContainAnObject(array( (object) array('banana' => 'bar'))));
    }

    function testSortBy() {
        $a = array(
            2 => array('a' => 2),
            1 => array('a' => 1),
            0 => array('a' => 3),
        );
        $sorted = Framework::sortBy($a, false, function($e) { return $e['a']; });
        $this->assertTrue(is_array($sorted));
        $this->assertIdentical(count($sorted), count($a));
        $this->assertIdentical(reset($sorted), $a[1]);
        $this->assertIdentical(next($sorted), $a[2]);
        $this->assertIdentical(next($sorted), $a[0]);
    }

    function testSortByReverse() {
        $a = array(
            2 => array('a' => 2),
            1 => array('a' => 1),
            0 => array('a' => 3),
        );
        $sorted = Framework::sortBy($a, true, function($e) { return $e['a']; });
        $this->assertTrue(is_array($sorted));
        $this->assertIdentical(count($sorted), count($a));
        $this->assertIdentical(reset($sorted), $a[0]);
        $this->assertIdentical(next($sorted), $a[2]);
        $this->assertIdentical(next($sorted), $a[1]);
    }

    function testSortByDuplicateKeys() {
        $a = array(
            3 => array('a' => 2),
            2 => array('a' => 2),
            1 => array('a' => 1),
            0 => array('a' => 3),
        );
        $sorted = Framework::sortBy($a, false, function($e) { return $e['a']; });
        $this->assertTrue(is_array($sorted));
        $this->assertIdentical(count($sorted), count($a));
        $this->assertIdentical(reset($sorted), $a[1]);
        $this->assertIdentical(next($sorted), $a[3]);
        $this->assertIdentical(next($sorted), $a[2]);
        $this->assertIdentical(next($sorted), $a[0]);
    }

    function testSortByWithStringKeys() {
        $a = array(
            3 => array('a' => 'B'),
            2 => array('a' => 'B'),
            1 => array('a' => 'A'),
            0 => array('a' => 'C'),
        );
        $sorted = Framework::sortBy($a, false, function($e) { return $e['a']; });
        $this->assertTrue(is_array($sorted));
        $this->assertIdentical(count($sorted), count($a));
        $this->assertIdentical(reset($sorted), $a[1]);
        $this->assertIdentical(next($sorted), $a[3]);
        $this->assertIdentical(next($sorted), $a[2]);
        $this->assertIdentical(next($sorted), $a[0]);
    }

    function testSortByWithInvalidKeyExtractorFunction() {
        $a = array(
            1 => array('a' => 1),
            0 => array('a' => 3),
        );
        $this->expectException();
        $sorted = Framework::sortBy($a, false, function($e) { return array('not a string', 'not an int', 'should cause exception'); });
    }

    function testSortByWithInvalidArray() {
        $this->expectException();
        $sorted = Framework::sortBy('a', false, function($e) { return $e['a']; });
    }

    function testPreserveParameters() {
        $output = Framework::getPreservedParameters();
        $this->assertIdentical($output, array('a_id', 'asset_id', 'i_id', 'kw', 'p', 'c', 'email', 'cid', 'unsub', 'request_source', 'chat_data', 'step', 'product_id', 'serial_no', 'qid', 'user', 'comment'));
    }

    function testPageAllowed() {
        $functionToUse = 'pageAllowed';
        $this->testPageAllowedContact("pageAllowedContact");
        $this->testPageAllowedAnswer("pageAllowedAnswer", true);
        $this->testPageAllowedAsset("pageAllowedAsset", true);
        $this->testPageAllowedIncident("pageAllowedIncident", true);
        $this->testPageAllowedSocialQuestion("pageAllowedSocialQuestion", true);
        $this->testPageAllowedSocialUser("pageAllowedSocialUser", true);
        $this->testPageAllowedSocialModerator("pageAllowedSocialModerator", true);
        $this->testPageAllowedProductCategory("pageAllowedProductCategory", true);
    }

    function testPageAllowedContact($functionToUse = 'pageAllowedContact') {
        $this->testPageAllowedSla($functionToUse, true);
        $this->testPageAllowedPta($functionToUse);
    }

    function runPageAllowedMethods($testValues = null) {
        if(!$testValues) {
            $usePost = true;
            $testValues = json_decode($_POST['data'], true);
        }
        $method = $this->getStaticMethod($testValues['method']);

        $CI = new FakePageController();
        $session = new FakeSessionObject();
        $CI->session = $session;
        foreach($testValues['sessionData'] as $key => $value) {
            $CI->session->setProfileData($key, $value);
        }
        if($testValues['configs']) {
            Helper::setConfigValues($testValues['configs']);
        }
        $CI->meta = $testValues['meta'];

        if($testValues['logIn']) {
            $this->logIn($testValues['logIn'] === true ? 'slatest' : $testValues['logIn']);
            $CI->session->setLoggedInResult(true);
        }

        if (in_array($testValues['method'], array('pageAllowed', 'pageAllowedPta'))) {
            // Most methods deal with just `$CI`.
            // But a few deal with both `$CI` and `$pagePath` or just `pagePath`.
            // Those ones expect `pagePath` to come first...
            $result = $method($testValues['pagePath'], $CI);
        }
        else {
            $result = $method($CI, $testValues['pagePath']);
        }

        if($testValues['logIn'])
            $this->logOut();

        // if using POST, echo results so that caller can examine them
        if($usePost)
            echo var_export($result, true);
        return $result;
    }

    function testPageAllowedSla($functionToUse = 'pageAllowedSla', $includePagePath = false) {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array('slac' => false, 'slai' => false, 'webAccess' => false),
            'meta' => array(),
        );
        if($includePagePath)
            $data['pagePath'] = 'fakePagePath';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($output);

        // verify each sla type
        foreach (array(array('sla_required_type' => 'chat'), array('sla_required_type' => 'incident'), array('sla_required_type' => 'selfservice')) as $meta) {
            // non-logged in users get redirected to login
            $data['meta'] = $meta;
            $result = $this->runPageAllowedMethods($data);
            $this->assertIdentical($result, array('type' => 'login'));

            // logged in users get an error page without the sla type available
            $data['logIn'] = true;
            $result = $this->runPageAllowedMethods($data);
            $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
            unset($data['logIn']);

            // non-logged in users get redirected to login
            $data['meta'] = $meta + array('sla_failed_page' => 'failplace');
            $result = $this->runPageAllowedMethods($data);
            $this->assertIdentical($result, array('type' => 'login'));

            // logged in users get redirected to sla_failed_page when set
            $data['logIn'] = true;
            $result = $this->runPageAllowedMethods($data);
            $this->assertIdentical($result, array('type' => 'location', 'url' => 'failplace' . \RightNow\Utils\Url::sessionParameter()));
            unset($data['logIn']);
        }

        $data['meta'] = array('sla_failed_page' => 'failplace');

        // non-logged in users get redirected to login
        $data['sessionData'] = array('slac' => false, 'slai' => false, 'webAccess' => true);
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));

        // logged in users with webAccess are allowed through
        $data['logIn'] = true;
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);

        // non-logged in users get redirected to login
        $data['sessionData'] = array('slac' => false, 'slai' => false, 'webAccess' => false);
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));

        // logged in users with no webAccess get redirected to sla_failed_page when set
        $data['logIn'] = true;
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'location', 'url' => 'failplace' . \RightNow\Utils\Url::sessionParameter()));
        unset($data['logIn']);
    }

    function testPageAllowedPta($functionToUse = 'pageAllowedPta') {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );

        $originalConfigValues = Helper::getConfigValues(array('PTA_ENABLED', 'PTA_IGNORE_CONTACT_PASSWORD'));
        foreach(array('/app/home' => false, '/app/utils/login_form' => true, '/app/utils/account_assistance' => true) as $pagePath => $shouldSendUserToErrorPage) {
            // let request go through
            $data['pagePath'] = $pagePath;
            $data['configs'] = array('PTA_ENABLED' => false, 'PTA_IGNORE_CONTACT_PASSWORD' => false);
            $result = $this->runPageAllowedMethods($data);
            $this->assertNull($result);

            // redirect to error page if page is login or account assistance page
            $data['pagePath'] = $pagePath;
            $data['configs'] = array('PTA_ENABLED' => true, 'PTA_IGNORE_CONTACT_PASSWORD' => false);
            $result = $this->runPageAllowedMethods($data);
            if($shouldSendUserToErrorPage)
                $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
            else
                $this->assertNull($result);

            // let request go through
            $data['pagePath'] = $pagePath;
            $data['configs'] = array('PTA_ENABLED' => false, 'PTA_IGNORE_CONTACT_PASSWORD' => true);
            $result = $this->runPageAllowedMethods($data);
            $this->assertNull($result);

            // let request go through
            $data['pagePath'] = $pagePath;
            $data['configs'] = array('PTA_ENABLED' => true, 'PTA_IGNORE_CONTACT_PASSWORD' => true);
            $result = $this->runPageAllowedMethods($data);
            $this->assertNull($result);
        }
        //Reset configs
        Helper::setConfigValues($originalConfigValues);
    }

    function testPageAllowedAnswer($functionToUse = 'pageAllowedAnswer', $includePagePath = false) {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );
        if($includePagePath)
            $data['pagePath'] = 'fakePagePath';

        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $data['meta']['answer_details'] = 'true';

        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 1));

        $this->addUrlParameters(array('a_id' => 0));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 1));
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('a_id' => -1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 1));
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('a_id' => 1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('a_id' => 50));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'location', 'url' => 'http://en.wikipedia.org/wiki/Sweet_track'));
        $this->restoreUrlParameters();


        $answer = Connect\Answer::fetch(1);
        $answer->StatusWithType->Status->ID = ANS_PRIVATE;
        $answer->save();
        Connect\ConnectAPI::commit();

        $output = $this->makeRequest($this->urlRequest . 'runPageAllowedMethods/a_id/1', array('post' => 'data=' . json_encode($data)));
        $this->assertTrue(Text::stringContains($output, "'type' => 'error'"));
        $this->assertTrue(Text::stringContains($output, " 'code' => 1"));
        

        $data['logIn'] = true;
        $output = $this->makeRequest($this->urlRequest . 'runPageAllowedMethods/a_id/1', array('post' => 'data=' . json_encode($data)));
        $this->assertTrue(Text::stringContains($output, "'type' => 'error'"));
        $this->assertTrue(Text::stringContains($output, " 'code' => 1"));
        unset($data['logIn']);

        $answer->StatusWithType->Status->ID = ANS_PUBLIC;
        $answer->save();
        Connect\ConnectAPI::commit();

        // no tests for answers of type file attachment
    }

    function testPageAllowedAsset($functionToUse = 'pageAllowedAsset', $includePagePath = false) {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );
        if($includePagePath)
            $data['pagePath'] = 'fakePagePath';

        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->addUrlParameters(array('asset_id' => 0));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('asset_id' => -1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('asset_id' => 750));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('asset_id' => 750));
        $data['logIn'] = true;
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
        unset($data['logIn']);
        $this->restoreUrlParameters();
    }

    function testPageAllowedIncident($functionToUse = 'pageAllowedIncident', $includePagePath = false) {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );
        if($includePagePath)
            $data['pagePath'] = 'fakePagePath';

        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->addUrlParameters(array('i_id' => 0));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('i_id' => -1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('i_id' => 141));

        // not logged in
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));

        // logged in, trying to view own incident
        $data['logIn'] = 'ehannan@rightnow.com.invalid';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);

        // logged in, but not user's incident
        $data['logIn'] = true;
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));
        unset($data['logIn']);

        $this->restoreUrlParameters();
    }

    function testPageAllowedSocialQuestion($functionToUse = 'pageAllowedSocialQuestion', $includePagePath = false){
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );

        if($includePagePath) {
            $data['pagePath'] = 'fakePagePath';
        }

        // Missing or invalid question ID
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->addUrlParameters(array('qid' => 0));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('qid' => -1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));
        $this->restoreUrlParameters();

        $suspendedQuestion1 = $this->fixtureInstance->make('QuestionSuspendedModActive');
        $suspendedQuestion2 = $this->fixtureInstance->make('QuestionSuspendedModActive');
        $suspendedQuestion3 = $this->fixtureInstance->make('QuestionSuspendedUserActive');
        $activeQuestion = $this->fixtureInstance->make('QuestionActiveModActive');

        // not logged in user cannot view a suspended question and is asked to login
        $this->addUrlParameters(array('qid' => $suspendedQuestion1->ID));
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));
        $this->restoreUrlParameters();

        // logged in user without permissions cannot view a suspended question
        $this->addUrlParameters(array('qid' => $suspendedQuestion2->ID));
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));
        $this->restoreUrlParameters();

        // logged in user with permissions can view a suspended question
        $this->addUrlParameters(array('qid' => $suspendedQuestion3->ID));
        $data['logIn'] = 'modactive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);
        $this->restoreUrlParameters();

        // not logged in user can view an active question
        $this->addUrlParameters(array('qid' => $activeQuestion->ID));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        // logged in user without 'moderator' permissions can view an active question
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        // logged in user with 'moderator' permissions can view an active question
        $data['logIn'] = 'modactive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);
        $this->restoreUrlParameters();
    }

    function testPageAllowedSocialUser($functionToUse = 'pageAllowedSocialUser', $includePagePath = false){
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );
        if($includePagePath)
            $data['pagePath'] = 'fakePagePath';

        // Invalid input
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->addUrlParameters(array('user' => 0));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        $this->addUrlParameters(array('user' => -1));

        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));

        $this->restoreUrlParameters();

        $userActive1 = $this->fixtureInstance->make('UserActive1');
        $userActive2 = $this->fixtureInstance->make('UserActive2');
        $userArchive = $this->fixtureInstance->make('UserArchive');
        $userSuspended = $this->fixtureInstance->make('UserSuspended');
        $userSuspended2 = $this->fixtureInstance->make('UserSuspended2');
        $userDeleted = $this->fixtureInstance->make('UserDeleted');
        $userModActive = $this->fixtureInstance->make('UserModActive');
        $userModSuspended = $this->fixtureInstance->make('UserModSuspended');

        $this->addUrlParameters(array('user' => $userSuspended->ID));

        // Not-logged-in user cannot view a suspended user
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userModSuspended->ID));

        // Logged-in user without permissions cannot view a suspended user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userSuspended2->ID));

        // Logged-in user with permissions can view a suspended user
        $data['logIn'] = 'modactive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result); 
        unset($data['logIn']);

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userDeleted->ID));

        // Not-logged-in user cannot view a deleted user
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));

        // Logged-in user without permissions cannot view a deleted user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));

        // Logged-in user with permissions cannot view a deleted user
        $data['logIn'] = 'modactive1';
        $this->assertIdentical($result, array('type' => 'error', 'code' => 8));
        unset($data['logIn']);

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userActive1->ID));

        // Not-logged-in user can view an active user
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userActive2->ID));

        // Logged-in user without 'moderator' permissions can view an active user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userModActive->ID));

        // Logged-in user with 'moderator' permissions can view an active user
        $data['logIn'] = 'modactive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);

        $this->restoreUrlParameters();
        $this->addUrlParameters(array('user' => $userArchive->ID));

        // Not-logged-in user can view an archived user
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        // Logged-in user without 'moderator' permissions can view an archived user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);

        // Logged-in user with 'moderator' permissions can view an archived user
        $data['logIn'] = 'modactive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        unset($data['logIn']);

        $this->restoreUrlParameters();
    }

    function testPageAllowedSocialModerator($functionToUse = 'pageAllowedSocialModerator') {
        //1. No meta data
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );
        $this->logOut();
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result, 'Shoud be null when tags are not passed');

        //2. add "social_moderator_required" meta tag and test
        $data['meta']['social_moderator_required'] = 'true';

        //2.1 without login
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));
        //2.2 login as regular social user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));

        //2.3 login as social moderator
        $data['logIn'] = 'usermoderator';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result, 'Shoud be null');

        //3. add "social_user_moderator_required" meta tag and test
        $data['meta']['social_user_moderator_required'] = 'true';

        //3.1 without login
        $data['logIn'] = false;
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'login'));

        //3.2 login as regular social user
        $data['logIn'] = 'useractive1';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));

        //3.3 login as social moderator
        $data['logIn'] = 'usermoderator';
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result, 'Shoud be null');

        //3.4 login as social content moderator
        $data['logIn'] = 'contentmoderator';
        $result = $this->runPageAllowedMethods($data);
        $this->assertIdentical($result, array('type' => 'error', 'code' => 4));

    }

    function testPageAllowedProductCategory($functionToUse = 'pageAllowedProductCategory', $includePagePath = false) {
        $data = array(
            'method' => $functionToUse,
            'sessionData' => array(),
            'meta' => array(),
        );

        if($includePagePath) {
            $data['pagePath'] = 'fakePagePath';
        }

        // enduser visible parent product
        $this->addUrlParameters(array('p' => 1));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        // enduser visible child product
        $this->addUrlParameters(array('p' => 4));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        // multiple enduser visible parent/child products
        $this->addUrlParameters(array('p' => '1,2,8;1,4;162'));
        $result = $this->runPageAllowedMethods($data);
        $this->assertStringContains($result['url'], '8;4;162');
        $this->restoreUrlParameters();

        // enduser visible parent category
        $this->addUrlParameters(array('c' => 161));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        // non enduser visible parent category
        $this->addUrlParameters(array('c' => 122));
        $result = $this->runPageAllowedMethods($data);
        $this->assertStringDoesNotContain($result['url'], '122');
        $this->restoreUrlParameters();

        // enduser visible child category
        $this->addUrlParameters(array('c' => 77));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        // multiple enduser visible/non visible category
        $this->addUrlParameters(array('c' => '77;122'));
        $result = $this->runPageAllowedMethods($data);
        $this->assertStringDoesNotContain($result['url'], '122');
        $this->assertStringContains($result['url'], '77');
        $this->restoreUrlParameters();

        // multiple enduser visible parent/child category
        $this->addUrlParameters(array('p' => 4, 'c' => 77));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();

        // enduser visible product and category
        $this->addUrlParameters(array('p' => '1;4;6', 'c' => '71;77;78'));
        $result = $this->runPageAllowedMethods($data);
        $this->assertNull($result);
        $this->restoreUrlParameters();
    }

    function testValidatePostHandler() {
        $getClass = function($time, $namespace) {
            return "<?
                namespace {$namespace};
                class TestLibrary{$time} extends \RightNow\Libraries\PostRequest {
                    public \$currentTime;
                    function __construct() { \$this->currentTime = {$time}; }
                    function test() {return 'hey';}
            }";
        };

        //Test with a standard library
        $time = time() + 1;
        $filePath = CPCORE . "Libraries/TestLibrary{$time}.php";

        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, $getClass($time, 'RightNow\Libraries'));
        $result = Framework::validatePostHandler("testLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);
        $this->assertIdentical('hey', $result->test());

        $result = Framework::validatePostHandler("TestLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);
        unlink($filePath);

        //Test with a standard library in a folder
        $time += 1;
        $filePath = CPCORE . "Libraries/TestPath{$time}/TestLibrary{$time}.php";

        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, $getClass($time, 'RightNow\Libraries'));
        $result = Framework::validatePostHandler("TestPath{$time}/testLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);

        $result = Framework::validatePostHandler("TestPath{$time}/TestLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);
        Filesystem::removeDirectory(CPCORE . "Libraries/TestPath{$time}/", true);

        //Test with a custom library
        $time += 1;
        $filePath = CUSTOMER_FILES . "libraries/TestLibrary{$time}.php";

        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, $getClass($time, 'Custom\Libraries'));
        $result = Framework::validatePostHandler("testLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);

        $result = Framework::validatePostHandler("TestLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);
        unlink($filePath);

        //Test with a custom library in a folder
        $time += 1;
        $filePath = CUSTOMER_FILES . "libraries/TestPath{$time}/TestLibrary{$time}.php";

        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, $getClass($time, 'Custom\Libraries'));
        $result = Framework::validatePostHandler("TestPath{$time}/testLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);

        $result = Framework::validatePostHandler("TestPath{$time}/TestLibrary{$time}/test", true);
        $this->assertIdentical($time, $result->currentTime);
        Filesystem::removeDirectory(CUSTOMER_FILES . "libraries/TestPath{$time}/", true);
    }

    function testFormatDate() {
        $dateRegex = '^\d{2}/\d{2}/\d{4}';
        $timeRegex = '\d{2}:\d{2} (AM|PM)';
        $datetimeRegex = "@{$dateRegex} {$timeRegex}$@";

        $inputs = array(
            //    SECONDS         DATE_FORMAT      TIME_FORMAT      EXPECTED
            array(null,           'default',       null,            "@^{$dateRegex}$@"),
            array(null,           '%m/%d/%Y',      null,            "@^{$dateRegex}$@"),
            array(null,           null,            null,            "@^{$dateRegex}$@"),
            array(null,           'default',       'default',       $datetimeRegex),
            array(null,           '%m/%d/%Y',      'default',       $datetimeRegex),
            array(null,           '%m/%d/%Y',      '%I:%M %p',      $datetimeRegex),
            array(0,              'default',       'default',       '12/31/1969 05:00 PM'),
            array(0,              'default',       null,            '12/31/1969'),
            array(1370242800,     'default',       'default',       '06/03/2013 01:00 AM'),
            array(1370242800,     'default',       null,            '06/03/2013'),
            array(1370242800,     'invalidFormat', 'default',       'invalidFormat 01:00 AM'),
            array(1370242800,     'invalidFormat', null,            'invalidFormat'),
            array(1370242800,     'default',       'invalidFormat', '06/03/2013 invalidFormat'),
            array(1370242800,     123,             null,            '123'),
            array(1370242800,     123,             456,             '123 456'),
            array(1370242800,     array(),         null,            'Array'),
            array(1370242800,     array(),         array(1,2,3),    'Array Array'),
            array('1370242800',   'default',       'default',       '06/03/2013 01:00 AM'),
            array(-1,             'default',       'default',       '12/31/1969 04:59 PM'),
            array('notAnInteger', 'default',       'default',       '12/31/1969 05:00 PM'),
            array(array(),        'default',       'default',       '12/31/1969 05:00 PM'),
            array(999999999999,   'default',       'default',       '12/13/1946 11:00 PM'),
            array(1370242800,     'default',       'default',       '06/03/2013 07:00 AM', false), //UTC-0 time, no adjustment for timezone
        );

        foreach($inputs as $input) {
            list($seconds, $date, $time, $expected, $excludeTimeZone) = $input;
            $actual = Framework::formatDate($seconds, $date, $time, ($excludeTimeZone === false) ? false : true);
            $isRegex =  Text::beginsWith($expected, '@');
            if (($isRegex && !preg_match($expected, $actual)) || (!$isRegex && $actual !== $expected)) {
                $msg = sprintf("Expected: '$expected', Got: '$actual' from formatDate(%s, %s, %s)",
                    var_export($seconds, true), var_export($date, true), var_export($time, true));
                $this->fail($msg);
            }
        }

        // no args returns current time, MM/DD/YYYY HH:MM (AM|PM)
        $result = Framework::formatDate();
        $this->assertEqual(1, preg_match($datetimeRegex, $result));
    }

    function testCreateDateRangeArray() {

        //day intervals
        $result = Framework::createDateRangeArray('2014-07-10', '2014-07-20');
        $this->assertEqual(count($result), 11, 'Date array should have 11 elements');
        end($result);
        $this->assertEqual(key($result), '2014-07-20', 'Value of last elements in an array should be 2014-07-20');

        //month intervals
        $result = Framework::createDateRangeArray('2014-01-01', '2014-07-23', '+1 month');
        $this->assertEqual(count($result), 7, 'Date array should have 7 elements');
        $this->assertEqual(key($result), '2014-01-01', 'Value of last elements in an array should be 2014-01-01');
        end($result);
        $this->assertEqual(key($result), '2014-07-01', 'Value of last elements in an array should be 2014-07-01');

        //year intervals
        $result = Framework::createDateRangeArray('2015-07-20', '2020-07-20', '+1 year');
        $this->assertEqual(count($result), 6, 'Date array should have 11 elements');
        $this->assertEqual(key($result), '2015-07-20', 'Value of last elements in an array should be 2015-07-20');
        end($result);
        $this->assertEqual(key($result), '2020-07-20', 'Value of last elements in an array should be 2020-07-20');

        //invalid use-cases
        $result = Framework::createDateRangeArray('2020-07-20', '2015-07-20', '+1 year'); //startDate is greater than endDate
        $this->assertEqual(count($result), 0, 'Date array should be empty');
        $result = Framework::createDateRangeArray('2014-07-10', '2014-07-20', 'some invalid interval'); //Invalid intervals
        $this->assertNotEqual(count($result), 11, 'Date array should not have 11 elements');
    }

    function testIsContactAllowedToUpdateAsset () {
        $user1 = (object) array(
            'login' => 'jerry@indigenous.example.com.invalid.070503.invalid',
            'email' => 'jerry@indigenous.example.com.invalid',
        );

        $user2 = (object) array(
            'login' => 'th@nway.xom.invalid.060804.060630.060523.invalid.060804.060630.invalid.060804.in',
            'email' => 'th@nway.xom.invalid',
        );

        $this->addUrlParameters(array('asset_id' => '8'));
        $this->logIn($user1->login);
        $result = Framework::isContactAllowedToUpdateAsset();
        $this->assertTrue($result);
        $this->logOut();

        $result = Framework::isContactAllowedToUpdateAsset();
        $this->assertFalse($result);

        $this->logIn($user1->login);
        $result = Framework::isContactAllowedToUpdateAsset('8');
        $this->assertTrue($result);
        $this->logOut();

        $this->logIn($user2->login);
        $result = Framework::isContactAllowedToUpdateAsset();
        $this->assertFalse($result);
        $this->logOut();

        $this->restoreUrlParameters();
    }

    function testJsonResponse(){
        $url = $this->urlRequest . 'jsonResponse';

        // Content Length and Type headers present
        $method = 'responseDataArray';
        $response = $this->makeRequest($url, array(
            'justHeaders' => true,
            'post' => $this->postArrayToParams(array('method' => $method)),
        ));
        $this->assertStringContains($response, 'Content-Length: 25');
        $this->assertStringContains($response, 'Content-Type: application/json');

        // array encoded
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => $method)),
        ));
        $this->assertIdentical((object) $this->$method(), json_decode($response));

        // already encoded array
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => 'responseDataEncodedArray', 'encode' => 'false')),
        ));
        $this->assertIdentical((object) $this->$method(), json_decode($response));

        // integer encoded
        $method = 'responseDataInteger';
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => $method)),
        ));
        $this->assertIdentical((string) $this->$method(), $response);

        // integer not encoded
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => $method, 'encode' => 'false')),
        ));
        $this->assertIdentical((string) $this->$method(), $response);

        // string encoded
        $method = 'responseDataString';
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => $method)),
        ));
        $this->assertIdentical("\"{$this->$method()}\"", $response);

        // string not encoded
        $response = $this->makeRequest($url, array(
            'post' => $this->postArrayToParams(array('method' => $method, 'encode' => 'false')),
        ));
        $this->assertIdentical($this->$method(), $response);
    }

    function makeRequestToMethodAndGetHeaderValues($locationHeader, $testContextObject, $method) {
        $output = $testContextObject->makeRequest($testContextObject->urlRequest . $method,
            array(
                "justHeaders" => true,
                "post" => $testContextObject->postArrayToParams(array(
                    "locationHeader" => $locationHeader
                ))
            )
        );

        $headerParts = explode("\n", $output);
        $firstLocationHeaderValue = $firstStatusCode = null;
        foreach($headerParts as $headerPart) {
            if(Text::beginsWith(trim($headerPart), "Location:"))
                $firstLocationHeaderValue = Text::getSubstringAfter($headerPart, "Location: ");
            if(Text::beginsWith(trim($headerPart), "HTTP/1.1"))
                $firstStatusCode = Text::getSubstringAfter($headerPart, "HTTP/1.1 ");
            // proxies (e.g. CI Hudson VMs) will use HTTP/1.0 instead of HTTP/1.1, it seems
            if(Text::beginsWith(trim($headerPart), "HTTP/1.0"))
                $firstStatusCode = Text::getSubstringAfter($headerPart, "HTTP/1.0 ");
            if($firstLocationHeaderValue && $firstStatusCode)
                break;
        }
        return array($firstLocationHeaderValue, $firstStatusCode);
    }

    function setLocationHeader() {
       Framework::setLocationHeader($_POST["locationHeader"]);
    }

    function testSetLocationHeader() {
        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli and Carrots", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli\r\n and\n \rCarrots", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli\r\n and\n \rCarrots\r\n", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli%0D%0A and%0D %0ACarrots", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli%0D%0A and%0D %0ACarrots%0D%0A", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli%5Cr%5Cn and%5Cr %5CnCarrots", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("%0DBroccoli%5Cr%5Cn and\r\n %5Cn%0ACarrots", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("%0DBroccoli%5Cr%5Cn and\r\n %5Cn%0ACarrots%5Cn", $this, "setLocationHeader");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("302 Moved Temporarily", $statusCode);
    }

    function setLocationHeaderPermanent() {
       Framework::setLocationHeader($_POST["locationHeader"], true);
    }

    function testSetLocationHeaderPermanent() {
        // Don"t need as many tests here, these tests are here to mostly confirm status code.
        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("Broccoli and Carrots", $this, "setLocationHeaderPermanent");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("301 Moved Permanently", $statusCode);

        list($locationValue, $statusCode) = $this->makeRequestToMethodAndGetHeaderValues("%0DBroccoli%5Cr%5Cn and\r\n %5Cn%0ACarrots%5Cn", $this, "setLocationHeaderPermanent");
        $this->assertIdentical("Broccoli and Carrots", $locationValue);
        $this->assertIdentical("301 Moved Permanently", $statusCode);
    }

    function responseDataArray() {
        return array(
            'ID' => 123,
            'message' => 'OK',
        );
    }

    function responseDataEncodedArray() {
        return json_encode($this->responseDataArray());
    }

    function responseDataInteger() {
        return 123;
    }

    function responseDataString() {
        return 'abc';
    }

    function jsonResponse() {
       $encode = $_POST['encode'] !== 'false';
       $method = $_POST['method'];
       echo Framework::jsonResponse($this->$method(), $encode);
    }
    
    function testgetSHA2Hash() {
        $this->assertIdentical(Framework::getSHA2Hash('abc.jpg12345table'), 'aea01dec7da53fe359c07a891b872fd4bc54d81b1672fa53489135e3a4332803');
    }
    
    function testcreateCommunityAttachmentToken() {
        $originalSessionID = get_instance()->session->getSessionData('sessionID');
        get_instance()->session->setSessionData(array('sessionID' => '4EnL2rjn'));
        //token for question
        $token = Framework::createCommunityAttachmentToken(32, '2010-03-25 23:04:04');
        $this->assertIdentical($token, 'f94d3f162976980ad3315a7f3459b4eed9543f4a5bba79dd741264b7fda8906c');
        //token for comment
        $token = Framework::createCommunityAttachmentToken(33, '2010-03-25 23:04:04');
        $this->assertIdentical($token, '330d9862da2bb3f9e2b9bdd2228ddef06be008f0b9224cf02c29e900ed1b381c');
        
        get_instance()->session->setSessionData(array('sessionID' => $originalSessionID));
    }
    
    function testisValidCommunityAttachmentToken() {
        //verifying for question
        $_GET['token'] = Framework::createCommunityAttachmentToken(32, '2010-03-25 23:04:04');
        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'cq';
        $segments[] = '22'; // setting question id
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        
        if (!\RightNow\Utils\Url::getParameter('cq'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);
        
        $this->assertTrue(Framework::isValidCommunityAttachmentToken(32, '2010-03-25 23:04:04'));
        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        unset($_GET['token']);
        
        //verifying for Comment 
        $_GET['token'] = Framework::createCommunityAttachmentToken(33, '2010-03-25 23:04:04');;
        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'cc';
        $segments[] = '66'; // setting comment id
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        
        if (!\RightNow\Utils\Url::getParameter('cc'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);
        
        $this->assertTrue(Framework::isValidCommunityAttachmentToken(33, '2010-03-25 23:04:04'));
        
        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        unset($_GET['token']);
        
    }

    function testReadCPConfigsFromFile() {
        $cpConfig = Framework::readCPConfigsFromFile();
        $expectedConfigs = array("CP.ServiceRetryable.Enable" => "false", "CP.ServiceRetryable.NextRequestAfter" => "5000");
        $this->assertEqual($cpConfig, $expectedConfigs);
    }

    function testgetSiteConfigValue() {
        $siteConfigValue = Framework::getSiteConfigValue('CP.EmailConfirmationLoop.Enable');
        $this->assertEqual($siteConfigValue, 0);
    }

    function testgetRandomPassword() {
        $constraints =  array(
            "length"=> array("bounds"=>"min", "count"=> 12),
            "lowercase"=> array("bounds"=>"min", "count"=>3),
            "specialAndDigits"=> array("bounds"=>"min", "count"=> 1),
            "special"=> array("bounds"=>"min", "count"=> 1),
            "uppercase"=> array("bounds"=>"min", "count"=> 2)
        );
      $password = Framework::getRandomPassword($constraints);
      $this->assertSame(1,(preg_match("#.*^(?=.{12,})(?=.*[a-z].{3,})(?=.*[A-Z].{2,})((?=.*[0-9])|(?=.*\W)).*$#", $password)));
    }

    function testSendRetryHeaders() {
        $response = $this->makeRequest($this->urlRequest . 'sendRetryHeadersAndRedirect', array('includeHeaders' => true));
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "OSVCSTATUS: 503"));
        $this->assertTrue(Text::stringContains($response, "Redirecting"));
        $this->assertTrue(Text::stringContains($response, "5000"));
        $this->assertTrue(Text::stringContains($response, $this->urlRequest));

        // Does Response preserve original query string - TO DO - 231204-000030 | Commented on 12-05-2023
        /*$response = $this->makeRequest($this->urlRequest . 'sendRetryHeadersAndRedirect?aod=123');
        $this->assertEqual(preg_match("#window.location.*aod%3D123#", $response), 1);*/

        // Does Response to JS redirect contain regex for window.location=/app/home
        $response = $this->makeRequest("/cpgtk/redirect?ru=https://evil.com/");
        $this->assertEqual(preg_match('#window.location.*app/home#', $response), 1);
        $this->assertEqual(preg_match('#window.location.*evil.com#', $response), 0);

        // Happy Path, ru contains correct webserver and path to page
        $webServerUrl = \RightNow\Utils\Config::getConfig(OE_WEB_SERVER);
        $response = $this->makeRequest("/cpgtk/redirect?ru=https://$webServerUrl/app/answers/list");
        $this->assertEqual(preg_match('#window.location.*app/home#', $response), 0);
        $this->assertEqual(preg_match("#window.location.*$webServerUrl%2Fapp%2Fanswers%2Flist#", $response), 1);
    }

    static function sendRetryHeadersAndRedirect() {
        Framework::sendRetryHeadersAndRedirect(array("CP.ServiceRetryable.Enable" => "true", "CP.ServiceRetryable.NextRequestAfter" => "5000"));
    }
    
    function testIsFormTokenRequired() {
        $this->assertTrue(Framework::isFormTokenRequired());
    }
}

class TestGetCodeExtensionsFrameworkMethod extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Framework';

    function __construct() {
        list($class, $prop) = $this->reflect('codeExtensions');
        $prop->setAccessible(true);
        $this->cache = $prop;
    }

    function setUp () {
        $this->cache->setValue(null);
        $this->path = APPPATH . "config/extensions.yml";
        $this->origContents = file_get_contents($this->path);
    }

    function tearDown () {
        file_put_contents($this->path, $this->origContents);
    }

    function testOutOfTheBoxValues() {
        $actual = Framework::getCodeExtensions();
        $this->assertIdentical(array('modelExtensions' => null, 'viewPartialExtensions' => null, 'viewHelperExtensions' => null, 'decoratorExtensions' => null), $actual);
        $this->assertNull(Framework::getCodeExtensions('modelExtensions'));
    }

    function testInvalidKey() {
        $this->assertNull(Framework::getCodeExtensions('bananas'));
    }

    function testInvalidYAML() {
        file_put_contents($this->path, "Manoteo: [");
        $this->assertFalse(Framework::getCodeExtensions());
        $this->assertFalse(Framework::getCodeExtensions('Manoteo'));
    }

    function testValidYAML() {
        $input = array('modelExtensions' => array('Answer' => 'Menor'));
        file_put_contents($this->path, yaml_emit($input));
        $this->assertIdentical($input['modelExtensions'], Framework::getCodeExtensions('modelExtensions'));
    }
}

class FakeAccountObject {
    function __construct() {
        $this->fname = 'Bob';
        $this->lname = 'Dylan';
    }
}
class FakeSessionObject{
    private $loggedInResult = null;
    private $sessionData = array();
    private $profileData = array();

    function isLoggedIn(){
        return $this->loggedInResult;
    }
    function setLoggedInResult($loggedInResult){
        $this->loggedInResult = $loggedInResult;
    }
    function getSessionData($key){
        return $this->sessionData[$key];
    }
    function setSessionData($key, $value = null){
        if(is_array($key)){
            foreach($key as $subKey => $value){
                $this->sessionData[$subKey] = $value;
            }
        }
        else {
            $this->sessionData[$key] = $value;
        }
    }
    function getProfileData($key){
        return $this->profileData[$key];
    }
    function setProfileData($key, $value){
        $this->profileData[$key] = $value;
    }
}

class EvalScopingObject{
    public $publicProperty = "public!";

    public function publicMethod(){
        echo "public!";
    }
}

class FakePageController{
    public $session = null;
    public $meta;

    public function __construct(){
        $this->session = new FakeSessionObject();
    }

    public function _sendUserToErrorPage($errorCode, $permanent = false){
        echo var_export($errorCode, true) . ' ' . var_export($permanent, true);
        exit();
    }

    public function _loginRedirect(){
        echo 'Login redirect was called';
        exit();
    }

    public function model($name){
        return get_instance()->model($name);
    }
}
