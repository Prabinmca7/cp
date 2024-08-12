<?php

use RightNow\Libraries\Session,
    RightNow\Libraries\ProfileData,
    RightNow\Libraries\SessionData,
    RightNow\Libraries\FlashData;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper as TestHelper;

class SessionTest extends CPTestCase {
    public $testingClass = '\RightNow\Libraries\Session';

    function createFakeProfileDataObject() {
        $fakeProfile = new \RightNow\Libraries\ProfileData();
        $fakeProfile->contactID = 10;
        $fakeProfile->firstName = 'Cuffy';
        $fakeProfile->lastName = 'Worthy';
        $fakeProfile->login = 'cWorthy';
        $fakeProfile->email = 'cworth@schoon.er';
        $fakeProfile->orgID = 7;
        $fakeProfile->webAccess = true;
        $fakeProfile->authToken = "8HNng78SEGn98Segl8g8ohSEG";
        $fakeProfile->ptaLoginUsed = 0;
        $fakeProfile->samlLoginUsed = false;
        $fakeProfile->orgLevel = 1;

        return $fakeProfile;
    }

    function testProfileDataObject(){
        $fakeProfile = $this->createFakeProfileDataObject();

        $this->assertIdentical($fakeProfile->c_id, $fakeProfile->contactID);
        $this->assertIdentical($fakeProfile->first_name, $fakeProfile->firstName);
        $this->assertIdentical($fakeProfile->last_name, $fakeProfile->lastName);
        $this->assertIdentical($fakeProfile->org_id, $fakeProfile->orgID);
        $this->assertIdentical($fakeProfile->web_access, $fakeProfile->webAccess);
        $this->assertIdentical($fakeProfile->cookie, $fakeProfile->authToken);
        $this->assertIdentical($fakeProfile->pta_login_used, $fakeProfile->ptaLoginUsed);
        $this->assertIdentical($fakeProfile->o_lvlN, $fakeProfile->orgLevel);

        $backwardCompatibleObject = $fakeProfile->getComplexMappedObject();
        foreach($backwardCompatibleObject as $key => $value){
            $this->assertIsA($value, 'stdClass');
            $this->assertIdentical($value->value, $fakeProfile->$key);
        }
    }

    function testSessionDataObject(){
        $fakeSession = new \RightNow\Libraries\SessionData();
        $this->assertNull($fakeSession->sessionID);
        $this->assertIdentical(0, $fakeSession->answersViewed);
        $this->assertIdentical(0, $fakeSession->questionsViewed);
        $this->assertIdentical(0, $fakeSession->numberOfSearches);
        $this->assertIdentical(array(), $fakeSession->urlParameters);
        $this->assertFalse($fakeSession->ptaUsed);
        $this->assertNull($fakeSession->cookiesEnabled);
        $this->assertNull($fakeSession->sessionString);
        $this->assertNull($fakeSession->previouslySeenEmail);
        $this->assertNull($fakeSession->lastActivity);
        $this->assertNull($fakeSession->sessionGeneratedTime);
        $this->assertIdentical(array(), $fakeSession->recentSearches);
        $this->assertNull($fakeSession->userCacheKey);
        $this->assertIdentical(array('i' => Api::intf_id()), $fakeSession->convertToCookie());

        $testData = array(
            's' => 'asgaseg',
            'a' => 2,
            'q' => 2,
            'n' => 2,
            'u' => array('a_id' => array('12', '19')),
            'p' => true,
            'e' => 'session/NlihasgenlD',
            'r' => 'test@example.com',
            'd' => array('dynamic' => 'variable'),
            'l' => 1233543323,
            'g' => 1233543323,
            'c' => array('recentSearches' => array('windows')),
            'k' => 'test',
            );
        $anotherFakeSession = new \RightNow\Libraries\SessionData($testData);

        $this->assertIdentical('asgaseg', $anotherFakeSession->sessionID);
        $this->assertIdentical(2, $anotherFakeSession->answersViewed);
        $this->assertIdentical(2, $anotherFakeSession->questionsViewed);
        $this->assertIdentical(2, $anotherFakeSession->numberOfSearches);
        $this->assertIdentical(array('a_id' => array('12', '19')), $anotherFakeSession->urlParameters);
        $this->assertTrue($anotherFakeSession->ptaUsed);
        $this->assertIdentical('session/NlihasgenlD', $anotherFakeSession->sessionString);
        $this->assertIdentical('test@example.com', $anotherFakeSession->previouslySeenEmail);
        $this->assertIdentical('variable', $anotherFakeSession->dynamic);
        $this->assertIdentical(1233543323, $anotherFakeSession->lastActivity);
        $this->assertIdentical(1233543323, $anotherFakeSession->sessionGeneratedTime);
        $this->assertIdentical(array('recentSearches' => array('windows')), $anotherFakeSession->recentSearches);
        $this->assertIdentical('test', $anotherFakeSession->userCacheKey);
        $converted = $anotherFakeSession->convertToCookie();
        $this->assertIdentical($converted['i'], Api::intf_id());
        unset($converted['i']);
        $this->assertIdentical($testData, $converted);

        $anotherFakeSession->anotherDynamic = "variable";
        $this->assertIdentical('variable', $anotherFakeSession->anotherDynamic);

        $moreTestData = array(
            's' => 'asgaseg',
            'a' => 2,
            'q' => 2,
            'n' => 2,
        );
        $lastFakeSession = new \RightNow\Libraries\SessionData($moreTestData);
        $converted = $lastFakeSession->convertToCookie();
        $this->assertIdentical($converted['i'], Api::intf_id());
        unset($converted['i']);
        $this->assertIdentical($moreTestData, $converted);
    }

