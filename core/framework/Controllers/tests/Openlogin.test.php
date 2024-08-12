<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Controllers,
    RightNow\Api,
    RightNow\Libraries\OpenLoginErrors,
    RightNow\Internal\Libraries\OpenLogin,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Connect as ConnectUtils,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\UnitTest\Fixture;

class OpenLoginTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Openlogin';
    protected static $hookData;

    function __construct() {
        parent::__construct();
        $this->fixtureInstance = new Fixture();
    }

    function tearDown() {
        $this->fixtureInstance->destroy();
    }

    function testOpenid() {
        $url = '/ci/openlogin/openid/authorize/facebook';
        $output = $this->makeRequest($url);
        $this->assertEqual($output, "The requesting page must be within CP");

        $url = '/ci/openlogin/openid/banana/authorize';
        $output = $this->makeRequest($url);
        $this->assertEqual($output, "The specified action or provider is incorrect");
    }

    function testOAuthEntryPoint(){
        //invalid action
        $url = '/ci/openlogin/oauth/facebook/banana';
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The specified action or provider is incorrect");

        //invalid provider
        $url = '/ci/openlogin/oauth/banana/authorize';
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The specified action or provider is incorrect");

        //invalid redirect parameter -> must be a single urlencoded value
        $url = '/ci/openlogin/oauth/authorize/facebook/app/answers/list/banana';
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The redirect parameter must be a URL-encoded, CP URL segment");

        //invalid redirect parameter
        $url = '/ci/openlogin/oauth/authorize/facebook/' . urlencode('http://google.com');
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The redirect parameter must be a URL-encoded, CP URL segment");

        //invalid referer
        $url = '/ci/openlogin/oauth/authorize/facebook/';
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The requesting page must be within CP");

        //invalid openid redirect parameter -> must be a single urlencoded value
        $url = '/ci/openlogin/openid/authorize/google/app/answers/list/';
        $output =$this->makeRequest($url);
        $this->assertEqual($output, "The redirect parameter must be a URL-encoded, CP URL segment");
    }

    function testTwitterRedirectOnError() {
        // If there's a problem with the original token request, we stay in CP and display
        // an error message rather than redirecting to, and abandoning, the user on Twitter's cryptic error page.
        $configs = array(
            'TWITTER_OAUTH_APP_ID'     => 'banana',
            'TWITTER_OAUTH_APP_SECRET' => 'banana',
        );
        foreach ($configs as $key => $value) {
            $configs[$key] = \Rnow::updateConfig($key, $value);
        }

        $url = '/ci/openlogin/oauth/authorize/twitter';
        $output = $this->makeRequest($url, array('includeHeaders' => true, 'referer' => Url::getShortEufBaseUrl()));

        // Handle known OCI machine network issue
        if (Text::stringContains($output, 'error_code: 7') !== true) {
            if (Text::stringContains($output, '302 Moved Temporarily') !== true || Text::stringContains($output, 'oautherror/4') !== true) {
                var_dump($output); //Temporary logging. This test fails randomly and when it does, we want to see what happened.
            }

            $this->assertTrue(Text::stringContains($output, '302 Moved Temporarily'));
            $this->assertTrue(Text::stringContains($output, 'oautherror/4'));
        }
        else {
            $this->assertTrue(Text::stringContains($output, 'error_code: 7'));
        }

        foreach ($configs as $key => $value) {
            \Rnow::updateConfig($key, $value);
        }
    }

    function testFacebookRegistration() {
        $response = $this->makeRequest('/ci/Openlogin/facebookRegistration', array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertTrue(Text::stringContains($response, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($response, '/app/home'));

        // Ensure an invalid field value results in the contact update|create error being logged to phpoutlog
        $configName = 'FACEBOOK_OAUTH_APP_SECRET';
        $configValue = 'ITSASECRET';
        $configs = TestHelper::getConfigValues(array($configName));
        TestHelper::setConfigValues(array($configName => $configValue), true);
        $nonSupportedDate = 86399;
        $data = array(
            'algorithm' => 'HMAC-SHA256',
            'user_id' => 23232,
            'registration' => array(
                'email' => 'foo@bar.baz',
                'first_name' => 'kevin',
                'last_name' => 'bacon',
                'c$datetime1' => $nonSupportedDate,
            ),
        );
        $payload = base64_encode(json_encode($data));
        $logPath = Api::cfg_path() . '/log';
        umask(0);
        $this->assertIsA(file_put_contents("$logPath/tr.cphp", 'ALL TIME'), 'int', 'tr.cphp file not written to disk');

        $response = $this->makeRequest('/ci/Openlogin/facebookRegistration', array(
            'justHeaders' => true,
            'post' => "signed_request=$configValue.$payload",
        ));
        unlink("$logPath/tr.cphp");
        $logged = false;
        $expected = "datetime1 value is too small (min value: 86400)";

        sleep(1);
        foreach(glob("$logPath/cphp*.tr") as $logFile) {
            if (Text::stringContains(file_get_contents($logFile), $expected)) {
                $logged = true;
            }
            unlink($logFile);
        }
        $this->assertTrue($logged, 'Contact update error not logged to phpoutlog');

        TestHelper::setConfigValues($configs);
    }

    function callHook($hookName, array $data = array(), array $parameters = array()) {
        $hooks = TestHelper::getHooks();
        $hooks->setValue(array($hookName => $parameters ?: array(
            'class' => 'OpenLoginTest',
            'function' => 'hookEndpoint',
            'filepath' => 'Controllers/tests/Openlogin.test.php'
        )));
        \RightNow\Libraries\Hooks::callHook($hookName, $data);
    }

    static function hookEndpoint($data) {
        self::$hookData = $data;
    }

    function preLoginHook() {
        self::$hookData = null;
        $hookName = 'pre_login';

        $this->callHook($hookName, array($hookName));
        $this->assertIdentical($hookName, self::$hookData[0]);

        $userInfo = new OpenLogin\TwitterUser((object) array('screen_name' => 'bananas2', 'id_str' => 42,));
        $userInfo->email = 'banana@bananas2.invalid';

        $this->setMockSession();
        $this->CI->session->returns('getSessionData','WlhOdmJX', array('sessionID'));

        list($class, $loginUserMethod) = $this->reflect('method:_loginUser');

        $openloginInstance = $class->newInstance();
        $openloginInstance->session = $this->CI->session;

        $results = $loginUserMethod->invokeArgs($openloginInstance, array($userInfo, 'twitter'));
        $this->assertTrue($results);
        $this->assertIsA(self::$hookData, 'array');
        $this->assertIsA(self::$hookData['data'], 'array');
        $this->assertIsA(self::$hookData['data']['source'], 'string');
        $this->assertEqual(self::$hookData['data']['source'], 'OPENLOGIN');

        $this->unsetMockSession();
    }

    function testPreLoginHook() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/preLoginHook");
        $this->assertSame('', $result);
    }

    function postLoginHook() {
        self::$hookData = null;
        $hookName = 'post_login';

        $this->callHook($hookName, array($hookName));
        $this->assertIdentical($hookName, self::$hookData[0]);

        $userInfo = new OpenLogin\TwitterUser((object) array('screen_name' => 'bananas2', 'id_str' => 42,));
        $userInfo->email = 'banana@bananas2.invalid';

        list($class, $loginUserMethod) = $this->reflect('method:_loginUser');

        $openloginInstance = $class->newInstance();
        $openloginInstance->session = $this->CI->session;

        $results = $loginUserMethod->invokeArgs($openloginInstance, array($userInfo, 'twitter'));
        $this->assertTrue($results);
        $this->assertIsA(self::$hookData, 'array');
        $this->assertIsA(self::$hookData['data'], 'array');
        $this->assertIsA(self::$hookData['data']['source'], 'string');
        $this->assertEqual(self::$hookData['data']['source'], 'OPENLOGIN');
        $this->assertIsA(self::$hookData['returnValue']->contactID, 'int');
        $this->assertTrue(self::$hookData['returnValue']->contactID > 0);

        $this->logout();
    }

    function testPostLoginHook() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/postLoginHook");
        $this->dump($result);
        $this->assertSame('', $result);
    }

    function preOpenLoginLookupContactHook() {
        self::$hookData = null;
        $hookName = 'pre_openlogin_lookup_contact';

        $this->callHook($hookName, array($hookName));
        $this->assertIdentical($hookName, self::$hookData[0]);

        $userInfo = new OpenLogin\TwitterUser((object) array('screen_name' => 'bananas2', 'id' => 42,));
        $userInfo->email = 'banana@bananas2.invalid';

        $this->setMockSession();
        $this->CI->session->returns('getSessionData','WlhOdmJX', array('sessionID'));

        list($class, $loginOpenIDUserMethod) = $this->reflect('method:_loginOpenIDUser');

        $openloginInstance = $class->newInstance();
        $openloginInstance->session = $this->CI->session;

        $loginOpenIDUserMethod->invokeArgs($openloginInstance, array($userInfo, 'twitter@twitter.com'));
        $this->assertIsA(self::$hookData, 'array');
        $this->assertIsA(self::$hookData['user'], 'RightNow\Internal\Libraries\OpenLogin\TwitterUser');
    }

    function testPreOpenLoginLookupContactHook() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/preOpenLoginLookupContactHook");
        $this->assertSame('', $result);
    }

    function preOpenLoginDecodeHook() {
        self::$hookData = null;
        $hookName = 'pre_openlogin_decode';

        $this->callHook($hookName, array($hookName));
        $this->assertIdentical($hookName, self::$hookData[0]);

        list($class, $loginOpenIDUserMethod) = $this->reflect('method:_getOpenIDUserInfo');

        $client = new \RightNow\Controllers\Client();
        $client->loadCurl();
        require_once CPCORE . 'Libraries/ThirdParty/LightOpenID.php';
        $openIDObject = new \RightNow\Libraries\ThirdParty\LightOpenID();

        $openloginInstance = $class->newInstance();
        $openloginInstance->session = $this->CI->session;

        $loginOpenIDUserMethod->invokeArgs($openloginInstance, array($openIDObject, 'twitter'));
        $this->assertIdentical(self::$hookData['openIDObject'], $openIDObject);
    }

    function testPreOpenLoginDecodeHook() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/preOpenLoginDecodeHook");
        $this->assertSame('', $result);
    }

    function testPostOpenLoginAuthURL(){
        // Nothing to test here because of non availability of environment
    }

    function testOpenLoginBuildURL(){
        // Nothing to test here because of non availability of environment
    }

    function testLogout() {
        $baseUrl = '/ci/openlogin/logout/';
        $home = '/app/home';
        $redirectUrl = null;

        $options = array(
            'justHeaders' => true,
            'cookie' => 'cp_login_start=1',
        );

        $getLocation = function($url) {
            return "Location: $url [following]";
        };

        $redirectHappened = function($response, $url = '/app/home') use ($getLocation) {
            $statusCode = '302 Moved Temporarily';
            return (Text::stringContains($response, "HTTP/1.1 $statusCode") || Text::stringContains($response, "HTTP/1.0 $statusCode"))
                && Text::stringContains($response, $getLocation($url));
        };

        // As a biproduct of QA 191113-000056 we have changed the behavior
        // to only redirect to the $redirectUrl when the contacts are already
        // logged in. There is no use case where the contact can use the endpoint
        // ci/openlogin/logout/<url param> without first being logged in.
        // Otherwise the default behavior will be to redirect to /app/home

        // No redirect specified
        $response = $this->makeRequest($baseUrl, $options);
        $this->assertStatusCode($response, "200 OK");
        $this->assertTrue(Text::stringContains($response, 'Location:'));

        // Valid relative redirects
        $redirectUrl = 'app/home';
        $this->assertTrue($redirectHappened($this->makeRequest("{$baseUrl}{$redirectUrl}", $options)));

        $redirectUrl = 'app/answers/list';
        $response = $this->makeRequest("{$baseUrl}{$redirectUrl}", $options);
        $this->assertFalse($redirectHappened($this->makeRequest("{$baseUrl}{$redirectUrl}", $options), "/$redirectUrl"));

        // Accepted absolute URL as it matches PTA_EXTERNAL_POST_LOGOUT_URL
        $redirectUrl = 'http://www.google.com';
        $this->setPtaUrl($redirectUrl, true);
        $encoded = urlencode($redirectUrl);
        $response = $this->makeRequest("{$baseUrl}{$encoded}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));

        $redirectUrl = 'www.google.com';
        $this->setPtaUrl($redirectUrl, true);
        $encoded = urlencode($redirectUrl);
        $response = $this->makeRequest("{$baseUrl}{$encoded}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));

        $this->setPtaUrl('', true);

        // BAD absolute URLs. Redirect to /app/home
        $redirectUrl = 'http://www.someMaliciousUrl.com';
        $response = $this->makeRequest("{$baseUrl}{$redirectUrl}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));
        $this->assertTrue($redirectHappened($response, $home));

        $redirectUrl = urlencode('http://www.someMaliciousUrl.com');
        $response = $this->makeRequest("{$baseUrl}{$redirectUrl}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));
        $this->assertTrue($redirectHappened($response, $home));

        $redirectUrl = urlencode('://www.someMaliciousUrl.com');
        $response = $this->makeRequest("{$baseUrl}{$redirectUrl}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));
        $this->assertTrue($redirectHappened($response, $home));

        $redirectUrl = urlencode('www.someMaliciousUrl.com');
        $response = $this->makeRequest("{$baseUrl}{$redirectUrl}", $options);
        $this->assertFalse($redirectHappened($response, $redirectUrl));
        $this->assertTrue($redirectHappened($response, $home));
    }

    function setPtaUrl($url, $save = false) {
        \Rnow::updateConfig('PTA_EXTERNAL_POST_LOGOUT_URL', $url, !$save);
    }

    function testValidateRedirect() {
        $method = $this->getMethod('_validateRedirect');

        $testInputs = function($inputs) use ($method) {
            $fails = array();
            foreach ($inputs as $input) {
                list($isValid, $redirect, $expected) = $input;
                if ($isValid && $expected === null) {
                    $expected = $redirect;
                }
                $actual = $method($redirect);
                if ($isValid && (!$actual || $actual !== $expected)) {
                    $fails[] = ("Expected: '$expected' received: '$actual'");
                }
                else if (!$isValid && $actual) {
                    $fails[] = ("Expected an invalid redirect url, but method returned: '$actual'");
                }
            }

            return $fails;
        };

        $baseInputs = array(
            array(true, '/app/home'),
            array(true, 'app/home', '/app/home'),
            array(true, 'app/home/', '/app/home/'),
            array(true, '/app/home/'),
            array(true, '/app/answers/list'),
            array(true, 'app/answers/list', '/app/answers/list'),
            array(true, '/ci/foo/whatever'),
            array(false, null),
            array(false, ''),
            array(false, 'http://www.someMaliciousUrl.com'),
            array(false, '://www.someMaliciousUrl.com'),
            array(false, '//www.someMaliciousUrl.com'),
            array(false, 'www.someMaliciousUrl.com'),
            array(false, 'c:\\malicious\\bad.exe'),
            array(false, '\\malicious\\bad.exe'),
            array(false, '\\\malicious\\\bad.exe'),
            array(false, '\\\\malicious\\\\bad.exe'),
        );

        $this->setPtaUrl('');
        $inputs = array_merge($baseInputs, array(
            array(false, 'http://www.google.com'),
            array(false, 'www.google.com'),
        ));
        foreach($testInputs($inputs) as $error) {
            $this->fail($error);
        }

        $this->setPtaUrl('http://www.google.com');
        $inputs = array_merge($baseInputs, array(
            array(true, 'http://www.google.com'),
            array(true, 'www.google.com'),
        ));
        foreach($testInputs($inputs) as $error) {
            $this->fail($error);
        }

        // No protocol specified
        $this->setPtaUrl('www.google.com');
        foreach($testInputs($inputs) as $error) {
            $this->fail($error);
        }

        $this->setPtaUrl('');
    }

    function _updateSocialUser() {
        list($class, $updateSocialUser) = $this->reflect('method:_updateSocialUser');

        $openloginInstance = $class->newInstance();
        $openloginInstance->session = $this->CI->session;

        $openLoginUser = new OpenLogin\TwitterUser((object) array(
            'email'             => 'brucewayne@notthebatman.com',
            'id'                => 'www.batcountry.com',
            'name'              => 'Bruce Wayneman',
            'profile_image_url' => 'http://placebatman.com/200/150'
        ));

        // Try Creating a socialUser without userName being set
        \RightNow\Libraries\AbuseDetection::check();
        $contact = $this->createContact();
        $this->logIn('slatest');
        $this->assertNull($contact->CommunityUser);
        $results = $updateSocialUser->invokeArgs($openloginInstance, array($contact, $openLoginUser));
        $this->assertNull($contact->CommunityUser);
        $this->assertFalse($results);

        // Create a new CommunityUser
        $openLoginUser->userName = 'XxTheRealBats406xX';
        $results = $updateSocialUser->invokeArgs($openloginInstance, array($contact, $openLoginUser));
        $this->assertNotNull($results);
        $this->assertTrue(Text::stringContains(get_class($results), 'CommunityUser'));
        $this->assertSame($results->AvatarURL, $openLoginUser->avatarUrl);
        $this->assertSame($results->DisplayName, $openLoginUser->userName);
        $this->logOut();
        $this->fixtureInstance->destroy();

        // Update an Existing CommunityUser
        $socialUser = $this->fixtureInstance->make('UserActive1');
        $this->logIn($socialUser->ID);
        $this->assertNull($socialUser->AvatarURL);
        $this->assertSame($socialUser->DisplayName, 'Active User1');
        $results = $updateSocialUser->invokeArgs($openloginInstance, array($socialUser->Contact, $openLoginUser));
        $this->assertTrue(Text::stringContains(get_class($results), 'CommunityUser'));
        // Since avatarUrl isn't set yet, we update it to the avatar given by OpenLogin
        $this->assertSame($results->AvatarURL, $openLoginUser->avatarUrl);
        // Since DisplayName is already set on the account, don't update it
        $this->assertSame($results->DisplayName, 'Active User1');

        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    function testUpdateSocialUser() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/_updateSocialUser");
        $this->assertSame('', $result);
    }
}

class TwitterIntegration extends CPTestCase {
    public $testingClass = '\RightNow\Controllers\OpenLogin';

    function setUp() {
        \Rnow::updateConfig('TWITTER_OAUTH_APP_ID', 'banana', true);
        \Rnow::updateConfig('TWITTER_OAUTH_APP_SECRET', 'banana', true);
        parent::setUp();
    }
    function tearDown() {
        // Set back to null so that the #getConfig process cache check falls thru and any other
        // test that relies on these config values being legit gets the "real" config value.
        \Rnow::updateConfig('TWITTER_OAUTH_APP_ID', null, true);
        \Rnow::updateConfig('TWITTER_OAUTH_APP_SECRET', null, true);
        parent::tearDown();
    }

    function testInitialAuthRequest() {
        list(
            $class,
            $method
        ) = $this->reflect('method:_authorizeTwitter');

        // Error
        $client = new MockClient;
        $controller = $class->newInstanceArgs(array($client));
        $method->invoke($controller, 'callback/banana/url');
        $this->assertTrue(Text::beginsWith($client->getCalledWith[0], Config::getConfig(TWITTER_REQUEST_TOKEN_URL)));
        $this->assertIdentical('/redirect/' . urlencode(urlencode('callback/banana/url')) . '/oautherror/'. OpenLoginErrors::AUTHENTICATION_ERROR, $client->redirectBackToCPPageCalledWith[0]);

        // Redirects successfully
        $client->statusCode = 200;
        $method->invoke($controller, 'callback/banana/url');
        $this->assertTrue(Text::beginsWith($client->redirectToThirdPartyLoginCalledWith[0], Config::getConfig(TWITTER_AUTHENTICATE_URL)));
        $this->assertIdentical('cp_oauth_credentials', $client->setCookieCalledWith[0]);
        $this->assertTrue(strlen($client->setCookieCalledWith[1]) > 1);
    }

    function testCallback() {
        # code...
    }

    function testUserRequest() {
        list(
            $class,
            $method
        ) = $this->reflect('method:_getTwitterUserInfo');

        $client = new MockClient;
        $controller = $class->newInstanceArgs(array($client));
        $method->invoke($controller, array(
            'oauth_token' => 'public token',
            'oauth_token_secret' => 'private token',
            'user_id' => 'banana',
        ));
        $this->assertTrue(Text::beginsWith($client->getCalledWith[0], Config::getConfig(TWITTER_API_URL) . 
            'account/verify_credentials.json?include_entities=false&skip_status=true&include_email=true'));
        $authHeader = $client->getCalledWith[1];
        $this->assertIsA($authHeader, 'array');
        $this->assertSame(1, count($authHeader));
        $this->assertTrue(Text::beginsWith($authHeader[0], 'Authorization: OAuth oauth_'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_version'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_nonce'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_timestamp'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_consumer_key'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_token="public%20token"'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_signature_method'));
        $this->assertTrue(Text::stringContains($authHeader[0], 'oauth_signature'));
    }

    function testProvideEmail() {
        $userData = (object) array(
            'providerName' => 'twitter',
            'id' => 'GlzIGF3ZXNvbWUgYXNsa2Qga2xqYXMgbGR',
            'userName' => 'chuckb',
            'avatarUrl' => 'https://somerandomwebsite.invalid.oracle.com',
            'firstName' => 'Chuck',
            'lastName' => 'Bee'
        );

        $userData = Api::ver_ske_encrypt_fast_urlsafe(serialize($userData));
        $response = $this->makeRequest('/ci/Openlogin/provideEmail',
            array('post' => "email=chuckb_1@bball1.invalid&userData=$userData")
        );

        $this->assertEqual($response, 'true');
    }

    function testProvideInvalidEmail() {
        $userData = (object) array(
            'providerName' => 'twitter',
            'id' => 'GlzIGF3ZXNvbWUgYXNsa2Qga2xqYXMgbGR',
            'avatarUrl' => 'https://somerandomwebsite.invalid.oracle.com',
            'userName' => 'chuckb',
            'firstName' => 'Chuck',
            'lastName' => 'Bee'
        );

        $userData = Api::ver_ske_encrypt_fast_urlsafe(serialize($userData));
        $response = $this->makeRequest('/ci/Openlogin/provideEmail',
            array('post' => "email=chuckb@bb#all1.invalid&userData=$userData")
        );

        $this->assertEqual($response, '');
    }
}

class GoogleIntegration extends CPTestCase {
    public $testingClass = '\RightNow\Controllers\OpenLogin';

    function setUp() {
        \Rnow::updateConfig('GOOGLE_OAUTH_APP_ID', 'banana', true);
        \Rnow::updateConfig('GOOGLE_OAUTH_APP_SECRET', 'banana', true);
        parent::setUp();
    }
    function tearDown() {
        // Set back to null so that the #getConfig process cache check falls thru and any other
        // test that relies on these config values being legit gets the "real" config value.
        \Rnow::updateConfig('GOOGLE_OAUTH_APP_ID', null, true);
        \Rnow::updateConfig('GOOGLE_OAUTH_APP_SECRET', null, true);
        parent::tearDown();
    }

    function testInitialAuthRequest() {
        list(
            $class,
            $method
        ) = $this->reflect('method:_authorizeGoogle');

        $client = new MockClient;
        $controller = $class->newInstanceArgs(array($client));
        $method->invoke($controller, 'callback/banana/url');
        $this->assertTrue(Text::beginsWith($client->redirectToThirdPartyLoginCalledWith[0], \RightNow\Controllers\Openlogin::GOOGLE_AUTH_URL));
    }

    function testCallback() {
        list(
            $class,
            $method
        ) = $this->reflect('method:_callbackGoogle');

        $client = new MockClient;
        $controller = $class->newInstanceArgs(array($client));
        $method->invoke($controller);

        // Error, didn't get the state / redirect url back.
        $this->assertNull($client->postCalledWith);
        $this->assertSame('/app/' . Config::getConfig(CP_LOGIN_URL) . '/redirect//oautherror/4', $client->redirectBackToCPPageCalledWith[0]);

        // Error, with state / redirect url.
        $_REQUEST['state'] = API::ver_ske_encrypt_fast_urlsafe('account/overview/onfail/app/' . Config::getConfig(CP_LOGIN_URL));
        $method->invoke($controller);
        $this->assertNull($client->postCalledWith);
        $this->assertSame('/app/' . Config::getConfig(CP_LOGIN_URL) . '/redirect/account%252Foverview/oautherror/4', $client->redirectBackToCPPageCalledWith[0]);
        unset($_REQUEST['state']);

        // Successful auth token, but unsuccessful access token.
        $_REQUEST['code'] = 'anything';
        $method->invoke($controller);
        $this->assertSame(\RightNow\Controllers\OpenLogin::GOOGLE_ACCESS_URL, $client->postCalledWith[0]);
        $this->assertSame('anything', $client->postCalledWith[1]['code']);
        $this->assertSame(Config::getConfig(GOOGLE_OAUTH_APP_SECRET), $client->postCalledWith[1]['client_secret']);
        $this->assertSame(Config::getConfig(GOOGLE_OAUTH_APP_ID), $client->postCalledWith[1]['client_id']);
        $this->assertNull($client->getCalledWith);
        $this->assertSame('/app/' . Config::getConfig(CP_LOGIN_URL) . '/redirect//oautherror/4', $client->redirectBackToCPPageCalledWith[0]);

        // Successful auth token, access token, but no user data.
        $client->postReturns = json_encode(array('token_type' => 'mimick', 'access_token' => 'bird'));
        $method->invoke($controller);
        $this->assertSame(\RightNow\Controllers\Openlogin::GOOGLE_CONTACT_API_URL, $client->getCalledWith[0]);
        $this->assertIdentical(array('Authorization: mimick bird'), $client->getCalledWith[1]);
        $this->assertSame('/app/' . Config::getConfig(CP_LOGIN_URL) . '/redirect//oautherror/4', $client->redirectBackToCPPageCalledWith[0]);

        unset($_REQUEST['code']);
    }

    function testUserRequest() {
        list(
            $class,
            $method
        ) = $this->reflect('method:_getGoogleUserInfo');

        $client = new MockClient;
        $controller = $class->newInstanceArgs(array($client));

        $user = $method->invokeArgs($controller, array('mimick bird'));
        $this->assertSame(\RightNow\Controllers\OpenLogin::GOOGLE_CONTACT_API_URL, $client->getCalledWith[0]);
        $authHeader = $client->getCalledWith[1];
        $this->assertIsA($authHeader, 'array');
        $this->assertSame(1, count($authHeader));
        $this->assertIdentical(array('Authorization: mimick bird'), $authHeader);
        $this->assertTrue(Text::stringContains(get_class($user), 'GoogleUser'));
        $this->assertNull($user->email);

        $client->getReturns = json_encode(array('email' => 'blood@lines.co'));
        $user = $method->invokeArgs($controller, array('mimick bird'));
        $this->assertTrue(Text::stringContains(get_class($user), 'GoogleUser'));
        $this->assertSame($user->email, 'blood@lines.co');
    }
}

class FacebookIntegration extends CPTestCase {
    # code...
}

class OpenIDIntegration extends CPTestCase {
    # code...
}

class OpenLoginUtilsTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Openlogin';

    function testUpdateContactFields() {
        $method = $this->getMethod('_updateContactFields');

        $contact = get_instance()->model('Contact')->getBlank()->result;
        $userInfo = new OpenLogin\TwitterUser((object) array('screen_name' => 'bananas', 'id' => 57,));
        $userInfo->email = 'banana@bananas.banana';

        // Contact is enabled and email is populated
        $contact->Disabled = true;
        $result = $method($contact, $userInfo, 'twitter');
        $this->assertIdentical(false, $result['Contact.Disabled']->value);
        $this->assertSame($userInfo->email, $result['Contact.Emails.PRIMARY.Address']->value);
        // Login is populated
        $this->assertSame($userInfo->email, $result['Contact.Login']->value);
        // Channels are filled in
        $this->assertSame($userInfo->userName, $result['Contact.ChannelUsernames.TWITTER.Username']->value);
        $this->assertSame($userInfo->id, $result['Contact.ChannelUsernames.TWITTER.UserNumber']->value);
        // name wasn't supplied so it isn't filled in
        $this->assertFalse(array_key_exists('Contact.Name.First', $result));
        $this->assertFalse(array_key_exists('Contact.Name.Last', $result));

        \RightNow\Utils\Connect::setFieldValue($contact, array('Contact', 'Emails', '0', 'Address'), $userInfo->email);
        \RightNow\Utils\Connect::setFieldValue($contact, array('Contact', 'Login'), $userInfo->userName);

        // Same contact comes back later using a different service
        $userInfo = new OpenLogin\FacebookUser((object) array('first_name' => 'Wally', 'last_name' => 'West', 'id' => 'facebookID34', 'email' => 'newbanana@bananas.banana'));
        $result = $method($contact, $userInfo, 'facebook');
        // Not enabled because email isn't null
        $this->assertFalse(array_key_exists('Contact.Disabled', $result));
        // Email is updated because it's different
        $this->assertSame($userInfo->email, $result['Contact.Emails.PRIMARY.Address']->value);
        // Names are updated because they're now supplied
        $this->assertSame($userInfo->firstName, $result['Contact.Name.First']->value);
        $this->assertSame($userInfo->lastName, $result['Contact.Name.Last']->value);

        // Login is not updated because it was non-null
        $this->assertFalse(array_key_exists('Contact.Login', $result));
        // Channels are filled in
        $this->assertSame($userInfo->email, $result['Contact.ChannelUsernames.FACEBOOK.Username']->value);
        $this->assertSame($userInfo->id, $result['Contact.ChannelUsernames.FACEBOOK.UserNumber']->value);
    }

    function testLoadCurl() {
        $client = new \RightNow\Controllers\Client();
        $client->loadCurl();
        $this->assertTrue(extension_loaded('curl'));
    }
}

class SamlTest extends CPTestCase {
        /**
     *  this test path passes in urls beginning with 'ci' which does not
     *  exactly match what is returned by the non testing path
     *  the non testing path uses $CI->uri->uri->segment_array());
     *  which does not include ci/ but is indexed starting at 1
     *  the 'ci' is added here to pad the array
     */
    function testSamlUrlArgumentHandling()
    {
        //nothing specified
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        //subject specified
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.login'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/Contact.Login'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);   //strtolower and conversion to define happens later
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.emails.address'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_EMAIL);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.id'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_ID);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        //redirect specified
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/redirect/app/answers/list'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], 'app/answers/list');
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/redirect/ci/social/ssoRedirect'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], 'ci/social/ssoRedirect');
        $this->assertEqual($results['customFieldName'], null);
        //redirect must begin with ci, app, or cc
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/redirect/badPlace/answers/list'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        //both parameters specified
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.emails.address/redirect/app/answers/list'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_EMAIL);
        $this->assertEqual($results['redirect'], 'app/answers/list');
        $this->assertEqual($results['customFieldName'], null);
        //I feel like enforcing correct spelling of parameters. Parameters I
        //don't recognize are effectively ignored
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/Subject/contact.emails.address'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/Redirect/app/ask'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/sillyPants/yes/deedle/app/ask'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_LOGIN);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);

        //Unknown subject parameters shouldn't have a default
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.email'));
        $this->assertEqual($results['subject'], null);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.c_id'));
        $this->assertEqual($results['subject'], null);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/foo'));
        $this->assertEqual($results['subject'], null);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], null);

        //subject = "CustomFieldName" cases
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.customfields.c$soFlexible'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_CF);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], 'c$soflexible');
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.customfields.soFlexible'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_CF);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], 'c$soflexible');
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.customfields.coolnessFactor/redirect/app/ask'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_CF);
        $this->assertEqual($results['redirect'], 'app/ask');
        $this->assertEqual($results['customFieldName'], 'c$coolnessfactor');
        //custom field names that match other possible subject values
        $results = RightNow\Controllers\OpenLogin::_interpretSamlArguments(explode('/', 'ci/openlogin/saml/subject/contact.customfields.c$id'));
        $this->assertEqual($results['subject'], SSO_TOKEN_SUBJECT_CF);
        $this->assertEqual($results['redirect'], Url::getShortEufBaseUrl('sameAsRequest', '/app/home'));
        $this->assertEqual($results['customFieldName'], 'c$id');
    }

    function testSamlError(){
        \Rnow::updateConfig('SAML_ERROR_URL', '');

        $expectedLocation = function($configSetting, $urlParameters, $errorCode = 14) {
            $parameters = $urlParameters;
            switch ($configSetting) {
                case 0:
                    $expectedOutput = "Location: /app/error/error_id/saml18{$parameters} [following]";
                    break;
                case 1:
                    $parameters = $parameters ?: '/';
                    $expectedOutput = "Location: /app/home/error/$errorCode/session{$parameters} [following]";
                    break;
                case 2:
                    $parameters = $parameters ?: '/';
                    $expectedOutput = "Location: /app/home/error/$errorCode/session/{$parameters} [following]";
                    break;
            }

            return $expectedOutput;
        };
        $httpCode = '302 Moved Temporarily';

        $url = "/ci/openlogin/saml";
        $parameters = "/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(0, $parameters)));

        $url = "/ci/openlogin/saml";
        $parameters = "/subject/contact.login/redirect/app%2Fhome/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(0, $parameters)));

        \Rnow::updateConfig('SAML_ERROR_URL', '/app/home/error/%error_code%/session/%session%');

        $url = "/ci/openlogin/saml";
        $parameters = "/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(1, $parameters)));

        $url = "/ci/openlogin/saml";
        $parameters = "/subject/contact.login/redirect/app%2Fhome/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(1, $parameters)));

        $url = "/ci/openlogin/saml";
        $parameters = '';
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(1, $parameters)));

        \Rnow::updateConfig('SAML_ERROR_URL', '/app/home/error/%error_code%/session/%session%/');

        $url = "/ci/openlogin/saml";
        $parameters = "/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(2, $parameters)));

        $url = "/ci/openlogin/saml";
        $parameters = "/subject/contact.login/redirect/app%2Fhome/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(2, $parameters)));

        $url = "/ci/openlogin/saml";
        $parameters = '';
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(2, $parameters)));

        $url = "/ci/openlogin/saml/subject/foo";
        $output =$this->makeRequest($url, array('justHeaders' => true, 'flags' => "--post-data 'SAMLResponse=fsdfsdfsf'"));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(2, '/subject/foo', 18)));

        \Rnow::updateConfig('SAML_ERROR_URL', '');

        $url = "/ci/openlogin/saml";
        $parameters = "/foo/bar/banana/no";
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true, 'flags' => "--post-data 'SAMLResponse=fsdfsdfsf'"));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(0, $parameters, 17)));

        $url = "/ci/openlogin/saml";
        $parameters = '';
        $output =$this->makeRequest($url . $parameters, array('justHeaders' => true, 'flags' => "--post-data 'SAMLResponse=fsdfsdfsf'"));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(0, $parameters, 17)));

        $url = "/ci/openlogin/saml/";
        $output =$this->makeRequest($url, array('justHeaders' => true, 'flags' => "--post-data 'SAMLResponse=fsdfsdfsf'"));
        $this->assertStatusCode($output, $httpCode);
        $this->assertTrue(Text::stringContains($output, $expectedLocation(0, $parameters, 17)));
    }
}

class ClientTest extends CPTestCase {
    # code...
}

/**
* Captures args to called methods in public {nameOfMethod}CalledWith property
* and returns value specified in public {nameOfMethod}Returns property
*/
class MockClient extends Controllers\Client {
    function get($url, $headers = null, &$statusCode = null) {
        if ($this->statusCode) {
            $statusCode = $this->statusCode;
        }

        return $this->captureCall(__FUNCTION__, func_get_args());
    }
    function post($url, $data, &$statusCode = null) { return $this->captureCall(__FUNCTION__, func_get_args()); }
    function redirectBackToCPPage($pageUrl = null) { return $this->captureCall(__FUNCTION__, func_get_args()); }
    function redirectToSamlErrorUrl($errorID, $urlParametersToPersist = '') { return $this->captureCall(__FUNCTION__, func_get_args()); }
    function redirectToThirdPartyLogin($url, $parameters = null) { return $this->captureCall(__FUNCTION__, func_get_args()); }
    function setCookie($name, $value) { return $this->captureCall(__FUNCTION__, func_get_args()); }

    private function captureCall($name, $args) {
        $calledWith = "{$name}CalledWith";
        $this->$calledWith = $args;
        $toReturn = "{$name}Returns";

        return $this->$toReturn;
    }
}
