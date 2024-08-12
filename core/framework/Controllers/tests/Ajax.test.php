<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Utils\Url,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Controllers\UnitTest\PhpFunctional,
    RightNow\UnitTest\Helper;

class AjaxControllerTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Ajax';

    function createPostParams(array $postParams = array()) {
        $timestamp = $postParams['rn_timestamp'] ?: time();
        $encodedContextData = base64_encode(json_encode(array('tree' => 'pine')));
        $contextDataHash = Api::ver_ske_encrypt_fast_urlsafe(sha1($encodedContextData . $timestamp));

        return array_merge(array(
            'rn_contextData' => $encodedContextData,
            'rn_contextToken' => $contextDataHash,
            'rn_timestamp' => $timestamp
        ), $postParams);
    }

    function createWidgetTokenParams($wid = 0) {
        return array(
            'rn_formToken' => \RightNow\Utils\Framework::createTokenWithExpiration($wid),
            'w_id' => $wid
        );
    }
    
    function createWidgetFormTokenParams($wid = 0) {
        return array(
            'rn_formToken' => \RightNow\Utils\Framework::createTokenWithExpiration($wid),
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            'w_id' => $wid
        );
    }

    function testWidget() {
        $base = "/ci/ajax/widget";
        $sessionCookie = get_instance()->sessionCookie;
        $response = $this->makeRequest("$base/custom/foo/bar/Handler/session/L3RpbWUvMTMzNTk4ODAyOS9zaWQvZExvZ2c3WGs%3D");
        // Session is ignored
        $this->assertFalse(Text::stringContains($response, 'session'));
        // instance ID isn't present
        $this->assertTrue(Text::stringContains($response, 'w_id'));
        // Widget doesn't exist
        $response = $this->makeRequest("$base/custom/foo/bar/handler", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetTokenParams(12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertFalse(Text::stringContains($response, 'w_id'));
        $this->assertTrue(Text::stringContains($response, 'errors'));
        $this->assertTrue(Text::stringContains($response, 'externalMessage'));

        // Invalid handler
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswersss", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/cc/bananas'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, 'errors'));
        $this->assertTrue(Text::stringContains($response, 'externalMessage'));

        // Valid referrer
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, 'errors'));
        $this->assertTrue(Text::stringContains($response, 'externalMessage'));

        // Valid referrer but not an AJAX request
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/')),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, '"externalMessage":"We\'re sorry, but that action cannot be completed at this time. Please refresh your page and try again."'));

        // Invalid referrer
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => 'http://google.com', 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, 'errors'));
        $this->assertTrue(Text::stringContains($response, 'externalMessage'));

        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => 'bananas', 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, 'errors'));
        $this->assertTrue(Text::stringContains($response, 'externalMessage'));

        // Invalid referrer when logging out still returns success
        $response = $this->makeRequest("$base/standard/login/LogoutLink/doLogout", array(
            'post' => http_build_query($this->createPostParams($this->createWidgetFormTokenParams(12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/')),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains(strtolower($response), '"success":1'));

        // Invalid context data
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetFormTokenParams(12), array('rn_contextData' => 'chair')))),
            'headers' => array('RNT_REFERRER' => 'bananas', 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains(strtolower($response), 'that action cannot be completed'));

        // Invalid context token
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetFormTokenParams(12), array('rn_contextToken' => 'patio')))),
            'headers' => array('RNT_REFERRER' => 'bananas', 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains(strtolower($response), 'that action cannot be completed'));

        // Timestamp older than 24 hours
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetFormTokenParams(12), array('rn_timestamp' => strtotime("-25 hours"))))),
            'headers' => array('RNT_REFERRER' => 'bananas', 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains(strtolower($response), 'that action cannot be completed'));

        //Valid form token
        $sessionCookie = get_instance()->sessionCookie;
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12);
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetFormTokenParams(12),array('to'=>'b.b.invalid@invalid.invalid', 'name'=>'Banana', 'from'=>'banana@invalid.invalid', 'a_id'=>"1")))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains(strtolower($response), '"result":true'));
        
        //Without f_tok
        $sessionCookie = get_instance()->sessionCookie;
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12);
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetTokenParams(12),array('to'=>'b.b.invalid@invalid.invalid', 'name'=>'Banana', 'from'=>'banana@invalid.invalid', 'a_id'=>"1")))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, '"externalMessage":"We\'re sorry, but that action cannot be completed at this time. Please refresh your page and try again."'));

        //Invalid form token
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12).'invalid';
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams($this->createPostParams(array('rn_formToken' => $formToken, 'w_id' => 12)))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, '"externalMessage":"We\'re sorry, but that action cannot be completed at this time. Please refresh your page and try again."'));

        //Invalid form token created using wrong widget id
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(10);
        $response = $this->makeRequest("$base/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array('rn_formToken' => $formToken, 'w_id' => 12))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$sessionCookie}",
        ));
        $this->assertTrue(Text::stringContains($response, '"externalMessage":"We\'re sorry, but that action cannot be completed at this time. Please refresh your page and try again."'));
    }

    function testCheckFormToken() {
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration('2');
        $methodGetContextData = $this->getMethod('_getContextData');
        $method = $this->getMethod('_checkFormToken');
        $originalPost = $_POST;

        //valid form token
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12);
        $_POST = array_merge($_POST, $this->createPostParams($this->createWidgetFormTokenParams(12)));
        $contextData = $methodGetContextData();
        $this->assertTrue($method('newComment', $contextData, 'standard/discussion/QuestionComments', 12));

        //invalid valid form token
        $_POST = $originalPost;
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12).'invalid';
        $_POST = array_merge($_POST, $this->createPostParams(array('rn_formToken' => $formToken, 'w_id' => 12)));
        $contextData = $methodGetContextData();
        $this->assertFalse($method('newComment', $contextData, 'standard/discussion/QuestionComments', 12));

        //no form token
        $_POST = $originalPost;
        $_POST = array_merge($_POST, $this->createPostParams(array('w_id' => 12)));
        $contextData = $methodGetContextData();
        $this->assertFalse($method('newComment', $contextData, 'standard/discussion/QuestionComments', 12));

        //f_tok present
        $_POST = $originalPost;
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $_POST = array_merge($_POST, $this->createPostParams(array('f_tok' => $f_tok, 'w_id' => 12)));
        $contextData = $methodGetContextData();
        //if f_tok present in POST, w_id is set to 0
        $this->assertTrue($method('newComment', $contextData, 'standard/discussion/QuestionComments', 12));

        //f_tok present, but associated with a widget ID
        $_POST = $originalPost;
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(12);
        $_POST = array_merge($_POST, $this->createPostParams(array('f_tok' => $f_tok, 'w_id' => 12)));
        $contextData = $methodGetContextData();
        //if f_tok present in POST, w_id is set to 0
        $this->assertFalse($method('newComment', $contextData, 'standard/discussion/QuestionComments', 12));
        $_POST = $originalPost;
    }

    function testIsLoginRequired() {
        $contextData = array('login_required' => array('newComment' => true));
        $method = $this->getMethod('_loginRequired');
        $this->assertTrue($method('newComment', $contextData));
    }

    function testAjaxRequestWithLoginRequiredField() {
        $base = "/ci/ajax/widget";
        $timestamp = time();
        $encodedContextData = base64_encode(json_encode(array('login_required' => array('newComment' => true))));
        $contextDataHash = Api::ver_ske_encrypt_fast_urlsafe(sha1($encodedContextData . $timestamp));
        $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(12);

        $postData = array(
            'rn_contextData' => $encodedContextData,
            'rn_contextToken' => $contextDataHash,
            'rn_timestamp' => $timestamp,
            'rn_formToken' => \RightNow\Utils\Framework::createTokenWithExpiration(12),
            'w_id' => 12
        );

        $response = $this->makeRequest("$base/standard/discussion/QuestionComments/newComment", array(
            'post' => http_build_query($postData),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session=invalid",
        ), false);
        $this->assertTrue(Text::stringContains($response, '"errorCode":"ERROR_USER_NOT_LOGGED_IN"'));
    }

    function testGetContextData() {
        $method = $this->getMethod('_getContextData');

        $originalPost = $_POST;
        $_POST = array_merge($_POST, $this->createPostParams()); // other cases captured in #testWidget above
        $result = $method();
        $this->assertSame($result, array('tree' => 'pine'));
        $_POST = $originalPost;
    }

    function testCleanUpPostParams() {
        $method = $this->getMethod('_cleanUpPostParams');

        $originalPost = $_POST;
        $_POST = array_merge($_POST, $this->createPostParams());
        $method();
        $this->assertSame($originalPost, $_POST);
        $_POST = $originalPost;
    }

    function testWidgetMethodExists() {
        require_once CORE_WIDGET_FILES . 'standard/utils/Blank/controller.php';

        $widget = new \RightNow\Widgets\Blank(array());
        $method = $this->getMethod('_widgetMethodExists');

        $this->assertFalse($method($widget, 'fable'));
        $this->assertTrue($method($widget, 'getData'));
    }

    function testInsertClickstreamAction() {
        $method = $this->getMethod('_insertClickstreamAction');

        $this->assertSame('kitchen', $method(array(), 'kitchen'));
        $this->assertSame('kitchen', $method(array('clickstream' => array('kitchen' => null)), 'kitchen'));
        $this->assertSame('table', $method(array('clickstream' => array('kitchen' => 'table')), 'kitchen'));
    }

    function testCallStaticMethod() {
        $method = $this->getMethod('_callStaticMethod');
        $params = array('kitchen' => 'table');

        $widget = new WidgetForAjaxTests;
        $this->assertTrue($method($widget, 'staticMethod', $params));
        $this->assertIdentical(array($params), WidgetForAjaxTests::$calledWith);

        WidgetForAjaxTests::$calledWith = null;
        $this->assertFalse($method($widget, 'instanceMethod', $params));
        $this->assertNull(WidgetForAjaxTests::$calledWith);

        $widget = 'WidgetForAjaxTests';
        $this->assertTrue($method($widget, 'staticMethod', $params));
        $this->assertIdentical(array($params), WidgetForAjaxTests::$calledWith);

        WidgetForAjaxTests::$calledWith = null;
        $this->assertFalse($method($widget, 'instanceMethod', $params));
        $this->assertNull(WidgetForAjaxTests::$calledWith);
    }

    function testCallInstanceMethodWithDefaultAttributes() {
        $method = $this->getMethod('_callInstanceMethod');
        $params = array('kitchen' => 'table');

        Mock::generate('WidgetForAjaxTests', 'MockWidgetForAjaxTests');
        $mock = new MockWidgetForAjaxTests;
        $mock->expectOnce('setInfo', array(array(
            'name' => 'pine',
            'w_id' => 'X',
        )));
        $mock->expectNever('setAttributes');
        $mock->expectOnce('setPath', array('name/pine'));
        $mock->expectOnce('initDataArray');
        $mock->expectOnce('instanceMethod', array(array('param' => 1)));

        $method($mock, 'instanceMethod', array(
            'info' => array('attributes' => array()),
            'path' => 'name/pine',
            'id'   => 'X',
        ), array('param' => 1));
    }

    function testCallInstanceMethodWithNonDefaultAttributes() {
        $method = $this->getMethod('_callInstanceMethod');
        $params = array('kitchen' => 'table');

        Mock::generate('WidgetForAjaxTests', 'MockWidgetForAjaxTests');
        $mock = new MockWidgetForAjaxTests;
        $mock->expectOnce('setInfo', array(array(
            'name' => 'pine',
            'w_id' => 'X',
        )));
        $mock->expectOnce('setAttributes', array(array('storm')));
        $mock->expectOnce('setPath', array('name/pine'));
        $mock->expectOnce('initDataArray');
        $mock->expectOnce('instanceMethod', array(array('param' => 1)));

        $method($mock, 'instanceMethod', array(
            'info' => array('attributes' => array(), 'nonDefaultAttrValues' => array('storm')),
            'path' => 'name/pine',
            'id'   => 'X',
        ), array('param' => 1));
    }

    // QA 200728-000051 w_id XSS found in penetration test
    function testPreventXSSInWID( ){
        $base = "/ci/ajax/widget";
        $widgetID = 12;
        $timestamp = time();
        $encodedContextData = base64_encode(json_encode(array('login_required' => array('newComment' => true))));
        $contextDataHash = Api::ver_ske_encrypt_fast_urlsafe(sha1($encodedContextData . $timestamp));
        $user = "slatest";
        $cookies = Helper::logInUser($user);
        $this->logIn($user);

        // removing any of the POST data will give a false result by reaching the 
        // generic error message before the w_id is checked
        $postData = array(
            'rn_contextData' => $encodedContextData,
            'rn_contextToken' => $contextDataHash,
            'rn_timestamp' => $timestamp,
            'rn_formToken' => \RightNow\Utils\Framework::createTokenWithExpiration($widgetID),
            'w_id' => $widgetID . "bdda6%22onmouseover%3d%22alert(1)%22s"
        );

        $response = $this->makeRequest("$base/standard/discussion/QuestionComments/newComment", array(
            'post' => http_build_query($postData),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$cookies['session']}; cp_profile={$cookies['profile']}",
        ), false);

        // if this fix isn't working, we'll get an error message about an invalid community ID instead
        $this->assertTrue(Text::stringContains($response, \RightNow\Utils\Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG)));

        // try it again with a malicious ID that has already been decoded
        $postData['w_id'] = $widgetID . 'bdda6"onmouseover="alert(1)"s';

        $response = $this->makeRequest("$base/standard/discussion/QuestionComments/newComment", array(
            'post' => http_build_query($postData),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session={$cookies['session']}; cp_profile={$cookies['profile']}",
        ), false);
        $this->assertTrue(Text::stringContains($response, \RightNow\Utils\Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG)));

        // clean up
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
    }

    // QA 201014-000029 need to allow non-numeric w_ids such as rn_SourceResultListing_31
    function testAllowValidStringsInWID( ){
        $widgetID = "rn_SourceResultListing_31";

        $response = $this->makeRequest("/ci/ajax/widget/standard/utils/EmailAnswerLink/emailAnswer", array(
            'post' => http_build_query($this->createPostParams(array_merge($this->createWidgetFormTokenParams(12),array('to'=>'b.b.invalid@invalid.invalid', 'name'=>'Banana', 'from'=>'banana@invalid.invalid', 'a_id'=>"1")))),
            'headers' => array('RNT_REFERRER' => Url::getShortEufBaseUrl(null, '/app/'), 'X_REQUESTED_WITH' => 'xmlhttprequest'),
            'cookie' => "cp_session=" . get_instance()->sessionCookie
        ), false);

        // we should succeed despite using a string for the w_id
        $this->assertTrue(Text::stringContains(strtolower($response), '"result":true'));
    }
}

class WidgetForAjaxTests {
    public static $calledWith = null;

    function setInfo () {}
    function setAttributes () {}
    function setPath () {}
    function initDataArray () {}
    function instanceMethod () {}
    function setHelper () {}
    static function staticMethod () {
        self::$calledWith = func_get_args();
    }
}