    function testWritePersistentProfileData() {
        $this->assertFalse($this->CI->session->writePersistentProfileData(null));
        $this->assertFalse($this->CI->session->writePersistentProfileData(array()));
        $this->assertFalse($this->CI->session->writePersistentProfileData(array('bananas' => 'country')));
        $this->assertFalse($this->CI->session->writePersistentProfileData('bananas', 'country'));
        $this->assertFalse($this->CI->session->writePersistentProfileData('SocialUserID', 'country'));
        // Not logged in: no profile data to set property on
        $this->assertFalse($this->CI->session->writePersistentProfileData('socialUserID', 'country'));

        $profileCookieForNewRequest = function ($methodToCall) {
            return TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/{$methodToCall}/", array('justHeaders' => true));
        };
        $extractData = function ($input) {
            $profileCookieString = urldecode($input);
            return json_decode(stripslashes(\RightNow\Api::ver_ske_decrypt($profileCookieString)), true);
        };

        // Set with an array of properties
        $profileCookie = $profileCookieForNewRequest('writePersistentProfileDataWithArray');
        list(, $profileCookieString) = $this->extractCookie($profileCookie);
        $profileCookie = $extractData($profileCookieString);
        $this->assertSame($profileCookie['s'], 'bright');
        $this->assertSame($profileCookie['o'], 'child');

        // Set with key-val
        $profileCookie = $profileCookieForNewRequest('writePersistentProfileDataWithKeyVal');
        list(, $profileCookieString) = $this->extractCookie($profileCookie);
        $profileCookie = $extractData($profileCookieString);
        $this->assertSame($profileCookie['s'], 'tongues');
        $this->assertNull($profileCookie['o']);
    }

    function writePersistentProfileDataWithArray() {
        list (, $profileData) = $this->reflect('profileData');
        $profileData->setValue($this->CI->session, $this->fakeProfile());
        $this->CI->session->writePersistentProfileData(array('socialUserID' => 'bright', 'openLoginUsed' => 'child'));
    }

    function writePersistentProfileDataWithKeyVal() {
        list (, $profileData) = $this->reflect('profileData');
        $profileData->setValue($this->CI->session, $this->fakeProfile());
        $this->CI->session->writePersistentProfileData('socialUserID', 'tongues');
    }

    function testCreateProfileCookieWithExpiration() {
        // Calls the method we're testing - the response should contain the newly-set profile cookie containing:
        // 'c': authToken
        // 'f': forceful logout seconds
        // 'l': login start time
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfileWithExpiration", array('justHeaders' => true));
        list($match, $profileCookieString) = $this->extractCookie($output);

        $this->assertIdentical(1, $match);

        $profileCookieString = urldecode($profileCookieString);
        $profileCookie = json_decode(stripslashes(Api::ver_ske_decrypt($profileCookieString)), true);

        $this->assertIsA($profileCookie, 'array');
        $this->assertSame('whatev', $profileCookie['c']);
        $this->assertSame(1, $profileCookie['f'], 'Expire time should be in the cookie contents');
        $this->assertIsA($profileCookie['l'], 'int');
        $this->assertTrue($profileCookie['l'] > 0, 'Start time was\'t set properly');
        $this->assertSame(Api::intf_id(), $profileCookie['i']);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getProfileCookieLength", array('cookie' => "cp_profile=$profileCookieString;"));
        $this->assertTrue(is_numeric($output));
        $this->assertEqual((int)$output, $output);

        // Verify that the forceful logout time has been hit and the profile is destroyed
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfileWithExpiration", array('justHeaders' => true, 'cookie' => "cp_profile=$profileCookieString;"));
        list($match, $profileCookie) = $this->extractCookie($output);
        $this->assertIdentical(1, $match);
        $this->assertIdentical('deleted', $profileCookie);
    }

    function testCreateProfileCookie() {
        // Calls the method we're testing - the response should contain the newly-set profile cookie containing:
        // 'c': authToken
        // 'f': forceful logout seconds
        // 'l': login start time
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfile", array('justHeaders' => true));
        list($match, $profileCookieString) = $this->extractCookie($output);

        $this->assertIdentical(1, $match);

        $profileCookieString = urldecode($profileCookieString);
        $profileCookie = json_decode(stripslashes(Api::ver_ske_decrypt($profileCookieString)), true);

        $this->assertIsA($profileCookie, 'array');
        $this->assertSame('whatev', $profileCookie['c']);
        $this->assertFalse(isset($profileCookie['f']), 'Expire time should not be set in the cookie contents');
        $this->assertFalse(isset($profileCookie['l']));
        $this->assertSame(Api::intf_id(), $profileCookie['i']);

        // Verify that the forceful logout time has been hit and the profile is destroyed
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/destroyProfile", array('justHeaders' => true, 'cookie' => "cp_profile=$profileCookieString;"));
        list($match, $profileCookie) = $this->extractCookie($output);

        $this->assertIdentical(1, $match);
        $this->assertIdentical('deleted', $profileCookie);

        list($match, $profileCookie) = $this->extractCookie($output, 'cp_profile_flag');
        $this->assertIdentical(1, $match);
        $this->assertIdentical('deleted', $profileCookie);
    }

    function testCreateProfileFlagCookie() {
        $config = TestHelper::getConfigValues(array('SEC_END_USER_HTTPS'));

        // Test without SEC_END_USER_HTTPS
        TestHelper::setConfigValues(array('SEC_END_USER_HTTPS' => '0'), true);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfile", array('justHeaders' => true, 'useHttps' => false));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertEqual(0, $match);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfile", array('justHeaders' => true, 'useHttps' => true));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertEqual(1, $match);

