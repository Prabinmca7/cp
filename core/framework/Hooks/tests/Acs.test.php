<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class AcsTest extends CPTestCase
{
    function __construct(){
        $this->class = new ReflectionClass('RightNow\Hooks\Acs');
        $this->instance = $this->class->newInstance();
        $this->CI = get_instance();
    }

    function setUp(){
        $this->parameterSegment = $this->CI->config->item('parm_segment');
        parent::setUp();
    }

    function tearDown(){
        $this->CI->config->set_item('parm_segment', $this->parameterSegment);
        parent::tearDown();
    }

    function setInstanceProperty($propertyName, $propertyValue){
        $property = $this->class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($this->instance, $propertyValue);
    }

    function callInstanceMethod($methodName, $arguments = array()){
        if(!is_array($arguments)){
            $arguments = array($arguments);
        }
        $method = $this->class->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->instance, $arguments);
    }

    function setPropertiesForMockData($url, $parameterSegmentIndex){
        $segmentArray = explode("/", $url);
        $this->CI->router->segments = $segmentArray;
        $this->CI->router->directory = '';
        $this->CI->router->setUriData();
        $this->CI->config->set_item('parm_segment', $parameterSegmentIndex);
        $pageSegments = array_slice($segmentArray, 2, $parameterSegmentIndex - 3);
        $this->CI->page = implode("/", $pageSegments);
        if($segmentArray[0] === 'page'){
            $this->setInstanceProperty('controllerName', 'page');
            $this->setInstanceProperty('controllerFunction', 'render');
        }
        else if($segmentArray[0] === 'facebook'){
            $this->setInstanceProperty('controllerName', 'facebook');
            $this->setInstanceProperty('controllerFunction', 'render');
        }
        else{
            $this->setInstanceProperty('controllerName', $segmentArray[0]);
            $this->setInstanceProperty('controllerFunction', $segmentArray[1]);
        }
    }

    function testGetUrlWithWhitelistedParameters(){

        //Page controller
        $this->setPropertiesForMockData('page/render/home/kw/abcdef', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/home/kw/abcdef", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/home/username/John/kw/abcdef', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/home/kw/abcdef", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/list/session/abcdef', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/list", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/list/session_id/abcdef', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/list", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/ask_confirm/i_id/12', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/ask_confirm", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/ask_confirm/refno/111209-000001', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/ask_confirm", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/utils/login_form/c_id/12', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/utils/login_form", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/list/session/abcdef/kw/test/i_id/12/p/1/username/jdoe/org/3/c_id/88/c/73/session_id/abc/search/1', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/list/kw/test/p/1/org/3/c/73/search/1", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/list/foo/bar/geagehew/asease/a/1/hes/3asgset', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/list", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/ask/contacts.email/eturner@rightnow.com', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/ask", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/error/error_id/7', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/error/error_id/7", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/home/a_id/12/a_id/13', 4);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/home/a_id/13", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/detail/a_id/13/comment/6', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/detail/a_id/13/comment/6", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/detail/a_id/13/g_id/44', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/detail/a_id/13/g_id/44", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/answers/detail/a_id/13/guideID/12', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/answers/detail/a_id/13/guideID/12", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/social/questions/detail/qid/3', 6);
        $this->assertEqual($_SERVER['HTTP_HOST'] . '/app/social/questions/detail/qid/3', $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/chat/chat_landing/pac/1', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/chat/chat_landing/pac/1", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        //Controller endpoints
        $this->setPropertiesForMockData('pta/login/p_li/abdcef/redirect/home', 3);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/ci/pta/login", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('tags/widgets/standard/search/KeywordText2', 3);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/ci/tags/widgets", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('ajaxRequest/getHierValues/filter/products/lvl/2/id/1/linking/0', 3);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/ci/ajaxRequest/getHierValues", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        //Length tests
        $this->setPropertiesForMockData('page/render/home/kw/' . str_repeat("a", \RightNow\ActionCapture::CLEAN_URL_MAX_LENGTH), 4);
        $this->assertTrue(strlen($this->callInstanceMethod('getUrlWithWhitelistedParameters')) === \RightNow\ActionCapture::CLEAN_URL_MAX_LENGTH);

        //Encoded path tests
        $this->setPropertiesForMockData('page/render/new folder/home', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/new+folder/home", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/new%folder/home', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/new%25folder/home", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/new=folder/home', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/new%3Dfolder/home", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/new?folder/home', 5);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/new%3Ffolder/home", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        $this->setPropertiesForMockData('page/render/new/folder/home', 6);
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/app/new/folder/home", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));

        //Custom controller named 'page'
        $this->setPropertiesForMockData('page/render/home', 4);
        $this->CI->router->foundControllerInCpCore = false;
        $this->assertEqual($_SERVER['HTTP_HOST'] . "/ci/page/render", $this->callInstanceMethod('getUrlWithWhitelistedParameters'));
        $this->CI->router->foundControllerInCpCore = true;
    }

    function testIgnoredControllers() {
        // There are certain controllers and controller+action combinations where we don't
        // want ACS to be initialized for. #initialize should return `false` for those.

        $this->setPropertiesForMockData('ajaxrequestmin/getCountryValues', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('browserSearch/wat', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('DQA/publish', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('designer/designstuff', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('inlineimage/something.png', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('inlineimg/something.png', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('redirect/to', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('webdav/cp', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('pta/logout', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('pta/login', 3);
        $this->assertTrue($this->callInstanceMethod('initialize'));

        $this->setPropertiesForMockData('page/render/answers/detail/a_id/13/g_id/44', 5);
        $this->assertTrue($this->callInstanceMethod('initialize'));

        //@@@ QA 130409-000021
        $this->setPropertiesForMockData('ajaxRequest/getChatQueueAndInformation', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));
        $this->setPropertiesForMockData('cache/rss', 3);
        $this->assertFalse($this->callInstanceMethod('initialize'));
    }
}