        // Test cookie is deleted
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/destroyProfile", array('justHeaders' => true, 'useHttps' => true));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertIdentical('deleted', $flagCookie);

        // Test with SEC_END_USER_HTTPS
        TestHelper::setConfigValues(array('SEC_END_USER_HTTPS' => '1'), true);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfile", array('justHeaders' => true, 'useHttps' => false));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertEqual(0, $match);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writeProfile", array('justHeaders' => true, 'useHttps' => true));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertEqual(0, $match);

        // Test that cookie is deleted
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/destroyProfile", array('justHeaders' => true, 'useHttps' => true));
        list($match, $flagCookie) = $this->extractCookie($output, "cp_profile_flag");

        $this->assertIdentical('deleted', $flagCookie);


        TestHelper::setConfigValues($config, true);
    }

    function testGetInstance() {
        $instance1 = Session::getInstance();
        $instance2 = Session::getInstance();

        $this->assertIsA($instance1, '\RightNow\Libraries\Session');
        $this->assertSame($instance1, $instance2);
    }

    function testGetProfileData() {
        list (, $method, $profileData) = $this->reflect('method:getProfileData', 'profileData');
        $instance = Session::getInstance();
        $this->assertFalse($instance->isLoggedIn());

        $fakeProfile = $this->createFakeProfileDataObject();
        $profileData->setValue($instance, $fakeProfile);

        foreach (array_keys(array_filter(get_object_vars($fakeProfile))) as $key) {
            $this->assertIdentical($method->invoke($instance, $key), $fakeProfile->{$key});
        }
        $this->assertTrue($instance->isLoggedIn());
    }

    //@@@ QA 130306-000049 Test that previously email values are escaped going into the session
    function testSetProfileData(){
        $instance = \RightNow\Libraries\Session::getInstance();

        // Prevent cookie from actually being written.
        list(, $canSetSessionCookie) = $this->reflect('canSetSessionCookie');
        $originalValue = $canSetSessionCookie->getValue($instance);
        $canSetSessionCookie->setValue($instance, false);

        $instance->setSessionData(array('previouslySeenEmail' => 'test@example.com'));
        $this->assertIdentical('test@example.com', $instance->getSessionData('previouslySeenEmail'));

        $instance->setSessionData(array('previouslySeenEmail' => '&"\'<>'));
        $this->assertIdentical('&amp;&quot;&#039;&lt;&gt;', $instance->getSessionData('previouslySeenEmail'));

        $instance->setSessionData(array('previouslySeenEmail' => null));

        $canSetSessionCookie->setValue($instance, $originalValue);
    }

    function testSetSocialUser(){
        $instance = \RightNow\Libraries\Session::getInstance();

        // no-op if profile not an object. No exceptions thrown
        $instance->setSocialUser(null);
        $instance->setSocialUser(array());

        // no-op if socialUserID set
        $profile = new \RightNow\Libraries\ProfileData;
        $profile->socialUserID = 5;
        $instance->setSocialUser($profile);
        $this->assertEqual(5, $profile->socialUserID);

        // socialUserID set from contact
        $this->logIn();
        $profile = new \RightNow\Libraries\ProfileData;
        $contactID = $this->CI->session->getProfileData('contactID');
        $this->assertIsA($contactID, 'integer');
        $profile->c_id = $contactID;
        $this->assertNull($profile->socialUserID);
        $instance->setSocialUser($profile);
        $this->assertIsA($profile->socialUserID, 'integer');
        $this->logOut();
    }

    function testGetProfile() {
        list (, $method, $profileData) = $this->reflect('method:getProfile', 'profileData');

        $instance = Session::getInstance();
        $fakeProfile = $this->createFakeProfileDataObject();
        $profileData->setValue($instance, $fakeProfile);

        $profile = $method->invoke($instance);
        $this->assertIdentical($fakeProfile->contactID, $profile->c_id->value);
        $this->assertIdentical($fakeProfile->firstName, $profile->first_name->value);
        $this->assertIdentical($fakeProfile->lastName, $profile->last_name->value);
        $this->assertIdentical($fakeProfile->orgID, $profile->org_id->value);
        $this->assertIdentical($fakeProfile->webAccess, $profile->web_access->value);
        $this->assertIdentical($fakeProfile->authToken, $profile->cookie->value);
        $this->assertIdentical($fakeProfile->ptaLoginUsed, $profile->pta_login_used->value);
        $this->assertIdentical($fakeProfile->orgLevel, $profile->o_lvlN->value);
    }

    function testIsRequired() {
        $instance = Session::getInstance();
        $this->assertTrue($instance->isRequired());
    }

    function testIsDisabled() {
        list (, $isDisabledMethod, $canSetSessionCookieMethod, $canSetSessionCookie) =
            $this->reflect('method:isDisabled', 'method:canSetSessionCookies', 'canSetSessionCookie');

        $instance = Session::getInstance();
        $canSetSessionCookieValue = $canSetSessionCookie->getValue($instance);

        $canSetSessionCookie->setValue($instance, true);
        $this->assertFalse($isDisabledMethod->invoke($instance));
        $this->assertTrue($canSetSessionCookieMethod->invoke($instance));

        $canSetSessionCookie->setValue($instance, false);
        $this->assertTrue($isDisabledMethod->invoke($instance));
        $this->assertFalse($canSetSessionCookieMethod->invoke($instance));

        $canSetSessionCookie->setValue($instance, $canSetSessionCookieValue);
    }

    function testGenerateNewSession() {
        $CI = get_instance();
        $origSessionID = $CI->session->getSessionData('sessionID');

        $CI->session->generateNewSession();
        $newSessionID = $CI->session->getSessionData('sessionID');

        $this->assertIsA($newSessionID, 'string');
        $this->assertNotEqual($origSessionID, $newSessionID);
    }

    function testIsNewSession() {
        list (, $isNewSession, $generateNewSession, $newSession) =
            $this->reflect('method:isNewSession', 'method:generateNewSession', 'newSession');
        $instance = Session::getInstance();
        $this->assertTrue($isNewSession->invoke($instance));

        $newSession->setValue($instance, false);
        $this->assertFalse($isNewSession->invoke($instance));

        $generateNewSession->invoke($instance);
        $this->assertTrue($isNewSession->invoke($instance));
    }

    function testCreateMapping() {
        $fakeApiProfileArray = array(
            'c_id'          => 13,
            'email'         => 'someoneawesome@email.null',
            'first_name'    => 'Someone',
            'last_name'     => 'Awesome',
            'org_id'        => 21,
            'web_access'    => true,
            'cookie'        => '8HNng78SEGn98Segl8g8ohSEG',
            'o_lvlN'        => 1,
        );

        $instance = Session::getInstance();
        $profileFromArray = $instance->createMapping($fakeApiProfileArray);
        $this->assertIsA($profileFromArray, 'RightNow\Libraries\ProfileData');

        foreach (array_keys($fakeApiProfileArray) as $key)
            $this->assertIdentical($fakeApiProfileArray[$key], $profileFromArray->{$key});

        // createMapping will work with either apiProfile being an array or object - test both ways
        $fakeApiProfileObject = (object)$fakeApiProfileArray;
        $profileFromObject = $instance->createMapping($fakeApiProfileArray);
        $this->assertIsA($profileFromObject, 'RightNow\Libraries\ProfileData');

        foreach (array_keys($fakeApiProfileArray) as $key)
            $this->assertIdentical($fakeApiProfileObject->{$key}, $profileFromObject->{$key});

        // guarantee that loginStartTime is the same, since we sometimes get off-by-one-second errors
        $profileFromObject->loginStartTime = $profileFromArray->loginStartTime = time();
        $this->assertIdentical($profileFromObject, $profileFromArray);
    }

    function testCreateMappingWithPersistentProperties() {
        // Values from existing profile data are persisted
        list(, $profileData) = $this->reflect('profileData');

        $fakeProfile = new \RightNow\Libraries\ProfileData;
        $fakeProfile->openLoginUsed = 'bananas';
        $fakeProfile->socialUserID = 5;

        $instance = Session::getInstance();
        $profileData->setValue($instance, $fakeProfile);
        $result = $instance->createMapping((object) array(), false, true);

        $this->assertIsA($result, 'RightNow\Libraries\ProfileData');
        $this->assertIdentical('bananas', $result->openLoginUsed);
        $this->assertIdentical(5, $result->socialUserID);

        // Passed in values take precedence
        $result = $instance->createMapping((object) array(
            'openLoginUsed' => 'body',
            'socialUserID'  => 'building',
        ), false, true);
        $this->assertIdentical('body', $result->openLoginUsed);
        $this->assertIdentical('building', $result->socialUserID);
    }

    function testPerformLogout() {
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/performLogout", array('justHeaders' => true));

        list($match, $profileCookieString) = $this->extractCookie($output);
        $this->assertIdentical(1, $match);
        $this->assertIdentical('deleted', $profileCookieString);

        list($match, $profileCookieString) = $this->extractCookie($output, 'cp_profile_flag');
        $this->assertIdentical(1, $match);
        $this->assertIdentical('deleted', $profileCookieString);
    }

    function testIsProfileFlagCookieSet() {
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/isProfileFlagCookieSet", array('cookie' => 'cp_profile_flag=1;'));
        $this->assertIdentical('true', $output);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/isProfileFlagCookieSet", array('cookie' => 'cp_profile_flag=true;'));
        $this->assertIdentical('false', $output);

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/isProfileFlagCookieSet");
        $this->assertIdentical('false', $output);
    }

    function testCreateProfileCookieFromPta() {
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/writePtaProfile", array('justHeaders' => true));
        list($match, $profileCookieString) = $this->extractCookie($output);
        $this->assertIdentical($match, 1);

        $profileCookieString = urldecode($profileCookieString);
        $profileCookie = json_decode(stripslashes(Api::ver_ske_decrypt($profileCookieString)), true);

        $this->assertIsA($profileCookie, 'array');
        $this->assertSame('whatev', $profileCookie['c']);
        $this->assertTrue($profileCookie['p']);
        $this->assertSame(Api::intf_id(), $profileCookie['i']);
    }

    function testGetFlashData() {
        $session = Session::getInstance();

        $this->assertNull($session->getFlashData('foo'));

        $session->setFlashData('foo', 'bar');
        $this->assertSame('bar', $session->getFlashData('foo'));

        $session->setFlashData(array(
            'foo'     => array(),
            'bar'     => 'yep',
            'bananas' => 23,
        ));

        $this->assertIdentical(array(), $session->getFlashData('foo'));
        $this->assertIdentical('yep', $session->getFlashData('bar'));
        $this->assertIdentical(23, $session->getFlashData('bananas'));
    }

    function testKeepFlashData() {
        $session = Session::getInstance();
        list(
            ,
            $flashClass
        ) = $this->reflect('flashData');
        $flashClass->setAccessible(true);

        $flash = new \ReflectionClass('\RightNow\Libraries\FlashData');
        $flashData = $flash->getProperty('data');
        $flashData->setAccessible(true);
        $flashInstance = new FlashData;
        $flashData->setValue($flashInstance, array(
            FlashData::OLD_DATA . 'yep'     => 'bar',
            FlashData::OLD_DATA . 'bananas' => 'yep',
            FlashData::OLD_DATA . 'foo'     => 'baz',
            FlashData::NEW_DATA . 'scrape'  => 'barrel',
        ));
        $flashClass->setValue($session, $flashInstance);

        $session->keepFlashData(array('yep', 'bananas'));
        $this->assertIdentical(array(
            FlashData::OLD_DATA . 'foo'     => 'baz',
            FlashData::NEW_DATA . 'scrape'  => 'barrel',
            FlashData::NEW_DATA . 'yep'     => 'bar',
            FlashData::NEW_DATA . 'bananas' => 'yep',
        ), $flashData->getValue($flashClass->getValue($session)));

        $session->keepFlashData('foo');
        $this->assertIdentical(array(
            FlashData::NEW_DATA . 'scrape'  => 'barrel',
            FlashData::NEW_DATA . 'yep'     => 'bar',
            FlashData::NEW_DATA . 'bananas' => 'yep',
            FlashData::NEW_DATA . 'foo'     => 'baz',
        ), $flashData->getValue($flashClass->getValue($session)));
    }

    function testSessionCookieWithInterface() {
        $sessionID = $this->CI->session->getSessionData('sessionID');
        $cookieData = Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('s' => array('i' => Api::intf_id(), 's' => $sessionID))));
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/returnSession", array('cookie' => "cp_session=$cookieData;"));
        $this->assertTrue($sessionID === $output);

        $cookieData = Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('i' => Api::intf_id() === 1 ? 2 : 1, 's' => $sessionID)));
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/returnSession", array('cookie' => "cp_session=$cookieData;"));
        $this->assertFalse($sessionID === $output);
    }

    function testGetSessionIdleLength() {
        $config = TestHelper::getConfigValues(array('BILLABLE_SESSION_LENGTH', 'VISIT_MAX_TIME', 'VISIT_INACTIVITY_TIMEOUT'));

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionIdleLength");
        $this->assertIdentical('1800', $output);

        TestHelper::setConfigValues(array('BILLABLE_SESSION_LENGTH' => '40', 'VISIT_MAX_TIME' => '80', 'VISIT_INACTIVITY_TIMEOUT' => '100'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionIdleLength");
        $this->assertIdentical('4800', $output);

        TestHelper::setConfigValues(array('VISIT_MAX_TIME' => '50'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionIdleLength");
        $this->assertIdentical('3000', $output);

        TestHelper::setConfigValues(array('VISIT_MAX_TIME' => '100', 'VISIT_INACTIVITY_TIMEOUT' => '60'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionIdleLength");
        $this->assertIdentical('3600', $output);

        TestHelper::setConfigValues($config, true);
    }

    function getSessionIdleLength() {
        echo get_instance()->session->getSessionIdleLength();
    }

    function testGetSessionLengthLimit() {
        $config = TestHelper::getConfigValues(array('BILLABLE_SESSION_LENGTH', 'VISIT_MAX_TIME', 'VISIT_INACTIVITY_TIMEOUT'));

        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionLengthLimit");
        $this->assertIdentical('14400', $output);

        TestHelper::setConfigValues(array('BILLABLE_SESSION_LENGTH' => '40', 'VISIT_MAX_TIME' => '80', 'VISIT_INACTIVITY_TIMEOUT' => '100'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionLengthLimit");
        $this->assertIdentical('4800', $output);

        TestHelper::setConfigValues(array('VISIT_MAX_TIME' => '50'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionLengthLimit");
        $this->assertIdentical('3000', $output);

        TestHelper::setConfigValues(array('VISIT_MAX_TIME' => '100', 'VISIT_INACTIVITY_TIMEOUT' => '60'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getSessionLengthLimit");
        $this->assertIdentical('6000', $output);

        TestHelper::setConfigValues($config, true);
    }

    function getSessionLengthLimit() {
        echo get_instance()->session->getSessionLengthLimit();
    }

    //@@@ QA 120424-000044
    function testProfileCookieWithInterface() {
        $sessionCookieData = Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('s' => array('i' => Api::intf_id(), 's' => $this->CI->session->getSessionData('sessionID')))));

        $authToken = $this->logIn()->getValue($this->CI->session)->authToken;
        $profileCookieData = Api::ver_ske_encrypt_urlsafe(json_encode(array('i' => Api::intf_id(), 'c' => $authToken)));
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/returnIsLoggedIn", array('cookie' => "cp_session=$sessionCookieData;cp_profile=$profileCookieData;"));
        $this->assertTrue('true' === $output);

        $cookieData = Api::ver_ske_encrypt_urlsafe(json_encode(array('i' => Api::intf_id() === 1 ? 2 : 1, 'c' => $authToken)));
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/returnIsLoggedIn", array('cookie' => "cp_session=$sessionCookieData;cp_profile=$profileCookieData;"));
        $this->assertTrue('false' === $output);

        $this->logOut();
    }

    function returnIsLoggedIn() {
        echo var_export($this->CI->session->isLoggedIn(), true);
    }


    function testIsValidSessionID() {
        list (, $method) = $this->reflect('method:isValidSessionID');
        $instance = \RightNow\Libraries\Session::getInstance();

        $isValidSessionID = function($sessionID) use ($method, $instance) {
            return $method->invoke($instance, $sessionID);
        };

        $this->assertFalse($isValidSessionID(''));
        $this->assertFalse($isValidSessionID(null));

        // bad characters
        $this->assertFalse($isValidSessionID('7n5eu~4mxx'));
        $this->assertFalse($isValidSessionID('7n5eu@4mxx'));
        $this->assertFalse($isValidSessionID('7n5eu`4mxx'));
        // too short
        $this->assertFalse($isValidSessionID('7n5euT4'));
        $this->assertFalse($isValidSessionID('7'));
        // too long
        $this->assertFalse($isValidSessionID('7n5euT4xgxg'));
        $this->assertFalse($isValidSessionID('7n5euT4xgxgXXXxxxXX'));

        // 8 or 10 alpha-numeric chars plus [*._]
        $this->assertTrue($isValidSessionID('7n5euT4m'));
        $this->assertTrue($isValidSessionID('7n5eu*4m'));
        $this->assertTrue($isValidSessionID('7n5eu.4m'));
        $this->assertTrue($isValidSessionID('7n5eu_4m'));
        $this->assertTrue($isValidSessionID('7n5euT4mxx'));
        $this->assertTrue($isValidSessionID('7n5eu*4mxx'));
        $this->assertTrue($isValidSessionID('7n5eu.4mxx'));
        $this->assertTrue($isValidSessionID('7n5eu_4mxx'));

    }

    function testVerifyContactLogin() {
        list (, $method, $sessionData, $profileData) = $this->reflect('method:verifyContactLogin', 'sessionData', 'profileData');
        $instance = \RightNow\Libraries\Session::getInstance();

        $verifyContactLogin = function() use ($method, $instance) {
            return $method->invoke($instance);
        };

        // Method returns null when contact not logged-in
        $this->assertNull($verifyContactLogin());

        $this->logIn();
        $originalSessionData = $sessionData->getValue($instance);
        $originalProfileData = $profileData->getValue($instance);

        // Method returns a contact profile object and the sessionID remains unchanged for a logged in contact with a valid session ID.
        $sessionID = $originalSessionData->sessionID;
        $this->assertNotNull($sessionID);
        $profile = $verifyContactLogin();
        $this->assertIsA($profile, 'stdClass');
        $this->assertIdentical($sessionID, $sessionData->getValue($instance)->sessionID);

        // set an invalid authToken/cookie and make sure a null profile is returned
        $this->assertNotNull($profileData->getValue($instance)->authToken);
        $newProfileData = clone $originalProfileData;
        $newProfileData->authToken = 'shoes';
        $profileData->setValue($instance, $newProfileData);
        $profile = $verifyContactLogin();
        $this->assertNull($profile);
        $profileData->setValue($instance, $originalProfileData);

        // An invalid session ID causes and new one to be generated
        $badSessionID = '7n5eu~4m';
        $newSessionData = clone $originalSessionData;
        $newSessionData->sessionID = $badSessionID;
        $sessionData->setValue($instance, $newSessionData);
        $this->assertIdentical($badSessionID, $sessionData->getValue($instance)->sessionID);
        $verifyContactLogin();
        $this->assertNotIdentical($badSessionID, $sessionData->getValue($instance)->sessionID);

        $sessionData->setValue($instance, $originalSessionData);
        $this->logOut();
    }

    function testCreateAndExtractUrlSafeSessionID() {
        $sessionID = "walrus";

        $instance = \RightNow\Libraries\Session::getInstance();
        $createUrlSafeSessionID = $this->getMethod('createUrlSafeSessionID', false, $instance);
        $extractSessionID = $this->getMethod('extractSessionID', false, $instance);

        $encrypted = $createUrlSafeSessionID($sessionID);
        $this->assertIsA($encrypted, 'string');
        $this->assertTrue(Text::getMultibyteStringLength($encrypted) > 8);

        $unencrypted = $extractSessionID($encrypted);
        $this->assertIdentical("walrus", $unencrypted);
    }

    function testBadExtractUrlSafeSessionID() {
        $invalidSessionParam = "walrusshoes";

        $instance = \RightNow\Libraries\Session::getInstance();
        $extractSessionID = $this->getMethod('extractSessionID', false, $instance);

        $this->assertNull($extractSessionID($invalidSessionParam));
    }

    // @@@ 140326-000136 Make sure openLoginUsed is preserved in profileData after call to processNoCookie
    function testProcessNoCookie() {
        list (, $sessionData) = $this->reflect('sessionData');
        $instance = \RightNow\Libraries\Session::getInstance();

        $this->logIn();
        $origSessionID = $sessionData->getValue($instance)->sessionID;
        $lastActivity  = $sessionData->getValue($instance)->lastActivity;
        $secureSessionID = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('session_id' => $origSessionID))));

        $output = \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/runProcessNoCookie/session/" . base64_encode("/time/" . $lastActivity . "/sid/" . $secureSessionID));
        $this->assertTrue('true' === $output);
        
        $samlLoginoutput = \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
        		. urlencode(__FILE__) . "/" . __CLASS__ . "/runProcessNoCookieForSAMLLogin/session/" . base64_encode("/time/" . $lastActivity . "/sid/" . $secureSessionID));
        $this->assertTrue('true' === $samlLoginoutput);
        
        $this->logOut();
    }

    function runProcessNoCookie() {
        list (, $method, $profileData) = $this->reflect('method:processNoCookie', 'profileData');
        $instance = \RightNow\Libraries\Session::getInstance();

        $processNoCookie = function() use ($method, $instance) {
            return $method->invoke($instance);
        };

        $this->logIn();

        $originalProfileData = $profileData->getValue($instance);
        $openLoginUsed = array('userID'=>'1234567890', 'source'=>'facebook');
        $originalProfileData->openLoginUsed = $openLoginUsed;
        $profileData->setValue($instance, $originalProfileData);

        $processNoCookie();

        $updatedProfileData = $profileData->getValue($instance);
        echo var_export($updatedProfileData->openLoginUsed == $openLoginUsed, true);
    }
    
    function runProcessNoCookieForSAMLLogin() {
        list (, $method, $profileData) = $this->reflect('method:processNoCookie', 'profileData');
    	$instance = \RightNow\Libraries\Session::getInstance();
    
    	$processNoCookie = function() use ($method, $instance) {
    		return $method->invoke($instance);
    	};
    
    	$this->logIn();
    
    	$originalProfileData = $profileData->getValue($instance);
    	$originalProfileData->samlLoginUsed = true;
    	$profileData->setValue($instance, $originalProfileData);
    
    	$processNoCookie();
    
    	$updatedProfileData = $profileData->getValue($instance);
    	echo var_export($updatedProfileData->samlLoginUsed);
    }

    private function extractCookie($output, $cookieName='cp_profile') {
        $matches = preg_match("/Set-Cookie: $cookieName=([A-Za-z0-9_%~!]+);/", $output, $profileCookie);
        return array($matches, $profileCookie[1]);
    }

    function writeProfileWithExpiration() {
        get_instance()->session->createProfileCookieWithExpiration($this->fakeProfile(), 1);
    }

    function fakeProfile() {
        $profileData = new \RightNow\Libraries\ProfileData();
        $profileData->authToken = 'whatev';
        $profileData->login = 'banana';

        return $profileData;
    }

    function performLogout() {
        get_instance()->session->performLogout();
    }

    function getProfileCookieLength() {
        echo get_instance()->session->getProfileCookieLength();
    }

    function writeProfile() {
        get_instance()->session->createProfileCookie($this->fakeProfile());
    }

    function destroyProfile() {
        get_instance()->session->destroyProfile();
    }

    function isProfileFlagCookieSet() {
        var_export(get_instance()->session->isProfileFlagCookieSet());
    }

    function writePtaProfile() {
        get_instance()->session->setPTA($this->fakeProfile());
    }

    function returnSession() {
        echo $this->CI->session->getSessionData('sessionID');
    }
}

class ProfileDataTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\ProfileData';

    function testConvertToCookie() {
        // Empty (aside from interface id)
        $emptyish = array('i' => Api::intf_id());
        $profileData = new ProfileData;
        $this->assertIdentical($emptyish, $profileData->convertToCookie());
        $profileData->bananas = 'Tropical';
        $this->assertIdentical($emptyish, $profileData->convertToCookie());
        $profileData->contactID = 12;
        $this->assertIdentical($emptyish, $profileData->convertToCookie());
        $profileData->authToken = '';
        $profileData->ptaLoginUsed = 0;
        $profileData->openLoginUsed = false;
        $profileData->samlLoginUsed = false;
        $profileData->forcefulLogoutTime = null;
        $profileData->loginStartTime = array();
        $this->assertIdentical($emptyish, $profileData->convertToCookie());

        // Values
        $input = array(
            'authToken' => array('value' => 'bananas', 'key' => 'c'),
            'ptaLoginUsed' => array('value' => 'geisha', 'key' => 'p'),
            'openLoginUsed' => array('value' => 'platinum', 'key' => 'o'),
            'samlLoginUsed' => array('value' => 'mango', 'key' => 'm'),
            'forcefulLogoutTime' => array('value' => 'sit', 'key' => 'f'),
            'loginStartTime' => array('value' => 'there', 'key' => 'l'),
            'socialUserID' => array('value' => 'know', 'key' => 's'),
        );
        foreach ($input as $name => $info) {
            $profileData->{$name} = $info['value'];
        }
        $result = $profileData->convertToCookie();
        foreach ($input as $name => $info) {
            $this->assertIdentical($result[$info['key']], $info['value']);
        }

        // Overrides
        $result = $profileData->convertToCookie(array(
            'authToken' => 'turn',
            'socialUserID' => 'around',
        ));
        $this->assertIdentical('turn', $result['c']);
        $this->assertIdentical('around', $result['s']);
    }

    function testNewFromCookie() {
        // Doesn't validate
        $this->assertFalse(ProfileData::newFromCookie('array'));
        $this->assertFalse(ProfileData::newFromCookie(array()));
        $result = ProfileData::newFromCookie(array(
            'c' => '',
            'p' => false,
            'o' => 0,
            'm' => false,
            'f' => null,
            'l' => array(),
        ));
        $this->assertFalse($result);
        $result = ProfileData::newFromCookie(array(
            'c' => '',
            'p' => false,
            'o' => 0,
            'm' => false,
            'f' => null,
            'l' => array(),
            'i' => 34,
        ));
        $this->assertFalse($result);

        // Empty
        $result = ProfileData::newFromCookie(array(
            'c' => '',
            'p' => false,
            'o' => 0,
            'm' => false,
            'f' => null,
            'l' => array(),
            'i' => Api::intf_id(),
        ));
        $this->assertIsA($result, get_class(new ProfileData));

        // Values
        $values = array(
            'c' => array('value' => 'bananas', 'key' => 'authToken'),
            'p' => array('value' => 'geisha', 'key' => 'ptaLoginUsed'),
            'o' => array('value' => 'platinum', 'key' => 'openLoginUsed'),
            'm' => array('value' => 'mango', 'key' => 'samlLoginUsed'),
            'f' => array('value' => 'sit', 'key' => 'forcefulLogoutTime'),
            'l' => array('value' => 'there', 'key' => 'loginStartTime'),
            's' => array('value' => 'know', 'key' => 'socialUserID'),
            'i' => array('value' => Api::intf_id()),
        );
        $input = array();
        foreach ($values as $key => $info) {
            $input[$key] = $info['value'];
        }
        $result = ProfileData::newFromCookie($input);
        $this->assertIsA($result, get_class(new ProfileData));
        foreach ($values as $key => $info) {
            if ($info['key']) {
                $prop = $info['key'];
                $this->assertIdentical($info['value'], $result->{$prop});
            }
        }

        // BC
        $result = ProfileData::newFromCookie(array(
            'cookie' => 'mystery',
            'ptaLoginUsed' => 'transparency',
            'i' => Api::intf_id(),
        ));
        $this->assertIdentical('mystery', $result->authToken);
        $this->assertIdentical('transparency', $result->ptaLoginUsed);
    }

    function testGetPersistentPropertyKeys() {
        $result = ProfileData::getPersistentPropertyKeys();
        $this->assertIdentical(array_keys($result), range(0, count($result) - 1));
    }
}

class SessionDataTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\FlashData';

    function testNewFromCookie() {
        // Validation errors
        $this->assertFalse(SessionData::newFromCookie('array'));
        $this->assertFalse(SessionData::newFromCookie(array()));
        $this->assertFalse(SessionData::newFromCookie(array(
            's' => 'session',
            'a' => 1,
            'n' => 0,
            'e' => 'bananas',
        )));
        $this->assertFalse(SessionData::newFromCookie(array(
            's' => 'session',
            'a' => 1,
            'n' => 0,
            'e' => 'bananas',
            'i' => 34,
        )));

        $result = SessionData::newFromCookie($input = array(
            's' => 'session',
            'a' => 1,
            'n' => 0,
            'e' => 'bananas',
            'i' => Api::intf_id(),
        ));

        $this->assertIsA($result, 'RightNow\Libraries\SessionData');
        $this->assertIdentical($input['s'], $result->sessionID);
        $this->assertIdentical($input['a'], $result->answersViewed);
        $this->assertIdentical($input['n'], $result->numberOfSearches);
        $this->assertIdentical($input['e'], $result->sessionString);
    }
}

class FlashDataTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\FlashData';

    function setUp() {
        list(
            $this->class,
            $this->data
        ) = $this->reflect('data');

        $this->flashData = new FlashData;

        parent::setUp();
    }

    function tearDown() {
        $this->class =
        $this->data =
        $this->flashData = null;

        parent::tearDown();
    }

    function testConstructorRestoresOldItems() {
        $instance = $this->class->newInstanceArgs(array(array(
            'banana' => 'foo',
            'i'      => 'nonono',
            'a'      => 'walrus',
        )));

        $prefix = FlashData::OLD_DATA;

        $this->assertIdentical(array(
            "{$prefix}banana"    => 'foo',
            "{$prefix}info"      => 'nonono',
            "{$prefix}alert"     => 'walrus',
        ), $this->data->getValue($instance));
    }

    function testConstructorWithUnicodeItems() {
        $instance = $this->class->newInstanceArgs(array(array(
            'a' => "%5Cu8cea%5Cu554f%5Cu304c%5Cu4f5c%5Cu6210%5Cu3055%5Cu308c%5Cu307e%5Cu3057%5Cu305f",
            'i' => "walrus's shoes and \"books\"",
        )));

        $prefix = FlashData::OLD_DATA;

        $this->assertIdentical(array(
            "{$prefix}alert" => "\u8cea\u554f\u304c\u4f5c\u6210\u3055\u308c\u307e\u3057\u305f",
            "{$prefix}info" => "walrus's shoes and \"books\"",
        ), $this->data->getValue($instance));
    }

    function testNoParamsToConstructorIsANoOp() {
        $instance = $this->class->newInstance();
        $this->assertIdentical(array(), $this->data->getValue($instance));
    }

    function testSetSetsANewItem() {
        $this->flashData->bananas = 'yeaaah';

        $this->assertIdentical(array(FlashData::NEW_DATA . 'bananas' => 'yeaaah'), $this->data->getValue($this->flashData));
    }

    function testGetReturnsOldItem() {
        $this->data->setValue($this->flashData, array(FlashData::OLD_DATA . 'bananas' => 'yep'));
        $this->assertIdentical('yep', $this->flashData->bananas);
    }

    function testGetAlsoReturnsNewItemIfOldDoesNotExist() {
        $this->flashData->bananas = 'yep';
        $this->assertIdentical('yep', $this->flashData->bananas);
    }

    function testKeepDoesNotKeepWhatIsNotSet() {
        $this->flashData->keep('bananas');
        $this->assertIdentical(array(), $this->data->getValue($this->flashData));
    }

    function testKeep() {
        $this->data->setValue($this->flashData, array(FlashData::OLD_DATA . 'bananas' => 'yep'));
        $this->flashData->keep('bananas');
        $this->assertIdentical(array(FlashData::NEW_DATA . 'bananas' => 'yep'), $this->data->getValue($this->flashData));
    }

    function testConvertToCookieOnlyReturnsNewItems() {
        $this->data->setValue($this->flashData, array(
            FlashData::OLD_DATA . 'nope' => 'no',
            FlashData::NEW_DATA . 'bananas' => 'yep',
        ));

        $this->assertIdentical(array('bananas' => 'yep'), $this->flashData->convertToCookie());
    }

    function testConvertToCookieReturnsEmptyArrayIfNoNewItems() {
        $this->data->setValue($this->flashData, array(FlashData::OLD_DATA . 'nope' => 'no'));
        $this->assertIdentical(array(), $this->flashData->convertToCookie());
    }

    function testConvertToCookieShortensCommonPropertyNames() {
        $this->data->setValue($this->flashData, array(
            FlashData::NEW_DATA . 'info' => 'hey',
            FlashData::NEW_DATA . 'alert' => 'yep',
        ));

        $this->assertIdentical(array('i' => 'hey', 'a' => 'yep'), $this->flashData->convertToCookie());
    }
}
